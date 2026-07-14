<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_technical_specs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
            $table->string('group')->nullable();
            $table->string('key');
            $table->string('label')->nullable();
            $table->text('value')->nullable();
            $table->string('unit', 32)->nullable();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index(['product_id', 'sort_order']);
            $table->index(['product_id', 'group']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_technical_specs');
    }
};
