<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Job;
use App\Models\JobLocation;
use App\Models\JobStatusHistory;
use App\Models\Skill;
use App\Models\User;
use Illuminate\Database\Seeder;

class DevelopmentJobSeeder extends Seeder
{
    public function run(): void
    {
        $customers = User::where('role', 'customer')->get();
        $workers = User::where('role', 'worker')->get();

        if ($customers->isEmpty() || $workers->isEmpty()) {
            return;
        }

        $c1 = $customers->get(0);
        $c2 = $customers->get(1);
        $c3 = $customers->get(2);
        $c4 = $customers->get(3);
        $c5 = $customers->get(4);
        $c6 = $customers->get(5);

        $w1 = $workers->get(0); // Ruwan Silva (Electrician)
        $w2 = $workers->get(1); // Kamal Perera (Plumber)
        $w3 = $workers->get(2); // Sunil Fernando (Carpenter)
        $w4 = $workers->get(3); // Janaka Wickrama (AC)
        $w5 = $workers->get(4); // Pradeep Kumara (Painter)
        $w6 = $workers->get(5); // Nuwan Bandara (Cleaning)
        $w7 = $workers->get(6); // Mahesh (Computer)
        $w8 = $workers->get(7); // Lasantha (Developer)

        $catElectrical = Category::where('slug', 'electrical-services')->first();
        $catPlumbing = Category::where('slug', 'plumbing-services')->first();
        $catCarpentry = Category::where('slug', 'carpentry-furniture')->first();
        $catAC = Category::where('slug', 'ac-appliance-repair')->first();
        $catPainting = Category::where('slug', 'painting-waterproofing')->first();
        $catCleaning = Category::where('slug', 'cleaning-sanitization')->first();

        $jobsDefinition = [
            // 1. REQUESTED Jobs
            [
                'job_number' => 'JOB-REQ-001',
                'customer_id' => $c1->id,
                'worker_id' => $w1->id,
                'category_id' => $catElectrical?->id,
                'title' => 'Main Distribution Board Trip Repair',
                'description' => 'Main RCD circuit breaker trips repeatedly when switching on water heater. Need diagnostic.',
                'status' => 'REQUESTED',
                'address' => '45 Galle Road',
                'city' => 'Colombo',
                'history' => [
                    ['status' => 'REQUESTED', 'note' => 'Job request submitted by customer.', 'minutes_ago' => 120],
                ],
            ],
            [
                'job_number' => 'JOB-REQ-002',
                'customer_id' => $c2->id,
                'worker_id' => $w2->id,
                'category_id' => $catPlumbing?->id,
                'title' => 'Under-Sink Pipe Leak Fixing',
                'description' => 'PVC drainage pipe under the kitchen sink is leaking heavily into the cabinet.',
                'status' => 'REQUESTED',
                'address' => '12 Kandy Road',
                'city' => 'Gampaha',
                'history' => [
                    ['status' => 'REQUESTED', 'note' => 'Job request submitted by customer.', 'minutes_ago' => 60],
                ],
            ],

            // 2. ACCEPTED Jobs
            [
                'job_number' => 'JOB-ACC-001',
                'customer_id' => $c3->id,
                'worker_id' => $w3->id,
                'category_id' => $catCarpentry?->id,
                'title' => 'Wooden Door Lock Replacement',
                'description' => 'Front entrance cylinder mortise lock has jammed and needs to be replaced with new hardware.',
                'status' => 'ACCEPTED',
                'address' => '78 Peradeniya Road',
                'city' => 'Kandy',
                'history' => [
                    ['status' => 'REQUESTED', 'note' => 'Customer requested job', 'minutes_ago' => 300],
                    ['status' => 'ACCEPTED', 'note' => 'Worker accepted job request', 'minutes_ago' => 240],
                ],
            ],

            // 3. SCHEDULED Jobs
            [
                'job_number' => 'JOB-SCH-001',
                'customer_id' => $c4->id,
                'worker_id' => $w4->id,
                'category_id' => $catAC?->id,
                'title' => 'Master Bedroom Inverter AC Servicing',
                'description' => '18,000 BTU LG inverter air conditioner is not cooling properly and needs filter wash and gas check.',
                'status' => 'SCHEDULED',
                'scheduled_at' => now()->addDays(1)->setHour(10)->setMinute(0),
                'address' => '23 Main Street',
                'city' => 'Galle',
                'history' => [
                    ['status' => 'REQUESTED', 'note' => 'Customer requested service', 'minutes_ago' => 600],
                    ['status' => 'ACCEPTED', 'note' => 'Worker accepted request', 'minutes_ago' => 500],
                    ['status' => 'SCHEDULED', 'note' => 'Appointment scheduled for tomorrow at 10:00 AM', 'minutes_ago' => 450],
                ],
            ],

            // 4. IN_PROGRESS Jobs
            [
                'job_number' => 'JOB-PRG-001',
                'customer_id' => $c5->id,
                'worker_id' => $w5->id,
                'category_id' => $catPainting?->id,
                'title' => 'Exterior Balcony Waterproofing & Paint',
                'description' => 'Applying primer and two coats of weather shield acrylic on the second-floor balcony.',
                'status' => 'IN_PROGRESS',
                'started_at' => now()->subHours(2),
                'address' => '190 Negombo Road',
                'city' => 'Wattala',
                'history' => [
                    ['status' => 'REQUESTED', 'note' => 'Job requested', 'minutes_ago' => 1200],
                    ['status' => 'ACCEPTED', 'note' => 'Job accepted', 'minutes_ago' => 1100],
                    ['status' => 'SCHEDULED', 'note' => 'Scheduled for today', 'minutes_ago' => 1000],
                    ['status' => 'IN_PROGRESS', 'note' => 'Worker on-site and commenced painting work', 'minutes_ago' => 120],
                ],
            ],

            // 5. COMPLETED Jobs (Ready for reviews)
            [
                'job_number' => 'JOB-CMP-001',
                'customer_id' => $c1->id,
                'worker_id' => $w1->id,
                'category_id' => $catElectrical?->id,
                'title' => 'Full Kitchen Appliance Rewiring',
                'description' => 'Installed dedicated 16A sockets for oven, microwave, and dishwasher with separate RCCB protection.',
                'status' => 'COMPLETED',
                'scheduled_at' => now()->subDays(5),
                'started_at' => now()->subDays(5)->addHours(1),
                'completed_at' => now()->subDays(5)->addHours(4),
                'final_cost' => 6500.00,
                'address' => '45 Galle Road',
                'city' => 'Colombo',
                'history' => [
                    ['status' => 'REQUESTED', 'note' => 'Job requested', 'minutes_ago' => 7500],
                    ['status' => 'ACCEPTED', 'note' => 'Worker accepted', 'minutes_ago' => 7400],
                    ['status' => 'SCHEDULED', 'note' => 'Scheduled appointment', 'minutes_ago' => 7300],
                    ['status' => 'IN_PROGRESS', 'note' => 'Work started', 'minutes_ago' => 7200],
                    ['status' => 'COMPLETED', 'note' => 'Wiring completed and verified with multimeter', 'minutes_ago' => 7000],
                ],
            ],
            [
                'job_number' => 'JOB-CMP-002',
                'customer_id' => $c2->id,
                'worker_id' => $w2->id,
                'category_id' => $catPlumbing?->id,
                'title' => 'Overhead Water Tank Float Valve Fix',
                'description' => 'Replaced faulty brass float valve to stop 1000L rooftop tank overflow.',
                'status' => 'COMPLETED',
                'scheduled_at' => now()->subDays(4),
                'started_at' => now()->subDays(4)->addHours(1),
                'completed_at' => now()->subDays(4)->addHours(3),
                'final_cost' => 3800.00,
                'address' => '12 Kandy Road',
                'city' => 'Gampaha',
                'history' => [
                    ['status' => 'REQUESTED', 'note' => 'Job requested', 'minutes_ago' => 6000],
                    ['status' => 'ACCEPTED', 'note' => 'Worker accepted', 'minutes_ago' => 5900],
                    ['status' => 'SCHEDULED', 'note' => 'Scheduled appointment', 'minutes_ago' => 5800],
                    ['status' => 'IN_PROGRESS', 'note' => 'Work started', 'minutes_ago' => 5750],
                    ['status' => 'COMPLETED', 'note' => 'New float switch installed and leak-free', 'minutes_ago' => 5600],
                ],
            ],
            [
                'job_number' => 'JOB-CMP-003',
                'customer_id' => $c3->id,
                'worker_id' => $w3->id,
                'category_id' => $catCarpentry?->id,
                'title' => 'Custom Study Bookshelf Assembly',
                'description' => 'Built 6-tier mahogany wall-mounted bookshelf with reinforced support brackets.',
                'status' => 'COMPLETED',
                'scheduled_at' => now()->subDays(3),
                'started_at' => now()->subDays(3)->addHours(1),
                'completed_at' => now()->subDays(3)->addHours(6),
                'final_cost' => 12500.00,
                'address' => '78 Peradeniya Road',
                'city' => 'Kandy',
                'history' => [
                    ['status' => 'REQUESTED', 'note' => 'Job requested', 'minutes_ago' => 4500],
                    ['status' => 'ACCEPTED', 'note' => 'Worker accepted', 'minutes_ago' => 4400],
                    ['status' => 'SCHEDULED', 'note' => 'Scheduled appointment', 'minutes_ago' => 4300],
                    ['status' => 'IN_PROGRESS', 'note' => 'Work started', 'minutes_ago' => 4200],
                    ['status' => 'COMPLETED', 'note' => 'Bookshelf securely anchored and polished', 'minutes_ago' => 3900],
                ],
            ],
            [
                'job_number' => 'JOB-CMP-004',
                'customer_id' => $c4->id,
                'worker_id' => $w4->id,
                'category_id' => $catAC?->id,
                'title' => 'Living Room Panasonic AC Gas Recharging',
                'description' => 'Tested flare joints for leaks and refilled 800g R32 eco-friendly refrigerant.',
                'status' => 'COMPLETED',
                'scheduled_at' => now()->subDays(2),
                'started_at' => now()->subDays(2)->addHours(1),
                'completed_at' => now()->subDays(2)->addHours(3),
                'final_cost' => 8500.00,
                'address' => '23 Main Street',
                'city' => 'Galle',
                'history' => [
                    ['status' => 'REQUESTED', 'note' => 'Job requested', 'minutes_ago' => 3000],
                    ['status' => 'ACCEPTED', 'note' => 'Worker accepted', 'minutes_ago' => 2900],
                    ['status' => 'SCHEDULED', 'note' => 'Scheduled appointment', 'minutes_ago' => 2800],
                    ['status' => 'IN_PROGRESS', 'note' => 'Work started', 'minutes_ago' => 2700],
                    ['status' => 'COMPLETED', 'note' => 'Cooling down to 18°C verified', 'minutes_ago' => 2500],
                ],
            ],
            [
                'job_number' => 'JOB-CMP-005',
                'customer_id' => $c5->id,
                'worker_id' => $w5->id,
                'category_id' => $catPainting?->id,
                'title' => 'Master Bedroom Two-Tone Wall Repainting',
                'description' => 'Prepared walls, filled hairline cracks, and applied 2 coats of Dulux Velvet Touch.',
                'status' => 'COMPLETED',
                'scheduled_at' => now()->subDays(1),
                'started_at' => now()->subDays(1)->addHours(1),
                'completed_at' => now()->subDays(1)->addHours(5),
                'final_cost' => 9000.00,
                'address' => '190 Negombo Road',
                'city' => 'Wattala',
                'history' => [
                    ['status' => 'REQUESTED', 'note' => 'Job requested', 'minutes_ago' => 1800],
                    ['status' => 'ACCEPTED', 'note' => 'Worker accepted', 'minutes_ago' => 1700],
                    ['status' => 'SCHEDULED', 'note' => 'Scheduled appointment', 'minutes_ago' => 1600],
                    ['status' => 'IN_PROGRESS', 'note' => 'Work started', 'minutes_ago' => 1500],
                    ['status' => 'COMPLETED', 'note' => 'Painting completed cleanly', 'minutes_ago' => 1200],
                ],
            ],
            [
                'job_number' => 'JOB-CMP-006',
                'customer_id' => $c6->id,
                'worker_id' => $w6->id,
                'category_id' => $catCleaning?->id,
                'title' => 'Living Room Sofa & 6 Dining Chairs Shampooing',
                'description' => 'Deep extraction steam shampooing on fabric L-shaped couch and dining chairs.',
                'status' => 'COMPLETED',
                'scheduled_at' => now()->subDays(1),
                'started_at' => now()->subDays(1)->addHours(2),
                'completed_at' => now()->subDays(1)->addHours(4),
                'final_cost' => 5500.00,
                'address' => '54 Kurunegala Road',
                'city' => 'Kurunegala',
                'history' => [
                    ['status' => 'REQUESTED', 'note' => 'Job requested', 'minutes_ago' => 1500],
                    ['status' => 'ACCEPTED', 'note' => 'Worker accepted', 'minutes_ago' => 1400],
                    ['status' => 'SCHEDULED', 'note' => 'Scheduled appointment', 'minutes_ago' => 1350],
                    ['status' => 'IN_PROGRESS', 'note' => 'Work started', 'minutes_ago' => 1300],
                    ['status' => 'COMPLETED', 'note' => 'Stains removed and sanitized', 'minutes_ago' => 1100],
                ],
            ],

            // 6. REJECTED Jobs
            [
                'job_number' => 'JOB-REJ-001',
                'customer_id' => $c1->id,
                'worker_id' => $w2->id,
                'category_id' => $catPlumbing?->id,
                'title' => 'Emergency Midnight Pipe Burst',
                'description' => 'Burst main inlet pipe at midnight.',
                'status' => 'REJECTED',
                'reject_reason' => 'Outside operating hours and currently fully booked on site.',
                'address' => '45 Galle Road',
                'city' => 'Colombo',
                'history' => [
                    ['status' => 'REQUESTED', 'note' => 'Customer submitted request', 'minutes_ago' => 3600],
                    ['status' => 'REJECTED', 'note' => 'Worker declined: Outside operating hours', 'minutes_ago' => 3550],
                ],
            ],

            // 7. CANCELLED Jobs
            [
                'job_number' => 'JOB-CAN-001',
                'customer_id' => $c2->id,
                'worker_id' => $w3->id,
                'category_id' => $catCarpentry?->id,
                'title' => 'Wardrobe Sliding Door Roller Repair',
                'description' => 'Sliding mirror door coming off metal tracks.',
                'status' => 'CANCELLED',
                'cancel_reason' => 'Customer decided to purchase replacement wardrobe instead.',
                'address' => '12 Kandy Road',
                'city' => 'Gampaha',
                'history' => [
                    ['status' => 'REQUESTED', 'note' => 'Customer submitted request', 'minutes_ago' => 4800],
                    ['status' => 'ACCEPTED', 'note' => 'Worker accepted request', 'minutes_ago' => 4700],
                    ['status' => 'CANCELLED', 'note' => 'Customer cancelled: Buying replacement wardrobe', 'minutes_ago' => 4600],
                ],
            ],
        ];

        foreach ($jobsDefinition as $jDef) {
            $history = $jDef['history'] ?? [];
            $address = $jDef['address'] ?? '123 Main Street';
            $city = $jDef['city'] ?? 'Colombo';
            unset($jDef['history'], $jDef['address'], $jDef['city']);

            $job = Job::updateOrCreate(
                ['job_number' => $jDef['job_number']],
                $jDef
            );

            // Create Location
            JobLocation::updateOrCreate(
                ['job_id' => $job->id],
                [
                    'address_line1' => $address,
                    'city' => $city,
                    'state' => $city,
                    'latitude' => 6.9271,
                    'longitude' => 79.8612,
                ]
            );

            // Populate Status History
            foreach ($history as $hItem) {
                JobStatusHistory::updateOrCreate(
                    [
                        'job_id' => $job->id,
                        'to_status' => $hItem['status'],
                    ],
                    [
                        'changed_by_user_id' => $job->customer_id,
                        'from_status' => null,
                        'notes' => $hItem['note'] ?? null,
                        'created_at' => now()->subMinutes($hItem['minutes_ago'] ?? 0),
                    ]
                );
            }
        }
    }
}
