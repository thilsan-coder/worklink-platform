<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\WorkerProfile;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminWorkerController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = WorkerProfile::query()->with(['user', 'skills.category', 'documents']);

        if ($request->filled('search')) {
            $search = $request->search;
            $query->whereHas('user', function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        if ($request->filled('verification_status')) {
            $query->where('verification_status', $request->verification_status);
        }

        if ($request->filled('skill_id')) {
            $query->whereHas('skills', function ($q) use ($request) {
                $q->where('skills.id', $request->skill_id);
            });
        }

        if ($request->filled('category_id')) {
            $query->whereHas('skills', function ($q) use ($request) {
                $q->where('category_id', $request->category_id);
            });
        }

        if ($request->filled('min_rating')) {
            $query->where('average_rating', '>=', (float) $request->min_rating);
        }

        if ($request->filled('status')) {
            $query->whereHas('user', function ($q) use ($request) {
                $q->where('status', $request->status);
            });
        }

        $perPage = min(max((int) $request->get('per_page', 15), 1), 100);
        $workers = $query->latest()->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => $workers,
        ]);
    }

    public function show(int $id): JsonResponse
    {
        $worker = WorkerProfile::with([
            'user' => fn($q) => $q->with([
                'workerJobs' => fn($jq) => $jq->with('customer')->latest()->take(10),
                'workerPayments' => fn($pq) => $pq->with('job')->latest()->take(10),
                'receivedReviews' => fn($rq) => $rq->with('customer')->latest()->take(10),
            ]),
            'skills.category',
            'documents',
            'portfolioItems',
        ])->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $worker,
        ]);
    }
}
