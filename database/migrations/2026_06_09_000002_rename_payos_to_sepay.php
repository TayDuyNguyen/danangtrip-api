<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('settings')) {
            $hasSepaySetting = DB::table('settings')->where('key', 'payment.sepay')->exists();

            if ($hasSepaySetting) {
                DB::table('settings')->where('key', 'payment.payos')->delete();
            } else {
                DB::table('settings')
                    ->where('key', 'payment.payos')
                    ->update(['key' => 'payment.sepay']);
            }
        }

        if (Schema::hasTable('bookings') && Schema::hasColumn('bookings', 'payment_method')) {
            DB::table('bookings')
                ->where('payment_method', 'payos')
                ->update(['payment_method' => 'sepay']);
        }

        if (Schema::hasTable('payments') && Schema::hasColumn('payments', 'payment_method')) {
            DB::table('payments')
                ->where('payment_method', 'payos')
                ->update(['payment_method' => 'sepay']);
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('settings')) {
            $hasPayosSetting = DB::table('settings')->where('key', 'payment.payos')->exists();

            if ($hasPayosSetting) {
                DB::table('settings')->where('key', 'payment.sepay')->delete();
            } else {
                DB::table('settings')
                    ->where('key', 'payment.sepay')
                    ->update(['key' => 'payment.payos']);
            }
        }

        if (Schema::hasTable('bookings') && Schema::hasColumn('bookings', 'payment_method')) {
            DB::table('bookings')
                ->where('payment_method', 'sepay')
                ->update(['payment_method' => 'payos']);
        }

        if (Schema::hasTable('payments') && Schema::hasColumn('payments', 'payment_method')) {
            DB::table('payments')
                ->where('payment_method', 'sepay')
                ->update(['payment_method' => 'payos']);
        }
    }
};
