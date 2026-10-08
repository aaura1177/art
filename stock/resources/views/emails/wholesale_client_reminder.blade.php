<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Wholesale Reminder</title>
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
        .table { border-collapse: collapse; width: 100%; margin-top: 16px; }
        .table th, .table td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        .table th { background: #f4f4f4; }
    </style>
</head>
<body>
    <p>Hello,</p>
    <p>
        This is a reminder for wholesale buyer order
        <strong>{{ $payload['buyer_orderno'] ?? '' }}</strong>.
        Delivery date: <strong>{{ $payload['delivery_date'] ?? '—' }}</strong>.
    </p>
    <p>Pending / remaining quantities:</p>
    <table class="table">
        <thead>
            <tr>
                <th>SKU</th>
                <th>Product</th>
                <th>Supplier</th>
                <th>Asked</th>
                <th>Remaining</th>
            </tr>
        </thead>
        <tbody>
            @forelse(($payload['lines'] ?? []) as $line)
                <tr>
                    <td>{{ $line['sku'] }}</td>
                    <td>{{ $line['product'] }}</td>
                    <td>{{ $line['supplier'] }}</td>
                    <td>{{ $line['asked'] }}</td>
                    <td>{{ $line['remaining'] }}</td>
                </tr>
            @empty
                <tr><td colspan="5">No remaining lines (or POs not raised yet). Please review the shipment in the system.</td></tr>
            @endempty
        </tbody>
    </table>
    <p style="margin-top:20px;">
        <a href="{{ $payload['view_url'] ?? '#' }}">Open shipment in system</a>
    </p>
    <p>Regards,<br>GlobalVision</p>
</body>
</html>
