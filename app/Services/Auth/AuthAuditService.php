<?php

namespace App\Services\Auth;

use App\Models\AuthEvent;
use App\Models\SecurityAuditLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Request as RequestFacade;
use Illuminate\Support\Str;

class AuthAuditService
{
    private static ?string $correlationId = null;

    public static function getCorrelationId(): string
    {
        if (self::$correlationId === null) {
            self::$correlationId = RequestFacade::header('X-Correlation-ID', (string) Str::uuid());
        }
        return self::$correlationId;
    }

    /**
     * Record an immutable authentication audit event.
     */
    public static function log(
        string $eventCategory,
        string $action,
        string $status = 'success',
        ?string $reasonCode = null,
        ?string $actorType = null,
        $actorId = null,
        ?string $actorIdentifier = null,
        ?string $guard = null,
        ?int $otpChallengeId = null,
        ?string $sessionIdRef = null,
        int $riskScore = 0,
        ?array $riskIndicators = null,
        ?array $metadata = null,
        ?Request $request = null
    ): ?AuthEvent {
        try {
            $req = $request ?: request();

            // Auto-detect actor if not provided
            if ($actorType === null && $actorId === null) {
                if (auth('admin')->check()) {
                    $actor = auth('admin')->user();
                    $actorType = 'admin';
                    $actorId = $actor->id;
                    $actorIdentifier = $actorIdentifier ?: $actor->username;
                    $guard = $guard ?: 'admin';
                } elseif (auth('member')->check()) {
                    $actor = auth('member')->user();
                    $actorType = 'member';
                    $actorId = $actor->id;
                    $actorIdentifier = $actorIdentifier ?: ($actor->membership_id_assigned ?: 'Member-' . $actor->id);
                    $guard = $guard ?: 'member';
                } else {
                    $actorType = 'guest';
                }
            }

            // Extract device metadata
            $deviceMeta = DeviceSessionService::parseUserAgent($req->userAgent());
            $deviceHash = DeviceSessionService::generateDeviceHash($req);

            // Sanitize metadata payload
            $sanitizedMeta = $metadata ? self::sanitize($metadata) : null;

            $event = AuthEvent::create([
                'event_uuid' => (string) Str::uuid(),
                'correlation_id' => self::getCorrelationId(),
                'actor_type' => $actorType ?: 'guest',
                'actor_id' => $actorId,
                'actor_identifier' => $actorIdentifier ? substr((string)$actorIdentifier, 0, 150) : null,
                'guard' => $guard,
                'event_category' => $eventCategory,
                'action' => $action,
                'status' => $status,
                'reason_code' => $reasonCode,
                'ip_address' => $req->ip(),
                'user_agent' => substr((string) $req->userAgent(), 0, 1000),
                'device_hash' => $deviceHash,
                'device_name' => $deviceMeta['device_name'] ?? 'Unknown Device',
                'browser' => $deviceMeta['browser'] ?? 'Unknown Browser',
                'os' => $deviceMeta['os'] ?? 'Unknown OS',
                'location_summary' => DeviceSessionService::getApproxLocation($req->ip()),
                'otp_challenge_id' => $otpChallengeId,
                'session_id_ref' => $sessionIdRef,
                'risk_score' => $riskScore,
                'risk_indicators' => $riskIndicators,
                'metadata' => $sanitizedMeta,
                'created_at' => now(),
            ]);

            // Sync high-level events with legacy security_audit_logs if present
            try {
                if (class_exists(SecurityAuditLog::class)) {
                    SecurityAuditLog::create([
                        'actor_id' => $actorId,
                        'actor_type' => $actorType ?: 'guest',
                        'actor_username' => $actorIdentifier,
                        'action' => "auth.{$eventCategory}.{$action}",
                        'resource_type' => 'AuthEvent',
                        'resource_id' => (string) $event->id,
                        'correlation_id' => self::getCorrelationId(),
                        'ip_address' => $req->ip(),
                        'user_agent' => substr((string) $req->userAgent(), 0, 500),
                        'justification' => $reasonCode ? "Reason: {$reasonCode}" : "Action: {$action}",
                        'outcome' => $status === 'success' ? 'success' : 'failed',
                        'payload_summary' => $sanitizedMeta,
                        'created_at' => now(),
                    ]);
                }
            } catch (\Throwable $e) {
                // Ignore fallback sync errors
            }

            return $event;
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error('AuthAuditService logging failure: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Sanitize sensitive values: NEVER log raw OTPs, MFA secrets, recovery codes, passwords or tokens.
     */
    public static function sanitize(array $data): array
    {
        $sensitiveKeys = [
            'otp', 'code', 'token', 'secret', 'password', 'password_confirmation',
            'two_factor_secret', 'recovery_code', 'recovery_codes', 'login_otp',
            'remember_token', 'cvv', 'card_number', 'bank_account_no', 'bank_sort_code'
        ];

        $clean = [];
        foreach ($data as $key => $val) {
            $lowerKey = strtolower((string)$key);
            if (in_array($lowerKey, $sensitiveKeys) || str_contains($lowerKey, 'secret') || str_contains($lowerKey, 'token') || str_contains($lowerKey, 'otp')) {
                $clean[$key] = '[PROTECTED_REDACTED]';
            } elseif (is_array($val)) {
                $clean[$key] = self::sanitize($val);
            } elseif (is_string($val) && strlen($val) > 250) {
                $clean[$key] = substr($val, 0, 250) . '... [TRUNCATED]';
            } else {
                $clean[$key] = $val;
            }
        }
        return $clean;
    }
}
