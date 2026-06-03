<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;

class RouteServiceProvider extends ServiceProvider
{
    /**
     * The path to your application's "home" route.
     *
     * Typically, users are redirected here after authentication.
     *
     * @var string
     */
    public const HOME = '/';

    /**
     * Define your route model bindings, pattern filters, and other route configuration.
     */
    public function boot(): void
    {
        RateLimiter::for('api', function (Request $request) {
            if ($request->is('api/video-calls*')) {
                $perMinute = max(120, (int) config('transport.api.video_calls_signal_requests_per_minute', 3000));

                return Limit::perMinute($perMinute)
                    ->by($this->resolveThrottleIdentity($request, 'video-calls'));
            }

            if ($request->is('api/bus/gps')) {
                $perMinute = max(60, (int) config('transport.api.bus_gps_requests_per_minute', 60));

                return Limit::perMinute($perMinute)
                    ->by($this->resolveThrottleIdentity($request, 'bus-gps-api'));
            }

            $perMinute = max(60, (int) config('transport.api.requests_per_minute', 120));

            return Limit::perMinute($perMinute)
                ->by($this->resolveThrottleIdentity($request, 'api'));
        });

        RateLimiter::for('bus-api', function (Request $request) {
            $perMinute = max(10, (int) config('transport.api.bus_requests_per_minute', 60));
            $identity = $request->user()?->id ?: $request->ip();

            return Limit::perMinute($perMinute)->by("bus-api:{$identity}");
        });

        RateLimiter::for('bus-gps', function (Request $request) {
            $perMinute = max(10, (int) config('transport.api.bus_gps_requests_per_minute', 60));
            $identity = $request->user()?->id ?: $request->ip();

            return [
                Limit::perMinute($perMinute)->by("bus-gps:{$identity}"),
                Limit::perMinute($perMinute * 5)->by('bus-gps-ip:'.$request->ip()),
            ];
        });

        $this->routes(function () {
            Route::middleware('api')
                ->prefix('api')
                ->group(base_path('routes/api.php'));

            Route::middleware('web')
                ->group(base_path('routes/web.php'));
        });
    }

    private function resolveThrottleIdentity(Request $request, string $prefix): string
    {
        if ($user = $request->user()) {
            return "{$prefix}:user:".get_class($user).":{$user->getAuthIdentifier()}";
        }

        $bearerToken = $request->bearerToken();
        if (is_string($bearerToken) && $bearerToken !== '') {
            return "{$prefix}:token:".sha1($bearerToken);
        }

        $sessionId = $request->hasSession() ? $request->session()->getId() : null;
        if (is_string($sessionId) && $sessionId !== '') {
            return "{$prefix}:session:{$sessionId}";
        }

        return "{$prefix}:ip:".$request->ip();
    }

}
