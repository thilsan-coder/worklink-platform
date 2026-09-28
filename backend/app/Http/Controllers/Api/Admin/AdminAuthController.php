<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdminUser;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AdminAuthController extends Controller
{
    public function login(Request $request): JsonResponse
    {
        $request->validate([
            'username_or_email' => 'required|string',
            'password' => 'required|string',
        ]);

        $admin = AdminUser::where('username', $request->username_or_email)
            ->orWhere('email', $request->username_or_email)
            ->first();

        if (! $admin || ! Hash::check($request->password, $admin->password)) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid administrative credentials.',
            ], 401);
        }

        if (! $admin->is_active) {
            return response()->json([
                'success' => false,
                'message' => 'Admin account is deactivated.',
            ], 403);
        }

        $admin->update(['last_login_at' => now()]);

        $token = $admin->createToken('admin-panel-token', ['admin'])->plainTextToken;

        return response()->json([
            'success' => true,
            'message' => 'Admin authentication successful.',
            'data' => [
                'token' => $token,
                'token_type' => 'Bearer',
                'admin' => $admin,
            ],
        ]);
    }

    public function profile(Request $request): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => $request->user(),
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'success' => true,
            'message' => 'Admin logged out.',
        ]);
    }
}
