@extends('layouts.app')

@section('content')
@php use App\Support\SupplierProductPriceLogWriter as LogPresent; @endphp

<div class="mx-3 my-3">
    <div class="row mb-3 align-items-center">
        <div class="col">
            <h2 class="mb-1">Supplier price history</h2>
            <p class="text-muted mb-0">All recorded price, UK 45, revise, and approve changes (newest first).</p>
        </div>
        <div class="col-auto">
            <a class="btn btn-secondary" href="{{ url('/supplier/pricing') }}">← Back to pricing</a>
        </div>
    </div>

    @if (!empty($tableMissing))
        <div class="alert alert-warning">
            Log table is not installed yet. Run
            <code>database/sql/supplier_product_price_logs.sql</code>
            on the database, then price changes will appear here.
        </div>
    @endif

    <div class="row mb-3">
        <div class="col-md-6">
            <form method="GET" action="{{ url('/supplier/pricing/history') }}" class="d-flex flex-wrap align-items-center">
                <input type="hidden" name="per_page" value="{{ (int) $perPage }}">
                <input type="search" name="q" class="form-control w-auto me-2" style="width: 260px;"
                    placeholder="SKU, supplier, user, event…" value="{{ $q ?? '' }}">
                <button type="submit" class="btn btn-primary me-2">Search</button>
                <a href="{{ url('/supplier/pricing/history') }}" class="btn btn-secondary">Clear</a>
            </form>
        </div>
    </div>

    <div class="card">
        <div class="card-body table-responsive">
            <table class="table table-bordered mb-0">
                <thead>
                    <tr>
                        <th>When</th>
                        <th>SKU</th>
                        <th>Supplier</th>
                        <th>Event</th>
                        <th>Source</th>
                        <th>Summary</th>
                        <th>By</th>
                        <th style="width:110px;">Detail</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($logs as $log)
                        <tr>
                            <td>{{ LogPresent::friendlyWhen($log->created_at) }}</td>
                            <td>{{ optional($log->product)->code ?? ('#'.$log->product_id) }}</td>
                            <td>{{ optional($log->supplier)->c_name ?? ('#'.$log->supplier_id) }}</td>
                            <td>{{ LogPresent::eventLabel((string) $log->event_type) }}</td>
                            <td>{{ LogPresent::sourceLabel((string) $log->source) }}</td>
                            <td>{{ $log->change_summary }}</td>
                            <td>{{ $log->changed_by_label ?: '—' }}</td>
                            <td>
                                <a class="btn btn-sm btn-primary"
                                   href="{{ url('/supplier/pricing/history/'.$log->product_id.'/'.$log->supplier_id) }}">
                                    Open
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center text-muted">No price change logs yet.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if(method_exists($logs, 'links') && $logs->total() > 0)
            <div class="card-footer d-flex justify-content-between align-items-center">
                <div class="text-muted small">
                    Showing {{ $logs->firstItem() }} to {{ $logs->lastItem() }} of {{ $logs->total() }}
                </div>
                <div>{{ $logs->onEachSide(1)->links() }}</div>
            </div>
        @endif
    </div>
</div>
@endsection
