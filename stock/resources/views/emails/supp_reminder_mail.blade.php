<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Purchase Order Reminder</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333;
        }
        .header {
            margin-bottom: 20px;
        }
        .table {
            border-collapse: collapse;
            width: 100%;
            margin-top: 20px;
        }
        .table th, .table td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: left;
        }
        .table th {
            background-color: #f4f4f4;
        }
        .footer {
            margin-top: 30px;
        }
        a.button {
            display: inline-block;
            padding: 10px 15px;
            background-color: #007bff;
            color: white;
            text-decoration: none;
            border-radius: 4px;
            margin-top: 15px;
        }
    </style>
</head>
<body>

    <div class="header">
        <h2>Dear {{ $request['firstname'] }} {{ $request['lastname'] }},</h2>
        <p>This is a reminder regarding the following purchase order(s). Please review the details below and proceed with the pending deliveries.</p>
    </div>

    <table class="table">
        <thead>
            <tr>
                <th>Item Name</th>
                <th>Item Code</th>
                <th>Asked Quantity</th>
                <th>Remaining Quantity</th>
                <th>PO Date</th>
                <th>Delivery Date</th>
            </tr>
        </thead>
        <tbody>
            @foreach($request['body'] as $item)
                <tr>
                    <td>{{ $item['item_name'] }}</td>
                    <td>{{ $item['item_code'] }}</td>
                    <td>{{ $item['asked_qty'] }}</td>
                    <td>{{ $item['remaining_qty'] }}</td>
                    <td>{{ \Carbon\Carbon::parse($item['po_date'])->format('d M Y') }}</td>
                    <td>{{ \Carbon\Carbon::parse($item['del_date'])->format('d M Y') }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <div class="footer">
        <p>You can view the full details and take action by clicking the link below:</p>
        <a href="{{ $request['invoice_link'] }}" class="button">View Purchase Order</a>
        <p>Thank you.</p>
    </div>

</body>
</html>
