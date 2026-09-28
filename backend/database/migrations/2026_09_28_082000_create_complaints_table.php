<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('complaints', function (Blueprint $table) {
            $table->id();
            $table->string('complaint_number')->unique();
            $table->foreignId('job_id')->constrained('jobs')->onDelete('cascade');
            $table->foreignId('complainant_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('against_user_id')->constrained('users')->onDelete('cascade');
            $table->string('subject');
            $table->text('description');
            $table->enum('status', ['OPEN', 'UNDER_INVESTIGATION', 'RESOLVED', 'DISMISSED'])->default('OPEN');
            $table->text('admin_notes')->nullable();
            $table->timestamp('resolved_at')->nullable();
            $table->foreignId('resolved_by_admin_id')->nullable()->constrained('admin_users')->onDelete('set null');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('complaints');
    }
};
