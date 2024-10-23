<?php

namespace Tests\Unit\Routes;

use Tests\TestCase;

class CompartmentRoutesTest extends TestCase
{
    public function test_compartment_routes_are_registered()
    {
        $routes = $this->getRoutes();
        
        $this->assertContains('api/v1/fleet-ops/compartments', $routes);
        $this->assertContains('api/v1/fleet-ops/compartments/{id}', $routes);
    }

    private function getRoutes()
    {
        $routes = [];
        foreach (\Route::getRoutes() as $route) {
            $routes[] = $route->uri();
        }
        return $routes;
    }
}
