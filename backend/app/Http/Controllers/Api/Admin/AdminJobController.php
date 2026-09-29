<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Job;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminJobController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Job::query()->with(['customer', 'worker', 'category']);

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%")
                    ->orWhereHas('customer', fn($cq) => $cq->where('name', 'like', "%{$search}%"))
                    ->orWhereHas('worker', fn($wq) => $wq->where('name', 'like', "%{$search}%"));
            });
        }

        if ($request->filled('customer_id')) {
            $query->where('customer_id', $request->customer_id);
        }

        if ($request->filled('worker_id')) {
            $query->where('worker_id', $request->worker_id);
        }

        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        $perPage = min(max((int) $request->get('per_page', 15), 1), 100);
        $jobs = $query->latest()->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => $jobs,
        ]);
    }

    public function show(int $id): JsonResponse
    {
        $job = Job::with([
            'customer',
            'worker.workerProfile',
            'category',
            'statusHistory.changedByUser',
            'location',
            'images',
            'payments.transactions',
            'review.customer',
            'chat',
        ])->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $job,
        ]);
    }
}
