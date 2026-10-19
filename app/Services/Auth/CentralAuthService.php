<?php

namespace App\Services\Auth;

use App\Models\Member;
use App\Models\Admin;
use App\Models\SecurityPolicy;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;

class CentralAuthService
{
    protected OtpService $otpService;
    protected MfaService $mfaService;

    public function __construct(OtpService $otpService, MfaService $mfaService)
    {
        $this->otpService = $otpService;
        $this->mfaService = $mfaService;
    }

    /**
     * Complete Member Authentication from primary OTP verification
     */
    public function handleMemberOtpVerification(string $submittedOtp, ?string $challengeUuid = null, ?string $identifier = null, ?Request $request = null): array
    {
        $req = $request ?: request();
        $result = OtpService::verifyOtp($submittedOtp, $challengeUuid, $identifier, $req);

        if (!$result['success']) {
            return $result;
        }

        $member = $result['member'];
        if (!$member) {
            return [
                'success' => false,
                'message' => 'Member account not found.',
                'status_code' => 404
            ];
        }

        // Check if Member has MFA enabled
        if (!empty($member->two_factor_enabled) && !empty($member->two_factor_secret)) {
            // Establish restricted pending MFA session state
            Session::put('pending_mfa_guard', 'member');
            Session::put('pending_mfa_member_id', $member->id);
            Session::put('pending_mfa_time', time());

            AuthAuditService::log(
                eventCategory: 'mfa',
                action: 'mfa_prompted',
                status: 'success',
                reasonCode: 'mfa_required',
                actorType: 'member',
                actorId: $member->id,
                guard: 'member',
                request: $req
            );

            return [
                'success' => true,
                'mfa_required' => true,
                'redirect' => route('member.mfa.challenge'),
                'message' => 'Please enter the 6-digit code from your authenticator app.'
            ];
        }

        // No MFA required: complete full member login
        return $this->establishMemberSession($member, $req);
    }

    /**
     * Complete Member MFA Challenge and establish session
     */
    public function completeMemberMfaChallenge(string $code, ?Request $request = null): array
    {
        $req = $request ?: request();
        $memberId = Session::get('pending_mfa_member_id');
        $pendingGuard = Session::get('pending_mfa_guard');

        if (!$memberId || $pendingGuard !== 'member') {
            return [
                'success' => false,
                'message' => 'Authentication session expired. Please sign in again.',
                'redirect' => route('member.login'),
                'status_code' => 401
            ];
        }

        $member = Member::find($memberId);
        if (!$member) {
            return [
                'success' => false,
                'message' => 'Member record not found.',
                'redirect' => route('member.login'),
                'status_code' => 404
            ];
        }

        $verify = $this->mfaService->verifyChallenge($member, 'member', $code, $req);
        if (!$verify['success']) {
            return $verify;
        }

        // Clear pending MFA markers
        Session::forget(['pending_mfa_guard', 'pending_mfa_member_id', 'pending_mfa_time']);

        return $this->establishMemberSession($member, $req, viaMfa: true);
    }

    /**
     * Establish authenticated Member session (with fixation prevention)
     */
    public function establishMemberSession(Member $member, Request $req, bool $viaMfa = false): array
    {
        // 1. Session fixation defense: regenerate session identifier
        if ($req->hasSession()) {
            $req->session()->regenerate();
        } else {
            \Illuminate\Support\Facades\Session::regenerate();
        }

        // 2. Log in through member guard
        Auth::guard('member')->login($member);

        // 3. Track device and create active session record in database
        $userSession = DeviceSessionService::createSessionRecord('member', $member->id, 'member', $req);

        AuthAuditService::log(
            eventCategory: 'login',
            action: 'login_success',
            status: 'success',
            actorType: 'member',
            actorId: $member->id,
            actorIdentifier: $member->membership_id_assigned ?: $member->email,
            guard: 'member',
            sessionIdRef: substr($userSession->session_id_hash, 0, 16),
            metadata: ['auth_method' => $viaMfa ? 'otp_and_totp_mfa' : 'otp_only'],
            request: $req
        );

        return [
            'success' => true,
            'mfa_required' => false,
            'redirect' => route('member.privacy.index'),
            'message' => "Welcome back, {$member->full_name}."
        ];
    }

    /**
     * Complete Admin Login Challenge (after username/password verified)
     */
    public function handleAdminLogin(Admin $admin, Request $req): array
    {
        $mandatoryAdminMfa = SecurityPolicy::get('admin_mandatory_mfa', true);

        // 1. If 2FA already enabled, require challenge
        if ($admin->two_factor_enabled && !empty($admin->two_factor_secret)) {
            Session::put('2fa_admin_id', $admin->id);
            Session::put('pending_mfa_guard', 'admin');

            AuthAuditService::log(
                eventCategory: 'mfa',
                action: 'admin_mfa_prompted',
                status: 'success',
                actorType: 'admin',
                actorId: $admin->id,
                guard: 'admin',
                request: $req
            );

            return [
                'mfa_required' => true,
                'redirect' => route('admin.2fa.challenge')
            ];
        }

        // 2. If 2FA is mandatory but not yet enrolled by this privileged admin
        if ($mandatoryAdminMfa) {
            // Log in admin but redirect directly to mandatory MFA setup
            Auth::guard('admin')->login($admin);
            $req->session()->regenerate();
            DeviceSessionService::createSessionRecord('admin', $admin->id, 'admin', $req);

            AuthAuditService::log(
                eventCategory: 'admin_security',
                action: 'admin_mandatory_mfa_prompt',
                status: 'warning',
                reasonCode: 'mfa_enrollment_required',
                actorType: 'admin',
                actorId: $admin->id,
                guard: 'admin',
                request: $req
            );

            return [
                'mfa_required' => false,
                'redirect' => route('admin.2fa.setup')
            ];
        }

        // 3. Normal login
        Auth::guard('admin')->login($admin);
        $req->session()->regenerate();
        DeviceSessionService::createSessionRecord('admin', $admin->id, 'admin', $req);

        AuthAuditService::log(
            eventCategory: 'login',
            action: 'admin_login_success',
            status: 'success',
            actorType: 'admin',
            actorId: $admin->id,
            actorIdentifier: $admin->username,
            guard: 'admin',
            metadata: ['role' => $admin->role],
            request: $req
        );

        $dest = $admin->role === 'staff' ? route('admin.staff.dashboard') : route('admin.dashboard');
        return [
            'mfa_required' => false,
            'redirect' => $dest
        ];
    }

    /**
     * Complete Admin MFA verification
     */
    public function completeAdminMfaChallenge(string $code, Request $req): array
    {
        $adminId = Session::get('2fa_admin_id');
        if (!$adminId) {
            return [
                'success' => false,
                'message' => 'Login session expired. Please sign in again.',
                'redirect' => route('admin.login'),
                'status_code' => 401
            ];
        }

        $admin = Admin::find($adminId);
        if (!$admin) {
            return [
                'success' => false,
                'message' => 'Admin record not found.',
                'redirect' => route('admin.login'),
                'status_code' => 404
            ];
        }

        $verify = $this->mfaService->verifyChallenge($admin, 'admin', $code, $req);
        if (!$verify['success']) {
            return $verify;
        }

        Session::forget(['2fa_admin_id', 'pending_mfa_guard']);
        Auth::guard('admin')->login($admin);
        $req->session()->regenerate();
        $sess = DeviceSessionService::createSessionRecord('admin', $admin->id, 'admin', $req);

        AuthAuditService::log(
            eventCategory: 'login',
            action: 'admin_login_success',
            status: 'success',
            actorType: 'admin',
            actorId: $admin->id,
            actorIdentifier: $admin->username,
            guard: 'admin',
            sessionIdRef: substr($sess->session_id_hash, 0, 16),
            metadata: ['auth_method' => 'password_and_totp_mfa', 'role' => $admin->role],
            request: $req
        );

        $dest = $admin->role === 'staff' ? route('admin.staff.dashboard') : route('admin.dashboard');

        return [
            'success' => true,
            'redirect' => $dest,
            'message' => 'Admin authentication complete.'
        ];
    }

    /**
     * Secure Logout for any guard
     */
    public function logout(string $guard, Request $req): void
    {
        $user = Auth::guard($guard)->user();
        $userType = $guard === 'admin' ? 'admin' : 'member';
        $userId = $user ? $user->id : null;
        $username = $user ? ($guard === 'admin' ? $user->username : ($user->membership_id_assigned ?: $user->email)) : null;

        if ($userId) {
            AuthAuditService::log(
                eventCategory: 'session',
                action: 'logout',
                status: 'success',
                actorType: $userType,
                actorId: $userId,
                actorIdentifier: $username,
                guard: $guard,
                request: $req
            );

            // Mark current session as revoked in DB
            $sessHash = DeviceSessionService::hashSessionId(Session::getId());
            \App\Models\UserSession::where('session_id_hash', $sessHash)->update([
                'is_active' => false,
                'revoked_at' => now(),
                'revoked_by' => 'user_logout'
            ]);
        }

        Auth::guard($guard)->logout();
        $req->session()->invalidate();
        $req->session()->regenerateToken();
    }
}
