<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('chat_knowledge_base', function (Blueprint $table): void {
            if (! Schema::hasColumn('chat_knowledge_base', 'embedding_model')) {
                $table->string('embedding_model', 100)->nullable()->after('embedding');
            }

            if (! Schema::hasColumn('chat_knowledge_base', 'embedding_dimension')) {
                $table->unsignedSmallInteger('embedding_dimension')->nullable()->after('embedding_model');
            }

            if (! Schema::hasColumn('chat_knowledge_base', 'content_hash')) {
                $table->string('content_hash', 64)->nullable()->index()->after('embedding_dimension');
            }

            if (! Schema::hasColumn('chat_knowledge_base', 'last_embedded_at')) {
                $table->timestamp('last_embedded_at')->nullable()->after('content_hash');
            }
        });
    }

    public function down(): void
    {
        Schema::table('chat_knowledge_base', function (Blueprint $table): void {
            if (Schema::hasColumn('chat_knowledge_base', 'last_embedded_at')) {
                $table->dropColumn('last_embedded_at');
            }

            if (Schema::hasColumn('chat_knowledge_base', 'content_hash')) {
                $table->dropColumn('content_hash');
            }

            if (Schema::hasColumn('chat_knowledge_base', 'embedding_dimension')) {
                $table->dropColumn('embedding_dimension');
            }

            if (Schema::hasColumn('chat_knowledge_base', 'embedding_model')) {
                $table->dropColumn('embedding_model');
            }
        });
    }
};
