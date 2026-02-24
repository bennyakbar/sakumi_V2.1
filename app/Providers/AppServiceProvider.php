<?php

namespace App\Providers;

use App\Events\TransactionCreated;
use App\Events\ObligationGenerated;
use App\Listeners\BumpDashboardCacheVersion;
use App\Listeners\SendPaymentNotification;
use App\Listeners\UpdateInvoiceStatus;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use RuntimeException;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->enforceSakumiMode();
    }

    public function boot(): void
    {
        $this->registerRateLimiters();

        Event::listen(TransactionCreated::class, SendPaymentNotification::class);
        Event::listen(TransactionCreated::class, UpdateInvoiceStatus::class);
        Event::listen(TransactionCreated::class, BumpDashboardCacheVersion::class);
        Event::listen(ObligationGenerated::class, BumpDashboardCacheVersion::class);

        $this->registerWriteProtection();
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
