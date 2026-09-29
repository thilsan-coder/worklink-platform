<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Chat;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminConversationController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Chat::query()
            ->with(['job', 'customer', 'worker'])
            ->withCount('messages');

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->whereHas('customer', fn($cq) => $cq->where('name', 'like', "%{$search}%"))
                    ->orWhereHas('worker', fn($wq) => $wq->where('name', 'like', "%{$search}%"))
                    ->orWhereHas('job', fn($jq) => $jq->where('title', 'like', "%{$search}%"));
            });
        }

        $perPage = min(max((int) $request->get('per_page', 15), 1), 100);
        $conversations = $query->orderBy('last_message_at', 'desc')->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => $conversations,
        ]);
    }

    public function show(int $id, Request $request): JsonResponse
    {
        $chat = Chat::with(['job', 'customer', 'worker'])->findOrFail($id);

        $perPage = min(max((int) $request->get('per_page', 50), 1), 100);
        $messages = $chat->messages()->with('sender')->orderBy('created_at', 'asc')->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => [
                'conversation' => $chat,
                'messages' => $messages,
            ],
        ]);
    }
}
