<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SecurityHeaders
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        // Prevent page from being displayed in an iframe (Clickjacking protection)
        $response->headers->set('X-Frame-Options', 'SAMEORIGIN');
        
        // Prevent browser from trying to guess MIME types
        $response->headers->set('X-Content-Type-Options', 'nosniff');
        
        // Enable basic XSS filter
        $response->headers->set('X-XSS-Protection', '1; mode=block');
        
        // Control how much referrer information is passed
        $response->headers->set('Referrer-Policy', 'no-referrer-when-downgrade');
        
        // Restrict where scripts/styles/images can be loaded from
        // Added exceptions for common CDNs used in the project
        $csp = "default-src 'self' https: data:; " .
               "script-src 'self' 'unsafe-inline' 'unsafe-eval' https://cdn.tailwindcss.com https://cdn.jsdelivr.net https://code.jquery.com https://cdnjs.cloudflare.com https://unpkg.com; " .
               "script-src-elem 'self' 'unsafe-inline' https://cdn.tailwindcss.com https://cdn.jsdelivr.net https://code.jquery.com https://cdnjs.cloudflare.com https://unpkg.com; " .
               "style-src 'self' 'unsafe-inline' https://fonts.googleapis.com https://cdn.jsdelivr.net https://cdnjs.cloudflare.com https://unpkg.com; " .
               "font-src 'self' data: https://fonts.gstatic.com https://cdnjs.cloudflare.com https://cdn.jsdelivr.net; " .
               "img-src 'self' data: https:; " .
               "connect-src 'self' https:;";
        
        $response->headers->set('Content-Security-Policy', $csp);

        return $response;
    }
}
