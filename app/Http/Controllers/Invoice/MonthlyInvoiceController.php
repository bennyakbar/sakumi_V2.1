<?php

namespace App\Http\Controllers\Invoice;

use App\Http\Controllers\Controller;
use App\Models\SchoolClass;
use App\Services\InvoiceGenerationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

class MonthlyInvoiceController extends Controller
{
    public function __construct(
        private readonly InvoiceGenerationService $generationService,
    ) {}

    /**
     * Show the monthly invoice generation form.
     */
    public function create(): View
    {
        $classes = SchoolClass::orderBy('name')->get();

        return view('invoices.generate-monthly', compact('classes'));
    }

    /**
     * Generate monthly invoices for a given month.
     */
    public function store(Request $request): RedirectResponse
    {
        $unitId = session('current_unit_id');

        $validated = $request->validate([
            'month' => 'required|integer|min:1|max:12',
            'year' => 'required|integer|min:2020|max:2099',
            'class_id' => ['nullable', 'exists:classes,id'],
        ]);

        try {
            $result = $this->generationService->generateMonthlyInvoices(
                month: (int) $validated['month'],
                year: (int) $validated['year'],
                userId: (int) auth()->id(),
                classId: $validated['class_id'] ? (int) $validated['class_id'] : null,
            );

            $message = __('message.monthly_invoice_generation_complete', [
                'created' => $result['created'],
                'skipped' => $result['skipped'],
            ]);

            if (! empty($result['errors'])) {
                $message .= ' ' . __('message.invoice_generation_errors', ['count' => count($result['errors'])]);
            }

            $flashData = ['success' => $message];
            if (! empty($result['errors'])) {
                $flashData['generation_errors'] = $result['errors'];
            }

            return redirect()->route('invoices.generate-monthly')->with($flashData);
        } catch (\Throwable $e) {
            Log::error('Monthly invoice generation failed', ['message' => $e->getMessage()]);

            return back()->withInput()->with('error', __('message.invoice_generation_failed', ['error' => $e->getMessage()]));
        }
    }
}
