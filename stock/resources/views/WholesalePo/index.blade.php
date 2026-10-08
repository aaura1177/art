@extends('layouts.app')

@section('content')
<div class="row mx-1 my-2">
    <div class="col">
        <h2>Wholesale PO Management</h2>
    </div>
    <div class="col">
        @can('wholesale-po-edit')
            <a class="btn btn-primary float-end" style="color:#fff;" href="{{ route('wholesale-po.create') }}">Upload / Create</a>
        @else
            @if(auth()->user()->hasRole('admin') || auth()->user()->hasRole('administrator'))
                <a class="btn btn-primary float-end" style="color:#fff;" href="{{ route('wholesale-po.create') }}">Upload / Create</a>
            @endif
        @endcan
        <a class="btn btn-secondary float-end me-2" style="color:#fff;" href="{{ route('wholesale-po.template') }}">Sample Excel</a>
    </div>
</div>

@if(session('success'))
    <div class="alert alert-success mx-1">{{ session('success') }}</div>
@endif
@if(session('error'))
    <div class="alert alert-danger mx-1">{{ session('error') }}</div>
@endif

<div class="card mb-3">
    <div class="card-header"><h3 class="card-title">Shipments</h3></div>
    <div class="card-body">
        <form class="row g-2 mb-3">
            <div class="col-md-4">
                <label class="form-label">Search Buyer Order No.</label>
                <input type="text" name="search" value="{{ $search }}" class="form-control" placeholder="e.g. UK-45 #154" />
            </div>
            <div class="col-md-3">
                <label class="form-label">Status</label>
                <select name="status" class="form-control">
                    <option value="">All</option>
                    @foreach(['draft','allocating','po_raised','partial','closed'] as $st)
                        <option value="{{ $st }}" {{ $status === $st ? 'selected' : '' }}>{{ ucfirst(str_replace('_',' ',$st)) }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3 d-flex align-items-end">
                <button class="btn btn-info me-2"><i class="fa fa-filter"></i> Filter</button>
                <a href="{{ route('wholesale-po.index') }}" class="btn btn-danger">Reset</a>
            </div>
        </form>

        <table class="table table-bordered table-striped table-hover">
            <thead class="table-dark">
                <tr>
                    <th>#</th>
                    <th>Buyer Order No.</th>
                    <th>Planned</th>
                    <th>Delivery</th>
                    <th>Status</th>
                    <th>SKUs / Qty</th>
                    <th>Uploaded By</th>
                    <th>Uploaded At</th>
                    <th>Next Reminder</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                @forelse($records as $record)
                    <tr>
                        <td>{{ $records->firstItem() + $loop->index }}</td>
                        <td>{{ $record->buyer_orderno }}</td>
                        <td>{{ optional($record->planned_date)->format('Y-m-d') }}</td>
                        <td>{{ optional($record->delivery_date)->format('Y-m-d') }}</td>
                        <td>{{ ucfirst(str_replace('_',' ',$record->status)) }}</td>
                        <td>{{ $record->items->count() }} / {{ $record->items->sum('qty') }}</td>
                        <td>{{ optional($record->uploader)->firstname }} {{ optional($record->uploader)->lastname }}</td>
                        <td>{{ optional($record->uploaded_at)->format('Y-m-d H:i') }}</td>
                        <td>{{ optional($record->next_reminder_at)->format('Y-m-d') ?: '—' }}</td>
                        <td>
                            <a href="{{ route('wholesale-po.show', $record->id) }}" class="btn btn-info btn-sm">View</a>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="10" class="text-center">No wholesale shipments yet. Click Upload / Create to start.</td></tr>
                @endforelse
            </tbody>
        </table>
        {{ $records->links() }}
    </div>
</div>
@endsection
