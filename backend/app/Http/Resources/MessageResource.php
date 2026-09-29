<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MessageResource extends JsonResource
{
    /**
     * Transform the message into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $sender = $this->sender;

        return [
            'id' => $this->id,
            'conversation_id' => $this->chat_id,
            'chat_id' => $this->chat_id,
            'sender_id' => $this->sender_id,
            'sender_name' => $sender->name ?? 'User',
            'sender_avatar' => $sender && $sender->avatar ? asset('storage/' . ltrim($sender->avatar, '/')) : null,
            'message' => $this->message_body,
            'message_body' => $this->message_body,
            'message_type' => $this->message_type ?? 'text',
            'attachment_url' => $this->attachment_path ? asset('storage/' . ltrim($this->attachment_path, '/')) : null,
            'latitude' => $this->latitude ? (float) $this->latitude : null,
            'longitude' => $this->longitude ? (float) $this->longitude : null,
            'is_read' => (bool) $this->is_read,
            'read_at' => $this->read_at?->toIso8601String(),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
