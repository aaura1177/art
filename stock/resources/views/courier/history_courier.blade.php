@extends('layouts.app')

@section('content')
@php use App\Support\CourierSnapshotPresenter as Present; @endphp
<style>
    .ch-timeline { list-style: none; padding: 0; margin: 0; }
    .ch-timeline li { position: relative; padding: 0 0 1.25rem 1.5rem; border-left: 3px solid #dee2e6; }
    .ch-timeline li:last-child { padding-bottom: 0; }
    .ch-timeline li::before { content: ''; position: absolute; left: -7px; top: 6px; width: 11px; height: 11px; border-radius: 50%; background: #0d6efd; border: 2px solid #fff; box-shadow: 0 0 0 1px #0d6efd; }
    .ch-tl-card { background: #fff; border: 1px solid #dee2e6; border-radius: 8px; padding: 1rem 1.15rem; }
    .ch-tl-when { font-weight: 600; font-size: 1.05rem; }
    .ch-tl-meta { color: #6c757d; font-size: .9rem; margin: .25rem 0 .5rem; }
    .ch-col-table th, .ch-col-table td { vertical-align: middle; }
    .courier-hist-help { background: #f8f9fa; border-left: 4px solid #0d6efd; padding: .85rem 1rem; border-radius: 4px; margin-bottom: 1.25rem; }
</style>

<div class="mx-3 my-3">
    <div class="d-flex flex-wrap justify-content-between align-items-start gap-2 mb-3">
        <div>
            <h2 class="mb-1">{{ $courier->name }}</h2>
            <p class="text-muted mb-0">Country: {{ $courier->country }}</p>
        </div>
        <div class="d-flex gap-2">
            <a class="btn btn-outline-secondary" href="{{ url('/courier/history') }}">← All couriers</a>
            <a class="btn btn-secondary" href="{{ url('/courier') }}">Courier list</a>
        </div>
    </div>

    <div class="courier-hist-help">
        Below: when each setting was last changed. Further down: the full change timeline (newest first).
    </div>

    {{-- Per-setting last updated --}}
    <div class="card mb-4">
        <div class="card-header"><strong>When each setting was last updated</strong></div>
        <div class="card-body table-responsive p-0">
            <table class="table table-bordered mb-0 ch-col-table">
                <thead>
                    <tr>
                        <th>Setting</th>
                        <th>Last updated</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($trackedColumns as $key => $label)
                        @php $col = $columnLastUpdated[$key] ?? null; @endphp
                        <tr>
                            <td>{{ $label }}</td>
                            <td>
                                @if($col && !empty($col['last_updated']) && !empty($col['snapshot_id']))
                                    <a href="{{ url('/courier/history/'.$courier->id.'/snapshot/'.$col['snapshot_id']) }}">
                                        {{ Present::friendlyWhen($col['last_updated']) }}
                                    </a>
                                @else
                                    <span class="text-muted">No record yet</span>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    {{-- Timeline --}}
    <h4 class="mb-3">Change timeline</h4>

    @if($snapshots->isEmpty())
        <div class="alert alert-info">
            No changes recorded yet for this courier. When someone saves an update, it will appear here.
        </div>
    @else
        <ul class="ch-timeline">
            @foreach($snapshots as $snap)
                @php
                    $changed = $snap->changed_fields;
                    $count = is_array($changed) ? count($changed) : 0;
                    $who = $snap->changed_by_label ?: 'Unknown user';
                @endphp
                <li>
                    <div class="ch-tl-card">
                        <div class="ch-tl-when">{{ Present::friendlyWhen($snap->created_at) }}</div>
                        <div class="ch-tl-meta">
                            {{ Present::eventLabel((string) $snap->event_type) }}
                            · by {{ $who }}
                            @if($count > 0)
                                · {{ $count }} {{ $count === 1 ? 'setting' : 'settings' }} changed
                            @endif
                        </div>
                        @if($snap->change_summary)
                            <p class="mb-2">{{ $snap->change_summary }}</p>
                        @endif
                        <a class="btn btn-sm btn-primary" href="{{ url('/courier/history/'.$courier->id.'/snapshot/'.$snap->id) }}">
                            See what changed
                        </a>
                    </div>
                </li>
            @endforeach
        </ul>
    @endif
</div>
@endsection
