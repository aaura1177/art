@extends('layouts.app')

@section('content')
<div class="row mx-1 my-2">
    <div class="col"><h2>Raise PO — {{ $shipment->buyer_orderno }}</h2></div>
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
    <div class="card-header"><h3 class="card-title">Pending allocations (one PO per selected supplier)</h3></div>
    <div class="card-body">
        @if($grouped->isEmpty())
            <p>No pending allocations. Allocate suppliers first, or all lines already have POs.</p>
        @else
            <form method="POST" action="{{ route('wholesale-po.generate-po', $shipment->id) }}">
                @csrf
                <div class="row mb-3">
                    <div class="col-md-4">
                        <label>Delivery Date for PO(s) <span class="text-danger">*</span></label>
                        <input type="date" name="delivery_date" class="form-control" required
                               value="{{ optional($shipment->delivery_date)->format('Y-m-d') }}" />
                    </div>
                </div>
                @foreach($grouped as $supplierId => $lines)
                    <div class="border p-3 mb-3">
                        <label class="fw-bold">
                            <input type="checkbox" name="selected[]" value="{{ $lines->pluck('id')->implode(',') }}" class="supplier-check" data-ids="{{ $lines->pluck('id')->implode(',') }}" />
                            {{ optional($lines->first()->supplier)->c_name }} — {{ $lines->sum('asked_quantity') }} pcs
                        </label>
                        <ul class="mb-0">
                            @foreach($lines as $line)
                                <li>{{ $line->product_sku }} × {{ $line->asked_quantity }} @ {{ $line->rate }}</li>
                                <input type="hidden" class="line-id-{{ $line->id }}" value="{{ $line->id }}" />
                            @endforeach
                        </ul>
                    </div>
                @endforeach
                <div id="selected-ids"></div>
                <button type="submit" class="btn btn-success" id="gen-btn" disabled>Generate selected PO(s)</button>
            </form>
        @endif
    </div>
</div>

<script>
(function () {
    const form = document.querySelector('form');
    if (!form) return;
    const box = document.getElementById('selected-ids');
    const btn = document.getElementById('gen-btn');

    function sync() {
        box.innerHTML = '';
        let any = false;
        document.querySelectorAll('.supplier-check:checked').forEach(function (cb) {
            any = true;
            (cb.getAttribute('data-ids') || '').split(',').forEach(function (id) {
                if (!id) return;
                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = 'selected[]';
                input.value = id;
                box.appendChild(input);
            });
        });
        // disable the checkbox values from posting as comma lists
        document.querySelectorAll('.supplier-check').forEach(function (cb) { cb.name = ''; });
        btn.disabled = !any;
    }
    document.querySelectorAll('.supplier-check').forEach(function (cb) {
        cb.addEventListener('change', sync);
    });
    sync();
})();
</script>
@endsection
