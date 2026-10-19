<?php

namespace App\Http\Controllers\Member;

use App\Http\Controllers\Controller;
use App\Models\AuthEvent;
use App\Models\SecurityAlert;
use App\Models\UserDevice;
use App\Services\Auth\MfaService;
use App\Services\Auth\DeviceSessionService;
use App\Services\Auth\AuthAuditService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class MemberSecurityController extends Controller
{
    protected MfaService $mfaService;

    public function __construct(MfaService $mfaService)
    {
        $this->mfaService = $mfaService;
    }

    /**
     * Show Member Security Dashboard
     */
    public function index(Request $request)
    {
        $member = Auth::guard('member')->user();

        // 1. Active Sessions
        $sessions = DeviceSessionService::getUserSessions('member', $member->id);

        // 2. Recognized Devices
        $devices = UserDevice::where('user_type', 'member')
            ->where('user_id', $member->id)
            ->orderBy('last_seen_at', 'desc')
            ->get();

        // 3. Recent Authentication Audit Events
        $recentEvents = AuthEvent::where('actor_type', 'member')
            ->where('actor_id', $member->id)
            ->orderBy('created_at', 'desc')
            ->limit(15)
            ->get();

        // 4. Security Alerts
        $alerts = SecurityAlert::where('user_type', 'member')
            ->where('user_id', $member->id)
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();

        // 5. Remaining recovery codes count
        $recoveryCodesCount = 0;
        if ($member->two_factor_enabled && !empty($member->two_factor_recovery_codes)) {
            $codes = json_decode($member->two_factor_recovery_codes, true);
            if (is_array($codes)) {
                $recoveryCodesCount = count(array_filter($codes, fn($c) => empty($c['used'])));
            }
        }

        return view('member.security.index', compact(
            'member',
            'sessions',
            'devices',
            'recentEvents',
            'alerts',
            'recoveryCodesCount'
        ));
    }

    /**
     * Initiate MFA Enrollment
     */
    public function initiateMfa(Request $request)
    {
        $member = Auth::guard('member')->user();
        $enrollment = $this->mfaService->initiateEnrollment($member, 'member');

        return response()->json([
            'success' => true,
            'secret' => $enrollment['secret'],
            'qr_image_url' => $enrollment['qr_image_url'],
            'otp_auth_url' => $enrollment['otp_auth_url'],
        ]);
    }

    /**
     * Confirm MFA Enrollment
     */
    public function confirmMfa(Request $request)
    {
        $request->validate(['code' => 'required|string']);
        $member = Auth::guard('member')->user();

        $result = $this->mfaService->confirmEnrollment($member, 'member', $request->code, $request);

        return response()->json($result, $result['status_code'] ?? 200);
    }

    /**
     * Disable MFA
     */
    public function disableMfa(Request $request)
    {
        $request->validate(['code' => 'required|string']);
        $member = Auth::guard('member')->user();

        $result = $this->mfaService->disableMfa($member, 'member', $request->code, $request);

        if (!$result['success']) {
            return back()->with('error', $result['message']);
        }

        return back()->with('success', $result['message']);
    }

    /**
     * Regenerate Recovery Codes
     */
    public function regenerateRecoveryCodes(Request $request)
    {
        $request->validate(['code' => 'required|string']);
        $member = Auth::guard('member')->user();

        $result = $this->mfaService->regenerateRecoveryCodes($member, 'member', $request->code, $request);

        return response()->json($result, $result['status_code'] ?? 200);
    }

    /**
     * Revoke a single active session
     */
    public function revokeSession(Request $request)
    {
        $request->validate(['session_id' => 'required|integer']);
        $member = Auth::guard('member')->user();

        $ok = DeviceSessionService::revokeSession($request->session_id, 'member', $member->id, 'member_self');

        if ($request->expectsJson()) {
            return response()->json(['success' => $ok]);
        }

        return back()->with($ok ? 'success' : 'error', $ok ? 'Session revoked successfully.' : 'Unable to revoke session.');
    }

    /**
     * Revoke all other active sessions
     */
    public function revokeOtherSessions(Request $request)
    {
        $member = Auth::guard('member')->user();
        $count = DeviceSessionService::revokeAllOtherSessions('member', $member->id, 'member_self');

        if ($request->expectsJson()) {
            return response()->json(['success' => true, 'revoked_count' => $count]);
        }

        return back()->with('success', "Revoked {$count} other active session(s).");
    }

    /**
     * Report suspicious activity
     */
    public function reportSuspiciousActivity(Request $request)
    {
        $request->validate([
            'description' => 'required|string|min:10|max:1000',
            'event_id' => 'nullable|integer',
        ]);

        $member = Auth::guard('member')->user();

        $alert = SecurityAlert::create([
            'alert_type' => 'member_reported_suspicious_activity',
            'severity' => 'high',
            'title' => "Suspicious Activity Reported by {$member->full_name} (ID: {$member->membership_id_assigned})",
            'details' => $request->description . ($request->event_id ? " [Associated Event ID: {$request->event_id}]" : ""),
            'ip_address' => $request->ip(),
            'user_type' => 'member',
            'user_id' => $member->id,
            'is_resolved' => false,
        ]);

        AuthAuditService::log(
            eventCategory: 'threat',
            action: 'member_reported_suspicious_activity',
            status: 'warning',
            reasonCode: 'user_incident_report',
            actorType: 'member',
            actorId: $member->id,
            guard: 'member',
            riskScore: 65,
            riskIndicators: ['user_reported_threat'],
            metadata: ['description' => $request->description, 'alert_id' => $alert->id],
            request: $request
        );

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Thank you. Your security report has been submitted directly to the Community Data Protection & Security Team.'
            ]);
        }

        return back()->with('success', 'Your security report has been submitted directly to the Community Data Protection & Security Team.');
    }
}
