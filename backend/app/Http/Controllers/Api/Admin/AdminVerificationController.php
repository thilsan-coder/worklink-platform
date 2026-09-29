<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdminAuditLog;
use App\Models\Notification;
use App\Models\WorkerDocument;
use App\Models\WorkerProfile;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminVerificationController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = WorkerProfile::query()->with(['user', 'documents', 'skills']);

        if ($request->filled('status')) {
            $query->where('verification_status', $request->status);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->whereHas('user', function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        $perPage = min(max((int) $request->get('per_page', 15), 1), 100);
        $verifications = $query->latest()->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => $verifications,
        ]);
    }

    public function show(int $id): JsonResponse
    {
        $verification = WorkerProfile::with(['user', 'documents', 'skills', 'portfolioItems'])->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $verification,
        ]);
    }

    public function approve(int $id, Request $request): JsonResponse
    {
        $profile = WorkerProfile::with('user')->findOrFail($id);

        $profile->update([
            'verification_status' => 'verified',
            'verification_rejection_reason' => null,
            'verified_at' => now(),
        ]);

        WorkerDocument::where('worker_profile_id', $profile->id)
            ->where('status', 'pending')
            ->update([
                'status' => 'approved',
                'rejection_reason' => null,
            ]);

        // Send Notification to Worker
        Notification::create([
            'user_id' => $profile->user_id,
            'title' => 'Verification Approved',
            'body' => 'Your worker account has been verified. You now have a verified badge and full access to job requests!',
            'type' => 'VERIFICATION_APPROVED',
            'reference_id' => $profile->id,
            'data' => [
                'worker_profile_id' => $profile->id,
                'status' => 'verified',
            ],
            'is_read' => false,
        ]);

        AdminAuditLog::record(
            adminUserId: $request->user()?->id,
            action: 'VERIFICATION_APPROVED',
            entityType: 'WorkerProfile',
            entityId: $profile->id,
            description: "Approved verification for worker #{$profile->user_id} ({$profile->user?->name})",
            metadata: ['worker_profile_id' => $profile->id, 'user_id' => $profile->user_id],
            ipAddress: $request->ip(),
            userAgent: $request->userAgent()
        );

        return response()->json([
            'success' => true,
            'message' => 'Worker verification approved successfully.',
            'data' => $profile->fresh(['user', 'documents', 'skills']),
        ]);
    }

    public function reject(int $id, Request $request): JsonResponse
    {
        $request->validate([
            'reason' => 'required|string|max:500',
        ]);

        $profile = WorkerProfile::with('user')->findOrFail($id);
        $reason = $request->reason;

        $profile->update([
            'verification_status' => 'rejected',
            'verification_rejection_reason' => $reason,
            'verified_at' => null,
        ]);

        WorkerDocument::where('worker_profile_id', $profile->id)
            ->where('status', 'pending')
            ->update([
                'status' => 'rejected',
                'rejection_reason' => $reason,
            ]);

        // Send Notification to Worker
        Notification::create([
            'user_id' => $profile->user_id,
            'title' => 'Verification Rejected',
            'body' => "Your verification request was rejected. Reason: {$reason}",
            'type' => 'VERIFICATION_REJECTED',
            'reference_id' => $profile->id,
            'data' => [
                'worker_profile_id' => $profile->id,
                'status' => 'rejected',
                'reason' => $reason,
            ],
            'is_read' => false,
        ]);

        AdminAuditLog::record(
            adminUserId: $request->user()?->id,
            action: 'VERIFICATION_REJECTED',
            entityType: 'WorkerProfile',
            entityId: $profile->id,
            description: "Rejected verification for worker #{$profile->user_id} ({$profile->user?->name}). Reason: {$reason}",
            metadata: ['worker_profile_id' => $profile->id, 'user_id' => $profile->user_id, 'reason' => $reason],
            ipAddress: $request->ip(),
            userAgent: $request->userAgent()
        );

        return response()->json([
            'success' => true,
            'message' => 'Worker verification rejected successfully.',
            'data' => $profile->fresh(['user', 'documents', 'skills']),
        ]);
    }
}
