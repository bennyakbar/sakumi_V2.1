@php
    $school = compact('school_name', 'school_address', 'school_phone', 'school_logo', 'foundation_logo');
@endphp

<x-print.document
    theme="expense"
    :title="__('receipt.title.expense_receipt')"
    :documentNumber="$transaction->transaction_number"
    :watermarkText="$watermarkText ?? ''"
    :verificationCode="$verificationCode ?? '-'"
    :verificationUrl="$verificationUrl ?? '-'"
    :receiptIssuedAt="$receiptIssuedAt ?? null"
    :receiptPrintedAt="$receiptPrintedAt ?? null"
    :receiptPrintStatus="$receiptPrintStatus ?? 'ORIGINAL'"
    :signerName="$transaction->creator->name ?? 'Administration Admin'"
    :school="$school"
    :statusBadge="$transaction->status === 'completed' ? __('receipt.status_disbursed') : $transaction->status"
    :footerNote="__('receipt.footer.official_expense')"
>
    <x-slot:meta>
        <div class="meta-grid">
            <div class="meta-card">
                <div class="meta-label">{{ __('receipt.label.voucher_no') }}</div>
                <div class="meta-value">{{ $transaction->transaction_number }}</div>
            </div>
            <div class="meta-card">
                <div class="meta-label">{{ __('receipt.label.date') }}</div>
                <div class="meta-value">{{ $transaction->transaction_date->format('d/m/Y') }}</div>
            </div>
            <div class="meta-card">
                <div class="meta-label">{{ __('receipt.label.method') }}</div>
                <div class="meta-value">{{ strtoupper($transaction->payment_method ?? '-') }}</div>
            </div>
        </div>

        <div class="meta-grid">
            <div class="meta-card">
                <div class="meta-label">{{ __('receipt.label.transaction_type') }}</div>
                <div class="meta-value">{{ __('receipt.expense_type') }}</div>
            </div>
            <div class="meta-card">
                <div class="meta-label">{{ __('receipt.label.notes') }}</div>
                <div class="meta-value">{{ $transaction->description ?: '-' }}</div>
            </div>
            <div class="meta-card">
                <div class="meta-label">{{ __('receipt.label.officer') }}</div>
                <div class="meta-value">{{ $transaction->creator->name ?? 'SYSTEM' }}</div>
            </div>
        </div>
    </x-slot:meta>

    <x-slot:items>
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th class="c-no">{{ __('receipt.table.no') }}</th>
                        <th class="c-item">{{ __('receipt.table.expense_desc') }}</th>
                        <th class="c-note">{{ __('receipt.table.detail') }}</th>
                        <th class="c-amt">{{ __('receipt.table.nominal') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($transaction->items as $index => $item)
                        <tr>
                            <td class="c-no">{{ $index + 1 }}</td>
                            <td class="c-item">
                                <div class="item-name">{{ $item->feeType->name ?? '-' }}</div>
                            </td>
                            <td class="c-note">
                                <div class="item-note">{{ $item->description ?: '-' }}</div>
                            </td>
                            <td class="c-amt">Rp {{ number_format((float) $item->amount, 0, ',', '.') }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" style="text-align:center; color:#64748b;">{{ __('receipt.empty') }}</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </x-slot:items>

    <x-slot:summary>
        <div class="total-box">
            <div class="total-head">{{ __('receipt.total.expense') }}</div>
            <div class="total-value">Rp {{ number_format((float) $transaction->total_amount, 0, ',', '.') }}</div>
        </div>
    </x-slot:summary>
</x-print.document>
