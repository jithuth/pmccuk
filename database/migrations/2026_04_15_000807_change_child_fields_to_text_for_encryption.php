<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('member_children', function (Blueprint $table) {
            $table->text('child_name')->nullable()->change();
            $table->text('dob')->nullable()->change();
        });

        Schema::table('renewal_children', function (Blueprint $table) {
            $table->text('child_name')->nullable()->change();
            $table->text('dob')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Keep as TEXT
    }
};
