<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('jobs', function (Blueprint $table) {
            $table->id();
            $table->string('job_number')->unique();
            $table->foreignId('customer_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('worker_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('category_id')->constrained('categories')->onDelete('cascade');
            $table->foreignId('skill_id')->nullable()->constrained('skills')->onDelete('set null');
            $table->string('title');
            $table->text('description');
            $table->enum('status', [
                'REQUESTED',
                'ACCEPTED',
                'ON_THE_WAY',
                'ARRIVED',
                'WORK_STARTED',
                'WORK_COMPLETED',
                'CUSTOMER_CONFIRMED',
                'COMPLETED',
                'CANCELLED',
                'REJECTED',
                'DISPUTED'
            ])->default('REQUESTED');
            $table->text('cancel_reason')->nullable();
            $table->text('reject_reason')->nullable();
            $table->decimal('estimated_cost', 10, 2)->nullable();
            $table->decimal('final_cost', 10, 2)->nullable();
            $table->timestamp('scheduled_at')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('confirmed_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['customer_id', 'status']);
            $table->index(['worker_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('jobs');
    }
};
