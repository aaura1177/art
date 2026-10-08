@extends('layouts.app')

@section('content')
    <div class="row mx-3 my-2">
        <h2>Accepted Purchase Orders (Carton) — Single Invoice</h2>
        <div class="col">
            <a class="btn btn-outline-primary float-end"
                href="{{ url('/supplier-dashboard/accepted-purchase-orders-carton-multi') }}">
                Multi-PO accepted orders
            </a>
        </div>
    </div>

    <div class="card mb-3">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered" id="dataTableCartonSingle" width="100%" cellspacing="0">
                    <thead>
                        <tr>
                            <th>PO No.</th>
                            <th>Supplier Ref.</th>
                            <th>PO Date</th>
                            <th>Delivery Date</th>
                            <th>Total Amount</th>
                            <th>Status</th>
                            <th>Pending</th>
                            <th>Options</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($purchaseOrder as $po)
                            @php $poUiId = '10000000' . $po->id; @endphp
                            <tr>
                                <td>{{ $po->pono }}</td>
                                <td>{{ $po->ref_supplier }}</td>
                                <td>{{ date('d-M-Y', strtotime($po->podate)) }}</td>
                                <td>{{ date('d-M-Y', strtotime($po->del_date)) }}</td>
                                <td>{{ $po->tamount }}</td>
                                <td>{{ $po->status == 1 ? 'Complete' : 'Pending' }}</td>
                                <td>
                                    @foreach ($po->popTable as $line)
                                        @if ($line->remqty_box1 > 0 || $line->remqty_box2 > 0)
                                            {{ optional($line->product)->code ?? $line->product_id }}
                                            ({{ $line->remqty_box1 }}/{{ $line->remqty_box2 }})<br>
                                        @endif
                                    @endforeach
                                </td>
                                <td>
                                    <div class="d-inline-flex flex-wrap gap-1 align-items-center">
                                    <a class="btn btn-primary btn-sm" style="color:#fff;"
                                        href="{{ url('/supplier-dashboard/modal/carton/' . $poUiId) }}"
                                        target="_blank"><i class="fa fa-eye"></i></a>
                                    <a class="btn btn-success btn-sm" style="color:#fff;"
                                        href="{{ url('/supplier-dashboard/raise-invoice/' . $poUiId) }}"
                                        target="_blank"><i class="fa fa-clipboard"></i> Raise Invoice</a>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection

@section('footer')
    <script>
        $(function() {
            $('#dataTableCartonSingle').DataTable();
        });
    </script>
@endsection
