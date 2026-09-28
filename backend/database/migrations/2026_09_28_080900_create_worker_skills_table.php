<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('worker_skills', function (Blueprint $table) {
            $table->id();
            $table->foreignId('worker_profile_id')->constrained('worker_profiles')->onDelete('cascade');
            $table->foreignId('skill_id')->constrained('skills')->onDelete('cascade');
            $table->timestamps();

            $table->unique(['worker_profile_id', 'skill_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('worker_skills');
    }
};
