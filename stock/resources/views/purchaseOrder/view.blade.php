@extends('layouts.app')

@section('content')

    <div class="mx-2">
        <div class="row mx-0 my-2">
            <h2>Update PO</h2>
        </div>

        <form id="myForm" method="POST" action="{{ url('/purchaseOrder/view/' . $purchaseOrder->id) }}">
            @csrf

            <!-- Form Starts -->
            <div class="form-group">

                <!-- first row -->
                <div class="row mt-3">
                    <div class="col-4">
                        <label class="control-label">{{ __('PO No.') }}</label>
                        <input type="text" class="form-control" name="pono" required="required"
                            value="{{ $purchaseOrder->pono }}" />
                    </div>
                    <div class="col-4">
                        <label class="control-label">{{ __('Supplier') }}</label><a href="{{ url('/supplier/create') }}"
                            style="float: right;" target="_blank"> (+New)</a>
                        <select type="text" class="selectpicker" data-live-search="true" name="supplier_id"
                            required="required" id='supplier_id'>
                            <option value="" selected disabled>Select Supplier</option>
                            @if (isset($supplier))
                                @foreach ($supplier as $key => $supplier)
                                    <option value="{{ $supplier->id }}"
                                        {{ $purchaseOrder->supplier_id == $supplier->id ? 'selected' : '' }}>
                                        {{ $supplier->c_name }}
                                    </option>
                                @endforeach
                            @endif
                        </select>
                    </div>
                </div>

                <div class="row mt-3">
                    <div class="col-4">
                        <label class="control-label">{{ __('Date of PO') }}</label>
                        <input type="date" class="form-control" name="podate" value="{{ $purchaseOrder->podate }}"
                            required="required" />
                    </div>
                    <div class="col-4">
                        <label class="control-label">{{ __('Delivery Date') }}</label>
                        <input type="date" class="form-control" name="del_date" value="{{ $purchaseOrder->del_date }}"
                            required="required" />
                    </div>
                </div>

                <!-- second row -->
                <div class="row mt-3">
                    <div class="col-4">
                        <label class="control-label">{{ __('Supplier Ref. No.') }}</label>
                        <input type="text" class="form-control toUpperCase" name="ref_supplier"
                            value="{{ $purchaseOrder->ref_supplier }}" required="required" />
                    </div>
                    <div class="col-4">
                        <label class="control-label">{{ __('Buyer Order Number') }}</label>
                        <input type="text" class="form-control toUpperCase" name="buyer_orderno"
                            value="{{ $purchaseOrder->buyer_orderno }}" required="required" />
                    </div>
                    <div class="col-4">
                        <label class="control-label">{{ __('Address to') }}</label>
                        <select name="address_option" class="form-control">
                            <option value="2" {{ $purchaseOrder->address_option == '2' ? 'selected' : '' }}>Factory
                            </option>
                            <option value="1" {{ $purchaseOrder->address_option == '1' ? 'selected' : '' }}>Office
                            </option>
                        </select>
                    </div>
                </div>

                <!-- third row -->
                <div class="row mt-3">
                    <div class="col-4">
                        <label class="control-label">{{ __('Terms of Payment') }}</label>
                        <textarea class="form-control" name="payterms">{{ $purchaseOrder->payterms }}</textarea>
                    </div>
                    <div class="col-8">
                        <label class="control-label">{{ __('Remarks') }}</label>
                        <textarea class="form-control" name="remarks">{{ $purchaseOrder->remarks }}</textarea>
                    </div>
                </div>

                <div class="row mt-3">
                    <div class="col-4">
                        <label class="control-label">{{ __('PO Revise Date') }}</label>
                        <input type="date" class="form-control" name="po_revise_date" /> (Leave empty for today)
                    </div>
                </div>

                <!-- forth Product row -->
                <div class="row mt-5 my-3">
                    <div class="col-6">
                        <h5>Products List</h5>
                    </div>
                </div>

                <!-- Product details -->
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr id="mytable">
                                <th scope="col" style="min-width: 250px;">Product <a href="{{ url('/product/create') }}"
                                        target="_blank"> (+New)</a></th>
                                <th scope="col" style="min-width: 250px;">EAN</th>
                                <th scope="col" style="min-width: 100px;">Qty</th>
                                <th scope="col" style="min-width: 100px;">Unit</th>
                                <th scope="col" style="min-width: 100px;">Rate/Item (₹)</th>
                                <th scope="col" style="min-width: 150px;">Amount (₹)</th>
                                <th scope="col" style="min-width: 100px;">GST Slab (%)</th>
                                <th scope="col" style="min-width: 130px;">GST (₹)</th>
                                <th scope="col" style="min-width: 130px;">Discount Type</th>
                                <th scope="col" style="min-width: 130px;">Discount </th>
                                <th scope="col" style="min-width: 130px;">Priority</th>
                                <th scope="col" style="min-width: 130px;">Delivery Point</th>
                                <th scope="col" style="min-width: 130px;">Legs</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody id="productTable">
                            @if (isset($poTable))
                                @foreach ($poTable as $key => $poTable)
                                    <tr>
                                        <td id="pr{{ $poTable->product->id }}">
                                            <select type="text" class="selectpicker" data-live-search="true"
                                                data-len="{{ $key + 1 }}" name="po[{{ $key + 1 }}][product]"
                                                required="required">
                                                <option value="{{ $poTable->product->id }}">
                                                    {{ $poTable->product->code }} - {{ $poTable->product->name }}
                                                </option>
                                            </select>
                                            <input type="text" class="form-control" style="margin-top:10px;"
                                                name="po[{{ $key + 1 }}][description]"
                                                placeholder="Description(Optional)"
                                                value="{{ $poTable->description }}" />
                                        </td>
                                        <td>
                                            <input type="text" class="form-control EAN"
                                                name="po[{{ $key + 1 }}][EAN]" value="{{ $poTable->EAN }}"
                                                readonly />
                                        </td>
                                        <td id="quan{{ $key + 1 }}">
                                            <input type="number" min="1" style="width:60px;"
                                                class="form-control quantity" onchange="changePrice(this);"
                                                name="po[{{ $key + 1 }}][quantity]" data-len="{{ $key + 1 }}"
                                                value="{{ $poTable->quantity }}" />
                                            <input type="hidden" data-len="{{ $key + 1 }}"
                                                name="po[{{ $key + 1 }}][consumed]"
                                                value="{{ $poTable->quantity - $poTable->remqty }}" />
                                        </td>
                                        <td id="unit{{ $key + 1 }}">
                                            <select type="text" class="selectpicker" data-live-search="true"
                                                name="po[{{ $key + 1 }}][unit]" required="required">
                                                <option value="" disabled>Select</option>
                                                <option value="No." {{ $poTable->unit == 'No.' ? 'selected' : '' }}>No.
                                                </option>
                                                <option value="Kg." {{ $poTable->unit == 'Kg.' ? 'selected' : '' }}>Kg.
                                                </option>
                                                <option value="Lt." {{ $poTable->unit == 'Lt.' ? 'selected' : '' }}>Lt.
                                                </option>
                                                <option value="M3" {{ $poTable->unit == 'M3' ? 'selected' : '' }}>M3
                                                </option>
                                            </select>
                                        </td>
                                        <td id="rate{{ $key + 1 }}">
                                            <input type="number" class="form-control rate" min="0"
                                                step="any" onchange="changePrice(this);"
                                                name="po[{{ $key + 1 }}][rate]" data-len="{{ $key + 1 }}"
                                                value="{{ $poTable->rate }}" />
                                        </td>
                                        <td id="amount{{ $key + 1 }}">
                                            <input type="number" class="form-control amount"
                                                name="po[{{ $key + 1 }}][amount]" data-len="{{ $key + 1 }}"
                                                value="{{ $poTable->amount }}" readonly />
                                        </td>
                                        <td id="gstslab{{ $key + 1 }}">
                                            <input type="number" class="form-control gstslab" min="0"
                                                onchange="changePrice(this);" name="po[{{ $key + 1 }}][gstslab]"
                                                data-len="{{ $key + 1 }}" value="{{ $poTable->gstslab }}" />
                                        </td>
                                        <td id="gstamount{{ $key + 1 }}">
                                            <input type="number" class="form-control gstamount"
                                                name="po[{{ $key + 1 }}][gstamount]" data-len="{{ $key + 1 }}"
                                                value="{{ $poTable->gstamount }}" readonly />
                                        </td>
                                        <td id="discount_type{{ $key + 1 }}">
                                            <select class="form-control" name="po[{{ $key + 1 }}][discount_type]" onchange="changePrice(this);">
                                                <option value="Per"
                                                    {{ $poTable->discount_type == 'Per' ? 'selected' : '' }}>Per</option>
                                                <option value="Amount"
                                                    {{ $poTable->discount_type == 'Amount' ? 'selected' : '' }}>Amount
                                                </option>
                                            </select>

                                        </td>
                                        <td id="discount{{ $key + 1 }}">
                                            <input type="number" class="form-control discount"
                                                name="po[{{ $key + 1 }}][discount]" data-len="{{ $key + 1 }}"
                                                onchange="changePrice(this);" value="{{ $poTable->discount }}" />
                                        </td>
                                        <td id="priority{{ $key + 1 }}">
                                            <select class="form-control" name="po[{{ $key + 1 }}][priority]">
                                                <option value="1" {{ $poTable->priority == 1 ? 'selected' : '' }}>1
                                                </option>
                                                <option value="2" {{ $poTable->priority == 2 ? 'selected' : '' }}>2
                                                </option>
                                                <option value="3" {{ $poTable->priority == 3 ? 'selected' : '' }}>3
                                                </option>
                                                <option value="" {{ $poTable->priority == '' ? 'selected' : '' }}>None
                                                </option>
                                            </select>
                                        </td>
                                        <td id="delpoint{{ $key + 1 }}">
                                            <select class="form-control" name="po[{{ $key + 1 }}][delivery_point]">
                                                <option value="Unit 1"
                                                    {{ $poTable->delivery_point == 'Unit 1' ? 'selected' : '' }}>Unit 1
                                                </option>
                                                <option value="Unit 2"
                                                    {{ $poTable->delivery_point == 'Unit 2' ? 'selected' : '' }}>Unit 2
                                                </option>
                                                <option value="Unit 3"
                                                    {{ $poTable->delivery_point == 'Unit 3' ? 'selected' : '' }}>Unit 3
                                                </option>
                                                <option value=""
                                                    {{ $poTable->delivery_point == '' ? 'selected' : '' }}>None</option>
                                            </select>
                                        </td>
                                        <td id="legs{{ $key + 1 }}">
                                            <select class="form-control" name="po[{{ $key + 1 }}][legs]">
                                                <option value="1" {{ $poTable->legs == 1 ? 'selected' : '' }}>With Legs
                                                </option>
                                                <option value="2" {{ $poTable->legs == 2 ? 'selected' : '' }}>Without
                                                    Legs</option>
                                            </select>
                                        </td>
                                        <td>
                                            <button type="button" class="close" onclick="deleteRow(this);"
                                                data-bs-dismiss="alert" aria-label="Close"><span
                                                    aria-hidden="true">&times;</span></button>
                                        </td>
                                    </tr>
                                @endforeach
                            @endif
                        </tbody>
                    </table>
                </div>

                    <div class="col">
                        <input type="button" id="addProduct" class="btn btn-primary" value="Add Product" />
                    </div>
                </div>


                <!-- fifth row -->
                <div class="row mt-3">
                    <div class="col-4">
                        <label class="control-label">{{ __('Total GST (₹)') }}</label>
                        <input type="text" class="form-control" name="tgst" id="totalgst"
                            value="{{ $purchaseOrder->tgst }}" readonly />
                    </div>
                    <div class="col-4">
                        <label class="control-label">{{ __('Total Quantity') }}</label>
                        <input type="number" class="form-control" name="tquantity" id="tquantity"
                            value="{{ $purchaseOrder->tquantity }}" readonly />
                    </div>
                    <div class="col-4">
                        <label class="control-label">{{ __('Sub Total Amount (₹)') }}</label>
                        <input type="number" class="form-control" name="subtotalamount" id="subtotalamount"
                            value="{{ $purchaseOrder->subTotal }}" readonly />
                    </div>
                </div>


                <!-- sixth row  -->
                <div class="row mt-3">
                    <div class="col-4">
                        <label class="control-label">{{ __('Total Amount (₹)') }}</label>
                        <input type="number" class="form-control" name="tamount" id="totalamount"
                            value="{{ $purchaseOrder->tamount }}"readonly />
                    </div>
                    <div class="col-4">
                        <label class="control-label">{{ __('Total Discount (₹)') }}</label>
                        <input type="number" class="form-control" name="totaldiscount" id="totaldiscount"
                            value="{{ $purchaseOrder->totaldiscount }}" readonly />
                    </div>
                </div>

                <div class="row col-4">
                    <button id="submitBtn" type="submit" onclick="return validateSubmit();"
                        class="btn btn-primary mt-3">Update PO</button>
                </div>
            </div>
        </form>
    </div>
@endsection

@section('footer')
@include('purchaseOrder.partials.po_supplier_limit_check', [
    'poKind' => 'furniture',
    'excludeFurniturePoId' => $purchaseOrder->id,
    'excludeConsumablePoId' => null,
])
<script type="text/javascript">
  // ===== Setup =====
  var products = [];
  var productsReady = $.Deferred();
  var addProductWaiting = false;
  $(document).ready(function () {
    $.ajax({ url: "{{ url('/purchaseOrder/data') }}", method: 'GET' })
      .done(function (data) { if (data) { products = data.product || []; } })
      .always(function () { productsReady.resolve(); });

    // Normalize pre-rendered rows: add data-len, hidden amountdiscount if missing, and recalc
    $('#productTable tr').each(function(idx) {
      const len = idx + 1;
      const $tr = $(this).attr('data-len', len);
      // add hidden amountdiscount if missing (keeps totals code consistent)
      if ($tr.find('.amountdiscount').length === 0) {
        $('<input type="hidden" class="form-control amountdiscount" name="po['+len+'][amountdiscount]" value="0.00" readonly/>')
          .appendTo($tr.find('#amount'+len));
      }
    });

    // Initial totals for existing PO rows
    $('#productTable tr').each(function(){ changePrice(this); });
  });
  // ===== Totals helper =====
  function recalcTotals() {
    let totQty = 0, subAmount = 0, gstTotal = 0, totalRowDiscount = 0;

    $('#productTable .quantity').each(function(){ totQty += parseFloat($(this).val()) || 0; });
    $('#productTable .amountdiscount').each(function(){ subAmount += parseFloat($(this).val()) || 0; });
    $('#productTable .gstamount').each(function(){ gstTotal += parseFloat($(this).val()) || 0; });

    // Compute total discount from rows (amount - amountAfterDisc)
    $('#productTable tr').each(function(){
      const $r = $(this);
      const len = $r.data('len');
      const qty = parseFloat($r.find('#quan'+len+' .quantity').val()) || 0;
      const rate = parseFloat($r.find('#rate'+len+' .rate, #rate'+len+' input').val()) || 0;
      const base = qty * rate;
      const afterDisc = parseFloat($r.find('.amountdiscount').val()) || 0;
      totalRowDiscount += Math.max(0, base - afterDisc);
    });

    $("#tquantity").val(totQty);
    $("#subtotalamount").val(subAmount.toFixed(2));
    $("#totalgst").val(gstTotal.toFixed(2));
    $("#totaldiscount").val(totalRowDiscount.toFixed(2));
    $("#totalamount").val((subAmount + gstTotal).toFixed(2));
    if (window.schedulePoLimitCheck) {
      window.schedulePoLimitCheck();
    }
  }

  // ===== Row pricing (GST on pre-discount base) =====
  function changePrice(ref) {
    if (!ref) { recalcTotals(); return; }

    const $row = $(ref).closest('tr');
    const len = $row.data('len');

    const quantity = parseFloat($row.find('#quan'+len+' .quantity').val()) || 0;
    const rate     = parseFloat($row.find('#rate'+len+' .rate, #rate'+len+' input').val()) || 0;
    const baseAmt  = quantity * rate;

    const gstslab      = parseFloat($row.find('#gstslab'+len+' .gstslab, #gstslab'+len+' input').val()) || 0;
    const discountVal  = parseFloat($row.find('#discount'+len+' .discount').val()) || 0;
    const discountType = ($row.find('#discount_type'+len+' select').val() || '').trim();

    let rowDiscount = 0;
    if (discountType === 'Per')      rowDiscount = (baseAmt * discountVal) / 100;
    else if (discountType === 'Amount') rowDiscount = discountVal;
    rowDiscount = Math.max(0, Math.min(rowDiscount, baseAmt));

    const amountAfterDiscount = baseAmt - rowDiscount;

    // GST computed WITHOUT discount
    const gst = (amountAfterDiscount * gstslab) / 100;

    // Update row fields
    $row.find('#amount'+len+' .amount').val(baseAmt.toFixed(2));
    $row.find('.amountdiscount').val(amountAfterDiscount.toFixed(2));
    $row.find('#gstamount'+len+' .gstamount').val(gst.toFixed(2));

    recalcTotals();
  }

  // ===== Product select: fill EAN/GST slab & supplier rate =====
  function changeHSN(ref) {
    const $sel = $(ref);
    const $row = $sel.closest('tr');
    const len  = $row.data('len');
    const id   = $sel.val();

    // Prevent duplicate product rows (use first cell id=pr{productId})
    if ($('#pr'+id).length) {
      alert('Product already added');
      $sel.prop('selectedIndex', 0).trigger('change');
      $sel.parent().attr('id','pr');
      return;
    }
    $sel.parent().attr('id','pr'+id);

    const prod = (products || []).find(p => String(p.id) === String(id)) || {};
    const EAN  = prod.EAN ?? prod.ean ?? prod.barcode ?? '';
    const GST  = parseFloat(prod.gstslab ?? prod.gst ?? prod.gstPercent ?? 0) || 0;

    $row.find('#ean'+len+' input, .EAN').val(EAN);
    $row.find('#gstslab'+len+' input').val(GST);

    // Optional: load supplier-specific rate if api exists
    const supplier_id = $('#supplier_id').val();
    $.ajax({
      url: "{{ url('/purchaseOrder/spdata') }}",
      method: 'GET',
      data: { supplier_id, product_id: id }
    }).done(function (data) {
      if (data && data.sp) {
        $row.find('#rate'+len+' input').val(data.sp.rate || 0);
      }
      changePrice(ref);
    }).fail(function () {
      changePrice(ref);
    });
  }

  // ===== Delete row =====
  function deleteRow(ref) {
    $(ref).closest('tr').remove();
    recalcTotals();
  }

  // Expose for inline handlers already in your markup
  window.changePrice = changePrice;
  window.changeHSN   = changeHSN;
  window.deleteRow   = deleteRow;

  // ===== Add new product row =====
  $("#addProduct").click(function () {
    if (productsReady.state() === 'pending') {
      if (addProductWaiting) return;
      addProductWaiting = true;
      var $btn = $(this).prop('disabled', true);
      productsReady.done(function () {
        addProductWaiting = false;
        $btn.prop('disabled', false);
        addProductRow();
      });
      return;
    }
    addProductRow();
  });

  function addProductRow() {
    // refresh counters based on current rows
    var r = $('#productTable tr').length + 1;

    var options = '<option value="" selected disabled>-- SELECT PRODUCT --</option>';
    (products || []).forEach(function(p){
      options += '<option value="'+ p.id +'">'+ (p.code ? (p.code+' - ') : '') + p.name +'</option>';
    });

    var html  = '';
    html += '<tr data-len="'+r+'">';
    html += '  <td id="pr">';
     html += '    <select type="text" class="selectpicker" data-live-search="true" data-container="body" onchange="changeHSN(this);" name="po['+r+'][product]" required>';
    html +=        options;
    html += '    </select>';
    html += '    <input type="text" class="form-control" style="margin-top:10px;" name="po['+r+'][description]" placeholder="Description(Optional)"/>';
    html += '  </td>';

    html += '  <td id="ean'+r+'"><input type="text" class="form-control EAN" name="po['+r+'][EAN]" readonly/></td>';

    html += '  <td id="quan'+r+'"><input type="number" min="1" class="form-control quantity" name="po['+r+'][quantity]" value="1" onchange="changePrice(this);" /></td></td><input type="hidden" data-len="' + r + '" name="po[' +
                r + '][consumed]" value="" /></td>';

     html += '  <td><select class="selectpicker" data-live-search="true" data-container="body" name="po['+r+'][unit]" onchange="changePrice(this);">';
    html += '      <option value="No.">No.</option><option value="Kg.">Kg.</option><option value="Lt.">Lt.</option><option value="M3">M3</option>';
    html += '    </select></td>';

    html += '  <td id="rate'+r+'"><input type="number" min="0" step="any" class="form-control rate" name="po['+r+'][rate]" value="0.00" onchange="changePrice(this);" required/></td>';

    html += '  <td id="amount'+r+'"><input type="number" class="form-control amount" name="po['+r+'][amount]" value="0.00" readonly/>';
    html += '    <input type="hidden" class="form-control amountdiscount" name="po['+r+'][amountdiscount]" value="0.00" readonly/>';
    html += '  </td>';

    html += '  <td id="gstslab'+r+'"><input type="number" min="0" class="form-control gstslab" name="po['+r+'][gstslab]" value="0" onchange="changePrice(this);" required/></td>';

    html += '  <td id="gstamount'+r+'"><input type="number" class="form-control gstamount" name="po['+r+'][gstamount]" value="0.00" readonly/></td>';

    html += '  <td id="discount_type'+r+'"><select class="form-control" name="po['+r+'][discount_type]" onchange="changePrice(this);"><option value="Per">Per</option><option value="Amount">Amount</option></select></td>';

    html += '  <td id="discount'+r+'"><input type="number" class="form-control discount" name="po['+r+'][discount]" value="0" onchange="changePrice(this);" /></td>';

    html += '  <td id="priority'+r+'"><select class="form-control" name="po['+r+'][priority]"><option value="1">1</option><option value="2">2</option><option value="3">3</option><option value="">None</option></select></td>';

    html += '  <td id="delpoint'+r+'"><select class="form-control" name="po['+r+'][delivery_point]"><option value="Unit 1">Unit 1</option><option value="Unit 2">Unit 2</option><option value="Unit 3">Unit 3</option><option value="">None</option></select></td>';

    html += '  <td id="legs'+r+'"><select class="form-control" name="po['+r+'][legs]"><option value="1">With Legs</option><option value="2">Without Legs</option></select></td>';

    html += '  <td><button type="button" class="close" onclick="deleteRow(this);" aria-label="Close"><span aria-hidden="true">&times;</span></button></td>';
    html += '</tr>';

    $("#productTable").append(html);
    $('.selectpicker').selectpicker('refresh');

    // seed amounts for the appended row
    changePrice($("#productTable tr:last")[0]);
  }

  // ===== Submit validation (kept same behavior) =====
  function validateSubmit() {
    if (window._poLimitAllowSubmit) {
      window._poLimitAllowSubmit = false;
      return true;
    }
    if ($('#productTable tr').length < 1) {
      alert("No product added. Add atleast 1 product.");
      return false;
    }
    let rowIdx = 0;
    let invalid = false;
    $('#productTable select.selectpicker[name*="[product]"]').each(function(){
      rowIdx++;
      if (!$(this).val()) {
        alert("Product Row " + rowIdx + " empty. Select a product or delete the row.");
        invalid = true;
        return false;
      }
    });
    if (invalid) {
      return false;
    }
    window.validatePoSupplierLimitOnSubmit().then(function (ok) {
      if (ok) {
        $('#submitBtn').prop('disabled', true);
        window._poLimitAllowSubmit = true;
        document.getElementById('myForm').submit();
      }
    });
    return false;
  }
  window.validateSubmit = validateSubmit;
</script>
@endsection

