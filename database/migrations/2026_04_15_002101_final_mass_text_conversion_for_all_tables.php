<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Team Members
        if (Schema::hasTable('team_members')) {
            Schema::table('team_members', function (Blueprint $table) {
                $table->text('name')->nullable()->change();
                $table->text('role')->nullable()->change();
            });
        }

        // 2. Sponsor Offers
        if (Schema::hasTable('sponsor_offers')) {
            Schema::table('sponsor_offers', function (Blueprint $table) {
                $table->text('sponsor_name')->nullable()->change();
                $table->text('title')->nullable()->change();
                $table->text('description')->nullable()->change();
            });
        }

        // 3. News
        if (Schema::hasTable('news')) {
            Schema::table('news', function (Blueprint $table) {
                $table->text('title')->nullable()->change();
                $table->text('content')->nullable()->change();
                $table->text('image_url')->nullable()->change();
            });
        }

        // 4. Activity Logs
        if (Schema::hasTable('activity_logs')) {
            Schema::table('activity_logs', function (Blueprint $table) {
                $table->text('action')->nullable()->change();
                $table->text('details')->nullable()->change();
            });
        }

        // 5. Contact Messages
        if (Schema::hasTable('messages')) {
            Schema::table('messages', function (Blueprint $table) {
                $table->text('name')->nullable()->change();
                $table->text('email')->nullable()->change();
                $table->text('subject')->nullable()->change();
                $table->text('message')->nullable()->change();
            });
        }

        // 6. Student Requests
        if (Schema::hasTable('student_requests')) {
            Schema::table('student_requests', function (Blueprint $table) {
                $table->text('parent_name')->nullable()->change();
                $table->text('student_name')->nullable()->change();
                $table->text('dob')->nullable()->change();
                $table->text('school')->nullable()->change();
                $table->text('grade')->nullable()->change();
            });
        }
    }

    public function down(): void { }
};
