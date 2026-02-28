<?php

namespace App\Http\Controllers\Invoice;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\StudentCategory;
use App\Models\StudentObligation;
use App\Services\InvoiceService;
use App\Services\SchoolIdentityService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

class InvoiceController extends Controller
{
    public function __construct(
        private readonly InvoiceService $invoiceService,
        private readonly SchoolIdentityService $schoolIdentityService,
    ) {}

    public function index(Request $request): View
    {
        $query = Invoice::with(['student.schoolClass', 'creator']);
        $likeOperator = DB::connection()->getDriverName() === 'pgsql' ? 'ilike' : 'like';

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search, $likeOperator) {
                $q->where('invoice_number', $likeOperator, "%{$search}%")
                    ->orWhereHas('student', fn ($sq) => $sq->where('name', $likeOperator, "%{$search}%"));
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        if ($request->filled('period_type')) {
            $query->where('period_type', $request->input('period_type'));
        }

        if ($request->filled('period_identifier')) {
            $query->where('period_identifier', $request->input('period_identifier'));
        }

        $invoices = $query->latest()->paginate(15)->withQueryString();

        return view('invoices.index', compact('invoices'));
    }

    public function show(Invoice $invoice): View
    {
        $invoice->load([
            'student.schoolClass',
            'items.feeType',
            'items.studentObligation',
            'allocations.settlement',
            'creator',
        ]);

        return view('invoices.show', compact('invoice'));
    }

    public function create(Request $request): View
    {
        $students = Student::with('schoolClass')->where('status', 'active')->orderBy('name')->get();
        $selectedStudentId = $request->input('student_id');
        $obligations = collect();

        if ($selectedStudentId) {
            $obligations = StudentObligation::where('student_id', $selectedStudentId)
                ->where('is_paid', false)
                ->whereDoesntHave('invoiceItems', function ($q) {
                    $q->whereHas('invoice', fn ($iq) => $iq->where('status', '!=', 'cancelled'));
                })
                ->with('feeType')
                ->orderBy('year')
                ->orderBy('month')
                ->get();
        }

        return view('invoices.create', compact('students', 'obligations', 'selectedStudentId'));
    }

    public function store(Request $request): RedirectResponse
    {
        $unitId = session('current_unit_id');

        $validated = $request->validate([
            'student_id' => ['required', Rule::exists('students', 'id')->where('unit_id', $unitId)],
            'obligation_ids' => 'required|array|min:1',
            'obligation_ids.*' => Rule::exists('student_obligations', 'id')->where('unit_id', $unitId),
            'due_date' => 'required|date|after_or_equal:today',
            'notes' => 'nullable|string|max:1000',
            'period_type' => 'nullable|in:monthly,annual',
            'period_identifier' => 'nullable|string|max:30',
        ]);

        try {
            $invoice = $this->invoiceService->createInvoice(
                studentId: (int) $validated['student_id'],
                obligationIds: $validated['obligation_ids'],
                data: [
                    'due_date' => $validated['due_date'],
                    'notes' => $validated['notes'] ?? null,
                    'period_type' => $validated['period_type'] ?? 'monthly',
                    'period_identifier' => $validated['period_identifier'] ?? null,
                ],
                userId: (int) auth()->id(),
            );

            return redirect()->route('invoices.show', $invoice)
                ->with('success', __('message.invoice_created', ['number' => $invoice->invoice_number]));
        } catch (\Throwable $e) {
            Log::error('Failed to create invoice', ['message' => $e->getMessage()]);
            return back()->withInput()->with('error', __('message.invoice_create_failed', ['error' => $e->getMessage()]));
        }
    }

    public function generate(): View
    {
        $classes = SchoolClass::orderBy('name')->get();
        $categories = StudentCategory::orderBy('name')->get();

        return view('invoices.generate', compact('classes', 'categories'));
    }

    public function runGeneration(Request $request): RedirectResponse
    {
        $unitId = session('current_unit_id');

        $validated = $request->validate([
            'period_type' => 'required|in:monthly,annual',
            'period_identifier' => 'required|string|max:30',
            'due_date' => 'required|date|after_or_equal:today',
            'class_id' => ['nullable', Rule::exists('classes', 'id')->where('unit_id', $unitId)],
            'category_id' => ['nullable', Rule::exists('student_categories', 'id')->where('unit_id', $unitId)],
        ]);

        try {
            $result = $this->invoiceService->generateInvoices(
                periodType: $validated['period_type'],
                periodIdentifier: $validated['period_identifier'],
                userId: (int) auth()->id(),
                classId: $validated['class_id'] ? (int) $validated['class_id'] : null,
                categoryId: $validated['category_id'] ? (int) $validated['category_id'] : null,
                dueDate: $validated['due_date'],
            );

            $message = __('message.invoice_generation_complete', ['created' => $result['created'], 'skipped' => $result['skipped']]);
            if (!empty($result['errors'])) {
                $message .= ' ' . __('message.invoice_generation_errors', ['count' => count($result['errors'])]);
            }

            $flashData = ['success' => $message];
            if (!empty($result['errors'])) {
                $flashData['generation_errors'] = $result['errors'];
            }

            return redirect()->route('invoices.generate')->with($flashData);
        } catch (\Throwable $e) {
            Log::error('Invoice generation failed', ['message' => $e->getMessage()]);
            return back()->withInput()->with('error', __('message.invoice_generation_failed', ['error' => $e->getMessage()]));
        }
    }

    public function print(Invoice $invoice): View
    {
        $invoice->load(['student.schoolClass', 'items.feeType', 'creator']);
        $school = $this->schoolIdentityService->resolve($invoice->unit_id);

        return view('invoices.print', [
            'invoice' => $invoice,
            ...$school,
        ]);
    }

    public function destroy(Request $request, Invoice $invoice): RedirectResponse
    {
        // Paid or partially paid invoices require escalated permission
        if (in_array($invoice->status, ['paid', 'partially_paid']) || (float) $invoice->paid_amount > 0) {
            if (!$request->user()->can('invoices.cancel_paid')) {
                abort(403, __('message.cancel_paid_invoice_not_authorized'));
            }
        }

        $validated = $request->validate([
            'cancellation_reason' => 'nullable|string|max:1000',
        ]);

        try {
            $this->invoiceService->cancel(
                invoice: $invoice,
                userId: (int) auth()->id(),
                reason: (string) ($validated['cancellation_reason'] ?? __('message.cancelled_by_admin')),
            );
            return redirect()->route('invoices.index')
                ->with('success', __('message.invoice_cancelled'));
        } catch (\Throwable $e) {
            return back()->with('error', $e->getMessage());
        }
    }
}
