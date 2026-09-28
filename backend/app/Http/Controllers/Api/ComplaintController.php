<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Complaint;
use App\Models\ComplaintAttachment;
use App\Models\Job;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ComplaintController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'job_id' => 'required|exists:jobs,id',
            'against_user_id' => 'required|exists:users,id',
            'subject' => 'required|string|max:255',
            'description' => 'required|string',
            'attachments.*' => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:5120',
        ]);

        $job = Job::findOrFail($validated['job_id']);
        $user = $request->user();

        if ($job->customer_id !== $user->id && $job->worker_id !== $user->id) {
            return response()->json(['success' => false, 'message' => 'Unauthorized complaint submission.'], 403);
        }

        $complaintNumber = 'CMP-' . date('Ymd') . '-' . Str::upper(Str::random(5));

        $complaint = Complaint::create([
            'complaint_number' => $complaintNumber,
            'job_id' => $job->id,
            'complainant_id' => $user->id,
            'against_user_id' => $validated['against_user_id'],
            'subject' => $validated['subject'],
            'description' => $validated['description'],
            'status' => 'OPEN',
        ]);

        // Update Job status to DISPUTED if open complaint
        $job->update(['status' => 'DISPUTED']);

        if ($request->hasFile('attachments')) {
            foreach ($request->file('attachments') as $file) {
                $path = $file->store('complaint_attachments', 'public');
                ComplaintAttachment::create([
                    'complaint_id' => $complaint->id,
                    'file_path' => $path,
                    'file_type' => $file->getClientOriginalExtension(),
                ]);
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'Complaint submitted successfully to Web Admin panel.',
            'data' => $complaint->load(['job', 'attachments']),
        ], 201);
    }

    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        $complaints = Complaint::with(['job', 'againstUser', 'attachments'])
            ->where('complainant_id', $user->id)
            ->latest()
            ->get();

        return response()->json([
            'success' => true,
            'data' => $complaints,
        ]);
    }
}
