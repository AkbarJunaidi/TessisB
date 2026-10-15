<?php

return [

    // Content-Security-Policy: off | report (default, tidak memblokir) | enforce.
    'csp' => env('SECURITY_CSP', 'report'),

    // Strict-Transport-Security, hanya dikirim pada permintaan HTTPS.
    'hsts' => (bool) env('SECURITY_HSTS', true),

];
