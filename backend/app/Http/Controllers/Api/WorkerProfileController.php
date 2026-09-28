<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\WorkerDocument;
use App\Models\WorkerPortfolio;
use App\Models\WorkerProfile;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class WorkerProfileController extends Controller
{
    public function show(Request $request): JsonResponse
    {
        $user = $request->user();
        $profile = WorkerProfile::firstOrCreate(['user_id' => $user->id]);
        $profile->load(['skills.category', 'documents', 'portfolioItems']);

        $completion = $this->calculateCompletionPercentage($user, $profile);

        return response()->json([
            'success' => true,
            'data' => [
                'user' => $user,
                'profile' => $profile,
                'completion_percentage' => $completion,
            ],
        ]);
    }

    public function update(Request $request): JsonResponse
    {
        $user = $request->user();
        $profile = WorkerProfile::firstOrCreate(['user_id' => $user->id]);

        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'email' => 'sometimes|nullable|email|unique:users,email,' . $user->id,
            'bio' => 'nullable|string',
            'experience_years' => 'nullable|integer|min:0|max:60',
            'hourly_rate' => 'nullable|numeric|min:0',
            'service_area_radius_km' => 'nullable|integer|min:1|max:200',
            'current_latitude' => 'nullable|numeric',
            'current_longitude' => 'nullable|numeric',
            'address' => 'nullable|string|max:255',
            'availability_status' => 'nullable|in:available,busy,offline',
            'skill_ids' => 'nullable|array',
            'skill_ids.*' => 'exists:skills,id',
        ]);

        if (isset($validated['name']) || isset($validated['email'])) {
            $userUpdate = [];
            if (isset($validated['name'])) $userUpdate['name'] = $validated['name'];
            if (isset($validated['email'])) $userUpdate['email'] = $validated['email'];
            $user->update($userUpdate);
        }

        $profile->update($validated);

        if ($request->has('skill_ids')) {
            $profile->skills()->sync($request->input('skill_ids', []));
        }

        $freshProfile = $profile->fresh(['skills.category', 'documents', 'portfolioItems']);
        $completion = $this->calculateCompletionPercentage($user->fresh(), $freshProfile);

        return response()->json([
            'success' => true,
            'message' => 'Worker profile updated successfully.',
            'data' => [
                'user' => $user->fresh(),
                'profile' => $freshProfile,
                'completion_percentage' => $completion,
            ],
        ]);
    }

    // Portfolio Management
    public function indexPortfolio(Request $request): JsonResponse
    {
        $profile = WorkerProfile::firstOrCreate(['user_id' => $request->user()->id]);
        $items = WorkerPortfolio::where('worker_profile_id', $profile->id)->get();

        return response()->json([
            'success' => true,
            'data' => $items,
        ]);
    }

    public function storePortfolio(Request $request): JsonResponse
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'image' => 'required|image|mimes:jpg,jpeg,png,webp|max:5120',
        ]);

        $profile = WorkerProfile::firstOrCreate(['user_id' => $request->user()->id]);
        $imagePath = $request->file('image')->store('portfolios', 'public');

        $item = WorkerPortfolio::create([
            'worker_profile_id' => $profile->id,
            'title' => $request->title,
            'description' => $request->description,
            'image_path' => $imagePath,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Portfolio item added.',
            'data' => $item,
        ], 201);
    }

    public function updatePortfolio(int $id, Request $request): JsonResponse
    {
        $profile = WorkerProfile::firstOrCreate(['user_id' => $request->user()->id]);
        $item = WorkerPortfolio::findOrFail($id);

        if ((int) $item->worker_profile_id !== (int) $profile->id) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized portfolio modification.',
            ], 403);
        }

        $validated = $request->validate([
            'title' => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'image' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:5120',
        ]);

        if ($request->hasFile('image')) {
            if ($item->image_path && Storage::disk('public')->exists($item->image_path)) {
                Storage::disk('public')->delete($item->image_path);
            }
            $validated['image_path'] = $request->file('image')->store('portfolios', 'public');
        }

        $item->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Portfolio item updated.',
            'data' => $item->fresh(),
        ]);
    }

    public function destroyPortfolio(int $id, Request $request): JsonResponse
    {
        $profile = WorkerProfile::firstOrCreate(['user_id' => $request->user()->id]);
        $item = WorkerPortfolio::findOrFail($id);

        if ((int) $item->worker_profile_id !== (int) $profile->id) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized portfolio deletion.',
            ], 403);
        }

        if ($item->image_path && Storage::disk('public')->exists($item->image_path)) {
            Storage::disk('public')->delete($item->image_path);
        }

        $item->delete();

        return response()->json([
            'success' => true,
            'message' => 'Portfolio item deleted successfully.',
        ]);
    }

    // Verification Workflow
    public function verificationStatus(Request $request): JsonResponse
    {
        $profile = WorkerProfile::firstOrCreate(['user_id' => $request->user()->id]);

        return response()->json([
            'success' => true,
            'data' => [
                'verification_status' => $profile->verification_status,
                'rejection_reason' => $profile->verification_rejection_reason,
                'verified_at' => $profile->verified_at,
                'documents' => $profile->documents,
            ],
        ]);
    }

    public function uploadDocument(Request $request): JsonResponse
    {
        $request->validate([
            'document_type' => 'required|in:id_card,license,certificate,other',
            'document_number' => 'nullable|string',
            'file' => 'required|file|mimes:jpg,jpeg,png,pdf|max:5120',
        ]);

        $profile = WorkerProfile::firstOrCreate(['user_id' => $request->user()->id]);
        $filePath = $request->file('file')->store('worker_documents', 'public');

        $doc = WorkerDocument::create([
            'worker_profile_id' => $profile->id,
            'document_type' => $request->document_type,
            'document_number' => $request->document_number,
            'file_path' => $filePath,
            'file_type' => $request->file('file')->getClientOriginalExtension(),
            'status' => 'pending',
        ]);

        // Submit to admin under review status (NOT self-approved!)
        $profile->update(['verification_status' => 'pending']);

        return response()->json([
            'success' => true,
            'message' => 'Document submitted for admin verification.',
            'data' => $doc,
        ]);
    }

    private function calculateCompletionPercentage($user, $profile): int
    {
        $fields = [
            ! empty($user->name),
            ! empty($user->phone),
            ! empty($user->email),
            ! empty($user->avatar),
            ! empty($profile->bio),
            ($profile->experience_years > 0),
            ($profile->hourly_rate > 0),
            ! empty($profile->address),
            ($profile->skills && $profile->skills->isNotEmpty()),
            ($profile->portfolioItems && $profile->portfolioItems->isNotEmpty()),
            ($profile->verification_status === 'verified'),
        ];

        $completed = count(array_filter($fields));
        return (int) round(($completed / count($fields)) * 100);
    }
}
