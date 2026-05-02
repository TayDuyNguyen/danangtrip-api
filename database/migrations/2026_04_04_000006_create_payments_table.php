<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('booking_id')->constrained('bookings')->cascadeOnDelete();
            $table->string('transaction_code', 100)->unique();
            $table->decimal('amount', 12, 2);
            $table->string('payment_method', 30);
            $table->string('payment_status', 30)->default('pending');
            $table->string('payment_gateway', 50)->nullable();
            $table->json('gateway_response')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamp('refunded_at')->nullable();
            $table->text('refund_reason')->nullable();
            $table->timestamps();

            $table->index('payment_status');
            $table->index('booking_id');
            $table->index('payment_gateway');
            $table->index('created_at');
            $table->index('paid_at');
        });

        DB::statement("
            ALTER TABLE payments
            ADD CONSTRAINT payments_status_chk
            CHECK (payment_status IN ('pending','success','failed','refunded'))
        ");
        DB::statement('ALTER TABLE payments ADD CONSTRAINT payments_amount_chk CHECK (amount >= 0)');
        DB::statement("
            CREATE INDEX IF NOT EXISTS payments_success_paid_idx
            ON payments (paid_at DESC, booking_id)
            WHERE payment_status = 'success' AND paid_at IS NOT NULL
        ");
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
