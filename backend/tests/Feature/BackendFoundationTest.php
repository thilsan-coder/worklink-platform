<?php

namespace Tests\Feature;

use App\Models\AdminUser;
use App\Models\Category;
use App\Models\CustomerProfile;
use App\Models\Job;
use App\Models\JobLocation;
use App\Models\Skill;
use App\Models\User;
use App\Models\WorkerProfile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BackendFoundationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_categories_and_skills_are_seeded(): void
    {
        $this->assertDatabaseHas('admin_users', ['username' => 'admin']);
        $this->assertGreaterThan(0, Category::count());
        $this->assertGreaterThan(0, Skill::count());
    }

    public function test_public_categories_api(): void
    {
        $response = $this->getJson('/api/categories');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    '*' => ['id', 'name', 'slug', 'skills'],
                ],
            ]);
    }

    public function test_phone_otp_dispatch_and_verification(): void
    {
        // 1. Dispatch OTP
        $sendResponse = $this->postJson('/api/auth/send-otp', [
            'phone' => '+1234567890',
        ]);

        $sendResponse->assertStatus(200)
            ->assertJson(['success' => true]);

        $otpCode = $sendResponse->json('data.debug_otp');
        $this->assertNotNull($otpCode);

        // 2. Verify OTP and Register Customer
        $verifyResponse = $this->postJson('/api/auth/verify-otp', [
            'phone' => '+1234567890',
            'otp_code' => $otpCode,
            'role' => 'customer',
            'name' => 'John Customer',
        ]);

        $verifyResponse->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => ['token', 'user'],
            ]);

        $this->assertDatabaseHas('users', ['phone' => '+1234567890', 'name' => 'John Customer']);
    }

    public function test_admin_authentication(): void
    {
        $response = $this->postJson('/api/admin/login', [
            'username_or_email' => 'admin',
            'password' => 'AdminPass123!',
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => ['token', 'admin'],
            ]);
    }

    public function test_job_lifecycle_and_model_relationships(): void
    {
        // Create Customer User
        $customer = User::create([
            'name' => 'Test Customer',
            'phone' => '+1999888001',
            'role' => 'customer',
        ]);
        CustomerProfile::create(['user_id' => $customer->id]);

        // Create Worker User
        $worker = User::create([
            'name' => 'Test Worker',
            'phone' => '+1999888002',
            'role' => 'worker',
        ]);
        WorkerProfile::create(['user_id' => $worker->id]);

        $category = Category::first();

        // Submit Job Request as Customer
        $token = $customer->createToken('test-token')->plainTextToken;

        $jobData = [
            'worker_id' => $worker->id,
            'category_id' => $category->id,
            'title' => 'Fix Leaking Sink Pipe',
            'description' => 'Kitchen sink leaking under cabinet.',
            'estimated_cost' => 75.00,
            'address_line1' => '123 Market Street',
            'city' => 'Metropolis',
            'latitude' => 37.7749,
            'longitude' => -122.4194,
        ];

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/jobs', $jobData);

        $response->assertStatus(201)
            ->assertJson(['success' => true]);

        $jobId = $response->json('data.id');

        // Verify Model Relationships
        $job = Job::with(['customer', 'worker', 'location', 'statusHistory', 'chat'])->find($jobId);
        $this->assertNotNull($job);
        $this->assertEquals($customer->id, $job->customer->id);
        $this->assertEquals($worker->id, $job->worker->id);
        $this->assertNotNull($job->location);
        $this->assertNotNull($job->chat);
        $this->assertCount(1, $job->statusHistory);
    }
}
