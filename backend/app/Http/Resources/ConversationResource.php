<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ConversationResource extends JsonResource
{
    /**
     * Transform the conversation into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $user = $request->user();
        $otherUser = ($user && $this->customer_id === $user->id) ? $this->worker : $this->customer;
        $latestMessage = $this->messages->first() ?? $this->messages()->latest('created_at')->first();

        $unreadCount = $user
            ? $this->messages()->where('sender_id', '!=', $user->id)->where('is_read', false)->count()
            : 0;

        return [
            'id' => $this->id,
            'conversation_id' => $this->id,
            'chat_id' => $this->id,
            'job_id' => $this->job_id,
            'job_title' => $this->job->title ?? 'Job Request',
            'job_status' => $this->job->status ?? 'REQUESTED',
            'other_participant' => [
                'id' => $otherUser->id ?? null,
                'name' => $otherUser->name ?? 'User',
                'avatar' => $otherUser && $otherUser->avatar ? asset('storage/' . ltrim($otherUser->avatar, '/')) : null,
                'role' => $otherUser->role ?? 'user',
            ],
            'latest_message' => $latestMessage ? [
                'id' => $latestMessage->id,
                'message' => $latestMessage->message_body,
                'message_type' => $latestMessage->message_type ?? 'text',
                'sender_id' => $latestMessage->sender_id,
                'created_at' => $latestMessage->created_at?->toIso8601String(),
            ] : null,
            'unread_count' => $unreadCount,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
