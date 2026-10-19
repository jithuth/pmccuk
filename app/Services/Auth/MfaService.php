<?php

namespace App\Services\Auth;

use App\Models\Member;
use App\Models\Admin;
use App\Models\SecurityPolicy;
use App\Models\SecurityAlert;
use PragmaRX\Google2FA\Google2FA;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class MfaService
{
    protected Google2FA $google2fa;

    public function __construct()
    {
        $this->google2fa = new Google2FA();
    }

    /**
     * Initiate MFA Enrollment for Member or Admin
     */
    public function initiateEnrollment(object $user, string $userType): array
    {
        $secret = $this->google2fa->generateSecretKey();
        
        $holderName = $userType === 'admin' ? ($user->username ?? 'Admin') : ($user->full_name ?? 'Member');
        $label = $userType === 'admin' 
            ? "PMCC-Admin:{$holderName}" 
            : "PMCC-Member:{$holderName}";

        $otpAuthUrl = $this->google2fa->getQRCodeUrl(
            config('app.name', 'PMCC-UK'),
            $label,
            $secret
        );

        // Store pending enrollment secret securely in session
        session(["mfa_enrollment_{$userType}_{$user->id}" => encrypt($secret)]);

        // QR Code URL using QRServer API (fallback-safe and no GD/Imagick dependencies)
        $qrImageUrl = 'https://api.qrserver.com/v1/create-qr-code/?size=240x240&data=' . urlencode($otpAuthUrl);

        AuthAuditService::log(
            eventCategory: 'mfa',
            action: 'mfa_enroll_initiated',
            status: 'success',
            actorType: $userType,
            actorId: $user->id,
            guard: $userType === 'admin' ? 'admin' : 'member'
        );

        return [
            'secret' => $secret,
            'qr_image_url' => $qrImageUrl,
            'otp_auth_url' => $otpAuthUrl,
        ];
    }

    /**
     * Confirm MFA Enrollment by verifying the first TOTP code
     */
    public function confirmEnrollment(object $user, string $userType, string $totpCode, ?Request $request = null): array
    {
        $req = $request ?: request();
        $sessionKey = "mfa_enrollment_{$userType}_{$user->id}";
        $encryptedSecret = session($sessionKey);

        if (!$encryptedSecret) {
            return [
                'success' => false,
                'message' => 'Enrollment session has expired. Please initiate setup again.',
                'status_code' => 422
            ];
        }

        $secret = $this->getDecryptedSecret($encryptedSecret);
        $valid = $this->google2fa->verifyKey($secret, $totpCode);

        if (!$valid) {
            AuthAuditService::log(
                eventCategory: 'mfa',
                action: 'mfa_enroll_failed',
                status: 'failed',
                reasonCode: 'invalid_confirmation_totp',
                actorType: $userType,
                actorId: $user->id,
                guard: $userType === 'admin' ? 'admin' : 'member',
                request: $req
            );

            return [
                'success' => false,
                'message' => 'The verification code does not match. Please verify your authenticator app time and try again.',
                'status_code' => 422
            ];
        }

        // Generate 8 single-use cryptographically random recovery codes (e.g. ABCD-EFGH)
        $plainRecoveryCodes = [];
        $hashedRecoveryCodes = [];

        for ($i = 0; $i < 8; $i++) {
            $code = strtoupper(Str::random(4) . '-' . Str::random(4));
            $plainRecoveryCodes[] = $code;
            $hashedRecoveryCodes[] = [
                'hash' => Hash::make($code),
                'used' => false,
                'used_at' => null
            ];
        }

        // Save MFA setup on model
        $user->two_factor_secret = encrypt($secret);
        $user->two_factor_enabled = true;
        $user->two_factor_recovery_codes = json_encode($hashedRecoveryCodes);
        $user->two_factor_confirmed_at = now();
        $user->save();

        session()->forget($sessionKey);

        AuthAuditService::log(
            eventCategory: 'mfa',
            action: 'mfa_enrolled',
            status: 'success',
            actorType: $userType,
            actorId: $user->id,
            guard: $userType === 'admin' ? 'admin' : 'member',
            request: $req
        );

        if (SecurityPolicy::get('notify_mfa_change', true)) {
            SecurityAlert::create([
                'alert_type' => 'mfa_enabled',
                'severity' => 'low',
                'title' => "Two-Factor Authentication Enabled ({$userType} #{$user->id})",
                'details' => "MFA was successfully verified and enabled on this account from IP {$req->ip()}.",
                'ip_address' => $req->ip(),
                'user_type' => $userType,
                'user_id' => $user->id,
            ]);
        }

        return [
            'success' => true,
            'message' => 'Two-Factor Authentication has been successfully enabled on your account.',
            'recovery_codes' => $plainRecoveryCodes // Shown ONLY ONCE to user upon enrollment!
        ];
    }

    /**
     * Verify a TOTP code during login challenge (with replay prevention & throttling)
     */
    public function verifyChallenge(object $user, string $userType, string $submittedCode, ?Request $request = null): array
    {
        $req = $request ?: request();

        // 1. Check Lockout Status
        if (ThreatDetectionService::isMfaLockedOut($userType, $user->id)) {
            AuthAuditService::log(
                eventCategory: 'mfa',
                action: 'mfa_verify_blocked',
                status: 'blocked',
                reasonCode: 'mfa_lockout_active',
                actorType: $userType,
                actorId: $user->id,
                guard: $userType === 'admin' ? 'admin' : 'member',
                riskScore: 80,
                riskIndicators: ['mfa_lockout_active'],
                request: $req
            );

            $lockoutMinutes = SecurityPolicy::get('mfa_lockout_minutes', 15);
            return [
                'success' => false,
                'message' => "Too many invalid attempts. MFA verification is temporarily locked for {$lockoutMinutes} minutes.",
                'status_code' => 429
            ];
        }

        // 2. Check if user is using a recovery code (format: XXXX-XXXX or 8-10 chars)
        $cleanInput = trim($submittedCode);
        if (str_contains($cleanInput, '-') || strlen($cleanInput) > 6) {
            return $this->verifyRecoveryCode($user, $userType, $cleanInput, $req);
        }

        // 3. Replay Prevention: ensure code has not been used in current 60s window
        $replayKey = "mfa_used_{$userType}_{$user->id}_{$cleanInput}";
        if (Cache::has($replayKey)) {
            AuthAuditService::log(
                eventCategory: 'mfa',
                action: 'mfa_replay_blocked',
                status: 'blocked',
                reasonCode: 'totp_replay_attempt',
                actorType: $userType,
                actorId: $user->id,
                guard: $userType === 'admin' ? 'admin' : 'member',
                riskScore: 75,
                riskIndicators: ['totp_code_reuse'],
                request: $req
            );

            return [
                'success' => false,
                'message' => 'This authenticator code has already been used. Please wait for the next code.',
                'status_code' => 422
            ];
        }

        // 4. Verify TOTP Code
        $secret = $this->getDecryptedSecret($user->two_factor_secret);
        // Allow timestamp window of 1 interval (+- 30 seconds clock drift tolerance)
        $valid = $this->google2fa->verifyKey($secret, $cleanInput, 1);

        // Emergency code fallback for admin if cached
        if (!$valid && $userType === 'admin') {
            $emergencyCode = Cache::get("admin_emergency_2fa_{$user->id}");
            if ($emergencyCode && (string)$cleanInput === (string)$emergencyCode) {
                $valid = true;
                Cache::forget("admin_emergency_2fa_{$user->id}");
            }
        }

        if (!$valid) {
            $threat = ThreatDetectionService::inspectFailedMfa($userType, $user->id, $req);

            AuthAuditService::log(
                eventCategory: 'mfa',
                action: 'mfa_verify_failed',
                status: 'failed',
                reasonCode: 'invalid_totp_code',
                actorType: $userType,
                actorId: $user->id,
                guard: $userType === 'admin' ? 'admin' : 'member',
                riskScore: $threat['risk_score'],
                riskIndicators: $threat['risk_indicators'],
                request: $req
            );

            $msg = $threat['is_locked']
                ? "Too many failed attempts. MFA verification locked for {$threat['lockout_minutes']} minutes."
                : 'Invalid verification code. Please try again.';

            return [
                'success' => false,
                'message' => $msg,
                'status_code' => $threat['is_locked'] ? 429 : 422
            ];
        }

        // Mark code as used for 60 seconds (Replay Protection)
        Cache::put($replayKey, true, 60);
        ThreatDetectionService::clearMfaFailures($userType, $user->id);

        AuthAuditService::log(
            eventCategory: 'mfa',
            action: 'mfa_verified',
            status: 'success',
            actorType: $userType,
            actorId: $user->id,
            guard: $userType === 'admin' ? 'admin' : 'member',
            request: $req
        );

        return [
            'success' => true,
            'message' => 'Two-Factor Authentication verified successfully.'
        ];
    }

    /**
     * Verify and atomically consume a single-use recovery code
     */
    protected function verifyRecoveryCode(object $user, string $userType, string $submittedRecoveryCode, Request $req): array
    {
        $rawStored = $user->two_factor_recovery_codes;
        if (empty($rawStored)) {
            return [
                'success' => false,
                'message' => 'No recovery codes are configured on this account.',
                'status_code' => 422
            ];
        }

        $codes = json_decode($rawStored, true);
        if (!is_array($codes)) {
            return [
                'success' => false,
                'message' => 'Recovery code storage corrupted. Contact administration.',
                'status_code' => 500
            ];
        }

        $matchedIndex = null;
        $cleanSubmitted = strtoupper(trim($submittedRecoveryCode));

        foreach ($codes as $idx => $item) {
            if (!empty($item['used'])) {
                continue;
            }
            if (Hash::check($cleanSubmitted, $item['hash'])) {
                $matchedIndex = $idx;
                break;
            }
        }

        if ($matchedIndex === null) {
            $threat = ThreatDetectionService::inspectFailedMfa($userType, $user->id, $req);

            AuthAuditService::log(
                eventCategory: 'mfa',
                action: 'recovery_code_failed',
                status: 'failed',
                reasonCode: 'invalid_recovery_code',
                actorType: $userType,
                actorId: $user->id,
                guard: $userType === 'admin' ? 'admin' : 'member',
                riskScore: $threat['risk_score'],
                riskIndicators: $threat['risk_indicators'],
                request: $req
            );

            return [
                'success' => false,
                'message' => 'The recovery code provided is invalid or has already been used.',
                'status_code' => 422
            ];
        }

        // Consume recovery code (single use)
        $codes[$matchedIndex]['used'] = true;
        $codes[$matchedIndex]['used_at'] = now()->toIso8601String();
        $user->two_factor_recovery_codes = json_encode($codes);
        $user->save();

        ThreatDetectionService::clearMfaFailures($userType, $user->id);

        $remainingCodes = count(array_filter($codes, fn($c) => empty($c['used'])));

        AuthAuditService::log(
            eventCategory: 'mfa',
            action: 'recovery_code_used',
            status: 'success',
            actorType: $userType,
            actorId: $user->id,
            guard: $userType === 'admin' ? 'admin' : 'member',
            metadata: ['remaining_recovery_codes' => $remainingCodes],
            request: $req
        );

        if (SecurityPolicy::get('notify_mfa_change', true)) {
            SecurityAlert::create([
                'alert_type' => 'recovery_code_used',
                'severity' => 'medium',
                'title' => "MFA Recovery Code Used ({$userType} #{$user->id})",
                'details' => "A single-use recovery code was consumed to sign in from IP {$req->ip()}. Remaining codes: {$remainingCodes}.",
                'ip_address' => $req->ip(),
                'user_type' => $userType,
                'user_id' => $user->id,
            ]);
        }

        return [
            'success' => true,
            'message' => "Signed in using a recovery code. {$remainingCodes} recovery codes remain available.",
            'remaining_codes' => $remainingCodes
        ];
    }

    /**
     * Disable MFA after re-verifying current password / TOTP
     */
    public function disableMfa(object $user, string $userType, string $verificationCode, ?Request $request = null): array
    {
        $req = $request ?: request();

        // Must verify a valid current TOTP code before disabling
        $secret = $this->getDecryptedSecret($user->two_factor_secret);
        $valid = $this->google2fa->verifyKey($secret, trim($verificationCode));

        if (!$valid) {
            AuthAuditService::log(
                eventCategory: 'mfa',
                action: 'mfa_disable_failed',
                status: 'failed',
                reasonCode: 'invalid_reverification_code',
                actorType: $userType,
                actorId: $user->id,
                guard: $userType === 'admin' ? 'admin' : 'member',
                request: $req
            );

            return [
                'success' => false,
                'message' => 'Invalid authenticator code. To disable MFA, you must enter a valid code from your authenticator app.',
                'status_code' => 422
            ];
        }

        $user->two_factor_enabled = false;
        $user->two_factor_secret = null;
        $user->two_factor_recovery_codes = null;
        $user->two_factor_confirmed_at = null;
        $user->save();

        AuthAuditService::log(
            eventCategory: 'mfa',
            action: 'mfa_disabled',
            status: 'warning',
            reasonCode: 'user_disabled_mfa',
            actorType: $userType,
            actorId: $user->id,
            guard: $userType === 'admin' ? 'admin' : 'member',
            request: $req
        );

        if (SecurityPolicy::get('notify_mfa_change', true)) {
            SecurityAlert::create([
                'alert_type' => 'mfa_disabled',
                'severity' => 'high',
                'title' => "Two-Factor Authentication Disabled ({$userType} #{$user->id})",
                'details' => "MFA was disabled for this account from IP {$req->ip()}.",
                'ip_address' => $req->ip(),
                'user_type' => $userType,
                'user_id' => $user->id,
            ]);
        }

        return [
            'success' => true,
            'message' => 'Two-Factor Authentication has been successfully disabled.'
        ];
    }

    /**
     * Regenerate new recovery codes
     */
    public function regenerateRecoveryCodes(object $user, string $userType, string $totpCode, ?Request $request = null): array
    {
        $req = $request ?: request();

        if (!$user->two_factor_enabled || !$user->two_factor_secret) {
            return [
                'success' => false,
                'message' => 'Two-factor authentication is not active on this account.',
                'status_code' => 422
            ];
        }

        $secret = $this->getDecryptedSecret($user->two_factor_secret);
        $valid = $this->google2fa->verifyKey($secret, trim($totpCode));

        if (!$valid) {
            return [
                'success' => false,
                'message' => 'Invalid authenticator code provided.',
                'status_code' => 422
            ];
        }

        $plainRecoveryCodes = [];
        $hashedRecoveryCodes = [];

        for ($i = 0; $i < 8; $i++) {
            $code = strtoupper(Str::random(4) . '-' . Str::random(4));
            $plainRecoveryCodes[] = $code;
            $hashedRecoveryCodes[] = [
                'hash' => Hash::make($code),
                'used' => false,
                'used_at' => null
            ];
        }

        $user->two_factor_recovery_codes = json_encode($hashedRecoveryCodes);
        $user->save();

        AuthAuditService::log(
            eventCategory: 'mfa',
            action: 'mfa_recovery_codes_regenerated',
            status: 'success',
            actorType: $userType,
            actorId: $user->id,
            guard: $userType === 'admin' ? 'admin' : 'member',
            request: $req
        );

        return [
            'success' => true,
            'message' => 'Recovery codes regenerated successfully. Please save these codes in a secure location.',
            'recovery_codes' => $plainRecoveryCodes
        ];
    }

    /**
     * Safely decrypt TOTP secret or return raw string if already decrypted by model accessor
     */
    protected function getDecryptedSecret(mixed $secret): string
    {
        if (empty($secret)) {
            return '';
        }
        // Handle serialized strings if decryptString was used on a serialized payload
        if (is_string($secret) && str_starts_with($secret, 's:') && str_contains($secret, '"')) {
            try {
                $unserialized = @unserialize($secret);
                if (is_string($unserialized)) {
                    $secret = $unserialized;
                }
            } catch (\Throwable) {}
        }
        try {
            $dec = decrypt($secret);
            if (is_string($dec)) {
                return $dec;
            }
        } catch (\Throwable) {
            //
        }
        return (string) $secret;
    }
}
