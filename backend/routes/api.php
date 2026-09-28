<?php

use App\Http\Controllers\Api\Admin\AdminAuthController;
use App\Http\Controllers\Api\Admin\AdminDashboardController;
use App\Http\Controllers\Api\Auth\AuthController;
use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\ChatController;
use App\Http\Controllers\Api\ComplaintController;
use App\Http\Controllers\Api\CustomerProfileController;
use App\Http\Controllers\Api\JobController;
use App\Http\Controllers\Api\NotificationController;
use App\Http\Controllers\Api\ProfilePhotoController;
use App\Http\Controllers\Api\ReviewController;
use App\Http\Controllers\Api\SkillController;
use App\Http\Controllers\Api\WorkerController;
use App\Http\Controllers\Api\WorkerProfileController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Public Routes
|--------------------------------------------------------------------------
*/

// Mobile Authentication Endpoints
Route::prefix('auth')->group(function () {
    Route::post('/send-otp', [AuthController::class, 'sendOtp']);
    Route::post('/verify-otp', [AuthController::class, 'verifyOtp']);
    Route::post('/google', [AuthController::class, 'googleLogin']);
    Route::post('/facebook', [AuthController::class, 'facebookLogin']);
});

// Admin Login Endpoint
Route::post('/admin/login', [AdminAuthController::class, 'login']);

// Public Service Discovery & Directory
Route::get('/categories', [CategoryController::class, 'index']);
Route::get('/categories/{id}', [CategoryController::class, 'show']);
Route::get('/skills', [SkillController::class, 'index']);
Route::get('/workers', [WorkerController::class, 'index']);
Route::get('/workers/{id}', [WorkerController::class, 'show']);
Route::get('/reviews/worker/{workerUserId}', [ReviewController::class, 'workerReviews']);

/*
|--------------------------------------------------------------------------
| Protected Mobile User Routes (Sanctum Auth)
|--------------------------------------------------------------------------
*/
Route::middleware('auth:sanctum')->group(function () {

    // Auth & User Profile
    Route::post('/auth/logout', [AuthController::class, 'logout']);
    Route::get('/user/profile', [AuthController::class, 'userProfile']);
    Route::put('/user/profile', [AuthController::class, 'updateProfile']);
    Route::post('/profile/photo', [ProfilePhotoController::class, 'upload']);
    Route::delete('/profile/photo', [ProfilePhotoController::class, 'destroy']);

    // Customer Profile Management
    Route::get('/customer/profile', [CustomerProfileController::class, 'show']);
    Route::put('/customer/profile', [CustomerProfileController::class, 'update']);

    // Worker Profile & Portfolio Management
    Route::get('/worker/profile', [WorkerProfileController::class, 'show']);
    Route::put('/worker/profile', [WorkerProfileController::class, 'update']);

    Route::get('/worker/portfolio', [WorkerProfileController::class, 'indexPortfolio']);
    Route::post('/worker/portfolio', [WorkerProfileController::class, 'storePortfolio']);
    Route::put('/worker/portfolio/{id}', [WorkerProfileController::class, 'updatePortfolio']);
    Route::delete('/worker/portfolio/{id}', [WorkerProfileController::class, 'destroyPortfolio']);

    Route::get('/worker/verification', [WorkerProfileController::class, 'verificationStatus']);
    Route::post('/worker/documents', [WorkerProfileController::class, 'uploadDocument']);

    // Jobs Core Management
    Route::get('/jobs', [JobController::class, 'index']);
    Route::post('/jobs', [JobController::class, 'store']);
    Route::get('/jobs/{id}', [JobController::class, 'show']);
    Route::post('/jobs/{id}/accept', [JobController::class, 'accept']);
    Route::post('/jobs/{id}/reject', [JobController::class, 'reject']);
    Route::post('/jobs/{id}/status', [JobController::class, 'updateStatus']);
    Route::post('/jobs/{id}/confirm', [JobController::class, 'confirmCompletion']);
    Route::post('/jobs/{id}/proof', [JobController::class, 'uploadProof']);
    Route::post('/jobs/{id}/cancel', [JobController::class, 'cancel']);

    // Chat System
    Route::get('/chats', [ChatController::class, 'index']);
    Route::get('/chats/{id}/messages', [ChatController::class, 'messages']);
    Route::post('/chats/{id}/messages', [ChatController::class, 'sendMessage']);

    // Reviews & Complaints
    Route::post('/reviews', [ReviewController::class, 'store']);
    Route::get('/complaints', [ComplaintController::class, 'index']);
    Route::post('/complaints', [ComplaintController::class, 'store']);

    // Notifications
    Route::get('/notifications', [NotificationController::class, 'index']);
    Route::post('/notifications/{id}/read', [NotificationController::class, 'markAsRead']);
    Route::post('/notifications/read-all', [NotificationController::class, 'markAllAsRead']);
});

/*
|--------------------------------------------------------------------------
| Protected Admin Panel Routes (Sanctum Auth + Admin Guard)
|--------------------------------------------------------------------------
*/
Route::middleware(['auth:sanctum', 'role:admin'])->prefix('admin')->group(function () {
    Route::get('/profile', [AdminAuthController::class, 'profile']);
    Route::post('/logout', [AdminAuthController::class, 'logout']);

    Route::get('/stats', [AdminDashboardController::class, 'stats']);
    Route::get('/workers/pending', [AdminDashboardController::class, 'pendingWorkers']);
    Route::post('/workers/{id}/verify', [AdminDashboardController::class, 'verifyWorker']);
    Route::post('/complaints/{id}/resolve', [AdminDashboardController::class, 'resolveComplaint']);
});
