<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ReviewResource;
use App\Models\Job;
use App\Models\Notification;
use App\Models\Review;
use App\Models\WorkerProfile;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ReviewController extends Controller
{
    /**
     * Get existing review for a specific job.
     */
    public function jobReview(int $jobId, Request $request): JsonResponse
    {
        $job = Job::findOrFail($jobId);
        $user = $request->user();

        // Only job customer, assigned worker, or admin can view the job review
        if ($user && $job->customer_id !== $user->id && $job->worker_id !== $user->id && $user->role !== 'admin') {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized to view review for this job.',
            ], 403);
        }

        $review = Review::where('job_id', $job->id)->with(['customer', 'job'])->first();

        return response()->json([
            'success' => true,
            'data' => $review ? new ReviewResource($review) : null,
        ]);
    }

    /**
     * Submit a review for a completed job.
     */
    public function store(Request $request, ?int $jobId = null): JsonResponse
    {
        $targetJobId = $jobId ?? $request->input('job_id');

        if (!$targetJobId) {
            return response()->json([
                'success' => false,
                'message' => 'The job_id field is required.',
            ], 422);
        }

        $job = Job::findOrFail($targetJobId);
        $user = $request->user();

        // 1. Customer ownership validation
        if ($job->customer_id !== $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'Only the customer who requested this job can submit a review.',
            ], 403);
        }

        // 2. Job completed validation
        $statusUpper = strtoupper((string) $job->status);
        if ($statusUpper !== 'COMPLETED' && $statusUpper !== 'WORK_COMPLETED') {
            return response()->json([
                'success' => false,
                'message' => 'Reviews can only be submitted for COMPLETED jobs.',
            ], 422);
        }

        // 3. Worker assignment validation
        if (!$job->worker_id) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot review a job without an assigned worker.',
            ], 422);
        }

        // 4. Duplicate review protection
        $existingReview = Review::where('job_id', $job->id)->first();
        if ($existingReview) {
            return response()->json([
                'success' => false,
                'message' => 'A review for this job has already been submitted.',
            ], 409);
        }

        // 5. Input validation
        $validated = $request->validate([
            'rating' => 'required_without:overall_rating|nullable|integer|min:1|max:5',
            'overall_rating' => 'required_without:rating|nullable|numeric|min:1|max:5',
            'comment' => 'nullable|string|max:1000',
            'review' => 'nullable|string|max:1000',
            'communication_rating' => 'nullable|numeric|min:1|max:5',
            'work_quality_rating' => 'nullable|numeric|min:1|max:5',
            'punctuality_rating' => 'nullable|numeric|min:1|max:5',
            'professionalism_rating' => 'nullable|numeric|min:1|max:5',
        ]);

        $ratingValue = $validated['rating'] ?? $validated['overall_rating'] ?? 5;
        $commentValue = trim($validated['comment'] ?? $validated['review'] ?? '');

        $review = Review::create([
            'job_id' => $job->id,
            'customer_id' => $user->id,
            'worker_id' => $job->worker_id,
            'overall_rating' => $ratingValue,
            'communication_rating' => $validated['communication_rating'] ?? null,
            'work_quality_rating' => $validated['work_quality_rating'] ?? null,
            'punctuality_rating' => $validated['punctuality_rating'] ?? null,
            'professionalism_rating' => $validated['professionalism_rating'] ?? null,
            'comment' => $commentValue ?: null,
        ]);

        // Recalculate worker stats
        $this->recalculateWorkerRating($job->worker_id);

        // Send Notification to worker
        Notification::create([
            'user_id' => $job->worker_id,
            'title' => 'New ' . (int) $ratingValue . '★ Review Received',
            'body' => $user->name . ' submitted a ' . (int) $ratingValue . '-star review for "' . $job->title . '".',
            'type' => 'review_received',
            'reference_id' => $review->id,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Review submitted successfully.',
            'data' => new ReviewResource($review->load(['customer', 'job'])),
        ], 201);
    }

    /**
     * Get paginated reviews for a worker (by worker userId or workerProfileId).
     */
    public function workerReviews(int $workerIdentifier, Request $request): JsonResponse
    {
        // Resolve worker user id
        $workerUserId = $this->resolveWorkerUserId($workerIdentifier);

        $perPage = $request->integer('per_page', 15);
        $reviews = Review::where('worker_id', $workerUserId)
            ->with(['customer', 'job'])
            ->latest('created_at')
            ->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => ReviewResource::collection($reviews)->response()->getData(true),
        ]);
    }

    /**
     * Get rating summary & breakdown for a worker.
     */
    public function workerRating(int $workerIdentifier): JsonResponse
    {
        $workerUserId = $this->resolveWorkerUserId($workerIdentifier);

        $reviews = Review::where('worker_id', $workerUserId)->get();
        $totalReviews = $reviews->count();
        $avgRating = $totalReviews > 0 ? round($reviews->avg('overall_rating'), 2) : 0.00;

        $breakdown = [
            5 => $reviews->whereBetween('overall_rating', [4.5, 5.0])->count(),
            4 => $reviews->whereBetween('overall_rating', [3.5, 4.49])->count(),
            3 => $reviews->whereBetween('overall_rating', [2.5, 3.49])->count(),
            2 => $reviews->whereBetween('overall_rating', [1.5, 2.49])->count(),
            1 => $reviews->whereBetween('overall_rating', [0.5, 1.49])->count(),
        ];

        return response()->json([
            'success' => true,
            'data' => [
                'worker_id' => $workerUserId,
                'average_rating' => (float) $avgRating,
                'total_reviews' => $totalReviews,
                'breakdown' => $breakdown,
            ],
        ]);
    }

    /**
     * Update an existing review (owner only).
     */
    public function update(int $reviewId, Request $request): JsonResponse
    {
        $review = Review::findOrFail($reviewId);
        $user = $request->user();

        if ($review->customer_id !== $user->id && $user->role !== 'admin') {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized to edit this review.',
            ], 403);
        }

        $validated = $request->validate([
            'rating' => 'nullable|integer|min:1|max:5',
            'overall_rating' => 'nullable|numeric|min:1|max:5',
            'comment' => 'nullable|string|max:1000',
            'review' => 'nullable|string|max:1000',
        ]);

        $ratingValue = $validated['rating'] ?? $validated['overall_rating'] ?? $review->overall_rating;
        $commentValue = isset($validated['comment']) || isset($validated['review'])
            ? trim($validated['comment'] ?? $validated['review'] ?? '')
            : $review->comment;

        $review->update([
            'overall_rating' => $ratingValue,
            'comment' => $commentValue ?: null,
        ]);

        $this->recalculateWorkerRating($review->worker_id);

        return response()->json([
            'success' => true,
            'message' => 'Review updated successfully.',
            'data' => new ReviewResource($review->load(['customer', 'job'])),
        ]);
    }

    /**
     * Delete an existing review (owner only).
     */
    public function destroy(int $reviewId, Request $request): JsonResponse
    {
        $review = Review::findOrFail($reviewId);
        $user = $request->user();

        if ($review->customer_id !== $user->id && $user->role !== 'admin') {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized to delete this review.',
            ], 403);
        }

        $workerId = $review->worker_id;
        $review->delete();

        $this->recalculateWorkerRating($workerId);

        return response()->json([
            'success' => true,
            'message' => 'Review deleted successfully.',
        ]);
    }

    /**
     * Helper to resolve worker user id from either user id or worker profile id.
     */
    private function resolveWorkerUserId(int $identifier): int
    {
        $profile = WorkerProfile::where('id', $identifier)->orWhere('user_id', $identifier)->first();
        return $profile ? $profile->user_id : $identifier;
    }

    /**
     * Helper to recalculate and synchronize average rating and total reviews on WorkerProfile.
     */
    private function recalculateWorkerRating(int $workerUserId): void
    {
        $workerProfile = WorkerProfile::where('user_id', $workerUserId)->first();
        if ($workerProfile) {
            $avgRating = Review::where('worker_id', $workerUserId)->avg('overall_rating') ?? 0.00;
            $totalReviews = Review::where('worker_id', $workerUserId)->count();

            $workerProfile->update([
                'average_rating' => round($avgRating, 2),
                'total_reviews' => $totalReviews,
            ]);
        }
    }
}
