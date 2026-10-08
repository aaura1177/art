@extends('layouts.app')

@section('content')
<div class="row mx-1 my-2">
    <div class="col"><h2>Update Wholesale Shipment</h2></div>
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
    <div class="card-header"><h3 class="card-title">{{ $shipment->buyer_orderno }}</h3></div>
    <div class="card-body">
        <p class="text-muted">Buyer Order Number cannot be changed (it must stay unique). You may update dates and optionally replace the Excel before POs are raised.</p>
        <form method="POST" action="{{ route('wholesale-po.update', $shipment->id) }}" enctype="multipart/form-data">
            @csrf
            <div class="row mt-3">
                <div class="col-md-4">
                    <label class="control-label">Buyer Order Number</label>
                    <input type="text" class="form-control" value="{{ $shipment->buyer_orderno }}" readonly disabled />
                </div>
                <div class="col-md-4">
                    <label class="control-label">Planned / PO Date</label>
                    <input type="date" name="planned_date" class="form-control"
                           value="{{ old('planned_date', optional($shipment->planned_date)->format('Y-m-d')) }}" />
                </div>
                <div class="col-md-4">
                    <label class="control-label">Delivery Date <span class="text-danger">*</span></label>
                    <input type="date" name="delivery_date" class="form-control" required
                           value="{{ old('delivery_date', optional($shipment->delivery_date)->format('Y-m-d')) }}" />
                </div>
            </div>
            <div class="row mt-3">
                <div class="col-md-6">
                    <label class="control-label">Replace Excel (optional)</label>
                    <input type="file" name="excel_file" class="form-control" accept=".xlsx,.xls,.csv" />
                    <small class="text-muted">Current file: {{ $shipment->excel_original_name ?: '—' }}</small>
                </div>
                <div class="col-md-6">
                    <label class="control-label">Notes</label>
                    <textarea name="notes" class="form-control" rows="2" maxlength="2000">{{ old('notes', $shipment->notes) }}</textarea>
                </div>
            </div>
            <div class="row mt-4">
                <div class="col">
                    <button type="submit" class="btn btn-primary">Save Update</button>
                </div>
            </div>
        </form>
    </div>
</div>
@endsection
