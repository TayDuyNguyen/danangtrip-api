<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * (Tạo bảng landing_pages — quản lý các trang SEO landing)
     */
    public function up(): void
    {
        Schema::create('landing_pages', function (Blueprint $table) {
            $table->id();

            // Core information
            $table->string('slug', 100)->unique()->comment('Đường dẫn SEO — duy nhất, ví dụ: du-lich-da-nang');
            $table->string('title', 150)->comment('Tiêu đề chính của Landing Page');
            $table->enum('page_type', ['destination', 'tour_line', 'promotion'])->comment('Loại trang landing');
            $table->text('intro')->nullable()->comment('Đoạn giới thiệu ngắn');
            $table->string('hero_image')->nullable()->comment('Đường dẫn ảnh banner chính');

            // SEO Metadata
            $table->string('seo_title', 150)->nullable()->comment('Tiêu đề SEO');
            $table->text('seo_description')->nullable()->comment('Mô tả SEO');
            $table->string('og_image')->nullable()->comment('Ảnh chia sẻ mạng xã hội');

            // Configurations
            $table->json('filters')->nullable()->comment('Bộ lọc tour mặc định');
            $table->json('content_blocks')->nullable()->comment('FAQ, CTA, section mô tả');

            // Status
            $table->enum('status', ['draft', 'published'])->default('draft')->comment('Trạng thái hiển thị');

            $table->timestamps();

            // Indexing for search performance
            $table->index('page_type');
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('landing_pages');
    }
};
