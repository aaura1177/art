@extends('layouts.app')

@section('content')
    <div class="row mx-3 my-2">
        <h2>Manual Consumable Invoice Count</h2>
        <p class="text-muted small mb-0">Invoices from the last 2 days with furniture stock-out but no consumable lines yet. Container consumables (<code>is_container</code>) appear in pending when stock is short; they are deducted after export stock-out completes or via pending fulfillment.</p>
    </div>

    @if (session('success'))
        <div class="alert alert-success mx-3">{{ session('success') }}</div>
    @endif
    @if (session('error'))
        <div class="alert alert-danger mx-3">{!! session('error') !!}</div>
    @endif

    <div class="card mb-3 mx-3">
        <div class="card-header d-flex flex-wrap justify-content-between align-items-center">
            <div>
                <strong>Pending consumables</strong> (by invoice)
                <span class="badge bg-warning text-dark">{{ $consumablePendingOpenSummaries->count() }} open</span>
                <span class="badge bg-success">{{ $consumablePendingCompletedSummaries->count() }} completed</span>
            </div>
            <form method="GET" action="{{ route('consumable.manual_invoice_count') }}" class="d-flex align-items-center mt-2 mt-md-0">
                <input type="text" name="pending_q" value="{{ $pending_q }}" class="form-control form-control-sm me-1" placeholder="Search invoice / container / buyer ref">
                <button type="submit" class="btn btn-sm btn-outline-secondary">Search</button>
            </form>
        </div>
        <div class="card-body p-0">
            @if ($consumablePendingOpenSummaries->isEmpty() && $consumablePendingCompletedSummaries->isEmpty())
                <div class="p-3 text-muted mb-0">No pending consumable invoices.</div>
            @else
                @if ($consumablePendingOpenSummaries->isNotEmpty())
                    <h6 class="px-3 pt-3 mb-2">Open (newest first)</h6>
                    <div class="table-responsive">
                        <table class="table table-bordered table-sm mb-0">
                            <thead class="thead-light">
                                <tr>
                                    <th>Invoice</th>
                                    <th>Date</th>
                                    <th>Container</th>
                                    <th>Buyer</th>
                                    <th>Pending lines</th>
                                    <th>Total still need (parsed)</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($consumablePendingOpenSummaries as $sum)
                                    @php $inv = $sum['invoice']; @endphp
                                    <tr>
                                        <td>{{ $inv->invoiceno ?? $inv->id }}</td>
                                        <td>{{ $inv->date ? date('d-M-Y', strtotime($inv->date)) : '' }}</td>
                                        <td>{{ $sum['container'] !== '' ? $sum['container'] : '—' }}</td>
                                        <td>{{ $inv->buyer->name ?? $inv->buyer->c_name ?? '' }}</td>
                                        <td>{{ $sum['pending_line_count'] }}</td>
                                        <td>{{ $sum['total_still_need'] !== null ? $sum['total_still_need'] : '—' }}</td>
                                        <td>
                                            @if ($sum['can_open_detail'])
                                                <a href="{{ route('consumable.manual_pending_detail', ['invoice' => $inv->id]) }}" class="btn btn-sm btn-primary">View</a>
                                            @else
                                                <span class="btn btn-sm btn-secondary disabled" style="pointer-events: none;" title="{{ $sum['lock_reason'] ?? '' }}">View</span>
                                                @if (!empty($sum['lock_reason']))
                                                    <br><small class="text-muted">{{ $sum['lock_reason'] }}</small>
                                                @endif
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
                @if ($consumablePendingCompletedSummaries->isNotEmpty())
                    <h6 class="px-3 pt-3 mb-2">Completed (fulfilled via pending flow)</h6>
                    <div class="table-responsive">
                        <table class="table table-bordered table-sm mb-3">
                            <thead class="thead-light">
                                <tr>
                                    <th>Invoice</th>
                                    <th>Date</th>
                                    <th>Container</th>
                                    <th>Buyer</th>
                                    <th>Lines cleared</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($consumablePendingCompletedSummaries as $sum)
                                    @php $inv = $sum['invoice']; @endphp
                                    <tr>
                                        <td>{{ $inv->invoiceno ?? $inv->id }}</td>
                                        <td>{{ $inv->date ? date('d-M-Y', strtotime($inv->date)) : '' }}</td>
                                        <td>{{ $sum['container'] !== '' ? $sum['container'] : '—' }}</td>
                                        <td>{{ $inv->buyer->name ?? $inv->buyer->c_name ?? '' }}</td>
                                        <td>{{ $sum['pending_line_count'] }}</td>
                                        <td><span class="badge bg-success">Completed</span></td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            @endif
        </div>
    </div>

    @if ($containerConsumablesCatalog->isNotEmpty())
        <div class="card mb-3 mx-3">
            <div class="card-header"><strong>Container consumables</strong> (master: <code>is_container</code> = Yes, <code>container_quantity</code> &gt; 0)</div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-sm table-bordered mb-0">
                        <thead class="thead-light">
                            <tr>
                                <th>Consumable</th>
                                <th>Required per export invoice</th>
                                <th>Current stock</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($containerConsumablesCatalog as $cc)
                                @php
                                    $req = (float) ($cc->container_quantity ?? 0);
                                    $avail = (float) ($cc->quantity ?? 0);
                                @endphp
                                <tr>
                                    <td>{{ $cc->name }} <small class="text-muted">#{{ $cc->id }}</small></td>
                                    <td>{{ $req }}</td>
                                    <td>{{ $avail }}</td>
                                    <td>
                                        @if ($avail >= $req)
                                            <span class="badge bg-success">OK</span>
                                        @else
                                            <span class="badge bg-warning text-dark">Short for new stock-outs</span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <p class="small text-muted mb-0 p-3">If stock is short when stock-out starts, a <strong>pending</strong> row is created (open in the table above). After furniture consumable lines are posted, container qty is deducted when the export completes (or issue from pending).</p>
            </div>
        </div>
    @endif

    @if ($invoices->isEmpty())
        <div class="alert alert-info mx-3">No invoices in the last 2 days need manual consumable posting (all already have consumable lines, or no stock-out).</div>
    @else
        <div class="card mb-3 mx-3">
            <div class="card-header d-flex justify-content-between align-items-center flex-wrap">
                <strong>Invoices — submit consumable stock-out</strong>
                <span class="badge bg-secondary">{{ $invoices->count() }} invoice(s)</span>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered" id="containerInvoicesTable" width="100%" cellspacing="0">
                        <thead>
                            <tr>
                                <th>Invoice No.</th>
                                <th>Date</th>
                                <th>Container</th>
                                <th>Buyer</th>
                                <th>Total Qty</th>
                                <th>Total Amount</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($invoices as $inv)
                                <tr>
                                    <td>{{ $inv->invoiceno ?? $inv->id }}</td>
                                    <td>{{ $inv->date ? date('d-M-Y', strtotime($inv->date)) : '' }}</td>
                                    <td>{{ $inv->containerno ?? '—' }}</td>
                                    <td>{{ $inv->buyer->name ?? '' }}</td>
                                    <td>{{ $inv->totalquantity ?? '' }}</td>
                                    <td>{{ $inv->totalamount ?? '' }}</td>
                                    <td>
                                        <form method="POST" action="{{ route('consumable.manual_invoice_consumable_stockout') }}"
                                            onsubmit="return confirm('Process consumable stockout for this invoice?');">
                                            @csrf
                                            <input type="hidden" name="invoice_id" value="{{ $inv->id }}">
                                            <button type="submit" class="btn btn-sm btn-primary">Submit Consumable Stockout</button>
                                        </form>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="card mb-3 mx-3">
            <div class="card-body">
                <h5 class="mb-3">Consumables required (product-wise, wf_consumable)</h5>

                @foreach ($invoices as $inv)
                    @php
                        $bd = $consumableBreakdowns[$inv->id] ?? null;
                        $lines = $bd['lines'] ?? [];
                        $totals = $bd['totals'] ?? [];
                    @endphp

                    <div class="border rounded p-3 mb-3">
                        <div class="d-flex justify-content-between align-items-start">
                            <div>
                                <strong>Invoice:</strong> {{ $inv->invoiceno ?? $inv->id }}
                                <br>
                                <strong>Buyer:</strong> {{ $inv->buyer->name ?? '' }}
                                @if (!empty($inv->containerno))
                                    <br><strong>Container:</strong> {{ $inv->containerno }}
                                @endif
                            </div>
                        </div>

                        @if (count($lines) === 0)
                            <div class="text-muted mt-2">No consumables mapped (wf_consumable) for products in this invoice.</div>
                        @else
                            <div class="table-responsive mt-3">
                                <table class="table table-sm table-bordered mb-0">
                                    <thead>
                                        <tr>
                                            <th>Product</th>
                                            <th>Stockout Qty</th>
                                            <th>Location</th>
                                            <th>Consumable</th>
                                            <th>Per Product</th>
                                            <th>Used Qty</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($lines as $ln)
                                            <tr>
                                                <td>
                                                    {{ $ln['product_code'] }}
                                                    @if (!empty($ln['product_name']))
                                                        <br><small class="text-muted">{{ $ln['product_name'] }}</small>
                                                    @endif
                                                </td>
                                                <td>{{ $ln['stockout_qty'] }}</td>
                                                <td>{{ $ln['location'] }}</td>
                                                <td>{{ $ln['consumable_name'] }}</td>
                                                <td>
                                                    {{ $ln['unit_per_product'] }}
                                                    @if (!empty($ln['unit_type']))
                                                        <small class="text-muted">{{ $ln['unit_type'] }}</small>
                                                    @endif
                                                </td>
                                                <td>{{ $ln['used_qty'] }}</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>

                            @if (count($totals) > 0)
                                <div class="mt-3">
                                    <strong>Totals (by consumable):</strong>
                                    <ul class="mb-0">
                                        @foreach ($totals as $cid => $qty)
                                            <li>Consumable ID {{ $cid }}: {{ $qty }}</li>
                                        @endforeach
                                    </ul>
                                </div>
                            @endif
                        @endif
                    </div>
                @endforeach
            </div>
        </div>
    @endif
@endsection

@section('footer')
    <script type="text/javascript">
        $(function() {
            if ($('#containerInvoicesTable').length) {
                $('#containerInvoicesTable').DataTable({
                    order: []
                });
            }
        });
    </script>
@endsection
