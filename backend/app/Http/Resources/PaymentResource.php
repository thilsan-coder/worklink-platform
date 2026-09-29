<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PaymentResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $successfulTxn = $this->transactions?->firstWhere('status', 'COMPLETED') ?? $this->transactions?->first();

        return [
            'id' => $this->id,
            'job_id' => $this->job_id,
            'customer_id' => $this->customer_id,
            'worker_id' => $this->worker_id,
            'amount' => (float) $this->amount,
            'currency' => $this->currency ?? 'LKR',
            'formatted_amount' => ($this->currency ?? 'LKR') . ' ' . number_format((float) $this->amount, 2),
            'status' => $this->status,
            'payment_method' => $this->payment_method,
            'gateway' => $this->gateway,
            'gateway_transaction_id' => $this->gateway_transaction_id,
            'transaction_reference' => $successfulTxn?->reference,
            'paid_at' => $this->paid_at?->toISOString(),
            'failure_reason' => $this->failure_reason,
            'refunded_at' => $this->refunded_at?->toISOString(),
            'refund_reason' => $this->refund_reason,
            'job' => $this->whenLoaded('job', function () {
                return [
                    'id' => $this->job->id,
                    'job_number' => $this->job->job_number,
                    'title' => $this->job->title,
                    'status' => $this->job->status,
                ];
            }),
            'customer' => $this->whenLoaded('customer', function () {
                return [
                    'id' => $this->customer->id,
                    'name' => $this->customer->name,
                    'avatar' => $this->customer->avatar,
                ];
            }),
            'worker' => $this->whenLoaded('worker', function () {
                return [
                    'id' => $this->worker->id,
                    'name' => $this->worker->name,
                    'avatar' => $this->worker->avatar,
                ];
            }),
            'transactions' => TransactionResource::collection($this->whenLoaded('transactions')),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
