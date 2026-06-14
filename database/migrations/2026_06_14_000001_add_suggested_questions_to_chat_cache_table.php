<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('chat_cache', function (Blueprint $table): void {
            $table->json('suggested_questions')->nullable()->after('recommendations');
        });
    }

    public function down(): void
    {
        Schema::table('chat_cache', function (Blueprint $table): void {
            $table->dropColumn('suggested_questions');
        });
    }
};
