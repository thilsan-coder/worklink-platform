<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class NotificationResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $data = $this->data ?? [];

        // Determine related entity type and id
        $relatedType = $data['entity_type'] ?? null;
        $relatedId = $data['entity_id'] ?? $this->reference_id ?? null;

        if (! $relatedType) {
            $upperType = strtoupper($this->type ?? '');
            if (str_starts_with($upperType, 'JOB_') || in_array($this->type, ['job_request', 'job_status'])) {
                $relatedType = 'job';
            } elseif (str_contains($upperType, 'MESSAGE') || $this->type === 'chat_message') {
                $relatedType = 'chat';
            } elseif (str_contains($upperType, 'REVIEW') || $this->type === 'review_received') {
                $relatedType = 'review';
            }
        }

        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'type' => $this->type,
            'title' => $this->title,
            'message' => $this->body,
            'body' => $this->body,
            'reference_id' => $this->reference_id,
            'related_type' => $relatedType,
            'related_id' => $relatedId,
            'data' => $data,
            'is_read' => (bool) $this->is_read,
            'read_at' => $this->read_at?->toISOString(),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
