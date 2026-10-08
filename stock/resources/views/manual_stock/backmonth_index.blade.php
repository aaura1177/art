@extends('layouts.app')

@section('content')
<div class="mx-2">
    <div class="row mx-0 my-2">
        <h2>Backmonth Stock</h2>
    </div>

    <div class="alert alert-info">
        <strong>Target date:</strong> {{ \Carbon\Carbon::parse($targetDate)->format('d-M-Y') }}<br>
        <strong>Edit window:</strong> Allowed till day {{ $editInfo['day_limit'] }} of current month.
        @if (!$editInfo['within_window'])
            <br><span class="text-danger">Editing window is closed for this month.</span>
        @endif
    </div>

    <div class="card mb-3">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered" id="backmonthInvoiceTable" width="100%" cellspacing="0">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Invoice No</th>
                            <th>Invoice Date</th>
                            <th>Buyer</th>
                            <th>Total Qty</th>
                            <th>Total Amount</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($invoices as $index => $invoice)
                            <tr>
                                <td>{{ $index + 1 }}</td>
                                <td>{{ $invoice->invoiceno }}</td>
                                <td>{{ $invoice->date ? \Carbon\Carbon::parse($invoice->date)->format('d-M-Y') : '' }}</td>
                                <td>{{ $invoice->buyer->name ?? $invoice->buyer->c_name ?? '' }}</td>
                                <td>{{ $invoice->totalquantity ?? 0 }}</td>
                                <td>{{ $invoice->totalamount ?? 0 }}</td>
                                <td>
                                    <a class="btn btn-primary btn-sm" href="{{ route('manual_stock.backmonth.show', $invoice->id) }}">
                                        View
                                    </a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection

