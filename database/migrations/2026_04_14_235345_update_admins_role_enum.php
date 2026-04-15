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
        // Add 'superadmin' to the role enum
        DB::statement("ALTER TABLE admins MODIFY COLUMN role ENUM('admin', 'staff', 'sponsor', 'superadmin') DEFAULT 'admin'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Revert to original enum (note: this may fail if 'superadmin' rows exist)
        DB::statement("ALTER TABLE admins MODIFY COLUMN role ENUM('admin', 'staff', 'sponsor') DEFAULT 'admin'");
    }
};
