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
        $user = $request->user();
        $profile = CustomerProfile::firstOrCreate(['user_id' => $user->id]);

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
        $profile = CustomerProfile::firstOrCreate(['user_id' => $user->id]);

        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'email' => 'sometimes|nullable|email|unique:users,email,' . $user->id,
            'default_address' => 'nullable|string|max:255',
            'default_latitude' => 'nullable|numeric',
            'default_longitude' => 'nullable|numeric',
            'emergency_contact' => 'nullable|string|max:20',
            'preferred_payment_method' => 'nullable|string|max:50',
        ]);

        if (isset($validated['name']) || isset($validated['email'])) {
            $userUpdate = [];
            if (isset($validated['name'])) $userUpdate['name'] = $validated['name'];
            if (isset($validated['email'])) $userUpdate['email'] = $validated['email'];
            $user->update($userUpdate);
        }

        $profile->update($validated);
        $completion = $this->calculateCompletionPercentage($user->fresh(), $profile->fresh());

        return response()->json([
            'success' => true,
            'message' => 'Customer profile updated successfully.',
            'data' => [
                'user' => $user->fresh(),
                'profile' => $profile->fresh(),
                'completion_percentage' => $completion,
            ],
        ]);
    }

    private function calculateCompletionPercentage($user, $profile): int
    {
        $fields = [
            ! empty($user->name),
            ! empty($user->phone),
            ! empty($user->email),
            ! empty($user->avatar),
            ! empty($profile->default_address),
            ! empty($profile->emergency_contact),
            ! empty($profile->preferred_payment_method),
        ];

        $completed = count(array_filter($fields));
        return (int) round(($completed / count($fields)) * 100);
    }
}
