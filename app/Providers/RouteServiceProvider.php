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
            return Limit::perMinute(60)
                ->by($request->user()?->id ?: $request->ip());
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

}
