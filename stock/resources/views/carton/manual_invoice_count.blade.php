@extends('layouts.app')

@section('content')
    <div class="row mx-3 my-2">
        <h2>Manual Carton Invoice Count</h2>
    </div>

    @if (session('success'))
        <div class="alert alert-success mx-3">{{ session('success') }}</div>
    @endif
    @if (session('error'))
        <div class="alert alert-danger mx-3">{!! session('error') !!}</div>
    @endif

    <div class="card mb-3 mx-3">
        <div class="card-body">
            <p class="text-muted small mb-2">
                Furniture stockout no longer deducts cartons. Process carton stockout here within
                <strong>{{ $fromDate }}</strong> to <strong>{{ $toDate }}</strong>.
                One invoice per container at a time (oldest first). Locked when a later export invoice in the same container has furniture stockout.
            </p>
            <form method="GET" action="{{ route('carton.manual_invoice_count') }}">
                <div class="row align-items-end">
                    <div class="col-md-6">
                        <label for="containerno">Select Invoice Container No.</label>
                        <select class="form-control" name="containerno" id="containerno" onchange="this.form.submit()">
                            <option value="">Select</option>
                            @foreach ($containers as $containerNo)
                                <option value="{{ $containerNo }}" {{ $selectedContainer === (string) $containerNo ? 'selected' : '' }}>
                                    {{ $containerNo }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>
            </form>
        </div>
    </div>

    @if ($selectedContainer !== '')
        @if (!$activeInvoice)
            <div class="alert alert-info mx-3">
                No eligible invoice in container <strong>{{ $selectedContainer }}</strong> for manual carton stockout
                (completed, outside 2-day window, or locked by a later export stockout).
            </div>
        @else
            @php
                $lines = $breakdown['lines'] ?? [];
                $lockReason = $breakdown['lock_reason'] ?? null;
                $canSubmit = $lockReason === null && count($lines) > 0;
            @endphp

            <div class="card mb-3 mx-3">
                <div class="card-header">
                    <strong>Active invoice:</strong> {{ $activeInvoice->invoiceno ?? $activeInvoice->id }}
                    &nbsp;|&nbsp;
                    <strong>Date:</strong> {{ $activeInvoice->date ? date('d-M-Y', strtotime($activeInvoice->date)) : '' }}
                    &nbsp;|&nbsp;
                    <strong>Buyer:</strong> {{ $activeInvoice->buyer->name ?? $activeInvoice->buyer->c_name ?? '' }}
                </div>
                <div class="card-body">
                    @if ($lockReason)
                        <div class="alert alert-warning">{{ $lockReason }}</div>
                    @endif

                    @if (count($lines) === 0)
                        <div class="text-muted">All product lines on this invoice already have carton stockout.</div>
                    @else
                        <form method="POST" action="{{ route('carton.manual_invoice_carton_stockout') }}" id="cartonStockoutForm"
                            onsubmit="return confirm('Process carton stockout for this invoice?');">
                            @csrf
                            <input type="hidden" name="invoice_id" value="{{ $activeInvoice->id }}">

                            @foreach ($lines as $idx => $ln)
                                <div class="border rounded p-3 mb-3 product-line-block" data-line-idx="{{ $idx }}"
                                    data-total-furniture="{{ $ln['stockout_qty'] }}"
                                    data-deducted-b1="{{ $ln['deducted_box1'] }}"
                                    data-deducted-b2="{{ $ln['deducted_box2'] }}"
                                    data-stock-box1="{{ $ln['stock_box1'] }}"
                                    data-stock-box2="{{ $ln['stock_box2'] }}">
                                    <div class="d-flex justify-content-between align-items-start mb-2">
                                        <div>
                                            <strong>{{ $ln['product_code'] }}</strong>
                                            @if (!empty($ln['product_name']))
                                                <span class="text-muted"> — {{ $ln['product_name'] }}</span>
                                            @endif
                                            <br>
                                            <small>
                                                Furniture out (total): {{ $ln['stockout_qty'] }}
                                                @if (!empty($ln['is_incremental']))
                                                    | Already cartoned B1: {{ $ln['deducted_box1'] }} / B2: {{ $ln['deducted_box2'] }}
                                                    | <strong class="text-primary">Pending carton for additional stockout</strong>
                                                @endif
                                                | Location: {{ $ln['location'] ?: '—' }}
                                            </small>
                                        </div>
                                    </div>

                                    <input type="hidden" name="lines[{{ $idx }}][product_id]" value="{{ $ln['product_id'] }}">

                                    <div class="table-responsive mb-2">
                                        <table class="table table-sm table-bordered mb-0">
                                            <thead class="thead-light">
                                                <tr>
                                                    <th>Qty1 / unit</th>
                                                    <th>Qty2 / unit</th>
                                                    <th>Pending B1</th>
                                                    <th>Pending B2</th>
                                                    <th>Stock B1</th>
                                                    <th>Stock B2</th>
                                                    <th>Short B1</th>
                                                    <th>Short B2</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <tr class="{{ !empty($ln['needs_qty']) ? 'table-warning' : '' }}">
                                                    <td>
                                                        <input type="number" step="any" min="0" class="form-control form-control-sm qty1-input"
                                                            name="lines[{{ $idx }}][quantity1]" value="{{ $ln['qty1_per_product'] }}">
                                                    </td>
                                                    <td>
                                                        <input type="number" step="any" min="0" class="form-control form-control-sm qty2-input"
                                                            name="lines[{{ $idx }}][quantity2]" value="{{ $ln['qty2_per_product'] }}">
                                                    </td>
                                                    <td class="need-b1">{{ $ln['need_box1'] }}</td>
                                                    <td class="need-b2">{{ $ln['need_box2'] }}</td>
                                                    <td class="stock-b1">{{ $ln['stock_box1'] }}</td>
                                                    <td class="stock-b2">{{ $ln['stock_box2'] }}</td>
                                                    <td class="short-b1">{{ $ln['short_box1'] }}</td>
                                                    <td class="short-b2">{{ $ln['short_box2'] }}</td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </div>

                                    <div class="swap-section">
                                        <div class="d-flex justify-content-between align-items-center mb-2">
                                            <strong>Swap sources</strong> <small class="text-muted">(processed in priority order; box1→box1, box2→box2)</small>
                                            <button type="button" class="btn btn-sm btn-outline-primary add-swap-row"
                                                data-line-idx="{{ $idx }}" data-exclude="{{ $ln['product_id'] }}">+ Add alternate</button>
                                        </div>
                                        <div class="swap-rows" data-line-idx="{{ $idx }}"></div>
                                    </div>
                                </div>
                            @endforeach

                            <button type="submit" class="btn btn-primary" {{ $canSubmit ? '' : 'disabled' }}>
                                Process Carton Stockout
                            </button>
                        </form>
                    @endif
                </div>
            </div>
        @endif
    @endif
@endsection

@section('footer')
    <script type="text/javascript">
        (function() {
            var searchUrl = @json(route('carton.manual_alternate_products'));
            var swapCounters = {};

            function recalcLine($block) {
                var totalFurniture = parseFloat($block.data('total-furniture')) || 0;
                var deductedB1 = parseFloat($block.data('deducted-b1')) || 0;
                var deductedB2 = parseFloat($block.data('deducted-b2')) || 0;
                var q1 = parseFloat($block.find('.qty1-input').val()) || 0;
                var q2 = parseFloat($block.find('.qty2-input').val()) || 0;
                var stock1 = parseFloat($block.data('stock-box1')) || 0;
                var stock2 = parseFloat($block.data('stock-box2')) || 0;
                var need1 = Math.max(0, (q1 * totalFurniture) - deductedB1);
                var need2 = Math.max(0, (q2 * totalFurniture) - deductedB2);

                var swap1 = 0, swap2 = 0;
                $block.find('.swap-row').each(function() {
                    swap1 += parseFloat($(this).find('.swap-box1').val()) || 0;
                    swap2 += parseFloat($(this).find('.swap-box2').val()) || 0;
                });

                var cover1 = stock1 + swap1;
                var cover2 = stock2 + swap2;

                $block.find('.need-b1').text(need1);
                $block.find('.need-b2').text(need2);
                $block.find('.short-b1').text(Math.max(0, need1 - cover1).toFixed(4).replace(/\.?0+$/, ''));
                $block.find('.short-b2').text(Math.max(0, need2 - cover2).toFixed(4).replace(/\.?0+$/, ''));
            }

            function buildSwapRow(lineIdx, excludeId, priority) {
                var rowId = (swapCounters[lineIdx] || 0);
                swapCounters[lineIdx] = rowId + 1;
                var uid = lineIdx + '_' + rowId;

                return '<div class="swap-row border rounded p-2 mb-2" data-uid="' + uid + '">' +
                    '<div class="row g-2 align-items-end">' +
                    '<div class="col-md-1"><label class="small">Priority</label>' +
                    '<input type="number" min="1" class="form-control form-control-sm swap-priority" name="lines[' + lineIdx + '][swaps][' + uid + '][priority]" value="' + priority + '"></div>' +
                    '<div class="col-md-4"><label class="small">Alternate product</label>' +
                    '<input type="text" class="form-control form-control-sm swap-search" placeholder="Search code/name..." autocomplete="off">' +
                    '<input type="hidden" class="swap-product-id" name="lines[' + lineIdx + '][swaps][' + uid + '][from_product_id]" value="">' +
                    '<div class="swap-search-results list-group" style="position:absolute;z-index:10;max-height:180px;overflow-y:auto;display:none;"></div>' +
                    '<small class="swap-product-label text-muted"></small></div>' +
                    '<div class="col-md-2"><label class="small">Avail B1</label><span class="d-block avail-b1">—</span></div>' +
                    '<div class="col-md-2"><label class="small">Avail B2</label><span class="d-block avail-b2">—</span></div>' +
                    '<div class="col-md-1"><label class="small">Swap B1</label>' +
                    '<input type="number" step="any" min="0" class="form-control form-control-sm swap-box1" name="lines[' + lineIdx + '][swaps][' + uid + '][box1]" value="0"></div>' +
                    '<div class="col-md-1"><label class="small">Swap B2</label>' +
                    '<input type="number" step="any" min="0" class="form-control form-control-sm swap-box2" name="lines[' + lineIdx + '][swaps][' + uid + '][box2]" value="0"></div>' +
                    '<div class="col-md-1"><button type="button" class="btn btn-sm btn-outline-danger remove-swap-row">×</button></div>' +
                    '</div></div>';
            }

            $(document).on('click', '.add-swap-row', function() {
                var lineIdx = $(this).data('line-idx');
                var excludeId = $(this).data('exclude');
                var $container = $('.swap-rows[data-line-idx="' + lineIdx + '"]');
                var priority = $container.find('.swap-row').length + 1;
                $container.append(buildSwapRow(lineIdx, excludeId, priority));
                $container.find('.swap-row').last().data('exclude', excludeId);
            });

            $(document).on('click', '.remove-swap-row', function() {
                var $block = $(this).closest('.product-line-block');
                $(this).closest('.swap-row').remove();
                recalcLine($block);
            });

            $(document).on('input change', '.qty1-input, .qty2-input, .swap-box1, .swap-box2', function() {
                recalcLine($(this).closest('.product-line-block'));
            });

            var searchTimer = null;
            $(document).on('input', '.swap-search', function() {
                var $input = $(this);
                var $row = $input.closest('.swap-row');
                var q = $input.val();
                var exclude = $row.data('exclude') || 0;
                var $results = $row.find('.swap-search-results');

                clearTimeout(searchTimer);
                if (q.length < 1) {
                    $results.hide().empty();
                    return;
                }
                searchTimer = setTimeout(function() {
                    $.getJSON(searchUrl, { q: q, exclude: exclude }, function(data) {
                        $results.empty();
                        if (!data.length) {
                            $results.append('<div class="list-group-item small text-muted">No results</div>');
                        }
                        data.forEach(function(item) {
                            var label = (item.product_code || item.product_id) + ' (B1:' + item.box_1_qty + ' B2:' + item.box_2_qty + ')';
                            $results.append(
                                '<button type="button" class="list-group-item list-group-item-action small swap-pick" ' +
                                'data-id="' + item.product_id + '" data-code="' + (item.product_code || '') + '" ' +
                                'data-b1="' + item.box_1_qty + '" data-b2="' + item.box_2_qty + '">' + label + '</button>'
                            );
                        });
                        $results.show();
                    });
                }, 300);
            });

            $(document).on('click', '.swap-pick', function() {
                var $btn = $(this);
                var $row = $btn.closest('.swap-row');
                $row.find('.swap-product-id').val($btn.data('id'));
                $row.find('.swap-search').val($btn.data('code'));
                $row.find('.swap-product-label').text('ID ' + $btn.data('id'));
                $row.find('.avail-b1').text($btn.data('b1'));
                $row.find('.avail-b2').text($btn.data('b2'));
                $row.find('.swap-search-results').hide().empty();
            });

            $(document).on('click', function(e) {
                if (!$(e.target).closest('.swap-search, .swap-search-results').length) {
                    $('.swap-search-results').hide();
                }
            });
        })();
    </script>
@endsection
