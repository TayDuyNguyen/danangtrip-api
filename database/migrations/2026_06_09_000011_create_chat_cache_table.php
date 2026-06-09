<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('chat_cache', function (Blueprint $table): void {
            $table->id();
            $table->string('question_hash', 64)->unique();
            $table->string('normalized_question', 500);
            $table->string('locale', 10)->default('vi')->index();
            $table->string('intent', 50)->index();
            $table->longText('answer');
            $table->json('recommendations')->nullable();
            $table->json('center')->nullable();
            $table->unsignedTinyInteger('zoom')->nullable();
            $table->string('provider', 50)->nullable();
            $table->string('model', 100)->nullable();
            $table->timestamp('expires_at')->nullable()->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('chat_cache');
    }
};
