@extends('layouts.app')

@section('content')
<div class="mx-2">
    <div class="row mx-0 my-2">
        <h2>Admin inward supply — carton</h2>
        <p class="text-muted mb-0">Creates purchase bill and supplier invoice together (no batch / UR). Open carton POs with remaining box quantities only.</p>
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
        <div class="alert alert-info">No open carton purchase orders with remaining box quantities. Create or complete a PO first.</div>
    @endif

    <style>
        #lineBody .carton-product-label {
            max-width: 240px;
            white-space: pre-line;
            word-break: break-word;
            overflow-wrap: anywhere;
            vertical-align: top;
        }
    </style>

    <form id="adminCartonInward" method="POST" action="{{ url('/purchaseBill/admin/carton') }}">
        @csrf
        <input type="hidden" name="totaldiscount" value="0" />
        <input type="hidden" id="supplier_tds_percent" value="0" />
        <input type="hidden" id="supplier_tds_date" value="" />

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
                <small class="text-danger">Required if total exceeds ₹1,00,000.</small>
            </div>
        </div>

        <div class="row mt-3">
            <div class="col-12">
                <table class="table table-bordered table-sm">
                    <thead class="thead-light">
                        <tr>
                            <th>#</th>
                            <th>Product</th>
                            <th>Rem. box 1</th>
                            <th>Rem. box 2</th>
                            <th>Recv box 1</th>
                            <th>Recv box 2</th>
                            <th>Rate box 1</th>
                            <th>Rate box 2</th>
                            <th>Line amount (₹)</th>
                            <th>GST %</th>
                        </tr>
                    </thead>
                    <tbody id="lineBody">
                        <tr><td colspan="10" class="text-center text-muted">Select a PO to load lines.</td></tr>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="row mt-3">
            <div class="col-md-3">
                <label class="control-label">Total quantity (boxes)</label>
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
            <div class="col-md-3">
                <label class="control-label">Round Off</label>
                <input type="number" step=".1" class="form-control" name="roundoff" id="roundoff" value="0" min="-3" max="3" />
            </div>
        </div>

        <div class="row mt-3 mb-4">
            <div class="col-md-4">
                <button type="submit" class="btn btn-primary" id="submitBtn" disabled>Save inward</button>
                <a href="{{ url('/purchaseBill/condition/carton') }}" class="btn btn-secondary ms-2">Cancel</a>
            </div>
        </div>
    </form>
</div>
@endsection

@section('footer')
<script>
(function () {
    var eligibleDate = null;
    var linesUrl = "{{ url('/purchaseBill/admin/data/carton-po') }}";

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
        $('#lineBody tr[data-line="1"]').find('.recv1, .recv2').prop('disabled', !ok);
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

    function recalcRow($tr) {
        var r1 = parseInt($tr.find('.recv1').val(), 10) || 0;
        var r2 = parseInt($tr.find('.recv2').val(), 10) || 0;
        var max1 = parseInt($tr.data('rem1'), 10) || 0;
        var max2 = parseInt($tr.data('rem2'), 10) || 0;
        if (r1 > max1) { r1 = max1; $tr.find('.recv1').val(max1); }
        if (r2 > max2) { r2 = max2; $tr.find('.recv2').val(max2); }
        var rate1 = parseFloat($tr.find('.rate1').val()) || 0;
        var rate2 = parseFloat($tr.find('.rate2').val()) || 0;
        var gst = parseFloat($tr.find('input.gstslab').val()) || 0;
        var amount = (r1 * rate1) + (r2 * rate2);
        $tr.find('.amount').val(amount.toFixed(2));
        $tr.find('.gstamount').val(((amount * gst) / 100).toFixed(2));
    }

    function recalcTotals() {
        var totBoxes = 0, sub = 0, gst = 0;
        $('#lineBody tr[data-line="1"]').each(function () {
            var $tr = $(this);
            recalcRow($tr);
            totBoxes += (parseInt($tr.find('.recv1').val(), 10) || 0) + (parseInt($tr.find('.recv2').val(), 10) || 0);
            sub += parseFloat($tr.find('.amount').val()) || 0;
            gst += parseFloat($tr.find('.gstamount').val()) || 0;
        });
        $('#pbQty').val(totBoxes);
        $('#pbSubTotal').val(sub.toFixed(2));
        $('#pbGST').val(gst.toFixed(2));
        var freight = parseFloat($('#freight').val()) || 0;
        var roundOff = parseFloat($('#roundoff').val()) || 0;
        if (roundOff > 3) { roundOff = 3; $('#roundoff').val(3); }
        if (roundOff < -3) { roundOff = -3; $('#roundoff').val(-3); }
        $('#pbTotal').val((sub + gst + freight + roundOff).toFixed(2));

        var tdsAmount = 0;
        var tdsPercent = parseFloat($('#supplier_tds_percent').val()) || 0;
        var tdsDateRaw = $('#supplier_tds_date').val();
        var invoiceDateRaw = $('#supp_inv_date').val();
        if (invoiceDateRaw && tdsDateRaw) {
            var invoiceDate = new Date(invoiceDateRaw);
            var tdsDate = new Date(tdsDateRaw);
            if (invoiceDate > tdsDate) {
                tdsAmount = (sub * tdsPercent) / 100;
            }
        } else if (invoiceDateRaw && !tdsDateRaw) {
            tdsAmount = (sub * tdsPercent) / 100;
        }
        $('#tdsTotal').val(tdsAmount.toFixed(2));

        var pbTotal = parseFloat($('#pbTotal').val()) || 0;
        $('#ewaybill').prop('required', pbTotal > 100000);
    }

    function renderLines(lines) {
        var $body = $('#lineBody');
        $body.empty();
        if (!lines.length) {
            $body.append('<tr><td colspan="10" class="text-center text-muted">No open lines on this PO.</td></tr>');
            updateSubmitState();
            return;
        }
        var html = '';
        lines.forEach(function (line, idx) {
            html += '<tr data-line="1" data-rem1="' + line.remqty_box1 + '" data-rem2="' + line.remqty_box2 + '">';
            html += '<td>' + (idx + 1) + '</td>';
            html += '<td class="carton-product-label">' + escAttr(line.product_label || line.name);
            html += '<input type="hidden" name="pb[' + idx + '][product]" value="' + line.product_id + '" />';
            html += '<input type="hidden" class="gstslab" name="pb[' + idx + '][gstslab]" value="' + line.gstslab + '" /></td>';
            html += '<td>' + line.remqty_box1 + '</td>';
            html += '<td>' + line.remqty_box2 + '</td>';
            html += '<td><input type="number" min="0" max="' + line.remqty_box1 + '" class="form-control recv1" name="pb[' + idx + '][receiveqty_box_1]" value="0" disabled /></td>';
            html += '<td><input type="number" min="0" max="' + line.remqty_box2 + '" class="form-control recv2" name="pb[' + idx + '][receiveqty_box_2]" value="0" disabled /></td>';
            html += '<td><input type="number" step="0.01" class="form-control rate1" name="pb[' + idx + '][box1_rate]" value="' + line.box1_rate + '" readonly /></td>';
            html += '<td><input type="number" step="0.01" class="form-control rate2" name="pb[' + idx + '][box2_rate]" value="' + line.box2_rate + '" readonly /></td>';
            html += '<td><input type="number" step="0.01" class="form-control amount" name="pb[' + idx + '][amount]" readonly /></td>';
            html += '<td>' + line.gstslab + '<input type="hidden" class="gstamount" value="0" /></td>';
            html += '</tr>';
        });
        $body.html(html);
        applyLineInputsGate();
        recalcTotals();
        updateSubmitState();
    }

    $('#lineBody').on('input change', '.recv1, .recv2', recalcTotals);
    $('#freight').on('input change', recalcTotals);
    $('#roundoff').on('input change', recalcTotals);
    $('#supp_inv_date').on('change', recalcTotals);
    $('#supp_inv_no, #supp_inv_date').on('input change', function () {
        applyLineInputsGate();
        recalcTotals();
        updateSubmitState();
    });

    $('#purchaseOrder_id').on('change', function () {
        var id = $(this).val();
        if (!id) return;
        $('#submitBtn').prop('disabled', true);
        $('#lineBody').html('<tr><td colspan="10">Loading…</td></tr>');
        $.getJSON(linesUrl + '/' + id)
            .done(function (res) {
                var po = res.purchase_order || {};
                $('#supplierName').val(po.supplier_name || '');
                $('#del_date').val(po.del_date || '');
                $('#Supplier_ref').val(po.ref_supplier || '');
                $('#ordered_qty').val(po.ordered_qty || '');
                $('#po_sub_total').val(po.po_sub_total || '');
                $('#remarks').val(po.remarks || '');
                $('#supplier_tds_percent').val(po.supplier_tds_percent || 0);
                $('#supplier_tds_date').val(po.supplier_tds_date || '');
                eligibleDate = res.eligible_inward_date || null;
                setEligibilityMessage();
                renderLines(res.lines || []);
            })
            .fail(function () {
                $('#lineBody').html('<tr><td colspan="10" class="text-danger">Could not load PO lines.</td></tr>');
            });
    });

    $('#adminCartonInward').on('submit', function (e) {
        $('#lineBody tr[data-line="1"]').find('.recv1, .recv2').prop('disabled', false);
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
            var a = parseInt($(this).find('.recv1').val(), 10) || 0;
            var b = parseInt($(this).find('.recv2').val(), 10) || 0;
            if (a > 0 || b > 0) hasRecv = true;
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
