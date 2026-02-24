<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class UpdateLastSeen
{
  public function handle(Request $request, Closure $next)
  {
    $response = $next($request);

    // Update user presence
    if ($request->user()) {
      $request->user()->forceFill(['last_seen_at' => now()])->saveQuietly();
    }

    // If bus uses auth too (Bus extends Authenticatable), and you have a bus guard:
    // $bus = auth('bus')->user();
    // if ($bus) { $bus->forceFill(['last_seen_at' => now()])->saveQuietly(); }

    return $response;
  }
}
