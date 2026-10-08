@extends('layouts.app')

@section('content')
<div class="row mx-1 my-2">
    <div class="col"><h2>Wholesale — {{ $shipment->buyer_orderno }}</h2></div>
    <div class="col text-end">
        <a class="btn btn-secondary btn-sm" style="color:#fff;" href="{{ route('wholesale-po.index') }}"><i class="fa fa-arrow-left"></i> List</a>
        @if(auth()->user()->hasRole('admin') || auth()->user()->hasRole('administrator') || auth()->user()->can('wholesale-po-edit'))
            <a class="btn btn-warning btn-sm" href="{{ route('wholesale-po.edit', $shipment->id) }}">Update</a>
            <a class="btn btn-primary btn-sm" style="color:#fff;" href="{{ route('wholesale-po.allocate', $shipment->id) }}">Allocate Suppliers</a>
            <a class="btn btn-success btn-sm" style="color:#fff;" href="{{ route('wholesale-po.po-generation', $shipment->id) }}">Raise PO</a>
        @endif
        <a class="btn btn-info btn-sm" style="color:#fff;" href="{{ route('wholesale-po.negative-list', $shipment->id) }}">Negative List</a>
        @if(auth()->user()->hasRole('admin') || auth()->user()->hasRole('administrator') || auth()->user()->can('wholesale-po-remind'))
            <form action="{{ route('wholesale-po.remind', $shipment->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Send reminder email to Harsh and Anu Jain now?');">
                @csrf
                <button type="submit" class="btn btn-dark btn-sm">Remind Client Now</button>
            </form>
        @endif
        @if(auth()->user()->hasRole('admin') || auth()->user()->hasRole('administrator') || auth()->user()->can('wholesale-po-edit'))
            @if($shipment->status !== 'closed')
                <form action="{{ route('wholesale-po.close', $shipment->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Mark this shipment closed and stop reminders?');">
                    @csrf
                    <button type="submit" class="btn btn-outline-danger btn-sm">Mark Closed</button>
                </form>
            @endif
        @endif
    </div>
</div>

@if(session('success'))
    <div class="alert alert-success mx-1">{{ session('success') }}</div>
@endif
@if(session('error'))
    <div class="alert alert-danger mx-1">{{ session('error') }}</div>
@endif

<div class="card mb-3">
    <div class="card-header"><h3 class="card-title">Shipment details</h3></div>
    <div class="card-body">
        <div class="row">
            <div class="col-md-6">
                <ul class="mb-0">
                    <li><strong>Buyer Order No.:</strong> {{ $shipment->buyer_orderno }}</li>
                    <li><strong>Status:</strong> {{ ucfirst(str_replace('_',' ',$shipment->status)) }}</li>
                    <li><strong>Planned date:</strong> {{ optional($shipment->planned_date)->format('Y-m-d') }}</li>
                    <li><strong>Delivery date:</strong> {{ optional($shipment->delivery_date)->format('Y-m-d') }}</li>
                </ul>
            </div>
            <div class="col-md-6">
                <ul class="mb-0">
                    <li><strong>Uploaded by:</strong> {{ optional($shipment->uploader)->firstname }} {{ optional($shipment->uploader)->lastname }}</li>
                    <li><strong>Uploaded at:</strong> {{ optional($shipment->uploaded_at)->format('Y-m-d H:i') }}</li>
                    <li><strong>Excel:</strong>
                        @if($shipment->excel_stored_path)
                            <a href="{{ route('wholesale-po.download-excel', $shipment->id) }}">{{ $shipment->excel_original_name }}</a>
                        @else
                            —
                        @endif
                    </li>
                    <li><strong>Reminders sent:</strong> {{ $shipment->reminder_count }}
                        @if($shipment->next_reminder_at) (next: {{ $shipment->next_reminder_at->format('Y-m-d') }}) @endif
                    </li>
                    <li><a href="{{ route('wholesale-po.logs', $shipment->id) }}">View activity log</a></li>
                </ul>
            </div>
        </div>
        @if($shipment->notes)
            <p class="mt-2 mb-0"><strong>Notes:</strong> {{ $shipment->notes }}</p>
        @endif
    </div>
</div>

<div class="card mb-3">
    <div class="card-header"><h3 class="card-title">SKU lines</h3></div>
    <div class="card-body">
        <table class="table table-bordered table-striped">
            <thead class="table-dark">
                <tr>
                    <th>SKU</th>
                    <th>Product</th>
                    <th>Asked Qty</th>
                    <th>Allocated Qty</th>
                    <th>Unallocated</th>
                </tr>
            </thead>
            <tbody>
                @foreach($shipment->items as $item)
                    @php $un = max(0, (int)$item->qty - (int)$item->allocated_qty); @endphp
                    <tr>
                        <td>{{ $item->sku }}</td>
                        <td>{{ optional($item->product)->name }}</td>
                        <td>{{ $item->qty }}</td>
                        <td>{{ $item->allocated_qty }}</td>
                        <td class="{{ $un > 0 ? 'text-danger fw-bold' : '' }}">{{ $un }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>

<div class="card mb-3">
    <div class="card-header"><h3 class="card-title">Allocations / POs</h3></div>
    <div class="card-body">
        <table class="table table-bordered table-striped">
            <thead class="table-dark">
                <tr>
                    <th>SKU</th>
                    <th>Supplier</th>
                    <th>Qty</th>
                    <th>Rate</th>
                    <th>Status</th>
                    <th>PO</th>
                </tr>
            </thead>
            <tbody>
                @forelse($shipment->allocations as $a)
                    <tr>
                        <td>{{ $a->product_sku }}</td>
                        <td>{{ optional($a->supplier)->c_name }}</td>
                        <td>{{ $a->asked_quantity }}</td>
                        <td>{{ $a->rate }}</td>
                        <td>{{ $a->status }}</td>
                        <td>
                            @if($a->purchaseOrder)
                                <a href="{{ url('/purchaseOrder/modal/'.$a->purchase_order_id) }}" target="_blank">{{ $a->purchaseOrder->pono }}</a>
                            @else
                                —
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="text-center">No allocations yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
