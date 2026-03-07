<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ __('receipt.verify.title') }} - {{ $transaction->transaction_number }}</title>
    <style>
        body {
            margin: 0;
            font-family: Inter, Arial, sans-serif;
            background: #f8fafc;
            color: #0f172a;
        }

        .wrap {
            max-width: 760px;
            margin: 32px auto;
            padding: 0 16px;
        }

        .card {
            background: #fff;
            border: 1px solid #cbd5e1;
            border-radius: 12px;
            overflow: hidden;
        }

        .head {
            padding: 16px 20px;
            color: #fff;
            background: {{ $isValid ? '#0f766e' : '#b91c1c' }};
            font-weight: 600;
        }

        .body {
            padding: 16px 20px;
            font-size: 14px;
            line-height: 1.5;
        }

        .row {
            display: flex;
            justify-content: space-between;
            gap: 16px;
            padding: 6px 0;
            border-bottom: 1px dashed #e2e8f0;
        }

        .row:last-child {
            border-bottom: 0;
        }

        .label {
            color: #64748b;
        }

        .value {
            font-weight: 600;
            text-align: right;
        }
    </style>
</head>

<body>
    <div class="wrap">
        <div class="card">
            <div class="head">
                {{ $isValid ? __('receipt.verify.valid') : __('receipt.verify.invalid') }}
            </div>
            <div class="body">
                <div class="row">
                    <div class="label">{{ __('receipt.verify.doc_no') }}</div>
                    <div class="value">{{ $transaction->transaction_number }}</div>
                </div>
                <div class="row">
                    <div class="label">{{ __('receipt.verify.type') }}</div>
                    <div class="value">{{ strtoupper($transaction->type) }}</div>
                </div>
                <div class="row">
                    <div class="label">{{ __('receipt.verify.date') }}</div>
                    <div class="value">{{ $transaction->transaction_date->format('d/m/Y') }}</div>
                </div>
                <div class="row">
                    <div class="label">{{ __('receipt.verify.total') }}</div>
                    <div class="value">{{ formatRupiah((float) $transaction->total_amount) }}</div>
                </div>
                <div class="row">
                    <div class="label">{{ __('receipt.verify.status') }}</div>
                    <div class="value">{{ strtoupper($transaction->status) }}</div>
                </div>
                <div class="row">
                    <div class="label">{{ __('receipt.verify.code_sent') }}</div>
                    <div class="value">{{ $code ?: '-' }}</div>
                </div>
                <div class="row">
                    <div class="label">{{ __('receipt.verify.code_valid') }}</div>
                    <div class="value">{{ $expectedCode }}</div>
                </div>
            </div>
        </div>
    </div>
</body>

</html>
