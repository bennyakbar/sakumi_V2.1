@php
    $items = $transaction->items;
    $itemCount = $items->count();
    $maxRows = 18;
    $displayItems = $items->take($maxRows);
    $remainingCount = max($itemCount - $maxRows, 0);
    $remainingAmount = $remainingCount > 0 ? (float) $items->slice($maxRows)->sum('amount') : 0;
    $compactMode = $itemCount > 10;

    $school = compact('school_name', 'school_address', 'school_phone', 'school_logo', 'foundation_logo');
@endphp

<x-print.document
    theme="receipt"
    :title="__('receipt.title.payment_receipt')"
    :documentNumber="$transaction->transaction_number"
    :watermarkText="$watermarkText ?? ''"
    :verificationCode="$verificationCode ?? '-'"
    :verificationUrl="$verificationUrl ?? '-'"
    :receiptIssuedAt="$receiptIssuedAt ?? null"
    :receiptPrintedAt="$receiptPrintedAt ?? null"
    :receiptPrintStatus="$receiptPrintStatus ?? 'ORIGINAL'"
    :signerName="$transaction->creator->name ?? 'Administration Admin'"
    :school="$school"
    statusBadge="paid"
    :footerNote="__('receipt.footer.official_receipt')"
    :totalLabel="__('receipt.total.payment')"
    :totalValue="'Rp ' . number_format((float) $transaction->total_amount, 0, ',', '.')"
>
    <x-slot:meta>
        <div class="meta-grid">
            <div class="meta-card">
                <div class="meta-label">{{ __('receipt.label.receipt_no') }}</div>
                <div class="meta-value">{{ $transaction->transaction_number }}</div>
            </div>
            <div class="meta-card">
                <div class="meta-label">{{ __('receipt.label.pay_date') }}</div>
                <div class="meta-value">{{ $transaction->transaction_date->format('d/m/Y') }}</div>
            </div>
            <div class="meta-card">
                <div class="meta-label">{{ __('receipt.label.method') }}</div>
                <div class="meta-value">{{ strtoupper($transaction->payment_method ?? '-') }}</div>
            </div>
        </div>

        <div class="meta-grid">
            <div class="meta-card">
                <div class="meta-label">{{ __('receipt.label.student_name') }}</div>
                <div class="meta-value">{{ $transaction->student->name ?? '-' }}</div>
            </div>
            <div class="meta-card">
                <div class="meta-label">{{ __('receipt.label.class') }}</div>
                <div class="meta-value">{{ $transaction->student->schoolClass->name ?? '-' }}</div>
            </div>
            <div class="meta-card">
                <div class="meta-label">{{ __('receipt.label.officer') }}</div>
                <div class="meta-value">{{ $transaction->creator->name ?? 'SYSTEM' }}</div>
            </div>
        </div>
    </x-slot:meta>

    <x-slot:items>
        <div class="table-wrap {{ $compactMode ? 'compact' : '' }}">
            <table>
                <thead>
                    <tr>
                        <th class="c-no">{{ __('receipt.table.no') }}</th>
                        <th class="c-item">{{ __('receipt.table.description') }}</th>
                        <th class="c-note">{{ __('receipt.table.detail') }}</th>
                        <th class="c-amt">{{ __('receipt.table.nominal') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($displayItems as $index => $item)
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

                    @if ($remainingCount > 0)
                        <tr class="summary-row">
                            <td class="c-no">+</td>
                            <td colspan="2">{{ __('receipt.footer.items_condensed', ['count' => $remainingCount]) }}</td>
                            <td class="c-amt">Rp {{ number_format($remainingAmount, 0, ',', '.') }}</td>
                        </tr>
                    @endif
                </tbody>
            </table>
        </div>
    </x-slot:items>

    <x-slot:summary>
        <div class="total-box">
            <div class="total-head">{{ __('receipt.total.payment') }}</div>
            <div class="total-value">Rp {{ number_format((float) $transaction->total_amount, 0, ',', '.') }}</div>
        </div>
    </x-slot:summary>
</x-print.document>
