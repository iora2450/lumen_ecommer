<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        Schema::create('erp_order_deliveries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('quote_id')->unique()->constrained()->cascadeOnDelete();
            $table->json('payload');
            $table->unsignedInteger('attempts')->default(0);
            $table->timestamp('delivered_at')->nullable();
            $table->timestamp('next_attempt_at')->nullable()->index();
            $table->timestamp('checked_at')->nullable();
            $table->string('last_error', 500)->nullable();
            $table->json('erp_status')->nullable();
            $table->timestamps();
        });
    }
    public function down(): void
    {
        Schema::dropIfExists('erp_order_deliveries');
    }
};
