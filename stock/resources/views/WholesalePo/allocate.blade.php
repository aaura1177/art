@extends('layouts.app')

@section('content')
<div class="row mx-1 my-2">
    <div class="col"><h2>Allocate Suppliers — {{ $shipment->buyer_orderno }}</h2></div>
    <div class="col">
        <a class="btn btn-secondary float-end" style="color:#fff;" href="{{ route('wholesale-po.show', $shipment->id) }}"><i class="fa fa-arrow-left"></i> Back</a>
    </div>
</div>

@if(session('error'))
    <div class="alert alert-danger mx-1">{{ session('error') }}</div>
@endif
@if($errors->any())
    <div class="alert alert-danger mx-1">
        <ul class="mb-0">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
    </div>
@endif

<div class="card mb-3">
    <div class="card-header"><h3 class="card-title">Assign suppliers (split across suppliers allowed)</h3></div>
    <div class="card-body">
        <p class="text-muted">Add one or more rows per SKU. Total allocated for a SKU cannot exceed asked qty. Rate = normal supplier price.</p>
        <form method="POST" action="{{ route('wholesale-po.allocate.save', $shipment->id) }}" id="alloc-form">
            @csrf
            <div id="alloc-rows">
                @php $row = 0; @endphp
                @foreach($skuBlocks as $block)
                    @php
                        $item = $block['item'];
                        $existing = $block['existing'];
                        if ($existing->isEmpty()) {
                            $existing = collect([(object)['supplier_id'=>null,'asked_quantity'=>'']]);
                        }
                    @endphp
                    <div class="mb-4 border p-3">
                        <h5>{{ $item->sku }} — {{ optional($item->product)->name }}</h5>
                        <p class="mb-2">Asked: <strong>{{ $item->qty }}</strong>
                            | Already on PO: <strong>{{ (int)$item->allocations()->where('status','po_generated')->sum('asked_quantity') }}</strong>
                        </p>
                        @foreach($existing as $ex)
                            <div class="row g-2 mb-2 alloc-line" data-item="{{ $item->id }}">
                                <input type="hidden" name="alloc[{{ $row }}][item_id]" value="{{ $item->id }}" />
                                <div class="col-md-5">
                                    <select name="alloc[{{ $row }}][supplier_id]" class="form-control selectpicker" data-live-search="true" required>
                                        <option value="">Select supplier</option>
                                        @foreach($suppliers as $s)
                                            <option value="{{ $s->id }}"
                                                {{ (int)($ex->supplier_id ?? 0) === (int)$s->id ? 'selected' : '' }}>
                                                {{ $s->c_name }}
                                                @if(isset($block['rates'][$s->id]))
                                                    (Rate: {{ $block['rates'][$s->id]->rate }})
                                                @endif
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <input type="number" min="1" name="alloc[{{ $row }}][qty]" class="form-control"
                                           value="{{ $ex->asked_quantity ?? '' }}" placeholder="Qty" required />
                                </div>
                                <div class="col-md-2">
                                    <button type="button" class="btn btn-outline-danger btn-sm remove-line">Remove</button>
                                </div>
                            </div>
                            @php $row++; @endphp
                        @endforeach
                        <button type="button" class="btn btn-sm btn-outline-primary add-line"
                                data-item-id="{{ $item->id }}"
                                data-sku="{{ $item->sku }}">+ Add another supplier for this SKU</button>
                    </div>
                @endforeach
            </div>
            <button type="submit" class="btn btn-primary">Save Allocations</button>
        </form>
    </div>
</div>

<template id="supplier-options">
    <option value="">Select supplier</option>
    @foreach($suppliers as $s)
        <option value="{{ $s->id }}">{{ $s->c_name }}</option>
    @endforeach
</template>

<script>
(function () {
    let rowIdx = {{ $row }};
    document.querySelectorAll('.add-line').forEach(function (btn) {
        btn.addEventListener('click', function () {
            const itemId = this.getAttribute('data-item-id');
            const wrap = this.closest('.border');
            const opts = document.getElementById('supplier-options').innerHTML;
            const div = document.createElement('div');
            div.className = 'row g-2 mb-2 alloc-line';
            div.innerHTML =
                '<input type="hidden" name="alloc[' + rowIdx + '][item_id]" value="' + itemId + '" />' +
                '<div class="col-md-5"><select name="alloc[' + rowIdx + '][supplier_id]" class="form-control" required>' + opts + '</select></div>' +
                '<div class="col-md-3"><input type="number" min="1" name="alloc[' + rowIdx + '][qty]" class="form-control" placeholder="Qty" required /></div>' +
                '<div class="col-md-2"><button type="button" class="btn btn-outline-danger btn-sm remove-line">Remove</button></div>';
            wrap.insertBefore(div, this);
            rowIdx++;
        });
    });
    document.getElementById('alloc-form').addEventListener('click', function (e) {
        if (e.target.classList.contains('remove-line')) {
            const line = e.target.closest('.alloc-line');
            const parent = line.parentElement;
            const lines = parent.querySelectorAll('.alloc-line');
            if (lines.length <= 1) {
                alert('Keep at least one line per SKU, or set quantity carefully before saving.');
                return;
            }
            line.remove();
        }
    });
})();
</script>
@endsection
