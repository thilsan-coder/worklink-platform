<?php

namespace Database\Seeders;

use App\Models\Job;
use App\Models\Review;
use App\Models\WorkerProfile;
use Illuminate\Database\Seeder;

class DevelopmentReviewSeeder extends Seeder
{
    public function run(): void
    {
        $completedJobs = Job::where('status', 'COMPLETED')->get();

        $reviewsCatalog = [
            'JOB-CMP-001' => [
                'rating' => 5,
                'comment' => 'Excellent electrical work! Ruwan arrived on time, diagnosed the short circuit immediately, and rewired the breaker cleanly. Highly recommended!',
                'communication' => 5,
                'quality' => 5,
                'punctuality' => 5,
            ],
            'JOB-CMP-002' => [
                'rating' => 5,
                'comment' => 'Kamal did a fantastic job fixing our water tank float valve. No more overflowing water. Very polite and professional.',
                'communication' => 5,
                'quality' => 5,
                'punctuality' => 4,
            ],
            'JOB-CMP-003' => [
                'rating' => 4,
                'comment' => 'Great custom carpentry work on the bookshelf. Solid mahogany timber and very neat joints. Took slightly longer than expected but quality is top notch.',
                'communication' => 4,
                'quality' => 5,
                'punctuality' => 4,
            ],
            'JOB-CMP-004' => [
                'rating' => 5,
                'comment' => 'Janaka is an expert with inverter ACs. Vacuumed the system and refilled gas properly. AC is cooling super fast now.',
                'communication' => 5,
                'quality' => 5,
                'punctuality' => 5,
            ],
            'JOB-CMP-005' => [
                'rating' => 4,
                'comment' => 'Very neat painting finish on the walls. Left the room clean and covered all furniture before painting. Good pricing.',
                'communication' => 4,
                'quality' => 4,
                'punctuality' => 4,
            ],
            'JOB-CMP-006' => [
                'rating' => 5,
                'comment' => 'Amazing deep cleaning and sofa shampooing! Removed old tea stains that other cleaners could not get out. Will hire again.',
                'communication' => 5,
                'quality' => 5,
                'punctuality' => 5,
            ],
        ];

        foreach ($completedJobs as $job) {
            $data = $reviewsCatalog[$job->job_number] ?? [
                'rating' => 5,
                'comment' => 'Very satisfied with the completed work. Professional and punctual.',
                'communication' => 5,
                'quality' => 5,
                'punctuality' => 5,
            ];

            Review::updateOrCreate(
                ['job_id' => $job->id],
                [
                    'customer_id' => $job->customer_id,
                    'worker_id' => $job->worker_id,
                    'overall_rating' => $data['rating'],
                    'communication_rating' => $data['communication'] ?? 5,
                    'work_quality_rating' => $data['quality'] ?? 5,
                    'punctuality_rating' => $data['punctuality'] ?? 5,
                    'comment' => $data['comment'],
                ]
            );
        }

        // Recalculate average ratings and total reviews for all workers
        $workerProfiles = WorkerProfile::all();
        foreach ($workerProfiles as $profile) {
            $reviews = Review::where('worker_id', $profile->user_id)->get();
            $count = $reviews->count();
            $avg = $count > 0 ? round($reviews->avg('overall_rating'), 2) : 0.00;

            $profile->update([
                'average_rating' => $avg,
                'total_reviews' => $count,
                'completed_jobs_count' => Job::where('worker_id', $profile->user_id)->where('status', 'COMPLETED')->count(),
            ]);
        }
    }
}
