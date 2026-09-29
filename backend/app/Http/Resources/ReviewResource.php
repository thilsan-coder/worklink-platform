<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ReviewResource extends JsonResource
{
    /**
     * Transform the review into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $customer = $this->customer;
        $job = $this->job;

        return [
            'id' => $this->id,
            'job_id' => $this->job_id,
            'job_title' => $job->title ?? 'Job Request',
            'worker_id' => $this->worker_id,
            'customer_id' => $this->customer_id,
            'customer_name' => $customer->name ?? 'Customer',
            'customer_avatar' => $customer && $customer->avatar ? asset('storage/' . ltrim($customer->avatar, '/')) : null,
            'rating' => (int) round($this->overall_rating),
            'overall_rating' => (float) $this->overall_rating,
            'communication_rating' => $this->communication_rating ? (float) $this->communication_rating : null,
            'work_quality_rating' => $this->work_quality_rating ? (float) $this->work_quality_rating : null,
            'punctuality_rating' => $this->punctuality_rating ? (float) $this->punctuality_rating : null,
            'professionalism_rating' => $this->professionalism_rating ? (float) $this->professionalism_rating : null,
            'comment' => $this->comment,
            'review' => $this->comment,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
