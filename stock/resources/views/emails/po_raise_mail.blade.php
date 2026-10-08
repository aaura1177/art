<!DOCTYPE html>
<html>

<head>
    <title>Email</title>
</head>
<style>
    .table-container {
        max-width: 800px;
        margin: auto;
        /* background: white; */
        border-radius: 4px;
        /* box-shadow: 0 2px 10px rgba(0, 0, 0, 0.074); */
        border: 4px solid #9d9d9dd3;
        overflow: hidden;
    }

    table {
        width: 100%;
        border-collapse: collapse;
    }

    th,
    td {
        padding: 12px;
        text-align: left;
        /* border-bottom: 1px solid #ddd; */
    }
    td{
        border: 1px solid rgb(138, 138, 138);
    }

    th {
        background-color: #9d9d9dd3;
        color: white;
        text-transform: uppercase;
    }

    /* Responsive styles */
    @media (max-width: 600px) {

        th,
        td {
            display: block;
            text-align: right;
        }

        th {
            position: absolute;
            left: -9999px;
            /* Hide headers in small view */
        }

        td {
            text-align: left;
            padding-left: 50%;
            /* Add padding for text alignment */
            position: relative;
            padding: 12px 20px;
            border: none;
            border-bottom: 1px solid #ddd;
        }

        td::before {
            content: attr(data-label);
            position: absolute;
            left: 10px;
            width: 45%;
            padding-right: 10px;
            white-space: nowrap;
            text-align: left;
            font-weight: bold;
        }
    }
</style>

<body style="padding: 6%;background-color: rgba(0, 0, 0, 0.1);">

    <div style="background-color: #fff;padding: 30px;border-radius: 7px;box-shadow: 2px 3px 4px rgba(0, 0, 0, 0.1);">
        <div style="background-color: #b1b0b0;padding: 1px 10px;">
            <p style="text-align: center;">
                <img style="width: 90px;" src="https://inventory.artisanadmin.net/images/gvllogo.png"/>
            </p>
        </div>
        <br>
        <p style="font-family: Arial, Helvetica, sans-serif;">Dear Team,</p>
        <p style="font-family: Arial, Helvetica, sans-serif;">This is an automated request to raise a Purchase Order (PO) for the following products:</p>
        <div class="table-container">
            <table class="table">
                <thead>
                    <tr>
                        <th style="font-family: Arial, Helvetica, sans-serif;">#</th>
                        <th style="font-family: Arial, Helvetica, sans-serif;">Product Code</th>
                        <th style="font-family: Arial, Helvetica, sans-serif;">Quantity Required</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($details as $item)
                    <tr>
                        <td style="font-family: Arial, Helvetica, sans-serif;">{{ $loop->iteration }}</td>
                        <td style="font-family: Arial, Helvetica, sans-serif;">{{ $item['SKU'] }}</td>
                        <td style="font-family: Arial, Helvetica, sans-serif;">{{ $item ['quantity_to_order'] }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <br>
        <p style="font-family: Arial, Helvetica, sans-serif;">Please process this request at your earliest convenience.</p>
        <p style="font-family: Arial, Helvetica, sans-serif;">Thank you.</p>
        <p style="margin: 0;font-family: Arial, Helvetica, sans-serif;">Best regards,</p>
        <p style="margin: 0;font-family: Arial, Helvetica, sans-serif;">Global Vision Direct (P) Ltd.</p>
        <div>
</body>

</html>