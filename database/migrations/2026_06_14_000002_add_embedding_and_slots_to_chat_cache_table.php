<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('chat_cache', function (Blueprint $table): void {
            $table->json('embedding')->nullable()->after('suggested_questions');
            $table->json('slots')->nullable()->after('embedding');
        });
    }

    public function down(): void
    {
        Schema::table('chat_cache', function (Blueprint $table): void {
            $table->dropColumn(['embedding', 'slots']);
        });
    }
};
