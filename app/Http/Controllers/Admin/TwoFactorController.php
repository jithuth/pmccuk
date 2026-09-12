<?php namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use PragmaRX\Google2FA\Google2FA;

class TwoFactorController extends Controller
{
    protected Google2FA $google2fa;

    public function __construct()
    {
        $this->google2fa = new Google2FA();
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
        $request->validate(['otp' => 'required|digits:6']);

        $adminId = session('2fa_admin_id');
        if (!$adminId) {
            return redirect()->route('admin.login');
        }

        $admin = \App\Models\Admin::findOrFail($adminId);
        $secret = decrypt($admin->two_factor_secret);

        $valid = $this->google2fa->verifyKey($secret, $request->otp);

        // Check emergency 2FA passcode generated via Telegram
        $emergencyCode = \Illuminate\Support\Facades\Cache::get("admin_emergency_2fa_{$admin->id}");
        if (!$valid && $emergencyCode && (string)$request->otp === (string)$emergencyCode) {
            $valid = true;
            \Illuminate\Support\Facades\Cache::forget("admin_emergency_2fa_{$admin->id}");
        }

        if (!$valid) {
            try {
                \App\Services\TelegramService::sendMessage(
                    "⚠️ 🛡️ <b>Security Alert: Failed 2FA Attempt</b>\n\n" .
                    "👤 <b>User:</b> " . htmlspecialchars($admin->username) . " (ID: {$admin->id})\n" .
                    "🌐 <b>IP Address:</b> " . $request->ip()
                );
            } catch (\Exception $e) {}

            return back()->withErrors(['otp' => 'Invalid code. Please try again.']);
        }

        // Complete login
        Auth::guard('admin')->login($admin);
        session()->forget('2fa_admin_id');
        $request->session()->regenerate();

        try {
            \App\Services\TelegramService::sendMessage(
                "🔑 🛡️ <b>Security Alert: Successful Admin Login (via 2FA)</b>\n\n" .
                "👤 <b>User:</b> " . htmlspecialchars($admin->username) . " (ID: {$admin->id}, Role: {$admin->role})\n" .
                "🌐 <b>IP Address:</b> " . $request->ip()
            );
        } catch (\Exception $e) {}

        session()->forget('url.intended');
        return redirect()->route('admin.dashboard');
    }

    // ─── 2FA Setup (from admin profile) ───────────────────────────
    public function showSetup()
    {
        $admin  = Auth::guard('admin')->user();
        $secret = $this->google2fa->generateSecretKey();
        session(['2fa_setup_secret' => $secret]);

        $otpUrl = $this->google2fa->getQRCodeUrl(
            config('app.name', 'PMCC-UK'),
            $admin->email ?? $admin->username,
            $secret
        );

        // Use QR Server API — no extra package needed
        $qrImageUrl = 'https://api.qrserver.com/v1/create-qr-code/?size=200x200&data=' . urlencode($otpUrl);

        return view('admin.2fa.setup', [
            'secret'     => $secret,
            'qrImageUrl' => $qrImageUrl,
            'enabled'    => $admin->two_factor_enabled,
        ]);
    }

    public function confirmSetup(Request $request)
    {
        $request->validate(['otp' => 'required|digits:6']);

        $secret = session('2fa_setup_secret');
        if (!$secret) {
            return redirect()->route('admin.2fa.setup')->withErrors(['otp' => 'Session expired. Please start over.']);
        }

        $valid = $this->google2fa->verifyKey($secret, $request->otp);

        if (!$valid) {
            return back()->withErrors(['otp' => 'Code did not match. Please scan the QR code again.']);
        }

        $admin = Auth::guard('admin')->user();
        $admin->two_factor_secret  = encrypt($secret);
        $admin->two_factor_enabled = true;
        $admin->save();

        session()->forget('2fa_setup_secret');

        return redirect()->route('admin.2fa.setup')
            ->with('success', '2FA has been enabled successfully on your account.');
    }

    public function disable(Request $request)
    {
        $request->validate(['password' => 'required']);

        $admin = Auth::guard('admin')->user();

        if (!\Illuminate\Support\Facades\Hash::check($request->password, $admin->password)) {
            return back()->withErrors(['password' => 'Incorrect password.']);
        }

        $admin->two_factor_enabled = false;
        $admin->two_factor_secret  = null;
        $admin->save();

        return redirect()->route('admin.2fa.setup')
            ->with('success', '2FA has been disabled.');
    }
}
