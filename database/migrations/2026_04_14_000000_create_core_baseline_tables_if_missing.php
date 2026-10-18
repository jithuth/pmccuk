<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Creates baseline core tables if they do not exist.
     * In existing production MySQL instances where these tables were imported directly,
     * this migration safely skips them without any modifications.
     */
    public function up(): void
    {
        if (!Schema::hasTable('admins')) {
            Schema::create('admins', function (Blueprint $table) {
                $table->id();
                $table->string('username', 60)->unique();
                $table->string('email', 255)->nullable();
                $table->string('password', 255);
                $table->string('role', 50)->default('admin');
                $table->timestamp('email_verified_at')->nullable();
            });
        }

        if (!Schema::hasTable('members')) {
            Schema::create('members', function (Blueprint $table) {
                $table->id();
                $table->string('guid', 64)->nullable()->index();
                $table->string('title', 20)->nullable();
                $table->text('full_name')->nullable();
                $table->text('email')->nullable();
                $table->text('mobile_number')->nullable();
                $table->text('photo')->nullable();
                $table->text('family_photo')->nullable();
                $table->text('dob')->nullable();
                $table->text('marital_status')->nullable();
                $table->text('spouse_name')->nullable();
                $table->text('spouse_mobile')->nullable();
                $table->text('spouse_dob')->nullable();
                $table->text('emergency_name')->nullable();
                $table->text('emergency_mobile')->nullable();
                $table->text('post_code')->nullable();
                $table->text('house_details')->nullable();
                $table->text('residing_area')->nullable();
                $table->text('membership_type')->nullable();
                $table->string('status', 50)->default('pending');
                $table->string('expiry_date', 50)->nullable();
                $table->text('membership_id_assigned')->nullable();
                $table->text('prev_membership_no')->nullable();
                $table->decimal('payment_amount', 10, 2)->nullable();
                $table->text('transaction_ref')->nullable();
                $table->text('payment_proof')->nullable();
                $table->string('payment_date', 50)->nullable();
                $table->text('bank_account_holder')->nullable();
                $table->boolean('consent_given')->default(false);
                $table->integer('children_below_18')->default(0);
                $table->integer('children_above_18')->default(0);
                $table->softDeletes();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('member_children')) {
            Schema::create('member_children', function (Blueprint $table) {
                $table->id();
                $table->foreignId('member_id')->constrained('members')->cascadeOnDelete();
                $table->text('child_name')->nullable();
                $table->string('child_gender', 20)->nullable();
                $table->text('child_dob')->nullable();
                $table->string('sex', 20)->nullable();
                $table->text('dob')->nullable();
                $table->integer('age')->nullable();
            });
        }

        if (!Schema::hasTable('events')) {
            Schema::create('events', function (Blueprint $table) {
                $table->id();
                $table->string('title', 255);
                $table->string('slug', 255)->nullable();
                $table->text('description')->nullable();
                $table->date('event_date')->nullable();
                $table->string('event_time', 50)->nullable();
                $table->string('location', 255)->nullable();
                $table->string('banner', 255)->nullable();
                $table->string('status', 50)->default('active');
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('event_bookings')) {
            Schema::create('event_bookings', function (Blueprint $table) {
                $table->id();
                $table->foreignId('event_id')->nullable()->constrained('events')->nullOnDelete();
                $table->string('membership_no', 60)->nullable();
                $table->text('full_name')->nullable();
                $table->text('email')->nullable();
                $table->text('phone')->nullable();
                $table->integer('adult_count')->default(0);
                $table->integer('child_count')->default(0);
                $table->integer('infant_count')->default(0);
                $table->text('attendee_breakdown')->nullable();
                $table->string('booking_status', 50)->default('pending');
                $table->decimal('total_amount', 10, 2)->default(0);
                $table->boolean('qr_code_sent')->default(false);
                $table->text('qr_code_data')->nullable();
                $table->string('check_in_status', 50)->nullable();
                $table->timestamp('check_in_at')->nullable();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('news')) {
            Schema::create('news', function (Blueprint $table) {
                $table->id();
                $table->string('title', 255);
                $table->text('content')->nullable();
                $table->string('image', 255)->nullable();
                $table->string('status', 50)->default('published');
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('settings')) {
            Schema::create('settings', function (Blueprint $table) {
                $table->id();
                $table->string('setting_key', 100)->unique();
                $table->text('setting_value')->nullable();
            });
        }

        if (!Schema::hasTable('renewal_requests')) {
            Schema::create('renewal_requests', function (Blueprint $table) {
                $table->id();
                $table->foreignId('member_id')->constrained('members')->cascadeOnDelete();
                $table->string('title', 20)->nullable();
                $table->text('full_name')->nullable();
                $table->text('email')->nullable();
                $table->text('mobile_number')->nullable();
                $table->text('dob')->nullable();
                $table->text('marital_status')->nullable();
                $table->text('membership_type')->nullable();
                $table->text('spouse_name')->nullable();
                $table->text('spouse_mobile')->nullable();
                $table->text('spouse_dob')->nullable();
                $table->text('post_code')->nullable();
                $table->text('house_details')->nullable();
                $table->text('emergency_name')->nullable();
                $table->text('emergency_mobile')->nullable();
                $table->text('photo')->nullable();
                $table->text('family_photo')->nullable();
                $table->decimal('payment_amount', 10, 2)->nullable();
                $table->text('transaction_ref')->nullable();
                $table->string('status', 50)->default('pending');
                $table->string('expiry_date', 50)->nullable();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('renewal_children')) {
            Schema::create('renewal_children', function (Blueprint $table) {
                $table->id();
                $table->foreignId('renewal_id')->constrained('renewal_requests')->cascadeOnDelete();
                $table->text('child_name')->nullable();
                $table->string('sex', 20)->nullable();
                $table->text('dob')->nullable();
            });
        }

        if (!Schema::hasTable('student_requests')) {
            Schema::create('student_requests', function (Blueprint $table) {
                $table->id();
                $table->text('full_name')->nullable();
                $table->text('email')->nullable();
                $table->text('phone')->nullable();
                $table->text('university')->nullable();
                $table->text('study_year')->nullable();
                $table->string('status', 50)->default('pending');
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('activity_logs')) {
            Schema::create('activity_logs', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('admin_id')->nullable();
                $table->string('admin_username', 100)->nullable();
                $table->string('user_type', 50)->default('admin');
                $table->text('action')->nullable();
                $table->text('details')->nullable();
                $table->string('ip_address', 45)->nullable();
                $table->text('user_agent')->nullable();
                $table->string('browser', 100)->nullable();
                $table->string('os', 100)->nullable();
                $table->string('device', 100)->nullable();
                $table->timestamp('created_at')->useCurrent();
            });
        }

        if (!Schema::hasTable('financial_transactions')) {
            Schema::create('financial_transactions', function (Blueprint $table) {
                $table->id();
                $table->string('type', 50);
                $table->string('category', 100)->nullable();
                $table->decimal('amount', 10, 2)->default(0);
                $table->text('description')->nullable();
                $table->text('ref_no')->nullable();
                $table->date('transaction_date')->nullable();
                $table->string('payment_method', 60)->nullable();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('payment_history')) {
            Schema::create('payment_history', function (Blueprint $table) {
                $table->id();
                $table->foreignId('member_id')->constrained('members')->cascadeOnDelete();
                $table->string('payment_type', 60)->nullable();
                $table->decimal('amount', 10, 2)->default(0);
                $table->text('transaction_ref')->nullable();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('messages')) {
            Schema::create('messages', function (Blueprint $table) {
                $table->id();
                $table->string('name', 150);
                $table->string('email', 150);
                $table->string('phone', 50)->nullable();
                $table->string('subject', 200)->nullable();
                $table->text('message');
                $table->string('status', 30)->default('unread');
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        // Non-destructive: do not drop core tables
    }
};
