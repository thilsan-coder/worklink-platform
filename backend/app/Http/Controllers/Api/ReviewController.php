<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Job;
use App\Models\Notification;
use App\Models\Review;
use App\Models\WorkerProfile;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ReviewController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'job_id' => 'required|exists:jobs,id|unique:reviews,job_id',
            'overall_rating' => 'required|numeric|min:1|max:5',
            'communication_rating' => 'nullable|numeric|min:1|max:5',
            'work_quality_rating' => 'nullable|numeric|min:1|max:5',
            'punctuality_rating' => 'nullable|numeric|min:1|max:5',
            'professionalism_rating' => 'nullable|numeric|min:1|max:5',
            'comment' => 'nullable|string',
        ]);

        $job = Job::findOrFail($validated['job_id']);
        $user = $request->user();

        if ($job->customer_id !== $user->id) {
            return response()->json(['success' => false, 'message' => 'Only job customer can submit review.'], 403);
        }

        if ($job->status !== 'COMPLETED') {
            return response()->json(['success' => false, 'message' => 'Reviews can only be submitted for COMPLETED jobs.'], 422);
        }

        $review = Review::create([
            'job_id' => $job->id,
            'customer_id' => $user->id,
            'worker_id' => $job->worker_id,
            'overall_rating' => $validated['overall_rating'],
            'communication_rating' => $validated['communication_rating'] ?? null,
            'work_quality_rating' => $validated['work_quality_rating'] ?? null,
            'punctuality_rating' => $validated['punctuality_rating'] ?? null,
            'professionalism_rating' => $validated['professionalism_rating'] ?? null,
            'comment' => $validated['comment'] ?? null,
        ]);

        // Recalculate worker rating stats
        $workerProfile = WorkerProfile::where('user_id', $job->worker_id)->first();
        if ($workerProfile) {
            $avgRating = Review::where('worker_id', $job->worker_id)->avg('overall_rating') ?? 0;
            $totalReviews = Review::where('worker_id', $job->worker_id)->count();

            $workerProfile->update([
                'average_rating' => $avgRating,
                'total_reviews' => $totalReviews,
            ]);
        }

        Notification::create([
            'user_id' => $job->worker_id,
            'title' => 'New Review Received',
            'body' => 'You received a ' . $validated['overall_rating'] . '★ review from customer.',
            'type' => 'review_received',
            'reference_id' => $job->id,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Review submitted successfully.',
            'data' => $review,
        ], 201);
    }

    public function workerReviews(int $workerUserId): JsonResponse
    {
        $reviews = Review::where('worker_id', $workerUserId)
            ->with(['customer', 'job'])
            ->latest()
            ->paginate(15);

        return response()->json([
            'success' => true,
            'data' => $reviews,
        ]);
    }
}
