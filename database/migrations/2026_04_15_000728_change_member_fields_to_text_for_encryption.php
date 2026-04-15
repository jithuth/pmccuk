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
        Schema::table('members', function (Blueprint $table) {
            $table->text('dob')->nullable()->change();
            $table->text('spouse_name')->nullable()->change();
            $table->text('spouse_mobile')->nullable()->change();
            $table->text('spouse_dob')->nullable()->change();
            $table->text('emergency_name')->nullable()->change();
            $table->text('emergency_mobile')->nullable()->change();
            $table->text('house_details')->nullable()->change();
            $table->text('post_code')->nullable()->change();
        });

        Schema::table('renewal_requests', function (Blueprint $table) {
            $table->text('dob')->nullable()->change();
            $table->text('spouse_name')->nullable()->change();
            $table->text('spouse_mobile')->nullable()->change();
            $table->text('spouse_dob')->nullable()->change();
            $table->text('emergency_name')->nullable()->change();
            $table->text('emergency_mobile')->nullable()->change();
            $table->text('house_details')->nullable()->change();
            $table->text('post_code')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Reverting to TEXT/DATE might lose data if it's already encrypted!
        // So we keep them as TEXT in down() or just leave it.
    }
};
