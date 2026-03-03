<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Routing\Route;
use Illuminate\Routing\Router;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use ReflectionClass;
use ReflectionMethod;
use Throwable;

/**
 * Systematic route integrity auditor.
 *
 * Detects:
 * - Routes pointing to missing controllers or methods
 * - Empty controller methods (placeholder stubs)
 * - Routes with no middleware protection
 * - Duplicate route URIs with conflicting methods
 * - Unused controller methods (defined but never routed)
 * - Missing views referenced by controllers
 */
class AuditRoutes extends Command
{
    protected $signature = 'audit:routes
        {--fix : Show suggested fixes for each issue}
        {--json : Output results as JSON}
        {--severity=all : Filter by severity: critical, warning, info, all}';

    protected $description = 'Audit all registered routes for integrity issues';

    /** @var array<string, list<array{severity: string, message: string, fix: string}>> */
    private array $issues = [];

    private int $checkedCount = 0;

    public function handle(Router $router): int
    {
        $this->info('');
        $this->info('  SAKUMI Route Integrity Audit');
        $this->info('  ════════════════════════════');
        $this->info('');

        $routes = collect($router->getRoutes()->getRoutes());

        $this->auditMissingControllers($routes);
        $this->auditEmptyMethods($routes);
        $this->auditUnprotectedRoutes($routes);
        $this->auditDuplicateUris($routes);
        $this->auditUnusedControllerMethods($routes);
        $this->auditMissingViews($routes);

        return $this->renderResults();
    }

    /**
     * Check 1: Routes pointing to missing controllers or methods.
     */
    private function auditMissingControllers($routes): void
    {
        foreach ($routes as $route) {
            $this->checkedCount++;
            $action = $route->getAction();

            if (! isset($action['controller'])) {
                // Closure route — skip
                continue;
            }

            $controllerAction = $action['controller'];

            // Parse "App\Http\Controllers\Foo@bar"
            if (! str_contains($controllerAction, '@')) {
                // Invokable controller
                $class = Str::before($controllerAction, '@') ?: $controllerAction;
                $method = '__invoke';
            } else {
                [$class, $method] = explode('@', $controllerAction, 2);
            }

            if (! class_exists($class)) {
                $this->addIssue($route, 'critical', "Controller class not found: {$class}", "Create the controller: php artisan make:controller {$class}");
                continue;
            }

            if (! method_exists($class, $method)) {
                $this->addIssue($route, 'critical', "Method not found: {$class}@{$method}", "Add the method to the controller or remove the route.");
            }
        }
    }

    /**
     * Check 2: Controller methods that exist but are empty stubs.
     */
    private function auditEmptyMethods($routes): void
    {
        foreach ($routes as $route) {
            $action = $route->getAction();

            if (! isset($action['controller'])) {
                continue;
            }

            $controllerAction = $action['controller'];

            if (! str_contains($controllerAction, '@')) {
                $class = $controllerAction;
                $method = '__invoke';
            } else {
                [$class, $method] = explode('@', $controllerAction, 2);
            }

            if (! class_exists($class) || ! method_exists($class, $method)) {
                continue; // Already caught in check 1
            }

            try {
                $reflection = new ReflectionMethod($class, $method);
                $startLine = $reflection->getStartLine();
                $endLine = $reflection->getEndLine();
                $fileName = $reflection->getFileName();

                if (! $fileName || ! file_exists($fileName)) {
                    continue;
                }

                $lines = array_slice(file($fileName), $startLine, $endLine - $startLine - 1);
                $body = implode('', $lines);

                // Strip comments and whitespace
                $stripped = preg_replace('/\/\/.*$/m', '', $body);
                $stripped = preg_replace('/\/\*.*?\*\//s', '', $stripped);
                $stripped = trim($stripped);

                if ($stripped === '' || $stripped === '//') {
                    $relativePath = str_replace(base_path() . '/', '', $fileName);
                    $this->addIssue(
                        $route,
                        'critical',
                        "Empty method body: {$class}@{$method} ({$relativePath}:{$startLine})",
                        "Implement the method or remove the route. An empty method returns null, which renders a blank page."
                    );
                }
            } catch (Throwable) {
                // Reflection failed — skip
            }
        }
    }

    /**
     * Check 3: Routes missing authentication or authorization middleware.
     */
    private function auditUnprotectedRoutes($routes): void
    {
        $publicPatterns = [
            'health/*',
            'verify-receipt/*',
            'receipts/verify/*',
            'login',
            'logout',
            'forgot-password',
            'reset-password',
            'reset-password/*',
            'register',
            'api/*',
            '_ignition/*',
            'sanctum/*',
        ];

        foreach ($routes as $route) {
            $uri = $route->uri();
            $middlewares = collect($route->gatherMiddleware())->map(fn ($m) => is_string($m) ? $m : get_class($m));

            // Skip known public routes
            foreach ($publicPatterns as $pattern) {
                if (Str::is($pattern, $uri)) {
                    continue 2;
                }
            }

            $hasAuth = $middlewares->contains(fn ($m) => str_contains($m, 'auth') || str_contains($m, 'Auth'));

            if (! $hasAuth && $uri !== '/' && $uri !== '') {
                $this->addIssue(
                    $route,
                    'warning',
                    "Route has no auth middleware: {$uri}",
                    "Add ->middleware('auth') to protect this route."
                );
            }
        }
    }

    /**
     * Check 4: Duplicate route URIs with the same HTTP method.
     */
    private function auditDuplicateUris($routes): void
    {
        $seen = [];

        foreach ($routes as $route) {
            foreach ($route->methods() as $method) {
                if ($method === 'HEAD') {
                    continue;
                }

                $key = strtoupper($method) . ' ' . $route->uri();

                if (isset($seen[$key])) {
                    $this->addIssue(
                        $route,
                        'warning',
                        "Duplicate route: {$key} (also defined at {$seen[$key]})",
                        "Remove one of the duplicate route definitions."
                    );
                } else {
                    $action = $route->getAction();
                    $seen[$key] = $action['controller'] ?? 'Closure';
                }
            }
        }
    }

    /**
     * Check 5: Public methods in controllers that have no route pointing to them.
     */
    private function auditUnusedControllerMethods($routes): void
    {
        // Collect all routed controller@method pairs
        $routedMethods = [];
        foreach ($routes as $route) {
            $action = $route->getAction();
            if (isset($action['controller'])) {
                $routedMethods[$action['controller']] = true;
            }
        }

        // Scan all controller files
        $controllerPath = app_path('Http/Controllers');
        $controllerFiles = File::allFiles($controllerPath);

        $baseControllerMethods = ['middleware', 'getMiddleware', 'callAction', '__construct'];

        foreach ($controllerFiles as $file) {
            if ($file->getExtension() !== 'php') {
                continue;
            }

            $className = $this->resolveClassName($file->getRealPath());
            if (! $className || ! class_exists($className)) {
                continue;
            }

            // Skip base controller
            if ($className === 'App\\Http\\Controllers\\Controller') {
                continue;
            }

            try {
                $reflection = new ReflectionClass($className);

                if ($reflection->isAbstract()) {
                    continue;
                }

                foreach ($reflection->getMethods(ReflectionMethod::IS_PUBLIC) as $method) {
                    if ($method->getDeclaringClass()->getName() !== $className) {
                        continue; // Inherited
                    }

                    $name = $method->getName();

                    if (str_starts_with($name, '__') || in_array($name, $baseControllerMethods, true)) {
                        continue;
                    }

                    $fullAction = $className . '@' . $name;
                    if (! isset($routedMethods[$fullAction])) {
                        $relativePath = str_replace(base_path() . '/', '', $file->getRealPath());
                        $this->addIssue(
                            null,
                            'info',
                            "Unused controller method: {$fullAction} ({$relativePath}:{$method->getStartLine()})",
                            "Remove the method if it is dead code, or add a route if it should be reachable."
                        );
                    }
                }
            } catch (Throwable) {
                // Class introspection failed
            }
        }
    }

    /**
     * Check 6: Controllers returning view() calls where the Blade file is missing.
     */
    private function auditMissingViews($routes): void
    {
        foreach ($routes as $route) {
            $action = $route->getAction();

            if (! isset($action['controller'])) {
                continue;
            }

            $controllerAction = $action['controller'];

            if (! str_contains($controllerAction, '@')) {
                $class = $controllerAction;
                $method = '__invoke';
            } else {
                [$class, $method] = explode('@', $controllerAction, 2);
            }

            if (! class_exists($class) || ! method_exists($class, $method)) {
                continue;
            }

            try {
                $reflection = new ReflectionMethod($class, $method);
                $fileName = $reflection->getFileName();

                if (! $fileName || ! file_exists($fileName)) {
                    continue;
                }

                $startLine = $reflection->getStartLine();
                $endLine = $reflection->getEndLine();
                $lines = array_slice(file($fileName), $startLine - 1, $endLine - $startLine + 1);
                $body = implode('', $lines);

                // Find view() calls
                if (preg_match_all("/view\(\s*['\"]([^'\"]+)['\"]/", $body, $matches)) {
                    foreach ($matches[1] as $viewName) {
                        $viewPath = str_replace('.', '/', $viewName);
                        $fullPath = resource_path("views/{$viewPath}.blade.php");

                        if (! file_exists($fullPath)) {
                            $this->addIssue(
                                $route,
                                'critical',
                                "Missing Blade view: {$viewName} (referenced in {$class}@{$method})",
                                "Create the view: {$fullPath}"
                            );
                        }
                    }
                }
            } catch (Throwable) {
                // skip
            }
        }
    }

    // ── Helpers ───────────────────────────────────────────────

    private function addIssue(?Route $route, string $severity, string $message, string $fix): void
    {
        $this->issues[] = [
            'severity' => $severity,
            'route' => $route ? implode('|', $route->methods()) . ' ' . $route->uri() : '—',
            'message' => $message,
            'fix' => $fix,
        ];
    }

    private function resolveClassName(string $filePath): ?string
    {
        $contents = file_get_contents($filePath);

        if (preg_match('/namespace\s+(.+?);/', $contents, $nsMatch)
            && preg_match('/class\s+(\w+)/', $contents, $classMatch)) {
            return $nsMatch[1] . '\\' . $classMatch[1];
        }

        return null;
    }

    private function renderResults(): int
    {
        $severityFilter = $this->option('severity');

        $filtered = $severityFilter === 'all'
            ? $this->issues
            : array_filter($this->issues, fn ($i) => $i['severity'] === $severityFilter);

        if ($this->option('json')) {
            $this->line(json_encode([
                'checked' => $this->checkedCount,
                'issues' => array_values($filtered),
            ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

            return count(array_filter($filtered, fn ($i) => $i['severity'] === 'critical')) > 0 ? 1 : 0;
        }

        // Group by severity
        $grouped = collect($filtered)->groupBy('severity');
        $showFix = $this->option('fix');

        $severityOrder = ['critical', 'warning', 'info'];
        $severityLabels = [
            'critical' => '<fg=red;options=bold>CRITICAL</>',
            'warning' => '<fg=yellow;options=bold>WARNING</>',
            'info' => '<fg=blue>INFO</>',
        ];

        foreach ($severityOrder as $level) {
            if (! $grouped->has($level)) {
                continue;
            }

            $items = $grouped->get($level);
            $this->line("  {$severityLabels[$level]} ({$items->count()})");
            $this->line('  ' . str_repeat('─', 60));

            foreach ($items as $issue) {
                $this->line("    {$issue['route']}");
                $this->line("    {$issue['message']}");
                if ($showFix) {
                    $this->line("    <fg=green>Fix:</> {$issue['fix']}");
                }
                $this->line('');
            }
        }

        // Summary
        $criticals = $grouped->get('critical', collect())->count();
        $warnings = $grouped->get('warning', collect())->count();
        $infos = $grouped->get('info', collect())->count();

        $this->info("  Summary: {$this->checkedCount} routes checked");
        $this->line("  <fg=red>{$criticals} critical</> | <fg=yellow>{$warnings} warnings</> | <fg=blue>{$infos} info</>");
        $this->info('');

        if ($criticals > 0) {
            $this->error('  AUDIT FAILED — critical issues must be resolved before deployment.');
            return 1;
        }

        if ($warnings > 0) {
            $this->warn('  AUDIT PASSED WITH WARNINGS — review recommended.');
            return 0;
        }

        $this->info('  AUDIT PASSED — all routes are healthy.');
        return 0;
    }
}
