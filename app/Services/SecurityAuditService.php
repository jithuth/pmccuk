<?php

namespace App\Services;

use App\Models\SecurityAuditLog;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Str;

class SecurityAuditService
{
    private static ?string $correlationId = null;

    public static function getCorrelationId(): string
    {
        if (self::$correlationId === null) {
            self::$correlationId = Request::header('X-Correlation-ID', (string) Str::uuid());
        }
        return self::$correlationId;
    }

    /**
     * Record a security and privacy audit log event.
     */
    public static function log(
        string $action,
        ?string $resourceType = null,
        $resourceId = null,
        ?string $justification = null,
        string $outcome = 'success',
        ?array $payload = null,
        ?string $approvalReference = null
    ): ?SecurityAuditLog {
        try {
            $actor = auth('admin')->user() ?? auth('member')->user();
            $actorId = $actor ? $actor->id : null;
            $actorType = 'guest';
            $actorUsername = null;

            if ($actor instanceof \App\Models\Admin) {
                $actorType = 'admin';
                $actorUsername = $actor->username;
            } elseif ($actor instanceof \App\Models\Member) {
                $actorType = 'member';
                $actorUsername = $actor->membership_id_assigned ?: ($actor->email ?: 'Member-' . $actor->id);
            }

            // Sanitize payload: strip any password, token, or secret
            $sanitizedPayload = null;
            if ($payload !== null) {
                $sanitizedPayload = self::sanitize($payload);
            }

            return SecurityAuditLog::create([
                'actor_id' => $actorId,
                'actor_type' => $actorType,
                'actor_username' => $actorUsername,
                'action' => $action,
                'resource_type' => $resourceType,
                'resource_id' => $resourceId !== null ? (string) $resourceId : null,
                'correlation_id' => self::getCorrelationId(),
                'ip_address' => Request::ip(),
                'user_agent' => substr((string) Request::userAgent(), 0, 500),
                'justification' => $justification,
                'outcome' => $outcome,
                'payload_summary' => $sanitizedPayload,
                'approval_reference' => $approvalReference,
                'created_at' => now(),
            ]);
        } catch (\Throwable $e) {
            // Never crash the primary request because of an audit log write failure
            \Illuminate\Support\Facades\Log::error('SecurityAuditService failure: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Sanitize sensitive keys from log payloads.
     */
    private static function sanitize(array $data): array
    {
        $sensitiveKeys = [
            'password', 'password_confirmation', 'token', 'secret',
            'two_factor_secret', 'remember_token', 'bank_account_no',
            'bank_sort_code', 'cvv', 'card_number'
        ];

        $clean = [];
        foreach ($data as $key => $val) {
            if (in_array(strtolower($key), $sensitiveKeys)) {
                $clean[$key] = '[REDACTED]';
            } elseif (is_array($val)) {
                $clean[$key] = self::sanitize($val);
            } elseif (is_string($val) && strlen($val) > 200) {
                $clean[$key] = substr($val, 0, 200) . '... [TRUNCATED]';
            } else {
                $clean[$key] = $val;
            }
        }
        return $clean;
    }
}
