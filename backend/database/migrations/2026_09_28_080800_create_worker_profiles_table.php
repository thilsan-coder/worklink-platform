<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('worker_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained('users')->onDelete('cascade');
            $table->text('bio')->nullable();
            $table->unsignedInteger('experience_years')->default(0);
            $table->decimal('hourly_rate', 10, 2)->nullable();
            $table->unsignedInteger('service_area_radius_km')->default(25);
            $table->decimal('current_latitude', 10, 8)->nullable();
            $table->decimal('current_longitude', 11, 8)->nullable();
            $table->string('address')->nullable();
            $table->enum('availability_status', ['available', 'busy', 'offline'])->default('offline');
            $table->enum('verification_status', ['unverified', 'pending', 'verified', 'rejected'])->default('unverified');
            $table->text('verification_rejection_reason')->nullable();
            $table->timestamp('verified_at')->nullable();
            $table->decimal('average_rating', 3, 2)->default(0.00);
            $table->unsignedInteger('total_reviews')->default(0);
            $table->unsignedInteger('completed_jobs_count')->default(0);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('worker_profiles');
    }
};
