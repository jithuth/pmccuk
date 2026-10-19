<?php

namespace App\Services\Auth;

use App\Models\AuthEvent;
use App\Models\SecurityAlert;
use App\Models\SecurityPolicy;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class ThreatDetectionService
{
    /**
     * Inspect OTP request for automated threat indicators
     */
    public static function inspectOtpRequest(string $identifier, ?Request $request = null): array
    {
        $req = $request ?: request();
        $ip = $req->ip();
        $riskScore = 0;
        $indicators = [];
        $isBlocked = false;
        $blockReason = null;

        // 1. IP Burst Check: count OTP requests from this IP in last 10 minutes
        $ipKey = "threat_otp_req_ip_" . md5($ip);
        $recentIpRequests = (int) Cache::get($ipKey, 0);
        $burstThreshold = SecurityPolicy::get('threat_ip_otp_burst_threshold', 6);

        if ($recentIpRequests >= $burstThreshold) {
            $riskScore += 45;
            $indicators[] = "ip_otp_burst_{$recentIpRequests}_attempts";

            self::recordAlert(
                alertType: 'ip_otp_burst',
                severity: 'medium',
                title: "Repeated OTP Requests from IP {$ip}",
                details: "Detected {$recentIpRequests} OTP requests in 10 minutes from IP {$ip}.",
                ip: $ip
            );
        }

        // 2. Credential Stuffing / Enumeration: distinct identifiers targeted by this IP
        $idTrackerKey = "threat_ip_distinct_ids_" . md5($ip);
        $targetedIds = Cache::get($idTrackerKey, []);
        if (!in_array(strtolower($identifier), $targetedIds)) {
            $targetedIds[] = strtolower($identifier);
            Cache::put($idTrackerKey, $targetedIds, 600); // 10 minutes window
        }

        $enumThreshold = SecurityPolicy::get('threat_ip_distinct_members_threshold', 3);
        if (count($targetedIds) >= $enumThreshold) {
            $riskScore += 60;
            $indicators[] = "credential_stuffing_detected_" . count($targetedIds) . "_identifiers";

            self::recordAlert(
                alertType: 'credential_stuffing',
                severity: 'high',
                title: "Multiple Member IDs Targeted from IP {$ip}",
                details: "IP {$ip} targeted " . count($targetedIds) . " distinct member identifiers in 10 minutes.",
                ip: $ip
            );
        }

        // 3. Hourly IP limit check
        $hourlyIpKey = "otp_ip_hourly_" . md5($ip);
        $hourlyCount = (int) Cache::get($hourlyIpKey, 0);
        $hourlyIpLimit = SecurityPolicy::get('otp_ip_hourly_limit', 15);

        if ($hourlyCount >= $hourlyIpLimit) {
            $isBlocked = true;
            $blockReason = 'rate_limit_exceeded';
            $riskScore += 50;
            $indicators[] = 'hourly_ip_rate_limit_reached';
        }

        // Increment trackers
        Cache::put($ipKey, $recentIpRequests + 1, 600);
        Cache::put($hourlyIpKey, $hourlyCount + 1, 3600);

        return [
            'is_blocked' => $isBlocked,
            'block_reason' => $blockReason,
            'risk_score' => min(100, $riskScore),
            'risk_indicators' => $indicators,
        ];
    }

    /**
     * Inspect failed OTP verification attempt
     */
    public static function inspectFailedOtp(int $challengeId, string $identifier, ?Request $request = null): array
    {
        $req = $request ?: request();
        $ip = $req->ip();
        $failKey = "threat_otp_fail_ip_" . md5($ip);
        $fails = (int) Cache::get($failKey, 0) + 1;
        Cache::put($failKey, $fails, 900); // 15 mins

        $riskScore = min(100, $fails * 20);
        $indicators = ["consecutive_invalid_otp_{$fails}"];

        if ($fails >= 5) {
            self::recordAlert(
                alertType: 'repeated_invalid_otp',
                severity: 'medium',
                title: "Repeated Invalid OTP Submissions from IP {$ip}",
                details: "Observed {$fails} failed OTP submissions within 15 minutes targeting '{$identifier}'.",
                ip: $ip
            );
        }

        return [
            'risk_score' => $riskScore,
            'risk_indicators' => $indicators
        ];
    }

    /**
     * Inspect failed MFA attempt
     */
    public static function inspectFailedMfa(string $userType, int $userId, ?Request $request = null): array
    {
        $req = $request ?: request();
        $ip = $req->ip();
        $userKey = "threat_mfa_fail_user_{$userType}_{$userId}";
        $userFails = (int) Cache::get($userKey, 0) + 1;
        $maxAttempts = SecurityPolicy::get('mfa_max_attempts', 5);
        $lockoutMinutes = SecurityPolicy::get('mfa_lockout_minutes', 15);

        Cache::put($userKey, $userFails, $lockoutMinutes * 60);

        $isLocked = false;
        if ($userFails >= $maxAttempts) {
            $isLocked = true;
            self::recordAlert(
                alertType: 'repeated_invalid_mfa',
                severity: 'high',
                title: "Repeated Invalid MFA Attempts on {$userType} #{$userId}",
                details: "Account locked out from MFA verification for {$lockoutMinutes} minutes following {$userFails} consecutive failures from IP {$ip}.",
                ip: $ip,
                userType: $userType,
                userId: $userId
            );
        }

        return [
            'is_locked' => $isLocked,
            'lockout_minutes' => $lockoutMinutes,
            'consecutive_failures' => $userFails,
            'risk_score' => min(100, $userFails * 25),
            'risk_indicators' => ["invalid_mfa_attempts_{$userFails}"]
        ];
    }

    /**
     * Clear MFA failure counter after successful verification
     */
    public static function clearMfaFailures(string $userType, int $userId): void
    {
        Cache::forget("threat_mfa_fail_user_{$userType}_{$userId}");
    }

    /**
     * Check if user is currently under MFA lockout
     */
    public static function isMfaLockedOut(string $userType, int $userId): bool
    {
        $userKey = "threat_mfa_fail_user_{$userType}_{$userId}";
        $fails = (int) Cache::get($userKey, 0);
        $maxAttempts = SecurityPolicy::get('mfa_max_attempts', 5);

        return $fails >= $maxAttempts;
    }

    /**
     * Record a deduplicated security alert
     */
    protected static function recordAlert(
        string $alertType,
        string $severity,
        string $title,
        string $details,
        ?string $ip = null,
        ?string $userType = null,
        ?int $userId = null
    ): void {
        // Debounce alert creation per alert_type + ip within 10 minutes
        $dedupeKey = "alert_debounce_" . md5("{$alertType}_{$ip}_{$userType}_{$userId}");
        if (Cache::has($dedupeKey)) {
            return;
        }
        Cache::put($dedupeKey, true, 600);

        try {
            SecurityAlert::create([
                'alert_type' => $alertType,
                'severity' => $severity,
                'title' => $title,
                'details' => $details,
                'ip_address' => $ip,
                'user_type' => $userType,
                'user_id' => $userId,
                'is_resolved' => false,
            ]);
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error('ThreatDetectionService alert creation error: ' . $e->getMessage());
        }
    }
}
