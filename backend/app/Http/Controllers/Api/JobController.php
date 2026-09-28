<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Chat;
use App\Models\Job;
use App\Models\JobImage;
use App\Models\JobLocation;
use App\Models\JobStatusHistory;
use App\Models\Notification;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class JobController extends Controller
{
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

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        $jobs = $query->latest()->paginate($request->integer('per_page', 15));

        return response()->json([
            'success' => true,
            'data' => $jobs,
        ]);
    }

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

        $jobNumber = 'WLJ-' . date('Ymd') . '-' . Str::upper(Str::random(5));

        $job = Job::create([
            'job_number' => $jobNumber,
            'customer_id' => $request->user()->id,
            'worker_id' => $validated['worker_id'],
            'category_id' => $validated['category_id'],
            'skill_id' => $validated['skill_id'] ?? null,
            'title' => $validated['title'],
            'description' => $validated['description'],
            'estimated_cost' => $validated['estimated_cost'] ?? null,
            'scheduled_at' => $validated['scheduled_at'] ?? null,
            'status' => 'REQUESTED',
        ]);

        // Save Location
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

        // Record Initial History
        JobStatusHistory::create([
            'job_id' => $job->id,
            'changed_by_user_id' => $request->user()->id,
            'from_status' => null,
            'to_status' => 'REQUESTED',
            'notes' => 'Job request initiated by customer.',
            'latitude' => $validated['latitude'],
            'longitude' => $validated['longitude'],
        ]);

        // Create Chat room
        Chat::create([
            'job_id' => $job->id,
            'customer_id' => $request->user()->id,
            'worker_id' => $validated['worker_id'],
        ]);

        // Create Notification for Worker
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
            'data' => $job->load(['location', 'category', 'skill', 'customer']),
        ], 201);
    }

    public function show(int $id, Request $request): JsonResponse
    {
        $job = Job::with(['customer', 'worker', 'category', 'skill', 'location', 'images', 'statusHistory', 'review', 'chat'])
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

    public function accept(int $id, Request $request): JsonResponse
    {
        $job = Job::findOrFail($id);

        if ($job->worker_id !== $request->user()->id) {
            return response()->json(['success' => false, 'message' => 'Unauthorized.'], 403);
        }

        if ($job->status !== 'REQUESTED') {
            return response()->json(['success' => false, 'message' => 'Job status cannot be accepted.'], 422);
        }

        $job->update(['status' => 'ACCEPTED']);

        JobStatusHistory::create([
            'job_id' => $job->id,
            'changed_by_user_id' => $request->user()->id,
            'from_status' => 'REQUESTED',
            'to_status' => 'ACCEPTED',
            'notes' => 'Worker accepted the job request.',
        ]);

        Notification::create([
            'user_id' => $job->customer_id,
            'title' => 'Job Accepted',
            'body' => 'The worker accepted your job request.',
            'type' => 'job_status',
            'reference_id' => $job->id,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Job accepted.',
            'data' => $job->fresh(),
        ]);
    }

    public function reject(int $id, Request $request): JsonResponse
    {
        $request->validate(['reason' => 'required|string']);

        $job = Job::findOrFail($id);

        if ($job->worker_id !== $request->user()->id) {
            return response()->json(['success' => false, 'message' => 'Unauthorized.'], 403);
        }

        $job->update([
            'status' => 'REJECTED',
            'reject_reason' => $request->reason,
        ]);

        JobStatusHistory::create([
            'job_id' => $job->id,
            'changed_by_user_id' => $request->user()->id,
            'from_status' => $job->status,
            'to_status' => 'REJECTED',
            'notes' => $request->reason,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Job request rejected.',
            'data' => $job->fresh(),
        ]);
    }

    public function updateStatus(int $id, Request $request): JsonResponse
    {
        $request->validate([
            'status' => 'required|in:ON_THE_WAY,ARRIVED,WORK_STARTED,WORK_COMPLETED',
            'notes' => 'nullable|string',
            'latitude' => 'nullable|numeric',
            'longitude' => 'nullable|numeric',
        ]);

        $job = Job::findOrFail($id);

        if ($job->worker_id !== $request->user()->id) {
            return response()->json(['success' => false, 'message' => 'Unauthorized.'], 403);
        }

        $oldStatus = $job->status;
        $newStatus = $request->status;

        $updateData = ['status' => $newStatus];
        if ($newStatus === 'WORK_STARTED') {
            $updateData['started_at'] = now();
        } elseif ($newStatus === 'WORK_COMPLETED') {
            $updateData['completed_at'] = now();
        }

        $job->update($updateData);

        JobStatusHistory::create([
            'job_id' => $job->id,
            'changed_by_user_id' => $request->user()->id,
            'from_status' => $oldStatus,
            'to_status' => $newStatus,
            'notes' => $request->notes,
            'latitude' => $request->latitude,
            'longitude' => $request->longitude,
        ]);

        Notification::create([
            'user_id' => $job->customer_id,
            'title' => 'Job Status Update',
            'body' => 'Job status updated to: ' . str_replace('_', ' ', $newStatus),
            'type' => 'job_status',
            'reference_id' => $job->id,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Job status updated to ' . $newStatus,
            'data' => $job->fresh(),
        ]);
    }

    public function confirmCompletion(int $id, Request $request): JsonResponse
    {
        $job = Job::findOrFail($id);

        if ($job->customer_id !== $request->user()->id) {
            return response()->json(['success' => false, 'message' => 'Only customer can confirm job completion.'], 403);
        }

        $job->update([
            'status' => 'COMPLETED',
            'confirmed_at' => now(),
        ]);

        JobStatusHistory::create([
            'job_id' => $job->id,
            'changed_by_user_id' => $request->user()->id,
            'from_status' => 'WORK_COMPLETED',
            'to_status' => 'COMPLETED',
            'notes' => 'Customer confirmed work completion.',
        ]);

        // Increment worker completed jobs count
        $job->worker->workerProfile()->increment('completed_jobs_count');

        Notification::create([
            'user_id' => $job->worker_id,
            'title' => 'Job Confirmed Completed',
            'body' => 'The customer confirmed completion of the job!',
            'type' => 'job_status',
            'reference_id' => $job->id,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Job marked as completed.',
            'data' => $job->fresh(),
        ]);
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

    public function cancel(int $id, Request $request): JsonResponse
    {
        $request->validate(['reason' => 'required|string']);

        $job = Job::findOrFail($id);

        $user = $request->user();
        if ($job->customer_id !== $user->id && $job->worker_id !== $user->id) {
            return response()->json(['success' => false, 'message' => 'Unauthorized.'], 403);
        }

        $job->update([
            'status' => 'CANCELLED',
            'cancel_reason' => $request->reason,
        ]);

        JobStatusHistory::create([
            'job_id' => $job->id,
            'changed_by_user_id' => $user->id,
            'from_status' => $job->status,
            'to_status' => 'CANCELLED',
            'notes' => $request->reason,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Job cancelled successfully.',
            'data' => $job->fresh(),
        ]);
    }
}
