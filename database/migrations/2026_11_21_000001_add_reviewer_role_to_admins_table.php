<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Update admins role enum in MySQL to include 'reviewer'
        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE admins MODIFY COLUMN role ENUM('admin', 'staff', 'sponsor', 'superadmin', 'reviewer') DEFAULT 'admin'");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE admins MODIFY COLUMN role ENUM('admin', 'staff', 'sponsor', 'superadmin') DEFAULT 'admin'");
        }
    }
};
