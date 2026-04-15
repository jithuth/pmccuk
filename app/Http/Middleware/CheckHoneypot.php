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
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // If the 'hp_field' is filled, it's a bot
        if ($request->filled('pmcc_identity_confirm')) {
            Log::warning('Honeypot triggered from IP: ' . $request->ip());
            return response()->json(['success' => false, 'message' => 'Spam detected.'], 422);
        }

        return $next($request);
    }
}
