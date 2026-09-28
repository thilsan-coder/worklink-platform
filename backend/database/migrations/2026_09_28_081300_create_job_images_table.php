<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('job_images', function (Blueprint $table) {
            $table->id();
            $table->foreignId('job_id')->constrained('jobs')->onDelete('cascade');
            $table->foreignId('uploader_id')->constrained('users')->onDelete('cascade');
            $table->enum('image_type', ['initial_request', 'work_proof'])->default('initial_request');
            $table->string('image_path');
            $table->string('caption')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('job_images');
    }
};
