<?php

namespace App\Services\Auth;

use App\Models\Member;
use App\Models\Admin;
use App\Models\UserDevice;
use App\Models\UserSession;
use App\Models\SecurityPolicy;
use App\Models\SecurityAlert;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Str;

class DeviceSessionService
{
    /**
     * Generate deterministic device hash
     */
    public static function generateDeviceHash(?Request $request = null): string
    {
        $req = $request ?: request();
        $ua = (string) $req->userAgent();
        $accept = (string) $req->header('accept-language');
        
        // Salted hash of browser platform parameters
        return hash('sha256', "pmcc_dev_{$ua}_{$accept}");
    }

    /**
     * Parse User-Agent into clean components
     */
    public static function parseUserAgent(?string $ua): array
    {
        $ua = $ua ?: '';
        $browser = 'Unknown Browser';
        $os = 'Unknown OS';
        $device = 'Desktop Computer';

        // OS Detection
        if (preg_match('/iPhone/i', $ua)) {
            $os = 'iOS';
            $device = 'Apple iPhone';
        } elseif (preg_match('/iPad/i', $ua)) {
            $os = 'iPadOS';
            $device = 'Apple iPad';
        } elseif (preg_match('/Android/i', $ua)) {
            $os = 'Android';
            $device = 'Android Device';
        } elseif (preg_match('/Windows NT 10.0/i', $ua)) {
            $os = 'Windows 10/11';
        } elseif (preg_match('/Windows NT 6.3/i', $ua)) {
            $os = 'Windows 8.1';
        } elseif (preg_match('/Windows NT 6.1/i', $ua)) {
            $os = 'Windows 7';
        } elseif (preg_match('/Macintosh|Mac OS X/i', $ua)) {
            $os = 'macOS';
            $device = 'Apple Mac';
        } elseif (preg_match('/Linux/i', $ua)) {
            $os = 'Linux';
        }

        // Browser Detection
        if (preg_match('/Edg\/([0-9.]+)/i', $ua, $matches)) {
            $browser = 'Microsoft Edge ' . explode('.', $matches[1])[0];
        } elseif (preg_match('/Chrome\/([0-9.]+)/i', $ua, $matches)) {
            $browser = 'Google Chrome ' . explode('.', $matches[1])[0];
        } elseif (preg_match('/Firefox\/([0-9.]+)/i', $ua, $matches)) {
            $browser = 'Mozilla Firefox ' . explode('.', $matches[1])[0];
        } elseif (preg_match('/Safari\/([0-9.]+)/i', $ua, $matches) && !str_contains($ua, 'Chrome')) {
            $browser = 'Apple Safari ' . explode('.', $matches[1])[0];
        } elseif (preg_match('/Opera|OPR\/([0-9.]+)/i', $ua, $matches)) {
            $browser = 'Opera';
        }

        return [
            'browser' => $browser,
            'os' => $os,
            'device_name' => "{$browser} on {$os} ({$device})"
        ];
    }

    /**
     * Get approximate IP-based location summary
     */
    public static function getApproxLocation(?string $ip): string
    {
        if (empty($ip) || in_array($ip, ['127.0.0.1', '::1']) || str_starts_with($ip, '192.168.') || str_starts_with($ip, '10.')) {
            return 'Localhost / Private Network';
        }

        // Check cache to avoid external API calls
        return \Illuminate\Support\Facades\Cache::remember("ip_loc_" . md5($ip), 86400, function() use ($ip) {
            try {
                $res = \Illuminate\Support\Facades\Http::timeout(2)->get("http://ip-api.com/json/{$ip}?fields=status,country,city,regionName");
                if ($res->successful() && $res->json('status') === 'success') {
                    $city = $res->json('city') ?? '';
                    $country = $res->json('country') ?? '';
                    return trim("{$city}, {$country}", ', ');
                }
            } catch (\Throwable $e) {}
            return 'United Kingdom (Approx)';
        });
    }

    /**
     * Record device and detect if newly observed
     */
    public static function trackDevice(string $userType, int $userId, ?Request $request = null): array
    {
        $req = $request ?: request();
        $deviceHash = self::generateDeviceHash($req);
        $deviceMeta = self::parseUserAgent($req->userAgent());
        $location = self::getApproxLocation($req->ip());

        $device = UserDevice::where('user_type', $userType)
            ->where('user_id', $userId)
            ->where('device_hash', $deviceHash)
            ->first();

        $isNewDevice = false;

        if (!$device) {
            $isNewDevice = true;
            $device = UserDevice::create([
                'user_type' => $userType,
                'user_id' => $userId,
                'device_hash' => $deviceHash,
                'device_name' => $deviceMeta['device_name'],
                'browser' => $deviceMeta['browser'],
                'os' => $deviceMeta['os'],
                'ip_address' => $req->ip(),
                'location' => $location,
                'is_trusted' => false,
                'first_seen_at' => now(),
                'last_seen_at' => now(),
            ]);

            AuthAuditService::log(
                eventCategory: 'device',
                action: 'device_new',
                status: 'warning',
                reasonCode: 'unrecognized_device',
                actorType: $userType,
                actorId: $userId,
                guard: $userType === 'admin' ? 'admin' : 'member',
                riskScore: 25,
                riskIndicators: ['unrecognized_device_fingerprint'],
                metadata: [
                    'device_name' => $deviceMeta['device_name'],
                    'ip' => $req->ip(),
                    'location' => $location,
                ],
                request: $req
            );

            // Optional alert dispatch
            if (SecurityPolicy::get('notify_new_device', true)) {
                SecurityAlert::create([
                    'alert_type' => 'new_device_login',
                    'severity' => 'low',
                    'title' => "New Device Detected for {$userType} #{$userId}",
                    'details' => "Signed in from {$deviceMeta['device_name']} (IP: {$req->ip()}, {$location}).",
                    'ip_address' => $req->ip(),
                    'user_type' => $userType,
                    'user_id' => $userId,
                ]);
            }
        } else {
            $device->update([
                'last_seen_at' => now(),
                'ip_address' => $req->ip(),
                'location' => $location,
            ]);
        }

        return [
            'device' => $device,
            'is_new' => $isNewDevice
        ];
    }

    /**
     * Hash session ID to safely store and match sessions
     */
    public static function hashSessionId(string $sessionId): string
    {
        return hash('sha256', "pmcc_sess_{$sessionId}");
    }

    /**
     * Create active user session record
     */
    public static function createSessionRecord(string $userType, int $userId, string $guard, ?Request $request = null): UserSession
    {
        $req = $request ?: request();
        $sessionId = Session::getId();
        $sessionIdHash = self::hashSessionId($sessionId);
        $deviceInfo = self::trackDevice($userType, $userId, $req);

        // Terminate any previous record with identical session_id_hash
        UserSession::where('session_id_hash', $sessionIdHash)->delete();

        // Enforce max active sessions policy
        $maxSessions = SecurityPolicy::get('max_active_sessions_per_user', 5);
        $activeSessions = UserSession::where('user_type', $userType)
            ->where('user_id', $userId)
            ->where('is_active', true)
            ->orderBy('last_activity', 'asc')
            ->get();

        if ($activeSessions->count() >= $maxSessions) {
            $excess = $activeSessions->count() - $maxSessions + 1;
            foreach ($activeSessions->take($excess) as $oldSession) {
                $oldSession->update([
                    'is_active' => false,
                    'revoked_at' => now(),
                    'revoked_by' => 'policy_max_sessions_exceeded'
                ]);
            }
        }

        $session = UserSession::create([
            'session_id_hash' => $sessionIdHash,
            'user_type' => $userType,
            'user_id' => $userId,
            'guard' => $guard,
            'device_id' => $deviceInfo['device']->id ?? null,
            'ip_address' => $req->ip(),
            'user_agent' => substr((string) $req->userAgent(), 0, 500),
            'last_activity' => now(),
            'is_active' => true,
        ]);

        AuthAuditService::log(
            eventCategory: 'session',
            action: 'session_created',
            status: 'success',
            actorType: $userType,
            actorId: $userId,
            guard: $guard,
            sessionIdRef: substr($sessionIdHash, 0, 16),
            metadata: [
                'device_id' => $deviceInfo['device']->id ?? null,
                'device_name' => $deviceInfo['device']->device_name ?? null,
                'ip' => $req->ip()
            ],
            request: $req
        );

        return $session;
    }

    /**
     * Update active session heartbeat timestamp
     */
    public static function touchCurrentSession(): void
    {
        $sessionId = Session::getId();
        if (empty($sessionId)) return;

        $hash = self::hashSessionId($sessionId);
        UserSession::where('session_id_hash', $hash)
            ->where('is_active', true)
            ->update(['last_activity' => now()]);
    }

    /**
     * Check if current session has been revoked in database
     */
    public static function isCurrentSessionRevoked(): bool
    {
        $sessionId = Session::getId();
        if (empty($sessionId)) return false;

        $hash = self::hashSessionId($sessionId);
        $sess = UserSession::where('session_id_hash', $hash)->first();

        return $sess && !$sess->is_active;
    }

    /**
     * Revoke a specific session record
     */
    public static function revokeSession(int $sessionId, string $userType, int $userId, string $revokedBy = 'user'): bool
    {
        $session = UserSession::where('id', $sessionId)
            ->where('user_type', $userType)
            ->where('user_id', $userId)
            ->first();

        if (!$session) {
            return false;
        }

        $session->update([
            'is_active' => false,
            'revoked_at' => now(),
            'revoked_by' => $revokedBy,
        ]);

        AuthAuditService::log(
            eventCategory: 'session',
            action: 'session_revoked',
            status: 'success',
            reasonCode: 'manual_session_revocation',
            actorType: $userType,
            actorId: $userId,
            sessionIdRef: substr($session->session_id_hash, 0, 16),
            metadata: ['revoked_by' => $revokedBy]
        );

        return true;
    }

    /**
     * Revoke all other sessions for this user except current
     */
    public static function revokeAllOtherSessions(string $userType, int $userId, string $revokedBy = 'user'): int
    {
        $currentHash = self::hashSessionId(Session::getId());

        $revokedCount = UserSession::where('user_type', $userType)
            ->where('user_id', $userId)
            ->where('session_id_hash', '!=', $currentHash)
            ->where('is_active', true)
            ->update([
                'is_active' => false,
                'revoked_at' => now(),
                'revoked_by' => $revokedBy,
            ]);

        AuthAuditService::log(
            eventCategory: 'session',
            action: 'session_revoked_others',
            status: 'success',
            reasonCode: 'revoke_all_other_sessions',
            actorType: $userType,
            actorId: $userId,
            metadata: ['revoked_count' => $revokedCount, 'revoked_by' => $revokedBy]
        );

        return $revokedCount;
    }

    /**
     * Get user active sessions with current session flag
     */
    public static function getUserSessions(string $userType, int $userId): array
    {
        $currentHash = self::hashSessionId(Session::getId());
        $sessions = UserSession::with('device')
            ->where('user_type', $userType)
            ->where('user_id', $userId)
            ->orderBy('last_activity', 'desc')
            ->limit(20)
            ->get();

        return $sessions->map(function ($s) use ($currentHash) {
            $meta = self::parseUserAgent($s->user_agent);
            return [
                'id' => $s->id,
                'is_current' => $s->session_id_hash === $currentHash,
                'is_active' => $s->is_active,
                'ip_address' => $s->ip_address,
                'location' => $s->device->location ?? self::getApproxLocation($s->ip_address),
                'browser' => $meta['browser'],
                'os' => $meta['os'],
                'device_name' => $s->device->device_name ?? $meta['device_name'],
                'last_activity' => $s->last_activity,
                'created_at' => $s->created_at,
                'revoked_at' => $s->revoked_at,
            ];
        })->toArray();
    }
}
