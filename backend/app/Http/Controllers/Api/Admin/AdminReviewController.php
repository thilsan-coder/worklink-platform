<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdminAuditLog;
use App\Models\Review;
use App\Models\WorkerProfile;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminReviewController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Review::query()->with(['customer', 'worker', 'job']);

        if ($request->filled('rating')) {
            $query->where('overall_rating', (int) $request->rating);
        }

        if ($request->filled('worker_id')) {
            $query->where('worker_id', $request->worker_id);
        }

        if ($request->filled('customer_id')) {
            $query->where('customer_id', $request->customer_id);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('comment', 'like', "%{$search}%")
                    ->orWhereHas('customer', fn($cq) => $cq->where('name', 'like', "%{$search}%"))
                    ->orWhereHas('worker', fn($wq) => $wq->where('name', 'like', "%{$search}%"));
            });
        }

        $perPage = min(max((int) $request->get('per_page', 15), 1), 100);
        $reviews = $query->latest()->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => $reviews,
        ]);
    }

    public function show(int $id): JsonResponse
    {
        $review = Review::with(['customer', 'worker', 'job'])->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $review,
        ]);
    }

    public function destroy(int $id, Request $request): JsonResponse
    {
        $review = Review::with(['customer', 'worker'])->findOrFail($id);
        $workerId = $review->worker_id;

        AdminAuditLog::record(
            adminUserId: $request->user()?->id,
            action: 'REVIEW_DELETED',
            entityType: 'Review',
            entityId: $review->id,
            description: "Deleted review #{$review->id} (Rating: {$review->overall_rating}) by {$review->customer?->name} for {$review->worker?->name}",
            metadata: [
                'overall_rating' => $review->overall_rating,
                'comment' => $review->comment,
                'worker_id' => $workerId,
                'customer_id' => $review->customer_id,
            ],
            ipAddress: $request->ip(),
            userAgent: $request->userAgent()
        );

        $review->delete();

        // Recalculate worker rating
        $workerProfile = WorkerProfile::where('user_id', $workerId)->first();
        if ($workerProfile) {
            $remainingReviews = Review::where('worker_id', $workerId);
            $avg = $remainingReviews->avg('overall_rating') ?? 0;
            $count = $remainingReviews->count();
            $workerProfile->update([
                'average_rating' => round($avg, 2),
                'total_reviews' => $count,
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Review moderated and removed successfully. Worker rating updated.',
        ]);
    }
}
