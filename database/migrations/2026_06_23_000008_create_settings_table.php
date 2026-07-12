<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('settings', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->text('value')->nullable();
            $table->string('group')->default('general')->index();
            $table->timestamps();
        });

        // Sync log
        Schema::create('sync_logs', function (Blueprint $table) {
            $table->id();
            $table->string('source')->default('lumen');
            $table->enum('status', ['started', 'success', 'failed'])->default('started');
            $table->unsignedInteger('products_synced')->default(0);
            $table->unsignedInteger('categories_synced')->default(0);
            $table->unsignedInteger('brands_synced')->default(0);
            $table->text('message')->nullable();
            $table->timestamp('finished_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sync_logs');
        Schema::dropIfExists('settings');
    }
};