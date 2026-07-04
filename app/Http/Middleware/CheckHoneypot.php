<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class CheckHoneypot
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // If the 'hp_field' is filled, it's a bot
        if ($request->filled('pmcc_identity_confirm')) {
            Log::warning('Honeypot triggered from IP: ' . $request->ip());
            try {
                \App\Services\TelegramService::sendMessage(
                    "🚨 <b>Security Alert: Honeypot Triggered</b>\n\n" .
                    "🌐 <b>IP Address:</b> " . $request->ip() . "\n" .
                    "🗺️ <b>User Agent:</b> " . htmlspecialchars($request->userAgent()) . "\n" .
                    "📍 <b>Request URI:</b> " . htmlspecialchars($request->getRequestUri())
                );
            } catch (\Exception $e) {}
            return response()->json(['success' => false, 'message' => 'Spam detected.'], 422);
        }

        return $next($request);
    }
}
