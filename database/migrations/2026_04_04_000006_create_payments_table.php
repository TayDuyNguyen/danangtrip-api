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
            $table->foreignId('booking_id')->constrained('bookings')->cascadeOnDelete();
            $table->string('transaction_code', 100)->unique();
            $table->decimal('amount', 12, 0);
            $table->string('payment_method', 30);
            $table->string('payment_status', 30)->default('pending');
            $table->string('payment_gateway', 50)->nullable();
            $table->json('gateway_response')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamp('refunded_at')->nullable();
            $table->text('refund_reason')->nullable();
            $table->timestamps();

            $table->index('payment_status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
