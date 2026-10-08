@extends('layouts.app')

@section('content')
@php use App\Support\PurchaseOrderVersionWriter as V; @endphp
<style>
    .pov-from { background: #f8d7da; color: #842029; border-radius: 4px; padding: .15rem .4rem; display: inline-block; min-width: 2rem; }
    .pov-to { background: #d1e7dd; color: #0f5132; border-radius: 4px; padding: .15rem .4rem; display: inline-block; min-width: 2rem; }
    .pov-help { background: #f8f9fa; border-left: 4px solid #0d6efd; padding: .85rem 1rem; border-radius: 4px; margin-bottom: 1.25rem; }
    .pov-summary { font-weight: 500; }
    .pov-group-title { font-weight: 600; margin-bottom: .35rem; }
    .pov-row { display: flex; flex-wrap: wrap; align-items: center; gap: .35rem .5rem; margin: .25rem 0; font-size: .92rem; }
    .pov-field { min-width: 110px; color: #495057; }
    .pov-arrow { color: #6c757d; }
    .pov-details summary { list-style: none; }
    .pov-details summary::-webkit-details-marker { display: none; }
</style>

<div class="mx-3 my-3">
    <div class="d-flex flex-wrap justify-content-between align-items-start gap-2 mb-3">
        <div>
            <h2 class="mb-1">{{ $purchaseOrder->pono }}
                @php $lv = $versions->max('version'); @endphp
                @if($lv)
                    <span class="badge bg-secondary">v{{ $lv }}</span>
                @endif
            </h2>
            <p class="text-muted mb-0">Accept every version before raising an invoice for this PO.</p>
        </div>
        <div class="d-flex gap-2">
            <a class="btn btn-secondary" href="{{ url('/supplier-dashboard/accepted-purchase-orders') }}">← Accepted POs</a>
            <a class="btn btn-primary" href="{{ url('/supplier-dashboard/modal/'.$purchaseOrder->id) }}" target="_blank">View PO</a>
        </div>
    </div>

    <div class="pov-help">
        If this PO was edited after you already invoiced part of it, accept the new version(s) before raising another invoice for remaining qty.
    </div>

    @if (!empty($tableMissing))
        <div class="alert alert-warning">Version history is not available yet.</div>
    @endif

    <h4 class="mb-3">Versions</h4>
    @if($versions->isEmpty())
        <div class="alert alert-info">No edited versions yet. You can raise invoice under normal rules.</div>
    @else
        <div class="card mb-4">
            <div class="card-body table-responsive p-0">
                <table class="table table-bordered mb-0">
                    <thead>
                        <tr>
                            <th style="width:70px;">Version</th>
                            <th style="width:150px;">When</th>
                            <th>What changed</th>
                            <th style="width:130px;">Accept</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($versions as $ver)
                            <tr>
                                <td><strong>v{{ $ver->version }}</strong></td>
                                <td>{{ V::friendlyWhen($ver->created_at) }}</td>
                                <td>
                                    @include('purchaseOrder.partials.version_changes', [
                                        'changed' => $ver->changed_fields,
                                        'versionNumber' => (int) $ver->version,
                                    ])
                                </td>
                                <td>
                                    @if($ver->isAccepted())
                                        <span class="badge bg-success">Accepted</span>
                                        <div class="small text-muted">{{ V::friendlyWhen($ver->supplier_accepted_at) }}</div>
                                    @elseif(!empty($canAcceptVersions))
                                        <form method="POST" action="{{ url('/supplier-dashboard/purchase-orders/'.$purchaseOrder->id.'/versions/'.$ver->id.'/accept') }}">
                                            @csrf
                                            <button type="submit" class="btn btn-sm btn-info">Accept v{{ $ver->version }}</button>
                                        </form>
                                    @else
                                        <span class="badge bg-warning text-dark">Pending</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif
</div>
@endsection
