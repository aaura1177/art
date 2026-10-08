@extends('layouts.app')

@section('content')
<div class="mx-2">
    <div class="row mx-0 my-2">
        <h2>Admin inward supply — services</h2>
        <p class="text-muted mb-0">Creates service purchase bill and supplier invoice together. Hours/Count units with sub-line rollup and 2-decimal amounts.</p>
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

    @if ($purchaseOrder->isEmpty())
        <div class="alert alert-info">No open service purchase orders with remaining quantity. Create or complete a PO first.</div>
    @endif

    <form id="createPurchaseBill" method="POST" action="{{ url('/purchaseBill/service/create') }}">
        @csrf

        <div class="row mt-3">
            <div class="col-md-4">
                <label class="control-label">PO No.</label>
                <a href="{{ url('/purchaseOrder/create/service') }}" style="float:right;" target="_blank">(+New)</a>
                <select class="form-control" name="purchaseOrder_id" id="purchaseOrder_id" required @if($purchaseOrder->isEmpty()) disabled @endif>
                    <option value="" selected disabled>Select PO</option>
                    @foreach ($purchaseOrder as $po)
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
                <input type="number" class="form-control" id="total_qty" readonly />
            </div>
            <div class="col-md-4">
                <label class="control-label">Sub Total Amount</label>
                <input type="number" class="form-control" id="total_amount" readonly />
            </div>
        </div>

        <div class="row mt-3">
            <div class="col-md-4">
                <label class="control-label">Remaining Quantity</label>
                <input type="number" step="0.01" class="form-control" id="remaining_qty" readonly />
            </div>
            <div class="col-md-4">
                <label class="control-label">Remaining Amount (₹)</label>
                <input type="number" step="0.01" class="form-control" id="remaining_amount" readonly />
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
            </div>
        </div>

        <div class="row mt-3">
            <div class="col-12">
                <table class="table table-bordered table-sm">
                    <thead class="thead-light">
                        <tr>
                            <th>Product</th>
                            <th>Rem. QTY / %</th>
                            <th>Rem. Amount (₹)</th>
                            <th>Receive QTY / %</th>
                            <th>Unit</th>
                            <th>Rate (₹)</th>
                            <th>Amount (₹)</th>
                            <th>GST %</th>
                            <th>GST (₹)</th>
                            <th>Location</th>
                        </tr>
                    </thead>
                    <tbody id="productTable">
                        <tr><td colspan="10" class="text-center text-muted">Select a PO to load lines.</td></tr>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="row mt-3">
            <div class="col-md-3">
                <label class="control-label">Total Quantity</label>
                <input type="number" step="0.01" class="form-control" name="pbQty" id="pbQty" readonly />
            </div>
            <div class="col-md-3">
                <label class="control-label">Sub-Total (₹)</label>
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
                <input type="hidden" id="tdsPercent" value="0" />
                <input type="hidden" id="supplier_city" value="" />
            </div>
        </div>

        <div class="row mt-3 mb-4">
            <div class="col-md-4">
                <button type="submit" class="btn btn-primary" id="formSubmit" disabled>Save inward</button>
                <a href="{{ url('/purchaseBill/service/condition') }}" class="btn btn-secondary ms-2">Cancel</a>
            </div>
        </div>
    </form>
</div>
@endsection

@section('footer')
<script>
(function () {
    var linesUrl = "{{ url('/purchaseBill/data/service-po') }}";

    const roundMoney = (value) => {
        const n = parseFloat(value);
        return isNaN(n) ? 0 : Math.round(n * 100) / 100;
    };
    const formatMoney = (value) => roundMoney(value).toFixed(2);
    const gstFromAmount = (amount, gstRate) => roundMoney((parseFloat(amount) || 0) * (parseFloat(gstRate) || 0) / 100);
    const amountFromRateAndQty = (rate, quantity, unitValue) => {
        const r = parseFloat(rate) || 0;
        const q = parseFloat(quantity) || 0;
        return unitValue === 'Count' ? roundMoney((r * q) / 100) : roundMoney(r * q);
    };

    function escAttr(s) {
        return String(s == null ? '' : s).replace(/&/g, '&amp;').replace(/"/g, '&quot;').replace(/</g, '&lt;');
    }

    function invoiceDetailsComplete() {
        return $.trim($('#supp_inv_no').val()) !== '' && ($('#supp_inv_date').val() || '') !== '';
    }

    function applyLineInputsGate() {
        var ok = invoiceDetailsComplete();
        $('#productTable .line-input').prop('disabled', !ok);
    }

    function updateSubmitState() {
        var hasLines = $('#productTable tr[data-parent="1"]').length > 0;
        $('#formSubmit').prop('disabled', !hasLines || !invoiceDetailsComplete());
    }

    function clampSubQuantity(input) {
        const maxQty = parseFloat(input.getAttribute('max')) || 0;
        let qty = parseFloat(input.value);
        if (isNaN(qty) || qty < 0) {
            input.value = '';
            return 0;
        }
        qty = roundMoney(qty);
        if (qty > maxQty) {
            qty = maxQty;
        }
        input.value = qty > 0 ? parseFloat(qty.toFixed(2)) : '';
        return qty;
    }

    function calculateChildSum(ref, unitValue) {
        const subRow = ref.closest('tr');
        const parentTr = subRow.previousElementSibling;
        let amount = 0;
        let gstAmount = 0;
        let quantity = 0;

        subRow.querySelectorAll('.add-more-quantity').forEach(function(block) {
            amount += roundMoney(block.querySelector('.amount-ch').value);
            gstAmount += roundMoney(block.querySelector('.gstamount-ch').value);
            quantity += parseFloat(block.querySelector('.quantity-ch').value) || 0;
        });

        amount = roundMoney(amount);
        gstAmount = roundMoney(gstAmount);
        const rate = parseFloat(parentTr.querySelector('.rate').value) || 0;

        if (unitValue === 'Count') {
            parentTr.querySelector('.receiveqty').value = rate > 0 ? formatMoney((amount / rate) * 100) : '0.00';
        } else {
            parentTr.querySelector('.receiveqty').value = formatMoney(quantity);
        }

        parentTr.querySelector('.amount').value = formatMoney(amount);
        parentTr.querySelector('.gstamount').value = formatMoney(gstAmount);
        calculateTotal();
    }

    function handleQuantityChange(ref) {
        const block = ref.closest('.add-more-quantity');
        const unitValue = ref.closest('tr').previousElementSibling.querySelector('.unit-hours').value;
        const quantity = clampSubQuantity(ref);
        const rate = block.querySelector('.rate-ch').value;
        const gstrate = block.querySelector('.gstslab-ch').value;
        const amountInput = block.querySelector('.amount-ch');

        let amount = amountFromRateAndQty(rate, quantity, unitValue);
        const max = parseFloat(amountInput.getAttribute('max'));
        if (!isNaN(max) && max >= 0 && amount > max) {
            amount = max;
        }
        amountInput.value = formatMoney(amount);
        block.querySelector('.gstamount-ch').value = formatMoney(gstFromAmount(amount, gstrate));
        calculateChildSum(ref, unitValue);
    }

    function handleParentQuantity(ref) {
        const tr = ref.closest('tr');
        const unit = tr.querySelector('.unit-hours').value;
        const qty = clampSubQuantity(ref);
        const rate = tr.querySelector('.rate').value;
        const gstslab = tr.querySelector('.gstslab').value;
        const amount = amountFromRateAndQty(rate, qty, unit);
        tr.querySelector('.amount').value = formatMoney(amount);
        tr.querySelector('.gstamount').value = formatMoney(gstFromAmount(amount, gstslab));
        calculateTotal();
    }

    window.handleQuantityChange = handleQuantityChange;
    window.handleParentQuantity = handleParentQuantity;

    function calculateTotal() {
        let totQty = 0;
        let subTot = 0;
        let gst = 0;

        $('#productTable tr[data-parent="1"]').each(function () {
            const tr = $(this);
            const unit = tr.find('.unit-hours').val();
            const rq = parseFloat(tr.find('.receiveqty').val()) || 0;
            if (unit === 'Count') {
                totQty += 1;
            } else {
                totQty += rq;
            }
            subTot += roundMoney(tr.find('.amount').val());
            gst += roundMoney(tr.find('.gstamount').val());
        });

        subTot = roundMoney(subTot);
        gst = roundMoney(gst);
        const pbTotal = roundMoney(subTot + gst);
        const tdsPercent = parseFloat($('#tdsPercent').val()) || 0;

        $('#pbQty').val(formatMoney(totQty));
        $('#pbSubTotal').val(formatMoney(subTot));
        $('#pbGST').val(formatMoney(gst));
        $('#pbTotal').val(formatMoney(pbTotal));
        $('#tdsTotal').val(formatMoney((subTot * tdsPercent) / 100));

        const supplierCity = (($('#supplier_city').val() || '').trim().toUpperCase());
        const ewayThreshold = supplierCity === 'JAIPUR' ? 200000 : 100000;
        $('#ewaybill').prop('required', pbTotal > ewayThreshold);
    }

    function renderSubBlock(idx, line, sub) {
        var html = '<div class="add-more-quantity" style="margin-top:8px;display:flex;flex-wrap:wrap;gap:8px;align-items:end;">';
        html += '<input type="hidden" name="po[' + idx + '][id][]" value="' + sub.id + '" />';
        html += '<div style="flex:1;min-width:120px;"><label>Name</label>';
        html += '<input type="text" class="form-control" name="po[' + idx + '][sub_name][]" value="' + escAttr(sub.sub_name) + '" readonly /></div>';

        if (line.unit === 'Count') {
            html += '<div style="flex:1;min-width:100px;"><label>Rem. %</label>';
            html += '<input type="number" step="0.01" class="form-control" value="' + formatMoney(sub.sub_remaining_percentage) + '" readonly /></div>';
            html += '<div style="flex:1;min-width:110px;"><label>Rem. Amount (₹)</label>';
            html += '<input type="number" step="0.01" class="form-control" value="' + formatMoney(sub.sub_remaining_amount) + '" readonly /></div>';
            html += '<div style="flex:1;min-width:100px;"><label>Receive %</label>';
            html += '<input type="number" step="0.01" min="0" max="' + sub.sub_remaining_percentage + '" class="form-control quantity-ch line-input" name="po[' + idx + '][sub_percentage][]" value="" oninput="handleQuantityChange(this)" disabled /></div>';
            html += '<input type="hidden" name="po[' + idx + '][sub_quantity][]" value="0" />';
        } else {
            html += '<div style="flex:1;min-width:100px;"><label>Rem. Qty</label>';
            html += '<input type="number" step="0.01" class="form-control remqty-ch" value="' + formatMoney(sub.sub_remqty) + '" readonly /></div>';
            html += '<div style="flex:1;min-width:110px;"><label>Rem. Amount (₹)</label>';
            html += '<input type="number" step="0.01" class="form-control" value="' + formatMoney(sub.sub_remaining_amount) + '" readonly /></div>';
            html += '<div style="flex:1;min-width:100px;"><label>Receive Qty</label>';
            html += '<input type="number" step="0.01" min="0" max="' + sub.sub_remqty + '" class="form-control quantity-ch line-input" name="po[' + idx + '][sub_quantity][]" value="" oninput="handleQuantityChange(this)" disabled /></div>';
            html += '<input type="hidden" name="po[' + idx + '][sub_percentage][]" value="0" />';
        }

        html += '<div style="flex:1;min-width:90px;"><label>Rate</label>';
        html += '<input type="number" step="0.01" class="form-control rate-ch" name="po[' + idx + '][sub_rate][]" value="' + sub.sub_rate + '" readonly /></div>';
        html += '<div style="flex:1;min-width:100px;"><label>Amount</label>';
        html += '<input type="number" step="0.01" class="form-control amount-ch" name="po[' + idx + '][sub_amount][]" value="0.00" max="' + sub.sub_remaining_amount + '" readonly /></div>';
        html += '<div style="flex:1;min-width:70px;"><label>GST %</label>';
        html += '<input type="number" step="0.01" class="form-control gstslab-ch" name="po[' + idx + '][sub_gstslab][]" value="' + sub.sub_gstslab + '" readonly /></div>';
        html += '<div style="flex:1;min-width:90px;"><label>GST (₹)</label>';
        html += '<input type="number" step="0.01" class="form-control gstamount-ch" name="po[' + idx + '][sub_gstamount][]" value="0.00" readonly /></div>';
        html += '</div>';
        return html;
    }

    function renderLines(lines) {
        var $body = $('#productTable');
        $body.empty();

        if (!lines.length) {
            $body.append('<tr><td colspan="10" class="text-center text-muted">No open lines on this PO.</td></tr>');
            updateSubmitState();
            calculateTotal();
            return;
        }

        lines.forEach(function (line, idx) {
            var remQtyLabel = line.unit === 'Count'
                ? formatMoney(line.remaining_percentage) + '%'
                : formatMoney(line.remqty);
            var remAmountLabel = formatMoney(line.remaining_amount);

            var parentHtml = '<tr data-parent="1">';
            parentHtml += '<td>' + escAttr(line.name);
            parentHtml += '<input type="hidden" name="pb[' + idx + '][product]" value="' + line.product_id + '" />';
            parentHtml += '<input type="hidden" class="unit-hours" name="pb[' + idx + '][unit]" value="' + escAttr(line.unit) + '" /></td>';
            parentHtml += '<td>' + remQtyLabel + '</td>';
            parentHtml += '<td>' + remAmountLabel + '</td>';

            if (line.subs && line.subs.length) {
                if (line.unit === 'Count') {
                    parentHtml += '<td><input type="number" step="0.01" class="form-control receiveqty" name="pb[' + idx + '][remaining_percentage]" value="0.00" readonly /></td>';
                } else {
                    parentHtml += '<td><input type="number" step="0.01" class="form-control receiveqty" name="pb[' + idx + '][receiveqty]" value="0.00" readonly /></td>';
                }
            } else if (line.unit === 'Count') {
                parentHtml += '<td><input type="number" step="0.01" min="0" max="' + line.remaining_percentage + '" class="form-control receiveqty line-input" name="pb[' + idx + '][remaining_percentage]" value="" oninput="handleParentQuantity(this)" disabled /></td>';
            } else {
                parentHtml += '<td><input type="number" step="0.01" min="0" max="' + line.remqty + '" class="form-control receiveqty line-input" name="pb[' + idx + '][receiveqty]" value="" oninput="handleParentQuantity(this)" disabled /></td>';
            }

            parentHtml += '<td>' + escAttr(line.unit) + '</td>';
            parentHtml += '<td><input type="number" step="0.01" class="form-control rate" name="pb[' + idx + '][rate]" value="' + line.rate + '" readonly /></td>';
            parentHtml += '<td><input type="number" step="0.01" class="form-control amount" name="pb[' + idx + '][amount]" value="0.00" readonly /></td>';
            parentHtml += '<td><input type="number" step="0.01" class="form-control gstslab" name="pb[' + idx + '][gstslab]" value="' + line.gstslab + '" readonly /></td>';
            parentHtml += '<td><input type="number" step="0.01" class="form-control gstamount" name="pb[' + idx + '][gstamount]" value="0.00" readonly /></td>';
            parentHtml += '<td><input type="text" class="form-control line-input" name="pb[' + idx + '][location]" value="" disabled /></td>';
            parentHtml += '</tr>';

            $body.append(parentHtml);

            if (line.subs && line.subs.length) {
                var subHtml = '<tr><td colspan="10">';
                line.subs.forEach(function (sub) {
                    subHtml += renderSubBlock(idx, line, sub);
                });
                subHtml += '</td></tr>';
                $body.append(subHtml);
            }
        });

        applyLineInputsGate();
        calculateTotal();
        updateSubmitState();
    }

    $('#freight').on('input', calculateTotal);
    $('#supp_inv_no, #supp_inv_date').on('input change', function () {
        applyLineInputsGate();
        updateSubmitState();
    });

    $('#purchaseOrder_id').on('change', function () {
        var id = $(this).val();
        if (!id) return;
        $('#formSubmit').prop('disabled', true);
        $('#productTable').html('<tr><td colspan="10">Loading…</td></tr>');
        $.getJSON(linesUrl + '/' + id)
            .done(function (res) {
                var po = res.purchase_order || {};
                $('#supplierName').val(po.supplier_name || '');
                $('#del_date').val(po.del_date || '');
                $('#Supplier_ref').val(po.ref_supplier || '');
                $('#total_qty').val(po.ordered_qty || '');
                $('#total_amount').val(po.po_sub_total || '');
                $('#remaining_qty').val(po.remaining_qty != null ? formatMoney(po.remaining_qty) : '');
                $('#remaining_amount').val(po.remaining_amount != null ? formatMoney(po.remaining_amount) : '');
                $('#remarks').val(po.remarks || '');
                $('#supplier_city').val(po.supplier_city || '');
                $('#tdsPercent').val(po.supplier_tds_percent || 0);
                renderLines(res.lines || []);
            })
            .fail(function () {
                $('#productTable').html('<tr><td colspan="10" class="text-danger">Could not load PO lines.</td></tr>');
            });
    });

    $('#createPurchaseBill').on('submit', function (e) {
        $('.line-input').prop('disabled', false);

        if (!invoiceDetailsComplete()) {
            e.preventDefault();
            applyLineInputsGate();
            alert('Please enter Supplier Invoice No. and Supplier Invoice Date first.');
            return;
        }

        var hasReceipt = false;
        $('#productTable tr[data-parent="1"]').each(function () {
            var amount = parseFloat($(this).find('.amount').val()) || 0;
            if (amount > 0) hasReceipt = true;
        });

        if (!hasReceipt) {
            e.preventDefault();
            alert('Enter receive quantity or percentage on at least one line.');
            return;
        }

        var qtyExceeded = false;
        $('.add-more-quantity').each(function () {
            var block = $(this);
            var unit = block.closest('tr').prev().find('.unit-hours').val();
            var maxQty = unit === 'Count'
                ? parseFloat(block.find('[name*="sub_percentage"]').attr('max')) || 0
                : parseFloat(block.find('.quantity-ch').attr('max')) || 0;
            var qty = parseFloat(block.find('.quantity-ch').val()) || 0;
            if (qty > maxQty) {
                qtyExceeded = true;
                return false;
            }
        });

        if (qtyExceeded) {
            e.preventDefault();
            alert('Receive qty/percentage cannot exceed remaining.');
            return;
        }

        $('#formSubmit').prop('disabled', true);
    });
})();
</script>
@endsection
