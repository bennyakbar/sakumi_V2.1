<?php

namespace App\Services;

use App\Models\Unit;

class SchoolIdentityService
{
    /**
     * Resolve school identity for a specific unit.
     *
     * Priority:
     * 1) unit-specific setting keys (school_name_mi, school_address_mi, ...)
     * 2) global setting keys (school_name, school_address, ...)
     * 3) unit canonical name from units table
     */
    public function resolve(?int $unitId): array
    {
        $unit = $unitId ? Unit::query()->find($unitId) : null;
        $unitCode = strtolower((string) ($unit?->code ?? ''));

        $unitName = is_string($unit?->name) ? trim($unit->name) : '';
        $name = $this->settingByUnit('school_name', $unitCode, false)
            ?? ($unitName !== '' ? $unitName : ($this->settingByUnit('school_name', $unitCode, true) ?? config('app.name', 'Sakumi')));

        $address = $this->normalizeAddress($this->settingByUnit('school_address', $unitCode, true) ?? '');

        $phone = $this->settingByUnit('school_phone', $unitCode, true) ?? '-';
        $logo = $this->settingByUnit('school_logo', $unitCode, true) ?? '';
        $foundationLogo = getSetting('foundation_logo', '');

        return [
            'school_name' => $name,
            'school_address' => $address,
            'school_phone' => $phone,
            'school_logo' => $logo,
            'foundation_logo' => $foundationLogo,
            'unit_code' => $unitCode,
        ];
    }

    private function settingByUnit(string $baseKey, string $unitCode, bool $fallbackGlobal): ?string
    {
        if ($unitCode !== '') {
            $unitValue = getSetting("{$baseKey}_{$unitCode}", null);
            if (is_string($unitValue) && trim($unitValue) !== '') {
                return $unitValue;
            }
        }

        if ($fallbackGlobal) {
            $globalValue = getSetting($baseKey, null);
            if (is_string($globalValue) && trim($globalValue) !== '') {
                return $globalValue;
            }
        }

        return null;
    }

    private function normalizeAddress(string $raw): string
    {
        $raw = str_replace('\n', "\n", $raw);
        $normalized = preg_replace('/<\s*br\s*\/?>/i', "\n", $raw) ?? $raw;
        $normalized = strip_tags($normalized);

        return trim($normalized);
    }
}
