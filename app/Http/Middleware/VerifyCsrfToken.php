<?php

namespace App\Http\Middleware;

use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken as Middleware;

class VerifyCsrfToken extends Middleware
{
    /**
     * The URIs that should be excluded from CSRF verification.
     *
     * @var array<int, string>
     */
    protected $except = [
        // Route-map directions endpoint is called by JS fetch and does not mutate DB state.
        // Excluding it prevents intermittent 419 token mismatch fallbacks on long-lived sessions.
        'admin/api/routes/directions',
    ];
}
