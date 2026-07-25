<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('categories', function (Blueprint $table) {
            $table->boolean('is_featured')->default(false)->after('is_active');
            $table->boolean('is_promotion')->default(false)->after('is_featured');
            $table->string('promotion_label')->nullable()->after('is_promotion');

            $table->index(['is_active', 'is_featured']);
            $table->index(['is_active', 'is_promotion']);
        });
    }

    public function down(): void
    {
        Schema::table('categories', function (Blueprint $table) {
            $table->dropIndex(['is_active', 'is_featured']);
            $table->dropIndex(['is_active', 'is_promotion']);
            $table->dropColumn(['is_featured', 'is_promotion', 'promotion_label']);
        });
    }
};
