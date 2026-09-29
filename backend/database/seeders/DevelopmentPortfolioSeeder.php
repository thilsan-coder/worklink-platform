<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\WorkerPortfolio;
use App\Models\WorkerProfile;
use Illuminate\Database\Seeder;

class DevelopmentPortfolioSeeder extends Seeder
{
    public function run(): void
    {
        $portfolioData = [
            'worker1@worklink.test' => [
                ['title' => '3-Phase Industrial Panel Wiring', 'description' => 'Completed full 3-phase commercial distribution panel installation with circuit breakers and surge protector.'],
                ['title' => 'Luxury Villa Concealed Lighting', 'description' => 'Installed modern ambient LED strip lighting and smart switches across 4 bedrooms in Colombo 7.'],
            ],
            'worker2@worklink.test' => [
                ['title' => 'Complete Bathroom Pipeline Installation', 'description' => 'PPR pipeline and drainage setup for 3 modern bathrooms with thermostatic concealed mixer valves.'],
                ['title' => 'Commercial Water Booster Pump Setup', 'description' => 'Installed dual pressure pump station with automatic level sensors for an apartment complex.'],
            ],
            'worker3@worklink.test' => [
                ['title' => 'Custom Teak Kitchen Cabinetry', 'description' => 'Designed and fitted modular pantry cupboards with soft-close Blum hinges and granite countertop integration.'],
                ['title' => 'Solid Mahogany Double Entry Door', 'description' => 'Handcrafted traditional carved entry door with 5-point secure mortise locking system.'],
            ],
            'worker4@worklink.test' => [
                ['title' => 'Inverter AC Multi-Split Installation', 'description' => 'Installed 3 Daikin multi-split inverter air conditioners with vacuum pressure testing.'],
                ['title' => 'Commercial Chiller Overhaul & Gas Refill', 'description' => 'Repaired condenser coils and recharged R410A refrigerant on a rooftop HVAC package unit.'],
            ],
            'worker5@worklink.test' => [
                ['title' => 'Heritage Villa Exterior Weatherproofing', 'description' => 'Applied 3 coats of elastomeric weatherproof barrier and premium exterior emulsion in Galle Fort.'],
                ['title' => 'Living Room Stucco Texture Feature Wall', 'description' => 'Custom Venetian plaster texture finish with gold-flecked architectural accent.'],
            ],
            'worker8@worklink.test' => [
                ['title' => 'E-Commerce Marketplace Platform', 'description' => 'Built high-concurrency multi-vendor shop with Stripe gateway and real-time inventory tracking.'],
                ['title' => 'Logistics Driver Tracking Mobile App', 'description' => 'Cross-platform Flutter application with real-time GPS telemetry and offline-first SQLite sync.'],
            ],
        ];

        foreach ($portfolioData as $workerEmail => $items) {
            $user = User::where('email', $workerEmail)->first();
            if ($user && $user->workerProfile) {
                $profile = $user->workerProfile;
                foreach ($items as $item) {
                    WorkerPortfolio::updateOrCreate(
                        [
                            'worker_profile_id' => $profile->id,
                            'title' => $item['title'],
                        ],
                        [
                            'description' => $item['description'],
                            'image_path' => 'portfolio/demo_work_' . substr(md5($item['title']), 0, 8) . '.jpg',
                        ]
                    );
                }
            }
        }
    }
}
