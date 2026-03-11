<?php

namespace App\Providers;

use App\Events\TransactionCreated;
use App\Events\ObligationGenerated;
use App\Listeners\BumpDashboardCacheVersion;
use App\Listeners\SendPaymentNotification;
use App\Listeners\UpdateInvoiceStatus;
use App\Services\UnitContext;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Console\Events\CommandStarting;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;
use RuntimeException;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->scoped(UnitContext::class);

        $this->enforceSakumiMode();
    }

    public function boot(): void
    {
        Password::defaults(fn () => Password::min(8)->letters()->mixedCase()->numbers()->symbols()->uncompromised());

        $this->registerRateLimiters();

        Event::listen(TransactionCreated::class, SendPaymentNotification::class);
        Event::listen(TransactionCreated::class, UpdateInvoiceStatus::class);
        Event::listen(TransactionCreated::class, BumpDashboardCacheVersion::class);
        Event::listen(ObligationGenerated::class, BumpDashboardCacheVersion::class);

        $this->registerWriteProtection();
        $this->registerCommandProtection();
    }

    private function registerRateLimiters(): void
    {
        RateLimiter::for('api-login', function ($request) {
            $email = strtolower((string) $request->input('email', 'guest'));
            return Limit::perMinute(10)->by($email.'|'.$request->ip());
        });

        RateLimiter::for('dashboard-read', function ($request) {
            $key = $request->user()?->id ? 'user:'.$request->user()->id : 'ip:'.$request->ip();
            return Limit::perMinute(120)->by($key);
        });

        RateLimiter::for('reports-read', function ($request) {
            $key = $request->user()?->id ? 'user:'.$request->user()->id : 'ip:'.$request->ip();
            return Limit::perMinute(60)->by($key);
        });

        RateLimiter::for('password-reset-link', function ($request) {
            return Limit::perMinute(5)->by('password-reset-link|'.$request->ip());
        });

        RateLimiter::for('password-reset', function ($request) {
            return Limit::perMinute(5)->by('password-reset|'.$request->ip());
        });
    }

    /**
     * Block destructive Artisan commands when running in 'real' mode.
     *
     * Hard-blocked commands are always prevented (no bypass).
     * Soft-blocked commands require the --force flag.
     *
     * Override with: SAKUMI_ALLOW_DANGEROUS=1 php artisan <command> --force
     */
    private function registerCommandProtection(): void
    {
        if (! $this->app->runningInConsole()) {
            return;
        }

        // Testing environments (CI, PHPUnit) need migrate:fresh etc. freely
        if ($this->app->environment('testing')) {
            return;
        }

        $mode = config('database.sakumi_mode', env('DB_SAKUMI_MODE'));

        if ($mode !== 'real') {
            return;
        }

        // Commands that are NEVER allowed on real database
        $hardBlocked = [
            'migrate:fresh',
            'migrate:reset',
            'db:wipe',
            'db:seed',
            'schema:dump',
        ];

        // Commands that require --force and optional env override
        $softBlocked = [
            'migrate',
            'migrate:rollback',
            'migrate:refresh',
        ];

        Event::listen(CommandStarting::class, function (CommandStarting $event) use ($hardBlocked, $softBlocked): void {
            $command = $event->command;

            if (in_array($command, $hardBlocked, true)) {
                Log::critical('SAKUMI SAFETY: Blocked destructive command on real database', [
                    'command' => $command,
                    'user' => get_current_user(),
                ]);

                // Write to stderr so the developer sees it immediately
                fwrite(STDERR, "\n");
                fwrite(STDERR, "╔══════════════════════════════════════════════════════════════╗\n");
                fwrite(STDERR, "║  BLOCKED: '{$command}' is not allowed on real database.     \n");
                fwrite(STDERR, "║                                                              \n");
                fwrite(STDERR, "║  Current mode: DB_SAKUMI_MODE=real                           \n");
                fwrite(STDERR, "║  This command would destroy production data.                 \n");
                fwrite(STDERR, "║                                                              \n");
                fwrite(STDERR, "║  If you meant to work on the dummy database:                 \n");
                fwrite(STDERR, "║    ./scripts/switch-env.sh dummy                             \n");
                fwrite(STDERR, "╚══════════════════════════════════════════════════════════════╝\n");
                fwrite(STDERR, "\n");

                exit(1);
            }

            if (in_array($command, $softBlocked, true)) {
                $hasForce = $event->input->hasParameterOption('--force');
                $envOverride = env('SAKUMI_ALLOW_DANGEROUS') === '1';

                if (! $hasForce) {
                    Log::warning('SAKUMI SAFETY: Soft-blocked command attempted without --force', [
                        'command' => $command,
                        'user' => get_current_user(),
                    ]);

                    fwrite(STDERR, "\n");
                    fwrite(STDERR, "╔══════════════════════════════════════════════════════════════╗\n");
                    fwrite(STDERR, "║  CAUTION: '{$command}' on real database requires --force.   \n");
                    fwrite(STDERR, "║                                                              \n");
                    fwrite(STDERR, "║  Current mode: DB_SAKUMI_MODE=real                           \n");
                    fwrite(STDERR, "║                                                              \n");
                    fwrite(STDERR, "║  To proceed:                                                 \n");
                    fwrite(STDERR, "║    php artisan {$command} --force                            \n");
                    fwrite(STDERR, "║                                                              \n");
                    fwrite(STDERR, "║  If you meant to work on the dummy database:                 \n");
                    fwrite(STDERR, "║    ./scripts/switch-env.sh dummy                             \n");
                    fwrite(STDERR, "╚══════════════════════════════════════════════════════════════╝\n");
                    fwrite(STDERR, "\n");

                    exit(1);
                }

                // Log when override is used — audit trail
                Log::warning('SAKUMI SAFETY: Dangerous command executed on real database with --force', [
                    'command' => $command,
                    'env_override' => $envOverride,
                    'user' => get_current_user(),
                ]);
            }
        });
    }

    /**
     * Enforce that DB_SAKUMI_MODE is explicitly set to 'dummy' or 'real'.
     * App crashes immediately if misconfigured.
     */
    private function enforceSakumiMode(): void
    {
        // PHPUnit uses in-memory sqlite — bypass
        if ($this->app->environment('testing') && config('database.default') === 'sqlite') {
            return;
        }

        $mode = config('database.sakumi_mode', env('DB_SAKUMI_MODE'));

        if (! in_array($mode, ['dummy', 'real'], true)) {
            throw new RuntimeException(
                "FATAL: DB_SAKUMI_MODE must be explicitly set to 'dummy' or 'real'.\n"
                . 'Current value: ' . var_export($mode, true) . "\n"
                . "Set DB_SAKUMI_MODE=dummy in .env.dummy or DB_SAKUMI_MODE=real in .env.real\n"
                . 'Use: ./scripts/switch-env.sh dummy|real'
            );
        }
    }

    /**
     * Block writes to the opposite connection to prevent cross-contamination.
     */
    private function registerWriteProtection(): void
    {
        // PHPUnit uses in-memory sqlite — bypass
        if ($this->app->environment('testing') && config('database.default') === 'sqlite') {
            return;
        }

        $mode = config('database.sakumi_mode', env('DB_SAKUMI_MODE'));

        if ($mode === 'dummy') {
            $this->blockWritesOn('sakumi_real', 'dummy');
        } elseif ($mode === 'real') {
            $this->blockWritesOn('sakumi_dummy', 'real');
        }
    }

    private function blockWritesOn(string $connection, string $currentMode): void
    {
        try {
            DB::connection($connection)->beforeExecuting(function (string $query) use ($connection, $currentMode): void {
                if ($this->isWriteQuery($query)) {
                    throw new RuntimeException(
                        "WRITE PROTECTION: Cannot write to '{$connection}' while in {$currentMode} mode.\n"
                        . 'Query: ' . substr($query, 0, 120)
                    );
                }
            });
        } catch (\InvalidArgumentException) {
            // Connection not configured — safe to ignore
        }
    }

    private function isWriteQuery(string $query): bool
    {
        $normalized = ltrim(strtoupper($query));

        foreach (['INSERT', 'UPDATE', 'DELETE', 'ALTER', 'DROP', 'CREATE', 'TRUNCATE'] as $keyword) {
            if (str_starts_with($normalized, $keyword)) {
                return true;
            }
        }

        return false;
    }
}
