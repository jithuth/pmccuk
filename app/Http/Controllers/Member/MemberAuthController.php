<?php

namespace App\Http\Controllers\Member;

use App\Http\Controllers\Controller;
use App\Models\Member;
use App\Services\SecurityAuditService;
use App\Mail\OtpMail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Session;

class MemberAuthController extends Controller
{
    public function showLogin()
    {
        if (Auth::guard('member')->check()) {
            return redirect()->route('member.privacy.index');
        }

        return view('member.login');
    }

    public function showLoginForm()
    {
        return $this->showLogin();
    }

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

        // Find member by membership ID, email, or mobile number
        $member = Member::where('membership_id_assigned', $login)
            ->orWhere('email', $login)
            ->orWhere('mobile_number', $login)
            ->first();

        if (!$member) {
            SecurityAuditService::log('member_login_failed', 'Member', null, 'Invalid login identifier: ' . $login, 'failed');
            return back()->withErrors(['login' => 'No membership found matching the provided details.'])->withInput();
        }

        // Verify password
        if (!$member->password || !Hash::check($request->password, $member->password)) {
            SecurityAuditService::log('member_login_failed', 'Member', $member->id, 'Invalid password attempt', 'failed');
            return back()->withErrors(['password' => 'The password provided is incorrect. If you have not set a password, use the One-Time Passcode (OTP) option below.'])->withInput();
        }

        Auth::guard('member')->login($member, $request->boolean('remember'));
        $request->session()->regenerate();

        SecurityAuditService::log('member_login_success', 'Member', $member->id, 'Member authenticated into Privacy Center via password', 'success');

        return redirect()->intended(route('member.privacy.index'))
            ->with('success', "Welcome back, {$member->full_name}. You are securely signed in to your Member Privacy Center.");
    }

    public function sendLoginOtp(Request $request)
    {
        $request->validate(['identifier' => 'required|string']);
        $id = trim($request->identifier);

        if ($id && !str_starts_with(strtoupper($id), 'PMCC-') && preg_match('/^\d+$/', $id)) {
            $id = 'PMCC-' . $id;
        }

        $member = Member::where('membership_id_assigned', $id)
            ->orWhere('email', $id)
            ->orWhere('mobile_number', $id)
            ->first();

        if (!$member || empty($member->email)) {
            return response()->json(['success' => false, 'message' => 'No active membership record found matching this detail.'], 404);
        }

        $otp = str_pad((string) random_int(100000, 999999), 6, '0', STR_PAD_LEFT);
        Session::put('member_login_otp', $otp);
        Session::put('member_login_member_id', $member->id);
        Session::put('member_login_otp_time', time());

        try {
            Mail::to($member->email)->send(new OtpMail($member->full_name, $otp));

            if (!empty($member->mobile_number)) {
                try {
                    \App\Services\OpenWaService::sendOtp($member->mobile_number, $otp, 'PMCC Member Privacy Center Login');
                } catch (\Throwable $e) {}
            }

            // Mask email for display
            $parts = explode('@', $member->email);
            $userPart = $parts[0];
            $domain = $parts[1] ?? 'pmcc-uk.org';
            $maskedUser = strlen($userPart) <= 3 ? $userPart . '***' : substr($userPart, 0, 2) . '***' . substr($userPart, -1);
            $maskedEmail = $maskedUser . '@' . $domain;

            return response()->json([
                'success'      => true,
                'message'      => "A 6-digit verification code has been sent to {$maskedEmail}.",
                'masked_email' => $maskedEmail
            ]);
        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'message' => 'Failed to dispatch email verification code. Please check SMTP connectivity.'], 500);
        }
    }

    public function verifyLoginOtp(Request $request)
    {
        $request->validate([
            'otp' => 'required|digits:6',
        ]);

        $storedOtp = Session::get('member_login_otp');
        $memberId = Session::get('member_login_member_id');
        $otpTime = Session::get('member_login_otp_time');

        if (!$storedOtp || !$memberId || time() - $otpTime > 600) {
            return response()->json(['success' => false, 'message' => 'The verification code has expired. Please request a new one.'], 422);
        }

        if ($request->otp !== $storedOtp) {
            SecurityAuditService::log('member_otp_login_failed', 'Member', $memberId, 'Invalid OTP submitted', 'failed');
            return response()->json(['success' => false, 'message' => 'The verification code provided is invalid.'], 422);
        }

        $member = Member::find($memberId);
        if (!$member) {
            return response()->json(['success' => false, 'message' => 'Member record not found.'], 404);
        }

        Session::forget(['member_login_otp', 'member_login_member_id', 'member_login_otp_time']);
        Auth::guard('member')->login($member, true);
        $request->session()->regenerate();

        SecurityAuditService::log('member_otp_login_success', 'Member', $member->id, 'Member authenticated into Privacy Center via OTP', 'success');

        return response()->json([
            'success'  => true,
            'redirect' => route('member.privacy.index')
        ]);
    }

    public function logout(Request $request)
    {
        $member = Auth::guard('member')->user();
        if ($member) {
            SecurityAuditService::log('member_logout', 'Member', $member->id, 'Member logged out from Privacy Center', 'success');
        }

        Auth::guard('member')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('member.login')
            ->with('success', 'You have been safely signed out of your Member Privacy Center.');
    }
}
