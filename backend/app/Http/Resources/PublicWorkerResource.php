<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PublicWorkerResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $user = $this->user;

        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'name' => $user->name ?? 'Skilled Worker',
            'avatar' => $user->avatar ? asset('storage/' . ltrim($user->avatar, '/')) : null,
            'bio' => $this->bio,
            'experience_years' => (int) $this->experience_years,
            'hourly_rate' => (float) $this->hourly_rate,
            'service_area_radius_km' => (int) $this->service_area_radius_km,
            'address' => $this->address,
            'availability_status' => $this->availability_status,
            'verification_status' => $this->verification_status,
            'is_verified' => $this->verification_status === 'verified',
            'average_rating' => (float) $this->average_rating,
            'total_reviews' => (int) $this->total_reviews,
            'completed_jobs_count' => (int) $this->completed_jobs_count,
            'skills' => $this->skills->map(function ($skill) {
                return [
                    'id' => $skill->id,
                    'name' => $skill->name,
                    'category' => $skill->category->name ?? null,
                ];
            }),
            'portfolio' => $this->portfolioItems->map(function ($item) {
                return [
                    'id' => $item->id,
                    'title' => $item->title,
                    'description' => $item->description,
                    'image_url' => asset('storage/' . ltrim($item->image_path, '/')),
                ];
            }),
        ];
    }
}
