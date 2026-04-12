<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * (Chạy migration)
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->index('created_at', 'idx_users_created_at');
        });
    }

    /**
     * Reverse the migrations.
     * (Hoàn tác migration)
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex('idx_users_created_at');
        });
    }
};
