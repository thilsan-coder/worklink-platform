<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('job_id')->constrained('jobs')->onDelete('cascade');
            $table->foreignId('customer_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('worker_id')->constrained('users')->onDelete('cascade');
            $table->decimal('amount', 12, 2);
            $table->string('currency', 10)->default('LKR');
            $table->string('status', 30)->default('PENDING'); // PENDING, PROCESSING, PAID, FAILED, REFUND_PENDING, REFUNDED
            $table->string('payment_method', 50)->default('TEST_PAYMENT');
            $table->string('gateway', 50)->default('test');
            $table->string('gateway_transaction_id', 100)->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->text('failure_reason')->nullable();
            $table->timestamp('refunded_at')->nullable();
            $table->text('refund_reason')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['job_id', 'status']);
            $table->index(['customer_id', 'status']);
            $table->index(['worker_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
