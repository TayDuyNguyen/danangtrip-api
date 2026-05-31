<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * (Tạo bảng promotions — mã giảm giá / khuyến mãi)
     */
    public function up(): void
    {
        Schema::create('promotions', function (Blueprint $table) {
            $table->id();

            // Core info
            $table->string('code', 50)->unique()->comment('Mã giảm giá — duy nhất, không phân biệt hoa thường');
            $table->string('name', 150)->comment('Tên chương trình khuyến mãi');
            $table->text('description')->nullable()->comment('Mô tả chi tiết');

            // Discount
            $table->enum('discount_type', ['percent', 'fixed'])->default('percent')->comment('Loại giảm giá: % hoặc số tiền cố định');
            $table->decimal('discount_value', 12, 2)->default(0)->comment('Giá trị giảm');
            $table->decimal('max_discount_amount', 12, 2)->nullable()->comment('Mức giảm tối đa (với discount_type=percent)');

            // Conditions
            $table->decimal('min_order_amount', 12, 2)->default(0)->comment('Giá trị đơn hàng tối thiểu để áp dụng');
            $table->integer('usage_limit')->unsigned()->nullable()->comment('Giới hạn tổng lượt dùng (null = không giới hạn)');
            $table->integer('usage_per_user')->unsigned()->default(1)->comment('Số lần mỗi user được dùng');
            $table->integer('used_count')->unsigned()->default(0)->comment('Số lượt đã dùng');

            // Validity
            $table->timestamp('starts_at')->nullable()->comment('Thời điểm bắt đầu hiệu lực');
            $table->timestamp('ends_at')->nullable()->comment('Thời điểm hết hạn');

            // Status
            $table->enum('status', ['active', 'inactive', 'expired'])->default('active')->comment('Trạng thái');

            $table->timestamps();

            // Indexes
            $table->index('status');
            $table->index(['starts_at', 'ends_at']);

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('promotions');
    }
};
