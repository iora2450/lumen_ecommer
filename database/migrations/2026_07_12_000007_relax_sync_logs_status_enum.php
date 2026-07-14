<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::getConnection()->getDriverName() === 'sqlite') {
            DB::statement("ALTER TABLE sync_logs DROP COLUMN status");
            DB::statement("ALTER TABLE sync_logs ADD COLUMN status VARCHAR(32) NOT NULL DEFAULT 'started'");
        } else {
            DB::statement("ALTER TABLE sync_logs MODIFY COLUMN status VARCHAR(32) NOT NULL DEFAULT 'started'");
        }
    }

    public function down(): void
    {
        if (Schema::getConnection()->getDriverName() === 'sqlite') {
            DB::statement("DELETE FROM sync_logs WHERE status NOT IN ('started','success','failed')");
            DB::statement("ALTER TABLE sync_logs DROP COLUMN status");
            DB::statement("ALTER TABLE sync_logs ADD COLUMN status VARCHAR(32) NOT NULL DEFAULT 'started'");
        }
    }
};
