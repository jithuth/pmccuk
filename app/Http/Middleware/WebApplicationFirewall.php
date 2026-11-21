<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Models\Setting;
use App\Models\ActivityLog;
use Symfony\Component\HttpFoundation\Response;

class WebApplicationFirewall
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $wafEnabled = '0';
        $blocklistStr = '';

        try {
            $wafConfig = \Illuminate\Support\Facades\Cache::remember('waf_config', 180, function() {
                $enabled = '0';
                $blocklist = '';
                try {
                    $wafEnabledSetting = Setting::where('setting_key', 'waf_enabled')->first();
                    if ($wafEnabledSetting) {
                        $enabled = $wafEnabledSetting->setting_value;
                        try { $enabled = decrypt($enabled); } catch (\Throwable $e) {}
                    }

                    $blocklistSetting = Setting::where('setting_key', 'waf_ip_blocklist')->first();
                    if ($blocklistSetting) {
                        $blocklist = $blocklistSetting->setting_value;
                        try { $blocklist = decrypt($blocklist); } catch (\Throwable $e) {}
                    }
                } catch (\Throwable $e) {}

                return ['enabled' => $enabled, 'blocklist' => $blocklist];
            });

            $wafEnabled = $wafConfig['enabled'] ?? '0';
            $blocklistStr = $wafConfig['blocklist'] ?? '';
        } catch (\Throwable $e) {}

        $ip = $request->ip();

        // 1. IP Blocklist Check
        if (!empty($blocklistStr)) {
            $blockedIps = preg_split('/[\s,;]+/', trim($blocklistStr));
            $blockedIps = array_filter(array_map('trim', $blockedIps));
            if (in_array($ip, $blockedIps)) {
                Log::warning("WAF Blocked IP Access attempt: {$ip}");
                return response('Forbidden: Access is denied by Web Application Firewall (IP Blocked).', 403);
            }
        }

        // Exclude Telegram Webhook and API endpoints from WAF payload inspection
        if ($request->is('telegram/*') || $request->is('api/telegram/*')) {
            return $next($request);
        }

        // If WAF check is not enabled, skip payload inspection
        if ($wafEnabled !== '1') {
            return $next($request);
        }

        // 2. Request payload inspection (Query params + Body params + Request URI path)
        $payloads = [
            'query' => $request->query(),
            'body' => $request->post(),
            'url' => $request->getRequestUri()
        ];

        // Core Attack Signatures
        $rules = [
            'SQL Injection' => '/(union\s+select|select\s+.+\s+from|insert\s+into|delete\s+from|drop\s+table|update\s+.+\s+set)/i',
            'Cross-Site Scripting (XSS)' => '/(<script|javascript:|onerror\s*=|onload\s*=)/i',
            'Path Traversal' => '/(\.\.\/|\.\.\\\\)/i',
            'Remote Code Execution (RCE)' => '/(exec\s*\(|system\s*\(|eval\s*\(|passthru\s*\()/i',
        ];

        foreach ($payloads as $source => $data) {
            $flatData = is_array($data) ? json_encode($data) : $data;
            if (empty($flatData)) continue;

            foreach ($rules as $ruleName => $pattern) {
                if (preg_match($pattern, $flatData)) {
                    // Log Blocked Attack in activity logs
                    try {
                        ActivityLog::create([
                            'user_type' => 'guest',
                            'action' => 'waf_blocked',
                            'details' => "WAF Blocked Attack: {$ruleName} from IP: {$ip}. Payload: " . substr($flatData, 0, 200),
                            'ip_address' => $ip
                        ]);
                    } catch (\Exception $e) {
                        Log::error('WAF failed to write block log: ' . $e->getMessage());
                    }

                    // Auto-ban malicious IP: permanently add to waf_ip_blocklist and flush cache
                    if ($ip && !in_array($ip, ['127.0.0.1', '::1', '141.136.39.186'])) {
                        try {
                            $blockSetting = Setting::where('setting_key', 'waf_ip_blocklist')->first();
                            $existingStr = $blockSetting ? $blockSetting->setting_value : '';
                            try { $existingStr = decrypt($existingStr); } catch (\Throwable $e) {}

                            $existingIps = preg_split('/[\s,;]+/', trim($existingStr));
                            $cleanIps = array_filter(array_map('trim', $existingIps));

                            if (!in_array($ip, $cleanIps)) {
                                $cleanIps[] = $ip;
                                $newBlocklist = implode("\n", array_values(array_unique($cleanIps)));
                                Setting::updateOrCreate(
                                    ['setting_key' => 'waf_ip_blocklist'],
                                    ['setting_value' => $newBlocklist]
                                );
                                \Illuminate\Support\Facades\Cache::forget('waf_config');
                                \Illuminate\Support\Facades\Cache::forget('pmcc_waf_config');
                                Log::warning("WAF Auto-Banned Attacker IP: {$ip} for {$ruleName}");
                            }
                        } catch (\Throwable $banErr) {
                            Log::error("WAF Auto-Ban Failed for {$ip}: " . $banErr->getMessage());
                        }
                    }

                    // Dispatch Telegram alert (Throttled per IP + Rule for 15 minutes to prevent alert storms)
                    try {
                        $wafAlertKey = 'waf_alert_sent_' . md5($ip . ':' . $ruleName);
                        if (!\Illuminate\Support\Facades\Cache::has($wafAlertKey)) {
                            \Illuminate\Support\Facades\Cache::put($wafAlertKey, true, 900);
                            \App\Services\TelegramService::sendMessage(
                                "🛡️ 🚨 <b>WAF Alert: Attack Blocked</b>\n\n" .
                                "🌐 <b>IP Address:</b> {$ip}\n" .
                                "🔥 <b>Attack Type:</b> {$ruleName}\n" .
                                "📍 <b>Request Source:</b> " . ucfirst($source) . "\n" .
                                "🕵️ <b>User Agent:</b> " . htmlspecialchars($request->userAgent() ?: 'Unknown / Bot')
                            );
                        }
                    } catch (\Exception $e) {}

                    return response('Forbidden: Request blocked by Web Application Firewall (WAF).', 403);
                }
            }
        }

        return $next($request);
    }
}
