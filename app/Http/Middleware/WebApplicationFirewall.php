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
            // Retrieve settings
            $wafEnabledSetting = Setting::where('setting_key', 'waf_enabled')->first();
            if ($wafEnabledSetting) {
                $wafEnabled = $wafEnabledSetting->setting_value;
            }

            $blocklistSetting = Setting::where('setting_key', 'waf_ip_blocklist')->first();
            if ($blocklistSetting) {
                $blocklistStr = $blocklistSetting->setting_value;
            }
        } catch (\Exception $e) {
            Log::error('WAF config fetch error: ' . $e->getMessage());
        }

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

                    // Dispatch Telegram alert
                    try {
                        \App\Services\TelegramService::sendMessage(
                            "🛡️ 🚨 <b>WAF Alert: Attack Blocked</b>\n\n" .
                            "🌐 <b>IP Address:</b> {$ip}\n" .
                            "🔥 <b>Attack Type:</b> {$ruleName}\n" .
                            "📍 <b>Request Source:</b> " . ucfirst($source) . "\n" .
                            "🕵️ <b>User Agent:</b> " . htmlspecialchars($request->userAgent())
                        );
                    } catch (\Exception $e) {}

                    return response('Forbidden: Request blocked by Web Application Firewall (WAF).', 403);
                }
            }
        }

        return $next($request);
    }
}
