<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_related', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
            $table->string('related_erp_id')->nullable();
            $table->string('related_sku')->nullable();
            $table->string('relation_type', 32)->default('similar');
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamp('resolved_at')->nullable();
            $table->timestamps();

            $table->index(['product_id', 'relation_type']);
            $table->index('related_erp_id');
            $table->index('related_sku');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_related');
    }
};
