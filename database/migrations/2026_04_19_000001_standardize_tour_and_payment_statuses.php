<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Standardize Tours status
        // Change 'available' to 'active'
        DB::table('tours')->where('status', 'available')->update(['status' => 'active']);

        Schema::table('tours', function (Blueprint $table) {
            $table->string('status', 20)->default('active')->change();
        });

        // 2. Standardize Payments status
        // Change 'paid' to 'success'
        DB::table('payments')->where('payment_status', 'paid')->update(['payment_status' => 'success']);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tours', function (Blueprint $table) {
            $table->string('status', 20)->default('available')->change();
        });

        DB::table('tours')->where('status', 'active')->update(['status' => 'available']);

        DB::table('payments')->where('payment_status', 'success')->update(['payment_status' => 'paid']);
    }
};
