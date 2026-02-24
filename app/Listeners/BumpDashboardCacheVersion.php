<?php

namespace App\Listeners;

use Illuminate\Support\Facades\Cache;

class BumpDashboardCacheVersion
{
    public function handle(mixed $event = null): void
    {
        $this->bumpVersion('cache-version:dashboard-metrics');
        $this->bumpVersion('cache-version:chart-data');
    }

    private function bumpVersion(string $key): void
    {
        $current = (int) Cache::get($key, 1);
        Cache::forever($key, $current + 1);
    }
}
