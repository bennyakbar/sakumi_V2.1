@php
    $school = compact('school_name', 'school_address', 'school_phone', 'school_logo', 'foundation_logo');
    $itemCount = $invoice->items->count();
    $isCompact = $itemCount > 15;
@endphp

<x-print.document
    theme="invoice"
    :title="__('receipt.title.invoice')"
    :documentNumber="$invoice->invoice_number"
    :showVerification="false"
    :statusBadge="$invoice->status"
    :school="$school"
>
    <x-slot:meta>
        <div class="meta-2col">
            <table class="meta-table">
                <tr>
                    <td>{{ __('receipt.label_invoice_no') }}</td>
                    <td>: {{ $invoice->invoice_number }}</td>
                </tr>
                <tr>
                    <td>{{ __('receipt.label.date') }}</td>
                    <td>: {{ $invoice->invoice_date->format('d/m/Y') }}</td>
                </tr>
                <tr>
                    <td>{{ __('receipt.label_due_date') }}</td>
                    <td>: {{ $invoice->due_date->format('d/m/Y') }}</td>
                </tr>
                <tr>
                    <td>{{ __('receipt.label_period') }}</td>
                    <td>: <span style="text-transform:uppercase">{{ $invoice->period_type }}</span> — {{ $invoice->period_identifier }}</td>
                </tr>
                <tr>
                    <td>{{ __('receipt.label_status') }}</td>
                    <td>: <span class="status-badge status-{{ $invoice->status }}">{{ str_replace('_', ' ', $invoice->status) }}</span></td>
                </tr>
            </table>

            <table class="meta-table">
                <tr>
                    <td>{{ __('receipt.label.student_name') }}</td>
                    <td>: {{ $invoice->student->name }}</td>
                </tr>
                <tr>
                    <td>{{ __('receipt.label_nis') }}</td>
                    <td>: {{ $invoice->student->nis ?? '-' }}</td>
                </tr>
                <tr>
                    <td>{{ __('receipt.label.class') }}</td>
                    <td>: {{ $invoice->student->schoolClass->name ?? '-' }}</td>
                </tr>
            </table>
        </div>
    </x-slot:meta>

    <x-slot:items>
        <div class="table-wrap {{ $isCompact ? 'compact' : '' }}">
            <table>
                <thead>
                    <tr>
                        <th class="c-no">{{ __('receipt.table.no') }}</th>
                        <th style="width:46%">{{ __('receipt.table.invoice_item') }}</th>
                        <th style="width:18%; text-align:center">{{ __('receipt.table.period') }}</th>
                        <th class="c-amt" style="width:28%">{{ __('receipt.table.nominal') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($invoice->items as $index => $item)
                        <tr>
                            <td class="c-no">{{ $index + 1 }}</td>
                            <td>{{ $item->feeType->name ?? $item->description }}</td>
                            <td style="text-align:center; white-space:nowrap">
                                @if($item->month && $item->year)
                                    {{ sprintf('%02d/%d', $item->month, $item->year) }}
                                @elseif($item->year)
                                    {{ $item->year }}
                                @else
                                    -
                                @endif
                            </td>
                            <td class="c-amt">{{ formatRupiah($item->amount) }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" style="text-align:center; color: #6b7280;">{{ __('receipt.no_invoice_items') }}</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </x-slot:items>

    <x-slot:summary>
        <table class="totals-table">
            <tr>
                <td>{{ __('receipt.total.invoice') }}</td>
                <td>{{ formatRupiah($invoice->total_amount) }}</td>
            </tr>
            <tr>
                <td>{{ __('receipt.total.paid') }}</td>
                <td>{{ formatRupiah($invoice->paid_amount) }}</td>
            </tr>
            <tr>
                <td>{{ __('receipt.total.outstanding') }}</td>
                <td>{{ formatRupiah($invoice->outstanding) }}</td>
            </tr>
        </table>
    </x-slot:summary>

    <x-slot:footerExtra>
        <section style="border-top: 1px solid var(--line); padding-top: 2.5mm; display: grid; grid-template-columns: 1fr 1fr; gap: 4mm; align-items: start;">
            <div style="font-size: 9px; color: var(--muted);">
                @if($invoice->notes)
                    <p style="margin: 0.5mm 0;"><strong>{{ __('receipt.label.notes') }}:</strong> {{ $invoice->notes }}</p>
                @endif
                <p style="margin: 0.5mm 0;">{{ __('receipt.label.printed_at') }}: {{ now()->format('d/m/Y H:i') }}</p>
            </div>
            <div class="signature">
                <div class="label">{{ __('receipt.label.school_treasurer') }}</div>
                <div class="line">{{ $invoice->creator->name ?? 'Administration' }}</div>
                <p class="hint">{{ __('receipt.footer.digitally_signed') }}</p>
            </div>
        </section>
    </x-slot:footerExtra>
</x-print.document>
