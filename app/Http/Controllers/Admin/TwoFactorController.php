<?php namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\Auth\CentralAuthService;
use App\Services\Auth\MfaService;
use App\Services\Auth\AuthAuditService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use PragmaRX\Google2FA\Google2FA;

class TwoFactorController extends Controller
{
    protected Google2FA $google2fa;
    protected CentralAuthService $centralAuth;
    protected MfaService $mfaService;

    public function __construct(CentralAuthService $centralAuth, MfaService $mfaService)
    {
        $this->google2fa = new Google2FA();
        $this->centralAuth = $centralAuth;
        $this->mfaService = $mfaService;
    }

    // ─── Step 2 of login: show OTP form ───────────────────────────
    public function showChallenge()
    {
        if (!session('2fa_admin_id')) {
            return redirect()->route('admin.login');
        }
        return view('admin.2fa.challenge');
    }

    public function verifyChallenge(Request $request)
    {
        $request->validate(['otp' => 'required|string']);

        $adminId = session('2fa_admin_id');
        if (!$adminId) {
            return redirect()->route('admin.login');
        }

        $result = $this->centralAuth->completeAdminMfaChallenge($request->otp, $request);

        if (!$result['success']) {
            try {
                $admin = \App\Models\Admin::find($adminId);
                \App\Services\TelegramService::sendMessage(
                    "⚠️ 🛡️ <b>Security Alert: Failed 2FA Attempt</b>\n\n" .
                    "👤 <b>User:</b> " . htmlspecialchars($admin ? $admin->username : 'Unknown') . " (ID: {$adminId})\n" .
                    "🌐 <b>IP Address:</b> " . $request->ip()
                );
            } catch (\Exception $e) {}

            return back()->withErrors(['otp' => $result['message']]);
        }

        try {
            $admin = Auth::guard('admin')->user();
            \App\Services\TelegramService::sendMessage(
                "🔑 🛡️ <b>Security Alert: Successful Admin Login (via 2FA)</b>\n\n" .
                "👤 <b>User:</b> " . htmlspecialchars($admin->username) . " (ID: {$admin->id}, Role: {$admin->role})\n" .
                "🌐 <b>IP Address:</b> " . $request->ip()
            );
        } catch (\Exception $e) {}

        session()->forget('url.intended');
        return redirect($result['redirect']);
    }

    // ─── 2FA Setup (from admin profile) ───────────────────────────
    public function showSetup()
    {
        $admin = Auth::guard('admin')->user();
        $enrollment = $this->mfaService->initiateEnrollment($admin, 'admin');

        return view('admin.2fa.setup', [
            'secret'     => $enrollment['secret'],
            'qrImageUrl' => $enrollment['qr_image_url'],
            'enabled'    => $admin->two_factor_enabled,
        ]);
    }

    public function confirmSetup(Request $request)
    {
        $request->validate(['otp' => 'required|string']);

        $admin = Auth::guard('admin')->user();
        $result = $this->mfaService->confirmEnrollment($admin, 'admin', $request->otp, $request);

        if (!$result['success']) {
            return back()->withErrors(['otp' => $result['message']]);
        }

        // Store plain recovery codes temporarily in session so they can be shown to the admin once
        session(['admin_new_recovery_codes' => $result['recovery_codes']]);

        return redirect()->route('admin.2fa.setup')
            ->with('success', '2FA has been enabled successfully on your account. Please record your emergency recovery codes below.');
    }

    public function disable(Request $request)
    {
        $request->validate(['password' => 'required']);

        $admin = Auth::guard('admin')->user();

        if (!\Illuminate\Support\Facades\Hash::check($request->password, $admin->password)) {
            return back()->withErrors(['password' => 'Incorrect password.']);
        }

        // If mandatory admin MFA is in effect, prevent disabling
        if (\App\Models\SecurityPolicy::get('admin_mandatory_mfa', true)) {
            return back()->withErrors(['password' => 'Mandatory MFA policy is enforced. You cannot disable 2FA on an administrative account.']);
        }

        $admin->two_factor_enabled = false;
        $admin->two_factor_secret  = null;
        $admin->two_factor_recovery_codes = null;
        $admin->two_factor_confirmed_at = null;
        $admin->save();

        AuthAuditService::log(
            eventCategory: 'admin_security',
            action: 'admin_mfa_disabled',
            status: 'warning',
            actorType: 'admin',
            actorId: $admin->id,
            guard: 'admin',
            request: $request
        );

        return redirect()->route('admin.2fa.setup')
            ->with('success', '2FA has been disabled.');
    }
}
