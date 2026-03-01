<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Receipt Verification - {{ $receipt->verification_code }}</title>
    <style>
        body { margin: 0; font-family: Inter, Arial, sans-serif; background: #f8fafc; color: #0f172a; }
        .wrap { max-width: 760px; margin: 32px auto; padding: 0 16px; }
        .card { background: #fff; border: 1px solid #cbd5e1; border-radius: 12px; overflow: hidden; }
        .head { padding: 16px 20px; color: #fff; background: {{ $isVoided ? '#b91c1c' : '#0f766e' }}; font-weight: 700; }
        .body { padding: 16px 20px; font-size: 14px; line-height: 1.5; }
        .row { display: flex; justify-content: space-between; gap: 16px; padding: 8px 0; border-bottom: 1px dashed #e2e8f0; }
        .row:last-child { border-bottom: 0; }
        .label { color: #64748b; }
        .value { font-weight: 600; text-align: right; }
    </style>
</head>

<body>
    <div class="wrap">
        <div class="card">
            <div class="head">RECEIPT STATUS: {{ $status }}</div>
            <div class="body">
                <div class="row">
                    <div class="label">Verification Code</div>
                    <div class="value">{{ $receipt->verification_code }}</div>
                </div>
                <div class="row">
                    <div class="label">Issued At</div>
                    <div class="value">{{ optional($receipt->issued_at)->format('d/m/Y H:i:s') ?? '-' }}</div>
                </div>
                <div class="row">
                    <div class="label">Printed At</div>
                    <div class="value">{{ optional($receipt->printed_at)->format('d/m/Y H:i:s') ?? '-' }}</div>
                </div>
                <div class="row">
                    <div class="label">Print Count</div>
                    <div class="value">{{ $receipt->print_count }}</div>
                </div>
                <div class="row">
                    <div class="label">Receipt Number</div>
                    <div class="value">{{ $transaction?->transaction_number ?? '-' }}</div>
                </div>
                <div class="row">
                    <div class="label">Amount</div>
                    <div class="value">{{ formatRupiah((float) ($transaction?->total_amount ?? 0)) }}</div>
                </div>
            </div>
        </div>
    </div>
</body>

</html>

