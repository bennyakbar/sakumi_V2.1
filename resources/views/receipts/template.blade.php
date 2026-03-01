<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="UTF-8">
    <title>{{ __('receipt.title.payment_receipt') }} - {{ $transaction->transaction_number }}</title>
    <style>
        @page {
            size: A5 landscape;
            margin: 10mm;
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            font-family: "Inter", Arial, Helvetica, sans-serif;
            font-weight: 400;
            color: var(--ink);
            font-size: 11px;
            line-height: 1.35;
        }

        .school-address {
            white-space: pre-line;
        }

        .receipt {
            --ink: #0f1f2e;
            --muted: #496378;
            --line-strong: #23445f;
            --line-soft: #b9ccd8;
            --surface-soft: #f3f8fb;
            --head-bg: #e8f3fa;
            --accent: #154f84;
            --amount: #176b73;
            border: 1px solid var(--line-strong);
            padding: 12mm;
            min-height: 100%;
            position: relative;
        }

        .receipt.expense-theme {
            --ink: #2a1715;
            --muted: #76524a;
            --line-strong: #5d2528;
            --line-soft: #d7b9a9;
            --surface-soft: #fcf7f4;
            --head-bg: #fff2ea;
            --accent: #7a2431;
            --amount: #b45309;
        }

        .receipt.expense-theme .watermark.dynamic {
            color: rgba(122, 36, 49, 0.08);
        }

        .watermark {
            position: absolute;
            top: 45%;
            left: 50%;
            transform: translate(-50%, -50%) rotate(-20deg);
            font-size: 44px;
            font-weight: 700;
            letter-spacing: 4px;
            color: rgba(220, 38, 38, 0.2);
        }

        .watermark.dynamic {
            top: 60%;
            font-size: 24px;
            letter-spacing: 2px;
            color: rgba(21, 79, 132, 0.08);
            transform: translate(-50%, -50%) rotate(-24deg);
        }

        .header-table {
            width: 100%;
            border-bottom: 1px solid var(--line-strong);
            padding-bottom: 6mm;
            margin-bottom: 6mm;
            border-collapse: collapse;
        }

        .header-table td {
            vertical-align: middle;
        }

        .header-logo {
            width: 20mm;
        }

        .logo-box {
            width: 16mm;
            height: 16mm;
            border: 1px solid var(--line-soft);
            border-radius: 999px;
            text-align: center;
            font-size: 7px;
            font-weight: 700;
            color: var(--accent);
            letter-spacing: 0.3px;
            overflow: hidden;
            display: flex;
            align-items: center;
            justify-content: center;
            text-transform: uppercase;
            background: #ffffff;
        }
        .logo-box-square {
            border-radius: 2px;
        }

        .logo-box img {
            width: 100%;
            height: 100%;
            max-width: 16mm;
            max-height: 16mm;
            object-fit: contain;
            display: block;
        }

        .title {
            margin: 0;
            font-size: 18px;
            font-family: "IBM Plex Sans", "Inter", Arial, Helvetica, sans-serif;
            font-weight: 500;
            color: var(--accent);
        }

        .meta {
            width: 100%;
            margin-bottom: 6mm;
        }

        .meta td {
            vertical-align: top;
            width: 50%;
        }

        .meta-table {
            width: 100%;
            border-collapse: collapse;
        }

        .meta-table td {
            padding: 1mm 0;
        }

        .meta-table td:first-child {
            width: 36mm;
            color: var(--muted);
        }

        .items {
            width: 100%;
            border-collapse: collapse;
        }

        .items th,
        .items td {
            border: 1px solid var(--line-strong);
            padding: 2mm;
            vertical-align: top;
        }

        .items th {
            background: var(--head-bg);
            color: var(--accent);
            font-size: 10px;
            text-transform: uppercase;
        }

        .text-right {
            text-align: right;
        }

        .text-center {
            text-align: center;
        }

        .total {
            width: 70mm;
            margin-top: 4mm;
            margin-left: auto;
            border-collapse: collapse;
        }

        .total td {
            border: 1px solid var(--line-strong);
            padding: 2mm;
        }

        .total td:first-child {
            background: var(--head-bg);
            font-weight: 700;
        }

        .total td:last-child {
            text-align: right;
            font-family: "IBM Plex Sans", "Inter", Arial, Helvetica, sans-serif;
            font-weight: 500;
            color: var(--amount);
        }

        .footer {
            border-top: 1px solid var(--line-strong);
            margin-top: 8mm;
            padding-top: 6mm;
        }

        .signature {
            width: 60mm;
            margin-left: auto;
            text-align: center;
        }

        .signature .label {
            margin-bottom: 16mm;
            font-size: 10px;
            color: var(--muted);
        }

        .signature .line {
            border-top: 1px solid var(--line-strong);
            padding-top: 1.5mm;
            font-weight: 700;
            font-size: 11px;
        }

        .signature .hint {
            font-size: 9px;
            color: var(--muted);
            margin-top: 1mm;
        }
    </style>
</head>

<body>
    @php
        $school_name = $school_name ?? __('School');
        $school_address = $school_address ?? '';
        $school_phone = $school_phone ?? '-';
        $school_initials = collect(preg_split('/\s+/', trim($school_name)))
            ->filter()
            ->take(2)
            ->map(fn ($part) => strtoupper(substr($part, 0, 1)))
            ->implode('');
        $school_initials = $school_initials !== '' ? $school_initials : 'SC';
        $foundation_logo = $foundation_logo ?? '';
        $school_logo = $school_logo ?? '';
        $toDataUri = static function (array $candidates): ?string {
            foreach ($candidates as $path) {
                if (!is_string($path) || $path === '' || !file_exists($path)) {
                    continue;
                }
                $contents = @file_get_contents($path);
                if ($contents === false) {
                    continue;
                }
                $mime = @mime_content_type($path) ?: 'image/png';
                return 'data:' . $mime . ';base64,' . base64_encode($contents);
            }
            return null;
        };
        $foundation_logo_src = $toDataUri([
            $foundation_logo !== '' ? storage_path('app/public/' . ltrim($foundation_logo, '/')) : null,
            public_path('images/logo-yayasan.png'),
            public_path('images/logo-yayasan.jpg'),
            public_path('images/logo-yayasan.jpeg'),
            public_path('images/logo-yayasan.webp'),
            storage_path('app/public/yayasan_logo.png'),
            storage_path('app/public/yayasan_logo.jpg'),
            storage_path('app/public/yayasan_logo.jpeg'),
            storage_path('app/public/yayasan_logo.webp'),
        ]);
        $school_logo_src = $toDataUri([
            $school_logo !== '' ? storage_path('app/public/' . ltrim($school_logo, '/')) : null,
            public_path('images/kwitansi-logo.png'),
            public_path('images/kwitansi-logo.jpg'),
            public_path('images/kwitansi-logo.jpeg'),
            public_path('images/kwitansi-logo.webp'),
            storage_path('app/public/logo.png'),
            storage_path('app/public/logo.jpg'),
            storage_path('app/public/logo.jpeg'),
            storage_path('app/public/logo.webp'),
        ]);
        $isExpense = ($transaction->type ?? 'income') === 'expense';
    @endphp

    <main class="receipt {{ $isExpense ? 'expense-theme' : '' }}">
        @if (!empty($cancelled))
            <div class="watermark">CANCELLED</div>
        @endif
        <div class="watermark dynamic">{{ $watermark_text ?? '' }}</div>

        <section>
            <table class="header-table">
                <tr>
                    <td class="header-logo">
                        <div class="logo-box logo-box-square">
                            @if ($foundation_logo_src)
                                <img src="{{ $foundation_logo_src }}" alt="Logo Yayasan">
                            @else
                                YYS
                            @endif
                        </div>
                    </td>
                    <td>
                        <h1 class="title">{{ $isExpense ? __('receipt.title.expense_receipt') : __('receipt.title.payment_receipt') }}</h1>
                        <div><strong>{{ $school_name }}</strong></div>
                        <div class="school-address">{{ $school_address !== '' ? $school_address : __('receipt.address_not_set') }}</div>
                        <div>{{ __('receipt.label.phone') }}: {{ $school_phone ?: '-' }}</div>
                    </td>
                    <td class="header-logo" style="text-align:right;">
                        <div class="logo-box" style="margin-left:auto;">
                            @if (!empty($show_logo) && $school_logo_src)
                                <img src="{{ $school_logo_src }}" alt="{{ __('receipt.logo_fallback') }}">
                            @else
                                {{ $school_initials }}
                            @endif
                        </div>
                    </td>
                </tr>
            </table>
        </section>

        <table class="meta">
            <tr>
                <td>
                    <table class="meta-table">
                        <tr>
                            <td>{{ __('receipt.label.transaction_no') }}</td>
                            <td>: {{ $transaction->transaction_number }}</td>
                        </tr>
                        <tr>
                            <td>{{ __('receipt.label.date') }}</td>
                            <td>: {{ $transaction->transaction_date->format('d/m/Y') }}</td>
                        </tr>
                        <tr>
                            <td>{{ __('receipt.label.payment_method') }}</td>
                            <td>: {{ ucfirst($transaction->payment_method ?? '-') }}</td>
                        </tr>
                    </table>
                </td>
                <td>
                    <table class="meta-table">
                        <tr>
                            <td>{{ $isExpense ? __('receipt.label.transaction_type') : __('receipt.label.student_name') }}</td>
                            <td>: {{ $isExpense ? __('receipt.expense_type') : ($transaction->student->name ?? '-') }}</td>
                        </tr>
                        <tr>
                            <td>{{ $isExpense ? __('receipt.label.notes') : __('receipt.label.class') }}</td>
                            <td>: {{ $isExpense ? ($transaction->description ?: '-') : ($transaction->student->schoolClass->name ?? '-') }}</td>
                        </tr>
                        <tr>
                            <td>{{ __('receipt.label.officer') }}</td>
                            <td>: {{ $transaction->creator->name ?? 'System' }}</td>
                        </tr>
                    </table>
                </td>
            </tr>
        </table>

        <table class="items">
            <thead>
                <tr>
                    <th class="text-center" style="width:10%;">{{ __('receipt.table.no') }}</th>
                    <th style="width:45%;">{{ $isExpense ? __('receipt.table.expense_desc') : __('receipt.table.description') }}</th>
                    <th style="width:25%;">{{ __('receipt.table.detail') }}</th>
                    <th class="text-right" style="width:20%;">{{ __('receipt.table.nominal') }}</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($transaction->items as $index => $item)
                    <tr>
                        <td class="text-center">{{ $index + 1 }}</td>
                        <td>{{ $item->feeType->name ?? '-' }}</td>
                        <td>{{ $item->description ?: '-' }}</td>
                        <td class="text-right">{{ formatRupiah((float) $item->amount) }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="text-center">{{ __('receipt.empty') }}</td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        <table class="total">
            <tr>
                <td>{{ $isExpense ? __('receipt.total.expense') : __('receipt.total.payment') }}</td>
                <td>{{ formatRupiah((float) $transaction->total_amount) }}</td>
            </tr>
        </table>

        <section class="footer">
            <div style="font-size:10px; color:#6b7280; margin-bottom:4mm;">
                Dokumen ini merupakan bukti pembayaran resmi sekolah.<br>
                {{ __('receipt.label.issued_at') }}: {{ optional($receiptIssuedAt ?? null)->format('d/m/Y H:i:s') ?? optional($transaction->created_at)->format('d/m/Y H:i:s') ?? '-' }}<br>
                {{ __('receipt.label.printed_at') }}: {{ optional($receiptPrintedAt ?? null)->format('d/m/Y H:i:s') ?? now()->format('d/m/Y H:i:s') }}<br>
                {{ __('receipt.label.print_status') }}: {{ $receiptPrintStatus ?? 'ORIGINAL' }}<br>
                {{ __('receipt.footer.verification') }}: <strong>{{ $verification_code ?? '-' }}</strong><br>
                {{ __('receipt.footer.verification') }}: {{ $verification_url ?? '-' }}
            </div>
            <div class="signature">
                <div class="label">{{ __('receipt.label_digital_sig') }}<br>{{ __('receipt.label_admin_tu') }}</div>
                <div class="line">{{ $transaction->creator->name ?? 'Administration Admin' }}</div>
                <div class="hint">{{ __('receipt.footer.digitally_signed') }}</div>
            </div>
        </section>
    </main>
</body>

</html>
