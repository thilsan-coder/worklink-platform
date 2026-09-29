<?php

namespace Database\Seeders;

use App\Models\Job;
use App\Models\Notification;
use App\Models\User;
use Illuminate\Database\Seeder;

class DevelopmentNotificationSeeder extends Seeder
{
    public function run(): void
    {
        $customers = User::where('role', 'customer')->get();
        $workers = User::where('role', 'worker')->get();

        if ($customers->isEmpty() || $workers->isEmpty()) {
            return;
        }

        $c1 = $customers->first();
        $w1 = $workers->first();

        $notifications = [
            [
                'user_id' => $w1->id,
                'title' => 'New Service Request',
                'body' => 'Customer requested Main Distribution Board Trip Repair.',
                'type' => 'job_request',
                'is_read' => false,
            ],
            [
                'user_id' => $c1->id,
                'title' => 'Job Request Accepted',
                'body' => 'Ruwan Silva accepted your service request.',
                'type' => 'job_status',
                'is_read' => true,
            ],
            [
                'user_id' => $c1->id,
                'title' => 'Work Completed',
                'body' => 'Full Kitchen Appliance Rewiring has been marked completed.',
                'type' => 'job_status',
                'is_read' => true,
            ],
            [
                'user_id' => $w1->id,
                'title' => 'New 5★ Review Received',
                'body' => 'Kasun Perera rated you 5 stars: "Excellent electrical work!"',
                'type' => 'review_received',
                'is_read' => false,
            ],
        ];

        foreach ($notifications as $n) {
            Notification::firstOrCreate(
                [
                    'user_id' => $n['user_id'],
                    'title' => $n['title'],
                ],
                [
                    'body' => $n['body'],
                    'type' => $n['type'],
                    'is_read' => $n['is_read'],
                    'read_at' => $n['is_read'] ? now()->subHours(2) : null,
                    'created_at' => now()->subHours(4),
                ]
            );
        }
    }
}
