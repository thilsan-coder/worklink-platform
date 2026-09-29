<?php

namespace Tests\Feature;

use App\Models\AdminAuditLog;
use App\Models\AdminUser;
use App\Models\Category;
use App\Models\Chat;
use App\Models\Job;
use App\Models\Message;
use App\Models\Notification;
use App\Models\Payment;
use App\Models\Review;
use App\Models\Skill;
use App\Models\Transaction;
use App\Models\User;
use App\Models\WorkerDocument;
use App\Models\WorkerProfile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AdminManagementTest extends TestCase
{
    use RefreshDatabase;

    protected AdminUser $admin;
    protected User $customer;
    protected User $workerUser;
    protected WorkerProfile $workerProfile;

    protected function setUp(): void
    {
        parent::setUp();

        // Create Admin User
        $this->admin = AdminUser::create([
            'name' => 'System Admin',
            'username' => 'admin',
            'email' => 'admin@worklink.com',
            'password' => Hash::make('AdminPass123!'),
            'role' => 'super_admin',
            'is_active' => true,
        ]);

        // Create Regular Customer
        $this->customer = User::create([
            'name' => 'Alice Customer',
            'phone' => '+94771111111',
            'email' => 'alice@customer.com',
            'role' => 'customer',
            'status' => 'active',
        ]);

        // Create Regular Worker
        $this->workerUser = User::create([
            'name' => 'Bob Worker',
            'phone' => '+94772222222',
            'email' => 'bob@worker.com',
            'role' => 'worker',
            'status' => 'active',
        ]);

        $this->workerProfile = WorkerProfile::create([
            'user_id' => $this->workerUser->id,
            'bio' => 'Professional Electrician',
            'experience_years' => 5,
            'hourly_rate' => 1500.00,
            'verification_status' => 'pending',
            'average_rating' => 4.50,
            'total_reviews' => 2,
        ]);
    }

    public function test_admin_can_login_with_valid_credentials(): void
    {
        $response = $this->postJson('/api/admin/login', [
            'username_or_email' => 'admin',
            'password' => 'AdminPass123!',
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Admin authentication successful.',
            ])
            ->assertJsonStructure([
                'data' => ['token', 'token_type', 'admin'],
            ]);
    }

    public function test_admin_login_fails_with_invalid_credentials(): void
    {
        $response = $this->postJson('/api/admin/login', [
            'username_or_email' => 'admin',
            'password' => 'WrongPassword',
        ]);

        $response->assertStatus(401)
            ->assertJson([
                'success' => false,
                'message' => 'Invalid administrative credentials.',
            ]);
    }

    public function test_admin_can_access_dashboard_stats(): void
    {
        $token = $this->admin->createToken('test-token', ['admin'])->plainTextToken;

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson('/api/admin/dashboard');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'total_users',
                    'total_customers',
                    'total_workers',
                    'verified_workers',
                    'pending_verifications',
                    'total_jobs',
                    'total_payments',
                    'total_transaction_amount',
                ],
            ]);
    }

    public function test_customer_cannot_access_admin_endpoints(): void
    {
        $token = $this->customer->createToken('customer-token')->plainTextToken;

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson('/api/admin/dashboard');

        $response->assertStatus(403);
    }

    public function test_worker_cannot_access_admin_endpoints(): void
    {
        $token = $this->workerUser->createToken('worker-token')->plainTextToken;

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson('/api/admin/users');

        $response->assertStatus(403);
    }

    public function test_unauthenticated_request_to_admin_endpoints_is_unauthorized(): void
    {
        $response = $this->getJson('/api/admin/dashboard');

        $response->assertStatus(401);
    }

    public function test_admin_can_list_users_with_search_and_filters(): void
    {
        $token = $this->admin->createToken('test-token', ['admin'])->plainTextToken;

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson('/api/admin/users?role=customer&search=Alice');

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonCount(1, 'data.data');
    }

    public function test_admin_can_view_single_user(): void
    {
        $token = $this->admin->createToken('test-token', ['admin'])->plainTextToken;

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson("/api/admin/users/{$this->customer->id}");

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.id', $this->customer->id);
    }

    public function test_admin_can_suspend_and_activate_user(): void
    {
        $token = $this->admin->createToken('test-token', ['admin'])->plainTextToken;

        // Suspend
        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->patchJson("/api/admin/users/{$this->customer->id}/status", [
                'status' => 'suspended',
                'reason' => 'Violation of terms',
            ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.status', 'suspended');

        $this->assertEquals('suspended', $this->customer->fresh()->status);

        // Check Audit Log
        $this->assertDatabaseHas('admin_audit_logs', [
            'action' => 'USER_SUSPENDED',
            'entity_type' => 'User',
            'entity_id' => $this->customer->id,
        ]);

        // Activate
        $response2 = $this->withHeader('Authorization', "Bearer {$token}")
            ->patchJson("/api/admin/users/{$this->customer->id}/status", [
                'status' => 'active',
            ]);

        $response2->assertStatus(200)
            ->assertJsonPath('data.status', 'active');

        $this->assertEquals('active', $this->customer->fresh()->status);
    }

    public function test_admin_can_list_and_view_workers(): void
    {
        $token = $this->admin->createToken('test-token', ['admin'])->plainTextToken;

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson('/api/admin/workers');

        $response->assertStatus(200)
            ->assertJsonPath('success', true);

        $showResponse = $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson("/api/admin/workers/{$this->workerProfile->id}");

        $showResponse->assertStatus(200)
            ->assertJsonPath('data.id', $this->workerProfile->id);
    }

    public function test_admin_can_approve_worker_verification(): void
    {
        $token = $this->admin->createToken('test-token', ['admin'])->plainTextToken;

        $doc = WorkerDocument::create([
            'worker_profile_id' => $this->workerProfile->id,
            'document_type' => 'id_card',
            'file_path' => 'documents/id.pdf',
            'status' => 'pending',
        ]);

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson("/api/admin/verifications/{$this->workerProfile->id}/approve");

        $response->assertStatus(200)
            ->assertJsonPath('data.verification_status', 'verified');

        $this->assertEquals('verified', $this->workerProfile->fresh()->verification_status);
        $this->assertEquals('approved', $doc->fresh()->status);

        // Verify Notification was dispatched to worker
        $this->assertDatabaseHas('notifications', [
            'user_id' => $this->workerUser->id,
            'type' => 'VERIFICATION_APPROVED',
        ]);

        // Verify Audit Log
        $this->assertDatabaseHas('admin_audit_logs', [
            'action' => 'VERIFICATION_APPROVED',
            'entity_type' => 'WorkerProfile',
            'entity_id' => $this->workerProfile->id,
        ]);
    }

    public function test_admin_can_reject_worker_verification_with_reason(): void
    {
        $token = $this->admin->createToken('test-token', ['admin'])->plainTextToken;

        $doc = WorkerDocument::create([
            'worker_profile_id' => $this->workerProfile->id,
            'document_type' => 'id_card',
            'file_path' => 'documents/id.pdf',
            'status' => 'pending',
        ]);

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson("/api/admin/verifications/{$this->workerProfile->id}/reject", [
                'reason' => 'National ID photo is blurry.',
            ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.verification_status', 'rejected');

        $this->assertEquals('rejected', $this->workerProfile->fresh()->verification_status);
        $this->assertEquals('National ID photo is blurry.', $this->workerProfile->fresh()->verification_rejection_reason);
        $this->assertEquals('rejected', $doc->fresh()->status);

        // Verify Notification
        $this->assertDatabaseHas('notifications', [
            'user_id' => $this->workerUser->id,
            'type' => 'VERIFICATION_REJECTED',
        ]);

        // Verify Audit Log
        $this->assertDatabaseHas('admin_audit_logs', [
            'action' => 'VERIFICATION_REJECTED',
            'entity_type' => 'WorkerProfile',
            'entity_id' => $this->workerProfile->id,
        ]);
    }

    public function test_admin_can_list_and_view_jobs(): void
    {
        $token = $this->admin->createToken('test-token', ['admin'])->plainTextToken;

        $category = Category::create(['name' => 'Plumbing', 'slug' => 'plumbing']);
        $job = Job::create([
            'customer_id' => $this->customer->id,
            'worker_id' => $this->workerUser->id,
            'category_id' => $category->id,
            'job_number' => 'JOB-ADM-' . uniqid(),
            'title' => 'Fix Sink Pipe',
            'description' => 'Leaking pipe',
            'status' => 'COMPLETED',
            'estimated_cost' => 3000.00,
            'final_cost' => 3000.00,
        ]);

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson('/api/admin/jobs?status=COMPLETED');

        $response->assertStatus(200)
            ->assertJsonPath('success', true);

        $showResponse = $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson("/api/admin/jobs/{$job->id}");

        $showResponse->assertStatus(200)
            ->assertJsonPath('data.id', $job->id);
    }

    public function test_admin_can_list_and_view_conversations(): void
    {
        $token = $this->admin->createToken('test-token', ['admin'])->plainTextToken;

        $category = Category::create(['name' => 'Electrical', 'slug' => 'electrical']);
        $job = Job::create([
            'customer_id' => $this->customer->id,
            'worker_id' => $this->workerUser->id,
            'category_id' => $category->id,
            'job_number' => 'JOB-ADM-' . uniqid(),
            'title' => 'Wiring inspection',
            'description' => 'Fix breaker',
            'status' => 'IN_PROGRESS',
        ]);

        $chat = Chat::create([
            'job_id' => $job->id,
            'customer_id' => $this->customer->id,
            'worker_id' => $this->workerUser->id,
            'last_message_at' => now(),
        ]);

        Message::create([
            'chat_id' => $chat->id,
            'sender_id' => $this->customer->id,
            'message' => 'Hello, are you on your way?',
        ]);

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson('/api/admin/conversations');

        $response->assertStatus(200)
            ->assertJsonPath('success', true);

        $showResponse = $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson("/api/admin/conversations/{$chat->id}");

        $showResponse->assertStatus(200)
            ->assertJsonPath('data.conversation.id', $chat->id)
            ->assertJsonCount(1, 'data.messages.data');
    }

    public function test_admin_can_list_and_moderate_reviews(): void
    {
        $token = $this->admin->createToken('test-token', ['admin'])->plainTextToken;

        $category = Category::create(['name' => 'Cleaning', 'slug' => 'cleaning']);
        $job = Job::create([
            'customer_id' => $this->customer->id,
            'worker_id' => $this->workerUser->id,
            'category_id' => $category->id,
            'job_number' => 'JOB-ADM-' . uniqid(),
            'title' => 'Deep Cleaning',
            'description' => 'Clean living room',
            'status' => 'COMPLETED',
        ]);

        $review1 = Review::create([
            'job_id' => $job->id,
            'customer_id' => $this->customer->id,
            'worker_id' => $this->workerUser->id,
            'overall_rating' => 5.0,
            'comment' => 'Great job!',
        ]);

        $job2 = Job::create([
            'customer_id' => $this->customer->id,
            'worker_id' => $this->workerUser->id,
            'category_id' => $category->id,
            'job_number' => 'JOB-ADM-' . uniqid(),
            'title' => 'House Painting',
            'description' => 'Paint wall',
            'status' => 'COMPLETED',
        ]);

        $review2 = Review::create([
            'job_id' => $job2->id,
            'customer_id' => $this->customer->id,
            'worker_id' => $this->workerUser->id,
            'overall_rating' => 1.0,
            'comment' => 'Spam comment with abusive words',
        ]);

        // List
        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson('/api/admin/reviews');

        $response->assertStatus(200)
            ->assertJsonPath('success', true);

        // Delete abusive review
        $deleteResponse = $this->withHeader('Authorization', "Bearer {$token}")
            ->deleteJson("/api/admin/reviews/{$review2->id}");

        $deleteResponse->assertStatus(200);
        $this->assertSoftDeleted('reviews', ['id' => $review2->id]);

        // Worker rating should now be recalculated to 5.00
        $this->assertEquals(5.00, $this->workerProfile->fresh()->average_rating);
        $this->assertEquals(1, $this->workerProfile->fresh()->total_reviews);

        // Verify Audit Log
        $this->assertDatabaseHas('admin_audit_logs', [
            'action' => 'REVIEW_DELETED',
            'entity_type' => 'Review',
            'entity_id' => $review2->id,
        ]);
    }

    public function test_admin_can_access_payments_and_transactions(): void
    {
        $token = $this->admin->createToken('test-token', ['admin'])->plainTextToken;

        $category = Category::create(['name' => 'Carpentry', 'slug' => 'carpentry']);
        $job = Job::create([
            'customer_id' => $this->customer->id,
            'worker_id' => $this->workerUser->id,
            'category_id' => $category->id,
            'job_number' => 'JOB-ADM-' . uniqid(),
            'title' => 'Door repair',
            'description' => 'Fix hinges',
            'status' => 'COMPLETED',
            'final_cost' => 4500.00,
        ]);

        $payment = Payment::create([
            'job_id' => $job->id,
            'customer_id' => $this->customer->id,
            'worker_id' => $this->workerUser->id,
            'amount' => 4500.00,
            'currency' => 'LKR',
            'status' => 'PAID',
            'payment_method' => 'test',
            'gateway' => 'test',
            'gateway_transaction_id' => 'GATEWAY-TEST-001',
            'paid_at' => now(),
        ]);

        $transaction = Transaction::create([
            'payment_id' => $payment->id,
            'job_id' => $job->id,
            'customer_id' => $this->customer->id,
            'worker_id' => $this->workerUser->id,
            'type' => 'PAYMENT',
            'amount' => 4500.00,
            'currency' => 'LKR',
            'status' => 'SUCCESS',
            'reference' => 'TXN-TEST-001',
        ]);

        // Payments list & detail
        $pList = $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson('/api/admin/payments?status=PAID');
        $pList->assertStatus(200)->assertJsonPath('success', true);

        $pShow = $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson("/api/admin/payments/{$payment->id}");
        $pShow->assertStatus(200)->assertJsonPath('data.id', $payment->id);

        // Transactions list & detail
        $tList = $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson('/api/admin/transactions?type=PAYMENT');
        $tList->assertStatus(200)->assertJsonPath('success', true);

        $tShow = $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson("/api/admin/transactions/{$transaction->id}");
        $tShow->assertStatus(200)->assertJsonPath('data.id', $transaction->id);
    }

    public function test_admin_can_view_notifications_and_audit_logs(): void
    {
        $token = $this->admin->createToken('test-token', ['admin'])->plainTextToken;

        Notification::create([
            'user_id' => $this->customer->id,
            'title' => 'Test Notification',
            'body' => 'Your job was accepted',
            'type' => 'JOB_ACCEPTED',
            'is_read' => false,
        ]);

        AdminAuditLog::record(
            adminUserId: $this->admin->id,
            action: 'TEST_ACTION',
            entityType: 'Test',
            entityId: 1,
            description: 'Test description'
        );

        $nList = $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson('/api/admin/notifications');
        $nList->assertStatus(200)->assertJsonPath('success', true);

        $aList = $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson('/api/admin/audit-logs');
        $aList->assertStatus(200)->assertJsonPath('success', true);
    }

    public function test_admin_profile_and_logout(): void
    {
        $token = $this->admin->createToken('test-token', ['admin'])->plainTextToken;

        $profile = $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson('/api/admin/profile');
        $profile->assertStatus(200)
            ->assertJsonPath('data.username', 'admin');

        $logout = $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson('/api/admin/logout');
        $logout->assertStatus(200)
            ->assertJsonPath('success', true);
    }

    public function test_admin_can_access_recent_activity(): void
    {
        $token = $this->admin->createToken('test-token', ['admin'])->plainTextToken;

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson('/api/admin/recent-activity');

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonStructure(['data']);
    }

    public function test_admin_can_filter_workers_by_verification_status_and_rating(): void
    {
        $token = $this->admin->createToken('test-token', ['admin'])->plainTextToken;

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson('/api/admin/workers?verification_status=pending&min_rating=4');

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonCount(1, 'data.data');
    }

    public function test_admin_can_filter_jobs_by_customer_and_status(): void
    {
        $token = $this->admin->createToken('test-token', ['admin'])->plainTextToken;

        $category = Category::create(['name' => 'Gardening', 'slug' => 'gardening']);
        Job::create([
            'customer_id' => $this->customer->id,
            'worker_id' => $this->workerUser->id,
            'category_id' => $category->id,
            'job_number' => 'JOB-ADM-' . uniqid(),
            'title' => 'Lawn Mowing',
            'description' => 'Mow lawn',
            'status' => 'REQUESTED',
        ]);

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson("/api/admin/jobs?customer_id={$this->customer->id}&status=REQUESTED");

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonCount(1, 'data.data');
    }

    public function test_admin_seeder_runs_repeatably_without_duplicate_admin_users(): void
    {
        $this->seed(\Database\Seeders\DatabaseSeeder::class);
        $initialAdminCount = AdminUser::where('username', 'admin')->count();

        $this->seed(\Database\Seeders\DatabaseSeeder::class);
        $finalAdminCount = AdminUser::where('username', 'admin')->count();

        $this->assertEquals(1, $initialAdminCount);
        $this->assertEquals(1, $finalAdminCount);
    }
}

