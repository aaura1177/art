@extends('layouts.app')

@section('content')
    <div class="row mx-3 my-2">
        <h2>Multi-PO Carton Supplier Invoices</h2>
        <a class="btn btn-outline-secondary float-end me-2" href="{{ url('/supplierInvoice/carton') }}">Single carton invoices</a>
    </div>
    <div class="card mb-3">
        <div class="card-body">
            <form method="GET" class="mb-3">
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Search" class="form-control d-inline-block w-auto">
                <button type="submit" class="btn btn-primary">Search</button>
            </form>
            <div class="table-responsive">
                <table class="table table-bordered" id="dataTable" width="100%" cellspacing="0">
                    <thead>
                        <tr>
                            <th>PO No.</th>
                            <th>Supplier Invoice No.</th>
                            <th>Supplier Name</th>
                            <th>Internal Invoice No.</th>
                            <th>Eway Bill No.</th>
                            <th>Vehicle No.</th>
                            <th>Total GST</th>
                            <th>Sub Total</th>
                            <th>Total Amount</th>
                            <th>Status</th>
                            <th style="min-width:120px;">Options</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($supplierInvoices as $supplierInvoice)
                            @php
                                $status = \App\Support\SupplierMultiPoSupport::adminSupplierInvoiceStatus($supplierInvoice);
                                $supplierName = optional(optional($supplierInvoice->user)->supplier)->c_name
                                    ?? optional($supplierInvoice->supplierData)->c_name
                                    ?? optional($supplierInvoice->supplier)->c_name
                                    ?? '';
                            @endphp
                            <tr>
                                <td>{{ \App\Support\SupplierMultiPoSupport::displayMultiPoNumbers($supplierInvoice) }}</td>
                                <td>{{ $supplierInvoice->supplier_invoice_number }}</td>
                                <td>{{ $supplierName }}</td>
                                <td>{{ $supplierInvoice->internal_invoice_number }}</td>
                                <td>
                                    @if ($supplierInvoice->eway_bill_pdf == '')
                                        {{ $supplierInvoice->eway_bill_no }}
                                    @endif
                                    @if ($supplierInvoice->eway_bill_pdf != '')
                                        <a href="{{ url('/supplierInvoice/download/' . $supplierInvoice->id) }}">{{ $supplierInvoice->eway_bill_no }}</a>
                                    @endif
                                </td>
                                <td>{{ $supplierInvoice->vehicle_no }}</td>
                                <td>{{ $supplierInvoice->tamount - $supplierInvoice->subTotal }}</td>
                                <td>{{ $supplierInvoice->subTotal }}</td>
                                <td>{{ $supplierInvoice->tamount }}</td>
                                <td>
                                    <span class="{{ $status['class'] }}">{{ $status['label'] }}</span>
                                </td>
                                <td>
                                    @if ($supplierInvoice->status == 2)
                                        Cancelled by Supplier
                                    @else
                                        <span class="tool-tip1" data-bs-toggle="tooltip" data-bs-placement="top" title="View">
                                            <a class="btn btn-primary btn-sm" style="color:#fff;"
                                                href="{{ url('/supplierInvoice/modal/multi/' . $supplierInvoice->id) }}"
                                                target="_blank"><i class="fa fa-eye"></i></a>
                                        </span>
                                        <span class="tool-tip1" data-bs-toggle="tooltip" data-bs-placement="top" title="Returns">
                                            <a class="btn btn-warning btn-sm" style="color:#fff;"
                                                href="{{ url('/rejectRepair/create?id=' . $supplierInvoice->id) }}"><i class="fa fa-undo"></i></a>
                                        </span>
                                        @if ($supplierInvoice->is_approved == 0)
                                            <span class="tool-tip1" data-bs-toggle="tooltip" data-bs-placement="top" title="Approve">
                                                <a class="btn btn-success btn-sm" style="color:#fff;"
                                                    href="{{ url('/supplierInvoice/approve/multi-carton/' . $supplierInvoice->id) }}"><i class="fas fa-check-circle"></i></a>
                                            </span>
                                        @endif
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            {{ $supplierInvoices->links() }}
        </div>
    </div>
@endsection

@section('footer')
    <script type="text/javascript">
        $(function() {
            $('[data-bs-toggle="tooltip"]').tooltip();
        });
    </script>
@endsection
