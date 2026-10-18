<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        // 1. Members
        if (Schema::hasTable('members')) {
            DB::statement("ALTER TABLE `members` MODIFY `title` TEXT NULL");
        }

        // 2. Member Children
        if (Schema::hasTable('member_children')) {
            DB::statement("ALTER TABLE `member_children` MODIFY `sex` TEXT NULL");
        }

        // 3. Renewal Requests
        if (Schema::hasTable('renewal_requests')) {
            DB::statement("ALTER TABLE `renewal_requests` MODIFY `title` TEXT NULL");
            DB::statement("ALTER TABLE `renewal_requests` MODIFY `marital_status` TEXT NULL");
        }

        // 4. Renewal Children
        if (Schema::hasTable('renewal_children')) {
            DB::statement("ALTER TABLE `renewal_children` MODIFY `sex` TEXT NULL");
        }

        // 5. Privacy Requests
        if (Schema::hasTable('privacy_requests')) {
            DB::statement("ALTER TABLE `privacy_requests` MODIFY `requester_name` TEXT NULL");
            DB::statement("ALTER TABLE `privacy_requests` MODIFY `requester_email` TEXT NULL");
            DB::statement("ALTER TABLE `privacy_requests` MODIFY `requester_phone` TEXT NULL");
        }
    }

    public function down(): void
    {
    }
};
