<?php

namespace App\Http\Controllers\Member;

use App\Http\Controllers\Controller;
use App\Models\Member;
use App\Services\Auth\CentralAuthService;
use App\Services\Auth\OtpService;
use App\Services\Auth\AuthAuditService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Session;

class MemberAuthController extends Controller
{
    protected CentralAuthService $centralAuth;

    public function __construct(CentralAuthService $centralAuth)
    {
        $this->centralAuth = $centralAuth;
    }

    public function showLogin()
    {
        if (Auth::guard('member')->check()) {
            return redirect()->route('member.privacy.index');
        }

        if (Session::get('pending_mfa_guard') === 'member' && Session::get('pending_mfa_member_id')) {
            return redirect()->route('member.mfa.challenge');
        }

        return view('member.login');
    }

    public function showLoginForm()
    {
        return $this->showLogin();
    }

    public function sendOtp(Request $request)
    {
        return $this->sendLoginOtp($request);
    }

    public function verifyOtp(Request $request)
    {
        return $this->verifyLoginOtp($request);
    }

    /**
     * Dispatch OTP using central OtpService
     */
    public function sendLoginOtp(Request $request)
    {
        $request->validate([
            'identifier' => 'required|string',
        ]);

        $result = OtpService::requestOtp($request->identifier, 'member_login', $request);

        if (!$result['success']) {
            return response()->json([
                'success' => false,
                'message' => $result['message'],
                'seconds_remaining' => $result['seconds_remaining'] ?? null
            ], $result['status_code'] ?? 422);
        }

        // Store active challenge uuid in session for seamless verification
        Session::put('member_challenge_uuid', $result['challenge_uuid']);
        Session::put('member_identifier', trim($request->identifier));

        if (app()->environment('local', 'testing') && !empty($result['plain_otp'])) {
            Session::put('member_login_otp', $result['plain_otp']);
        }

        return response()->json([
            'success' => true,
            'message' => $result['message'],
            'challenge_uuid' => $result['challenge_uuid'],
            'resend_cooldown' => $result['resend_cooldown'] ?? 60
        ]);
    }

    /**
     * Verify OTP using central auth service
     */
    public function verifyLoginOtp(Request $request)
    {
        $request->validate([
            'otp' => 'required|digits:6',
            'challenge_uuid' => 'nullable|string',
        ]);

        $challengeUuid = $request->input('challenge_uuid') ?: Session::get('member_challenge_uuid');
        $identifier = Session::get('member_identifier');

        $result = $this->centralAuth->handleMemberOtpVerification(
            submittedOtp: $request->otp,
            challengeUuid: $challengeUuid,
            identifier: $identifier,
            request: $request
        );

        if (!$result['success']) {
            return response()->json([
                'success' => false,
                'message' => $result['message']
            ], $result['status_code'] ?? 422);
        }

        Session::forget(['member_challenge_uuid', 'member_identifier']);

        return response()->json([
            'success' => true,
            'mfa_required' => $result['mfa_required'] ?? false,
            'redirect' => $result['redirect']
        ]);
    }

    /**
     * Show MFA Challenge view for members
     */
    public function showMfaChallenge()
    {
        $memberId = Session::get('pending_mfa_member_id');
        $pendingGuard = Session::get('pending_mfa_guard');

        if (!$memberId || $pendingGuard !== 'member') {
            return redirect()->route('member.login');
        }

        $member = Member::find($memberId);
        if (!$member) {
            return redirect()->route('member.login');
        }

        return view('member.mfa.challenge', compact('member'));
    }

    /**
     * Verify MFA code during member login
     */
    public function verifyMfaChallenge(Request $request)
    {
        $request->validate([
            'code' => 'required|string',
        ]);

        $result = $this->centralAuth->completeMemberMfaChallenge($request->code, $request);

        if (!$result['success']) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => $result['message']
                ], $result['status_code'] ?? 422);
            }
            return back()->withErrors(['code' => $result['message']]);
        }

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'redirect' => $result['redirect']
            ]);
        }

        $intended = session()->get('url.intended');
        if ($intended && (str_contains($intended, '/mfa/') || str_contains($intended, '/login'))) {
            session()->forget('url.intended');
        }

        return redirect()->intended($result['redirect'])->with('success', $result['message']);
    }

    /**
     * Backward-compatible password login for testing/legacy
     */
    public function loginWithPassword(Request $request)
    {
        return $this->login($request);
    }

    public function login(Request $request)
    {
        $request->validate([
            'login'    => 'required|string',
            'password' => 'required|string',
        ]);

        $login = trim($request->login);
        $member = OtpService::resolveMember($login);

        if (!$member) {
            AuthAuditService::log(
                eventCategory: 'login',
                action: 'member_login_failed',
                status: 'failed',
                reasonCode: 'invalid_identifier',
                actorIdentifier: $login,
                guard: 'member',
                request: $request
            );
            return back()->withErrors(['login' => 'No membership found matching the provided details.'])->withInput();
        }

        if (!$member->password || !Hash::check($request->password, $member->password)) {
            AuthAuditService::log(
                eventCategory: 'login',
                action: 'member_login_failed',
                status: 'failed',
                reasonCode: 'invalid_password',
                actorType: 'member',
                actorId: $member->id,
                actorIdentifier: $member->membership_id_assigned,
                guard: 'member',
                request: $request
            );
            return back()->withErrors(['password' => 'The password provided is incorrect.'])->withInput();
        }

        // Check MFA
        if (!empty($member->two_factor_enabled) && !empty($member->two_factor_secret)) {
            Session::put('pending_mfa_guard', 'member');
            Session::put('pending_mfa_member_id', $member->id);
            Session::put('pending_mfa_time', time());

            return redirect()->route('member.mfa.challenge');
        }

        $sessionResult = $this->centralAuth->establishMemberSession($member, $request);

        return redirect()->intended($sessionResult['redirect'])
            ->with('success', "Welcome back, {$member->full_name}. You are securely signed in.");
    }

    public function logout(Request $request)
    {
        $this->centralAuth->logout('member', $request);

        return redirect()->route('member.login')
            ->with('success', 'You have been safely signed out of your Member Privacy Center.');
    }
}
