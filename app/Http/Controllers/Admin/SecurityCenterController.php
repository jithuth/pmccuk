<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuthEvent;
use App\Models\OtpChallenge;
use App\Models\UserDevice;
use App\Models\UserSession;
use App\Models\SecurityPolicy;
use App\Models\SecurityAlert;
use App\Models\Member;
use App\Models\Admin;
use App\Models\Setting;
use App\Services\Auth\AuthAuditService;
use App\Services\Auth\DeviceSessionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class SecurityCenterController extends Controller
{
    /**
     * Enforce access control: Admin / Superadmin / Privacy Officer only
     */
    protected function checkAccess()
    {
        $admin = Auth::guard('admin')->user();
        if (!$admin) {
            abort(401, 'Unauthorized');
        }
        if (!in_array($admin->role, ['admin', 'superadmin', 'privacy_officer']) && !$admin->hasRole(['admin', 'superadmin', 'privacy_officer'])) {
            abort(403, 'Access denied. Security Centre requires privileged administrative permissions.');
        }

        // Audit sensitive view access
        AuthAuditService::log(
            eventCategory: 'admin_security',
            action: 'security_center_accessed',
            status: 'success',
            actorType: 'admin',
            actorId: $admin->id,
            guard: 'admin',
            metadata: ['route' => request()->route()->getName()]
        );
    }

    /**
     * Screen 1: Security Overview Dashboard
     */
    public function overview()
    {
        $this->checkAccess();

        $now = now();
        $oneDayAgo = $now->copy()->subDay();

        // 24-hour event metrics
        $total24h = AuthEvent::where('created_at', '>=', $oneDayAgo)->count();
        $failed24h = AuthEvent::where('created_at', '>=', $oneDayAgo)
            ->whereIn('status', ['failed', 'blocked'])
            ->count();
        $successRate = $total24h > 0 ? round((($total24h - $failed24h) / $total24h) * 100, 1) : 100;

        // OTP metrics
        $otpTotal24h = OtpChallenge::where('created_at', '>=', $oneDayAgo)->count();
        $otpVerified24h = OtpChallenge::where('created_at', '>=', $oneDayAgo)->whereNotNull('verified_at')->count();

        // MFA Adoption
        $totalMembers = Member::count();
        $mfaMembers = Member::where('two_factor_enabled', true)->count();
        $totalAdmins = Admin::count();
        $mfaAdmins = Admin::where('two_factor_enabled', true)->count();

        // Active Sessions & Devices
        $activeSessionsCount = UserSession::where('is_active', true)->count();
        $recognizedDevicesCount = UserDevice::count();

        // Open Alerts
        $openAlertsCount = SecurityAlert::where('is_resolved', false)->count();

        // Recent High Risk Events (risk_score >= 40)
        $highRiskEvents = AuthEvent::where('risk_score', '>=', 40)
            ->orderBy('created_at', 'desc')
            ->limit(8)
            ->get();

        // Top Targeted or Active IPs in 24h
        $topIps = AuthEvent::select('ip_address', DB::raw('count(*) as count'), DB::raw('max(risk_score) as max_risk'))
            ->where('created_at', '>=', $oneDayAgo)
            ->whereNotNull('ip_address')
            ->groupBy('ip_address')
            ->orderBy('count', 'desc')
            ->limit(6)
            ->get();

        // Recent 10 Auth Events
        $recentEvents = AuthEvent::orderBy('created_at', 'desc')->limit(10)->get();

        return view('admin.security.overview', compact(
            'total24h', 'failed24h', 'successRate',
            'otpTotal24h', 'otpVerified24h',
            'totalMembers', 'mfaMembers', 'totalAdmins', 'mfaAdmins',
            'activeSessionsCount', 'recognizedDevicesCount',
            'openAlertsCount', 'highRiskEvents', 'topIps', 'recentEvents'
        ));
    }

    /**
     * Screen 2: Authentication Event Explorer
     */
    public function events(Request $request)
    {
        $this->checkAccess();

        $query = AuthEvent::query();

        if ($request->filled('actor')) {
            $query->where('actor_identifier', 'LIKE', '%' . trim($request->actor) . '%');
        }
        if ($request->filled('ip')) {
            $query->where('ip_address', 'LIKE', '%' . trim($request->ip) . '%');
        }
        if ($request->filled('category')) {
            $query->where('event_category', $request->category);
        }
        if ($request->filled('action')) {
            $query->where('action', $request->action);
        }
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        $events = $query->orderBy('created_at', 'desc')->paginate(25)->withQueryString();

        return view('admin.security.events', compact('events'));
    }

    /**
     * Screen 3: OTP Request History
     */
    public function otpHistory(Request $request)
    {
        $this->checkAccess();

        $query = OtpChallenge::with('member');

        if ($request->filled('identifier')) {
            $query->where('identifier', 'LIKE', '%' . trim($request->identifier) . '%');
        }
        if ($request->filled('delivery_status')) {
            $query->where('delivery_status', $request->delivery_status);
        }
        if ($request->filled('ip')) {
            $query->where('ip_address', 'LIKE', '%' . trim($request->ip) . '%');
        }
        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        $challenges = $query->orderBy('created_at', 'desc')->paginate(25)->withQueryString();

        return view('admin.security.otp_history', compact('challenges'));
    }

    /**
     * Screen 4: MFA Enrollment Status
     */
    public function mfaStatus(Request $request)
    {
        $this->checkAccess();

        $tab = $request->get('tab', 'members');

        if ($tab === 'admins') {
            $query = Admin::query();
            if ($request->filled('search')) {
                $s = trim($request->search);
                $query->where('username', 'LIKE', "%{$s}%")->orWhere('email', 'LIKE', "%{$s}%");
            }
            $admins = $query->paginate(25)->withQueryString();
            $members = null;
        } else {
            $query = Member::query();
            if ($request->filled('search')) {
                $s = trim($request->search);
                $query->where('membership_id_assigned', 'LIKE', "%{$s}%")
                    ->orWhere('email', 'LIKE', "%{$s}%")
                    ->orWhere('full_name', 'LIKE', "%{$s}%");
            }
            if ($request->filled('mfa_state')) {
                $query->where('two_factor_enabled', $request->mfa_state === 'enabled');
            }
            $members = $query->paginate(25)->withQueryString();
            $admins = null;
        }

        $memberStats = [
            'total' => Member::count(),
            'enabled' => Member::where('two_factor_enabled', true)->count(),
        ];
        $adminStats = [
            'total' => Admin::count(),
            'enabled' => Admin::where('two_factor_enabled', true)->count(),
        ];

        return view('admin.security.mfa_status', compact('tab', 'members', 'admins', 'memberStats', 'adminStats'));
    }

    /**
     * Screen 5: Login Attempts Explorer
     */
    public function loginAttempts(Request $request)
    {
        $this->checkAccess();

        $query = AuthEvent::whereIn('event_category', ['login', 'otp', 'mfa']);

        if ($request->filled('guard')) {
            $query->where('guard', $request->guard);
        }
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('actor')) {
            $query->where('actor_identifier', 'LIKE', '%' . trim($request->actor) . '%');
        }
        if ($request->filled('ip')) {
            $query->where('ip_address', 'LIKE', '%' . trim($request->ip) . '%');
        }
        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        $attempts = $query->orderBy('created_at', 'desc')->paginate(25)->withQueryString();

        return view('admin.security.login_attempts', compact('attempts'));
    }

    /**
     * Screen 6: Active Sessions List & Revocation
     */
    public function activeSessions(Request $request)
    {
        $this->checkAccess();

        $query = UserSession::with('device');

        if ($request->filled('user_type')) {
            $query->where('user_type', $request->user_type);
        }
        if ($request->filled('is_active')) {
            $query->where('is_active', $request->is_active === '1');
        }
        if ($request->filled('ip')) {
            $query->where('ip_address', 'LIKE', '%' . trim($request->ip) . '%');
        }

        $sessions = $query->orderBy('last_activity', 'desc')->paginate(25)->withQueryString();

        return view('admin.security.active_sessions', compact('sessions'));
    }

    /**
     * Revoke Session from Admin Security Centre
     */
    public function revokeSession(Request $request)
    {
        $this->checkAccess();
        $request->validate(['session_id' => 'required|integer']);

        $currentAdmin = Auth::guard('admin')->user();
        $session = UserSession::findOrFail($request->session_id);

        $session->update([
            'is_active' => false,
            'revoked_at' => now(),
            'revoked_by' => "admin_{$currentAdmin->username}_(ID:{$currentAdmin->id})"
        ]);

        AuthAuditService::log(
            eventCategory: 'admin_security',
            action: 'admin_revoked_session',
            status: 'success',
            actorType: 'admin',
            actorId: $currentAdmin->id,
            sessionIdRef: substr($session->session_id_hash, 0, 16),
            metadata: [
                'target_user_type' => $session->user_type,
                'target_user_id' => $session->user_id,
                'session_ip' => $session->ip_address
            ]
        );

        return back()->with('success', "Session #{$session->id} has been immediately revoked.");
    }

    /**
     * Screen 7: Device History Explorer
     */
    public function deviceHistory(Request $request)
    {
        $this->checkAccess();

        $query = UserDevice::query();

        if ($request->filled('user_type')) {
            $query->where('user_type', $request->user_type);
        }
        if ($request->filled('browser')) {
            $query->where('browser', 'LIKE', '%' . trim($request->browser) . '%');
        }
        if ($request->filled('ip')) {
            $query->where('ip_address', 'LIKE', '%' . trim($request->ip) . '%');
        }

        $devices = $query->orderBy('last_seen_at', 'desc')->paginate(25)->withQueryString();

        return view('admin.security.device_history', compact('devices'));
    }

    /**
     * Screen 8: Unified Suspicious IP Investigation
     */
    public function investigateIp(Request $request)
    {
        $this->checkAccess();

        $targetIp = trim((string) $request->get('ip'));
        $data = null;

        if (!empty($targetIp)) {
            // 1. IP Summary Metrics
            $totalEvents = AuthEvent::where('ip_address', $targetIp)->count();
            $failedEvents = AuthEvent::where('ip_address', $targetIp)->whereIn('status', ['failed', 'blocked'])->count();
            $firstSeen = AuthEvent::where('ip_address', $targetIp)->min('created_at');
            $lastSeen = AuthEvent::where('ip_address', $targetIp)->max('created_at');

            // 2. Targeted accounts
            $targetedIdentifiers = AuthEvent::where('ip_address', $targetIp)
                ->whereNotNull('actor_identifier')
                ->distinct()
                ->pluck('actor_identifier')
                ->toArray();

            // 3. OTP Challenges from this IP
            $otpChallenges = OtpChallenge::where('ip_address', $targetIp)->orderBy('created_at', 'desc')->limit(20)->get();

            // 4. Active & historical sessions from this IP
            $sessions = UserSession::where('ip_address', $targetIp)->orderBy('last_activity', 'desc')->limit(20)->get();

            // 5. Security Alerts associated with this IP
            $alerts = SecurityAlert::where('ip_address', $targetIp)->orderBy('created_at', 'desc')->get();

            // 6. Chronological event timeline
            $timeline = AuthEvent::where('ip_address', $targetIp)->orderBy('created_at', 'desc')->limit(50)->get();

            // 7. Check if IP is in WAF blocklist
            $wafSetting = Setting::where('setting_key', 'waf_ip_blocklist')->first();
            $blocklistStr = $wafSetting ? $wafSetting->setting_value : '';
            try { $blocklistStr = decrypt($blocklistStr); } catch (\Throwable $e) {}
            $isBlocked = in_array($targetIp, array_filter(array_map('trim', preg_split('/[\s,;]+/', (string)$blocklistStr))));

            $data = [
                'ip' => $targetIp,
                'location' => DeviceSessionService::getApproxLocation($targetIp),
                'total_events' => $totalEvents,
                'failed_events' => $failedEvents,
                'first_seen' => $firstSeen,
                'last_seen' => $lastSeen,
                'targeted_identifiers' => $targetedIdentifiers,
                'otp_challenges' => $otpChallenges,
                'sessions' => $sessions,
                'alerts' => $alerts,
                'timeline' => $timeline,
                'is_blocked' => $isBlocked,
            ];
        }

        return view('admin.security.investigate_ip', compact('targetIp', 'data'));
    }

    /**
     * Block or Unblock IP in WAF Blocklist from Security Centre
     */
    public function toggleBlockIp(Request $request)
    {
        $this->checkAccess();
        $request->validate(['ip' => 'required|ip']);
        $ip = trim($request->ip);

        $wafSetting = Setting::firstOrCreate(['setting_key' => 'waf_ip_blocklist']);
        $raw = $wafSetting->setting_value;
        try { $raw = decrypt($raw); } catch (\Throwable $e) {}
        
        $list = array_filter(array_map('trim', preg_split('/[\s,;]+/', (string)$raw)));

        $currentAdmin = Auth::guard('admin')->user();

        if (in_array($ip, $list)) {
            // Unblock
            $list = array_values(array_diff($list, [$ip]));
            $action = 'unblocked';
        } else {
            // Block
            $list[] = $ip;
            $action = 'blocked';
        }

        $newVal = implode("\n", array_unique($list));
        $wafSetting->setting_value = encrypt($newVal);
        $wafSetting->save();

        \Illuminate\Support\Facades\Cache::forget('waf_config');

        AuthAuditService::log(
            eventCategory: 'admin_security',
            action: "admin_ip_{$action}",
            status: 'warning',
            actorType: 'admin',
            actorId: $currentAdmin->id,
            metadata: ['target_ip' => $ip, 'action' => $action]
        );

        return back()->with('success', "IP {$ip} has been successfully {$action} in the firewall.");
    }

    /**
     * Screen 9: Security Alerts and Incident Management
     */
    public function alerts(Request $request)
    {
        $this->checkAccess();

        $query = SecurityAlert::with('resolver');

        if ($request->filled('severity')) {
            $query->where('severity', $request->severity);
        }
        if ($request->filled('is_resolved')) {
            $query->where('is_resolved', $request->is_resolved === '1');
        }
        if ($request->filled('ip')) {
            $query->where('ip_address', 'LIKE', '%' . trim($request->ip) . '%');
        }

        $alerts = $query->orderBy('created_at', 'desc')->paginate(20)->withQueryString();

        return view('admin.security.alerts', compact('alerts'));
    }

    /**
     * Resolve Security Alert
     */
    public function resolveAlert(Request $request, int $id)
    {
        $this->checkAccess();
        $request->validate(['resolution_notes' => 'required|string|max:1000']);

        $alert = SecurityAlert::findOrFail($id);
        $admin = Auth::guard('admin')->user();

        $alert->update([
            'is_resolved' => true,
            'resolved_at' => now(),
            'resolved_by' => $admin->id,
            'resolution_notes' => $request->resolution_notes,
        ]);

        AuthAuditService::log(
            eventCategory: 'admin_security',
            action: 'admin_resolved_security_alert',
            status: 'success',
            actorType: 'admin',
            actorId: $admin->id,
            metadata: ['alert_id' => $alert->id, 'alert_type' => $alert->alert_type]
        );

        return back()->with('success', "Security alert #{$alert->id} marked as resolved.");
    }

    /**
     * Screen 10: Security Policies Configuration
     */
    public function policies()
    {
        $this->checkAccess();

        // Ensure default policies exist
        if (SecurityPolicy::count() === 0) {
            (new \Database\Seeders\SecurityPolicySeeder())->run();
        }

        $policies = SecurityPolicy::orderBy('name')->get();

        return view('admin.security.policies', compact('policies'));
    }

    /**
     * Update Security Policy
     */
    public function updatePolicies(Request $request)
    {
        $this->checkAccess();
        $admin = Auth::guard('admin')->user();

        $policies = SecurityPolicy::all();
        $updates = [];

        foreach ($policies as $policy) {
            if ($request->has($policy->key)) {
                $val = $request->input($policy->key);
                SecurityPolicy::set($policy->key, $val, $admin->id);
                $updates[$policy->key] = $val;
            } elseif ($policy->type === 'boolean') {
                // Checkbox unchecked
                SecurityPolicy::set($policy->key, '0', $admin->id);
                $updates[$policy->key] = '0';
            }
        }

        AuthAuditService::log(
            eventCategory: 'security_policy',
            action: 'security_policies_updated',
            status: 'warning',
            actorType: 'admin',
            actorId: $admin->id,
            metadata: ['updated_keys' => array_keys($updates)]
        );

        return back()->with('success', 'Security policies updated successfully.');
    }

    /**
     * Screen 11: Audit Trail Export
     */
    public function export(Request $request)
    {
        $this->checkAccess();
        $admin = Auth::guard('admin')->user();

        $query = AuthEvent::query();

        if ($request->filled('actor')) {
            $query->where('actor_identifier', 'LIKE', '%' . trim($request->actor) . '%');
        }
        if ($request->filled('ip')) {
            $query->where('ip_address', 'LIKE', '%' . trim($request->ip) . '%');
        }
        if ($request->filled('category')) {
            $query->where('event_category', $request->category);
        }
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        $events = $query->orderBy('created_at', 'desc')->limit(2000)->get();

        AuthAuditService::log(
            eventCategory: 'admin_security',
            action: 'audit_trail_exported',
            status: 'warning',
            actorType: 'admin',
            actorId: $admin->id,
            metadata: ['rows_exported' => $events->count()]
        );

        $csvFileName = 'pmcc_auth_audit_export_' . date('Y-m-d_His') . '.csv';
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$csvFileName}\"",
        ];

        $callback = function () use ($events) {
            $file = fopen('php://output', 'w');
            fputcsv($file, [
                'Event UUID', 'Timestamp (UTC)', 'Correlation ID', 'Actor Type',
                'Actor Identifier', 'Guard', 'Event Category', 'Action', 'Status',
                'Reason Code', 'IP Address', 'Location', 'Device Name', 'Browser',
                'OS', 'Risk Score'
            ]);

            foreach ($events as $e) {
                fputcsv($file, [
                    $e->event_uuid,
                    $e->created_at->toIso8601String(),
                    $e->correlation_id,
                    $e->actor_type,
                    $e->actor_identifier,
                    $e->guard,
                    $e->event_category,
                    $e->action,
                    $e->status,
                    $e->reason_code,
                    $e->ip_address,
                    $e->location_summary,
                    $e->device_name,
                    $e->browser,
                    $e->os,
                    $e->risk_score,
                ]);
            }
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }
}
