<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('job_locations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('job_id')->unique()->constrained('jobs')->onDelete('cascade');
            $table->string('address_line1');
            $table->string('address_line2')->nullable();
            $table->string('city');
            $table->string('state')->nullable();
            $table->string('postal_code')->nullable();
            $table->decimal('latitude', 10, 8);
            $table->decimal('longitude', 11, 8);
            $table->string('landmark')->nullable();
            $table->timestamps();

            $table->index(['latitude', 'longitude']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('job_locations');
    }
};
