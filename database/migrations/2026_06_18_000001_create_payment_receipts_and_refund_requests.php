<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payment_receipts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('booking_id')->constrained('bookings')->cascadeOnDelete();
            $table->foreignId('payment_id')->nullable()->constrained('payments')->nullOnDelete();
            $table->string('gateway', 50)->default('sepay');
            $table->string('gateway_transaction_id', 150)->unique();
            $table->decimal('amount', 12, 2);
            $table->string('transfer_content', 255)->nullable();
            $table->json('gateway_payload')->nullable();
            $table->timestamp('received_at')->nullable();
            $table->timestamps();

            $table->index(['booking_id', 'received_at']);
        });

        Schema::create('refund_requests', function (Blueprint $table) {
            $table->id();
            $table->string('refund_code', 30)->unique();
            $table->foreignId('booking_id')->constrained('bookings')->cascadeOnDelete();
            $table->foreignId('payment_id')->nullable()->constrained('payments')->nullOnDelete();
            $table->string('reason_type', 30);
            $table->decimal('requested_amount', 12, 2);
            $table->decimal('approved_amount', 12, 2)->nullable();
            $table->decimal('refund_percent', 5, 2)->default(0);
            $table->string('status', 30)->default('pending');
            $table->string('bank_code', 20)->nullable();
            $table->text('account_no')->nullable();
            $table->string('account_name', 120)->nullable();
            $table->json('policy_snapshot')->nullable();
            $table->text('reason')->nullable();
            $table->foreignId('requested_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('completed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('transfer_reference', 150)->nullable();
            $table->string('evidence_url', 500)->nullable();
            $table->timestamp('requested_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->index(['status', 'created_at']);
            $table->index(['booking_id', 'status']);
        });

        Schema::table('payments', function (Blueprint $table) {
            $table->decimal('received_amount', 12, 2)->default(0)->after('amount');
            $table->decimal('short_amount', 12, 2)->default(0)->after('received_amount');
            $table->decimal('excess_amount', 12, 2)->default(0)->after('short_amount');
            $table->boolean('is_discrepancy')->default(false)->after('excess_amount');
            $table->string('reconciliation_status', 30)->nullable()->after('is_discrepancy');
        });

        DB::statement('ALTER TABLE payments ADD CONSTRAINT payments_reconciliation_amounts_chk CHECK (received_amount >= 0 AND short_amount >= 0 AND excess_amount >= 0)');
        DB::statement('ALTER TABLE refund_requests ADD CONSTRAINT refund_requests_amount_chk CHECK (requested_amount >= 0 AND approved_amount >= 0 AND refund_percent >= 0 AND refund_percent <= 100)');
        DB::statement("ALTER TABLE refund_requests ADD CONSTRAINT refund_requests_status_chk CHECK (status IN ('pending','processing','completed','failed','rejected'))");
        DB::statement("ALTER TABLE refund_requests ADD CONSTRAINT refund_requests_reason_chk CHECK (reason_type IN ('cancellation','overpayment','admin_adjustment','legacy_refund'))");

        $now = now();
        DB::table('payments')
            ->whereIn('payment_status', ['success', 'refunded'])
            ->orderBy('id')
            ->each(function ($payment) use ($now) {
                DB::table('payment_receipts')->insertOrIgnore([
                    'booking_id' => $payment->booking_id,
                    'payment_id' => $payment->id,
                    'gateway' => $payment->payment_gateway ?: $payment->payment_method,
                    'gateway_transaction_id' => 'legacy-payment-'.$payment->id,
                    'amount' => $payment->amount,
                    'transfer_content' => 'Migrated from existing payment '.$payment->transaction_code,
                    'received_at' => $payment->paid_at ?: $payment->created_at,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);

                DB::table('payments')->where('id', $payment->id)->update([
                    'received_amount' => $payment->amount,
                    'reconciliation_status' => 'matched',
                ]);

                if ($payment->payment_status === 'refunded') {
                    DB::table('refund_requests')->insert([
                        'refund_code' => 'RF-LEGACY-'.$payment->id,
                        'booking_id' => $payment->booking_id,
                        'payment_id' => $payment->id,
                        'reason_type' => 'legacy_refund',
                        'requested_amount' => $payment->amount,
                        'approved_amount' => $payment->amount,
                        'refund_percent' => 100,
                        'status' => 'completed',
                        'reason' => $payment->refund_reason ?: 'Migrated legacy refund',
                        'requested_at' => $payment->refunded_at ?: $payment->updated_at,
                        'completed_at' => $payment->refunded_at ?: $payment->updated_at,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ]);
                }
            });
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE payments DROP CONSTRAINT IF EXISTS payments_reconciliation_amounts_chk');
        Schema::table('payments', function (Blueprint $table) {
            $table->dropColumn([
                'received_amount',
                'short_amount',
                'excess_amount',
                'is_discrepancy',
                'reconciliation_status',
            ]);
        });

        Schema::dropIfExists('refund_requests');
        Schema::dropIfExists('payment_receipts');
    }
};
