@extends('layouts.app')

@section('content')
<div class="mx-2">
    <div class="row mx-0 my-2">
        <h2>Draft Purchase Orders</h2>
        <div class="col">
            <a class="btn btn-primary float-end" href="{{ url('/draftPurchaseOrder/create') }}">Add Draft PO</a>
        </div>
    </div>

    @if (session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    @if (session('danger'))
        <div class="alert alert-danger">{{ session('danger') }}</div>
    @endif
    @if (session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif
    @if (session('info'))
        <div class="alert alert-info">{{ session('info') }}</div>
    @endif

    <div class="card mb-3">
        <div class="card-body table-responsive">
            <table class="table table-bordered" id="dataTable" width="100%">
                <thead>
                    <tr>
                        <th>Draft PO No.</th>
                        <th>Supplier</th>
                        <th>PO Date</th>
                        <th>Delivery Date</th>
                        <th>Total Qty</th>
                        <th>Total Amount</th>
                        <th>Status</th>
                        <th>Supplier visibility</th>
                        <th>Main PO</th>
                        <th style="min-width:180px;">Options</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($drafts as $draft)
                        <tr>
                            <td>{{ $draft->draft_pono }}</td>
                            <td>{{ optional($draft->supplier)->c_name }}</td>
                            <td>{{ $draft->podate ? date('d-M-Y', strtotime($draft->podate)) : '' }}</td>
                            <td>{{ $draft->del_date ? date('d-M-Y', strtotime($draft->del_date)) : '' }}</td>
                            <td>{{ $draft->tquantity }}</td>
                            <td>{{ $draft->tamount }}</td>
                            <td>
                                @if ($draft->status == 0) Open @else Complete @endif
                            </td>
                            <td>
                                @if ($draft->send_to_supplier_status == 1)
                                    <span class="badge badge-success">Sent</span>
                                @else
                                    <span class="badge badge-secondary">Not sent</span>
                                @endif
                            </td>
                            <td>
                                @if ($draft->converted_purchase_order_id)
                                    @php $main = $draft->convertedPurchaseOrder; @endphp
                                    {{ $main ? $main->pono : '#' . $draft->converted_purchase_order_id }}
                                @else
                                    —
                                @endif
                            </td>
                            <td>
                                @if ($draft->status == 0)
                                    <a class="btn btn-info btn-sm" href="{{ url('/draftPurchaseOrder/edit/' . $draft->id) }}" title="Edit"><i class="fa fa-edit"></i></a>
                                    <a class="btn btn-primary btn-sm" href="{{ url('/draftPurchaseOrder/modal/' . $draft->id) }}" target="_blank" title="View"><i class="fa fa-eye"></i></a>
                                    <a class="btn btn-success btn-sm" href="{{ url('/draftPurchaseOrder/modal/' . $draft->id . '?print=1') }}" target="_blank" title="Print"><i class="fa fa-print"></i></a>
                                    @if ($draft->send_to_supplier_status != 1)
                                        <form method="POST" action="{{ url('/draftPurchaseOrder/' . $draft->id . '/send-to-supplier') }}" style="display:inline-block;" onsubmit="return confirm('Send this draft PO to the supplier?');">
                                            @csrf
                                            <button type="submit" class="btn btn-warning btn-sm" title="Send to Supplier"><i class="fa fa-paper-plane"></i></button>
                                        </form>
                                    @endif
                                    <a class="btn btn-primary btn-sm" href="{{ url('/draftPurchaseOrder/' . $draft->id . '/convert') }}" title="Create Main PO"><i class="fa fa-file-alt"></i> Main PO</a>
                                    <form method="POST" action="{{ url('/draftPurchaseOrder/delete/' . $draft->id) }}" style="display:inline-block;" onsubmit="return confirm('Delete this draft PO?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-danger btn-sm" title="Delete"><i class="fa fa-trash"></i></button>
                                    </form>
                                @else
                                    <a class="btn btn-primary btn-sm" href="{{ url('/draftPurchaseOrder/modal/' . $draft->id) }}" target="_blank" title="View"><i class="fa fa-eye"></i></a>
                                    @if ($draft->converted_purchase_order_id)
                                        <a class="btn btn-secondary btn-sm" href="{{ url('/purchaseOrder/view/' . $draft->converted_purchase_order_id) }}" title="Open main PO">Main PO</a>
                                    @endif
                                @endif
                            </td>
                        </tr>
                    @empty
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
