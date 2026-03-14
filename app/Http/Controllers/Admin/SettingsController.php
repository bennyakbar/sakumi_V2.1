<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SettingsController extends Controller
{
    public function edit(): View
    {
        return view('settings.edit', [
            'academicYearCurrent' => (string) Setting::get('academic_year_current', ''),
            'dangerousPermanentDeleteEnabled' => filter_var(Setting::get('dangerous_permanent_delete_enabled', false), FILTER_VALIDATE_BOOLEAN),
            'makerCheckerInvoicesEnabled' => filter_var(Setting::get('maker_checker.invoices_enabled', false), FILTER_VALIDATE_BOOLEAN),
            'makerCheckerSettlementsEnabled' => filter_var(Setting::get('maker_checker.settlements_enabled', false), FILTER_VALIDATE_BOOLEAN),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'academic_year_current' => [
                'required',
                'regex:/^\d{4}\/\d{4}$/',
                function (string $attribute, mixed $value, \Closure $fail): void {
                    if (!is_string($value) || !preg_match('/^(\d{4})\/(\d{4})$/', $value, $matches)) {
                        return;
                    }

                    $startYear = (int) $matches[1];
                    $endYear = (int) $matches[2];
                    if ($endYear !== $startYear + 1) {
                        $fail(__('message.academic_year_must_be_consecutive'));
                    }
                },
            ],
            'dangerous_permanent_delete_enabled' => ['nullable', 'boolean'],
            'maker_checker_invoices_enabled' => ['nullable', 'boolean'],
            'maker_checker_settlements_enabled' => ['nullable', 'boolean'],
        ]);

        Setting::updateOrCreate(
            ['key' => 'academic_year_current'],
            [
                'value' => (string) $validated['academic_year_current'],
                'type' => 'string',
                'group' => 'system',
                'description' => 'Tahun akademik aktif',
            ]
        );
        Setting::updateOrCreate(
            ['key' => 'dangerous_permanent_delete_enabled'],
            [
                'value' => $request->boolean('dangerous_permanent_delete_enabled') ? 'true' : 'false',
                'type' => 'boolean',
                'group' => 'system',
                'description' => 'Izinkan superadmin melakukan permanent delete pada data tertentu',
            ]
        );
        Setting::updateOrCreate(
            ['key' => 'maker_checker.invoices_enabled'],
            [
                'value' => $request->boolean('maker_checker_invoices_enabled') ? 'true' : 'false',
                'type' => 'boolean',
                'group' => 'system',
                'description' => 'Aktifkan persetujuan maker-checker untuk invoice baru',
            ]
        );
        Setting::updateOrCreate(
            ['key' => 'maker_checker.settlements_enabled'],
            [
                'value' => $request->boolean('maker_checker_settlements_enabled') ? 'true' : 'false',
                'type' => 'boolean',
                'group' => 'system',
                'description' => 'Aktifkan persetujuan maker-checker untuk settlement baru',
            ]
        );
        Setting::clearCache();

        return redirect()->route('settings.edit')->with('success', __('message.settings_updated'));
    }
}
