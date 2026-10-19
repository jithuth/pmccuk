<?php

namespace App\Services\Auth;

use App\Models\Member;
use App\Models\OtpChallenge;
use App\Models\SecurityPolicy;
use App\Mail\OtpMail;
use App\Services\OpenWaService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class OtpService
{
    /**
     * Request an OTP challenge for a member identifier.
     * Returns generic, enumeration-safe response.
     */
    public static function requestOtp(string $rawIdentifier, string $purpose = 'member_login', ?Request $request = null): array
    {
        $req = $request ?: request();
        $cleanIdentifier = trim($rawIdentifier);

        // 1. Threat Detection & Rate Limiting Inspection
        $threatAssessment = ThreatDetectionService::inspectOtpRequest($cleanIdentifier, $req);
        if ($threatAssessment['is_blocked']) {
            AuthAuditService::log(
                eventCategory: 'otp',
                action: 'otp_request_blocked',
                status: 'blocked',
                reasonCode: $threatAssessment['block_reason'],
                actorIdentifier: $cleanIdentifier,
                riskScore: $threatAssessment['risk_score'],
                riskIndicators: $threatAssessment['risk_indicators'],
                request: $req
            );

            return [
                'success' => false,
                'message' => 'Too many requests. Please wait before requesting another verification code.',
                'status_code' => 429
            ];
        }

        // 2. Resolve Member securely
        $member = self::resolveMember($cleanIdentifier);

        // Audit the OTP request event (before generation)
        AuthAuditService::log(
            eventCategory: 'otp',
            action: 'otp_requested',
            status: 'success',
            actorType: $member ? 'member' : 'guest',
            actorId: $member?->id,
            actorIdentifier: $cleanIdentifier,
            riskScore: $threatAssessment['risk_score'],
            riskIndicators: $threatAssessment['risk_indicators'],
            metadata: ['purpose' => $purpose],
            request: $req
        );

        // Generic response if member does not exist (Enumeration Prevention)
        if (!$member) {
            // Fake challenge ID or delay to mitigate timing attacks
            usleep(random_int(100000, 250000));
            
            return [
                'success' => true,
                'generic' => true,
                'message' => 'If an active account exists with the provided information, a verification code has been dispatched.',
                'challenge_uuid' => (string) Str::uuid(),
                'channels' => ['Email / WhatsApp']
            ];
        }

        // 3. Check Resend Cooldown
        $recentChallenge = OtpChallenge::where('member_id', $member->id)
            ->where('purpose', $purpose)
            ->whereNull('consumed_at')
            ->latest('created_at')
            ->first();

        $cooldownSeconds = SecurityPolicy::get('otp_resend_cooldown_seconds', 60);

        if ($recentChallenge && $recentChallenge->resend_available_at && $recentChallenge->resend_available_at->isFuture()) {
            $secondsRemaining = now()->diffInSeconds($recentChallenge->resend_available_at);
            
            AuthAuditService::log(
                eventCategory: 'otp',
                action: 'otp_cooldown_active',
                status: 'warning',
                reasonCode: 'cooldown_active',
                actorType: 'member',
                actorId: $member->id,
                actorIdentifier: $cleanIdentifier,
                otpChallengeId: $recentChallenge->id,
                request: $req
            );

            return [
                'success' => false,
                'message' => "Please wait {$secondsRemaining} seconds before requesting a new verification code.",
                'seconds_remaining' => $secondsRemaining,
                'status_code' => 429
            ];
        }

        // 4. Generate Cryptographically Secure 6-digit OTP
        $rawOtp = str_pad((string) random_int(100000, 999999), 6, '0', STR_PAD_LEFT);
        $otpHash = Hash::make($rawOtp);
        $expiryMinutes = SecurityPolicy::get('otp_expiry_minutes', 5);
        $maxAttempts = SecurityPolicy::get('otp_max_attempts', 5);

        // Record Generation Event (NEVER log $rawOtp)
        $challenge = OtpChallenge::create([
            'challenge_uuid' => (string) Str::uuid(),
            'identifier' => $cleanIdentifier,
            'member_id' => $member->id,
            'otp_hash' => $otpHash,
            'purpose' => $purpose,
            'channel' => 'both',
            'delivery_status' => 'pending',
            'attempts' => 0,
            'max_attempts' => $maxAttempts,
            'resend_available_at' => now()->addSeconds($cooldownSeconds),
            'expires_at' => now()->addMinutes($expiryMinutes),
            'ip_address' => $req->ip(),
            'user_agent' => substr((string) $req->userAgent(), 0, 500),
            'correlation_id' => AuthAuditService::getCorrelationId(),
        ]);

        AuthAuditService::log(
            eventCategory: 'otp',
            action: 'otp_generated',
            status: 'success',
            actorType: 'member',
            actorId: $member->id,
            actorIdentifier: $cleanIdentifier,
            otpChallengeId: $challenge->id,
            metadata: [
                'expiry_minutes' => $expiryMinutes,
                'challenge_uuid' => $challenge->challenge_uuid
            ],
            request: $req
        );

        // 5. Dispatch via Email & WhatsApp with Provider Delivery Evidence Tracking
        $dispatchResult = self::dispatchOtp($member, $challenge, $rawOtp);

        return [
            'success' => true,
            'generic' => false,
            'message' => $dispatchResult['user_message'],
            'challenge_uuid' => $challenge->challenge_uuid,
            'channels' => $dispatchResult['channels'],
            'resend_cooldown' => $cooldownSeconds,
            'plain_otp' => app()->environment('local', 'testing') ? $rawOtp : null,
        ];
    }

    /**
     * Dispatch OTP to Email and WhatsApp, collecting evidence
     */
    protected static function dispatchOtp(Member $member, OtpChallenge $challenge, string $rawOtp): array
    {
        $channels = [];
        $emailEvidence = null;
        $whatsappEvidence = null;
        $anySuccess = false;

        // 1. Email Dispatch
        if (!empty($member->email)) {
            try {
                Mail::to($member->email)->send(new OtpMail($member->full_name, $rawOtp));
                $emailEvidence = [
                    'recipient' => self::maskEmail($member->email),
                    'timestamp' => now()->toIso8601String(),
                    'provider' => config('mail.default', 'smtp'),
                    'status' => 'dispatched'
                ];
                $channels[] = 'Email (' . self::maskEmail($member->email) . ')';
                $anySuccess = true;
            } catch (\Throwable $e) {
                $emailEvidence = [
                    'recipient' => self::maskEmail($member->email),
                    'timestamp' => now()->toIso8601String(),
                    'provider' => config('mail.default', 'smtp'),
                    'status' => 'failed',
                    'error' => $e->getMessage()
                ];
                Log::warning("Member OTP Email dispatch error: " . $e->getMessage());
            }
        }

        // 2. WhatsApp Dispatch
        if (!empty($member->mobile_number)) {
            try {
                $textMessage = "🔒 *PMCC Verification Code*\n\nYour 6-digit access code is: *{$rawOtp}*\n\nValid for 5 minutes. Do not share this code with anyone.";
                $waResult = OpenWaService::sendTextMessage($member->mobile_number, $textMessage);

                if (!empty($waResult['success']) && !empty($waResult['messageId'])) {
                    $whatsappEvidence = [
                        'recipient' => self::maskPhone($member->mobile_number),
                        'message_id' => $waResult['messageId'],
                        'timestamp' => now()->toIso8601String(),
                        'provider' => 'OpenWA microservice',
                        'status' => 'confirmed'
                    ];
                    $channels[] = 'WhatsApp (' . self::maskPhone($member->mobile_number) . ')';
                    $anySuccess = true;
                } else {
                    $whatsappEvidence = [
                        'recipient' => self::maskPhone($member->mobile_number),
                        'timestamp' => now()->toIso8601String(),
                        'provider' => 'OpenWA microservice',
                        'status' => 'failed',
                        'error' => $waResult['error'] ?? 'Daemon rejected message'
                    ];
                }
            } catch (\Throwable $e) {
                $whatsappEvidence = [
                    'recipient' => self::maskPhone($member->mobile_number),
                    'timestamp' => now()->toIso8601String(),
                    'provider' => 'OpenWA microservice',
                    'status' => 'failed',
                    'error' => $e->getMessage()
                ];
                Log::warning("Member OTP WhatsApp dispatch error: " . $e->getMessage());
            }
        }

        // Update challenge delivery evidence
        $deliveryStatus = $anySuccess ? 'confirmed' : 'failed';
        $challenge->update([
            'delivery_status' => $deliveryStatus,
            'email_delivery_evidence' => $emailEvidence,
            'whatsapp_delivery_evidence' => $whatsappEvidence,
        ]);

        AuthAuditService::log(
            eventCategory: 'otp',
            action: 'otp_dispatched',
            status: $anySuccess ? 'success' : 'failed',
            reasonCode: $anySuccess ? 'delivery_confirmed' : 'delivery_failed',
            actorType: 'member',
            actorId: $member->id,
            otpChallengeId: $challenge->id,
            metadata: [
                'channels' => $channels,
                'email_status' => $emailEvidence['status'] ?? 'not_configured',
                'whatsapp_status' => $whatsappEvidence['status'] ?? 'not_configured'
            ]
        );

        $channelsDesc = !empty($channels) ? implode(' & ', $channels) : 'your registered contact channels';
        $msg = $anySuccess
            ? "A 6-digit verification code has been dispatched to {$channelsDesc}."
            : "Verification code generated. If you do not receive it shortly, please verify your details with community support.";

        return [
            'success' => $anySuccess,
            'channels' => $channels,
            'user_message' => $msg
        ];
    }

    /**
     * Atomically verify an OTP submission
     */
    public static function verifyOtp(string $submittedOtp, ?string $challengeUuid = null, ?string $identifier = null, ?Request $request = null): array
    {
        $req = $request ?: request();

        return DB::transaction(function () use ($submittedOtp, $challengeUuid, $identifier, $req) {
            // Find challenge with row locking
            $query = OtpChallenge::lockForUpdate();

            if (!empty($challengeUuid)) {
                $challenge = $query->where('challenge_uuid', $challengeUuid)->first();
            } else {
                // Find latest active challenge for identifier
                $challenge = $query->where('identifier', trim((string)$identifier))
                    ->whereNull('consumed_at')
                    ->latest('created_at')
                    ->first();
            }

            if (!$challenge) {
                AuthAuditService::log(
                    eventCategory: 'otp',
                    action: 'otp_verify_failed',
                    status: 'failed',
                    reasonCode: 'challenge_not_found',
                    actorIdentifier: $identifier,
                    request: $req
                );

                return [
                    'success' => false,
                    'message' => 'The verification request was not found. Please request a new code.',
                    'status_code' => 404
                ];
            }

            // 1. Check if already consumed (Single-Use enforcement)
            if ($challenge->isConsumed()) {
                AuthAuditService::log(
                    eventCategory: 'otp',
                    action: 'otp_replay_blocked',
                    status: 'blocked',
                    reasonCode: 'challenge_already_consumed',
                    actorType: 'member',
                    actorId: $challenge->member_id,
                    otpChallengeId: $challenge->id,
                    riskScore: 70,
                    riskIndicators: ['otp_replay_attempt'],
                    request: $req
                );

                return [
                    'success' => false,
                    'message' => 'This verification code has already been used. Please request a new one.',
                    'status_code' => 422
                ];
            }

            // 2. Check if expired
            if ($challenge->isExpired()) {
                AuthAuditService::log(
                    eventCategory: 'otp',
                    action: 'otp_expired',
                    status: 'failed',
                    reasonCode: 'otp_code_expired',
                    actorType: 'member',
                    actorId: $challenge->member_id,
                    otpChallengeId: $challenge->id,
                    request: $req
                );

                return [
                    'success' => false,
                    'message' => 'The verification code has expired. Please request a new one.',
                    'status_code' => 422
                ];
            }

            // 3. Check attempt limit
            if ($challenge->hasExceededMaxAttempts()) {
                AuthAuditService::log(
                    eventCategory: 'otp',
                    action: 'otp_max_attempts_exceeded',
                    status: 'blocked',
                    reasonCode: 'max_attempts_exceeded',
                    actorType: 'member',
                    actorId: $challenge->member_id,
                    otpChallengeId: $challenge->id,
                    riskScore: 60,
                    riskIndicators: ['otp_bruteforce_limit_reached'],
                    request: $req
                );

                return [
                    'success' => false,
                    'message' => 'Maximum verification attempts exceeded. Please request a new code.',
                    'status_code' => 429
                ];
            }

            // 4. Verify OTP Hash
            $isValid = Hash::check($submittedOtp, $challenge->otp_hash);

            if (!$isValid) {
                $challenge->increment('attempts');
                $threat = ThreatDetectionService::inspectFailedOtp($challenge->id, $challenge->identifier, $req);

                AuthAuditService::log(
                    eventCategory: 'otp',
                    action: 'otp_verify_failed',
                    status: 'failed',
                    reasonCode: 'invalid_code',
                    actorType: 'member',
                    actorId: $challenge->member_id,
                    otpChallengeId: $challenge->id,
                    riskScore: $threat['risk_score'],
                    riskIndicators: $threat['risk_indicators'],
                    metadata: ['attempt_number' => $challenge->attempts],
                    request: $req
                );

                return [
                    'success' => false,
                    'message' => 'The verification code provided is invalid.',
                    'status_code' => 422
                ];
            }

            // 5. Successful Verification -> Atomically Consume
            $challenge->update([
                'verified_at' => now(),
                'consumed_at' => now(),
            ]);

            $member = Member::find($challenge->member_id);

            AuthAuditService::log(
                eventCategory: 'otp',
                action: 'otp_verified',
                status: 'success',
                actorType: 'member',
                actorId: $member?->id,
                actorIdentifier: $challenge->identifier,
                otpChallengeId: $challenge->id,
                request: $req
            );

            return [
                'success' => true,
                'challenge' => $challenge,
                'member' => $member,
                'mfa_required' => (bool) ($member && $member->two_factor_enabled),
            ];
        });
    }

    /**
     * Resolve member safely across ID, email, and mobile variants
     */
    public static function resolveMember(string $identifier): ?Member
    {
        $raw = trim($identifier);
        $cleanPhone = preg_replace('/[^0-9]/', '', $raw);

        $id = $raw;
        if (!str_starts_with(strtoupper($id), 'PMCC-') && preg_match('/^\d+$/', $id) && strlen($id) <= 6) {
            $id = 'PMCC-' . $id;
        }

        // Direct database query first
        $member = Member::where('membership_id_assigned', $id)
            ->orWhere('membership_id_assigned', $raw)
            ->orWhere('email', $raw)
            ->orWhere('mobile_number', $raw)
            ->when(!empty($cleanPhone), function ($q) use ($cleanPhone) {
                $q->orWhere('mobile_number', 'LIKE', '%' . substr($cleanPhone, -10));
            })
            ->first();

        if ($member) {
            return $member;
        }

        // Fallback for encrypted member records (HasSmartDecryption)
        return Member::all()->first(function ($m) use ($raw, $cleanPhone) {
            if ($m->membership_id_assigned && strtolower(trim($m->membership_id_assigned)) === strtolower(trim($raw))) {
                return true;
            }
            if ($m->email && strtolower(trim($m->email)) === strtolower(trim($raw))) {
                return true;
            }
            if ($m->mobile_number) {
                $mPhone = preg_replace('/[^0-9]/', '', $m->mobile_number);
                if (!empty($cleanPhone) && ($mPhone === $cleanPhone || str_ends_with($mPhone, substr($cleanPhone, -10)))) {
                    return true;
                }
            }
            return false;
        });
    }

    private static function maskEmail(?string $email): string
    {
        if (empty($email) || !str_contains($email, '@')) return '***@***';
        $parts = explode('@', $email);
        $user = $parts[0];
        $domain = $parts[1] ?? 'pmcc-uk.org';
        $maskedUser = strlen($user) <= 3 ? $user . '***' : substr($user, 0, 2) . '***' . substr($user, -1);
        return "{$maskedUser}@{$domain}";
    }

    private static function maskPhone(?string $phone): string
    {
        $clean = preg_replace('/[^0-9]/', '', (string)$phone);
        if (strlen($clean) < 7) return '***';
        return substr($clean, 0, 3) . '****' . substr($clean, -3);
    }
}
