<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('chat_knowledge_base', function (Blueprint $table): void {
            $table->id();
            $table->string('type', 30)->index(); // tour, location, blog, faq, policy
            $table->string('title', 255);
            $table->longText('content');
            $table->unsignedBigInteger('reference_id')->nullable()->index();
            $table->string('reference_slug', 280)->nullable()->index();
            $table->json('metadata')->nullable();
            $table->json('embedding')->nullable();
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();

            $table->index(['type', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('chat_knowledge_base');
    }
};
