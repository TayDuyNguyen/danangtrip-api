<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Add is_new column to ratings table.
     * is_new = 1: Admin chưa xem (New)
     * is_new = 0: Admin đã xem (Viewed)
     * Không ảnh hưởng đến hiển thị công khai.
     */
    public function up(): void
    {
        Schema::table('ratings', function (Blueprint $table) {
            $table->boolean('is_new')
                ->default(true)
                ->after('helpful_count')
                ->comment('1 = new/unread by admin, 0 = admin has viewed. Does not affect public display.');
        });
    }

    public function down(): void
    {
        Schema::table('ratings', function (Blueprint $table) {
            $table->dropColumn('is_new');
        });
    }
};
