<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->string('currency', 3)->default('USD')->after('cost');
            $table->integer('available_qty')->default(0)->after('qty');
            $table->integer('reserved_qty')->default(0)->after('available_qty');
            $table->integer('backorder_qty')->default(0)->after('reserved_qty');
            $table->string('stock_status', 32)->default('in_stock')->after('backorder_qty');

            $table->text('short_description')->nullable()->after('description');

            $table->timestamp('promotion_starts_at')->nullable()->after('promotion_price');
            $table->timestamp('promotion_ends_at')->nullable()->after('promotion_starts_at');

            $table->timestamp('erp_last_sync_at')->nullable()->after('synced_at');

            $table->index('stock_status');
            $table->index('is_promotion');
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropIndex(['stock_status']);
            $table->dropIndex(['is_promotion']);

            $table->dropColumn([
                'currency',
                'available_qty',
                'reserved_qty',
                'backorder_qty',
                'stock_status',
                'short_description',
                'promotion_starts_at',
                'promotion_ends_at',
                'erp_last_sync_at',
            ]);
        });
    }
};
