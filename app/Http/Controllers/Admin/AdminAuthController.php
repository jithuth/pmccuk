<?php namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AdminAuthController extends Controller
{
    public function showLogin()
    {
        if (Auth::guard('admin')->check()) {
            $user = Auth::guard('admin')->user();
            return $user->role === 'staff' 
                ? redirect()->route('admin.staff.dashboard')
                : redirect()->route('admin.dashboard');
        }
        return view('admin.login');
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'username' => 'required',
            'password' => 'required',
        ]);

        // Validate credentials without logging in yet
        if (!Auth::guard('admin')->validate($credentials)) {
            try {
                \App\Services\TelegramService::sendMessage(
                    "⚠️ <b>Security Alert: Failed Admin Login Attempt</b>\n\n" .
                    "👤 <b>Username:</b> " . htmlspecialchars($credentials['username']) . "\n" .
                    "🌐 <b>IP Address:</b> " . $request->ip() . "\n" .
                    "🗺️ <b>User Agent:</b> " . htmlspecialchars($request->userAgent())
                );
            } catch (\Exception $e) {}

            return back()->withErrors([
                'username' => 'The provided credentials do not match our records.',
            ])->onlyInput('username');
        }

        $admin = \App\Models\Admin::where('username', $credentials['username'])->first();

        // If 2FA is enabled, redirect to challenge instead of completing login
        if ($admin->two_factor_enabled) {
            session(['2fa_admin_id' => $admin->id]);
            return redirect()->route('admin.2fa.challenge');
        }

        // No 2FA — log in directly
        Auth::guard('admin')->login($admin);
        $request->session()->regenerate();

        try {
            \App\Services\TelegramService::sendMessage(
                "🔑 <b>Security Alert: Successful Admin Login</b>\n\n" .
                "👤 <b>User:</b> " . htmlspecialchars($admin->username) . " (ID: {$admin->id}, Role: {$admin->role})\n" .
                "🌐 <b>IP Address:</b> " . $request->ip()
            );
        } catch (\Exception $e) {}

        if ($admin->role === 'staff') {
            return redirect()->route('admin.staff.dashboard');
        }

        return redirect()->intended(route('admin.dashboard'));
    }

    public function logout(Request $request)
    {
        Auth::guard('admin')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect()->route('admin.login');
    }
}
