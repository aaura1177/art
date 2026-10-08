@extends('layouts.app')

@section('content')
    <div class="row mx-3 my-2">
        <h2>Multi-PO Consumable Invoices</h2>
        <a class="btn btn-outline-secondary float-end" href="{{ url('/supplier-dashboard/invoice-orders?kind=consumable') }}">Single-PO invoices</a>
    </div>
    <div class="card mb-3">
        <div class="card-body">
            <table class="table table-bordered" id="dataTable">
                <thead>
                    <tr>
                        <th>PO No.</th>
                        <th>Supplier Invoice No.</th>
                        <th>Internal Invoice No.</th>
                        <th>Total Amount</th>
                        <th>Status</th>
                        <th>Options</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($supplierInvoice as $inv)
                        <tr>
                            <td>{{ \App\Support\SupplierMultiPoSupport::displayMultiPoNumbers($inv) }}</td>
                            <td>{{ $inv->supplier_invoice_number }}</td>
                            <td>{{ $inv->internal_invoice_number }}</td>
                            <td>{{ $inv->tamount }}</td>
                            <td>
                                @if ($inv->status == 2)
                                    Cancelled
                                @elseif ($inv->is_approved == 1)
                                    Approved
                                @else
                                    Pending
                                @endif
                            </td>
                            <td>
                                <a class="btn btn-primary btn-sm" style="color:#fff;"
                                    href="{{ url('/supplier-dashboard/invoiceModal/multi/' . $inv->id) }}"
                                    target="_blank"><i class="fa fa-eye"></i></a>
                                @if ($inv->status != 2 && $inv->is_approved != 1)
                                    <a class="btn btn-warning btn-sm" style="color:#fff;"
                                        href="{{ url('/supplier-dashboard/edit-invoice/multi-consumable/' . $inv->id) }}"
                                        target="_blank"><i class="fa fa-edit"></i></a>
                                    <a class="btn btn-danger btn-sm" style="color:#fff;"
                                        href="{{ url('/supplier-dashboard/cancelInvoice/multi-consumable/' . $inv->id) }}"
                                        onclick="return confirm('Cancel this invoice?')"><i
                                            class="fa fa-window-close"></i></a>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
@endsection

@section('footer')
    <script>$(function() { $('#dataTable').DataTable(); });</script>
@endsection
