<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminPaymentController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Payment::query()->with(['job', 'customer', 'worker', 'transactions']);

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('gateway')) {
            $query->where('gateway', $request->gateway);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('gateway_transaction_id', 'like', "%{$search}%")
                    ->orWhereHas('customer', fn($cq) => $cq->where('name', 'like', "%{$search}%"))
                    ->orWhereHas('worker', fn($wq) => $wq->where('name', 'like', "%{$search}%"))
                    ->orWhereHas('job', fn($jq) => $jq->where('title', 'like', "%{$search}%"));
            });
        }

        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        $perPage = min(max((int) $request->get('per_page', 15), 1), 100);
        $payments = $query->latest()->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => $payments,
        ]);
    }

    public function show(int $id): JsonResponse
    {
        $payment = Payment::with([
            'job.category',
            'customer',
            'worker',
            'transactions',
        ])->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $payment,
        ]);
    }
}
