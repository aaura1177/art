@extends('layouts.app')

@section('content')
<div class="row mx-3 my-2">
    <h2>Consumable Draft Purchase Orders</h2>
</div>

@if (session('danger'))
    <div class="alert alert-danger mx-3">{{ session('danger') }}</div>
@endif

@if ($drafts->isEmpty())
    <p class="text-muted mx-3 mb-0">No consumable draft POs.</p>
@endif

<div class="card mb-3 mx-3">
    <div class="card-body table-responsive">
        <table class="table table-bordered" id="dataTable" width="100%">
            <thead>
                <tr>
                    <th>Draft PO No.</th>
                    <th>PO Date</th>
                    <th>Delivery Date</th>
                    <th>Total Qty</th>
                    <th>Total Amount</th>
                    <th>Status</th>
                    <th style="min-width:120px;">Options</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($drafts as $draft)
                    <tr>
                        <td>{{ $draft->draft_pono }}</td>
                        <td>{{ $draft->podate ? date('d-M-Y', strtotime($draft->podate)) : '' }}</td>
                        <td>{{ $draft->del_date ? date('d-M-Y', strtotime($draft->del_date)) : '' }}</td>
                        <td>{{ $draft->tquantity }}</td>
                        <td>{{ $draft->tamount }}</td>
                        <td>{{ $draft->status == 0 ? 'Open' : 'Complete' }}</td>
                        <td>
                            @if ($draft->status == 0)
                                <a class="btn btn-primary btn-sm" href="{{ url('/supplier-dashboard/draft-consumable-purchase-orders/modal/' . $draft->id) }}" target="_blank" title="View"><i class="fa fa-eye"></i></a>
                                <a class="btn btn-success btn-sm" href="{{ url('/supplier-dashboard/draft-consumable-purchase-orders/modal/' . $draft->id . '?print=1') }}" target="_blank" title="Print"><i class="fa fa-print"></i></a>
                            @else
                                <span class="text-muted">Converted to main PO</span>
                            @endif
                        </td>
                    </tr>
                @empty
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
