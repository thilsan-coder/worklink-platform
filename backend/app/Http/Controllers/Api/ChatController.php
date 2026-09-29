<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ConversationResource;
use App\Http\Resources\MessageResource;
use App\Models\Chat;
use App\Models\Job;
use App\Models\Message;
use App\Models\Notification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ChatController extends Controller
{
    /**
     * List all conversations for authenticated customer or worker.
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        $chats = Chat::with(['job', 'customer', 'worker', 'messages' => function ($q) {
            $q->latest('created_at')->limit(1);
        }])
        ->where(function ($q) use ($user) {
            $q->where('customer_id', $user->id)
              ->orWhere('worker_id', $user->id);
        })
        ->latest('updated_at')
        ->get();

        return response()->json([
            'success' => true,
            'data' => ConversationResource::collection($chats),
        ]);
    }

    /**
     * Get or create conversation for a specific job.
     */
    public function forJob(int $jobId, Request $request): JsonResponse
    {
        $job = Job::findOrFail($jobId);
        $user = $request->user();

        if ($job->customer_id !== $user->id && $job->worker_id !== $user->id && $user->role !== 'admin') {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized to access conversation for this job.',
            ], 403);
        }

        $chat = Chat::firstOrCreate(
            ['job_id' => $job->id],
            [
                'customer_id' => $job->customer_id,
                'worker_id' => $job->worker_id,
                'is_active' => true,
            ]
        );

        $chat->load(['job', 'customer', 'worker', 'messages' => function ($q) {
            $q->latest('created_at')->limit(1);
        }]);

        return response()->json([
            'success' => true,
            'data' => new ConversationResource($chat),
        ]);
    }

    /**
     * Retrieve paginated message history for a conversation.
     */
    public function messages(int $chatId, Request $request): JsonResponse
    {
        $chat = Chat::findOrFail($chatId);
        $user = $request->user();

        if ($chat->customer_id !== $user->id && $chat->worker_id !== $user->id && $user->role !== 'admin') {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized access to conversation messages.',
            ], 403);
        }

        $perPage = $request->integer('per_page', 50);
        $messages = Message::where('chat_id', $chat->id)
            ->with('sender')
            ->orderBy('created_at', 'asc')
            ->paginate($perPage);

        // Mark incoming messages as read
        Message::where('chat_id', $chat->id)
            ->where('sender_id', '!=', $user->id)
            ->where('is_read', false)
            ->update([
                'is_read' => true,
                'read_at' => now(),
            ]);

        return response()->json([
            'success' => true,
            'data' => MessageResource::collection($messages)->response()->getData(true),
        ]);
    }

    /**
     * Send a message in a conversation.
     */
    public function sendMessage(int $chatId, Request $request): JsonResponse
    {
        $chat = Chat::findOrFail($chatId);
        $user = $request->user();

        if ($chat->customer_id !== $user->id && $chat->worker_id !== $user->id && $user->role !== 'admin') {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized to send message in this conversation.',
            ], 403);
        }

        $request->validate([
            'message' => 'required_without:message_body|nullable|string|max:2000',
            'message_body' => 'required_without:message|nullable|string|max:2000',
            'message_type' => 'nullable|in:text,image,location,TEXT,IMAGE,LOCATION',
            'attachment' => 'nullable|file|mimes:jpg,jpeg,png,webp|max:5120',
            'latitude' => 'nullable|numeric',
            'longitude' => 'nullable|numeric',
        ]);

        $messageText = trim($request->input('message', $request->input('message_body', '')));
        if (empty($messageText) && !$request->hasFile('attachment') && !$request->filled('latitude')) {
            return response()->json([
                'success' => false,
                'message' => 'Message content cannot be empty.',
            ], 422);
        }

        $attachmentPath = null;
        if ($request->hasFile('attachment')) {
            $attachmentPath = $request->file('attachment')->store('chat_attachments', 'public');
        }

        $type = strtolower($request->input('message_type', 'text'));

        $message = Message::create([
            'chat_id' => $chat->id,
            'sender_id' => $user->id,
            'message_type' => $type,
            'message_body' => $messageText,
            'attachment_path' => $attachmentPath,
            'latitude' => $request->latitude,
            'longitude' => $request->longitude,
            'is_read' => false,
        ]);

        $chat->update(['last_message_at' => now()]);
        $chat->touch();

        // Notify counter-party
        $recipientId = ($chat->customer_id === $user->id) ? $chat->worker_id : $chat->customer_id;
        Notification::create([
            'user_id' => $recipientId,
            'title' => 'New message from ' . $user->name,
            'body' => Str::limit($messageText ?: 'Sent an attachment', 60),
            'type' => 'chat_message',
            'reference_id' => $chat->id,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Message sent successfully.',
            'data' => new MessageResource($message->load('sender')),
        ], 201);
    }

    /**
     * Mark a specific message or conversation as read.
     */
    public function markAsRead(int $messageId, Request $request): JsonResponse
    {
        $message = Message::with('chat')->findOrFail($messageId);
        $user = $request->user();

        if ($message->chat->customer_id !== $user->id && $message->chat->worker_id !== $user->id) {
            return response()->json(['success' => false, 'message' => 'Unauthorized.'], 403);
        }

        if ($message->sender_id !== $user->id && ! $message->is_read) {
            $message->update([
                'is_read' => true,
                'read_at' => now(),
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Message marked as read.',
            'data' => new MessageResource($message),
        ]);
    }

    /**
     * Mark all unread messages in a conversation as read.
     */
    public function markConversationAsRead(int $chatId, Request $request): JsonResponse
    {
        $chat = Chat::findOrFail($chatId);
        $user = $request->user();

        if ($chat->customer_id !== $user->id && $chat->worker_id !== $user->id && $user->role !== 'admin') {
            return response()->json(['success' => false, 'message' => 'Unauthorized.'], 403);
        }

        Message::where('chat_id', $chat->id)
            ->where('sender_id', '!=', $user->id)
            ->where('is_read', false)
            ->update([
                'is_read' => true,
                'read_at' => now(),
            ]);

        return response()->json([
            'success' => true,
            'message' => 'Conversation messages marked as read.',
        ]);
    }
}
