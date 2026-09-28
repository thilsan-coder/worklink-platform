<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reviews', function (Blueprint $table) {
            $table->id();
            $table->foreignId('job_id')->unique()->constrained('jobs')->onDelete('cascade');
            $table->foreignId('customer_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('worker_id')->constrained('users')->onDelete('cascade');
            $table->decimal('overall_rating', 2, 1);
            $table->decimal('communication_rating', 2, 1)->nullable();
            $table->decimal('work_quality_rating', 2, 1)->nullable();
            $table->decimal('punctuality_rating', 2, 1)->nullable();
            $table->decimal('professionalism_rating', 2, 1)->nullable();
            $table->text('comment')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reviews');
    }
};
