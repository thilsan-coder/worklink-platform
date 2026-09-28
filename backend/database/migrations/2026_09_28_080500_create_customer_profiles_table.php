<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('customer_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained('users')->onDelete('cascade');
            $table->string('default_address')->nullable();
            $table->decimal('default_latitude', 10, 8)->nullable();
            $table->decimal('default_longitude', 11, 8)->nullable();
            $table->string('emergency_contact')->nullable();
            $table->string('preferred_payment_method')->default('cash');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customer_profiles');
    }
};
