<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Complaint;
use App\Models\Job;
use App\Models\Review;
use App\Models\Skill;
use App\Models\User;
use App\Models\WorkerDocument;
use App\Models\WorkerProfile;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminDashboardController extends Controller
{
    public function stats(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => [
                'total_users' => User::count(),
                'total_customers' => User::whereIn('role', ['customer', 'both'])->count(),
                'total_workers' => User::whereIn('role', ['worker', 'both'])->count(),
                'pending_verifications' => WorkerProfile::where('verification_status', 'pending')->count(),
                'total_jobs' => Job::count(),
                'active_jobs' => Job::whereNotIn('status', ['COMPLETED', 'CANCELLED', 'REJECTED'])->count(),
                'completed_jobs' => Job::where('status', 'COMPLETED')->count(),
                'open_complaints' => Complaint::whereIn('status', ['OPEN', 'UNDER_INVESTIGATION'])->count(),
                'total_reviews' => Review::count(),
                'total_categories' => Category::count(),
                'total_skills' => Skill::count(),
            ],
        ]);
    }

    public function pendingWorkers(): JsonResponse
    {
        $workers = WorkerProfile::where('verification_status', 'pending')
            ->with(['user', 'documents', 'skills'])
            ->paginate(15);

        return response()->json([
            'success' => true,
            'data' => $workers,
        ]);
    }

    public function verifyWorker(int $workerProfileId, Request $request): JsonResponse
    {
        $request->validate([
            'status' => 'required|in:verified,rejected',
            'rejection_reason' => 'required_if:status,rejected|nullable|string',
        ]);

        $profile = WorkerProfile::findOrFail($workerProfileId);

        $status = $request->status;
        $profile->update([
            'verification_status' => $status,
            'verification_rejection_reason' => $status === 'rejected' ? $request->rejection_reason : null,
            'verified_at' => $status === 'verified' ? now() : null,
        ]);

        WorkerDocument::where('worker_profile_id', $profile->id)
            ->where('status', 'pending')
            ->update([
                'status' => $status === 'verified' ? 'approved' : 'rejected',
                'rejection_reason' => $status === 'rejected' ? $request->rejection_reason : null,
            ]);

        return response()->json([
            'success' => true,
            'message' => 'Worker verification status updated to ' . $status,
            'data' => $profile->fresh(['user', 'documents']),
        ]);
    }

    public function resolveComplaint(int $id, Request $request): JsonResponse
    {
        $request->validate([
            'status' => 'required|in:RESOLVED,DISMISSED',
            'admin_notes' => 'required|string',
        ]);

        $complaint = Complaint::findOrFail($id);

        $complaint->update([
            'status' => $request->status,
            'admin_notes' => $request->admin_notes,
            'resolved_at' => now(),
            'resolved_by_admin_id' => $request->user()->id,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Complaint updated successfully.',
            'data' => $complaint->fresh(),
        ]);
    }
}
