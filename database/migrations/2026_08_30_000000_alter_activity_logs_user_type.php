<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('activity_logs')) {
            try {
                DB::statement("ALTER TABLE activity_logs MODIFY user_type VARCHAR(50) NULL DEFAULT 'guest'");
            } catch (\Exception $e) {}
        }
    }

    public function down(): void
    {
    }
};
