<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('chats', function (Blueprint $table) {
            $table->id();
            $table->foreignId('job_id')->unique()->constrained('jobs')->onDelete('cascade');
            $table->foreignId('customer_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('worker_id')->constrained('users')->onDelete('cascade');
            $table->timestamp('last_message_at')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('chats');
    }
};
