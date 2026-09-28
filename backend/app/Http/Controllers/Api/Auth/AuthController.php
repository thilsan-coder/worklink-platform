<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use App\Models\CustomerProfile;
use App\Models\OtpRequest;
use App\Models\User;
use App\Models\WorkerProfile;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class AuthController extends Controller
{
    /**
     * Send OTP to phone number.
     */
    public function sendOtp(Request $request): JsonResponse
    {
        $request->validate([
            'phone' => 'required|string|min:8|max:20',
        ]);

        $phone = preg_replace('/[^0-9+]/', '', $request->phone);

        // Generate 6-digit OTP
        $otpCode = str_pad((string) random_int(100000, 999999), 6, '0', STR_PAD_LEFT);

        OtpRequest::create([
            'phone' => $phone,
            'otp_code' => $otpCode,
            'attempts' => 0,
            'is_verified' => false,
            'expires_at' => now()->addMinutes(10),
        ]);

        // In production, integrate with SMS Gateway (Twilio / AWS SNS / Local Provider)
        return response()->json([
            'success' => true,
            'message' => 'OTP dispatched successfully to phone.',
            'data' => [
                'phone' => $phone,
                'expires_in_seconds' => 600,
                // Included in non-production debug for testing ease
                'debug_otp' => app()->environment('local', 'testing') ? $otpCode : null,
            ],
        ]);
    }

    /**
     * Verify OTP and return Sanctum Bearer token.
     */
    public function verifyOtp(Request $request): JsonResponse
    {
        $request->validate([
            'phone' => 'required|string',
            'otp_code' => 'required|string|size:6',
            'role' => 'nullable|in:customer,worker,both',
            'name' => 'nullable|string|max:255',
        ]);

        $phone = preg_replace('/[^0-9+]/', '', $request->phone);

        $otpRecord = OtpRequest::where('phone', $phone)
            ->where('is_verified', false)
            ->where('expires_at', '>', now())
            ->latest()
            ->first();

        if (! $otpRecord || $otpRecord->otp_code !== $request->otp_code) {
            if ($otpRecord) {
                $otpRecord->increment('attempts');
            }

            return response()->json([
                'success' => false,
                'message' => 'Invalid or expired OTP code.',
            ], 422);
        }

        $otpRecord->update(['is_verified' => true]);

        // Find or create User with account linking
        $user = User::where('phone', $phone)->first();
        $isNewUser = false;

        if (! $user) {
            $isNewUser = true;
            $userRole = $request->role ?? 'customer';

            $user = User::create([
                'name' => $request->name ?? 'User ' . substr($phone, -4),
                'phone' => $phone,
                'phone_verified_at' => now(),
                'role' => $userRole,
                'status' => 'active',
            ]);

            if (in_array($userRole, ['customer', 'both'])) {
                CustomerProfile::create(['user_id' => $user->id]);
            }

            if (in_array($userRole, ['worker', 'both'])) {
                WorkerProfile::create(['user_id' => $user->id]);
            }
        }

        $token = $user->createToken('worklink-mobile-token')->plainTextToken;

        return response()->json([
            'success' => true,
            'message' => 'Authentication successful.',
            'data' => [
                'token' => $token,
                'token_type' => 'Bearer',
                'is_new_user' => $isNewUser,
                'user' => $user->load(['customerProfile', 'workerProfile']),
            ],
        ]);
    }

    /**
     * Google Social Login & Account Linking.
     */
    public function googleLogin(Request $request): JsonResponse
    {
        $request->validate([
            'google_id' => 'required|string',
            'email' => 'required|email',
            'name' => 'nullable|string',
            'avatar' => 'nullable|string',
            'role' => 'nullable|in:customer,worker,both',
        ]);

        $user = User::where('google_id', $request->google_id)
            ->orWhere('email', $request->email)
            ->first();

        $isNewUser = false;

        if (! $user) {
            $isNewUser = true;
            $userRole = $request->role ?? 'customer';

            $user = User::create([
                'name' => $request->name ?? 'User',
                'email' => $request->email,
                'google_id' => $request->google_id,
                'avatar' => $request->avatar,
                'email_verified_at' => now(),
                'role' => $userRole,
                'status' => 'active',
            ]);

            if (in_array($userRole, ['customer', 'both'])) {
                CustomerProfile::create(['user_id' => $user->id]);
            }

            if (in_array($userRole, ['worker', 'both'])) {
                WorkerProfile::create(['user_id' => $user->id]);
            }
        } else {
            // Account linking
            $user->update([
                'google_id' => $user->google_id ?? $request->google_id,
                'avatar' => $user->avatar ?? $request->avatar,
            ]);
        }

        $token = $user->createToken('worklink-google-token')->plainTextToken;

        return response()->json([
            'success' => true,
            'message' => 'Google authentication successful.',
            'data' => [
                'token' => $token,
                'token_type' => 'Bearer',
                'is_new_user' => $isNewUser,
                'user' => $user->load(['customerProfile', 'workerProfile']),
            ],
        ]);
    }

    /**
     * Facebook Social Login & Account Linking.
     */
    public function facebookLogin(Request $request): JsonResponse
    {
        $request->validate([
            'facebook_id' => 'required|string',
            'email' => 'nullable|email',
            'name' => 'nullable|string',
            'avatar' => 'nullable|string',
            'role' => 'nullable|in:customer,worker,both',
        ]);

        $query = User::where('facebook_id', $request->facebook_id);
        if ($request->email) {
            $query->orWhere('email', $request->email);
        }

        $user = $query->first();
        $isNewUser = false;

        if (! $user) {
            $isNewUser = true;
            $userRole = $request->role ?? 'customer';

            $user = User::create([
                'name' => $request->name ?? 'User',
                'email' => $request->email,
                'facebook_id' => $request->facebook_id,
                'avatar' => $request->avatar,
                'role' => $userRole,
                'status' => 'active',
            ]);

            if (in_array($userRole, ['customer', 'both'])) {
                CustomerProfile::create(['user_id' => $user->id]);
            }

            if (in_array($userRole, ['worker', 'both'])) {
                WorkerProfile::create(['user_id' => $user->id]);
            }
        } else {
            $user->update([
                'facebook_id' => $user->facebook_id ?? $request->facebook_id,
                'avatar' => $user->avatar ?? $request->avatar,
            ]);
        }

        $token = $user->createToken('worklink-facebook-token')->plainTextToken;

        return response()->json([
            'success' => true,
            'message' => 'Facebook authentication successful.',
            'data' => [
                'token' => $token,
                'token_type' => 'Bearer',
                'is_new_user' => $isNewUser,
                'user' => $user->load(['customerProfile', 'workerProfile']),
            ],
        ]);
    }

    /**
     * Revoke current API token.
     */
    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'success' => true,
            'message' => 'Successfully logged out.',
        ]);
    }

    /**
     * Get authenticated user details.
     */
    public function userProfile(Request $request): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => $request->user()->load(['customerProfile', 'workerProfile.skills']),
        ]);
    }

    /**
     * Update user basic profile details.
     */
    public function updateProfile(Request $request): JsonResponse
    {
        $user = $request->user();

        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'email' => 'sometimes|nullable|email|unique:users,email,' . $user->id,
            'avatar' => 'sometimes|nullable|string',
            'fcm_token' => 'sometimes|nullable|string',
        ]);

        $user->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Profile updated successfully.',
            'data' => $user->fresh(['customerProfile', 'workerProfile']),
        ]);
    }
}
