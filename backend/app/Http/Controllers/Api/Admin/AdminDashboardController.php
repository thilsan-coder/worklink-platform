<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Complaint;
use App\Models\Job;
use App\Models\Payment;
use App\Models\Review;
use App\Models\Skill;
use App\Models\Transaction;
use App\Models\User;
use App\Models\WorkerDocument;
use App\Models\WorkerProfile;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminDashboardController extends Controller
{
    public function stats(): JsonResponse
    {
        $totalPaidAmount = (float) Payment::where('status', 'PAID')->sum('amount');
        $totalRefundedAmount = (float) Payment::where('status', 'REFUNDED')->sum('amount');
        $avgRating = (float) (Review::avg('overall_rating') ?? 0);

        return response()->json([
            'success' => true,
            'data' => [
                'total_users' => User::count(),
                'total_customers' => User::whereIn('role', ['customer', 'both'])->count(),
                'total_workers' => User::whereIn('role', ['worker', 'both'])->count(),
                'verified_workers' => WorkerProfile::where('verification_status', 'verified')->count(),
                'pending_verifications' => WorkerProfile::where('verification_status', 'pending')->count(),
                'suspended_users' => User::where('status', 'suspended')->count(),

                'total_jobs' => Job::count(),
                'active_jobs' => Job::whereIn('status', ['REQUESTED', 'ACCEPTED', 'SCHEDULED', 'IN_PROGRESS'])->count(),
                'completed_jobs' => Job::where('status', 'COMPLETED')->count(),
                'cancelled_jobs' => Job::whereIn('status', ['CANCELLED', 'REJECTED'])->count(),

                'total_payments' => Payment::count(),
                'successful_payments' => Payment::where('status', 'PAID')->count(),
                'failed_payments' => Payment::where('status', 'FAILED')->count(),
                'refunded_payments' => Payment::where('status', 'REFUNDED')->count(),
                'total_transaction_amount' => round($totalPaidAmount, 2),
                'total_refunded_amount' => round($totalRefundedAmount, 2),

                'total_reviews' => Review::count(),
                'average_rating' => round($avgRating, 2),
                'open_complaints' => Complaint::whereIn('status', ['OPEN', 'UNDER_INVESTIGATION'])->count(),
                'total_categories' => Category::count(),
                'total_skills' => Skill::count(),
            ],
        ]);
    }

    public function recentActivity(): JsonResponse
    {
        $activities = collect();

        // Recent users
        $recentUsers = User::latest()->take(5)->get()->map(function ($u) {
            return [
                'id' => 'user_' . $u->id,
                'type' => 'USER_REGISTERED',
                'title' => 'New User Registered',
                'description' => ($u->name ?? 'New User') . ' registered as ' . ucfirst($u->role),
                'entity_type' => 'User',
                'entity_id' => $u->id,
                'created_at' => $u->created_at->toISOString(),
            ];
        });
        $activities = $activities->concat($recentUsers);

        // Recent jobs
        $recentJobs = Job::with(['customer', 'worker'])->latest()->take(5)->get()->map(function ($j) {
            return [
                'id' => 'job_' . $j->id,
                'type' => 'JOB_CREATED',
                'title' => 'Job ' . $j->status,
                'description' => ($j->title ?? 'Service') . ' (' . $j->status . ')',
                'entity_type' => 'Job',
                'entity_id' => $j->id,
                'created_at' => $j->created_at->toISOString(),
            ];
        });
        $activities = $activities->concat($recentJobs);

        // Recent payments
        $recentPayments = Payment::with(['job', 'customer'])->latest()->take(5)->get()->map(function ($p) {
            return [
                'id' => 'payment_' . $p->id,
                'type' => 'PAYMENT_' . $p->status,
                'title' => 'Payment ' . $p->status,
                'description' => 'LKR ' . number_format($p->amount, 2) . ' for ' . ($p->job->title ?? 'Job #' . $p->job_id),
                'entity_type' => 'Payment',
                'entity_id' => $p->id,
                'created_at' => $p->created_at->toISOString(),
            ];
        });
        $activities = $activities->concat($recentPayments);

        // Recent reviews
        $recentReviews = Review::with(['customer', 'worker'])->latest()->take(5)->get()->map(function ($r) {
            return [
                'id' => 'review_' . $r->id,
                'type' => 'REVIEW_SUBMITTED',
                'title' => 'Review Submitted (' . $r->overall_rating . ' ★)',
                'description' => ($r->customer->name ?? 'Customer') . ' reviewed ' . ($r->worker->name ?? 'Worker'),
                'entity_type' => 'Review',
                'entity_id' => $r->id,
                'created_at' => $r->created_at->toISOString(),
            ];
        });
        $activities = $activities->concat($recentReviews);

        // Sort by created_at desc and take 15
        $sorted = $activities->sortByDesc('created_at')->values()->take(15);

        return response()->json([
            'success' => true,
            'data' => $sorted,
        ]);
    }

    // Keep backwards compatibility for existing tests
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
