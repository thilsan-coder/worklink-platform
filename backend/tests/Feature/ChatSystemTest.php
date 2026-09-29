<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Chat;
use App\Models\Job;
use App\Models\Message;
use App\Models\Notification;
use App\Models\User;
use App\Models\WorkerProfile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ChatSystemTest extends TestCase
{
    use RefreshDatabase;

    protected User $customer1;
    protected User $customer2;
    protected User $worker1;
    protected User $worker2;
    protected Category $category;
    protected Job $job1;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();

        $this->customer1 = User::create(['name' => 'Customer Alice', 'phone' => '+94770001001', 'role' => 'customer']);
        $this->customer2 = User::create(['name' => 'Customer Bob', 'phone' => '+94770001002', 'role' => 'customer']);

        $this->worker1 = User::create(['name' => 'Worker Charlie', 'phone' => '+94770001003', 'role' => 'worker']);
        WorkerProfile::create(['user_id' => $this->worker1->id, 'verification_status' => 'verified']);

        $this->worker2 = User::create(['name' => 'Worker David', 'phone' => '+94770001004', 'role' => 'worker']);
        WorkerProfile::create(['user_id' => $this->worker2->id, 'verification_status' => 'verified']);

        $this->category = Category::firstOrCreate(['slug' => 'home-repairs'], ['name' => 'Home Repairs']);

        $this->job1 = Job::create([
            'job_number' => 'WLJ-CHAT-001',
            'customer_id' => $this->customer1->id,
            'worker_id' => $this->worker1->id,
            'category_id' => $this->category->id,
            'title' => 'Plumbing Repair',
            'description' => 'Fix sink pipe',
            'status' => 'ACCEPTED',
        ]);
    }

    public function test_1_customer_can_access_conversation_for_own_job(): void
    {
        $response = $this->actingAs($this->customer1, 'sanctum')
            ->getJson("/api/jobs/{$this->job1->id}/conversation");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'job_id' => $this->job1->id,
                    'other_participant' => [
                        'id' => $this->worker1->id,
                        'name' => 'Worker Charlie',
                    ],
                ],
            ]);
    }

    public function test_2_worker_can_access_assigned_job_conversation(): void
    {
        $response = $this->actingAs($this->worker1, 'sanctum')
            ->getJson("/api/jobs/{$this->job1->id}/conversation");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'job_id' => $this->job1->id,
                    'other_participant' => [
                        'id' => $this->customer1->id,
                        'name' => 'Customer Alice',
                    ],
                ],
            ]);
    }

    public function test_3_unauthorized_customer_cannot_access_another_customers_conversation(): void
    {
        $response = $this->actingAs($this->customer2, 'sanctum')
            ->getJson("/api/jobs/{$this->job1->id}/conversation");

        $response->assertStatus(403);
    }

    public function test_4_unauthorized_worker_cannot_access_another_workers_conversation(): void
    {
        $response = $this->actingAs($this->worker2, 'sanctum')
            ->getJson("/api/jobs/{$this->job1->id}/conversation");

        $response->assertStatus(403);
    }

    public function test_5_user_can_send_message(): void
    {
        $chat = Chat::firstOrCreate([
            'job_id' => $this->job1->id,
            'customer_id' => $this->customer1->id,
            'worker_id' => $this->worker1->id,
        ]);

        $response = $this->actingAs($this->customer1, 'sanctum')
            ->postJson("/api/conversations/{$chat->id}/messages", [
                'message' => 'Hello, can you arrive tomorrow morning at 9am?',
            ]);

        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
                'data' => [
                    'message' => 'Hello, can you arrive tomorrow morning at 9am?',
                    'sender_id' => $this->customer1->id,
                    'is_read' => false,
                ],
            ]);

        $this->assertDatabaseHas('messages', [
            'chat_id' => $chat->id,
            'sender_id' => $this->customer1->id,
            'message_body' => 'Hello, can you arrive tomorrow morning at 9am?',
        ]);
    }

    public function test_6_empty_message_rejected(): void
    {
        $chat = Chat::firstOrCreate([
            'job_id' => $this->job1->id,
            'customer_id' => $this->customer1->id,
            'worker_id' => $this->worker1->id,
        ]);

        $response = $this->actingAs($this->customer1, 'sanctum')
            ->postJson("/api/conversations/{$chat->id}/messages", [
                'message' => '   ',
            ]);

        $response->assertStatus(422);
    }

    public function test_7_message_history_is_paginated(): void
    {
        $chat = Chat::firstOrCreate([
            'job_id' => $this->job1->id,
            'customer_id' => $this->customer1->id,
            'worker_id' => $this->worker1->id,
        ]);

        for ($i = 1; $i <= 5; $i++) {
            Message::create([
                'chat_id' => $chat->id,
                'sender_id' => $this->customer1->id,
                'message_body' => "Message number {$i}",
                'message_type' => 'text',
            ]);
        }

        $response = $this->actingAs($this->customer1, 'sanctum')
            ->getJson("/api/conversations/{$chat->id}/messages?per_page=3");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'data',
                    'meta' => ['current_page', 'per_page', 'total'],
                ],
            ]);
    }

    public function test_8_conversation_list_returns_only_users_conversations(): void
    {
        // Conversation 1 for Customer 1 & Worker 1
        Chat::firstOrCreate([
            'job_id' => $this->job1->id,
            'customer_id' => $this->customer1->id,
            'worker_id' => $this->worker1->id,
        ]);

        // Conversation 2 for Customer 2 & Worker 2
        $job2 = Job::create([
            'job_number' => 'WLJ-CHAT-002',
            'customer_id' => $this->customer2->id,
            'worker_id' => $this->worker2->id,
            'category_id' => $this->category->id,
            'title' => 'Job Two',
            'description' => 'Job Two Desc',
            'status' => 'REQUESTED',
        ]);
        Chat::create([
            'job_id' => $job2->id,
            'customer_id' => $this->customer2->id,
            'worker_id' => $this->worker2->id,
        ]);

        $response = $this->actingAs($this->customer1, 'sanctum')
            ->getJson('/api/conversations');

        $response->assertStatus(200)
            ->assertJsonFragment(['job_id' => $this->job1->id])
            ->assertJsonMissing(['job_id' => $job2->id]);
    }

    public function test_9_latest_message_appears_in_conversation_list(): void
    {
        $chat = Chat::firstOrCreate([
            'job_id' => $this->job1->id,
            'customer_id' => $this->customer1->id,
            'worker_id' => $this->worker1->id,
        ]);

        Message::create([
            'chat_id' => $chat->id,
            'sender_id' => $this->worker1->id,
            'message_body' => 'I will be there in 15 minutes',
            'created_at' => now(),
        ]);

        $response = $this->actingAs($this->customer1, 'sanctum')
            ->getJson('/api/conversations');

        $response->assertStatus(200)
            ->assertJsonFragment(['message' => 'I will be there in 15 minutes']);
    }

    public function test_10_unread_count_works(): void
    {
        $chat = Chat::firstOrCreate([
            'job_id' => $this->job1->id,
            'customer_id' => $this->customer1->id,
            'worker_id' => $this->worker1->id,
        ]);

        // Worker sends 2 messages
        Message::create([
            'chat_id' => $chat->id,
            'sender_id' => $this->worker1->id,
            'message_body' => 'Unread Message 1',
            'is_read' => false,
        ]);
        Message::create([
            'chat_id' => $chat->id,
            'sender_id' => $this->worker1->id,
            'message_body' => 'Unread Message 2',
            'is_read' => false,
        ]);

        $response = $this->actingAs($this->customer1, 'sanctum')
            ->getJson('/api/conversations');

        $response->assertStatus(200)
            ->assertJsonFragment(['unread_count' => 2]);
    }

    public function test_11_message_read_status_works(): void
    {
        $chat = Chat::firstOrCreate([
            'job_id' => $this->job1->id,
            'customer_id' => $this->customer1->id,
            'worker_id' => $this->worker1->id,
        ]);

        $msg = Message::create([
            'chat_id' => $chat->id,
            'sender_id' => $this->worker1->id,
            'message_body' => 'Mark me read',
            'is_read' => false,
        ]);

        // Customer marks single message as read
        $response = $this->actingAs($this->customer1, 'sanctum')
            ->patchJson("/api/messages/{$msg->id}/read");

        $response->assertStatus(200);
        $this->assertTrue($msg->fresh()->is_read);
        $this->assertNotNull($msg->fresh()->read_at);
    }

    public function test_12_new_message_creates_notification(): void
    {
        $chat = Chat::firstOrCreate([
            'job_id' => $this->job1->id,
            'customer_id' => $this->customer1->id,
            'worker_id' => $this->worker1->id,
        ]);

        $this->actingAs($this->customer1, 'sanctum')
            ->postJson("/api/conversations/{$chat->id}/messages", [
                'message' => 'Notification Trigger Test',
            ]);

        $this->assertDatabaseHas('notifications', [
            'user_id' => $this->worker1->id,
            'type' => 'chat_message',
            'reference_id' => $chat->id,
        ]);
    }

    public function test_13_unauthorized_user_cannot_read_another_users_messages(): void
    {
        $chat = Chat::firstOrCreate([
            'job_id' => $this->job1->id,
            'customer_id' => $this->customer1->id,
            'worker_id' => $this->worker1->id,
        ]);

        $response = $this->actingAs($this->customer2, 'sanctum')
            ->getJson("/api/conversations/{$chat->id}/messages");

        $response->assertStatus(403);
    }

    public function test_14_duplicate_conversation_is_prevented(): void
    {
        $res1 = $this->actingAs($this->customer1, 'sanctum')
            ->getJson("/api/jobs/{$this->job1->id}/conversation");

        $res2 = $this->actingAs($this->customer1, 'sanctum')
            ->getJson("/api/jobs/{$this->job1->id}/conversation");

        $this->assertEquals($res1->json('data.id'), $res2->json('data.id'));
        $this->assertEquals(1, Chat::where('job_id', $this->job1->id)->count());
    }

    public function test_15_authentication_is_required(): void
    {
        $response = $this->getJson('/api/conversations');
        $response->assertStatus(401);
    }
}
