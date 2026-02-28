@php
    $school = $schoolData;
@endphp

<x-print.document
    theme="settlement"
    :title="__('receipt.title.settlement_receipt')"
    :documentNumber="$settlement->settlement_number"
    :watermarkText="$watermarkText ?? ''"
    :verificationCode="$verificationCode ?? '-'"
    :verificationUrl="$verificationUrl ?? '-'"
    :receiptIssuedAt="$receiptIssuedAt ?? null"
    :receiptPrintedAt="$receiptPrintedAt ?? null"
    :receiptPrintStatus="$receiptPrintStatus ?? 'ORIGINAL'"
    :signerName="$settlement->creator->name ?? 'Administration Admin'"
    :school="$school"
    :statusBadge="$settlement->status"
    :footerNote="__('receipt.footer.official_settlement')"
>
    <x-slot:meta>
        <div class="meta-grid">
            <div class="meta-card">
                <div class="meta-label">{{ __('receipt.label.settlement_no') }}</div>
                <div class="meta-value">{{ $settlement->settlement_number }}</div>
            </div>
            <div class="meta-card">
                <div class="meta-label">{{ __('receipt.label.pay_date') }}</div>
                <div class="meta-value">{{ $settlement->payment_date->format('d/m/Y') }}</div>
            </div>
            <div class="meta-card">
                <div class="meta-label">{{ __('receipt.label.method') }}</div>
                <div class="meta-value">{{ strtoupper($settlement->payment_method ?? '-') }}</div>
            </div>
        </div>

        <div class="meta-grid">
            <div class="meta-card">
                <div class="meta-label">{{ __('receipt.label.student_name') }}</div>
                <div class="meta-value">{{ $settlement->student->name ?? '-' }}</div>
            </div>
            <div class="meta-card">
                <div class="meta-label">{{ __('receipt.label.class') }}</div>
                <div class="meta-value">{{ $settlement->student->schoolClass->name ?? '-' }}</div>
            </div>
            <div class="meta-card">
                <div class="meta-label">{{ __('receipt.label.reference_number') }}</div>
                <div class="meta-value">{{ $settlement->reference_number ?: '-' }}</div>
            </div>
        </div>
    </x-slot:meta>

    <x-slot:items>
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th class="c-no">{{ __('receipt.table.no') }}</th>
                        <th style="width:28%">{{ __('receipt.table.invoice_no') }}</th>
                        <th style="width:18%; text-align:center">{{ __('receipt.table.period') }}</th>
                        <th style="width:22%; text-align:right">{{ __('receipt.table.invoice_total') }}</th>
                        <th class="c-amt" style="width:24%">{{ __('receipt.table.allocated') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($settlement->allocations as $index => $allocation)
                        <tr>
                            <td class="c-no">{{ $index + 1 }}</td>
                            <td>{{ $allocation->invoice->invoice_number }}</td>
                            <td style="text-align:center; white-space:nowrap">
                                <span style="text-transform:uppercase; font-size:8px; color:var(--muted)">{{ $allocation->invoice->period_type }}</span>
                                {{ $allocation->invoice->period_identifier }}
                            </td>
                            <td style="text-align:right; white-space:nowrap">Rp {{ number_format((float) $allocation->invoice->total_amount, 0, ',', '.') }}</td>
                            <td class="c-amt">Rp {{ number_format((float) $allocation->amount, 0, ',', '.') }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" style="text-align:center; color:#64748b;">{{ __('receipt.empty') }}</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </x-slot:items>

    <x-slot:summary>
        <div class="total-box">
            <div class="total-head">{{ __('receipt.total.settlement') }}</div>
            <div class="total-value">Rp {{ number_format((float) $settlement->total_amount, 0, ',', '.') }}</div>
        </div>
    </x-slot:summary>
</x-print.document>
