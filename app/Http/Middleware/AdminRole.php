<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AdminRole
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @param  string  ...$roles
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function handle(Request $request, Closure $next, ...$roles): Response
    {
        $user = auth('admin')->user();

        if (!$user) {
            return redirect()->route('admin.login');
        }

        // If the logged-in user's role is in the allowed list, proceed
        if (in_array($user->role, $roles)) {
            return $next($request);
        }

        // Redirect staff users back to their operational Counter Dashboard
        if ($user->role === 'staff') {
            return redirect()->route('admin.staff.dashboard')
                ->with('error', 'Access Denied: You only have permission to access the Gate Control Counter Dashboard.');
        }

        abort(403, 'Unauthorized access: Your role (' . $user->role . ') is not allowed to access this resource.');
    }
}
