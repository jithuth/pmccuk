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
        // 1. Central Auth Audit Events
        if (!Schema::hasTable('auth_events')) {
            Schema::create('auth_events', function (Blueprint $table) {
                $table->id();
                $table->uuid('event_uuid')->unique();
                $table->string('correlation_id', 64)->nullable()->index();
                $table->string('actor_type', 40)->default('guest')->index(); // member, admin, guest, system
                $table->unsignedBigInteger('actor_id')->nullable()->index();
                $table->string('actor_identifier', 150)->nullable()->index();
                $table->string('guard', 40)->nullable()->index();
                $table->string('event_category', 60)->index(); // otp, mfa, login, session, device, threat, admin_security
                $table->string('action', 100)->index();
                $table->string('status', 30)->default('success')->index(); // success, failed, blocked, warning
                $table->string('reason_code', 80)->nullable()->index();
                $table->string('ip_address', 45)->nullable()->index();
                $table->text('user_agent')->nullable();
                $table->string('device_hash', 64)->nullable()->index();
                $table->string('device_name', 150)->nullable();
                $table->string('browser', 80)->nullable();
                $table->string('os', 80)->nullable();
                $table->string('location_summary', 150)->nullable();
                $table->unsignedBigInteger('otp_challenge_id')->nullable()->index();
                $table->string('session_id_ref', 64)->nullable()->index();
                $table->integer('risk_score')->default(0);
                $table->json('risk_indicators')->nullable();
                $table->json('metadata')->nullable();
                $table->timestamp('created_at')->useCurrent()->index();
            });
        }

        // 2. OTP Challenges
        if (!Schema::hasTable('otp_challenges')) {
            Schema::create('otp_challenges', function (Blueprint $table) {
                $table->id();
                $table->uuid('challenge_uuid')->unique();
                $table->string('identifier', 150)->index();
                $table->unsignedBigInteger('member_id')->nullable()->index();
                $table->string('otp_hash', 255);
                $table->string('purpose', 60)->default('member_login');
                $table->string('channel', 40)->default('email'); // email, whatsapp, both
                $table->string('delivery_status', 40)->default('pending')->index(); // pending, sent, confirmed, failed
                $table->json('email_delivery_evidence')->nullable();
                $table->json('whatsapp_delivery_evidence')->nullable();
                $table->integer('attempts')->default(0);
                $table->integer('max_attempts')->default(5);
                $table->timestamp('resend_available_at')->nullable();
                $table->timestamp('expires_at')->index();
                $table->timestamp('verified_at')->nullable()->index();
                $table->timestamp('consumed_at')->nullable()->index();
                $table->string('ip_address', 45)->nullable()->index();
                $table->text('user_agent')->nullable();
                $table->string('correlation_id', 64)->nullable()->index();
                $table->timestamps();
            });
        }

        // 3. User Devices
        if (!Schema::hasTable('user_devices')) {
            Schema::create('user_devices', function (Blueprint $table) {
                $table->id();
                $table->string('user_type', 40)->index(); // member, admin
                $table->unsignedBigInteger('user_id')->index();
                $table->string('device_hash', 64)->index();
                $table->string('device_name', 150)->nullable();
                $table->string('browser', 80)->nullable();
                $table->string('os', 80)->nullable();
                $table->string('ip_address', 45)->nullable();
                $table->string('location', 150)->nullable();
                $table->boolean('is_trusted')->default(false);
                $table->timestamp('first_seen_at')->useCurrent();
                $table->timestamp('last_seen_at')->useCurrent();
                $table->timestamps();

                $table->unique(['user_type', 'user_id', 'device_hash']);
            });
        }

        // 4. User Active Sessions
        if (!Schema::hasTable('user_sessions')) {
            Schema::create('user_sessions', function (Blueprint $table) {
                $table->id();
                $table->string('session_id_hash', 64)->unique();
                $table->string('user_type', 40)->index(); // member, admin
                $table->unsignedBigInteger('user_id')->index();
                $table->string('guard', 40);
                $table->unsignedBigInteger('device_id')->nullable();
                $table->string('ip_address', 45)->nullable();
                $table->text('user_agent')->nullable();
                $table->timestamp('last_activity')->useCurrent()->index();
                $table->boolean('is_active')->default(true)->index();
                $table->timestamp('revoked_at')->nullable();
                $table->string('revoked_by', 100)->nullable();
                $table->timestamps();
            });
        }

        // 5. Security Policies
        if (!Schema::hasTable('security_policies')) {
            Schema::create('security_policies', function (Blueprint $table) {
                $table->id();
                $table->string('key', 100)->unique();
                $table->string('name', 150);
                $table->text('value');
                $table->string('type', 30)->default('string'); // integer, boolean, string, json
                $table->text('description')->nullable();
                $table->unsignedBigInteger('updated_by')->nullable();
                $table->timestamps();
            });
        }

        // 6. Security Alerts
        if (!Schema::hasTable('security_alerts')) {
            Schema::create('security_alerts', function (Blueprint $table) {
                $table->id();
                $table->string('alert_type', 80)->index();
                $table->string('severity', 30)->default('medium')->index(); // low, medium, high, critical
                $table->string('title', 200);
                $table->text('details')->nullable();
                $table->string('ip_address', 45)->nullable()->index();
                $table->string('user_type', 40)->nullable()->index();
                $table->unsignedBigInteger('user_id')->nullable()->index();
                $table->boolean('is_resolved')->default(false)->index();
                $table->timestamp('resolved_at')->nullable();
                $table->unsignedBigInteger('resolved_by')->nullable();
                $table->text('resolution_notes')->nullable();
                $table->timestamps();
            });
        }

        // 7. Add MFA columns to members
        if (Schema::hasTable('members')) {
            Schema::table('members', function (Blueprint $table) {
                if (!Schema::hasColumn('members', 'two_factor_secret')) {
                    $table->text('two_factor_secret')->nullable()->after('remember_token');
                }
                if (!Schema::hasColumn('members', 'two_factor_enabled')) {
                    $table->boolean('two_factor_enabled')->default(false)->after('two_factor_secret');
                }
                if (!Schema::hasColumn('members', 'two_factor_recovery_codes')) {
                    $table->text('two_factor_recovery_codes')->nullable()->after('two_factor_enabled');
                }
                if (!Schema::hasColumn('members', 'two_factor_confirmed_at')) {
                    $table->timestamp('two_factor_confirmed_at')->nullable()->after('two_factor_recovery_codes');
                }
            });
        }

        // 8. Add recovery codes and confirmed timestamp to admins (two_factor_secret/two_factor_enabled already exist)
        if (Schema::hasTable('admins')) {
            Schema::table('admins', function (Blueprint $table) {
                if (!Schema::hasColumn('admins', 'two_factor_recovery_codes')) {
                    $table->text('two_factor_recovery_codes')->nullable()->after('two_factor_enabled');
                }
                if (!Schema::hasColumn('admins', 'two_factor_confirmed_at')) {
                    $table->timestamp('two_factor_confirmed_at')->nullable()->after('two_factor_recovery_codes');
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('admins')) {
            Schema::table('admins', function (Blueprint $table) {
                $cols = [];
                if (Schema::hasColumn('admins', 'two_factor_recovery_codes')) $cols[] = 'two_factor_recovery_codes';
                if (Schema::hasColumn('admins', 'two_factor_confirmed_at')) $cols[] = 'two_factor_confirmed_at';
                if (!empty($cols)) $table->dropColumn($cols);
            });
        }

        if (Schema::hasTable('members')) {
            Schema::table('members', function (Blueprint $table) {
                $cols = [];
                if (Schema::hasColumn('members', 'two_factor_secret')) $cols[] = 'two_factor_secret';
                if (Schema::hasColumn('members', 'two_factor_enabled')) $cols[] = 'two_factor_enabled';
                if (Schema::hasColumn('members', 'two_factor_recovery_codes')) $cols[] = 'two_factor_recovery_codes';
                if (Schema::hasColumn('members', 'two_factor_confirmed_at')) $cols[] = 'two_factor_confirmed_at';
                if (!empty($cols)) $table->dropColumn($cols);
            });
        }

        Schema::dropIfExists('security_alerts');
        Schema::dropIfExists('security_policies');
        Schema::dropIfExists('user_sessions');
        Schema::dropIfExists('user_devices');
        Schema::dropIfExists('otp_challenges');
        Schema::dropIfExists('auth_events');
    }
};
