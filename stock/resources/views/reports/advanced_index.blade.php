@extends('layouts.app')

@section('content')
<div class="row mx-3 my-2">
    <h2>Advanced Reports</h2>
</div>

<div class="card mb-3">
    <div class="card-body">
        <form method="GET" action="{{ url('/reports/advanced') }}">
            <div class="row">
                <div class="col-md-3 mb-2">
                    <label>Report Type</label>
                    <select class="form-control" name="report_type" id="report_type">
                        @foreach($columnsMap as $key => $meta)
                            <option value="{{ $key }}" {{ $reportType === $key ? 'selected' : '' }}>{{ $meta['label'] }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2 mb-2">
                    <label>From</label>
                    <input type="date" class="form-control" name="from" value="{{ request('from') }}">
                </div>
                <div class="col-md-2 mb-2">
                    <label>To</label>
                    <input type="date" class="form-control" name="to" value="{{ request('to') }}">
                </div>
                <div class="col-md-2 mb-2">
                    <label>Product</label>
                    <select class="form-control selectpicker" data-live-search="true" name="product_id">
                        <option value="">All</option>
                        @foreach($products as $p)
                            <option value="{{ $p->id }}" {{ (string)request('product_id') === (string)$p->id ? 'selected' : '' }}>
                                {{ $p->code }} - {{ $p->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3 mb-2 filter-block" data-filter="invoice_no" style="{{ $reportType === 'movement_by_sales_invoice' ? '' : 'display:none;' }}">
                    <label>Sales invoice no (searchable)</label>
                    <select class="form-control selectpicker" data-live-search="true" name="invoice_no">
                        <option value="">All</option>
                        @foreach($invoiceOptions as $invNo)
                            <option value="{{ $invNo }}" {{ (string)request('invoice_no') === (string)$invNo ? 'selected' : '' }}>
                                {{ $invNo }}
                            </option>
                        @endforeach
                    </select>
                    @if($reportType === 'movement_by_sales_invoice' && $invoiceOptions->isEmpty() && (request()->filled('from') || request()->filled('to')))
                        <small class="text-muted">No invoices found for the selected date range.</small>
                    @endif
                </div>
                <div class="col-md-2 mb-2 filter-block" data-filter="batch_no" style="{{ $reportType === 'batch_detail' ? '' : 'display:none;' }}">
                    <label>Batch No (searchable)</label>
                    <select class="form-control selectpicker" data-live-search="true" name="batch_no">
                        <option value="">All</option>
                        @foreach($batchOptions as $batch)
                            <option value="{{ $batch['value'] }}" {{ (string)request('batch_no') === (string)$batch['value'] ? 'selected' : '' }}>
                                {{ $batch['label'] }}
                            </option>
                        @endforeach
                    </select>
                </div>
            </div>

            <hr>
            <div class="row">
                <div class="col-md-12">
                    <label><strong>Select Columns</strong></label>
                    <div class="mb-2">
                        <button type="button" class="btn btn-sm btn-outline-primary me-1" id="check-all-cols">Check All</button>
                        <button type="button" class="btn btn-sm btn-outline-secondary" id="uncheck-all-cols">Uncheck All</button>
                    </div>
                    <div id="columns-wrap">
                        @foreach($columnsMap as $key => $meta)
                            <div class="columns-group" data-report="{{ $key }}" style="{{ $reportType === $key ? '' : 'display:none;' }}">
                                @foreach($meta['columns'] as $colKey => $colLabel)
                                    <label class="me-3">
                                        <input type="checkbox" name="columns[]" value="{{ $colKey }}"
                                               {{ in_array($colKey, $selectedColumns, true) ? 'checked' : '' }}>
                                        {{ $colLabel }}
                                    </label>
                                @endforeach
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>

            <div class="row mt-3">
                <div class="col-md-12">
                    <button type="submit" name="preview" value="1" class="btn btn-primary">Preview</button>
                    <button type="submit" formaction="{{ url('/reports/advanced/export') }}" class="btn btn-success">Download Excel</button>
                    <a href="{{ url('/reports/advanced') }}" class="btn btn-danger">Hard Reset</a>
                </div>
            </div>
        </form>
    </div>
</div>

@if($previewRequested)
<div class="card">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-bordered table-sm">
                <thead>
                    <tr>
                        @foreach($selectedColumns as $colKey)
                            <th>{{ $columnsMap[$reportType]['columns'][$colKey] ?? $colKey }}</th>
                        @endforeach
                    </tr>
                </thead>
                <tbody>
                    @forelse($rows as $row)
                        <tr>
                            @foreach($selectedColumns as $colKey)
                                <td>{{ $row[$colKey] ?? '' }}</td>
                            @endforeach
                        </tr>
                    @empty
                        <tr>
                            <td colspan="{{ count($selectedColumns) }}">No data found for selected filters.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endif
@endsection

@section('footer')
<script>
    (function () {
        var report = document.getElementById('report_type');
        var form = report ? report.closest('form') : null;
        function syncColumns() {
            var current = report.value;
            document.querySelectorAll('.columns-group').forEach(function (el) {
                var active = el.getAttribute('data-report') === current;
                el.style.display = active ? '' : 'none';
                // Prevent hidden report groups from submitting duplicate columns[] values.
                el.querySelectorAll('input[type="checkbox"]').forEach(function (cb) {
                    cb.disabled = !active;
                });
            });

            // Show only one contextual filter at a time to keep UI simple.
            document.querySelectorAll('.filter-block').forEach(function (el) {
                var key = el.getAttribute('data-filter');
                var show = false;
                if (current === 'batch_detail' && key === 'batch_no') show = true;
                if (current === 'movement_by_sales_invoice' && key === 'invoice_no') show = true;
                el.style.display = show ? '' : 'none';
                el.querySelectorAll('select').forEach(function (sel) {
                    sel.disabled = !show;
                });
            });
        }
        report.addEventListener('change', function () {
            // Hard reset on type change: keep only report_type in query.
            var base = "{{ url('/reports/advanced') }}";
            window.location.href = base + '?report_type=' + encodeURIComponent(report.value);
        });

        function updateUrlFromForm(formEl) {
            var action = formEl.getAttribute('action') || "{{ url('/reports/advanced') }}";
            var fd = new FormData(formEl);
            fd.delete('preview');
            var params = new URLSearchParams();
            fd.forEach(function (value, key) {
                if (value === null || value === '') return;
                params.append(key, value);
            });
            var next = action + (params.toString() ? ('?' + params.toString()) : '');
            window.history.replaceState({}, '', next);
        }

        function refreshInvoiceOptionsAjax() {
            if (!form || report.value !== 'movement_by_sales_invoice') return;
            var invoiceSelect = form.querySelector('select[name="invoice_no"]');
            if (!invoiceSelect) return;

            var params = new URLSearchParams();
            var fromEl = form.querySelector('input[name="from"]');
            var toEl = form.querySelector('input[name="to"]');
            if (fromEl && fromEl.value) params.set('from', fromEl.value);
            if (toEl && toEl.value) params.set('to', toEl.value);

            fetch("{{ route('reports.advanced.invoice_options') }}?" + params.toString(), {
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            })
            .then(function (res) { return res.json(); })
            .then(function (data) {
                var options = (data && data.invoice_options) ? data.invoice_options : [];
                var current = invoiceSelect.value || '';
                while (invoiceSelect.options.length > 0) {
                    invoiceSelect.remove(0);
                }
                invoiceSelect.add(new Option('All', ''));
                options.forEach(function (invNo) {
                    var opt = new Option(invNo, invNo);
                    if (String(invNo) === String(current)) opt.selected = true;
                    invoiceSelect.add(opt);
                });
                if (typeof $ !== 'undefined' && $.fn.selectpicker) {
                    $(invoiceSelect).selectpicker('refresh');
                }
            })
            .catch(function () {
                // Fallback to normal submit if ajax fails.
                form.submit();
            });
        }

        // Apply date filters immediately (without waiting for Preview click).
        ['from', 'to'].forEach(function (name) {
            var el = form ? form.querySelector('[name="' + name + '"]') : null;
            if (!el) return;
            el.addEventListener('change', function () {
                if (!form) return;
                updateUrlFromForm(form);
                if (report.value === 'movement_by_sales_invoice') {
                    refreshInvoiceOptionsAjax();
                }
            });
        });
        syncColumns();
    })();
</script>
<script>
    (function () {
        if (typeof $ !== 'undefined' && $.fn.selectpicker) {
            $('.selectpicker').selectpicker('refresh');
        }
    })();
</script>
<script>
    (function () {
        function activeGroup() {
            return document.querySelector('.columns-group:not([style*="display:none"])');
        }
        document.getElementById('check-all-cols').addEventListener('click', function () {
            var g = activeGroup();
            if (!g) return;
            g.querySelectorAll('input[type="checkbox"]:not([disabled])').forEach(function (cb) { cb.checked = true; });
        });
        document.getElementById('uncheck-all-cols').addEventListener('click', function () {
            var g = activeGroup();
            if (!g) return;
            g.querySelectorAll('input[type="checkbox"]:not([disabled])').forEach(function (cb) { cb.checked = false; });
        });
    })();
</script>
@endsection

