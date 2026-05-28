<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('blog_categories', function (Blueprint $table) {
            $table->integer('sort_order')->default(0)->after('description');
        });

        $categories = DB::table('blog_categories')
            ->orderBy('created_at')
            ->orderBy('id')
            ->get(['id']);

        foreach ($categories as $index => $category) {
            DB::table('blog_categories')
                ->where('id', $category->id)
                ->update(['sort_order' => $index + 1]);
        }
    }

    public function down(): void
    {
        Schema::table('blog_categories', function (Blueprint $table) {
            $table->dropColumn('sort_order');
        });
    }
};
