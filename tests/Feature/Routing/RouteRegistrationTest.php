<?php

namespace Tests\Feature\Routing;

use Tests\TestCase;

class RouteRegistrationTest extends TestCase
{
    public function test_live_bus_routes_are_registered_for_admin_and_company(): void
    {
        $this->assertSame('/admin/api/live-buses', route('admin.api.live-buses', [], false));
        $this->assertSame('/company/api/live-buses', route('company.api.live-buses', [], false));
    }

    public function test_company_assignments_index_route_is_registered_once(): void
    {
        $routes = collect(app('router')->getRoutes()->getRoutes())
            ->filter(function ($route) {
                return $route->uri() === 'company/assignments'
                    && in_array('GET', $route->methods(), true)
                    && in_array('HEAD', $route->methods(), true);
            });

        $this->assertCount(1, $routes);
        $this->assertSame('company.assignments.index', $routes->first()->getName());
    }

    public function test_bus_gps_route_has_dedicated_rate_limit_middleware(): void
    {
        $route = collect(app('router')->getRoutes()->getRoutes())
            ->first(function ($route) {
                return $route->uri() === 'api/bus/gps'
                    && in_array('POST', $route->methods(), true);
            });

        $this->assertNotNull($route, 'Expected POST api/bus/gps route to be registered.');

        $middlewares = $route->gatherMiddleware();

        $this->assertContains('auth:sanctum', $middlewares);
        $this->assertContains('throttle:bus-api', $middlewares);
        $this->assertContains('throttle:bus-gps', $middlewares);
        $this->assertContains('abilities:bus:gps', $middlewares);
    }
}
