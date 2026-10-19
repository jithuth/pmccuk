<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\SecurityPolicy;

class SecurityPolicySeeder extends Seeder
{
    public function run(): void
    {
        $defaults = [
            [
                'key' => 'otp_expiry_minutes',
                'name' => 'OTP Code Validity Window',
                'value' => '5',
                'type' => 'integer',
                'description' => 'Expiration timeframe in minutes for one-time verification passcodes.'
            ],
            [
                'key' => 'otp_max_attempts',
                'name' => 'Maximum OTP Verification Attempts',
                'value' => '5',
                'type' => 'integer',
                'description' => 'Maximum invalid verification attempts allowed before invalidating the OTP challenge.'
            ],
            [
                'key' => 'otp_resend_cooldown_seconds',
                'name' => 'OTP Resend Cooldown',
                'value' => '60',
                'type' => 'integer',
                'description' => 'Cooldown interval in seconds before a member can request another OTP.'
            ],
            [
                'key' => 'otp_ip_hourly_limit',
                'name' => 'Hourly OTP Requests per IP',
                'value' => '15',
                'type' => 'integer',
                'description' => 'Maximum OTP dispatch requests allowed from a single IP address per hour.'
            ],
            [
                'key' => 'otp_member_hourly_limit',
                'name' => 'Hourly OTP Requests per Member',
                'value' => '5',
                'type' => 'integer',
                'description' => 'Maximum OTP generation requests allowed for a specific member identity per hour.'
            ],
            [
                'key' => 'mfa_max_attempts',
                'name' => 'Maximum MFA Attempts',
                'value' => '5',
                'type' => 'integer',
                'description' => 'Maximum consecutive failed TOTP attempts before a temporary security lockout.'
            ],
            [
                'key' => 'mfa_lockout_minutes',
                'name' => 'MFA Verification Lockout Duration',
                'value' => '15',
                'type' => 'integer',
                'description' => 'Lockout window in minutes after exceeding consecutive MFA failures.'
            ],
            [
                'key' => 'admin_mandatory_mfa',
                'name' => 'Privileged Administrator Mandatory MFA',
                'value' => '1',
                'type' => 'boolean',
                'description' => 'Enforce mandatory two-factor authentication setup for all administrative logins.'
            ],
            [
                'key' => 'session_lifetime_minutes',
                'name' => 'Inactivity Session Lifetime',
                'value' => '120',
                'type' => 'integer',
                'description' => 'Duration of inactivity before an authenticated session is terminated.'
            ],
            [
                'key' => 'max_active_sessions_per_user',
                'name' => 'Max Concurrent Sessions per Member',
                'value' => '5',
                'type' => 'integer',
                'description' => 'Maximum concurrent active sessions before older sessions are automatically retired.'
            ],
            [
                'key' => 'threat_ip_otp_burst_threshold',
                'name' => 'Suspicious IP OTP Burst Threshold',
                'value' => '6',
                'type' => 'integer',
                'description' => 'Failed OTP attempts from one IP triggering high-priority threat alert.'
            ],
            [
                'key' => 'threat_ip_distinct_members_threshold',
                'name' => 'Credential Stuffing Member ID Threshold',
                'value' => '3',
                'type' => 'integer',
                'description' => 'Distinct member IDs targeted from one IP in 10 minutes triggering incident warning.'
            ],
            [
                'key' => 'notify_new_device',
                'name' => 'Security Alert on Unrecognized Device',
                'value' => '1',
                'type' => 'boolean',
                'description' => 'Send security notification when an account signs in from a newly observed device.'
            ],
            [
                'key' => 'notify_mfa_change',
                'name' => 'Security Alert on MFA Status Changes',
                'value' => '1',
                'type' => 'boolean',
                'description' => 'Send notification when MFA is enabled, disabled, or recovery codes regenerated.'
            ],
        ];

        foreach ($defaults as $policy) {
            SecurityPolicy::firstOrCreate(
                ['key' => $policy['key']],
                $policy
            );
        }
    }
}
