@extends('layouts.app')

@section('content')
<div class="row mx-1 my-2">
    <div class="col"><h2>Upload Wholesale Shipment</h2></div>
    <div class="col">
        <a class="btn btn-secondary float-end" style="color:#fff;" href="{{ route('wholesale-po.index') }}"><i class="fa fa-arrow-left"></i> Back</a>
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
    <div class="card-header"><h3 class="card-title">Buyer order + Excel (SKU, Qty)</h3></div>
    <div class="card-body">
        <p class="text-muted">
            One Excel file = one Buyer Order Number. If this number already exists, you will be asked to <strong>update</strong> it instead of creating a duplicate.
            Duplicate SKUs in the file are merged (quantities added). Price used later is the normal supplier rate.
        </p>
        <p><a href="{{ route('wholesale-po.template') }}">Download sample Excel template</a></p>

        <form method="POST" action="{{ route('wholesale-po.store') }}" enctype="multipart/form-data">
            @csrf
            <div class="row mt-3">
                <div class="col-md-4">
                    <label class="control-label">Buyer Order Number <span class="text-danger">*</span></label>
                    <input type="text" name="buyer_orderno" class="form-control toUpperCase" required maxlength="191"
                           value="{{ old('buyer_orderno') }}" placeholder="e.g. UK-45 #154" />
                </div>
                <div class="col-md-4">
                    <label class="control-label">Planned / PO Date</label>
                    <input type="date" name="planned_date" class="form-control" value="{{ old('planned_date', date('Y-m-d')) }}" />
                </div>
                <div class="col-md-4">
                    <label class="control-label">Delivery Date <span class="text-danger">*</span></label>
                    <input type="date" name="delivery_date" class="form-control" required value="{{ old('delivery_date') }}" />
                    <small class="text-muted">Client reminders run until this date (first after 7 days, then every 3 days).</small>
                </div>
            </div>
            <div class="row mt-3">
                <div class="col-md-6">
                    <label class="control-label">Excel file (SKU, Qty) <span class="text-danger">*</span></label>
                    <input type="file" name="excel_file" class="form-control" accept=".xlsx,.xls,.csv" required />
                </div>
                <div class="col-md-6">
                    <label class="control-label">Notes</label>
                    <textarea name="notes" class="form-control" rows="2" maxlength="2000">{{ old('notes') }}</textarea>
                </div>
            </div>
            <div class="row mt-4">
                <div class="col">
                    <button type="submit" class="btn btn-primary">Upload &amp; Save</button>
                </div>
            </div>
        </form>
    </div>
</div>
@endsection
