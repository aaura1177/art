@extends('layouts.app')

@section('content')
<div class="mx-2">
    <div class="row mx-0 my-2">
        <h2>Backmonth Stock - Invoice {{ $invoice->invoiceno }}</h2>
    </div>

    <div class="mb-3">
        <a class="btn btn-secondary btn-sm" href="{{ route('manual_stock.backmonth.index') }}">Back to List</a>
    </div>

    <div class="alert alert-info">
        <strong>Invoice Date:</strong> {{ $invoice->date ? \Carbon\Carbon::parse($invoice->date)->format('d-M-Y') : '-' }}<br>
        <strong>Target Date:</strong> {{ \Carbon\Carbon::parse($targetDate)->format('d-M-Y') }}<br>
        <strong>Edit window:</strong> Till day {{ $editInfo['day_limit'] }} of current month.
        @if (!$editInfo['within_window'])
            <br><span class="text-danger">Editing is locked now because cutoff window has passed.</span>
        @endif
    </div>

    <div class="card mb-3">
        <div class="card-body">
            <div class="form-check mb-2">
                <input class="form-check-input" type="checkbox" id="toggleCumulative" {{ $editInfo['can_cumulative'] ? 'checked' : '' }} {{ $editInfo['can_cumulative'] ? '' : 'disabled' }}>
                <label class="form-check-label" for="toggleCumulative">
                    Enable Cumulative Update
                </label>
            </div>
            <div class="form-check mb-3">
                <input class="form-check-input" type="checkbox" id="toggleIndividual" {{ $editInfo['can_individual'] ? 'checked' : '' }} {{ $editInfo['can_individual'] ? '' : 'disabled' }}>
                <label class="form-check-label" for="toggleIndividual">
                    Enable Individual Edit
                </label>
            </div>

            <form method="POST" action="{{ route('manual_stock.backmonth.cumulative_update', $invoice->id) }}" class="mb-3">
                @csrf
                <input type="hidden" name="selected_log_ids" id="selectedLogIdsForCumulative" value="">
                <button type="submit" class="btn btn-warning btn-sm" id="btnCumulative"
                    {{ ($editInfo['can_edit'] && $editInfo['can_cumulative']) ? '' : 'disabled' }}>
                    Apply Cumulative Update
                </button>
            </form>

            <form method="POST" action="{{ route('manual_stock.backmonth.revert_cumulative_update', $invoice->id) }}" class="mb-3">
                @csrf
                <input type="hidden" name="selected_log_ids" id="selectedLogIdsForRevert" value="">
                <button type="submit" class="btn btn-danger btn-sm" id="btnRevertCumulative"
                    {{ ($editInfo['can_edit'] && $editInfo['can_cumulative']) ? '' : 'disabled' }}>
                    Revert Cumulative (use updated_at)
                </button>
            </form>

            <form method="POST" action="{{ route('manual_stock.backmonth.individual_update', $invoice->id) }}">
                @csrf
                <div class="table-responsive">
                    <table class="table table-bordered table-sm" id="backmonthStockTable">
                        <thead>
                            <tr>
                                <th>Cumulative Select</th>
                                <th>Select</th>
                                <th>Stock Log ID</th>
                                <th>Product Code</th>
                                <th>Product Name</th>
                                <th>Voucher No</th>
                                <th>Current Created At</th>
                                <th>New Created At</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($stockRows as $idx => $item)
                                @php
                                    $log = $item['log'];
                                    $invoiceRow = $item['invoice_row'];
                                @endphp
                                <tr>
                                    <td>
                                        <input type="checkbox" class="cumulative-checkbox" value="{{ $log->id }}"
                                            {{ ($editInfo['can_edit'] && $editInfo['can_cumulative']) ? 'checked' : 'disabled' }}>
                                    </td>
                                    <td>
                                        <input type="checkbox" name="rows[{{ $idx }}][selected]" value="1" class="individual-checkbox"
                                            {{ ($editInfo['can_edit'] && $editInfo['can_individual']) ? '' : 'disabled' }}>
                                        <input type="hidden" name="rows[{{ $idx }}][id]" value="{{ $log->id }}">
                                    </td>
                                    <td>{{ $log->id }}</td>
                                    <td>{{ $invoiceRow->product->code ?? '' }}</td>
                                    <td>{{ $invoiceRow->product->name ?? '' }}</td>
                                    <td>{{ $log->voucher_no }}</td>
                                    <td>{{ $log->created_at ? \Carbon\Carbon::parse($log->created_at)->format('d-M-Y H:i:s') : '' }}</td>
                                    <td>
                                        <input type="datetime-local"
                                            name="rows[{{ $idx }}][created_at]"
                                            class="form-control form-control-sm individual-input"
                                            value="{{ $log->created_at ? \Carbon\Carbon::parse($log->created_at)->format('Y-m-d\TH:i:s') : '' }}"
                                            step="1"
                                            {{ ($editInfo['can_edit'] && $editInfo['can_individual']) ? '' : 'disabled' }}>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <button type="submit" class="btn btn-primary btn-sm" id="btnIndividual"
                    {{ ($editInfo['can_edit'] && $editInfo['can_individual']) ? '' : 'disabled' }}>
                    Save Individual Updates
                </button>
            </form>
        </div>
    </div>
</div>
@endsection

@section('footer')
<script>
    (function () {
        var canCumulative = {{ ($editInfo['can_edit'] && $editInfo['can_cumulative']) ? 'true' : 'false' }};
        var canIndividual = {{ ($editInfo['can_edit'] && $editInfo['can_individual']) ? 'true' : 'false' }};
        var toggleCumulative = document.getElementById('toggleCumulative');
        var toggleIndividual = document.getElementById('toggleIndividual');
        var btnCumulative = document.getElementById('btnCumulative');
        var btnRevertCumulative = document.getElementById('btnRevertCumulative');
        var btnIndividual = document.getElementById('btnIndividual');
        var cumulativeChecks = document.querySelectorAll('.cumulative-checkbox');
        var individualInputs = document.querySelectorAll('.individual-input');
        var individualChecks = document.querySelectorAll('.individual-checkbox');
        var selectedLogIdsForCumulative = document.getElementById('selectedLogIdsForCumulative');
        var selectedLogIdsForRevert = document.getElementById('selectedLogIdsForRevert');

        function syncSelectedLogIds() {
            var selected = [];
            for (var i = 0; i < cumulativeChecks.length; i++) {
                if (!cumulativeChecks[i].disabled && cumulativeChecks[i].checked) {
                    selected.push(cumulativeChecks[i].value);
                }
            }
            var payload = selected.join(',');
            if (selectedLogIdsForCumulative) {
                selectedLogIdsForCumulative.value = payload;
            }
            if (selectedLogIdsForRevert) {
                selectedLogIdsForRevert.value = payload;
            }
        }

        function applyModes() {
            if (btnCumulative) {
                btnCumulative.disabled = !canCumulative || !toggleCumulative.checked;
            }
            if (btnRevertCumulative) {
                btnRevertCumulative.disabled = !canCumulative || !toggleCumulative.checked;
            }
            for (var k = 0; k < cumulativeChecks.length; k++) {
                cumulativeChecks[k].disabled = !canCumulative || !toggleCumulative.checked;
            }
            var enableIndividual = toggleIndividual.checked;
            if (btnIndividual) {
                btnIndividual.disabled = !canIndividual || !enableIndividual;
            }
            for (var i = 0; i < individualInputs.length; i++) {
                individualInputs[i].disabled = !canIndividual || !enableIndividual;
            }
            for (var j = 0; j < individualChecks.length; j++) {
                individualChecks[j].disabled = !canIndividual || !enableIndividual;
            }
            syncSelectedLogIds();
        }

        if (toggleCumulative) {
            toggleCumulative.addEventListener('change', applyModes);
        }
        if (toggleIndividual) {
            toggleIndividual.addEventListener('change', applyModes);
        }
        for (var m = 0; m < cumulativeChecks.length; m++) {
            cumulativeChecks[m].addEventListener('change', syncSelectedLogIds);
        }
        applyModes();
    })();
</script>
@endsection

