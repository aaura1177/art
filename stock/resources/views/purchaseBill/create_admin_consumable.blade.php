@extends('layouts.app')

@section('content')
<div class="mx-2">
    <div class="row mx-0 my-2">
        <h2>Admin inward supply — consumables</h2>
        <p class="text-muted mb-0">Creates purchase bill and supplier invoice together (no batch / UR). Open consumable POs with remaining line quantity only.</p>
    </div>

    @if (session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif
    @if (session('danger'))
        <div class="alert alert-danger">{{ session('danger') }}</div>
    @endif
    @if ($errors->any())
        <div class="alert alert-danger">
            <ul class="mb-0">@foreach ($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
        </div>
    @endif

    <div id="eligibilityAlert" class="alert alert-warning d-none" role="alert"></div>

    @if ($purchaseOrders->isEmpty())
        <div class="alert alert-info">No open consumable purchase orders with remaining line quantity. Create or complete a PO first.</div>
    @endif

    <form id="adminConsumableInward" method="POST" action="{{ url('/purchaseBill/admin/consumable') }}">
        @csrf
        <input type="hidden" name="totaldiscount" value="0" />
        <input type="hidden" id="supplier_tds_percent" value="0" />
        <input type="hidden" id="supplier_city" value="" />

        <div class="row mt-3">
            <div class="col-md-4">
                <label class="control-label">PO No.</label>
                <select class="form-control" name="purchaseOrder_id" id="purchaseOrder_id" required @if ($purchaseOrders->isEmpty()) disabled @endif>
                    <option value="" selected disabled>Select PO</option>
                    @foreach ($purchaseOrders as $po)
                        <option value="{{ $po->id }}">{{ $po->pono }} — {{ optional($po->supplier)->c_name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-4">
                <label class="control-label">Supplier Name</label>
                <input type="text" class="form-control" id="supplierName" readonly />
            </div>
            <div class="col-md-4">
                <label class="control-label">Delivery Date</label>
                <input type="date" class="form-control" id="del_date" readonly />
            </div>
        </div>

        <div class="row mt-3">
            <div class="col-md-4">
                <label class="control-label">Supplier Ref.</label>
                <input type="text" class="form-control" id="Supplier_ref" readonly />
            </div>
            <div class="col-md-4">
                <label class="control-label">Ordered Quantity</label>
                <input type="number" class="form-control" id="ordered_qty" readonly />
            </div>
            <div class="col-md-4">
                <label class="control-label">Sub Total Amount</label>
                <input type="number" class="form-control" id="po_sub_total" readonly />
            </div>
        </div>

        <div class="row mt-3">
            <div class="col-md-8">
                <label class="control-label">Remark</label>
                <textarea class="form-control" id="remarks" readonly></textarea>
            </div>
        </div>

        <div class="row mt-3">
            <div class="col-md-4">
                <label class="control-label">Supplier Invoice No.</label>
                <input type="text" class="form-control text-uppercase" name="supp_inv_no" id="supp_inv_no" required />
            </div>
            <div class="col-md-4">
                <label class="control-label">Supplier Invoice Date</label>
                <input type="date" class="form-control" name="supp_inv_date" id="supp_inv_date" required />
            </div>
            <div class="col-md-4">
                <label class="control-label">E-Way Bill No.</label>
                <input type="text" class="form-control text-uppercase" name="ewaybill" id="ewaybill" />
                <small class="text-danger">Required if total exceeds threshold (Jaipur: ₹2,00,000, others: ₹1,00,000).</small>
            </div>
        </div>

        <div class="row mt-3">
            <div class="col-12">
                <table class="table table-bordered table-sm" id="linesTable">
                    <thead class="thead-light">
                        <tr>
                            <th>#</th>
                            <th>Item</th>
                            <th>Unit</th>
                            <th>Rem. qty</th>
                            <th>Receive qty</th>
                            <th>Rate (₹)</th>
                            <th>Amount (₹)</th>
                            <th>GST %</th>
                            <th>Location</th>
                        </tr>
                    </thead>
                    <tbody id="lineBody">
                        <tr><td colspan="9" class="text-center text-muted">Select a PO to load lines.</td></tr>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="row mt-3">
            <div class="col-md-3">
                <label class="control-label">Total quantity</label>
                <input type="number" class="form-control" name="pbQty" id="pbQty" readonly />
            </div>
            <div class="col-md-3">
                <label class="control-label">Sub-total (₹)</label>
                <input type="number" step="0.01" class="form-control" name="pbSubTotal" id="pbSubTotal" readonly />
            </div>
            <div class="col-md-3">
                <label class="control-label">GST (₹)</label>
                <input type="number" step="0.01" class="form-control" name="pbGST" id="pbGST" readonly />
            </div>
            <div class="col-md-3">
                <label class="control-label">Freight (₹)</label>
                <input type="number" step="0.01" class="form-control" name="freight" id="freight" value="0" min="0" />
            </div>
        </div>

        <div class="row mt-3">
            <div class="col-md-3">
                <label class="control-label">Total (₹)</label>
                <input type="number" step="0.01" class="form-control" name="pbTotal" id="pbTotal" readonly />
            </div>
            <div class="col-md-3">
                <label class="control-label">Total TDS</label>
                <input type="number" step="0.01" class="form-control" name="tdsTotal" id="tdsTotal" readonly />
            </div>
        </div>

        <div class="row mt-3 mb-4">
            <div class="col-md-4">
                <button type="submit" class="btn btn-primary" id="submitBtn" disabled>Save inward</button>
                <a href="{{ url('/purchaseBill/condition/consumables') }}" class="btn btn-secondary ms-2">Cancel</a>
            </div>
        </div>
    </form>
</div>
@endsection

@section('footer')
<script>
(function () {
    var eligibleDate = null;
    var linesUrl = "{{ url('/purchaseBill/admin/data/consumable-po') }}";

    function escAttr(s) {
        return String(s == null ? '' : s).replace(/&/g, '&amp;').replace(/"/g, '&quot;').replace(/</g, '&lt;');
    }

    function todayStr() {
        return new Date().toISOString().slice(0, 10);
    }

    function isEligible() {
        if (!eligibleDate) return true;
        return todayStr() >= eligibleDate;
    }

    function invoiceDetailsComplete() {
        var inv = $.trim($('#supp_inv_no').val() || '');
        var dt = $('#supp_inv_date').val() || '';
        return inv !== '' && dt !== '';
    }

    function applyLineInputsGate() {
        var ok = invoiceDetailsComplete();
        $('#lineBody tr[data-line="1"]').find('.recv, .loc').prop('disabled', !ok);
    }

    function updateSubmitState() {
        var hasLines = $('#lineBody tr[data-line="1"]').length > 0;
        $('#submitBtn').prop('disabled', !hasLines || !invoiceDetailsComplete() || !isEligible());
    }

    function setEligibilityMessage() {
        var $a = $('#eligibilityAlert');
        if (!eligibleDate) {
            $a.addClass('d-none').text('');
            return;
        }
        if (!isEligible()) {
            var parts = eligibleDate.split('-');
            $a.removeClass('d-none').html(
                'Inward is allowed from <strong>' + parts[2] + '-' + parts[1] + '-' + parts[0] + '</strong> (7 days before delivery).'
            );
        } else {
            $a.addClass('d-none').text('');
        }
    }

    function normalizeReceiveQtyInput($input, dataType) {
        var raw = ($input.val() || '').toString();
        if (dataType === 'int') {
            var intVal = parseInt(raw, 10);
            if (isNaN(intVal)) intVal = 0;
            $input.val(intVal);
        } else {
            var floatVal = parseFloat(raw);
            if (isNaN(floatVal)) floatVal = 0;
            $input.val(floatVal);
        }
    }

    function recalcRow($tr) {
        var dataType = $tr.data('dtype') || 'int';
        var $recv = $tr.find('.recv');
        normalizeReceiveQtyInput($recv, dataType);
        var rq = parseFloat($recv.val()) || 0;
        var rem = parseFloat($tr.data('remqty')) || 0;
        if (rq > rem) {
            rq = rem;
            $recv.val(dataType === 'int' ? parseInt(rem, 10) : rem.toFixed(2));
        }
        var rate = parseFloat($tr.find('.rate').val()) || 0;
        var gst = parseFloat($tr.find('input.gstslab').val()) || 0;
        var amount = rq * rate;
        $tr.find('.amount').val(amount.toFixed(2));
        $tr.find('.gstamount').val(((amount * gst) / 100).toFixed(2));
    }

    function recalcTotals() {
        var totQty = 0, sub = 0, gst = 0;
        $('#lineBody tr[data-line="1"]').each(function () {
            var $tr = $(this);
            recalcRow($tr);
            totQty += parseFloat($tr.find('.recv').val()) || 0;
            sub += parseFloat($tr.find('.amount').val()) || 0;
            gst += parseFloat($tr.find('.gstamount').val()) || 0;
        });
        var freight = parseFloat($('#freight').val()) || 0;
        $('#pbQty').val(totQty.toFixed(2));
        $('#pbSubTotal').val(sub.toFixed(2));
        $('#pbGST').val(gst.toFixed(2));
        $('#pbTotal').val((sub + gst + freight).toFixed(2));
        var tdsPercent = parseFloat($('#supplier_tds_percent').val()) || 0;
        var tdsAmount = (sub * tdsPercent) / 100;
        $('#tdsTotal').val(tdsAmount.toFixed(2));

        var pbTotal = parseFloat($('#pbTotal').val()) || 0;
        var supplierCity = (($('#supplier_city').val() || '').toString().trim().toUpperCase());
        var ewayThreshold = (supplierCity === 'JAIPUR') ? 200000 : 100000;
        $('#ewaybill').prop('required', pbTotal > ewayThreshold);
    }

    function renderLines(lines) {
        var $body = $('#lineBody');
        $body.empty();
        if (!lines.length) {
            $body.append('<tr><td colspan="9" class="text-center text-muted">No open lines on this PO.</td></tr>');
            updateSubmitState();
            return;
        }
        var html = '';
        lines.forEach(function (line, idx) {
            var label = escAttr(line.name) + (line.sku ? ' (' + escAttr(line.sku) + ')' : '');
            html += '<tr data-line="1" data-remqty="' + line.remqty + '" data-dtype="' + (line.data_type || 'int') + '">';
            html += '<td>' + (idx + 1) + '</td>';
            html += '<td>' + label;
            html += '<input type="hidden" name="pb[' + idx + '][product]" value="' + line.consumable_id + '" />';
            html += '<input type="hidden" class="gstslab" name="pb[' + idx + '][gstslab]" value="' + line.gstslab + '" /></td>';
            html += '<td>' + escAttr(line.unit) + '<input type="hidden" name="pb[' + idx + '][unit]" value="' + escAttr(line.unit) + '" /></td>';
            html += '<td>' + line.remqty + '</td>';
            var qtyStep = (line.data_type === 'int') ? '1' : '0.01';
            html += '<td><input type="number" step="' + qtyStep + '" min="0" max="' + line.remqty + '" class="form-control recv" name="pb[' + idx + '][receiveqty]" value="0" disabled /></td>';
            html += '<td><input type="number" step="0.01" class="form-control rate" name="pb[' + idx + '][rate]" value="' + line.rate + '" readonly /></td>';
            html += '<td><input type="number" step="0.01" class="form-control amount" name="pb[' + idx + '][amount]" readonly /></td>';
            html += '<td>' + line.gstslab + '</td>';
            html += '<td><input type="text" class="form-control loc" name="pb[' + idx + '][location]" value="" disabled />';
            html += '<input type="hidden" class="gstamount" value="0" /></td>';
            html += '</tr>';
        });
        $body.html(html);
        applyLineInputsGate();
        recalcTotals();
        updateSubmitState();
    }

    $('#lineBody').on('input change', '.recv', function () {
        recalcTotals();
    });
    $('#freight').on('input', recalcTotals);

    $('#supp_inv_no, #supp_inv_date').on('input change', function () {
        applyLineInputsGate();
        recalcTotals();
        updateSubmitState();
    });

    $('#purchaseOrder_id').on('change', function () {
        var id = $(this).val();
        if (!id) return;
        $('#submitBtn').prop('disabled', true);
        $('#lineBody').html('<tr><td colspan="9">Loading…</td></tr>');
        $.getJSON(linesUrl + '/' + id)
            .done(function (res) {
                var po = res.purchase_order || {};
                $('#supplierName').val(po.supplier_name || '');
                $('#del_date').val(po.del_date || '');
                $('#Supplier_ref').val(po.ref_supplier || '');
                $('#ordered_qty').val(po.ordered_qty || '');
                $('#po_sub_total').val(po.po_sub_total || '');
                $('#remarks').val(po.remarks || '');
                $('#supplier_city').val(po.supplier_city || '');
                $('#supplier_tds_percent').val(po.supplier_tds_percent || 0);
                eligibleDate = res.eligible_inward_date || null;
                setEligibilityMessage();
                renderLines(res.lines || []);
            })
            .fail(function () {
                $('#lineBody').html('<tr><td colspan="9" class="text-danger">Could not load PO lines.</td></tr>');
            });
    });

    $('#adminConsumableInward').on('submit', function (e) {
        $('#lineBody tr[data-line="1"]').find('.recv, .loc').prop('disabled', false);
        if (!invoiceDetailsComplete()) {
            e.preventDefault();
            applyLineInputsGate();
            alert('Please enter Supplier Invoice No. and Supplier Invoice Date first.');
            return;
        }
        if (!isEligible()) {
            e.preventDefault();
            alert('This PO is not yet eligible for inward (date rule).');
            return;
        }
        var hasRecv = false;
        $('#lineBody tr[data-line="1"]').each(function () {
            var v = parseFloat($(this).find('.recv').val()) || 0;
            if (v > 0) hasRecv = true;
        });
        if (!hasRecv) {
            e.preventDefault();
            alert('Enter receive quantity on at least one line.');
            return;
        }
        $('#submitBtn').prop('disabled', true);
    });
})();
</script>
@endsection
