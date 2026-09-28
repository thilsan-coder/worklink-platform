<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\WorkerDocument;
use App\Models\WorkerPortfolio;
use App\Models\WorkerProfile;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class WorkerProfileController extends Controller
{
    public function show(Request $request): JsonResponse
    {
        $profile = WorkerProfile::firstOrCreate(['user_id' => $request->user()->id]);

        return response()->json([
            'success' => true,
            'data' => $profile->load(['skills', 'documents', 'portfolioItems']),
        ]);
    }

    public function update(Request $request): JsonResponse
    {
        $profile = WorkerProfile::firstOrCreate(['user_id' => $request->user()->id]);

        $validated = $request->validate([
            'bio' => 'nullable|string',
            'experience_years' => 'nullable|integer|min:0|max:60',
            'hourly_rate' => 'nullable|numeric|min:0',
            'service_area_radius_km' => 'nullable|integer|min:1|max:200',
            'current_latitude' => 'nullable|numeric',
            'current_longitude' => 'nullable|numeric',
            'address' => 'nullable|string|max:255',
            'availability_status' => 'nullable|in:available,busy,offline',
        ]);

        $profile->update($validated);

        if ($request->has('skill_ids')) {
            $profile->skills()->sync($request->input('skill_ids', []));
        }

        return response()->json([
            'success' => true,
            'message' => 'Worker profile updated successfully.',
            'data' => $profile->fresh(['skills', 'documents', 'portfolioItems']),
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

        // Update verification status to pending for review
        $profile->update(['verification_status' => 'pending']);

        return response()->json([
            'success' => true,
            'message' => 'Document uploaded for admin verification.',
            'data' => $doc,
        ]);
    }

    public function uploadPortfolio(Request $request): JsonResponse
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
        ]);
    }
}
