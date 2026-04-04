<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bookings', function (Blueprint $table) {
            $table->id();
            $table->string('booking_code', 20)->unique();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('customer_name', 100);
            $table->string('customer_email', 100);
            $table->string('customer_phone', 20);
            $table->text('customer_address')->nullable();
            $table->text('customer_note')->nullable();
            $table->decimal('total_amount', 12, 0);
            $table->decimal('discount_amount', 12, 0)->default(0);
            $table->decimal('final_amount', 12, 0);
            $table->decimal('deposit_amount', 12, 0)->default(0);
            $table->string('payment_method', 30);
            $table->string('payment_status', 30)->default('unpaid');
            $table->string('booking_status', 30)->default('pending');
            $table->text('cancellation_reason')->nullable();
            $table->timestamp('booked_at')->useCurrent();
            $table->timestamp('confirmed_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->index('booking_status');
            $table->index('payment_status');
            $table->index('booked_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bookings');
    }
};
