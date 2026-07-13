<?php

namespace WPMCP\Tools\Rest;

if (! defined('ABSPATH')) {
    exit;
}

/**
 * Read-only: enumerate the routes registered on the current WP REST server
 * (rest_get_server()->get_routes()), each reduced to its route path, the
 * allowed HTTP methods, and a short summary built from the route's own
 * registered args (never executes any route).
 *
 * An optional 'namespace' (substring match, e.g. "wp/v2") and/or 'search'
 * (substring match against the route path) narrow the result. 'limit' caps
 * the number of rows returned (default 50, hard max 200) so a caller cannot
 * force a dump of the entire route table in one call.
 */
class List_Rest_Routes
{
    private const DEFAULT_LIMIT = 50;
    private const MAX_LIMIT     = 200;

    public function handle(array $args): array
    {
        $namespace = isset($args['namespace']) ? (string) $args['namespace'] : '';
        $search    = isset($args['search']) ? (string) $args['search'] : '';
        $limit     = isset($args['limit']) ? (int) $args['limit'] : self::DEFAULT_LIMIT;
        $limit     = max(1, min($limit, self::MAX_LIMIT));

        $server = rest_get_server();
        $all    = $server->get_routes();

        // get_routes() returns routes in plugin registration order, not any
        // meaningful order: on a site with several REST-heavy plugins, a
        // well-known core route like /wp/v2/posts can sit hundreds of
        // entries deep behind another plugin's namespace. Sort by route path
        // so results are deterministic and a 'namespace' filter (e.g.
        // "wp/v2") reliably groups a namespace together instead of depending
        // on registration order.
        $keys = array_keys($all);
        sort($keys);

        $total  = 0;
        $routes = [];

        foreach ($keys as $route) {
            if ('' !== $namespace && false === strpos($route, $namespace)) {
                continue;
            }
            if ('' !== $search && false === strpos($route, $search)) {
                continue;
            }

            $total++;
            if (count($routes) >= $limit) {
                continue;
            }

            $handlers = $all[ $route ];
            $routes[] = [
                'route'   => $route,
                'methods' => self::methods_for($handlers),
                'summary' => self::summary_for($route, $handlers),
            ];
        }

        return [
            'routes'    => $routes,
            'total'     => $total,
            'limit'     => $limit,
            'truncated' => $total > count($routes),
        ];
    }

    /**
     * Collect the union of HTTP methods allowed across every handler
     * registered for a route, as a sorted list of method names.
     */
    private static function methods_for(array $handlers): array
    {
        $methods = [];
        foreach ($handlers as $handler) {
            $handler_methods = $handler['methods'] ?? [];
            if (is_string($handler_methods)) {
                $methods[] = $handler_methods;
                continue;
            }
            if (is_array($handler_methods)) {
                foreach (array_keys($handler_methods) as $method) {
                    $methods[] = (string) $method;
                }
            }
        }
        $methods = array_values(array_unique($methods));
        sort($methods);
        return $methods;
    }

    /**
     * Build a short human-readable summary of a route from its first
     * handler's registered arg names, e.g. "GET,POST /wp/v2/posts (args:
     * page, per_page, search, ...)". Falls back to just the methods when a
     * handler declares no args.
     */
    private static function summary_for(string $route, array $handlers): string
    {
        $first = reset($handlers);
        $args  = is_array($first) && isset($first['args']) && is_array($first['args'])
            ? array_keys($first['args'])
            : [];

        $methods = implode(',', self::methods_for($handlers));

        if (empty($args)) {
            return "{$methods} {$route}";
        }

        $shown = array_slice($args, 0, 8);
        $more  = count($args) > 8 ? ', ...' : '';

        return "{$methods} {$route} (args: " . implode(', ', $shown) . "{$more})";
    }
}
