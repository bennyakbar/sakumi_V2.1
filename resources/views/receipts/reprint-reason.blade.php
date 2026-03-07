<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reprint Reason - {{ $transaction->transaction_number }}</title>
    <style>
        body { margin: 0; font-family: Inter, Arial, sans-serif; background: #f8fafc; color: #0f172a; }
        .wrap { min-height: 100vh; display: flex; align-items: center; justify-content: center; padding: 16px; }
        .card { width: 100%; max-width: 520px; background: #fff; border: 1px solid #cbd5e1; border-radius: 12px; padding: 20px; }
        h1 { margin: 0 0 8px; font-size: 18px; }
        p { margin: 0 0 16px; color: #475569; font-size: 14px; }
        label { display: block; margin-bottom: 6px; font-size: 12px; font-weight: 600; color: #334155; }
        select, input { width: 100%; border: 1px solid #cbd5e1; border-radius: 8px; padding: 10px; font-size: 14px; }
        .row { margin-bottom: 12px; }
        button { width: 100%; border: 0; border-radius: 8px; background: #1e40af; color: #fff; padding: 10px; font-weight: 600; cursor: pointer; }
    </style>
</head>

<body>
    <div class="wrap">
        <form method="GET" action="{{ route('receipts.print', $transaction) }}" class="card">
            <h1>Reprint Authorization</h1>
            <p>Receipt {{ $transaction->transaction_number }} has been printed {{ $receipt->print_count }} time(s). Please provide reprint reason.</p>

            <div class="row">
                <label for="reason_type">Reason</label>
                <select id="reason_type" name="reason_type" required onchange="toggleOther(this.value)">
                    <option value="">Select reason</option>
                    <option value="lost">Lost</option>
                    <option value="damaged">Damaged</option>
                    <option value="parent_request">Parent request</option>
                    <option value="other">Other</option>
                </select>
            </div>

            <div class="row" id="other-wrap" style="display:none;">
                <label for="reason_other">Other Reason</label>
                <input id="reason_other" name="reason_other" type="text" placeholder="Type custom reason">
            </div>

            <button type="submit">Continue to Print</button>
        </form>
    </div>

    <script>
        function toggleOther(value) {
            const wrap = document.getElementById('other-wrap');
            const input = document.getElementById('reason_other');
            const isOther = value === 'other';
            wrap.style.display = isOther ? 'block' : 'none';
            input.required = isOther;
            if (!isOther) input.value = '';
        }
    </script>
</body>

</html>

