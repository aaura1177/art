@extends('layouts.app')

@section('content')
    <div class="row mx-3 my-2 d-flex justify-content-between align-items-center flex-wrap">
        <div>
            <h2 class="mb-0">Pending cartons — detail</h2>
            <p class="text-muted small mb-0">
                Invoice {{ $invoice->invoiceno ?? $invoice->id }}
                @if (!empty($invoice->containerno))
                    · Container <strong>{{ $invoice->containerno }}</strong>
                @endif
            </p>
        </div>
        <a href="{{ route('carton.manual_invoice_count') }}" class="btn btn-sm btn-outline-secondary mt-2">Back to list</a>
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
        <div class="alert alert-info mx-3">No open pending carton lines for this invoice. If everything was fulfilled, see Completed on the list.</div>
    @else
        <div class="card mx-3 mb-3">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-bordered table-sm mb-0">
                        <thead class="thead-light">
                            <tr>
                                <th>Product</th>
                                <th>Furniture qty</th>
                                <th>Qty1 / unit</th>
                                <th>Qty2 / unit</th>
                                <th>Need B1 / B2</th>
                                <th>Deducted B1 / B2 (est.)</th>
                                <th>Short B1 / B2</th>
                                <th>Stock B1 / B2</th>
                                <th>Location</th>
                                @if ($can_fulfill)
                                    <th>Issue (B1 / B2)</th>
                                @endif
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($lines as $ln)
                                @php
                                    $p = $ln['pending'];
                                    $sb1 = $ln['short_box1'];
                                    $sb2 = $ln['short_box2'];
                                    $ab1 = $ln['avail_box1'];
                                    $ab2 = $ln['avail_box2'];
                                    $cap1 = ($sb1 > 0 && $ab1 !== null) ? min($sb1, max(0, $ab1)) : 0;
                                    $cap2 = ($sb2 > 0 && $ab2 !== null) ? min($sb2, max(0, $ab2)) : 0;
                                    $canRow = ($cap1 > 0.00001 || $cap2 > 0.00001);
                                @endphp
                                <tr>
                                    <td>
                                        {{ $ln['product_code'] }}
                                        @if ($ln['product_name'] !== '')
                                            <br><small class="text-muted">{{ $ln['product_name'] }}</small>
                                        @endif
                                    </td>
                                    <td>{{ $ln['furniture_qty'] !== null ? $ln['furniture_qty'] : '—' }}</td>
                                    <td>{{ $ln['qty1_per_unit'] }}</td>
                                    <td>{{ $ln['qty2_per_unit'] }}</td>
                                    <td>
                                        {{ $ln['total_need_box1'] !== null ? $ln['total_need_box1'] : '—' }}
                                        /
                                        {{ $ln['total_need_box2'] !== null ? $ln['total_need_box2'] : '—' }}
                                    </td>
                                    <td>
                                        {{ $ln['deducted_box1'] !== null ? $ln['deducted_box1'] : '—' }}
                                        /
                                        {{ $ln['deducted_box2'] !== null ? $ln['deducted_box2'] : '—' }}
                                    </td>
                                    <td>{{ $sb1 > 0 ? $sb1 : '—' }} / {{ $sb2 > 0 ? $sb2 : '—' }}</td>
                                    <td>{{ $ab1 !== null ? $ab1 : '—' }} / {{ $ab2 !== null ? $ab2 : '—' }}</td>
                                    <td>{{ $ln['location'] }}</td>
                                    @if ($can_fulfill)
                                        <td>
                                            @if ($canRow)
                                                <form method="POST" action="{{ route('stockout.manual_pending_fulfill_carton') }}" class="small" onsubmit="return confirm('Issue these carton quantities?');">
                                                    @csrf
                                                    <input type="hidden" name="pending_id" value="{{ $p->id }}">
                                                    <div class="mb-1">
                                                        <label class="mb-0 small text-muted">Box 1</label>
                                                        <input type="number" step="any" name="qty_box1" class="form-control form-control-sm" placeholder="max {{ $cap1 }}" value="{{ $cap1 > 0 ? $cap1 : '' }}" style="width:6rem;">
                                                    </div>
                                                    <div class="mb-1">
                                                        <label class="mb-0 small text-muted">Box 2</label>
                                                        <input type="number" step="any" name="qty_box2" class="form-control form-control-sm" placeholder="max {{ $cap2 }}" value="{{ $cap2 > 0 ? $cap2 : '' }}" style="width:6rem;">
                                                    </div>
                                                    <button type="submit" class="btn btn-sm btn-primary">Issue</button>
                                                    <div class="text-muted small mt-1">Leave a box empty to issue max for that box only when the other is filled; leave both empty for max on both.</div>
                                                </form>
                                            @else
                                                <span class="text-muted small">No stock / no short</span>
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
@endsection
