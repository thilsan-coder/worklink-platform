<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Chat;
use App\Models\Job;
use App\Models\JobImage;
use App\Models\JobLocation;
use App\Models\JobStatusHistory;
use App\Models\Notification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class JobController extends Controller
{
    /**
     * List jobs assigned to or created by authenticated user.
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        $query = Job::with(['customer', 'worker', 'category', 'skill', 'location', 'images', 'review']);

        if ($user->role === 'customer') {
            $query->where('customer_id', $user->id);
        } elseif ($user->role === 'worker') {
            $query->where('worker_id', $user->id);
        } else {
            $query->where(function ($q) use ($user) {
                $q->where('customer_id', $user->id)->orWhere('worker_id', $user->id);
            });
        }

        if ($request->filled('status')) {
            $status = $request->status;
            if ($status === 'IN_PROGRESS') {
                $query->whereIn('status', ['IN_PROGRESS', 'WORK_STARTED']);
            } elseif ($status === 'COMPLETED') {
                $query->whereIn('status', ['COMPLETED', 'WORK_COMPLETED']);
            } else {
                $query->where('status', $status);
            }
        }

        $jobs = $query->latest()->paginate($request->integer('per_page', 15));

        return response()->json([
            'success' => true,
            'data' => $jobs,
        ]);
    }

    /**
     * Create a new job request.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'worker_id' => 'required|exists:users,id',
            'category_id' => 'required|exists:categories,id',
            'skill_id' => 'nullable|exists:skills,id',
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'estimated_cost' => 'nullable|numeric|min:0',
            'scheduled_at' => 'nullable|date',
            // Location payload
            'address_line1' => 'required|string|max:255',
            'address_line2' => 'nullable|string|max:255',
            'city' => 'required|string|max:100',
            'state' => 'nullable|string|max:100',
            'postal_code' => 'nullable|string|max:20',
            'latitude' => 'required|numeric',
            'longitude' => 'required|numeric',
            'landmark' => 'nullable|string|max:255',
        ]);

        $user = $request->user();

        $jobNumber = 'WLJ-' . date('Ymd') . '-' . Str::upper(Str::random(5));

        $job = Job::create([
            'job_number' => $jobNumber,
            'customer_id' => $user->id,
            'worker_id' => $validated['worker_id'],
            'category_id' => $validated['category_id'],
            'skill_id' => $validated['skill_id'] ?? null,
            'title' => $validated['title'],
            'description' => $validated['description'],
            'estimated_cost' => $validated['estimated_cost'] ?? null,
            'scheduled_at' => $validated['scheduled_at'] ?? null,
            'status' => 'REQUESTED',
        ]);

        JobLocation::create([
            'job_id' => $job->id,
            'address_line1' => $validated['address_line1'],
            'address_line2' => $validated['address_line2'] ?? null,
            'city' => $validated['city'],
            'state' => $validated['state'] ?? null,
            'postal_code' => $validated['postal_code'] ?? null,
            'latitude' => $validated['latitude'],
            'longitude' => $validated['longitude'],
            'landmark' => $validated['landmark'] ?? null,
        ]);

        JobStatusHistory::create([
            'job_id' => $job->id,
            'changed_by_user_id' => $user->id,
            'from_status' => null,
            'to_status' => 'REQUESTED',
            'notes' => 'Job request initiated by customer.',
            'latitude' => $validated['latitude'],
            'longitude' => $validated['longitude'],
        ]);

        Chat::firstOrCreate([
            'job_id' => $job->id,
            'customer_id' => $user->id,
            'worker_id' => $validated['worker_id'],
        ]);

        Notification::create([
            'user_id' => $validated['worker_id'],
            'title' => 'New Job Request',
            'body' => 'You received a new job request: ' . $job->title,
            'type' => 'job_request',
            'reference_id' => $job->id,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Job request submitted successfully.',
            'data' => $job->load(['location', 'category', 'skill', 'customer', 'statusHistory']),
        ], 201);
    }

    /**
     * Show job details.
     */
    public function show(int $id, Request $request): JsonResponse
    {
        $job = Job::with(['customer', 'worker.workerProfile', 'category', 'skill', 'location', 'images', 'statusHistory.changedBy', 'review', 'chat'])
            ->findOrFail($id);

        $user = $request->user();
        if ($job->customer_id !== $user->id && $job->worker_id !== $user->id && $user->role !== 'admin') {
            return response()->json(['success' => false, 'message' => 'Unauthorized access to job details.'], 403);
        }

        return response()->json([
            'success' => true,
            'data' => $job,
        ]);
    }

    /**
     * Worker accepts a job request.
     */
    public function accept(int $id, Request $request): JsonResponse
    {
        $job = Job::findOrFail($id);
        $user = $request->user();

        if ($job->worker_id !== $user->id) {
            return response()->json(['success' => false, 'message' => 'Unauthorized.'], 403);
        }

        if ($job->status !== 'REQUESTED') {
            return response()->json(['success' => false, 'message' => 'Job status cannot be accepted from current status: ' . $job->status], 422);
        }

        $oldStatus = $job->status;
        $job->update(['status' => 'ACCEPTED']);

        JobStatusHistory::create([
            'job_id' => $job->id,
            'changed_by_user_id' => $user->id,
            'from_status' => $oldStatus,
            'to_status' => 'ACCEPTED',
            'notes' => 'Worker accepted the job request.',
        ]);

        Notification::create([
            'user_id' => $job->customer_id,
            'title' => 'Job Request Accepted',
            'body' => 'The worker accepted your job request: ' . $job->title,
            'type' => 'job_status',
            'reference_id' => $job->id,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Job accepted.',
            'data' => $job->fresh(['statusHistory']),
        ]);
    }

    /**
     * Worker rejects a job request.
     */
    public function reject(int $id, Request $request): JsonResponse
    {
        $request->validate(['reason' => 'required|string']);

        $job = Job::findOrFail($id);
        $user = $request->user();

        if ($job->worker_id !== $user->id) {
            return response()->json(['success' => false, 'message' => 'Unauthorized.'], 403);
        }

        if ($job->status !== 'REQUESTED') {
            return response()->json(['success' => false, 'message' => 'Only requested jobs can be rejected.'], 422);
        }

        $oldStatus = $job->status;
        $job->update([
            'status' => 'REJECTED',
            'reject_reason' => $request->reason,
        ]);

        JobStatusHistory::create([
            'job_id' => $job->id,
            'changed_by_user_id' => $user->id,
            'from_status' => $oldStatus,
            'to_status' => 'REJECTED',
            'notes' => $request->reason,
        ]);

        Notification::create([
            'user_id' => $job->customer_id,
            'title' => 'Job Request Declined',
            'body' => 'The worker declined your job request.',
            'type' => 'job_status',
            'reference_id' => $job->id,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Job request rejected.',
            'data' => $job->fresh(['statusHistory']),
        ]);
    }

    /**
     * Schedule a job appointment date/time.
     */
    public function schedule(int $id, Request $request): JsonResponse
    {
        $request->validate([
            'scheduled_at' => 'required|date',
            'notes' => 'nullable|string',
        ]);

        $job = Job::findOrFail($id);
        $user = $request->user();

        if ($job->customer_id !== $user->id && $job->worker_id !== $user->id) {
            return response()->json(['success' => false, 'message' => 'Unauthorized.'], 403);
        }

        if (in_array($job->status, ['COMPLETED', 'CANCELLED', 'REJECTED'])) {
            return response()->json(['success' => false, 'message' => 'Cannot schedule job in status: ' . $job->status], 422);
        }

        $oldStatus = $job->status;
        $job->update([
            'scheduled_at' => $request->scheduled_at,
            'status' => 'SCHEDULED',
        ]);

        JobStatusHistory::create([
            'job_id' => $job->id,
            'changed_by_user_id' => $user->id,
            'from_status' => $oldStatus,
            'to_status' => 'SCHEDULED',
            'notes' => $request->notes ?? ('Job scheduled for ' . $request->scheduled_at),
        ]);

        $recipientId = ($user->id === $job->customer_id) ? $job->worker_id : $job->customer_id;
        Notification::create([
            'user_id' => $recipientId,
            'title' => 'Job Scheduled',
            'body' => 'Job appointment scheduled for: ' . date('M d, Y H:i', strtotime($request->scheduled_at)),
            'type' => 'job_status',
            'reference_id' => $job->id,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Job scheduled successfully.',
            'data' => $job->fresh(['statusHistory']),
        ]);
    }

    /**
     * Start work on job (Worker action).
     */
    public function start(int $id, Request $request): JsonResponse
    {
        $job = Job::findOrFail($id);
        $user = $request->user();

        if ($job->worker_id !== $user->id) {
            return response()->json(['success' => false, 'message' => 'Unauthorized.'], 403);
        }

        if (! in_array($job->status, ['ACCEPTED', 'SCHEDULED', 'IN_PROGRESS', 'WORK_STARTED'])) {
            return response()->json(['success' => false, 'message' => 'Cannot start job in status: ' . $job->status], 422);
        }

        $oldStatus = $job->status;
        $job->update([
            'status' => 'IN_PROGRESS',
            'started_at' => now(),
        ]);

        JobStatusHistory::create([
            'job_id' => $job->id,
            'changed_by_user_id' => $user->id,
            'from_status' => $oldStatus,
            'to_status' => 'IN_PROGRESS',
            'notes' => $request->notes ?? 'Worker started the job.',
        ]);

        Notification::create([
            'user_id' => $job->customer_id,
            'title' => 'Work Started',
            'body' => 'The worker has started work on your job: ' . $job->title,
            'type' => 'job_status',
            'reference_id' => $job->id,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Job status updated to IN_PROGRESS.',
            'data' => $job->fresh(['statusHistory']),
        ]);
    }

    /**
     * Mark job work as completed (Worker / Customer action).
     */
    public function complete(int $id, Request $request): JsonResponse
    {
        $job = Job::findOrFail($id);
        $user = $request->user();

        if ($job->worker_id !== $user->id && $job->customer_id !== $user->id) {
            return response()->json(['success' => false, 'message' => 'Unauthorized.'], 403);
        }

        if (in_array($job->status, ['REQUESTED', 'REJECTED', 'CANCELLED'])) {
            return response()->json(['success' => false, 'message' => 'Cannot complete job directly from status: ' . $job->status], 422);
        }

        $oldStatus = $job->status;
        $job->update([
            'status' => 'COMPLETED',
            'completed_at' => $job->completed_at ?? now(),
            'confirmed_at' => now(),
        ]);

        JobStatusHistory::create([
            'job_id' => $job->id,
            'changed_by_user_id' => $user->id,
            'from_status' => $oldStatus,
            'to_status' => 'COMPLETED',
            'notes' => $request->notes ?? 'Job completed.',
        ]);

        if ($job->worker && $job->worker->workerProfile) {
            $job->worker->workerProfile()->increment('completed_jobs_count');
        }

        $recipientId = ($user->id === $job->customer_id) ? $job->worker_id : $job->customer_id;
        Notification::create([
            'user_id' => $recipientId,
            'title' => 'Job Completed',
            'body' => 'The job has been marked as COMPLETED!',
            'type' => 'job_status',
            'reference_id' => $job->id,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Job completed successfully.',
            'data' => $job->fresh(['statusHistory']),
        ]);
    }

    /**
     * Cancel a job.
     */
    public function cancel(int $id, Request $request): JsonResponse
    {
        $request->validate(['reason' => 'required|string']);

        $job = Job::findOrFail($id);
        $user = $request->user();

        if ($job->customer_id !== $user->id && $job->worker_id !== $user->id) {
            return response()->json(['success' => false, 'message' => 'Unauthorized.'], 403);
        }

        if (in_array($job->status, ['COMPLETED', 'WORK_COMPLETED'])) {
            return response()->json(['success' => false, 'message' => 'Completed jobs cannot be cancelled.'], 422);
        }

        $oldStatus = $job->status;
        $job->update([
            'status' => 'CANCELLED',
            'cancel_reason' => $request->reason,
        ]);

        JobStatusHistory::create([
            'job_id' => $job->id,
            'changed_by_user_id' => $user->id,
            'from_status' => $oldStatus,
            'to_status' => 'CANCELLED',
            'notes' => $request->reason,
        ]);

        $recipientId = ($user->id === $job->customer_id) ? $job->worker_id : $job->customer_id;
        Notification::create([
            'user_id' => $recipientId,
            'title' => 'Job Cancelled',
            'body' => 'Job was cancelled: ' . $request->reason,
            'type' => 'job_status',
            'reference_id' => $job->id,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Job cancelled successfully.',
            'data' => $job->fresh(['statusHistory']),
        ]);
    }

    /**
     * Fetch status history timeline for a job.
     */
    public function history(int $id, Request $request): JsonResponse
    {
        $job = Job::findOrFail($id);
        $user = $request->user();

        if ($job->customer_id !== $user->id && $job->worker_id !== $user->id && $user->role !== 'admin') {
            return response()->json(['success' => false, 'message' => 'Unauthorized.'], 403);
        }

        $history = JobStatusHistory::where('job_id', $id)
            ->with('changedBy')
            ->orderBy('id', 'asc')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $history,
        ]);
    }

    /**
     * Update job status (generic helper).
     */
    public function updateStatus(int $id, Request $request): JsonResponse
    {
        $request->validate([
            'status' => 'required|in:ON_THE_WAY,ARRIVED,WORK_STARTED,IN_PROGRESS,WORK_COMPLETED,COMPLETED',
            'notes' => 'nullable|string',
        ]);

        $job = Job::findOrFail($id);

        if ($job->status === 'COMPLETED') {
            return response()->json(['success' => false, 'message' => 'Completed job status cannot be modified.'], 422);
        }

        $statusMap = [
            'WORK_STARTED' => 'IN_PROGRESS',
            'WORK_COMPLETED' => 'COMPLETED',
        ];

        $targetStatus = $statusMap[$request->status] ?? $request->status;

        if ($targetStatus === 'IN_PROGRESS') {
            return $this->start($id, $request);
        } elseif ($targetStatus === 'COMPLETED') {
            return $this->complete($id, $request);
        }

        $oldStatus = $job->status;
        $job->update(['status' => $targetStatus]);

        JobStatusHistory::create([
            'job_id' => $job->id,
            'changed_by_user_id' => $request->user()->id,
            'from_status' => $oldStatus,
            'to_status' => $targetStatus,
            'notes' => $request->notes,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Job status updated to ' . $targetStatus,
            'data' => $job->fresh(['statusHistory']),
        ]);
    }

    public function confirmCompletion(int $id, Request $request): JsonResponse
    {
        return $this->complete($id, $request);
    }

    public function uploadProof(int $id, Request $request): JsonResponse
    {
        $request->validate([
            'image' => 'required|image|mimes:jpg,jpeg,png|max:5120',
            'caption' => 'nullable|string',
        ]);

        $job = Job::findOrFail($id);

        $path = $request->file('image')->store('work_proofs', 'public');

        $jobImage = JobImage::create([
            'job_id' => $job->id,
            'uploader_id' => $request->user()->id,
            'image_type' => 'work_proof',
            'image_path' => $path,
            'caption' => $request->caption,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Work proof image uploaded.',
            'data' => $jobImage,
        ]);
    }
}
