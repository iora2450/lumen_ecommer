<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('processed_syncs', function (Blueprint $table) {
            $table->string('sync_id')->primary();
            $table->string('source', 32)->default('erp');
            $table->string('mode', 16)->default('upsert');
            $table->string('status', 16);
            $table->unsignedInteger('items_processed')->default(0);
            $table->unsignedInteger('items_failed')->default(0);
            $table->json('summary')->nullable();
            $table->timestamp('received_at');
            $table->timestamp('finished_at')->nullable();

            $table->index(['source', 'status']);
            $table->index('received_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('processed_syncs');
    }
};
