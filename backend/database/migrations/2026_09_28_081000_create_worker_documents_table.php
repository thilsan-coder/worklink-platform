<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('worker_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('worker_profile_id')->constrained('worker_profiles')->onDelete('cascade');
            $table->enum('document_type', ['id_card', 'license', 'certificate', 'other'])->default('id_card');
            $table->string('document_number')->nullable();
            $table->string('file_path');
            $table->string('file_type')->default('image');
            $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');
            $table->text('rejection_reason')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('worker_documents');
    }
};
