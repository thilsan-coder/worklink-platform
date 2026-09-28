<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CustomerProfile;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CustomerProfileController extends Controller
{
    public function show(Request $request): JsonResponse
    {
        $profile = CustomerProfile::firstOrCreate(['user_id' => $request->user()->id]);

        return response()->json([
            'success' => true,
            'data' => $profile->load('user'),
        ]);
    }

    public function update(Request $request): JsonResponse
    {
        $profile = CustomerProfile::firstOrCreate(['user_id' => $request->user()->id]);

        $validated = $request->validate([
            'default_address' => 'nullable|string|max:255',
            'default_latitude' => 'nullable|numeric',
            'default_longitude' => 'nullable|numeric',
            'emergency_contact' => 'nullable|string|max:20',
            'preferred_payment_method' => 'nullable|string|max:50',
        ]);

        $profile->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Customer profile updated successfully.',
            'data' => $profile->fresh('user'),
        ]);
    }
}
