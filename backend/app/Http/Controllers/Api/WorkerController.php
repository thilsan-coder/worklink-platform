<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\PublicWorkerResource;
use App\Models\WorkerProfile;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class WorkerController extends Controller
{
    /**
     * Display a paginated listing of workers with search and filtering.
     */
    public function index(Request $request): JsonResponse
    {
        $request->validate([
            'search' => 'nullable|string|max:255',
            'category_id' => 'nullable|integer|exists:categories,id',
            'skill_id' => 'nullable|integer|exists:skills,id',
            'location' => 'nullable|string|max:255',
            'min_rate' => 'nullable|numeric|min:0',
            'max_rate' => 'nullable|numeric|min:0',
            'min_rating' => 'nullable|numeric|min:0|max:5',
            'verified' => 'nullable|boolean',
            'per_page' => 'nullable|integer|min:1|max:100',
        ]);

        $query = WorkerProfile::with(['user', 'skills.category', 'portfolioItems']);

        // Filter by verification status if explicitly requested
        if ($request->has('verified') && $request->boolean('verified')) {
            $query->where('verification_status', 'verified');
        }

        if ($request->filled('availability_status')) {
            $query->where('availability_status', $request->availability_status);
        }

        if ($request->filled('category_id')) {
            $categoryId = $request->category_id;
            $query->where(function ($q) use ($categoryId) {
                $q->whereHas('skills', function ($sq) use ($categoryId) {
                    $sq->where('category_id', $categoryId);
                })->orWhereHas('portfolioItems', function ($pq) use ($categoryId) {
                    $pq->where('category_id', $categoryId);
                });
            });
        }

        if ($request->filled('skill_id')) {
            $skillId = $request->skill_id;
            $query->whereHas('skills', function ($q) use ($skillId) {
                $q->where('skills.id', $skillId);
            });
        }

        if ($request->filled('location')) {
            $loc = $request->location;
            $query->where(function ($q) use ($loc) {
                $q->where('address', 'like', "%{$loc}%")
                    ->orWhereHas('user', function ($uq) use ($loc) {
                        $uq->where('address', 'like', "%{$loc}%")
                            ->orWhere('city', 'like', "%{$loc}%")
                            ->orWhere('district', 'like', "%{$loc}%");
                    });
            });
        }

        if ($request->filled('min_rate')) {
            $query->where('hourly_rate', '>=', (float) $request->min_rate);
        }

        if ($request->filled('max_rate')) {
            $query->where('hourly_rate', '<=', (float) $request->max_rate);
        }

        if ($request->filled('min_rating')) {
            $query->where('average_rating', '>=', (float) $request->min_rating);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->whereHas('user', function ($uq) use ($search) {
                    $uq->where('name', 'like', "%{$search}%")
                        ->orWhere('city', 'like', "%{$search}%")
                        ->orWhere('district', 'like', "%{$search}%");
                })
                    ->orWhere('bio', 'like', "%{$search}%")
                    ->orWhere('address', 'like', "%{$search}%")
                    ->orWhereHas('skills', function ($sq) use ($search) {
                        $sq->where('name', 'like', "%{$search}%")
                            ->orWhereHas('category', function ($cq) use ($search) {
                                $cq->where('name', 'like', "%{$search}%");
                            });
                    });
            });
        }

        // Sort verified profiles first, then by average rating descending
        $query->orderByRaw("CASE WHEN verification_status = 'verified' THEN 1 ELSE 2 END")
            ->orderBy('average_rating', 'desc')
            ->orderBy('id', 'desc');

        $perPage = $request->integer('per_page', 15);
        $workers = $query->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => PublicWorkerResource::collection($workers)->response()->getData(true),
        ]);
    }

    /**
     * Display sanitized public profile details for a specific worker.
     */
    public function show(int $id): JsonResponse
    {
        $worker = WorkerProfile::with(['user', 'skills.category', 'portfolioItems'])
            ->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => new PublicWorkerResource($worker),
        ]);
    }
}
