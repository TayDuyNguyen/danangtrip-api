<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('chat_messages', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('session_id', 100)->nullable()->index();
            $table->text('question');
            $table->longText('answer')->nullable();
            $table->string('intent', 50)->index();
            $table->boolean('is_in_scope')->default(true)->index();
            $table->unsignedInteger('tokens_used')->default(0);
            $table->string('provider', 50)->nullable();
            $table->string('model', 100)->nullable();
            $table->boolean('cache_hit')->default(false);
            $table->json('context')->nullable();
            $table->json('metadata')->nullable();
            $table->ipAddress('ip_address')->nullable();
            $table->string('user_agent', 500)->nullable();
            $table->timestamps();

            $table->index(['user_id', 'created_at']);
            $table->index(['intent', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('chat_messages');
    }
};
