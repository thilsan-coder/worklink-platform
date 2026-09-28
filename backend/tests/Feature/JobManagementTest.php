<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Job;
use App\Models\JobStatusHistory;
use App\Models\Notification;
use App\Models\OtpRequest;
use App\Models\User;
use App\Models\WorkerProfile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Tests\TestCase;

class JobManagementTest extends TestCase
{
    use RefreshDatabase;

    protected User $customer;
    protected User $worker1;
    protected User $worker2;
    protected Category $category;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();

        $this->customer = User::create([
            'name' => 'Test Customer',
            'phone' => '+94770000001',
            'role' => 'customer',
        ]);

        $this->worker1 = User::create([
            'name' => 'Worker One',
            'phone' => '+94770000002',
            'role' => 'worker',
        ]);
        WorkerProfile::create(['user_id' => $this->worker1->id, 'verification_status' => 'verified']);

        $this->worker2 = User::create([
            'name' => 'Worker Two',
            'phone' => '+94770000003',
            'role' => 'worker',
        ]);
        WorkerProfile::create(['user_id' => $this->worker2->id, 'verification_status' => 'verified']);

        $this->category = Category::firstOrCreate(['slug' => 'general-repair'], ['name' => 'General Repair']);
    }

    public function test_1_customer_creates_job(): void
    {
        $response = $this->actingAs($this->customer, 'sanctum')
            ->postJson('/api/jobs', [
                'worker_id' => $this->worker1->id,
                'category_id' => $this->category->id,
                'title' => 'Fix Leaking Tap',
                'description' => 'Kitchen tap is leaking heavily',
                'address_line1' => '123 Main St',
                'city' => 'Colombo',
                'latitude' => 6.9271,
                'longitude' => 79.8612,
            ]);

        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
                'data' => [
                    'title' => 'Fix Leaking Tap',
                    'status' => 'REQUESTED',
                ],
            ]);

        $this->assertDatabaseHas('jobs', ['title' => 'Fix Leaking Tap', 'status' => 'REQUESTED']);
    }

    public function test_2_worker_sees_assigned_request(): void
    {
        $job = Job::create([
            'job_number' => 'WLJ-TEST-001',
            'customer_id' => $this->customer->id,
            'worker_id' => $this->worker1->id,
            'category_id' => $this->category->id,
            'title' => 'Assigned Work',
            'description' => 'Need help',
            'status' => 'REQUESTED',
        ]);

        $response = $this->actingAs($this->worker1, 'sanctum')
            ->getJson('/api/jobs');

        $response->assertStatus(200)
            ->assertJsonFragment(['title' => 'Assigned Work']);
    }

    public function test_3_unauthorized_worker_cannot_modify_another_workers_job(): void
    {
        $job = Job::create([
            'job_number' => 'WLJ-TEST-002',
            'customer_id' => $this->customer->id,
            'worker_id' => $this->worker1->id,
            'category_id' => $this->category->id,
            'title' => 'Worker 1 Job',
            'description' => 'Private job',
            'status' => 'REQUESTED',
        ]);

        $response = $this->actingAs($this->worker2, 'sanctum')
            ->postJson("/api/jobs/{$job->id}/accept");

        $response->assertStatus(403);
    }

    public function test_4_worker_accepts_request(): void
    {
        $job = Job::create([
            'job_number' => 'WLJ-TEST-003',
            'customer_id' => $this->customer->id,
            'worker_id' => $this->worker1->id,
            'category_id' => $this->category->id,
            'title' => 'Acceptable Job',
            'description' => 'Please accept',
            'status' => 'REQUESTED',
        ]);

        $response = $this->actingAs($this->worker1, 'sanctum')
            ->postJson("/api/jobs/{$job->id}/accept");

        $response->assertStatus(200)
            ->assertJson(['success' => true]);

        $this->assertEquals('ACCEPTED', $job->fresh()->status);
    }

    public function test_5_worker_rejects_request(): void
    {
        $job = Job::create([
            'job_number' => 'WLJ-TEST-004',
            'customer_id' => $this->customer->id,
            'worker_id' => $this->worker1->id,
            'category_id' => $this->category->id,
            'title' => 'Rejectable Job',
            'description' => 'Please reject',
            'status' => 'REQUESTED',
        ]);

        $response = $this->actingAs($this->worker1, 'sanctum')
            ->postJson("/api/jobs/{$job->id}/reject", [
                'reason' => 'Schedule fully booked',
            ]);

        $response->assertStatus(200);
        $this->assertEquals('REJECTED', $job->fresh()->status);
        $this->assertEquals('Schedule fully booked', $job->fresh()->reject_reason);
    }

    public function test_6_status_transition_validation(): void
    {
        $job = Job::create([
            'job_number' => 'WLJ-TEST-005',
            'customer_id' => $this->customer->id,
            'worker_id' => $this->worker1->id,
            'category_id' => $this->category->id,
            'title' => 'Direct Complete Invalid',
            'description' => 'Invalid transition',
            'status' => 'REQUESTED',
        ]);

        $response = $this->actingAs($this->worker1, 'sanctum')
            ->postJson("/api/jobs/{$job->id}/complete");

        $response->assertStatus(422);
    }

    public function test_7_scheduling(): void
    {
        $job = Job::create([
            'job_number' => 'WLJ-TEST-006',
            'customer_id' => $this->customer->id,
            'worker_id' => $this->worker1->id,
            'category_id' => $this->category->id,
            'title' => 'Schedulable Job',
            'description' => 'Schedule test',
            'status' => 'ACCEPTED',
        ]);

        $futureDate = date('Y-m-d H:i:s', strtotime('+2 days'));

        $response = $this->actingAs($this->worker1, 'sanctum')
            ->postJson("/api/jobs/{$job->id}/schedule", [
                'scheduled_at' => $futureDate,
            ]);

        $response->assertStatus(200);
        $this->assertEquals('SCHEDULED', $job->fresh()->status);
        $this->assertNotNull($job->fresh()->scheduled_at);
    }

    public function test_8_start_job(): void
    {
        $job = Job::create([
            'job_number' => 'WLJ-TEST-007',
            'customer_id' => $this->customer->id,
            'worker_id' => $this->worker1->id,
            'category_id' => $this->category->id,
            'title' => 'Start Work Test',
            'description' => 'Starting work',
            'status' => 'ACCEPTED',
        ]);

        $response = $this->actingAs($this->worker1, 'sanctum')
            ->postJson("/api/jobs/{$job->id}/start");

        $response->assertStatus(200);
        $this->assertEquals('IN_PROGRESS', $job->fresh()->status);
    }

    public function test_9_complete_job(): void
    {
        $job = Job::create([
            'job_number' => 'WLJ-TEST-008',
            'customer_id' => $this->customer->id,
            'worker_id' => $this->worker1->id,
            'category_id' => $this->category->id,
            'title' => 'Complete Work Test',
            'description' => 'Completing work',
            'status' => 'IN_PROGRESS',
        ]);

        $response = $this->actingAs($this->worker1, 'sanctum')
            ->postJson("/api/jobs/{$job->id}/complete");

        $response->assertStatus(200);
        $this->assertEquals('COMPLETED', $job->fresh()->status);
    }

    public function test_10_customer_cancellation(): void
    {
        $job = Job::create([
            'job_number' => 'WLJ-TEST-009',
            'customer_id' => $this->customer->id,
            'worker_id' => $this->worker1->id,
            'category_id' => $this->category->id,
            'title' => 'Customer Cancel Test',
            'description' => 'Cancelling job',
            'status' => 'REQUESTED',
        ]);

        $response = $this->actingAs($this->customer, 'sanctum')
            ->postJson("/api/jobs/{$job->id}/cancel", [
                'reason' => 'No longer needed',
            ]);

        $response->assertStatus(200);
        $this->assertEquals('CANCELLED', $job->fresh()->status);
    }

    public function test_11_worker_cancellation(): void
    {
        $job = Job::create([
            'job_number' => 'WLJ-TEST-010',
            'customer_id' => $this->customer->id,
            'worker_id' => $this->worker1->id,
            'category_id' => $this->category->id,
            'title' => 'Worker Cancel Test',
            'description' => 'Worker cancelling job',
            'status' => 'ACCEPTED',
        ]);

        $response = $this->actingAs($this->worker1, 'sanctum')
            ->postJson("/api/jobs/{$job->id}/cancel", [
                'reason' => 'Emergency issue',
            ]);

        $response->assertStatus(200);
        $this->assertEquals('CANCELLED', $job->fresh()->status);
    }

    public function test_12_completed_job_cannot_be_cancelled(): void
    {
        $job = Job::create([
            'job_number' => 'WLJ-TEST-011',
            'customer_id' => $this->customer->id,
            'worker_id' => $this->worker1->id,
            'category_id' => $this->category->id,
            'title' => 'Completed Cancel Test',
            'description' => 'Already completed',
            'status' => 'COMPLETED',
        ]);

        $response = $this->actingAs($this->customer, 'sanctum')
            ->postJson("/api/jobs/{$job->id}/cancel", [
                'reason' => 'Want refund',
            ]);

        $response->assertStatus(422);
    }

    public function test_13_status_history_creation(): void
    {
        $job = Job::create([
            'job_number' => 'WLJ-TEST-012',
            'customer_id' => $this->customer->id,
            'worker_id' => $this->worker1->id,
            'category_id' => $this->category->id,
            'title' => 'Status History Test',
            'description' => 'History check',
            'status' => 'REQUESTED',
        ]);

        $this->actingAs($this->worker1, 'sanctum')->postJson("/api/jobs/{$job->id}/accept");

        $this->assertDatabaseHas('job_status_history', [
            'job_id' => $job->id,
            'from_status' => 'REQUESTED',
            'to_status' => 'ACCEPTED',
        ]);

        $historyResponse = $this->actingAs($this->customer, 'sanctum')->getJson("/api/jobs/{$job->id}/history");
        $historyResponse->assertStatus(200)->assertJson(['success' => true]);
    }

    public function test_14_notifications_creation(): void
    {
        $job = Job::create([
            'job_number' => 'WLJ-TEST-013',
            'customer_id' => $this->customer->id,
            'worker_id' => $this->worker1->id,
            'category_id' => $this->category->id,
            'title' => 'Notification Test',
            'description' => 'Notification check',
            'status' => 'REQUESTED',
        ]);

        $this->actingAs($this->worker1, 'sanctum')->postJson("/api/jobs/{$job->id}/accept");

        $this->assertDatabaseHas('notifications', [
            'user_id' => $this->customer->id,
            'type' => 'job_status',
            'reference_id' => $job->id,
        ]);
    }

    public function test_15_job_ownership_protection(): void
    {
        $job = Job::create([
            'job_number' => 'WLJ-TEST-014',
            'customer_id' => $this->customer->id,
            'worker_id' => $this->worker1->id,
            'category_id' => $this->category->id,
            'title' => 'Show Protection',
            'description' => 'Show check',
            'status' => 'REQUESTED',
        ]);

        $response = $this->actingAs($this->worker2, 'sanctum')
            ->getJson("/api/jobs/{$job->id}");

        $response->assertStatus(403);
    }

    public function test_16_otp_test_mode_enabled(): void
    {
        Config::set('auth.otp_test_mode', true);
        Config::set('auth.otp_test_code', '123456');

        $response = $this->postJson('/api/auth/verify-otp', [
            'phone' => '+94779998877',
            'otp_code' => '123456',
            'role' => 'customer',
        ]);

        $response->assertStatus(200)
            ->assertJson(['success' => true]);
    }

    public function test_17_otp_test_mode_disabled_in_production(): void
    {
        Config::set('auth.otp_test_mode', false);

        $response = $this->postJson('/api/auth/verify-otp', [
            'phone' => '+94779998877',
            'otp_code' => '123456',
            'role' => 'customer',
        ]);

        $response->assertStatus(422);
    }
}
