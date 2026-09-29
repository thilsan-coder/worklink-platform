<?php

namespace Database\Seeders;

use App\Models\Chat;
use App\Models\Job;
use App\Models\Notification;
use App\Models\Review;
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
        $c2 = $customers->count() > 1 ? $customers->get(1) : $c1;
        $w1 = $workers->first();
        $w2 = $workers->count() > 1 ? $workers->get(1) : $w1;

        $jobs = Job::all();
        $j1 = $jobs->first();
        $j2 = $jobs->count() > 1 ? $jobs->get(1) : $j1;

        $chats = Chat::all();
        $ch1 = $chats->first();

        $reviews = Review::all();
        $r1 = $reviews->first();

        $notifications = [
            // Worker 1 notifications
            [
                'user_id' => $w1->id,
                'title' => 'New Service Request',
                'body' => 'Customer requested Main Distribution Board Trip Repair.',
                'type' => 'JOB_REQUESTED',
                'reference_id' => $j1?->id,
                'data' => [
                    'entity_type' => 'job',
                    'entity_id' => $j1?->id,
                    'job_id' => $j1?->id,
                    'customer_id' => $c1->id,
                ],
                'is_read' => false,
                'created_at' => now()->subMinutes(15),
            ],
            [
                'user_id' => $w1->id,
                'title' => 'New Message',
                'body' => $c1->name . ' sent you a message: "Can you arrive 15 minutes earlier?"',
                'type' => 'NEW_MESSAGE',
                'reference_id' => $ch1?->id,
                'data' => [
                    'entity_type' => 'chat',
                    'entity_id' => $ch1?->id,
                    'conversation_id' => $ch1?->id,
                    'job_id' => $ch1?->job_id,
                    'sender_id' => $c1->id,
                ],
                'is_read' => false,
                'created_at' => now()->subHours(1),
            ],
            [
                'user_id' => $w1->id,
                'title' => 'New Review',
                'body' => 'You received a new 5-star review from ' . $c1->name . ' for "Electrical Wiring".',
                'type' => 'NEW_REVIEW',
                'reference_id' => $r1?->id,
                'data' => [
                    'entity_type' => 'review',
                    'entity_id' => $r1?->id,
                    'review_id' => $r1?->id,
                    'job_id' => $r1?->job_id,
                    'rating' => 5,
                ],
                'is_read' => true,
                'created_at' => now()->subDays(1),
            ],
            [
                'user_id' => $w1->id,
                'title' => 'Job Cancelled',
                'body' => 'Job was cancelled: Customer rescheduled for next week.',
                'type' => 'JOB_CANCELLED',
                'reference_id' => $j2?->id,
                'data' => [
                    'entity_type' => 'job',
                    'entity_id' => $j2?->id,
                    'job_id' => $j2?->id,
                ],
                'is_read' => true,
                'created_at' => now()->subDays(2),
            ],

            // Customer 1 notifications
            [
                'user_id' => $c1->id,
                'title' => 'Job Accepted',
                'body' => 'Ruwan Silva accepted your service request: Main Distribution Board Repair.',
                'type' => 'JOB_ACCEPTED',
                'reference_id' => $j1?->id,
                'data' => [
                    'entity_type' => 'job',
                    'entity_id' => $j1?->id,
                    'job_id' => $j1?->id,
                    'worker_id' => $w1->id,
                ],
                'is_read' => true,
                'created_at' => now()->subHours(5),
            ],
            [
                'user_id' => $c1->id,
                'title' => 'Job Scheduled',
                'body' => 'Job appointment scheduled for: ' . now()->addDay()->format('M d, Y 10:00 A'),
                'type' => 'JOB_SCHEDULED',
                'reference_id' => $j1?->id,
                'data' => [
                    'entity_type' => 'job',
                    'entity_id' => $j1?->id,
                    'job_id' => $j1?->id,
                ],
                'is_read' => false,
                'created_at' => now()->subHours(3),
            ],
            [
                'user_id' => $c1->id,
                'title' => 'Job Started',
                'body' => 'The worker has started work on your job.',
                'type' => 'JOB_STARTED',
                'reference_id' => $j1?->id,
                'data' => [
                    'entity_type' => 'job',
                    'entity_id' => $j1?->id,
                    'job_id' => $j1?->id,
                ],
                'is_read' => false,
                'created_at' => now()->subMinutes(45),
            ],
            [
                'user_id' => $c1->id,
                'title' => 'Job Completed',
                'body' => 'The job has been marked as completed: Main Distribution Board Repair.',
                'type' => 'JOB_COMPLETED',
                'reference_id' => $j1?->id,
                'data' => [
                    'entity_type' => 'job',
                    'entity_id' => $j1?->id,
                    'job_id' => $j1?->id,
                ],
                'is_read' => false,
                'created_at' => now()->subMinutes(10),
            ],
            [
                'user_id' => $c1->id,
                'title' => 'Job Rejected',
                'body' => 'The worker declined your job request: Outside service area.',
                'type' => 'JOB_REJECTED',
                'reference_id' => $j2?->id,
                'data' => [
                    'entity_type' => 'job',
                    'entity_id' => $j2?->id,
                    'job_id' => $j2?->id,
                ],
                'is_read' => true,
                'created_at' => now()->subDays(3),
            ],
            [
                'user_id' => $c1->id,
                'title' => 'New Message',
                'body' => 'Kamal Fernando sent you a message: "I have arrived at the location."',
                'type' => 'NEW_MESSAGE',
                'reference_id' => $ch1?->id,
                'data' => [
                    'entity_type' => 'chat',
                    'entity_id' => $ch1?->id,
                    'conversation_id' => $ch1?->id,
                    'job_id' => $ch1?->job_id,
                ],
                'is_read' => true,
                'created_at' => now()->subHours(2),
            ],
        ];

        foreach ($notifications as $n) {
            Notification::updateOrCreate(
                [
                    'user_id' => $n['user_id'],
                    'title' => $n['title'],
                    'type' => $n['type'],
                ],
                [
                    'body' => $n['body'],
                    'reference_id' => $n['reference_id'],
                    'data' => $n['data'],
                    'is_read' => $n['is_read'],
                    'read_at' => $n['is_read'] ? now()->subHours(1) : null,
                    'created_at' => $n['created_at'],
                    'updated_at' => now(),
                ]
            );
        }
    }
}
