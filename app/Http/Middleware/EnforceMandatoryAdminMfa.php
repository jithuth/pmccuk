<?php

namespace App\Http\Middleware;

use App\Models\SecurityPolicy;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnforceMandatoryAdminMfa
{
    /**
     * Enforce mandatory MFA configuration for privileged administrators.
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (Auth::guard('admin')->check()) {
            $admin = Auth::guard('admin')->user();
            $mandatoryMfa = SecurityPolicy::get('admin_mandatory_mfa', true);

            if ($mandatoryMfa && empty($admin->two_factor_enabled)) {
                // If not enabled, only allow 2FA setup, setup confirmation, or logout
                if (!$request->routeIs('admin.2fa.setup*') && !$request->routeIs('admin.logout')) {
                    if ($request->expectsJson()) {
                        return response()->json([
                            'success' => false,
                            'message' => 'Mandatory MFA enrollment required before accessing admin controls.',
                            'redirect' => route('admin.2fa.setup')
                        ], 403);
                    }
                    return redirect()->route('admin.2fa.setup')
                        ->with('warning', 'Mandatory Security Requirement: You must configure Two-Factor Authentication (MFA) before accessing administrative dashboards.');
                }
            }
        }

        return $next($request);
    }
}
