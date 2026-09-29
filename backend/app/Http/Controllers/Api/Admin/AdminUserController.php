<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdminAuditLog;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminUserController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = User::query()->with(['customerProfile', 'workerProfile.skills']);

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        if ($request->filled('role')) {
            $query->where('role', $request->role);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $perPage = min(max((int) $request->get('per_page', 15), 1), 100);
        $users = $query->latest()->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => $users,
        ]);
    }

    public function show(int $id): JsonResponse
    {
        $user = User::with([
            'customerProfile',
            'workerProfile.skills',
            'workerProfile.documents',
            'workerProfile.portfolioItems',
            'customerJobs' => fn($q) => $q->with('worker')->latest()->take(10),
            'workerJobs' => fn($q) => $q->with('customer')->latest()->take(10),
            'customerPayments' => fn($q) => $q->with('job')->latest()->take(10),
            'workerPayments' => fn($q) => $q->with('job')->latest()->take(10),
            'givenReviews' => fn($q) => $q->with('worker')->latest()->take(10),
            'receivedReviews' => fn($q) => $q->with('customer')->latest()->take(10),
        ])->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $user,
        ]);
    }

    public function updateStatus(int $id, Request $request): JsonResponse
    {
        $request->validate([
            'status' => 'required|in:active,suspended',
            'reason' => 'nullable|string',
        ]);

        $user = User::findOrFail($id);
        $oldStatus = $user->status;
        $newStatus = $request->status;

        $user->update([
            'status' => $newStatus,
        ]);

        $action = $newStatus === 'suspended' ? 'USER_SUSPENDED' : 'USER_ACTIVATED';
        $desc = "User #{$user->id} ({$user->name}) status changed from {$oldStatus} to {$newStatus}";
        if ($request->filled('reason')) {
            $desc .= ". Reason: " . $request->reason;
        }

        AdminAuditLog::record(
            adminUserId: $request->user()?->id,
            action: $action,
            entityType: 'User',
            entityId: $user->id,
            description: $desc,
            metadata: ['old_status' => $oldStatus, 'new_status' => $newStatus, 'reason' => $request->reason],
            ipAddress: $request->ip(),
            userAgent: $request->userAgent()
        );

        return response()->json([
            'success' => true,
            'message' => "User status updated to {$newStatus} successfully.",
            'data' => $user->fresh(['customerProfile', 'workerProfile']),
        ]);
    }
}
