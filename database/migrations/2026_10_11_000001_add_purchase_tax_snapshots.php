<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->decimal('tax_rate', 5, 2)->default(13);
        });
        Schema::table('quotes', function (Blueprint $table) {
            // Null distinguishes historical orders, whose agreed totals must not change.
            $table->decimal('tax_amount', 14, 2)->nullable();
        });
        Schema::table('quote_items', function (Blueprint $table) {
            $table->decimal('tax_rate', 5, 2)->nullable();
            $table->decimal('discount_amount', 14, 2)->nullable();
            $table->decimal('tax_amount', 14, 2)->nullable();
            $table->decimal('total', 14, 2)->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('quote_items', fn (Blueprint $table) => $table->dropColumn(['tax_rate', 'discount_amount', 'tax_amount', 'total']));
        Schema::table('quotes', fn (Blueprint $table) => $table->dropColumn('tax_amount'));
        Schema::table('products', fn (Blueprint $table) => $table->dropColumn('tax_rate'));
    }
};
