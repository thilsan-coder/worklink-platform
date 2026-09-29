<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Job;
use App\Models\Notification;
use App\Models\Review;
use App\Models\User;
use App\Models\WorkerProfile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReviewAndDemoDataTest extends TestCase
{
    use RefreshDatabase;

    protected User $customer;
    protected User $otherCustomer;
    protected User $workerUser;
    protected WorkerProfile $workerProfile;
    protected Category $category;
    protected Job $completedJob;
    protected Job $activeJob;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();

        $this->category = Category::firstOrCreate(
            ['slug' => 'test-plumbing'],
            ['name' => 'Test Plumbing', 'icon' => 'plumbing', 'description' => 'Test', 'sort_order' => 1]
        );

        $this->customer = User::create([
            'name' => 'Test Review Customer',
            'email' => 'test_customer_rev@worklink.test',
            'phone' => '+94779999001',
            'role' => 'customer',
            'status' => 'active',
        ]);

        $this->otherCustomer = User::create([
            'name' => 'Other Customer',
            'email' => 'test_other_customer_rev@worklink.test',
            'phone' => '+94779999002',
            'role' => 'customer',
            'status' => 'active',
        ]);

        $this->workerUser = User::create([
            'name' => 'Test Review Worker',
            'email' => 'test_worker_rev@worklink.test',
            'phone' => '+94719999001',
            'role' => 'worker',
            'status' => 'active',
        ]);

        $this->workerProfile = WorkerProfile::updateOrCreate(
            ['user_id' => $this->workerUser->id],
            [
                'bio' => 'Experienced review test worker',
                'hourly_rate' => 2000.00,
                'experience_years' => 5,
                'verification_status' => 'verified',
                'average_rating' => 0.00,
                'total_reviews' => 0,
            ]
        );

        // Completed Job
        $this->completedJob = Job::create([
            'job_number' => 'TEST-REV-CMP-01',
            'customer_id' => $this->customer->id,
            'worker_id' => $this->workerUser->id,
            'category_id' => $this->category->id,
            'title' => 'Test Completed Plumbing Job',
            'description' => 'Fixing pipe leak',
            'status' => 'COMPLETED',
            'final_cost' => 5000.00,
        ]);

        // Active Job (not completed)
        $this->activeJob = Job::create([
            'job_number' => 'TEST-REV-ACT-01',
            'customer_id' => $this->customer->id,
            'worker_id' => $this->workerUser->id,
            'category_id' => $this->category->id,
            'title' => 'Test In-Progress Job',
            'description' => 'Ongoing work',
            'status' => 'IN_PROGRESS',
        ]);
    }

    public function test_1_customer_can_review_completed_own_job(): void
    {
        $response = $this->actingAs($this->customer, 'sanctum')
            ->postJson("/api/jobs/{$this->completedJob->id}/review", [
                'rating' => 5,
                'comment' => 'Outstanding service and very polite worker!',
            ]);

        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
                'data' => [
                    'job_id' => $this->completedJob->id,
                    'customer_id' => $this->customer->id,
                    'worker_id' => $this->workerUser->id,
                    'rating' => 5,
                    'comment' => 'Outstanding service and very polite worker!',
                ],
            ]);

        $this->assertDatabaseHas('reviews', [
            'job_id' => $this->completedJob->id,
            'customer_id' => $this->customer->id,
            'worker_id' => $this->workerUser->id,
            'overall_rating' => 5,
        ]);
    }

    public function test_2_customer_cannot_review_non_completed_job(): void
    {
        $response = $this->actingAs($this->customer, 'sanctum')
            ->postJson("/api/jobs/{$this->activeJob->id}/review", [
                'rating' => 5,
                'comment' => 'Premature review attempt',
            ]);

        $response->assertStatus(422)
            ->assertJson([
                'success' => false,
            ]);
    }

    public function test_3_customer_cannot_review_another_customers_job(): void
    {
        $response = $this->actingAs($this->otherCustomer, 'sanctum')
            ->postJson("/api/jobs/{$this->completedJob->id}/review", [
                'rating' => 4,
                'comment' => 'Unauthorized review',
            ]);

        $response->assertStatus(403);
    }

    public function test_4_worker_cannot_create_review(): void
    {
        $response = $this->actingAs($this->workerUser, 'sanctum')
            ->postJson("/api/jobs/{$this->completedJob->id}/review", [
                'rating' => 5,
                'comment' => 'Worker self-review attempt',
            ]);

        $response->assertStatus(403);
    }

    public function test_5_duplicate_review_is_rejected(): void
    {
        // First review
        $first = $this->actingAs($this->customer, 'sanctum')
            ->postJson("/api/jobs/{$this->completedJob->id}/review", [
                'rating' => 5,
                'comment' => 'Initial review',
            ]);
        $first->assertStatus(201);

        // Second duplicate review
        $second = $this->actingAs($this->customer, 'sanctum')
            ->postJson("/api/jobs/{$this->completedJob->id}/review", [
                'rating' => 4,
                'comment' => 'Duplicate attempt',
            ]);
        $second->assertStatus(409);
    }

    public function test_6_rating_validation_rejects_out_of_bounds_values(): void
    {
        // Rating 0
        $res0 = $this->actingAs($this->customer, 'sanctum')
            ->postJson("/api/jobs/{$this->completedJob->id}/review", [
                'rating' => 0,
                'comment' => 'Zero rating',
            ]);
        $res0->assertStatus(422);

        // Rating 6
        $res6 = $this->actingAs($this->customer, 'sanctum')
            ->postJson("/api/jobs/{$this->completedJob->id}/review", [
                'rating' => 6,
                'comment' => 'Six rating',
            ]);
        $res6->assertStatus(422);
    }

    public function test_7_worker_rating_and_count_are_calculated_correctly(): void
    {
        $this->actingAs($this->customer, 'sanctum')
            ->postJson("/api/jobs/{$this->completedJob->id}/review", [
                'rating' => 4,
                'comment' => 'Good job done',
            ]);

        $this->workerProfile->refresh();
        $this->assertEquals(4.00, (float) $this->workerProfile->average_rating);
        $this->assertEquals(1, $this->workerProfile->total_reviews);

        // Rating summary API endpoint
        $summaryRes = $this->getJson("/api/workers/{$this->workerProfile->id}/rating");
        $summaryRes->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'worker_id' => $this->workerUser->id,
                    'average_rating' => 4.0,
                    'total_reviews' => 1,
                ],
            ]);
    }

    public function test_8_reviews_appear_on_worker_reviews_endpoint(): void
    {
        $this->actingAs($this->customer, 'sanctum')
            ->postJson("/api/jobs/{$this->completedJob->id}/review", [
                'rating' => 5,
                'comment' => 'Highly recommended plumber',
            ]);

        $reviewsRes = $this->getJson("/api/workers/{$this->workerProfile->id}/reviews");
        $reviewsRes->assertStatus(200)
            ->assertJson([
                'success' => true,
            ]);

        $this->assertStringContainsString('Highly recommended plumber', $reviewsRes->getContent());
    }

    public function test_9_notification_is_created_for_worker_on_review(): void
    {
        $this->actingAs($this->customer, 'sanctum')
            ->postJson("/api/jobs/{$this->completedJob->id}/review", [
                'rating' => 5,
                'comment' => 'Great work!',
            ]);

        $this->assertDatabaseHas('notifications', [
            'user_id' => $this->workerUser->id,
            'type' => 'review_received',
        ]);
    }

    public function test_10_review_can_be_retrieved_by_job(): void
    {
        $this->actingAs($this->customer, 'sanctum')
            ->postJson("/api/jobs/{$this->completedJob->id}/review", [
                'rating' => 5,
                'comment' => 'Direct job review query test',
            ]);

        $getRes = $this->actingAs($this->customer, 'sanctum')
            ->getJson("/api/jobs/{$this->completedJob->id}/review");
        $getRes->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'job_id' => $this->completedJob->id,
                    'rating' => 5,
                ],
            ]);
    }

    public function test_11_review_owner_can_update_review_and_rating_recalculates(): void
    {
        $createRes = $this->actingAs($this->customer, 'sanctum')
            ->postJson("/api/jobs/{$this->completedJob->id}/review", [
                'rating' => 3,
                'comment' => 'Average initially',
            ]);
        $reviewId = $createRes->json('data.id');

        $this->workerProfile->refresh();
        $this->assertEquals(3.00, (float) $this->workerProfile->average_rating);

        // Customer updates rating to 5
        $updateRes = $this->actingAs($this->customer, 'sanctum')
            ->putJson("/api/reviews/{$reviewId}", [
                'rating' => 5,
                'comment' => 'Updated: Outstanding after follow-up',
            ]);
        $updateRes->assertStatus(200);

        $this->workerProfile->refresh();
        $this->assertEquals(5.00, (float) $this->workerProfile->average_rating);
    }

    public function test_12_review_owner_can_delete_review_and_rating_recalculates(): void
    {
        $createRes = $this->actingAs($this->customer, 'sanctum')
            ->postJson("/api/jobs/{$this->completedJob->id}/review", [
                'rating' => 4,
                'comment' => 'To be deleted',
            ]);
        $reviewId = $createRes->json('data.id');

        $this->workerProfile->refresh();
        $this->assertEquals(4.00, (float) $this->workerProfile->average_rating);

        // Customer deletes review
        $deleteRes = $this->actingAs($this->customer, 'sanctum')
            ->deleteJson("/api/reviews/{$reviewId}");
        $deleteRes->assertStatus(200);

        $this->workerProfile->refresh();
        $this->assertEquals(0.00, (float) $this->workerProfile->average_rating);
        $this->assertEquals(0, $this->workerProfile->total_reviews);
    }
}
