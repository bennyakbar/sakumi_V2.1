<?php

use App\Models\Setting;

if (!function_exists('getSetting')) {
    function getSetting(string $key, mixed $default = null): mixed
    {
        return Setting::get($key, $default);
    }
}

if (!function_exists('setSetting')) {
    function setSetting(string $key, mixed $value): void
    {
        Setting::set($key, $value);
    }
}

if (!function_exists('formatRupiah')) {
    function formatRupiah(float|int|null $value, int $decimals = 2): string
    {
        return \App\Support\CurrencyFormatter::formatRupiah($value, $decimals);
    }
}
