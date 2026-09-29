<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TransactionResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'payment_id' => $this->payment_id,
            'job_id' => $this->job_id,
            'customer_id' => $this->customer_id,
            'worker_id' => $this->worker_id,
            'type' => $this->type,
            'amount' => (float) $this->amount,
            'currency' => $this->currency ?? 'LKR',
            'formatted_amount' => ($this->currency ?? 'LKR') . ' ' . number_format((float) $this->amount, 2),
            'status' => $this->status,
            'reference' => $this->reference,
            'job' => $this->whenLoaded('job', function () {
                return [
                    'id' => $this->job->id,
                    'job_number' => $this->job->job_number,
                    'title' => $this->job->title,
                ];
            }),
            'customer' => $this->whenLoaded('customer', function () {
                return [
                    'id' => $this->customer->id,
                    'name' => $this->customer->name,
                ];
            }),
            'worker' => $this->whenLoaded('worker', function () {
                return [
                    'id' => $this->worker->id,
                    'name' => $this->worker->name,
                ];
            }),
            'metadata' => $this->metadata,
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
