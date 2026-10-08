@extends('layouts.app')

@section('content')
@php use App\Support\CourierSnapshotPresenter as Present; @endphp
<style>
    .ch-change-card { border: 1px solid #dee2e6; border-radius: 8px; margin-bottom: 1rem; overflow: hidden; background: #fff; }
    .ch-change-card .ch-h { background: #f8f9fa; padding: .75rem 1rem; font-weight: 600; border-bottom: 1px solid #eee; }
    .ch-change-card .ch-cols { display: grid; grid-template-columns: 1fr 1fr; gap: 0; }
    @media (max-width: 768px) { .ch-change-card .ch-cols { grid-template-columns: 1fr; } }
    .ch-change-card .ch-from, .ch-change-card .ch-to { padding: 1rem; }
    .ch-change-card .ch-from { background: #fff5f5; border-right: 1px solid #eee; }
    .ch-change-card .ch-to { background: #f0fff4; }
    .ch-change-card .ch-tag { display: inline-block; font-size: .75rem; font-weight: 600; text-transform: uppercase; letter-spacing: .03em; margin-bottom: .4rem; }
    .ch-change-card .ch-from .ch-tag { color: #b02a37; }
    .ch-change-card .ch-to .ch-tag { color: #146c43; }
    .ch-banner { background: #f8f9fa; border: 1px solid #dee2e6; border-radius: 8px; padding: 1rem 1.25rem; margin-bottom: 1.25rem; }
    .ch-state-row { display: flex; flex-wrap: wrap; justify-content: space-between; gap: .5rem; padding: .75rem 0; border-bottom: 1px solid #f1f1f1; }
    .ch-state-row:last-child { border-bottom: 0; }
    .courier-hist-help { background: #e7f1ff; border-left: 4px solid #0d6efd; padding: .85rem 1rem; border-radius: 4px; margin-bottom: 1.25rem; }
</style>

<div class="mx-3 my-3">
    <div class="d-flex flex-wrap justify-content-between align-items-start gap-2 mb-3">
        <div>
            <h2 class="mb-1">What changed</h2>
            <p class="text-muted mb-0"><strong>{{ $courier->name }}</strong> · {{ $courier->country }}</p>
        </div>
        <div class="d-flex gap-2">
            <a class="btn btn-outline-secondary" href="{{ url('/courier/history/'.$courier->id) }}">← Timeline</a>
            <a class="btn btn-secondary" href="{{ url('/courier/history') }}">All couriers</a>
        </div>
    </div>

    <div class="ch-banner">
        <div><strong>When:</strong> {{ Present::friendlyWhen($snapshot->created_at) }}</div>
        <div><strong>What happened:</strong> {{ Present::eventLabel((string) $snapshot->event_type) }}</div>
        <div><strong>Who:</strong> {{ $snapshot->changed_by_label ?: 'Unknown user' }}</div>
        @if($snapshot->change_summary)
            <div class="mt-1"><strong>Summary:</strong> {{ $snapshot->change_summary }}</div>
        @endif
    </div>

    <div class="courier-hist-help">
        Pink side = <strong>before</strong> the change. Green side = <strong>after</strong> the change.
    </div>

    <h4 class="mb-3">Settings that changed</h4>

    @if(count($changedFields) === 0)
        <div class="alert alert-secondary">No individual setting differences were recorded for this save.</div>
    @else
        @foreach($changedFields as $key => $info)
            <div class="ch-change-card">
                <div class="ch-h">{{ $info['label'] ?? ($trackedColumns[$key] ?? $key) }}</div>
                <div class="ch-cols">
                    <div class="ch-from">
                        <div class="ch-tag">Before</div>
                        <div>{!! Present::detailHtml($info['from'] ?? null, $key) !!}</div>
                    </div>
                    <div class="ch-to">
                        <div class="ch-tag">After</div>
                        <div>{!! Present::detailHtml($info['to'] ?? null, $key) !!}</div>
                    </div>
                </div>
            </div>
        @endforeach
    @endif

    <details class="mt-4 mb-4">
        <summary style="cursor:pointer;font-weight:600;margin-bottom:.75rem;">
            Show full courier details at this time
        </summary>
        <div class="card">
            <div class="card-body">
                @foreach($trackedColumns as $key => $label)
                    <div class="ch-state-row">
                        <div class="fw-semibold" style="min-width:180px;">{{ $label }}</div>
                        <div class="flex-grow-1">{!! Present::detailHtml($fullState[$key] ?? null, $key) !!}</div>
                    </div>
                @endforeach
            </div>
        </div>
    </details>
</div>
@endsection
