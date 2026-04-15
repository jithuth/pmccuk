<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Admins
        Schema::table('admins', function (Blueprint $table) {
            $table->text('email')->nullable()->change();
        });

        // 2. Members (Full PII)
        Schema::table('members', function (Blueprint $table) {
            $table->text('full_name')->nullable()->change();
            $table->text('email')->nullable()->change();
            $table->text('mobile_number')->nullable()->change();
            $table->text('photo')->nullable()->change();
            $table->text('family_photo')->nullable()->change();
            $table->text('prev_membership_no')->nullable()->change();
            $table->text('membership_id_assigned')->nullable()->change();
            $table->text('transaction_ref')->nullable()->change();
            $table->text('payment_proof')->nullable()->change();
            $table->text('bank_account_holder')->nullable()->change();
        });

        // 3. Member Children
        Schema::table('member_children', function (Blueprint $table) {
            $table->text('child_name')->nullable()->change();
            $table->text('dob')->nullable()->change();
        });

        // 4. Renewal Requests
        Schema::table('renewal_requests', function (Blueprint $table) {
            $table->text('full_name')->nullable()->change();
            $table->text('email')->nullable()->change();
            $table->text('mobile_number')->nullable()->change();
            $table->text('photo')->nullable()->change();
            $table->text('family_photo')->nullable()->change();
        });

        // 5. Renewal Children
        Schema::table('renewal_children', function (Blueprint $table) {
            $table->text('child_name')->nullable()->change();
            $table->text('dob')->nullable()->change();
        });

        // 6. Event Bookings
        Schema::table('event_bookings', function (Blueprint $table) {
            $table->text('full_name')->nullable()->change();
            $table->text('email')->nullable()->change();
            $table->text('phone')->nullable()->change();
        });

        // 7. Financials
        if (Schema::hasTable('payment_history')) {
            Schema::table('payment_history', function (Blueprint $table) {
                $table->text('transaction_ref')->nullable()->change();
            });
        }

        if (Schema::hasTable('financial_transactions')) {
            Schema::table('financial_transactions', function (Blueprint $table) {
                $table->text('description')->nullable()->change();
                $table->text('ref_no')->nullable()->change();
            });
        }

        // 8. Settings
        Schema::table('settings', function (Blueprint $table) {
            $table->text('setting_value')->nullable()->change();
        });
    }

    public function down(): void { }
};
