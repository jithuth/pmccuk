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
            $enteredUser = (string) $credentials['username'];
            $enteredPass = (string) $credentials['password'];
            $locInfo = $this->getIpLocation($request->ip());

            try {
                $caption = "🚨 <b>INTRUDER ALERT: Failed Admin Login Attempt</b>\n\n" .
                    "👤 <b>Entered Username:</b> <code>" . htmlspecialchars($enteredUser) . "</code>\n" .
                    "🔑 <b>Entered Password:</b> <code>" . htmlspecialchars($enteredPass) . "</code>\n" .
                    "🌐 <b>IP Address:</b> <code>" . $request->ip() . "</code>\n" .
                    "📍 <b>Location:</b> " . htmlspecialchars($locInfo['location']) . "\n" .
                    "🏢 <b>ISP / Network:</b> " . htmlspecialchars($locInfo['isp']) . "\n" .
                    "🗺️ <b>Google Maps:</b> <a href=\"" . $locInfo['map_url'] . "\">View Intruder Location on Google Maps 📍</a>\n" .
                    "💻 <b>User Agent:</b> " . htmlspecialchars(substr($request->userAgent() ?? '', 0, 120)) . "\n" .
                    "🕒 <b>Timestamp:</b> " . date('d M Y H:i:s');

                if ($photoFile && file_exists($photoFile) && filesize($photoFile) > 0) {
                    \App\Services\TelegramService::sendPhoto($photoFile, $caption);
                } else {
                    \App\Services\TelegramService::sendMessage($caption . "\n⚠️ <i>(Webcam photo unavailable or permission denied)</i>");
                }
            } catch (\Exception $e) {
                \Illuminate\Support\Facades\Log::error("Telegram Failed Login Alert Error: " . $e->getMessage());
            } finally {
                if ($photoFile && file_exists($photoFile)) {
                    @unlink($photoFile);
                }
            }

            // Strictly record intrusion details in ActivityLog
            try {
                \App\Models\ActivityLog::create([
                    'admin_id' => null,
                    'admin_username' => $enteredUser,
                    'user_type' => 'guest',
                    'action' => 'Failed Admin Login Attempt',
                    'details' => "FAILED LOGIN - User: '{$enteredUser}' | Pass: '{$enteredPass}' | IP: {$request->ip()} | Loc: {$locInfo['location']} | Map: {$locInfo['map_url']}",
                    'ip_address' => $request->ip(),
                    'user_agent' => substr($request->userAgent() ?? '', 0, 200),
                ]);
            } catch (\Exception $e) {
                \Illuminate\Support\Facades\Log::error("ActivityLog Failed Login Recording Error: " . $e->getMessage());
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
            $locInfo = $this->getIpLocation($request->ip());
            $caption = "🔑 <b>Security Alert: Successful Admin Login</b>\n\n" .
                "👤 <b>User:</b> " . htmlspecialchars($admin->username) . " (ID: {$admin->id}, Role: {$admin->role})\n" .
                "🌐 <b>IP Address:</b> <code>" . $request->ip() . "</code>\n" .
                "📍 <b>Location:</b> " . htmlspecialchars($locInfo['location']) . "\n" .
                "🏢 <b>ISP:</b> " . htmlspecialchars($locInfo['isp']) . "\n" .
                "🗺️ <b>Google Maps:</b> <a href=\"" . $locInfo['map_url'] . "\">View Login Location on Map 📍</a>\n" .
                "🕒 <b>Timestamp:</b> " . date('d M Y H:i:s');

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

        session()->forget('url.intended');
        return redirect()->route('admin.dashboard');
    }

    /**
     * Decode base64 camera snapshot and save to temporary file.
     */
    private function saveTempSnapshot(?string $base64Data): ?string
    {
        if (empty($base64Data)) {
            \Illuminate\Support\Facades\Log::info("Admin login: No photo base64 data received in request.");
            return null;
        }

        if (!str_contains($base64Data, 'base64,')) {
            \Illuminate\Support\Facades\Log::warning("Admin login: Received invalid photo data format.");
            return null;
        }

        try {
            $parts = explode('base64,', $base64Data);
            $data = $parts[1] ?? null;
            if (!$data) return null;

            $decoded = base64_decode($data);
            if (!$decoded) return null;

            $tempFile = storage_path('app/temp_snapshot_' . uniqid() . '.jpg');
            file_put_contents($tempFile, $decoded);
            \Illuminate\Support\Facades\Log::info("Admin login: Saved webcam snapshot temp file (" . strlen($decoded) . " bytes) at {$tempFile}");
            return $tempFile;
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error("Admin login: Error saving temp snapshot - " . $e->getMessage());
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

    /**
     * Resolve IP Geolocation & Google Maps URL
     */
    private function getIpLocation(string $ip): array
    {
        if (in_array($ip, ['127.0.0.1', '::1']) || str_starts_with($ip, '192.168.') || str_starts_with($ip, '10.')) {
            return [
                'location' => 'Localhost / Internal Dev Environment',
                'isp' => 'Local Network',
                'map_url' => 'https://www.google.com/maps?q=50.3755,-4.1427'
            ];
        }

        try {
            $response = \Illuminate\Support\Facades\Http::timeout(3)->get("http://ip-api.com/json/{$ip}");
            if ($response->successful()) {
                $data = $response->json();
                if (($data['status'] ?? '') === 'success') {
                    $city = $data['city'] ?? '';
                    $region = $data['regionName'] ?? '';
                    $country = $data['country'] ?? '';
                    $isp = $data['isp'] ?? ($data['org'] ?? 'Unknown ISP');
                    $lat = $data['lat'] ?? null;
                    $lon = $data['lon'] ?? null;

                    $parts = array_filter([$city, $region, $country]);
                    $locStr = !empty($parts) ? implode(', ', $parts) : 'Location Unknown';

                    $mapUrl = ($lat && $lon) 
                        ? "https://www.google.com/maps?q={$lat},{$lon}"
                        : "https://www.google.com/maps/search/" . urlencode($locStr);

                    return [
                        'location' => $locStr,
                        'isp' => $isp,
                        'map_url' => $mapUrl
                    ];
                }
            }
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::warning("IP Geolocation error for {$ip}: " . $e->getMessage());
        }

        return [
            'location' => 'Location Unavailable',
            'isp' => 'Unknown ISP',
            'map_url' => "https://www.google.com/maps/search/" . urlencode($ip)
        ];
    }
}
