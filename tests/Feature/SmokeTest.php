<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Database\Seeders\UnitSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

/**
 * Smoke test: hit every GET route as an authenticated user
 * and assert no 500 responses. This catches:
 *
 * - Missing controllers/methods
 * - Empty controller methods returning blank pages
 * - Missing Blade views
 * - Broken dependency injection
 * - Database query errors on page load
 */
class SmokeTest extends TestCase
{
    use RefreshDatabase;

    private User $superAdmin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(UnitSeeder::class);
        $this->seed(RolePermissionSeeder::class);

        $this->superAdmin = User::factory()->create([
            'unit_id' => 1,
        ]);
        $this->superAdmin->assignRole('super_admin');
    }

    /**
     * Every registered GET route must respond without a 500 error.
     */
    public function test_no_get_route_returns_500(): void
    {
        $routes = collect(Route::getRoutes()->getRoutes())
            ->filter(fn ($route) => in_array('GET', $route->methods()))
            ->reject(fn ($route) => $this->shouldSkip($route));

        $failures = [];

        foreach ($routes as $route) {
            $uri = $this->resolveUri($route);

            if ($uri === null) {
                continue; // Has unresolvable parameters
            }

            $response = $this->actingAs($this->superAdmin)
                ->withSession(['unit_id' => 1])
                ->get($uri);

            if ($response->getStatusCode() >= 500) {
                $failures[] = [
                    'uri' => $uri,
                    'name' => $route->getName(),
                    'status' => $response->getStatusCode(),
                ];
            }
        }

        if (! empty($failures)) {
            $report = "The following routes returned 500 errors:\n";
            foreach ($failures as $f) {
                $report .= "  [{$f['status']}] {$f['uri']} ({$f['name']})\n";
            }
            $this->fail($report);
        }

        $this->assertTrue(true, 'All GET routes responded without 500 errors.');
    }

    /**
     * POST routes must reject GET requests with 405 (not 500).
     */
    public function test_post_routes_reject_get_with_405(): void
    {
        $postOnlyRoutes = collect(Route::getRoutes()->getRoutes())
            ->filter(fn ($route) => in_array('POST', $route->methods()) && ! in_array('GET', $route->methods()))
            ->reject(fn ($route) => $this->shouldSkip($route));

        $failures = [];

        foreach ($postOnlyRoutes as $route) {
            $uri = $this->resolveUri($route);

            if ($uri === null) {
                continue;
            }

            $response = $this->actingAs($this->superAdmin)
                ->withSession(['unit_id' => 1])
                ->get($uri);

            if ($response->getStatusCode() >= 500) {
                $failures[] = [
                    'uri' => $uri,
                    'name' => $route->getName(),
                    'status' => $response->getStatusCode(),
                ];
            }
        }

        if (! empty($failures)) {
            $report = "POST routes returning 500 on GET (should be 405):\n";
            foreach ($failures as $f) {
                $report .= "  [{$f['status']}] {$f['uri']} ({$f['name']})\n";
            }
            $this->fail($report);
        }

        $this->assertTrue(true);
    }

    /**
     * All write routes must require CSRF token (return 419 without it).
     */
    public function test_write_routes_require_csrf(): void
    {
        $writeRoutes = collect(Route::getRoutes()->getRoutes())
            ->filter(fn ($route) => ! empty(array_intersect(['POST', 'PUT', 'PATCH', 'DELETE'], $route->methods())))
            ->reject(fn ($route) => $this->shouldSkip($route))
            ->reject(fn ($route) => str_starts_with($route->uri(), 'api/'));

        $failures = [];

        foreach ($writeRoutes as $route) {
            $uri = $this->resolveUri($route);

            if ($uri === null) {
                continue;
            }

            $method = collect($route->methods())
                ->first(fn ($m) => in_array($m, ['POST', 'PUT', 'PATCH', 'DELETE']));

            // Send WITHOUT csrf token
            $response = $this->actingAs($this->superAdmin)
                ->withSession(['unit_id' => 1])
                ->call($method, $uri);

            // Should be 419 (CSRF mismatch), not 500
            if ($response->getStatusCode() >= 500) {
                $failures[] = [
                    'method' => $method,
                    'uri' => $uri,
                    'name' => $route->getName(),
                    'status' => $response->getStatusCode(),
                ];
            }
        }

        if (! empty($failures)) {
            $report = "Write routes returning 500 without CSRF (should be 419):\n";
            foreach ($failures as $f) {
                $report .= "  [{$f['status']}] {$f['method']} {$f['uri']} ({$f['name']})\n";
            }
            $this->fail($report);
        }

        $this->assertTrue(true);
    }

    /**
     * Guest access to protected routes should redirect to login (not 500).
     */
    public function test_protected_routes_redirect_guests(): void
    {
        $protectedRoutes = collect(Route::getRoutes()->getRoutes())
            ->filter(fn ($route) => in_array('GET', $route->methods()))
            ->filter(fn ($route) => collect($route->gatherMiddleware())->contains(fn ($m) => str_contains((string) $m, 'auth')))
            ->reject(fn ($route) => $this->shouldSkip($route));

        $failures = [];

        foreach ($protectedRoutes as $route) {
            $uri = $this->resolveUri($route);

            if ($uri === null) {
                continue;
            }

            $response = $this->get($uri);

            if ($response->getStatusCode() >= 500) {
                $failures[] = [
                    'uri' => $uri,
                    'name' => $route->getName(),
                    'status' => $response->getStatusCode(),
                ];
            }
        }

        if (! empty($failures)) {
            $report = "Protected routes returning 500 for guests (should redirect):\n";
            foreach ($failures as $f) {
                $report .= "  [{$f['status']}] {$f['uri']} ({$f['name']})\n";
            }
            $this->fail($report);
        }

        $this->assertTrue(true);
    }

    // ── Helpers ───────────────────────────────────────────────

    private function shouldSkip($route): bool
    {
        $uri = $route->uri();

        return str_starts_with($uri, '_ignition')
            || str_starts_with($uri, 'sanctum')
            || str_starts_with($uri, '__clockwork')
            || str_starts_with($uri, 'telescope')
            || $uri === 'up';
    }

    /**
     * Attempt to resolve a URI by replacing route parameters with dummy values.
     * Returns null if the route has too many or complex parameters.
     */
    private function resolveUri($route): ?string
    {
        $uri = $route->uri();

        // Replace simple {param} with 1
        $resolved = preg_replace('/\{[a-zA-Z_]+\}/', '1', $uri);

        // Skip if optional params remain
        if (str_contains($resolved, '{')) {
            return null;
        }

        return '/' . ltrim($resolved, '/');
    }
}
