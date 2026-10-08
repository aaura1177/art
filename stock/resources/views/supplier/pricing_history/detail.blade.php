@extends('layouts.app')

@section('content')
@php use App\Support\SupplierProductPriceLogWriter as LogPresent; @endphp
<style>
    .sph-timeline { list-style: none; padding: 0; margin: 0; }
    .sph-timeline li { position: relative; padding: 0 0 1.25rem 1.5rem; border-left: 3px solid #dee2e6; }
    .sph-timeline li:last-child { padding-bottom: 0; }
    .sph-timeline li::before { content: ''; position: absolute; left: -7px; top: 6px; width: 11px; height: 11px; border-radius: 50%; background: #0d6efd; border: 2px solid #fff; box-shadow: 0 0 0 1px #0d6efd; }
    .sph-tl-card { background: #fff; border: 1px solid #dee2e6; border-radius: 8px; padding: 1rem 1.15rem; }
    .sph-tl-when { font-weight: 600; font-size: 1.05rem; }
    .sph-tl-meta { color: #6c757d; font-size: .9rem; margin: .25rem 0 .75rem; }
    .sph-from { background: #f8d7da; color: #842029; border-radius: 4px; padding: .2rem .45rem; }
    .sph-to { background: #d1e7dd; color: #0f5132; border-radius: 4px; padding: .2rem .45rem; }
    .sph-help { background: #f8f9fa; border-left: 4px solid #0d6efd; padding: .85rem 1rem; border-radius: 4px; margin-bottom: 1.25rem; }
</style>

<div class="mx-3 my-3">
    <div class="d-flex flex-wrap justify-content-between align-items-start gap-2 mb-3">
        <div>
            <h2 class="mb-1">{{ optional($product)->code ?? ('Product #'.$product?->id) }}</h2>
            <p class="text-muted mb-0">
                Supplier: {{ optional($supplier)->c_name ?? ('#'.$supplier?->id) }}
                @if($current)
                    · Current price: {{ $current->rate }}
                    · UK 45: {{ $current->uk_45_rate ?? '—' }}
                    @if($current->pending_rate)
                        · Pending: {{ $current->pending_rate }}
                    @endif
                @endif
            </p>
        </div>
        <div class="d-flex gap-2">
            <a class="btn btn-outline-secondary" href="{{ url('/supplier/pricing/history') }}">← All history</a>
            <a class="btn btn-secondary" href="{{ url('/supplier/pricing') }}">Pricing list</a>
        </div>
    </div>

    <div class="sph-help">
        Timeline of price, UK 45 price, revise/pending, and approve changes for this SKU + supplier (newest first).
    </div>

    @if (!empty($tableMissing))
        <div class="alert alert-warning">
            Log table is not installed yet. Run <code>database/sql/supplier_product_price_logs.sql</code> on the database.
        </div>
    @elseif ($logs->isEmpty())
        <div class="alert alert-info">
            No changes recorded yet for this product/supplier. New imports, edits, revise, and approve actions will appear here.
        </div>
    @else
        <ul class="sph-timeline">
            @foreach($logs as $log)
                @php
                    $changed = $log->changed_fields;
                    $who = $log->changed_by_label ?: 'Unknown user';
                @endphp
                <li>
                    <div class="sph-tl-card">
                        <div class="sph-tl-when">{{ LogPresent::friendlyWhen($log->created_at) }}</div>
                        <div class="sph-tl-meta">
                            {{ LogPresent::eventLabel((string) $log->event_type) }}
                            · {{ LogPresent::sourceLabel((string) $log->source) }}
                            · by {{ $who }}
                        </div>
                        @if($log->change_summary)
                            <p class="mb-2">{{ $log->change_summary }}</p>
                        @endif
                        @if(is_array($changed) && count($changed) > 0)
                            <div class="table-responsive">
                                <table class="table table-sm table-bordered mb-0">
                                    <thead>
                                        <tr>
                                            <th>Field</th>
                                            <th>From</th>
                                            <th>To</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($changed as $field => $info)
                                            <tr>
                                                <td>{{ $info['label'] ?? $field }}</td>
                                                <td><span class="sph-from">{{ LogPresent::formatValue($info['from'] ?? null) }}</span></td>
                                                <td><span class="sph-to">{{ LogPresent::formatValue($info['to'] ?? null) }}</span></td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @endif
                    </div>
                </li>
            @endforeach
        </ul>
    @endif
</div>
@endsection
