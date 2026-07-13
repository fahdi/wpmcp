<?php

namespace WPMCP\Tests\Free\Rest;

use WPMCP\Tools\Rest\List_Rest_Routes;

class ListRestRoutesTest extends \WP_UnitTestCase
{
    public function test_returns_a_known_core_route(): void
    {
        // On a site with several REST-heavy plugins active, get_routes() can
        // return hundreds of entries, so a known core route is not
        // guaranteed to land within a small default cap on namespace alone
        // (e.g. wp/v2 itself carries 100+ routes on a plugin-heavy site).
        // Combine namespace and search, the documented way to reliably
        // surface one specific route.
        $out = (new List_Rest_Routes())->handle(['namespace' => 'wp/v2', 'search' => '/wp/v2/posts']);

        $this->assertArrayHasKey('routes', $out);

        $routes = array_column($out['routes'], 'route');
        $this->assertContains('/wp/v2/posts', $routes);
    }

    public function test_each_route_reports_methods_and_a_summary(): void
    {
        $out = (new List_Rest_Routes())->handle(['namespace' => 'wp/v2', 'search' => '/wp/v2/posts']);

        $posts_route = null;
        foreach ($out['routes'] as $row) {
            if ('/wp/v2/posts' === $row['route']) {
                $posts_route = $row;
                break;
            }
        }

        $this->assertNotNull($posts_route);
        $this->assertIsArray($posts_route['methods']);
        $this->assertContains('GET', $posts_route['methods']);
        $this->assertIsString($posts_route['summary']);
        $this->assertNotSame('', $posts_route['summary']);
    }
}
