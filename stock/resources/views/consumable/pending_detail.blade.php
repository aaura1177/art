@extends('layouts.app')

@section('content')
    <div class="row mx-3 my-2 d-flex justify-content-between align-items-center flex-wrap">
        <div>
            <h2 class="mb-0">Pending consumables — detail</h2>
            <p class="text-muted small mb-0">
                Invoice {{ $invoice->invoiceno ?? $invoice->id }}
                @if (!empty($invoice->containerno))
                    · Container <strong>{{ $invoice->containerno }}</strong>
                @endif
            </p>
        </div>
        <a href="{{ route('consumable.manual_invoice_count') }}" class="btn btn-sm btn-outline-secondary mt-2">Back to list</a>
    </div>

    @if (session('success'))
        <div class="alert alert-success mx-3">{{ session('success') }}</div>
    @endif
    @if (session('error'))
        <div class="alert alert-danger mx-3">{!! session('error') !!}</div>
    @endif

    @if (! $can_fulfill)
        <div class="alert alert-warning mx-3">
            <strong>Fulfillment locked.</strong> {{ $lock_reason ?? 'You can review details below but cannot issue stock until rules allow.' }}
        </div>
    @endif

    @if ($lines->isEmpty())
        <div class="alert alert-info mx-3">No open pending consumable lines for this invoice. Return to the list — if fulfilled here, the invoice appears under Completed.</div>
    @else
        <div class="card mx-3 mb-3">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-bordered table-sm mb-0">
                        <thead class="thead-light">
                            <tr>
                                <th>Furniture</th>
                                <th>Furniture qty</th>
                                <th>Consumable</th>
                                <th>Per unit</th>
                                <th>Total need</th>
                                <th>Deducted (est.)</th>
                                <th>Remaining</th>
                                <th>In stock</th>
                                <th>Location</th>
                                @if ($can_fulfill)
                                    <th>Issue</th>
                                @endif
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($lines as $ln)
                                @php
                                    $p = $ln['pending'];
                                    $still = $ln['still_need'];
                                    $avail = $ln['available'];
                                    $consName = optional($p->consumable)->name ?? ('#' . $p->consumable_id);
                                    $maxIssue = ($still !== null && $avail !== null) ? min($still, max(0, $avail)) : 0;
                                @endphp
                                <tr>
                                    <td>
                                        {{ $ln['product_code'] }}
                                        @if ($ln['product_name'] !== '')
                                            <br><small class="text-muted">{{ $ln['product_name'] }}</small>
                                        @endif
                                    </td>
                                    <td>{{ $ln['furniture_qty'] !== null ? $ln['furniture_qty'] : '—' }}</td>
                                    <td>{{ $consName }}</td>
                                    <td>{{ $ln['per_unit'] !== null ? $ln['per_unit'] : '—' }}</td>
                                    <td>{{ $ln['total_need'] !== null ? $ln['total_need'] : '—' }}</td>
                                    <td>{{ $ln['deducted_so_far'] !== null ? $ln['deducted_so_far'] : '—' }}</td>
                                    <td>{{ $still !== null ? $still : '—' }}</td>
                                    <td>{{ $avail !== null ? $avail : '—' }}</td>
                                    <td>{{ $ln['location'] }}</td>
                                    @if ($can_fulfill)
                                        <td>
                                            @if ($still !== null && $still > 0 && $avail !== null && $avail > 0)
                                                <form method="POST" action="{{ route('stockout.manual_pending_fulfill_consumable') }}" class="d-flex align-items-center" onsubmit="return confirm('Issue this quantity from stock?');">
                                                    @csrf
                                                    <input type="hidden" name="pending_id" value="{{ $p->id }}">
                                                    <input type="number" step="any" name="quantity" class="form-control form-control-sm me-1" style="width:5.5rem;" min="0.0001" max="{{ $maxIssue }}" value="{{ $maxIssue }}" title="Max {{ $maxIssue }}">
                                                    <button type="submit" class="btn btn-sm btn-primary">Issue</button>
                                                </form>
                                            @else
                                                <span class="text-muted small">—</span>
                                            @endif
                                        </td>
                                    @endif
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    @endif

    @if (count($totals_by_consumable) > 0)
        <div class="card mx-3 mb-3">
            <div class="card-header"><strong>Total consumable need</strong> (this invoice furniture stock-out, wf_consumable × stockout qty)</div>
            <div class="card-body py-2">
                <ul class="mb-0">
                    @foreach ($totals_by_consumable as $t)
                        <li>{{ $t['name'] }}: <strong>{{ $t['qty'] }}</strong></li>
                    @endforeach
                </ul>
            </div>
        </div>
    @endif
@endsection
