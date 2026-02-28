@props([
    'theme' => 'receipt',
    'title' => '',
    'documentNumber' => '',
    'watermarkText' => '',
    'verificationCode' => '-',
    'verificationUrl' => '-',
    'receiptIssuedAt' => null,
    'receiptPrintedAt' => null,
    'receiptPrintStatus' => 'ORIGINAL',
    'signerName' => null,
    'school' => [],
    'footerNote' => null,
    'totalLabel' => '',
    'totalValue' => '',
    'showVerification' => true,
    'compact' => false,
    'statusBadge' => null,
])
@php
    $themes = [
        'receipt'    => ['brand1' => '#154f84', 'brand2' => '#1d6394', 'brand3' => '#e8f3fa', 'ok' => '#176b73', 'line' => '#b9ccd8', 'soft' => '#f3f8fb', 'border' => '#9ab6c6', 'wm' => 'rgba(21, 79, 132, 0.08)', 'stripe' => '#f8fcff', 'totalBg' => '#e7f2f9', 'totalBorder' => '#84b3c9'],
        'expense'    => ['brand1' => '#7c4a1e', 'brand2' => '#96602e', 'brand3' => '#fef7ed', 'ok' => '#92400e', 'line' => '#d4b896', 'soft' => '#fdf8f3', 'border' => '#c4a57a', 'wm' => 'rgba(124, 74, 30, 0.08)', 'stripe' => '#fefcf8', 'totalBg' => '#fef3e2', 'totalBorder' => '#d4a85c'],
        'invoice'    => ['brand1' => '#374151', 'brand2' => '#4b5563', 'brand3' => '#f3f4f6', 'ok' => '#111827', 'line' => '#d1d5db', 'soft' => '#f9fafb', 'border' => '#9ca3af', 'wm' => 'rgba(55, 65, 81, 0.08)', 'stripe' => '#f9fafb', 'totalBg' => '#f3f4f6', 'totalBorder' => '#9ca3af'],
        'settlement' => ['brand1' => '#166534', 'brand2' => '#15803d', 'brand3' => '#ecfdf5', 'ok' => '#166534', 'line' => '#86efac', 'soft' => '#f0fdf4', 'border' => '#6bbe8a', 'wm' => 'rgba(22, 101, 52, 0.08)', 'stripe' => '#f7fdf9', 'totalBg' => '#dcfce7', 'totalBorder' => '#6bbe8a'],
    ];
    $t = $themes[$theme] ?? $themes['receipt'];

    $schoolName = $school['school_name'] ?? __('School');
    $schoolAddress = $school['school_address'] ?? '';
    $schoolPhone = $school['school_phone'] ?? '-';
    $schoolInitials = collect(preg_split('/\s+/', trim($schoolName)))
        ->filter()
        ->take(2)
        ->map(fn ($part) => strtoupper(substr($part, 0, 1)))
        ->implode('');
    $schoolInitials = $schoolInitials !== '' ? $schoolInitials : 'SC';
    $foundationLogo = $school['foundation_logo'] ?? '';
    $schoolLogo = $school['school_logo'] ?? '';

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
    $foundationLogoSrc = $toDataUri([
        $foundationLogo !== '' ? storage_path('app/public/' . ltrim($foundationLogo, '/')) : null,
        public_path('images/logo-yayasan.png'),
        public_path('images/logo-yayasan.jpg'),
        public_path('images/logo-yayasan.jpeg'),
        public_path('images/logo-yayasan.webp'),
        storage_path('app/public/yayasan_logo.png'),
        storage_path('app/public/yayasan_logo.jpg'),
        storage_path('app/public/yayasan_logo.jpeg'),
        storage_path('app/public/yayasan_logo.webp'),
    ]);
    $schoolLogoSrc = $toDataUri([
        $schoolLogo !== '' ? storage_path('app/public/' . ltrim($schoolLogo, '/')) : null,
        public_path('images/kwitansi-logo.png'),
        public_path('images/kwitansi-logo.jpg'),
        public_path('images/kwitansi-logo.jpeg'),
        public_path('images/kwitansi-logo.webp'),
        storage_path('app/public/logo.png'),
        storage_path('app/public/logo.jpg'),
        storage_path('app/public/logo.jpeg'),
        storage_path('app/public/logo.webp'),
    ]);

    $showWatermark = $watermarkText !== '';

    // Status badge overlay
    $badgeColors = [
        'paid'            => '#065f46',
        'unpaid'          => '#92400e',
        'partially_paid'  => '#1e40af',
        'partial'         => '#1e40af',
        'completed'       => '#065f46',
        'cancelled'       => '#991b1b',
        'void'            => '#991b1b',
    ];
    $badgeLabel = null;
    $badgeColor = '#6b7280';
    if ($statusBadge) {
        $normalised = strtolower(trim($statusBadge));
        $badgeColor = $badgeColors[$normalised] ?? '#6b7280';
        $badgeLabel = strtoupper(str_replace('_', ' ', $statusBadge));
    }
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title }} - {{ $documentNumber }}</title>
    <style>
        :root {
            --ink: #0f1f2e;
            --muted: #496378;
            --line: {{ $t['line'] }};
            --soft: {{ $t['soft'] }};
            --brand-1: {{ $t['brand1'] }};
            --brand-2: {{ $t['brand2'] }};
            --brand-3: {{ $t['brand3'] }};
            --ok: {{ $t['ok'] }};
            --border: {{ $t['border'] }};
            --wm: {{ $t['wm'] }};
            --stripe: {{ $t['stripe'] }};
            --total-bg: {{ $t['totalBg'] }};
            --total-border: {{ $t['totalBorder'] }};
        }

        * { box-sizing: border-box; }

        @page { size: A5 landscape; margin: 6mm; }

        html, body {
            margin: 0; padding: 0;
            font-family: "Inter", "Segoe UI", Tahoma, Arial, sans-serif;
            font-weight: 400;
            color: var(--ink);
            background: #ffffff;
            font-size: 10px;
            line-height: 1.25;
        }

        .sheet {
            min-height: 136mm; max-height: 136mm;
            border: 1px solid var(--border);
            border-radius: 8px;
            overflow: hidden;
            display: flex; flex-direction: column;
            position: relative;
        }

        .dynamic-watermark {
            position: absolute; top: 52%; left: 50%;
            transform: translate(-50%, -50%) rotate(-24deg);
            font-family: "IBM Plex Sans", "Inter", "Segoe UI", Tahoma, Arial, sans-serif;
            font-weight: 500; font-size: 20px; letter-spacing: 0.8px;
            color: var(--wm);
            white-space: nowrap; pointer-events: none; z-index: 0;
        }

        .topbar {
            padding: 2.5mm 4mm; color: #fff;
            background: linear-gradient(180deg, var(--brand-1), var(--brand-2));
            display: grid; grid-template-columns: 14mm 1fr 14mm;
            align-items: center; gap: 3mm;
        }

        .head-center { min-width: 0; text-align: center; }
        .logo-wrap { width: 14mm; height: 14mm; border-radius: 999px; background: #fff; display: flex; align-items: center; justify-content: center; overflow: hidden; border: 1px solid #ffffff55; flex: 0 0 auto; }
        .logo-wrap-square { border-radius: 3px; }
        .logo-wrap img { width: 100%; height: 100%; object-fit: contain; }
        .logo-fallback { font-size: 7px; color: var(--brand-1); font-weight: 700; text-align: center; }
        .head-center .name { font-size: 11.5px; font-family: "IBM Plex Sans", "Inter", "Segoe UI", Tahoma, Arial, sans-serif; font-weight: 500; letter-spacing: 0.1px; white-space: normal; overflow: visible; text-overflow: clip; line-height: 1.15; }
        .head-center .address { font-size: 8.5px; opacity: 0.92; white-space: normal; overflow: hidden; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; line-height: 1.2; max-height: 2.4em; }
        .doc-title { margin: 0; font-size: 11.5px; font-family: "IBM Plex Sans", "Inter", "Segoe UI", Tahoma, Arial, sans-serif; font-weight: 500; letter-spacing: 0.35px; line-height: 1.1; }

        .body { padding: 3.5mm 4mm 3mm; display: flex; flex-direction: column; gap: 2.5mm; flex: 1; min-height: 0; position: relative; z-index: 1; }

        .meta-grid { display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 2mm; }
        .meta-card { background: var(--soft); border: 1px solid var(--line); border-radius: 6px; padding: 1.8mm 2.2mm; }
        .meta-label { font-size: 8px; color: var(--muted); text-transform: uppercase; letter-spacing: 0.3px; margin-bottom: 0.8mm; }
        .meta-value { font-size: 10px; font-weight: 400; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }

        .table-wrap { border: 1px solid var(--line); border-radius: 6px; overflow: hidden; flex: 1; min-height: 0; background: #fff; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border-bottom: 1px solid #e2e8f0; padding: 1.4mm 1.8mm; vertical-align: top; }
        th { background: var(--brand-3); color: var(--brand-1); font-size: 8px; text-transform: uppercase; letter-spacing: 0.35px; font-weight: 700; }
        tbody tr:nth-child(even) { background: var(--stripe); }
        tbody tr:last-child td { border-bottom: none; }

        .c-no { width: 8%; text-align: center; }
        .c-item { width: 46%; }
        .c-note { width: 24%; }
        .c-amt { width: 22%; text-align: right; white-space: nowrap; }
        .item-name { font-weight: 400; }
        .item-note { color: #475569; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; max-width: 0; }
        .compact th, .compact td { padding-top: 1.1mm; padding-bottom: 1.1mm; font-size: 9px; }
        .summary-row td { background: var(--soft); color: var(--muted); font-style: italic; }

        .footer { display: grid; grid-template-columns: 1fr auto; gap: 3mm; align-items: end; }
        .foot-note { color: var(--muted); font-size: 8.2px; }

        .total-box { min-width: 56mm; border: 1px solid var(--total-border); border-radius: 6px; overflow: hidden; }
        .total-head { background: var(--total-bg); padding: 1.3mm 2mm; font-size: 8px; color: var(--brand-1); text-transform: uppercase; letter-spacing: 0.3px; font-weight: 700; }
        .total-value { padding: 2mm; text-align: right; font-size: 14px; font-family: "IBM Plex Sans", "Inter", "Segoe UI", Tahoma, Arial, sans-serif; font-weight: 500; color: var(--ok); }
        .stamp { margin-top: 0.7mm; text-align: right; font-size: 7.8px; color: var(--muted); }

        /* Invoice multi-row totals */
        .totals-table { margin-left: auto; width: 64mm; border-collapse: collapse; }
        .totals-table td { border: 1px solid var(--line); padding: 1.2mm 1.8mm; }
        .totals-table td:first-child { background: var(--soft); font-weight: 600; font-size: 9px; }
        .totals-table td:last-child { text-align: right; font-weight: 700; white-space: nowrap; }

        /* Invoice signature block */
        .signature { justify-self: end; width: 54mm; text-align: center; align-self: end; display: flex; flex-direction: column; align-items: center; }
        .signature .label { margin: 0; font-size: 9px; color: #374151; }
        .signature .line { border-top: 1px solid var(--ink); padding-top: 1mm; margin-top: 4mm; font-weight: 700; font-size: 10px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .signature .hint { margin-top: 0.5mm; font-size: 8px; color: var(--muted); }

        /* Invoice meta uses 2-column table layout */
        .meta-2col { display: grid; grid-template-columns: 1fr 1fr; gap: 4mm; }
        .meta-table { width: 100%; border-collapse: collapse; table-layout: fixed; }
        .meta-table td { padding: 0.7mm 0; vertical-align: top; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .meta-table td:first-child { width: 34mm; color: var(--muted); }

        /* Status badges (inline) */
        .status-badge { display: inline-block; padding: 0.7mm 2mm; font-size: 8px; font-weight: 700; border-radius: 1.5mm; text-transform: uppercase; letter-spacing: 0.2px; white-space: nowrap; }
        .status-unpaid { background: #fef3c7; color: #92400e; }
        .status-partially_paid { background: #dbeafe; color: #1e40af; }
        .status-paid { background: #d1fae5; color: #065f46; }

        /* Status badge overlay — centered, outline only */
        .status-overlay {
            position: absolute;
            top: 50%; left: 50%;
            transform: translate(-50%, -50%) rotate(-18deg);
            z-index: 2;
            pointer-events: none;
            padding: 2mm 6mm;
            border: 2px solid var(--status-color, #6b7280);
            border-radius: 4px;
            background: transparent;
            font-family: "IBM Plex Sans", "Inter", "Segoe UI", Tahoma, Arial, sans-serif;
            font-weight: 700;
            font-size: 22px;
            letter-spacing: 1.5px;
            text-transform: uppercase;
            color: var(--status-color, #6b7280);
            opacity: 0.35;
            white-space: nowrap;
        }

        @media print {
            html, body { width: 100%; height: 100%; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
        }
    </style>
</head>

<body onload="window.print()">
    <main class="sheet">
        @if($showWatermark)
            <div class="dynamic-watermark">{{ $watermarkText }}</div>
        @endif

        @if($badgeLabel)
            <div class="status-overlay" style="--status-color: {{ $badgeColor }}">{{ $badgeLabel }}</div>
        @endif

        <section class="topbar">
            <div class="logo-wrap logo-wrap-square" aria-label="Logo Yayasan">
                @if ($foundationLogoSrc)
                    <img src="{{ $foundationLogoSrc }}" alt="Logo Yayasan">
                @else
                    <div class="logo-fallback">YYS</div>
                @endif
            </div>

            <div class="head-center">
                <div class="name">{{ $schoolName }}</div>
                <div class="address">{!! nl2br(e($schoolAddress !== '' ? $schoolAddress : __('receipt.address_not_set'))) !!}</div>
                <h1 class="doc-title">{{ $title }}</h1>
            </div>

            <div class="logo-wrap" aria-label="Logo {{ $schoolName }}">
                @if ($schoolLogoSrc)
                    <img src="{{ $schoolLogoSrc }}" alt="Logo {{ $schoolName }}">
                @else
                    <div class="logo-fallback">{{ $schoolInitials }}</div>
                @endif
            </div>
        </section>

        <section class="body">
            {{ $meta }}

            {{ $items }}

            <section class="footer">
                <div class="foot-note">
                    @if($footerNote)
                        {{ $footerNote }}<br>
                    @endif
                    @if($showVerification)
                        {{ __('receipt.label.issued_at') }}: {{ optional($receiptIssuedAt)->format('d/m/Y H:i:s') ?? '-' }}<br>
                        {{ __('receipt.label.printed_at') }}: {{ optional($receiptPrintedAt)->format('d/m/Y H:i:s') ?? now()->format('d/m/Y H:i:s') }}<br>
                        {{ __('receipt.label.print_status') }}: {{ $receiptPrintStatus }}<br>
                        {{ __('receipt.footer.verification') }}: {{ $verificationCode }}
                    @endif
                </div>
                <div>
                    {{ $summary }}

                    @if($showVerification && $signerName)
                        <div class="stamp">
                            Digital Signature: {{ $signerName }}<br>
                            {{ __('receipt.footer.verification') }}: {{ $verificationUrl }}
                        </div>
                    @endif
                </div>
            </section>

            @if(isset($footerExtra))
                {{ $footerExtra }}
            @endif
        </section>
    </main>
</body>

</html>
