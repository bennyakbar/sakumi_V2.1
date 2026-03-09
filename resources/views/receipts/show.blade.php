@php
    $items = $transaction->items;
    $itemCount = $items->count();
    $isExpense = ($transaction->type ?? 'income') === 'expense';

    $school_name    = $school_name ?? __('School');
    $school_address = $school_address ?? '';
    $school_phone   = $school_phone ?? '-';

    $schoolInitials = collect(preg_split('/\s+/', trim($school_name)))
        ->filter()->take(2)
        ->map(fn($p) => strtoupper(substr($p, 0, 1)))
        ->implode('') ?: 'SC';
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ __('receipt.title.payment_receipt') }} - {{ $transaction->transaction_number }}</title>
    <style>
        :root {
            --ink: #1e293b;
            --muted: #64748b;
            --light: #94a3b8;
            --border: #e2e8f0;
            --border-strong: #cbd5e1;
            --surface: #f8fafc;
            --brand: #3730a3;
            --brand-light: #eef2ff;
            --brand-dark: #312e81;
            --success: #059669;
            --success-bg: #ecfdf5;
        }

        * { box-sizing: border-box; margin: 0; padding: 0; }

        @page {
            size: A4 portrait;
            margin: 15mm;
        }

        html, body {
            font-family: "Inter", "Segoe UI", -apple-system, sans-serif;
            font-size: 13px;
            line-height: 1.5;
            color: var(--ink);
            background: #f1f5f9;
        }

        .receipt-container {
            max-width: 680px;
            margin: 24px auto;
            background: #fff;
            border-radius: 12px;
            box-shadow: 0 1px 3px rgba(0,0,0,.06), 0 4px 16px rgba(0,0,0,.04);
            overflow: hidden;
        }

        /* ── Header ── */
        .receipt-header {
            padding: 28px 32px 24px;
            border-bottom: 2px solid var(--brand);
            background: linear-gradient(135deg, var(--brand-light) 0%, #fff 100%);
        }

        .header-top {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 16px;
        }

        .school-identity {
            flex: 1;
            min-width: 0;
        }

        .school-name {
            font-size: 18px;
            font-weight: 700;
            color: var(--brand-dark);
            line-height: 1.3;
        }

        .school-detail {
            font-size: 11px;
            color: var(--muted);
            margin-top: 2px;
            white-space: pre-line;
        }

        .school-logo {
            width: 52px;
            height: 52px;
            border-radius: 50%;
            border: 2px solid var(--border);
            display: flex;
            align-items: center;
            justify-content: center;
            background: #fff;
            flex-shrink: 0;
            font-size: 16px;
            font-weight: 700;
            color: var(--brand);
        }

        .doc-title {
            margin-top: 16px;
            font-size: 15px;
            font-weight: 600;
            letter-spacing: 0.5px;
            text-transform: uppercase;
            color: var(--brand);
        }

        .receipt-number {
            margin-top: 4px;
            font-size: 20px;
            font-weight: 700;
            color: var(--ink);
            letter-spacing: -0.3px;
        }

        .receipt-date {
            margin-top: 2px;
            font-size: 12px;
            color: var(--muted);
        }

        /* ── Body ── */
        .receipt-body {
            padding: 28px 32px;
        }

        /* ── Info Grid ── */
        .info-section {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-bottom: 28px;
        }

        .info-block-title {
            font-size: 10px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.8px;
            color: var(--light);
            margin-bottom: 10px;
            padding-bottom: 6px;
            border-bottom: 1px solid var(--border);
        }

        .info-row {
            display: flex;
            justify-content: space-between;
            padding: 4px 0;
            font-size: 12px;
        }

        .info-label {
            color: var(--muted);
            flex-shrink: 0;
        }

        .info-value {
            font-weight: 500;
            text-align: right;
            color: var(--ink);
        }

        /* ── Items Table ── */
        .section-title {
            font-size: 10px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.8px;
            color: var(--light);
            margin-bottom: 10px;
        }

        .items-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
            border: 1px solid var(--border);
            border-radius: 8px;
            overflow: hidden;
            margin-bottom: 20px;
        }

        .items-table th {
            background: var(--surface);
            font-size: 10px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: var(--muted);
            padding: 10px 14px;
            text-align: left;
            border-bottom: 1px solid var(--border);
        }

        .items-table th:last-child {
            text-align: right;
        }

        .items-table td {
            padding: 10px 14px;
            font-size: 12px;
            border-bottom: 1px solid var(--border);
            vertical-align: top;
        }

        .items-table tbody tr:last-child td {
            border-bottom: none;
        }

        .items-table tbody tr:nth-child(even) {
            background: var(--surface);
        }

        .item-no {
            width: 40px;
            text-align: center;
            color: var(--light);
            font-weight: 500;
        }

        .item-name {
            font-weight: 500;
            color: var(--ink);
        }

        .item-desc {
            font-size: 11px;
            color: var(--muted);
            margin-top: 2px;
        }

        .item-period {
            text-align: center;
            white-space: nowrap;
            color: var(--muted);
        }

        .item-amount {
            text-align: right;
            font-weight: 600;
            white-space: nowrap;
        }

        /* ── Total ── */
        .total-section {
            display: flex;
            justify-content: flex-end;
            margin-bottom: 28px;
        }

        .total-box {
            min-width: 260px;
            border: 2px solid var(--brand);
            border-radius: 10px;
            overflow: hidden;
        }

        .total-label {
            background: var(--brand);
            color: #fff;
            padding: 8px 16px;
            font-size: 10px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.8px;
        }

        .total-value {
            padding: 12px 16px;
            text-align: right;
            font-size: 22px;
            font-weight: 700;
            color: var(--brand-dark);
            letter-spacing: -0.5px;
            background: var(--brand-light);
        }

        /* ── Footer ── */
        .receipt-footer {
            padding: 20px 32px 28px;
            border-top: 1px solid var(--border);
            background: var(--surface);
        }

        .footer-grid {
            display: grid;
            grid-template-columns: 1fr auto;
            gap: 24px;
            align-items: end;
        }

        .footer-meta {
            font-size: 10px;
            color: var(--light);
            line-height: 1.8;
        }

        .footer-meta strong {
            color: var(--muted);
        }

        .signature-block {
            text-align: center;
            min-width: 180px;
        }

        .sig-title {
            font-size: 10px;
            color: var(--muted);
            margin-bottom: 32px;
        }

        .sig-line {
            border-top: 1.5px solid var(--ink);
            padding-top: 6px;
            font-size: 12px;
            font-weight: 700;
            color: var(--ink);
        }

        .sig-hint {
            font-size: 9px;
            color: var(--light);
            margin-top: 2px;
        }

        .footer-note {
            margin-top: 16px;
            padding-top: 12px;
            border-top: 1px dashed var(--border-strong);
            font-size: 10px;
            color: var(--light);
            text-align: center;
        }

        /* ── Print button (hidden in print) ── */
        .print-actions {
            max-width: 680px;
            margin: 16px auto;
            display: flex;
            gap: 8px;
            justify-content: center;
        }

        .btn-print {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 10px 24px;
            font-size: 13px;
            font-weight: 600;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.15s;
        }

        .btn-print-primary {
            background: var(--brand);
            color: #fff;
        }

        .btn-print-primary:hover {
            background: var(--brand-dark);
        }

        .btn-print-secondary {
            background: #fff;
            color: var(--muted);
            border: 1px solid var(--border-strong);
        }

        .btn-print-secondary:hover {
            background: var(--surface);
            color: var(--ink);
        }

        @media print {
            html, body {
                background: #fff;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }

            .receipt-container {
                margin: 0;
                box-shadow: none;
                border-radius: 0;
                max-width: 100%;
            }

            .print-actions {
                display: none !important;
            }

            .receipt-footer {
                background: #f8fafc !important;
            }
        }
    </style>
</head>
<body>

    {{-- Print / Back buttons --}}
    <div class="print-actions">
        <button class="btn-print btn-print-primary" onclick="window.print()">
            <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M6.72 13.829c-.24.03-.48.062-.72.096m.72-.096a42.415 42.415 0 0110.56 0m-10.56 0L6.34 18m10.94-4.171c.24.03.48.062.72.096m-.72-.096L17.66 18m0 0l.229 2.523a1.125 1.125 0 01-1.12 1.227H7.231c-.662 0-1.18-.568-1.12-1.227L6.34 18m11.318 0h1.091A2.25 2.25 0 0021 15.75V9.456c0-1.081-.768-2.015-1.837-2.175a48.055 48.055 0 00-1.913-.247M6.34 18H5.25A2.25 2.25 0 013 15.75V9.456c0-1.081.768-2.015 1.837-2.175a48.041 48.041 0 011.913-.247m10.5 0a48.536 48.536 0 00-10.5 0m10.5 0V3.375c0-.621-.504-1.125-1.125-1.125h-8.25c-.621 0-1.125.504-1.125 1.125v3.659M9.75 21h4.5"/>
            </svg>
            {{ __('app.button.print') }}
        </button>
        <button class="btn-print btn-print-secondary" onclick="window.history.back()">
            <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 15L3 9m0 0l6-6M3 9h12a6 6 0 010 12h-3"/>
            </svg>
            {{ __('app.button.back') }}
        </button>
    </div>

    <main class="receipt-container">
        {{-- ── Header ── --}}
        <header class="receipt-header">
            <div class="header-top">
                <div class="school-identity">
                    <div class="school-name">{{ $school_name }}</div>
                    @if($school_address)
                        <div class="school-detail">{{ $school_address }}</div>
                    @endif
                    @if($school_phone && $school_phone !== '-')
                        <div class="school-detail">{{ __('receipt.label.phone') }}: {{ $school_phone }}</div>
                    @endif
                </div>
                <div class="school-logo">{{ $schoolInitials }}</div>
            </div>

            <div class="doc-title">{{ $isExpense ? __('receipt.title.expense_receipt') : __('receipt.title.payment_receipt') }}</div>
            <div class="receipt-number">{{ $transaction->transaction_number }}</div>
            <div class="receipt-date">{{ $transaction->transaction_date->translatedFormat('d F Y') }}</div>
        </header>

        {{-- ── Body ── --}}
        <div class="receipt-body">
            {{-- Student & Payment Info --}}
            <div class="info-section">
                <div>
                    <div class="info-block-title">{{ $isExpense ? __('receipt.label.transaction_type') : __('Student Info') }}</div>
                    @if($isExpense)
                        <div class="info-row">
                            <span class="info-label">{{ __('Type') }}</span>
                            <span class="info-value">{{ __('receipt.expense_type') }}</span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">{{ __('receipt.label.notes') }}</span>
                            <span class="info-value">{{ $transaction->description ?: '-' }}</span>
                        </div>
                    @else
                        <div class="info-row">
                            <span class="info-label">{{ __('receipt.label.student_name') }}</span>
                            <span class="info-value">{{ $transaction->student->name ?? '-' }}</span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">{{ __('receipt.label_nis') ?? 'NIS' }}</span>
                            <span class="info-value">{{ $transaction->student->nis ?? '-' }}</span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">{{ __('receipt.label.class') }}</span>
                            <span class="info-value">{{ $transaction->student->schoolClass->name ?? '-' }}</span>
                        </div>
                    @endif
                </div>
                <div>
                    <div class="info-block-title">{{ __('Payment Info') }}</div>
                    <div class="info-row">
                        <span class="info-label">{{ __('receipt.label.receipt_no') }}</span>
                        <span class="info-value">{{ $transaction->transaction_number }}</span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">{{ __('receipt.label.pay_date') }}</span>
                        <span class="info-value">{{ $transaction->transaction_date->format('d/m/Y') }}</span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">{{ __('receipt.label.method') }}</span>
                        <span class="info-value">{{ strtoupper($transaction->payment_method ?? '-') }}</span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">{{ __('receipt.label.officer') }}</span>
                        <span class="info-value">{{ $transaction->creator->name ?? 'System' }}</span>
                    </div>
                </div>
            </div>

            {{-- Line Items --}}
            <div class="section-title">{{ __('Invoice Details') }}</div>
            <table class="items-table">
                <thead>
                    <tr>
                        <th class="item-no">{{ __('receipt.table.no') }}</th>
                        <th>{{ __('receipt.table.description') }}</th>
                        <th style="text-align:center; width:100px;">{{ __('receipt.table.detail') }}</th>
                        <th style="text-align:right; width:140px;">{{ __('receipt.table.nominal') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($items as $index => $item)
                        <tr>
                            <td class="item-no">{{ $index + 1 }}</td>
                            <td>
                                <div class="item-name">{{ $item->feeType->name ?? '-' }}</div>
                                @if($item->description && $item->description !== ($item->feeType->name ?? ''))
                                    <div class="item-desc">{{ $item->description }}</div>
                                @endif
                            </td>
                            <td class="item-period">
                                @if($item->month && $item->year)
                                    {{ sprintf('%02d/%d', $item->month, $item->year) }}
                                @elseif($item->year)
                                    {{ $item->year }}
                                @else
                                    -
                                @endif
                            </td>
                            <td class="item-amount">{{ formatRupiah((float) $item->amount) }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" style="text-align:center; padding:20px; color:var(--light);">
                                {{ __('receipt.empty') }}
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>

            {{-- Total Paid --}}
            <div class="total-section">
                <div class="total-box">
                    <div class="total-label">{{ $isExpense ? __('receipt.total.expense') : __('receipt.total.payment') }}</div>
                    <div class="total-value">{{ formatRupiah((float) $transaction->total_amount) }}</div>
                </div>
            </div>
        </div>

        {{-- ── Footer ── --}}
        <footer class="receipt-footer">
            <div class="footer-grid">
                <div class="footer-meta">
                    {{ __('receipt.label.issued_at') }}: <strong>{{ optional($receiptIssuedAt ?? $transaction->created_at)->format('d/m/Y H:i:s') }}</strong><br>
                    {{ __('receipt.label.printed_at') }}: <strong>{{ optional($receiptPrintedAt ?? null)?->format('d/m/Y H:i:s') ?? now()->format('d/m/Y H:i:s') }}</strong><br>
                    {{ __('receipt.label.print_status') }}: <strong>{{ $receiptPrintStatus ?? 'ORIGINAL' }}</strong><br>
                    @if(!empty($verificationCode) && $verificationCode !== '-')
                        {{ __('receipt.footer.verification') }}: <strong>{{ $verificationCode }}</strong>
                    @endif
                </div>
                <div class="signature-block">
                    <div class="sig-title">{{ __('receipt.label_admin_tu') }}</div>
                    <div class="sig-line">{{ $transaction->creator->name ?? 'Administration' }}</div>
                    <div class="sig-hint">{{ __('receipt.footer.digitally_signed') }}</div>
                </div>
            </div>

            <div class="footer-note">
                {{ __('receipt.footer.official_receipt') }}
                @if(!empty($verificationUrl) && $verificationUrl !== '-')
                    <br>{{ __('receipt.footer.verification') }}: {{ $verificationUrl }}
                @endif
            </div>
        </footer>
    </main>

</body>
</html>
