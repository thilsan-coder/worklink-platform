<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Chat;
use App\Models\Message;
use App\Models\Notification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ChatController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        $chats = Chat::with(['job', 'customer', 'worker', 'messages' => function ($q) {
            $q->latest()->limit(1);
        }])
        ->where('customer_id', $user->id)
        ->orWhere('worker_id', $user->id)
        ->latest('updated_at')
        ->get();

        return response()->json([
            'success' => true,
            'data' => $chats,
        ]);
    }

    public function messages(int $chatId, Request $request): JsonResponse
    {
        $chat = Chat::findOrFail($chatId);
        $user = $request->user();

        if ($chat->customer_id !== $user->id && $chat->worker_id !== $user->id) {
            return response()->json(['success' => false, 'message' => 'Unauthorized.'], 403);
        }

        $messages = Message::where('chat_id', $chatId)
            ->with('sender')
            ->orderBy('created_at', 'asc')
            ->paginate($request->integer('per_page', 50));

        // Mark unread messages as read
        Message::where('chat_id', $chatId)
            ->where('sender_id', '!=', $user->id)
            ->where('is_read', false)
            ->update([
                'is_read' => true,
                'read_at' => now(),
            ]);

        return response()->json([
            'success' => true,
            'data' => $messages,
        ]);
    }

    public function sendMessage(int $chatId, Request $request): JsonResponse
    {
        $request->validate([
            'message_type' => 'required|in:text,image,location',
            'message_body' => 'nullable|string',
            'attachment' => 'nullable|file|mimes:jpg,jpeg,png,webp|max:5120',
            'latitude' => 'nullable|numeric',
            'longitude' => 'nullable|numeric',
        ]);

        $chat = Chat::findOrFail($chatId);
        $user = $request->user();

        if ($chat->customer_id !== $user->id && $chat->worker_id !== $user->id) {
            return response()->json(['success' => false, 'message' => 'Unauthorized.'], 403);
        }

        $attachmentPath = null;
        if ($request->hasFile('attachment')) {
            $attachmentPath = $request->file('attachment')->store('chat_attachments', 'public');
        }

        $message = Message::create([
            'chat_id' => $chat->id,
            'sender_id' => $user->id,
            'message_type' => $request->message_type,
            'message_body' => $request->message_body,
            'attachment_path' => $attachmentPath,
            'latitude' => $request->latitude,
            'longitude' => $request->longitude,
            'is_read' => false,
        ]);

        $chat->touch();

        // Recipient user ID
        $recipientId = ($chat->customer_id === $user->id) ? $chat->worker_id : $chat->customer_id;

        Notification::create([
            'user_id' => $recipientId,
            'title' => 'New Message',
            'body' => $user->name . ': ' . Str::limit($request->message_body ?? 'Sent an attachment', 50),
            'type' => 'chat_message',
            'reference_id' => $chat->job_id,
        ]);

        return response()->json([
            'success' => true,
            'data' => $message->load('sender'),
        ], 201);
    }
}
