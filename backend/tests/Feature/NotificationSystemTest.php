<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Chat;
use App\Models\Job;
use App\Models\Notification;
use App\Models\Skill;
use App\Models\User;
use Database\Seeders\DevelopmentNotificationSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NotificationSystemTest extends TestCase
{
    use RefreshDatabase;

    private User $customer;
    private User $worker;
    private User $otherUser;
    private Category $category;
    private Skill $skill;

    protected function setUp(): void
    {
        parent::setUp();

        $this->customer = User::create([
            'phone' => '+94770000001',
            'name' => 'Test Customer',
            'role' => 'customer',
            'is_active' => true,
        ]);

        $this->worker = User::create([
            'phone' => '+94770000002',
            'name' => 'Test Worker',
            'role' => 'worker',
            'is_active' => true,
        ]);

        $this->otherUser = User::create([
            'phone' => '+94770000003',
            'name' => 'Other User',
            'role' => 'customer',
            'is_active' => true,
        ]);

        $this->category = Category::create([
            'name' => 'Electrical',
            'slug' => 'electrical',
            'is_active' => true,
        ]);

        $this->skill = Skill::create([
            'category_id' => $this->category->id,
            'name' => 'Wiring',
            'slug' => 'wiring',
            'is_active' => true,
        ]);
    }

    /** 1. Authenticated user can fetch own notifications */
    public function test_authenticated_user_can_fetch_own_notifications(): void
    {
        Notification::create([
            'user_id' => $this->customer->id,
            'title' => 'Customer Alert',
            'body' => 'Notice for customer',
            'type' => 'JOB_SCHEDULED',
            'is_read' => false,
        ]);

        Notification::create([
            'user_id' => $this->worker->id,
            'title' => 'Worker Alert',
            'body' => 'Notice for worker',
            'type' => 'JOB_REQUESTED',
            'is_read' => false,
        ]);

        $response = $this->actingAs($this->customer)->getJson('/api/notifications');

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.title', 'Customer Alert');
    }

    /** 2. Unauthenticated user cannot fetch notifications */
    public function test_unauthenticated_user_cannot_fetch_notifications(): void
    {
        $response = $this->getJson('/api/notifications');
        $response->assertUnauthorized();
    }

    /** 3. User cannot access another user's notification / data is scoped */
    public function test_user_cannot_access_another_users_notifications(): void
    {
        Notification::create([
            'user_id' => $this->worker->id,
            'title' => 'Worker Notification',
            'body' => 'Secret worker data',
            'type' => 'JOB_REQUESTED',
            'is_read' => false,
        ]);

        $response = $this->actingAs($this->customer)->getJson('/api/notifications');

        $response->assertOk()
            ->assertJsonCount(0, 'data');
    }

    /** 4. Unread count is correct */
    public function test_unread_count_is_correct(): void
    {
        Notification::create([
            'user_id' => $this->customer->id,
            'title' => 'Unread 1',
            'body' => 'Body 1',
            'type' => 'NEW_MESSAGE',
            'is_read' => false,
        ]);

        Notification::create([
            'user_id' => $this->customer->id,
            'title' => 'Unread 2',
            'body' => 'Body 2',
            'type' => 'JOB_ACCEPTED',
            'is_read' => false,
        ]);

        Notification::create([
            'user_id' => $this->customer->id,
            'title' => 'Read Notification',
            'body' => 'Body 3',
            'type' => 'JOB_COMPLETED',
            'is_read' => true,
            'read_at' => now(),
        ]);

        $response = $this->actingAs($this->customer)->getJson('/api/notifications/unread-count');

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('count', 2);
    }

    /** 5. Mark notification as read works */
    public function test_mark_notification_as_read_works(): void
    {
        $notif = Notification::create([
            'user_id' => $this->customer->id,
            'title' => 'To Read',
            'body' => 'Unread message',
            'type' => 'JOB_SCHEDULED',
            'is_read' => false,
        ]);

        $response = $this->actingAs($this->customer)->patchJson("/api/notifications/{$notif->id}/read");

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.is_read', true);

        $this->assertDatabaseHas('notifications', [
            'id' => $notif->id,
            'is_read' => true,
        ]);
    }

    /** 6. User cannot mark another user's notification as read */
    public function test_user_cannot_mark_another_users_notification_as_read(): void
    {
        $notif = Notification::create([
            'user_id' => $this->worker->id,
            'title' => 'Worker Only',
            'body' => 'Content',
            'type' => 'JOB_REQUESTED',
            'is_read' => false,
        ]);

        $response = $this->actingAs($this->customer)->patchJson("/api/notifications/{$notif->id}/read");

        $response->assertForbidden();
    }

    /** 7. Mark-all-read works */
    public function test_mark_all_read_works(): void
    {
        Notification::create([
            'user_id' => $this->customer->id,
            'title' => 'Cust 1',
            'body' => 'B1',
            'type' => 'JOB_ACCEPTED',
            'is_read' => false,
        ]);

        Notification::create([
            'user_id' => $this->customer->id,
            'title' => 'Cust 2',
            'body' => 'B2',
            'type' => 'JOB_STARTED',
            'is_read' => false,
        ]);

        Notification::create([
            'user_id' => $this->worker->id,
            'title' => 'Worker 1',
            'body' => 'W1',
            'type' => 'JOB_REQUESTED',
            'is_read' => false,
        ]);

        $response = $this->actingAs($this->customer)->patchJson('/api/notifications/read-all');

        $response->assertOk()
            ->assertJsonPath('success', true);

        $this->assertEquals(0, Notification::where('user_id', $this->customer->id)->where('is_read', false)->count());
        $this->assertEquals(1, Notification::where('user_id', $this->worker->id)->where('is_read', false)->count());
    }

    /** 8. Delete notification works */
    public function test_delete_notification_works(): void
    {
        $notif = Notification::create([
            'user_id' => $this->customer->id,
            'title' => 'To Delete',
            'body' => 'Will be removed',
            'type' => 'JOB_CANCELLED',
            'is_read' => false,
        ]);

        $response = $this->actingAs($this->customer)->deleteJson("/api/notifications/{$notif->id}");

        $response->assertOk()
            ->assertJsonPath('success', true);

        $this->assertDatabaseMissing('notifications', ['id' => $notif->id]);
    }

    /** 9. User cannot delete another user's notification */
    public function test_user_cannot_delete_another_users_notification(): void
    {
        $notif = Notification::create([
            'user_id' => $this->worker->id,
            'title' => 'Worker Private',
            'body' => 'Do not touch',
            'type' => 'JOB_REQUESTED',
            'is_read' => false,
        ]);

        $response = $this->actingAs($this->customer)->deleteJson("/api/notifications/{$notif->id}");

        $response->assertForbidden();
        $this->assertDatabaseHas('notifications', ['id' => $notif->id]);
    }

    /** 10. Clear all notifications works */
    public function test_clear_all_notifications_works(): void
    {
        Notification::create([
            'user_id' => $this->customer->id,
            'title' => 'Customer Notif',
            'body' => 'Customer body',
            'type' => 'JOB_COMPLETED',
        ]);

        Notification::create([
            'user_id' => $this->worker->id,
            'title' => 'Worker Notif',
            'body' => 'Worker body',
            'type' => 'JOB_REQUESTED',
        ]);

        $response = $this->actingAs($this->customer)->deleteJson('/api/notifications');

        $response->assertOk()
            ->assertJsonPath('success', true);

        $this->assertEquals(0, Notification::where('user_id', $this->customer->id)->count());
        $this->assertEquals(1, Notification::where('user_id', $this->worker->id)->count());
    }

    /** 11. New job request creates notification for worker */
    public function test_new_job_request_creates_notification_for_worker(): void
    {
        $payload = [
            'worker_id' => $this->worker->id,
            'category_id' => $this->category->id,
            'skill_id' => $this->skill->id,
            'title' => 'Fix Living Room Switch',
            'description' => 'Switch is sparking',
            'estimated_cost' => 3500,
            'address_line1' => '123 Galle Road',
            'city' => 'Colombo',
            'latitude' => 6.9271,
            'longitude' => 79.8612,
        ];

        $response = $this->actingAs($this->customer)->postJson('/api/jobs', $payload);
        $response->assertCreated();

        $this->assertDatabaseHas('notifications', [
            'user_id' => $this->worker->id,
            'type' => 'JOB_REQUESTED',
            'title' => 'New Service Request',
        ]);
    }

    /** 12. Job accepted creates notification for customer */
    public function test_job_accepted_creates_notification_for_customer(): void
    {
        $job = Job::create([
            'job_number' => 'WLJ-TEST-001',
            'customer_id' => $this->customer->id,
            'worker_id' => $this->worker->id,
            'category_id' => $this->category->id,
            'title' => 'Fix Switch',
            'description' => 'Details',
            'status' => 'REQUESTED',
        ]);

        $response = $this->actingAs($this->worker)->postJson("/api/jobs/{$job->id}/accept");
        $response->assertOk();

        $this->assertDatabaseHas('notifications', [
            'user_id' => $this->customer->id,
            'type' => 'JOB_ACCEPTED',
            'title' => 'Job Accepted',
        ]);
    }

    /** 13. Job rejected creates notification for customer */
    public function test_job_rejected_creates_notification_for_customer(): void
    {
        $job = Job::create([
            'job_number' => 'WLJ-TEST-002',
            'customer_id' => $this->customer->id,
            'worker_id' => $this->worker->id,
            'category_id' => $this->category->id,
            'title' => 'Install AC',
            'description' => 'Details',
            'status' => 'REQUESTED',
        ]);

        $response = $this->actingAs($this->worker)->postJson("/api/jobs/{$job->id}/reject", [
            'reason' => 'Busy schedule today',
        ]);
        $response->assertOk();

        $this->assertDatabaseHas('notifications', [
            'user_id' => $this->customer->id,
            'type' => 'JOB_REJECTED',
            'title' => 'Job Rejected',
        ]);
    }

    /** 14. Job scheduled creates notification for recipient */
    public function test_job_scheduled_creates_notification(): void
    {
        $job = Job::create([
            'job_number' => 'WLJ-TEST-003',
            'customer_id' => $this->customer->id,
            'worker_id' => $this->worker->id,
            'category_id' => $this->category->id,
            'title' => 'Fan Repair',
            'description' => 'Details',
            'status' => 'ACCEPTED',
        ]);

        $response = $this->actingAs($this->worker)->postJson("/api/jobs/{$job->id}/schedule", [
            'scheduled_at' => now()->addDays(2)->toDateTimeString(),
            'notes' => 'Will come at 10 AM',
        ]);
        $response->assertOk();

        $this->assertDatabaseHas('notifications', [
            'user_id' => $this->customer->id,
            'type' => 'JOB_SCHEDULED',
            'title' => 'Job Scheduled',
        ]);
    }

    /** 15. Job started creates notification for customer */
    public function test_job_started_creates_notification(): void
    {
        $job = Job::create([
            'job_number' => 'WLJ-TEST-004',
            'customer_id' => $this->customer->id,
            'worker_id' => $this->worker->id,
            'category_id' => $this->category->id,
            'title' => 'Socket Replacement',
            'description' => 'Details',
            'status' => 'ACCEPTED',
        ]);

        $response = $this->actingAs($this->worker)->postJson("/api/jobs/{$job->id}/start");
        $response->assertOk();

        $this->assertDatabaseHas('notifications', [
            'user_id' => $this->customer->id,
            'type' => 'JOB_STARTED',
            'title' => 'Job Started',
        ]);
    }

    /** 16. Job completed creates notification */
    public function test_job_completed_creates_notification(): void
    {
        $job = Job::create([
            'job_number' => 'WLJ-TEST-005',
            'customer_id' => $this->customer->id,
            'worker_id' => $this->worker->id,
            'category_id' => $this->category->id,
            'title' => 'Pipe Fixing',
            'description' => 'Details',
            'status' => 'IN_PROGRESS',
        ]);

        $response = $this->actingAs($this->worker)->postJson("/api/jobs/{$job->id}/complete");
        $response->assertOk();

        $this->assertDatabaseHas('notifications', [
            'user_id' => $this->customer->id,
            'type' => 'JOB_COMPLETED',
            'title' => 'Job Completed',
        ]);
    }

    /** 17. Job cancelled creates notification */
    public function test_job_cancelled_creates_notification(): void
    {
        $job = Job::create([
            'job_number' => 'WLJ-TEST-006',
            'customer_id' => $this->customer->id,
            'worker_id' => $this->worker->id,
            'category_id' => $this->category->id,
            'title' => 'Painting',
            'description' => 'Details',
            'status' => 'ACCEPTED',
        ]);

        $response = $this->actingAs($this->customer)->postJson("/api/jobs/{$job->id}/cancel", [
            'reason' => 'Need to travel urgently',
        ]);
        $response->assertOk();

        $this->assertDatabaseHas('notifications', [
            'user_id' => $this->worker->id,
            'type' => 'JOB_CANCELLED',
            'title' => 'Job Cancelled',
        ]);
    }

    /** 18. New chat message creates notification for recipient only */
    public function test_new_chat_message_creates_notification_for_recipient_only(): void
    {
        $job = Job::create([
            'job_number' => 'WLJ-CHAT-001',
            'customer_id' => $this->customer->id,
            'worker_id' => $this->worker->id,
            'category_id' => $this->category->id,
            'title' => 'Chat Job',
            'description' => 'Details',
            'status' => 'ACCEPTED',
        ]);

        $chat = Chat::create([
            'job_id' => $job->id,
            'customer_id' => $this->customer->id,
            'worker_id' => $this->worker->id,
        ]);

        $response = $this->actingAs($this->customer)->postJson("/api/chats/{$chat->id}/messages", [
            'message' => 'Hello, are you available?',
        ]);
        $response->assertCreated();

        // Worker should receive notification
        $this->assertDatabaseHas('notifications', [
            'user_id' => $this->worker->id,
            'type' => 'NEW_MESSAGE',
            'title' => 'New Message',
        ]);

        // Customer (sender) should NOT receive notification
        $this->assertDatabaseMissing('notifications', [
            'user_id' => $this->customer->id,
            'type' => 'NEW_MESSAGE',
        ]);
    }

    /** 19. New review creates notification for worker */
    public function test_new_review_creates_notification_for_worker(): void
    {
        $job = Job::create([
            'job_number' => 'WLJ-TEST-007',
            'customer_id' => $this->customer->id,
            'worker_id' => $this->worker->id,
            'category_id' => $this->category->id,
            'title' => 'Rewiring Job',
            'description' => 'Details',
            'status' => 'COMPLETED',
        ]);

        $response = $this->actingAs($this->customer)->postJson("/api/jobs/{$job->id}/review", [
            'rating' => 5,
            'comment' => 'Great work done!',
        ]);
        $response->assertCreated();

        $this->assertDatabaseHas('notifications', [
            'user_id' => $this->worker->id,
            'type' => 'NEW_REVIEW',
            'title' => 'New Review',
        ]);
    }

    /** 20. Notification pagination works */
    public function test_notification_pagination_works(): void
    {
        for ($i = 1; $i <= 25; $i++) {
            Notification::create([
                'user_id' => $this->customer->id,
                'title' => "Notification #{$i}",
                'body' => "Message body {$i}",
                'type' => 'JOB_SCHEDULED',
                'is_read' => false,
            ]);
        }

        $response = $this->actingAs($this->customer)->getJson('/api/notifications?per_page=10');

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('total', 25)
            ->assertJsonPath('per_page', 10)
            ->assertJsonPath('last_page', 3)
            ->assertJsonCount(10, 'data');
    }

    /** 21. Demo notification seeder runs idempotently */
    public function test_demo_notification_seeder_is_idempotent(): void
    {
        $seeder = new DevelopmentNotificationSeeder();
        $seeder->run();
        $countAfterFirst = Notification::count();

        $seeder->run();
        $countAfterSecond = Notification::count();

        $this->assertGreaterThan(0, $countAfterFirst);
        $this->assertEquals($countAfterFirst, $countAfterSecond);
    }
}
