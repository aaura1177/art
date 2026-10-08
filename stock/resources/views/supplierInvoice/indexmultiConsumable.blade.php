@extends('layouts.app')

@section('content')
    <div class="row mx-3 my-2">
        <h2>Multi-PO Consumable Supplier Invoices</h2>
        <a class="btn btn-outline-secondary float-end" href="{{ url('/supplierInvoice') }}">All invoices</a>
    </div>
    <div class="card mb-3">
        <div class="card-body">
            <form method="GET" class="mb-3">
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Search" class="form-control d-inline-block w-auto">
                <button type="submit" class="btn btn-primary">Search</button>
            </form>
            <table class="table table-bordered" id="dataTabless">
                <thead>
                    <tr>
                        <th>PO No.</th>
                        <th>Invoice Date</th>
                        <th>Supplier Invoice No.</th>
                        <th>Internal Invoice No.</th>
                        <th>Total Amount</th>
                        <th>Status</th>
                        <th>Options</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($supplierInvoices as $supplierInvoice)
                        @php
                            $status = \App\Support\SupplierMultiPoSupport::adminSupplierInvoiceStatus($supplierInvoice);
                        @endphp
                        <tr>
                            <td>{{ \App\Support\SupplierMultiPoSupport::displayMultiPoNumbers($supplierInvoice) }}</td>
                            <td>
                                @if ($supplierInvoice->invoice_date)
                                    {{ \Carbon\Carbon::parse($supplierInvoice->invoice_date)->format('d-M-Y') }}
                                @endif
                            </td>
                            <td>{{ $supplierInvoice->supplier_invoice_number }}</td>
                            <td>{{ $supplierInvoice->internal_invoice_number }}</td>
                            <td>{{ $supplierInvoice->tamount }}</td>
                            <td>
                                <span class="{{ $status['class'] }}">{{ $status['label'] }}</span>
                            </td>
                            <td>
                                @if ($supplierInvoice->status != 2)
                                    <a class="btn btn-primary btn-sm" style="color:#fff;"
                                        href="{{ url('/supplierInvoice/modal/multi/' . $supplierInvoice->id) }}"
                                        target="_blank" title="View"><i class="fa fa-eye"></i></a>
                                    @if ($supplierInvoice->is_approved == 0)
                                        <a class="btn btn-success btn-sm" style="color:#fff;"
                                            href="{{ url('/supplierInvoice/approve/multi-consumable/' . $supplierInvoice->id) }}"
                                            title="Approve"><i class="fas fa-check-circle"></i></a>
                                    @endif
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
            {{ $supplierInvoices->links() }}
        </div>
    </div>
@endsection
