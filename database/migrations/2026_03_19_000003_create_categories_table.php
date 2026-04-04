<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('categories', function (Blueprint $table): void {
            $table->id();
            $table->string('name', 50);
            $table->string('slug', 60)->unique();
            $table->string('icon', 50)->nullable();
            $table->text('description')->nullable();
            $table->string('image', 255)->nullable();
            $table->integer('sort_order')->default(0);
            $table->string('status', 20)->default('active')->index(); // active, inactive
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('categories');
    }
};
