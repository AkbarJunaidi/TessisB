<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Header keamanan untuk semua respons web. Pengaturannya di config/security.php.
 * CSP default mode "report" (hanya dilaporkan di console browser, tidak memblokir);
 * ganti SECURITY_CSP=enforce di .env setelah console bersih dari pelanggaran.
 */
class SecurityHeaders
{
    private const CSP = [
        "default-src 'self'",
        "script-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net",
        "style-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net https://fonts.googleapis.com",
        "font-src 'self' data: https://cdn.jsdelivr.net https://fonts.gstatic.com",
        "img-src 'self' data: blob: https://tile.openstreetmap.org https://cdn.jsdelivr.net",
        "media-src 'self' blob:",
        "connect-src 'self' https://nominatim.openstreetmap.org",
        "frame-src 'self'",
        "worker-src 'self' blob:",
        "manifest-src 'self'",
        "object-src 'none'",
        "base-uri 'self'",
        "form-action 'self'",
        "frame-ancestors 'self'",
    ];

    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);
        $headers = $response->headers;

        $headers->set('X-Frame-Options', 'SAMEORIGIN');
        $headers->set('X-Content-Type-Options', 'nosniff');
        $headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        $headers->set('Permissions-Policy', 'camera=(self), microphone=(), geolocation=(), payment=()');

        // HSTS hanya lewat HTTPS (X-Forwarded-Proto dari Cloudflare dipercaya di bootstrap/app.php).
        if (config('security.hsts') && $request->isSecure()) {
            $headers->set('Strict-Transport-Security', 'max-age=15552000');
        }

        $mode = config('security.csp');
        if ($mode === 'enforce' || $mode === 'report') {
            $name = $mode === 'enforce' ? 'Content-Security-Policy' : 'Content-Security-Policy-Report-Only';
            $headers->set($name, implode('; ', self::CSP));
        }

        return $response;
    }
}
