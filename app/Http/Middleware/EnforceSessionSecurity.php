<?php

namespace App\Http\Middleware;

use App\Services\Auth\DeviceSessionService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnforceSessionSecurity
{
    /**
     * Terminate revoked sessions and keep session heartbeat updated.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $isAdmin = Auth::guard('admin')->check();
        $isMember = Auth::guard('member')->check();

        if ($isAdmin || $isMember) {
            // Check if this specific session was revoked remotely
            if (DeviceSessionService::isCurrentSessionRevoked()) {
                $guard = $isAdmin ? 'admin' : 'member';
                Auth::guard($guard)->logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();

                $redirect = $isAdmin ? route('admin.login') : route('member.login');

                if ($request->expectsJson()) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Your session was remotely terminated or expired. Please sign in again.',
                        'redirect' => $redirect
                    ], 401);
                }

                return redirect($redirect)->with('error', 'Your session was terminated or revoked from another device.');
            }

            // Update session heartbeat
            DeviceSessionService::touchCurrentSession();
        }

        return $next($request);
    }
}
