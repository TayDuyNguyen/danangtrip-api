<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        DB::transaction(function (): void {
            $ids = DB::table('tour_categories')
                ->orderBy('sort_order')
                ->orderBy('id')
                ->pluck('id');

            foreach ($ids as $index => $id) {
                DB::table('tour_categories')
                    ->where('id', $id)
                    ->update([
                        'sort_order' => $index + 1,
                        'updated_at' => now(),
                    ]);
            }
        });

        Schema::table('tour_categories', function (Blueprint $table): void {
            $table->unique('sort_order');
        });
    }

    public function down(): void
    {
        Schema::table('tour_categories', function (Blueprint $table): void {
            $table->dropUnique('tour_categories_sort_order_unique');
        });
    }
};
