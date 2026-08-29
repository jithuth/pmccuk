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

        $photoFile = $this->saveTempSnapshot($request->input('login_photo'));

        // Validate credentials without logging in yet
        if (!Auth::guard('admin')->validate($credentials)) {
            try {
                $caption = "⚠️ <b>Security Alert: Failed Admin Login Attempt</b>\n\n" .
                    "👤 <b>Username:</b> " . htmlspecialchars($credentials['username']) . "\n" .
                    "🌐 <b>IP Address:</b> " . $request->ip() . "\n" .
                    "🗺️ <b>User Agent:</b> " . htmlspecialchars(substr($request->userAgent() ?? '', 0, 150));

                if ($photoFile) {
                    \App\Services\TelegramService::sendPhoto($photoFile, $caption);
                } else {
                    \App\Services\TelegramService::sendMessage($caption);
                }
            } catch (\Exception $e) {
            } finally {
                if ($photoFile && file_exists($photoFile)) {
                    @unlink($photoFile);
                }
            }

            return back()->withErrors([
                'username' => 'The provided credentials do not match our records.',
            ])->onlyInput('username');
        }

        $admin = \App\Models\Admin::where('username', $credentials['username'])->first();

        // If 2FA is enabled, redirect to challenge instead of completing login
        if ($admin->two_factor_enabled) {
            session(['2fa_admin_id' => $admin->id]);
            if ($photoFile && file_exists($photoFile)) {
                @unlink($photoFile);
            }
            return redirect()->route('admin.2fa.challenge');
        }

        // No 2FA — log in directly
        Auth::guard('admin')->login($admin);
        $request->session()->regenerate();

        try {
            $caption = "🔑 <b>Security Alert: Successful Admin Login</b>\n\n" .
                "👤 <b>User:</b> " . htmlspecialchars($admin->username) . " (ID: {$admin->id}, Role: {$admin->role})\n" .
                "🌐 <b>IP Address:</b> " . $request->ip();

            if ($photoFile) {
                \App\Services\TelegramService::sendPhoto($photoFile, $caption);
            } else {
                \App\Services\TelegramService::sendMessage($caption);
            }
        } catch (\Exception $e) {
        } finally {
            if ($photoFile && file_exists($photoFile)) {
                @unlink($photoFile);
            }
        }

        if ($admin->role === 'staff') {
            return redirect()->route('admin.staff.dashboard');
        }

        return redirect()->intended(route('admin.dashboard'));
    }

    /**
     * Decode base64 camera snapshot and save to temporary file.
     */
    private function saveTempSnapshot(?string $base64Data): ?string
    {
        if (empty($base64Data) || !str_contains($base64Data, 'base64,')) {
            return null;
        }

        try {
            $parts = explode('base64,', $base64Data);
            $data = $parts[1] ?? null;
            if (!$data) return null;

            $tempFile = storage_path('app/temp_snapshot_' . uniqid() . '.jpg');
            file_put_contents($tempFile, base64_decode($data));
            return $tempFile;
        } catch (\Exception $e) {
            return null;
        }
    }

    public function logout(Request $request)
    {
        Auth::guard('admin')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect()->route('admin.login');
    }
}
