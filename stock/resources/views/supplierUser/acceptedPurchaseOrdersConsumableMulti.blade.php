@extends('layouts.app')

@section('content')
    <div class="row mx-3 my-2">
        <h2>Accepted Purchase Orders (Consumable) — Multi Invoice</h2>
        <div class="col">
            <button class="btn btn-success float-end" id="multi-raise-invoice-btn" style="color: #fff;">
                Raise Invoice for Selected POs
            </button>
            <a class="btn btn-outline-primary float-end me-2" href="{{ url('/supplier-dashboard/accepted-purchase-orders-consumable') }}">
                Single-PO accepted orders
            </a>
        </div>
    </div>

    <div class="card mb-3">
        <div class="card-body">
            <form id="multi-po-invoice-form" method="POST"
                action="{{ url('/supplier-dashboard/raise-invoice/multiple-consumable') }}" target="_blank">
                @csrf
                <input type="hidden" id="selected_po_ids" name="selected_po_ids" value="">
                <div class="table-responsive">
                    <table class="table table-bordered" id="dataTableConsumableMulti" width="100%" cellspacing="0">
                        <thead>
                            <tr>
                                <th>Select</th>
                                <th>PO No.</th>
                                <th>Supplier Ref.</th>
                                <th>PO Date</th>
                                <th>Delivery Date</th>
                                <th>Total Quantity</th>
                                <th>Total Amount</th>
                                <th>Status</th>
                                <th>Pending Products</th>
                                <th>Options</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($purchaseOrder as $po)
                                @if ((int) $po->type !== 1)
                                    @continue
                                @endif
                                <tr>
                                    <td><input type="checkbox" name="multi_po" value="{{ $po->id }}"></td>
                                    <td>{{ $po->pono }}</td>
                                    <td>{{ $po->ref_supplier }}</td>
                                    <td>{{ date('d-M-Y', strtotime($po->podate)) }}</td>
                                    <td>{{ date('d-M-Y', strtotime($po->del_date)) }}</td>
                                    <td>{{ $po->tquantity }}</td>
                                    <td>{{ $po->tamount }}</td>
                                    <td>{{ $po->status == 1 ? 'Complete' : 'Pending' }}</td>
                                    <td>
                                        @foreach ($po->poTable as $potable)
                                            @if ($potable->remqty > 0)
                                                {{ optional($potable->consumable)->name ?? optional($potable->product)->code }}
                                                - {{ $potable->remqty }}/{{ $potable->quantity }}<br>
                                            @endif
                                        @endforeach
                                    </td>
                                    <td>
                                        <div class="d-inline-flex flex-wrap gap-1 align-items-center">
                                        <a class="btn btn-primary btn-sm" style="color:#fff;"
                                            href="{{ url('/supplier-dashboard/modal/10000000' . $po->id) }}"
                                            target="_blank"><i class="fa fa-eye"></i></a>
                                        <a class="btn btn-success btn-sm" style="color:#fff;"
                                            href="{{ url('/supplier-dashboard/raise-invoice/10000000' . $po->id) }}"
                                            target="_blank">Single invoice</a>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </form>
        </div>
    </div>
@endsection

@section('footer')
    <script>
        $('#multi-raise-invoice-btn').on('click', function() {
            const ids = [];
            $('input[name="multi_po"]:checked').each(function() {
                ids.push($(this).val());
            });
            if (ids.length < 2) {
                alert('Please select at least 2 consumable purchase orders.');
                return;
            }
            $('#selected_po_ids').val(ids.join(','));
            $('#multi-po-invoice-form').submit();
        });
        $(function() {
            $('#dataTableConsumableMulti').DataTable();
        });
    </script>
@endsection
