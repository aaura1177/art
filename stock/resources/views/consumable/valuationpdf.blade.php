@extends('layouts.modal')
<body>
<style>
    .table th {
        font-size: 12px !important;
    }
    .table td {
        font-size: 11px !important;
    }
</style>
    <div class="container">
        <div style="text-align:center;">
            <img src="{{ url('uploads/' . $companyDetails->logoUrl) }}" alt="Logo Image" width='100px' />
            <h1>Consumable Valuation</h1>
            <h5 style="font-size:12px;">As on {{ isset($from) ? date('d F Y', strtotime($from)) : date('d F Y') }}</h5>
        </div>
        <div class="row box-space" id="clogo">
            <table class="table table-bordered" width="100%" cellspacing="0">
                <thead>
                    <tr>
                        <th>Code</th>
                        <th>Name</th>
                        <th>Quantity</th>
                        <th>Rate</th>
                        <th>Total Value</th>
                    </tr>
                </thead>
                <tbody>
                    @if (isset($valuationRows))
                        @foreach ($valuationRows as $row)
                            <tr>
                                <td>{{ $row['code'] }}</td>
                                <td>{{ $row['name'] }}</td>
                                <td>{{ $row['quantity'] }}</td>
                                <td>{{ $row['rate'] }}</td>
                                <td>{{ $row['total_value'] }}</td>
                            </tr>
                        @endforeach
                    @endif
                </tbody>
            </table>
        </div>
    </div>
</body>
