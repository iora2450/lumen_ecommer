<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('product_variants', function (Blueprint $table) {
            $table->decimal('compare_at_price', 10, 2)->nullable()->after('cost');
            $table->integer('available_qty')->default(0)->after('qty');
            $table->integer('reserved_qty')->default(0)->after('available_qty');
            $table->string('stock_status', 32)->default('in_stock')->after('reserved_qty');
            $table->string('image_url')->nullable()->after('stock_status');
            $table->timestamp('erp_last_sync_at')->nullable()->after('is_active');

            $table->index('stock_status');
        });
    }

    public function down(): void
    {
        Schema::table('product_variants', function (Blueprint $table) {
            $table->dropIndex(['stock_status']);
            $table->dropColumn([
                'compare_at_price',
                'available_qty',
                'reserved_qty',
                'stock_status',
                'image_url',
                'erp_last_sync_at',
            ]);
        });
    }
};
