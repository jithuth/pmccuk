<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureMfaCompleted
{
    /**
     * Prevent accessing protected dashboard/privacy routes while in a pending MFA state.
     */
    public function handle(Request $request, Closure $next, ?string $guard = null): Response
    {
        // 1. Check Member Pending MFA
        if (session('pending_mfa_guard') === 'member' && session('pending_mfa_member_id')) {
            $isMfaRoute = $request->is('member/mfa*') || $request->is('member/logout*') || ($request->route() && ($request->routeIs('member.mfa.*') || $request->routeIs('member.logout*')));
            if (!$isMfaRoute) {
                if ($request->expectsJson()) {
                    return response()->json([
                        'success' => false,
                        'message' => 'MFA verification required.',
                        'redirect' => route('member.mfa.challenge')
                    ], 403);
                }
                return redirect()->route('member.mfa.challenge');
            }
        }

        // 2. Check Admin Pending 2FA
        if (session('2fa_admin_id')) {
            $isAdmin2faRoute = $request->is('admin/2fa*') || $request->is('admin/logout*') || ($request->route() && ($request->routeIs('admin.2fa.*') || $request->routeIs('admin.logout*')));
            if (!$isAdmin2faRoute) {
                if ($request->expectsJson()) {
                    return response()->json([
                        'success' => false,
                        'message' => '2FA verification required.',
                        'redirect' => route('admin.2fa.challenge')
                    ], 403);
                }
                return redirect()->route('admin.2fa.challenge');
            }
        }

        return $next($request);
    }
}
