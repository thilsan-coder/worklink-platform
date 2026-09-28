<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Skill;
use App\Models\User;
use App\Models\WorkerProfile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WorkerDiscoveryTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_worker_listing_returns_paginated_results(): void
    {
        $response = $this->getJson('/api/workers?per_page=5');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'data',
                    'meta' => [
                        'current_page',
                        'per_page',
                        'total',
                    ],
                ],
            ]);
    }

    public function test_worker_search_by_name_and_skill(): void
    {
        $category = Category::firstOrCreate(['slug' => 'electrical-services'], ['name' => 'Electrical Services']);
        $skill = Skill::firstOrCreate(['slug' => 'solar-panel-wiring'], ['category_id' => $category->id, 'name' => 'Solar Panel Wiring']);

        $user = User::create([
            'name' => 'Anura Kumara Electrician',
            'phone' => '+94771112233',
            'role' => 'worker',
        ]);

        $workerProfile = WorkerProfile::create([
            'user_id' => $user->id,
            'bio' => 'Solar panel installation specialist in Colombo',
            'hourly_rate' => 3500.00,
            'verification_status' => 'verified',
            'address' => 'Colombo 03',
        ]);
        $workerProfile->skills()->attach($skill->id);

        // Search by worker name
        $searchResponse1 = $this->getJson('/api/workers?search=Anura');
        $searchResponse1->assertStatus(200)
            ->assertJsonFragment(['name' => 'Anura Kumara Electrician']);

        // Search by skill name
        $searchResponse2 = $this->getJson('/api/workers?search=Solar Panel');
        $searchResponse2->assertStatus(200)
            ->assertJsonFragment(['name' => 'Anura Kumara Electrician']);
    }

    public function test_worker_filtering_by_category_and_skill(): void
    {
        $cat1 = Category::firstOrCreate(['slug' => 'plumbing-services'], ['name' => 'Plumbing Services']);
        $cat2 = Category::firstOrCreate(['slug' => 'carpentry-services'], ['name' => 'Carpentry Services']);

        $skill1 = Skill::firstOrCreate(['slug' => 'pipe-fitting'], ['category_id' => $cat1->id, 'name' => 'Pipe Fitting']);
        $skill2 = Skill::firstOrCreate(['slug' => 'furniture-design'], ['category_id' => $cat2->id, 'name' => 'Furniture Design']);

        $plumber = User::create(['name' => 'Perera Plumber', 'phone' => '+94772223344', 'role' => 'worker']);
        $pProfile = WorkerProfile::create(['user_id' => $plumber->id, 'verification_status' => 'verified']);
        $pProfile->skills()->attach($skill1->id);

        $carpenter = User::create(['name' => 'Nimal Carpenter', 'phone' => '+94773334455', 'role' => 'worker']);
        $cProfile = WorkerProfile::create(['user_id' => $carpenter->id, 'verification_status' => 'verified']);
        $cProfile->skills()->attach($skill2->id);

        // Filter by Category 1
        $catResponse = $this->getJson('/api/workers?category_id=' . $cat1->id);
        $catResponse->assertStatus(200)
            ->assertJsonFragment(['name' => 'Perera Plumber'])
            ->assertJsonMissing(['name' => 'Nimal Carpenter']);

        // Filter by Skill 2
        $skillResponse = $this->getJson('/api/workers?skill_id=' . $skill2->id);
        $skillResponse->assertStatus(200)
            ->assertJsonFragment(['name' => 'Nimal Carpenter'])
            ->assertJsonMissing(['name' => 'Perera Plumber']);
    }

    public function test_worker_filtering_by_hourly_rate_and_rating(): void
    {
        $user1 = User::create(['name' => 'Cheap Worker', 'phone' => '+94774445566', 'role' => 'worker']);
        WorkerProfile::create([
            'user_id' => $user1->id,
            'hourly_rate' => 1000.00,
            'average_rating' => 3.5,
            'verification_status' => 'verified',
        ]);

        $user2 = User::create(['name' => 'Premium Worker', 'phone' => '+94775556677', 'role' => 'worker']);
        WorkerProfile::create([
            'user_id' => $user2->id,
            'hourly_rate' => 5000.00,
            'average_rating' => 4.9,
            'verification_status' => 'verified',
        ]);

        // Filter rate between 4000 and 6000
        $rateResponse = $this->getJson('/api/workers?min_rate=4000&max_rate=6000');
        $rateResponse->assertStatus(200)
            ->assertJsonFragment(['name' => 'Premium Worker'])
            ->assertJsonMissing(['name' => 'Cheap Worker']);

        // Filter rating >= 4.5
        $ratingResponse = $this->getJson('/api/workers?min_rating=4.5');
        $ratingResponse->assertStatus(200)
            ->assertJsonFragment(['name' => 'Premium Worker'])
            ->assertJsonMissing(['name' => 'Cheap Worker']);
    }

    public function test_worker_filtering_by_verification_status(): void
    {
        $verifiedUser = User::create(['name' => 'Verified Worker', 'phone' => '+94776667788', 'role' => 'worker']);
        WorkerProfile::create(['user_id' => $verifiedUser->id, 'verification_status' => 'verified']);

        $unverifiedUser = User::create(['name' => 'Unverified Worker', 'phone' => '+94777778899', 'role' => 'worker']);
        WorkerProfile::create(['user_id' => $unverifiedUser->id, 'verification_status' => 'pending']);

        $response = $this->getJson('/api/workers?verified=1');
        $response->assertStatus(200)
            ->assertJsonFragment(['name' => 'Verified Worker'])
            ->assertJsonMissing(['name' => 'Unverified Worker']);
    }

    public function test_public_worker_detail_endpoint_returns_sanitized_resource(): void
    {
        $user = User::create(['name' => 'Public Detail Worker', 'phone' => '+94778889900', 'role' => 'worker']);
        $profile = WorkerProfile::create([
            'user_id' => $user->id,
            'bio' => 'Senior Technician',
            'verification_status' => 'verified',
        ]);

        $response = $this->getJson('/api/workers/' . $profile->id);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'id' => $profile->id,
                    'name' => 'Public Detail Worker',
                    'bio' => 'Senior Technician',
                ],
            ])
            ->assertJsonMissing(['password', 'remember_token', 'emergency_contact']);
    }
}
