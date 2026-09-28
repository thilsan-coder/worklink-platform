<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\CustomerProfile;
use App\Models\Skill;
use App\Models\User;
use App\Models\WorkerPortfolio;
use App\Models\WorkerProfile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ProfileSystemTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_customer_profile_view_and_update(): void
    {
        $customer = User::create([
            'name' => 'Jane Customer',
            'phone' => '+15550001',
            'email' => 'jane@example.com',
            'role' => 'customer',
        ]);
        CustomerProfile::create(['user_id' => $customer->id]);

        $token = $customer->createToken('test-token')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/customer/profile');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => ['user', 'profile', 'completion_percentage'],
            ]);

        $updateResponse = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->putJson('/api/customer/profile', [
                'default_address' => '456 Galle Road, Colombo 03',
                'emergency_contact' => '+94770001122',
                'preferred_payment_method' => 'card',
            ]);

        $updateResponse->assertStatus(200)
            ->assertJson(['success' => true]);

        $this->assertDatabaseHas('customer_profiles', [
            'user_id' => $customer->id,
            'default_address' => '456 Galle Road, Colombo 03',
        ]);
    }

    public function test_worker_profile_update_and_skills_persistence(): void
    {
        $worker = User::create([
            'name' => 'Bob Electrician',
            'phone' => '+15550002',
            'role' => 'worker',
        ]);
        $workerProfile = WorkerProfile::create(['user_id' => $worker->id]);

        $skills = Skill::limit(2)->get();
        $skillIds = $skills->pluck('id')->toArray();

        $token = $worker->createToken('test-token')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->putJson('/api/worker/profile', [
                'bio' => 'Certified Master Electrician with 8 years experience.',
                'experience_years' => 8,
                'hourly_rate' => 2500.00,
                'service_area_radius_km' => 30,
                'address' => 'Kandy Road, Kiribathgoda',
                'skill_ids' => $skillIds,
            ]);

        $response->assertStatus(200)
            ->assertJson(['success' => true]);

        $this->assertDatabaseHas('worker_profiles', [
            'id' => $workerProfile->id,
            'experience_years' => 8,
        ]);

        $this->assertCount(2, $workerProfile->fresh()->skills);
    }

    public function test_worker_portfolio_crud_and_ownership(): void
    {
        Storage::fake('public');

        $worker1 = User::create(['name' => 'Worker 1', 'phone' => '+15550003', 'role' => 'worker']);
        $p1 = WorkerProfile::create(['user_id' => $worker1->id]);
        $token1 = $worker1->createToken('token1')->plainTextToken;

        $worker2 = User::create(['name' => 'Worker 2', 'phone' => '+15550004', 'role' => 'worker']);
        $p2 = WorkerProfile::create(['user_id' => $worker2->id]);
        $token2 = $worker2->createToken('token2')->plainTextToken;

        // 1. Worker 1 Creates Portfolio Item
        $file = UploadedFile::fake()->image('work1.jpg');
        $createResponse = $this->actingAs($worker1, 'sanctum')
            ->postJson('/api/worker/portfolio', [
                'title' => 'House Wiring Project',
                'description' => 'Full 3-story house electrical setup.',
                'image' => $file,
            ]);

        $createResponse->assertStatus(201);
        $portfolioId = $createResponse->json('data.id');

        // 2. Worker 2 Tries to Delete Worker 1's Portfolio Item (Forbidden / Unauthorized)
        $unauthorizedResponse = $this->actingAs($worker2, 'sanctum')
            ->deleteJson('/api/worker/portfolio/' . $portfolioId);

        $unauthorizedResponse->assertStatus(403);

        // 3. Worker 1 Deletes Own Portfolio Item
        $deleteResponse = $this->actingAs($worker1, 'sanctum')
            ->deleteJson('/api/worker/portfolio/' . $portfolioId);

        $deleteResponse->assertStatus(200);
        $this->assertDatabaseMissing('worker_portfolio', ['id' => $portfolioId]);
    }

    public function test_profile_photo_upload(): void
    {
        Storage::fake('public');

        $user = User::create(['name' => 'User Photo', 'phone' => '+15550005', 'role' => 'customer']);
        $token = $user->createToken('token')->plainTextToken;

        $file = UploadedFile::fake()->image('avatar.jpg');

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/profile/photo', [
                'photo' => $file,
            ]);

        $response->assertStatus(200)
            ->assertJson(['success' => true]);

        $this->assertNotNull($user->fresh()->avatar);
    }

    public function test_public_worker_profile_does_not_expose_private_data(): void
    {
        $worker = User::create(['name' => 'Public Worker', 'phone' => '+15550006', 'role' => 'worker']);
        $profile = WorkerProfile::create([
            'user_id' => $worker->id,
            'bio' => 'Plumbing specialist',
            'verification_status' => 'verified',
        ]);

        $response = $this->getJson('/api/workers/' . $profile->id);

        $response->assertStatus(200)
            ->assertJsonMissing(['emergency_contact', 'password', 'remember_token']);
    }
}
