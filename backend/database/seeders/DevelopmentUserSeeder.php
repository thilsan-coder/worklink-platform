<?php

namespace Database\Seeders;

use App\Models\CustomerProfile;
use App\Models\Skill;
use App\Models\User;
use App\Models\WorkerProfile;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DevelopmentUserSeeder extends Seeder
{
    public function run(): void
    {
        // ----------------------------------------------------
        // 1. Create 10 Development Customers
        // ----------------------------------------------------
        $customersData = [
            ['name' => 'Kasun Perera', 'email' => 'customer1@worklink.test', 'phone' => '+94771110001', 'address' => '45 Galle Road, Bambalapitiya', 'city' => 'Colombo'],
            ['name' => 'Dilani Fernando', 'email' => 'customer2@worklink.test', 'phone' => '+94771110002', 'address' => '12 Kandy Road, Kiribathgoda', 'city' => 'Gampaha'],
            ['name' => 'Nimal Jayasinghe', 'email' => 'customer3@worklink.test', 'phone' => '+94771110003', 'address' => '78 Peradeniya Road', 'city' => 'Kandy'],
            ['name' => 'Anusha Silva', 'email' => 'customer4@worklink.test', 'phone' => '+94771110004', 'address' => '23 Main Street, Fort', 'city' => 'Galle'],
            ['name' => 'Thilina Bandara', 'email' => 'customer5@worklink.test', 'phone' => '+94771110005', 'address' => '190 Negombo Road, Wattala', 'city' => 'Gampaha'],
            ['name' => 'Saman Kumara', 'email' => 'customer6@worklink.test', 'phone' => '+94771110006', 'address' => '54 Kurunegala Road', 'city' => 'Kurunegala'],
            ['name' => 'Priyanka Wickramasinghe', 'email' => 'customer7@worklink.test', 'phone' => '+94771110007', 'address' => '88 High Level Road, Nugegoda', 'city' => 'Colombo'],
            ['name' => 'Rohan De Silva', 'email' => 'customer8@worklink.test', 'phone' => '+94771110008', 'address' => '15 Beach Road, Mount Lavinia', 'city' => 'Colombo'],
            ['name' => 'Chamari Athapaththu', 'email' => 'customer9@worklink.test', 'phone' => '+94771110009', 'address' => '62 Matara Road', 'city' => 'Matara'],
            ['name' => 'Dinesh Chandimal', 'email' => 'customer10@worklink.test', 'phone' => '+94771110010', 'address' => '31 Station Road', 'city' => 'Kalutara'],
        ];

        foreach ($customersData as $c) {
            $user = User::updateOrCreate(
                ['email' => $c['email']],
                [
                    'name' => $c['name'],
                    'phone' => $c['phone'],
                    'phone_verified_at' => now(),
                    'email_verified_at' => now(),
                    'password' => Hash::make('Password123!'),
                    'role' => 'customer',
                    'status' => 'active',
                ]
            );

            CustomerProfile::updateOrCreate(
                ['user_id' => $user->id],
                [
                    'default_address' => $c['address'] . ', ' . $c['city'],
                    'default_latitude' => 6.9271,
                    'default_longitude' => 79.8612,
                    'preferred_payment_method' => 'cash',
                ]
            );
        }

        // ----------------------------------------------------
        // 2. Create 15 Development Workers with Diverse Trades
        // ----------------------------------------------------
        $workersData = [
            [
                'name' => 'Ruwan Silva',
                'email' => 'worker1@worklink.test',
                'phone' => '+94711110001',
                'bio' => 'Certified Master Electrician with 12+ years of residential and commercial wiring experience in Colombo and suburbs.',
                'experience_years' => 12,
                'hourly_rate' => 2000.00,
                'address' => 'Colombo 03',
                'verification_status' => 'verified',
                'skill_slugs' => ['wiring-full-circuit-rewiring', 'switchboard-socket-installation', 'short-circuit-diagnosis-fuse-repair'],
            ],
            [
                'name' => 'Kamal Perera',
                'email' => 'worker2@worklink.test',
                'phone' => '+94711110002',
                'bio' => 'Experienced Plumbing Specialist. Expert in rapid leak detection, water pump installations, and unclogging sewer systems.',
                'experience_years' => 8,
                'hourly_rate' => 1800.00,
                'address' => 'Kandy City Center',
                'verification_status' => 'verified',
                'skill_slugs' => ['leak-detection-pipe-repair', 'faucet-tap-fitting', 'water-heater-repair-installation'],
            ],
            [
                'name' => 'Sunil Fernando',
                'email' => 'worker3@worklink.test',
                'phone' => '+94711110003',
                'bio' => 'Custom Wood Craftsman & Carpenter. Building bespoke wooden cabinets, pantry cupboards, and restored teak furniture.',
                'experience_years' => 15,
                'hourly_rate' => 2200.00,
                'address' => 'Negombo',
                'verification_status' => 'verified',
                'skill_slugs' => ['custom-woodworking-shelving', 'modular-cabinet-assembly', 'door-lock-hinge-fitting'],
            ],
            [
                'name' => 'Janaka Wickrama',
                'email' => 'worker4@worklink.test',
                'phone' => '+94711110004',
                'bio' => 'HVAC & Air Conditioner technician. Fast split unit maintenance, gas recharging, and inverter diagnostics.',
                'experience_years' => 7,
                'hourly_rate' => 1900.00,
                'address' => 'Nugegoda, Colombo',
                'verification_status' => 'verified',
                'skill_slugs' => ['split-window-ac-servicing', 'ac-gas-refill-leak-testing', 'refrigerator-freezer-repair'],
            ],
            [
                'name' => 'Pradeep Kumara',
                'email' => 'worker5@worklink.test',
                'phone' => '+94711110005',
                'bio' => 'Professional interior and exterior painter with waterproofing mastery. Weather-resistant coatings and texture finishes.',
                'experience_years' => 10,
                'hourly_rate' => 1600.00,
                'address' => 'Galle Fort Area',
                'verification_status' => 'verified',
                'skill_slugs' => ['interior-wall-painting', 'exterior-wall-weatherproofing', 'roof-wall-damp-proofing'],
            ],
            [
                'name' => 'Nuwan Bandara',
                'email' => 'worker6@worklink.test',
                'phone' => '+94711110006',
                'bio' => 'Deep cleaning, sofa shampooing, water tank purification, and residential pest fumigation specialist.',
                'experience_years' => 5,
                'hourly_rate' => 1400.00,
                'address' => 'Kiribathgoda, Gampaha',
                'verification_status' => 'verified',
                'skill_slugs' => ['full-home-deep-cleaning', 'sofa-carpet-shampooing', 'water-tank-cleaning'],
            ],
            [
                'name' => 'Mahesh Jayawardena',
                'email' => 'worker7@worklink.test',
                'phone' => '+94711110007',
                'bio' => 'Computer and laptop hardware expert. Motherboard repair, SSD upgrades, and liquid spill rehabilitation.',
                'experience_years' => 9,
                'hourly_rate' => 2500.00,
                'address' => 'Bambalapitiya, Colombo',
                'verification_status' => 'verified',
                'skill_slugs' => ['hardware-troubleshooting-repair', 'laptop-screen-battery-replacement', 'data-recovery-virus-cleaning'],
            ],
            [
                'name' => 'Lasantha Rodrigo',
                'email' => 'worker8@worklink.test',
                'phone' => '+94711110008',
                'bio' => 'Full-stack software developer. Building responsive business portals, Laravel backends, and Flutter mobile apps.',
                'experience_years' => 6,
                'hourly_rate' => 3500.00,
                'address' => 'Rajagiriya, Colombo',
                'verification_status' => 'verified',
                'skill_slugs' => ['custom-website-development', 'mobile-application-development', 'wordpress-e-commerce-stores'],
            ],
            [
                'name' => 'Asanka Gurusinha',
                'email' => 'worker9@worklink.test',
                'phone' => '+94711110009',
                'bio' => 'Creative graphic designer and UI specialist. Branding, vector logos, 3D mockups, and mobile application UI kits.',
                'experience_years' => 5,
                'hourly_rate' => 2200.00,
                'address' => 'Kaduwela',
                'verification_status' => 'verified',
                'skill_slugs' => ['logo-brand-identity', 'brochures-flyers-banners', 'uiux-mobile-web-wireframing'],
            ],
            [
                'name' => 'Sanath Jayasuriya',
                'email' => 'worker10@worklink.test',
                'phone' => '+94711110010',
                'bio' => 'Precision smartphone technician. Fast screen replacement, battery renewal, and micro-soldering for Apple and Android devices.',
                'experience_years' => 8,
                'hourly_rate' => 1900.00,
                'address' => 'Matara City',
                'verification_status' => 'verified',
                'skill_slugs' => ['screen-oled-display-replacement', 'battery-swap-charging-port-fix', 'water-damage-diagnostic-board-repair'],
            ],
            [
                'name' => 'Chaminda Vaas',
                'email' => 'worker11@worklink.test',
                'phone' => '+94711110011',
                'bio' => 'Licensed industrial and residential electrical technician. Solar inverter wiring and smart home integration.',
                'experience_years' => 14,
                'hourly_rate' => 2400.00,
                'address' => 'Wattala',
                'verification_status' => 'verified',
                'skill_slugs' => ['wiring-full-circuit-rewiring', 'inverter-backup-battery-setup', 'ceiling-fan-light-fixture-fitting'],
            ],
            [
                'name' => 'Muttiah Murali',
                'email' => 'worker12@worklink.test',
                'phone' => '+94711110012',
                'bio' => 'Plumbing engineer with extensive knowledge in commercial pipelines, high-pressure booster systems, and drainage networks.',
                'experience_years' => 16,
                'hourly_rate' => 2300.00,
                'address' => 'Kandy',
                'verification_status' => 'verified',
                'skill_slugs' => ['drainage-sewer-unclogging', 'leak-detection-pipe-repair', 'bathroom-fixture-installation'],
            ],
            [
                'name' => 'Kumar Sangakkara',
                'email' => 'worker13@worklink.test',
                'phone' => '+94711110013',
                'bio' => 'Master furniture designer and timber restorer. Polishing, teak dining sets, and antique wooden restoration.',
                'experience_years' => 18,
                'hourly_rate' => 3000.00,
                'address' => 'Kurunegala',
                'verification_status' => 'verified',
                'skill_slugs' => ['furniture-restoration-polish', 'custom-woodworking-shelving'],
            ],
            [
                'name' => 'Tillakaratne Dilshan',
                'email' => 'worker14@worklink.test',
                'phone' => '+94711110014',
                'bio' => 'Appliance repair technician for major brands (LG, Samsung, Singer, Abans). Washing machines and refrigerators.',
                'experience_years' => 6,
                'hourly_rate' => 1750.00,
                'address' => 'Kalutara South',
                'verification_status' => 'pending',
                'skill_slugs' => ['washing-machine-repair', 'refrigerator-freezer-repair', 'microwave-oven-repair'],
            ],
            [
                'name' => 'Lasith Malinga',
                'email' => 'worker15@worklink.test',
                'phone' => '+94711110015',
                'bio' => 'Rapid home cleaning & pest control emergency responder. Termite treatment and post-construction sanitization.',
                'experience_years' => 4,
                'hourly_rate' => 1500.00,
                'address' => 'Galle Road, Rathmalana',
                'verification_status' => 'verified',
                'skill_slugs' => ['full-home-deep-cleaning', 'pest-control-fumigation'],
            ],
        ];

        foreach ($workersData as $w) {
            $user = User::updateOrCreate(
                ['email' => $w['email']],
                [
                    'name' => $w['name'],
                    'phone' => $w['phone'],
                    'phone_verified_at' => now(),
                    'email_verified_at' => now(),
                    'password' => Hash::make('Password123!'),
                    'role' => 'worker',
                    'status' => 'active',
                ]
            );

            $profile = WorkerProfile::updateOrCreate(
                ['user_id' => $user->id],
                [
                    'bio' => $w['bio'],
                    'experience_years' => $w['experience_years'],
                    'hourly_rate' => $w['hourly_rate'],
                    'service_area_radius_km' => 30,
                    'address' => $w['address'],
                    'availability_status' => 'available',
                    'verification_status' => $w['verification_status'],
                    'verified_at' => $w['verification_status'] === 'verified' ? now()->subMonths(3) : null,
                    'current_latitude' => 6.9271,
                    'current_longitude' => 79.8612,
                ]
            );

            // Attach skills
            $skillIds = Skill::whereIn('slug', $w['skill_slugs'])->pluck('id')->toArray();
            if (!empty($skillIds)) {
                $profile->skills()->sync($skillIds);
            }
        }
    }
}
