@extends('layouts.app')

@section('content')
<div class="mx-2">
    <div class="row mx-0 my-2">
        <h2>Edit admin inward — consumables (delta mode)</h2>
        <p class="text-muted mb-0">Select PO, loads latest linked SI/PB, and appends positive delta qty only.</p>
    </div>

    @if (session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif
    @if (session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    @if ($errors->any())
        <div class="alert alert-danger">
            <ul class="mb-0">@foreach ($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
        </div>
    @endif

    <form id="editConsumableInward" method="POST" action="{{ url('/purchaseBill/admin/consumable/edit') }}">
        @csrf

        <div class="row mt-3">
            <div class="col-md-4">
                <label class="control-label">PO No.</label>
                <select class="form-control" name="purchaseOrder_id" id="purchaseOrder_id" required>
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
                <label class="control-label">PO Sub Total</label>
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
                <label class="control-label">Latest Supplier Invoice No.</label>
                <input type="text" class="form-control" id="supp_inv_no" readonly />
            </div>
            <div class="col-md-4">
                <label class="control-label">Latest Supplier Invoice Date</label>
                <input type="date" class="form-control" id="supp_inv_date" readonly />
            </div>
            <div class="col-md-4">
                <label class="control-label">Latest E-Way Bill No.</label>
                <input type="text" class="form-control" id="ewaybill" readonly />
            </div>
        </div>

        <div class="row mt-3">
            <div class="col-12">
                <table class="table table-bordered table-sm">
                    <thead class="thead-light">
                        <tr>
                            <th>#</th>
                            <th>Item</th>
                            <th>Unit</th>
                            <th>Ordered</th>
                            <th>Already inwarded</th>
                            <th>Remaining</th>
                            <th>Delta receive</th>
                            <th>Rate</th>
                            <th>Amount</th>
                            <th>GST %</th>
                            <th>Location</th>
                        </tr>
                    </thead>
                    <tbody id="lineBody">
                        <tr><td colspan="11" class="text-center text-muted">Select a PO to load data.</td></tr>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="row mt-3">
            <div class="col-md-3">
                <label class="control-label">Delta Total Qty</label>
                <input type="number" class="form-control" id="delta_qty" readonly />
            </div>
            <div class="col-md-3">
                <label class="control-label">Delta Sub-total</label>
                <input type="number" class="form-control" id="delta_sub" readonly />
            </div>
            <div class="col-md-3">
                <label class="control-label">Delta GST</label>
                <input type="number" class="form-control" id="delta_gst" readonly />
            </div>
            <div class="col-md-3">
                <label class="control-label">Delta Total</label>
                <input type="number" class="form-control" id="delta_total" readonly />
            </div>
        </div>

        <div class="row mt-3 mb-4">
            <div class="col-md-4">
                <button type="submit" class="btn btn-primary" id="submitBtn" disabled>Update inward (append delta)</button>
            </div>
        </div>
    </form>
</div>
@endsection

@section('footer')
<script>
(function () {
    var dataUrl = "{{ url('/purchaseBill/admin/data/consumable-edit-po') }}";

    function recalcRow($tr) {
        var dtype = $tr.data('dtype') || 'int';
        var rem = parseFloat($tr.data('remaining')) || 0;
        var $recv = $tr.find('.recv');
        var rq = parseFloat($recv.val()) || 0;
        if (dtype === 'int') {
            rq = Math.floor(rq);
        }
        if (rq < 0) rq = 0;
        if (rq > rem) rq = rem;
        $recv.val(dtype === 'int' ? rq : rq.toFixed(2));

        var rate = parseFloat($tr.find('.rate').val()) || 0;
        var gst = parseFloat($tr.find('.gstslab').val()) || 0;
        var amount = rq * rate;
        $tr.find('.amount').val(amount.toFixed(2));
        $tr.find('.gstamount').val(((amount * gst) / 100).toFixed(2));
    }

    function recalcTotals() {
        var qty = 0, sub = 0, gst = 0;
        $('#lineBody tr[data-line="1"]').each(function () {
            var $tr = $(this);
            recalcRow($tr);
            qty += parseFloat($tr.find('.recv').val()) || 0;
            sub += parseFloat($tr.find('.amount').val()) || 0;
            gst += parseFloat($tr.find('.gstamount').val()) || 0;
        });
        $('#delta_qty').val(qty.toFixed(2));
        $('#delta_sub').val(sub.toFixed(2));
        $('#delta_gst').val(gst.toFixed(2));
        $('#delta_total').val((sub + gst).toFixed(2));
        $('#submitBtn').prop('disabled', qty <= 0);
    }

    function renderLines(lines) {
        var $body = $('#lineBody');
        $body.empty();
        if (!lines.length) {
            $body.html('<tr><td colspan="11" class="text-center text-muted">No lines found for this PO.</td></tr>');
            $('#submitBtn').prop('disabled', true);
            return;
        }
        var html = '';
        lines.forEach(function (line, idx) {
            var step = (line.data_type === 'int') ? '1' : '0.01';
            html += '<tr data-line="1" data-dtype="' + (line.data_type || 'int') + '" data-remaining="' + line.remaining_qty + '">';
            html += '<td>' + (idx + 1) + '</td>';
            html += '<td>' + (line.name || '') + (line.sku ? ' (' + line.sku + ')' : '') + '<input type="hidden" name="pb[' + idx + '][product]" value="' + line.consumable_id + '" /></td>';
            html += '<td>' + (line.unit || '') + '<input type="hidden" name="pb[' + idx + '][unit]" value="' + (line.unit || '') + '" /></td>';
            html += '<td>' + line.ordered_qty + '</td>';
            html += '<td>' + line.already_received_qty + '</td>';
            html += '<td>' + line.remaining_qty + '</td>';
            html += '<td><input type="number" class="form-control recv" step="' + step + '" min="0" max="' + line.remaining_qty + '" name="pb[' + idx + '][receiveqty]" value="0" /></td>';
            html += '<td><input type="number" class="form-control rate" step="0.01" name="pb[' + idx + '][rate]" value="' + line.rate + '" readonly /></td>';
            html += '<td><input type="number" class="form-control amount" step="0.01" name="pb[' + idx + '][amount]" readonly /></td>';
            html += '<td>' + line.gstslab + '<input type="hidden" class="gstslab" name="pb[' + idx + '][gstslab]" value="' + line.gstslab + '" /><input type="hidden" class="gstamount" /></td>';
            html += '<td><input type="text" class="form-control" name="pb[' + idx + '][location]" value="" /></td>';
            html += '</tr>';
        });
        $body.html(html);
        recalcTotals();
    }

    $('#purchaseOrder_id').on('change', function () {
        var id = $(this).val();
        if (!id) return;
        $('#lineBody').html('<tr><td colspan="11">Loading…</td></tr>');
        $.getJSON(dataUrl + '/' + id).done(function (res) {
            var po = res.purchase_order || {};
            var lb = res.latest_bill || null;

            $('#supplierName').val(po.supplier_name || '');
            $('#del_date').val(po.del_date || '');
            $('#Supplier_ref').val(po.ref_supplier || '');
            $('#ordered_qty').val(po.ordered_qty || 0);
            $('#po_sub_total').val(po.po_sub_total || 0);
            $('#remarks').val(po.remarks || '');

            if (lb) {
                $('#supp_inv_no').val(lb.supp_inv_no || '');
                $('#supp_inv_date').val(lb.supp_inv_date || '');
                $('#ewaybill').val(lb.ewaybill || '');
                renderLines(res.lines || []);
            } else {
                $('#supp_inv_no').val('');
                $('#supp_inv_date').val('');
                $('#ewaybill').val('');
                $('#lineBody').html('<tr><td colspan="11" class="text-danger">No existing SI/PB found for this PO. Nothing to edit in delta mode.</td></tr>');
                $('#submitBtn').prop('disabled', true);
            }
        }).fail(function () {
            $('#lineBody').html('<tr><td colspan="11" class="text-danger">Could not load PO data.</td></tr>');
            $('#submitBtn').prop('disabled', true);
        });
    });

    $('#lineBody').on('input change', '.recv', recalcTotals);

    $('#editConsumableInward').on('submit', function (e) {
        var ok = false;
        $('#lineBody tr[data-line="1"]').each(function () {
            if ((parseFloat($(this).find('.recv').val()) || 0) > 0) {
                ok = true;
            }
        });
        if (!ok) {
            e.preventDefault();
            alert('Enter positive delta qty on at least one line.');
            return;
        }
        $('#submitBtn').prop('disabled', true);
    });
})();
</script>
@endsection

