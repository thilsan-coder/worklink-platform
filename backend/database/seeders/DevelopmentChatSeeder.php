<?php

namespace Database\Seeders;

use App\Models\Chat;
use App\Models\Job;
use App\Models\Message;
use Illuminate\Database\Seeder;

class DevelopmentChatSeeder extends Seeder
{
    public function run(): void
    {
        $eligibleJobs = Job::whereIn('status', ['SCHEDULED', 'IN_PROGRESS', 'COMPLETED'])->get();

        foreach ($eligibleJobs as $job) {
            $chat = Chat::updateOrCreate(
                ['job_id' => $job->id],
                [
                    'customer_id' => $job->customer_id,
                    'worker_id' => $job->worker_id,
                    'is_active' => true,
                    'last_message_at' => now()->subMinutes(15),
                ]
            );

            $messages = [
                [
                    'sender_id' => $job->customer_id,
                    'message_body' => 'Hello, are you available to inspect the job location?',
                    'is_read' => true,
                    'minutes_ago' => 60,
                ],
                [
                    'sender_id' => $job->worker_id,
                    'message_body' => 'Yes, I can arrive as per the schedule with necessary tools and safety gear.',
                    'is_read' => true,
                    'minutes_ago' => 45,
                ],
                [
                    'sender_id' => $job->customer_id,
                    'message_body' => 'Great! Please call me when you reach the gate.',
                    'is_read' => true,
                    'minutes_ago' => 30,
                ],
                [
                    'sender_id' => $job->worker_id,
                    'message_body' => 'Understood. On my way now.',
                    'is_read' => ($job->status === 'COMPLETED'), // Unread if in progress or scheduled
                    'minutes_ago' => 15,
                ],
            ];

            foreach ($messages as $msgData) {
                Message::firstOrCreate(
                    [
                        'chat_id' => $chat->id,
                        'sender_id' => $msgData['sender_id'],
                        'message_body' => $msgData['message_body'],
                    ],
                    [
                        'message_type' => 'text',
                        'is_read' => $msgData['is_read'],
                        'read_at' => $msgData['is_read'] ? now()->subMinutes($msgData['minutes_ago'] - 5) : null,
                        'created_at' => now()->subMinutes($msgData['minutes_ago']),
                    ]
                );
            }
        }
    }
}
