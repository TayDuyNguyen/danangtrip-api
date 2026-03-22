<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('point_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('transaction_code', 50)->unique();
            $table->string('type', 30); // purchase, spend, bonus, refund
            $table->integer('amount'); // positive (+) for receive, negative (-) for spend
            $table->integer('balance_before');
            $table->integer('balance_after');
            $table->unsignedBigInteger('reference_id')->nullable();
            $table->string('reference_type', 50)->nullable(); // rating, purchase
            $table->string('description', 255)->nullable();
            $table->string('payment_method', 30)->nullable(); // momo, vnpay, bank
            $table->string('status', 20)->default('completed')->index(); // pending, completed, failed
            $table->timestamp('created_at')->nullable();

            $table->index('user_id');
            $table->index(['reference_id', 'reference_type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('point_transactions');
    }
};
