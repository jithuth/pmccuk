<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SharedHostingCompatibility
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        // 🚨 SKIP if the response is a direct file (like our /img route)
        if ($response instanceof \Symfony\Component\HttpFoundation\BinaryFileResponse) {
            return $response;
        }

        if (!method_exists($response, 'getContent')) {
            return $response;
        }

        $content = $response->getContent();
        
        // Only rewrite if it's actual HTML content
        if (is_string($content) && str_contains($response->headers->get('Content-Type', ''), 'text/html')) {
            // First, protect any already correct /img?p= links
            // Then replace uncorrected storage/ and uploads/ links
            $content = str_replace(['/storage/', 'storage/'], '/img?p=', $content);
            $content = str_replace(['/uploads/', 'uploads/'], '/img?p=', $content);
            
            // Clean up any accidental double img?p=img?p=
            $content = str_replace('/img?p=/img?p=', '/img?p=', $content);
            $content = str_replace('img?p=/', 'img?p=', $content);
            
            return $response->setContent($content);
        }

        return $response;
    }
}
