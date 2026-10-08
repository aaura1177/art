@extends('layouts.app')

@section('content')


    <div class="mx-2">
        <div class="row mx-0 my-2">
            <h2>{{ !empty($draftPrefill) ? 'Create Main PO from Draft ' . $draftPrefill->draft_pono : (!empty($boSeries) ? 'Add BO Purchase Order' : 'Add PO') }}</h2>
        </div>

        <form id="myForm" method="POST" action="{{ url('/purchaseOrder/create') }}">
            @csrf
            @if (!empty($fromDraftId))
                <input type="hidden" name="from_draft_id" value="{{ $fromDraftId }}" />
            @endif

            <!-- Form Starts -->
            <div class="form-group">
                @if (session('error'))
                    <div class="alert alert-danger">
                        {{ session('error') }}
                    </div>
                @endif

                <!-- first row -->
                <div class="row mt-3">
                    <div class="col-4">
                        <label class="control-label">{{ __('PO No.') }}</label>
                        <input type="text" class="form-control toUpperCase" name="pono" required="required"
                            value="{{ !empty($draftPrefill) ? ($nextPono ?? 1) : (!empty($boSeries) ? 'BO/' . ((int) ($companyDetails->bopo_no ?? 0) + 1) : ($nextPono ?? 1)) }}" />
                    </div>
                    <div class="col-4">
                        <label class="control-label">{{ __('Supplier') }}</label><a href="{{ url('/supplier/create') }}"
                            style="float: right;" target="_blank"> (+New)</a>
                        <select type="text" class="selectpicker" data-live-search="true"
                            onchange="handleSelectSupplier(this)" name="supplier_id" required="required" id='supplier_id'>
                            <option value="" selected disabled>Select Supplier</option>
                            @if (isset($supplier))
                                @foreach ($supplier as $key => $supplier)
                                    <option value="{{ $supplier->id }}" id="{{ $supplier->id }}"
                                        data-gst="{{ $supplier->gst }}"
                                        {{ !empty($draftPrefill) && (int) $draftPrefill->supplier_id === (int) $supplier->id ? 'selected' : '' }}>
                                        {{ $supplier->c_name }}
                                    </option>
                                @endforeach
                            @endif
                        </select>
                        <input type="number" value="{{ !empty($draftPrefill) ? optional($draftPrefill->supplier)->gst : '' }}" id="supplier_gst" hidden>
                        <!-- <input type="text" id="supplier_gst" readonly> -->
                    </div>
                </div>
                <div class="row mt-3">
                    <div class="col-4">
                        <label class="control-label">{{ __('Date of PO') }}</label>
                        <input type="date" class="form-control" name="podate" required="required"
                            value="{{ !empty($draftPrefill) ? $draftPrefill->podate : date('Y-m-d') }}" />
                    </div>
                    <div class="col-4">
                        <label class="control-label">{{ __('Delivery Date') }}</label>
                        <input type="date" class="form-control" name="del_date" required="required"
                            value="{{ !empty($draftPrefill) ? $draftPrefill->del_date : '' }}" />
                    </div>
                </div>

                <!-- second row -->
                <div class="row mt-3">
                    <div class="col-4">
                        <label class="control-label">{{ __('Supplier Ref. No.') }}</label>
                        <input type="text" class="form-control toUpperCase" name="ref_supplier" required="required"
                            value="{{ !empty($draftPrefill) ? $draftPrefill->ref_supplier : '' }}" />
                    </div>
                    <div class="col-4">
                        <label class="control-label">{{ __('Buyer Order Number') }}</label>
                        <input type="text" class="form-control toUpperCase" name="buyer_orderno" required="required"
                            value="{{ !empty($draftPrefill) ? $draftPrefill->buyer_orderno : '' }}" />
                    </div>
                    <div class="col-4">
                        <label class="control-label">{{ __('Address to') }}</label>
                        <select name="address_option" class="form-control">
                            <option value="2" {{ empty($draftPrefill) || (string) $draftPrefill->address_option === '2' ? 'selected' : '' }}>Factory</option>
                            <option value="1" {{ !empty($draftPrefill) && (string) $draftPrefill->address_option === '1' ? 'selected' : '' }}>Office</option>
                        </select>
                    </div>
                </div>
                <div class="row mt-3">
                    <div class="col-4">
                        <div class="form-check mt-4">
                            <input class="form-check-input" type="checkbox" name="is_wholesale" id="is_wholesale" value="1"
                                {{ old('is_wholesale') ? 'checked' : '' }} />
                            <label class="form-check-label" for="is_wholesale">
                                Is Wholesale
                            </label>
                            <small class="d-block text-muted">Tick if this PO belongs to Wholesale PO Management (links by Buyer Order Number).</small>
                        </div>
                    </div>
                </div>

                <!-- third row -->
                <div class="row mt-3">
                    <div class="col-4">
                        <label class="control-label">{{ __('Terms of Payment') }}</label>
                        <select class="form-control" name="payterms">
                            @php $payterms = !empty($draftPrefill) ? $draftPrefill->payterms : '30-45 Days'; @endphp
                            <option value="30-45 Days" {{ $payterms === '30-45 Days' ? 'selected' : '' }}>30-45 Days</option>
                            <option value="30 Days" {{ $payterms === '30 Days' ? 'selected' : '' }}>30 Days</option>
                            <option value="45 Days" {{ $payterms === '45 Days' ? 'selected' : '' }}>45 Days</option>
                        </select>
                    </div>
                    <div class="col-8">
                        <label class="control-label">{{ __('Remarks') }}</label>
                        <textarea class="form-control" name="remarks">{{ !empty($draftPrefill) ? $draftPrefill->remarks : "1. Wood must be seasoned & chemically treated.\n2. Timber MUST be sourced from regulated & legal plantations only." }}</textarea>
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

                        </tbody>
                    </table>
                </div>

                <div class="row mt-2">
                    <div class="col">
                        <input type="button" id="addProduct" class="btn btn-primary" value="Add Product" />
                    </div>
                </div>

                <!-- fifth row -->
                <div class="row mt-3">
                    <div class="col-4">
                        <label class="control-label">{{ __('Total GST (₹)') }}</label>
                        <input type="text" class="form-control" name="tgst" id="totalgst" readonly />
                    </div>
                    <div class="col-4">
                        <label class="control-label">{{ __('Total Quantity') }}</label>
                        <input type="number" class="form-control" name="tquantity" id="tquantity" readonly />
                    </div>
                    <div class="col-4">
                        <label class="control-label">{{ __('Sub Total Amount (₹)') }}</label>
                        <input type="number" class="form-control" name="subtotalamount" id="subtotalamount" readonly />
                    </div>
                </div>


                <!-- sixth row  -->
                <div class="row mt-3">
                    <div class="col-4">
                        <label class="control-label">{{ __('Total Amount (₹)') }}</label>
                        <input type="number" class="form-control" name="tamount" id="totalamount" readonly />
                    </div>
                    <div class="col-4">
                        <label class="control-label">{{ __('Total Discount (₹)') }}</label>
                        <input type="number" class="form-control" name="totaldiscount" id="totaldiscount" readonly />
                    </div>
                </div>

                <div class="row col-4">
                    <button id="submitBtn" type="submit" form="myForm" class="btn btn-primary mt-3">Create PO</button>
                </div>
            </div>
        </form>
    </div>
@endsection

@section('footer')
@include('purchaseOrder.partials.po_supplier_limit_check', [
    'poKind' => 'furniture',
    'excludeFurniturePoId' => null,
    'excludeConsumablePoId' => null,
])

<style>
  /* Force equal column widths between header and body */
  table.table th,
  table.table td {
      vertical-align: middle;      /* centers input vertically */
      text-align: center;          /* centers text horizontally */
      padding: 8px;                /* consistent spacing */
  }

  /* Make form controls fill their cell */
  table.table td .form-control,
  table.table td select {
      width: 100%;
      min-width: 80px;             /* prevents collapsing */
      box-sizing: border-box;
  }

  /* Prevent headers from shrinking */
  table.table th {
      white-space: nowrap;
  }

  /* Align close button properly */
  table.table td .close {
      float: none;
  }
</style>
    <!-- Script start for get data from database -->
    <script type="text/javascript">
  var productRows = 0;
  var products = [];
  var draftPrefillLines = @json($draftPrefillLines ?? []);

  function buildProductOptions(selectedId) {
    var options = '<option value="" disabled>-- SELECT PRODUCT --</option>';
    $.each(products, function (_, value) {
      var sel = String(selectedId) === String(value.id) ? ' selected' : '';
      options += '<option value="' + value.id + '"' + sel + '>' + value.code + ' - ' + value.name + '</option>';
    });
    return options;
  }

  function appendRow(prefill) {
    productRows += 1;
    var r = productRows;
    var pid = prefill ? prefill.product_id : '';
    var desc = prefill && prefill.description ? String(prefill.description).replace(/"/g, '&quot;') : '';
    var $block = '';

    $block += '<tr class="po-row" data-len="' + r + '">';
    $block += '<td id="' + (pid ? 'pr' + pid : 'pr_tmp_' + r) + '">';
    $block += '<select type="text" class="selectpicker" data-live-search="true" onchange="changeHSN(this);" data-len="' + r + '" name="po[' + r + '][product]">';
    $block += buildProductOptions(pid) + '</select>';
    $block += '<input type="text" class="form-control" style="margin-top:10px;" name="po[' + r + '][description]" placeholder="Description(Optional)" value="' + desc + '" />';
    $block += '</td>';

    $block += '<td id="ean' + r + '"><input type="text" class="form-control" name="po[' + r + '][EAN]" readonly value="' + (prefill && prefill.EAN ? prefill.EAN : '') + '" /></td>';
    $block += '<td id="quan' + r + '"><input type="number" min="1" class="form-control quantity" onchange="changePrice(this);" data-len="' + r + '" name="po[' + r + '][quantity]" value="' + (prefill ? prefill.quantity : 1) + '" /></td>';

    $block += '<td><select type="text" class="selectpicker" data-live-search="true" onchange="changePrice(this);" data-len="' + r + '" name="po[' + r + '][unit]">';
    ['No.', 'Kg.', 'Lt.', 'M3.'].forEach(function (u) {
      var sel = (prefill && prefill.unit === u) || (!prefill && u === 'No.') ? ' selected' : '';
      $block += '<option value="' + u + '"' + sel + '>' + u + '</option>';
    });
    $block += '</select></td>';

    $block += '<td id="rate' + r + '"><input type="number" step="any" min="0" class="form-control" onchange="changePrice(this);" data-len="' + r + '" name="po[' + r + '][rate]" value="' + (prefill ? prefill.rate : '0.00') + '" required /></td>';
    $block += '<td id="amount' + r + '"><input type="number" class="form-control amount" name="po[' + r + '][amount]" value="' + (prefill ? prefill.amount : '0.00') + '" readonly/></td>';
    $block += '<td id="gstslab' + r + '"><input type="number" min="0" class="form-control" onchange="changePrice(this);" data-len="' + r + '" name="po[' + r + '][gstslab]" value="' + (prefill ? prefill.gstslab : 0) + '"/></td>';
    $block += '<td id="gstamount' + r + '"><input type="number" class="form-control gstamount" name="po[' + r + '][gstamount]" value="' + (prefill ? prefill.gstamount : '0.00') + '" readonly/></td>';

    $block += '<td><select type="text" class="selectpicker" data-live-search="true" onchange="changePrice(this);" data-len="' + r + '" name="po[' + r + '][discount_type]">';
    $block += '<option value="Per"' + (prefill && prefill.discount_type === 'Per' ? ' selected' : (!prefill ? ' selected' : '')) + '>Per</option>';
    $block += '<option value="Amount"' + (prefill && prefill.discount_type === 'Amount' ? ' selected' : '') + '>Amount</option>';
    $block += '</select></td>';

    $block += '<td id="discount' + r + '"><input type="number" min="0" class="form-control discount" name="po[' + r + '][discount]" onchange="changePrice(this);" value="' + (prefill ? prefill.discount : '0.00') + '" /></td>';

    $block += '<td id="priority' + r + '"><select class="form-control" name="po[' + r + '][priority]">';
    [1, 2, 3, ''].forEach(function (p) {
      var lbl = p === '' ? 'None' : p;
      var sel = prefill && String(prefill.priority) === String(p) ? ' selected' : '';
      $block += '<option value="' + p + '"' + sel + '>' + lbl + '</option>';
    });
    $block += '</select></td>';

    $block += '<td id="delpoint' + r + '"><select class="form-control" name="po[' + r + '][delivery_point]">';
    ['Unit 1', 'Unit 2', 'Unit 3', ''].forEach(function (d) {
      var lbl = d === '' ? 'None' : d;
      var sel = prefill && prefill.delivery_point === d ? ' selected' : '';
      $block += '<option value="' + d + '"' + sel + '>' + lbl + '</option>';
    });
    $block += '</select></td>';

    $block += '<td id="legs' + r + '"><select class="form-control" name="po[' + r + '][legs]">';
    $block += '<option value="1"' + (prefill && String(prefill.legs) === '1' ? ' selected' : (!prefill ? ' selected' : '')) + '>With Legs</option>';
    $block += '<option value="2"' + (prefill && String(prefill.legs) === '2' ? ' selected' : '') + '>Without Legs</option>';
    $block += '</select></td>';

    $block += '<td><button type="button" class="close" onclick="deleteRow(this);" data-bs-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button></td>';
    $block += '<td id="amountdiscount' + r + '"><input type="hidden" class="form-control amountdiscount" name="po[' + r + '][amountdiscount]" value="0.00" readonly/></td>';
    $block += '<td id="rowDiscountAmount' + r + '"><input type="hidden" class="form-control rowDiscountAmount" name="po[' + r + '][rowDiscountAmount]" value="0.00" readonly/></td>';
    $block += '</tr>';

    $('#productTable').append($block);
    $('.selectpicker').selectpicker('refresh');
    changePrice($('#productTable tr:last')[0]);
  }

  $(document).ready(function () {
    $.ajax({ url: "{{ url('/purchaseOrder/data') }}" })
      .done(function (data) {
        if (data) products = data.product || [];
        if (draftPrefillLines.length) {
          draftPrefillLines.forEach(function (line) { appendRow(line); });
        }
        recalcTotals();
        handleGstSlab();
      });
  });

  // ---------- Repricing ----------
  function changePrice(ref) {
  if (!ref) { recalcTotals(); return; }

  const $row = $(ref).closest('tr');
  const len = $(ref).data('len') ?? $row.data('len');

  const quantity = parseFloat($row.find('#quan' + len + ' input').val()) || 0;
  const rate     = parseFloat($row.find('#rate' + len + ' input').val()) || 0;
  const amount   = quantity * rate;                       // base amount (no discount)

  const gstslab      = parseFloat($row.find('#gstslab' + len + ' input').val()) || 0;
  const discountVal  = parseFloat($row.find('#discount' + len + ' input').val()) || 0;
  const discountType = $row.find("select[name='po[" + len + "][discount_type]']").val();

  let rowDiscount = 0;
  if (discountType === 'Per') {
    rowDiscount = (amount * discountVal) / 100;
  } else if (discountType === 'Amount') {
    rowDiscount = discountVal;
  }
  rowDiscount = Math.max(0, Math.min(rowDiscount, amount));

  const amountAfterDiscount = amount - rowDiscount;

  // === KEY CHANGE: GST computed WITHOUT discount ===
  const gstBase = amountAfterDiscount;                                 // ⟵ use pre-discount amount
  const gst = (gstBase * gstslab) / 100;                  // ⟵ tax on full amount
  // (previously: const gst = (amountAfterDiscount * gstslab) / 100;)

  // update this row
  $row.find('#amount' + len + ' input').val(amount.toFixed(2));
  $row.find('#amountdiscount' + len + ' input').val(amountAfterDiscount.toFixed(2));
  $row.find('#rowDiscountAmount' + len + ' input').val(rowDiscount.toFixed(2));
  $row.find('#gstamount' + len + ' input').val(gst.toFixed(2));

  recalcTotals();
}

function recalcTotals() {
  let totQty = 0, subAmount = 0, gstTotal = 0, totalRowDiscount = 0;
  $('#productTable .quantity').each(function () { totQty += parseFloat($(this).val()) || 0; });
  $('#productTable .amountdiscount').each(function () { subAmount += parseFloat($(this).val()) || 0; });
  $('#productTable .gstamount').each(function () { gstTotal += parseFloat($(this).val()) || 0; });
  $('#productTable .rowDiscountAmount').each(function () { totalRowDiscount += parseFloat($(this).val()) || 0; });

  $("#tquantity").val(totQty);
  $("#subtotalamount").val(subAmount.toFixed(2));
  $("#totalgst").val(gstTotal.toFixed(2));
  $("#totaldiscount").val(totalRowDiscount.toFixed(2));
  $("#totalamount").val((subAmount + gstTotal).toFixed(2));
  if (window.schedulePoLimitCheck) {
    window.schedulePoLimitCheck();
  }
}

  // ---------- Product select → fill EAN/GST & rate (only when user picks/changes product) ----------
  function changeHSN(ref) {
    const $sel = $(ref);
    const len = $sel.data('len');
    const id  = $sel.val();

    if ($('#pr' + id).length && !$sel.parent().is('#pr' + id)) {
      alert('Product already added');
      $sel.prop('selectedIndex', 0).selectpicker('refresh');
      return;
    }

    $sel.parent().attr('id', 'pr' + id);

    const prod = (products || []).find(p => String(p.id) === String(id)) || {};
    const EAN  = prod.EAN ?? prod.ean ?? prod.barcode ?? '';
    const GST  = parseFloat(prod.gstslab ?? prod.gst ?? prod.gstPercent ?? 0) || 0;
    const existingRate = parseFloat($('#rate' + len + ' input').val()) || 0;

    $('#ean' + len + ' input').val(EAN);
    if (!$('#gstslab' + len + ' input').val() || parseFloat($('#gstslab' + len + ' input').val()) === 0) {
      $('#gstslab' + len + ' input').val(GST);
    }

    const supplier_id = $('#supplier_id').val();
    $.ajax({
      url: "{{ url('/purchaseOrder/spdata') }}",
      method: 'GET',
      data: { supplier_id: supplier_id, product_id: id }
    }).done(function (data) {
      if (existingRate <= 0 && data && data.sp) {
        $('#rate' + len + ' input').val(data.sp.rate || 0);
      }
      changePrice(ref);
    }).fail(function () {
      changePrice(ref);
    });
  }

  // ---------- Delete row ----------
  function deleteRow(ref) {
    $(ref).closest("tr").remove();
    recalcTotals(); // recompute after removal
  }

  // ---------- Submit validation ----------
  $('#myForm').on('submit', function (event) {
    if (window._poLimitAllowSubmit) {
      window._poLimitAllowSubmit = false;
      return;
    }
    if ($('#productTable tr').length < 1) {
      alert("No product added. Add atleast 1 product.");
      event.preventDefault();
      return;
    }
    let productTableRow = 0, submitFlag = 0;
    $('#productTable select.selectpicker[name*="[product]"]').each(function () {
      productTableRow++;
      if (!$(this).val()) {
        alert("Product Row " + productTableRow + " empty. Select a product or delete the row.");
        event.preventDefault();
        submitFlag++;
        return false;
      }
    });
    if (submitFlag !== 0) {
      return;
    }
    event.preventDefault();
    window.validatePoSupplierLimitOnSubmit().then(function (ok) {
      if (ok) {
        $('#submitBtn').prop('disabled', true);
        window._poLimitAllowSubmit = true;
        $('#myForm').submit();
      }
    });
  });

  // ---------- Add product row ----------
  $("#addProduct").click(function () {
    appendRow(null);
  });



        // Call this anytime supplier changes or rows are added
function handleGstSlab() {
  const gstFlag = Number($('#supplier_gst').val()) || 0; // 1 = registered? 0 = not?
  
  // Only iterate GST inputs inside the product table
  $('#productTable [id^="gstslab"]').each(function () {
    const $input = $(this).find('input').first();

    // Required if supplier has GST (keep your original rule)
    if (!Number.isNaN(gstFlag) && gstFlag !== 0) {
      $input.prop('required', true);
    } else {
      $input.prop('required', false);
    }

    // If gstFlag === 1, enforce min=1; otherwise remove min
    if (gstFlag === 1) {
      $input.attr('min', '1');
    } else {
      $input.removeAttr('min');
    }
  });
}

// Hook on supplier select
// <select id="supplier_id" onchange="handleSelectSupplier(this)">...</select>
function handleSelectSupplier(ref) {
  const $sel = $(ref);
  const gstFlag = Number($sel.find('option:selected').data('gst')) || 0;

  // Store for later (used by handleGstSlab)
  $('#supplier_gst').val(gstFlag);

  // Adjust GST field requirements/min based on supplier GST flag
  handleGstSlab();

  // Optional: if supplier is NOT GST registered, zero GST on all lines and recalc totals
  if (gstFlag === 0) {
    $('#productTable [id^="gstslab"] input').each(function () {
      $(this).val(0);
    });
    // Recalculate totals once per row (safe & quick)
    $('#productTable tr').each(function () { changePrice(this); });
  }
}

        // function changeDetailsMore(ele) {
        //     let row = $(ele).data("len");

        //     // Row values
        //     let qty = parseFloat($("input[name='po[" + row + "][quantity]']").val()) || 0;
        //     let rate = parseFloat($("input[name='po[" + row + "][rate]']").val()) || 0;
        //     let gst = parseFloat($("input[name='po[" + row + "][gstslab]']").val()) || 0;
        //     let discount = parseFloat($("input[name='po[" + row + "][discount]']").val()) || 0;
        //     let discountType = $("select[name='po[" + row + "][discount_type]']").val();

        //     let $discountInput = $("input[name='po[" + row + "][discount]']");

        //     let baseAmount = qty * rate; // before discount
        //     let discountAmount = 0;

        //     // Discount calculation
        //     if (discountType === "Per") {
        //         discountAmount = (baseAmount * discount) / 100;
        //         $discountInput.val(discount).attr("max", 100);

        //     } else if (discountType === "Amount") {
        //         discountAmount = discount;
        //         $discountInput.val(discount).removeAttr("max");
        //     }

        //     let amountAfterDiscount = baseAmount - discountAmount;

        //     // GST calculation
        //     let gstAmount = (amountAfterDiscount * gst) / 100;

        //     // Update row fields
        //     $("input[name='po[" + row + "][amount]']").val(amountAfterDiscount.toFixed(2));
        //     $("input[name='po[" + row + "][gstamount]']").val(gstAmount.toFixed(2));

        //     // Update total discount
        //     calculateTotalDiscountMore();

        // }

        // // Calculate total discount for all rows
        // function calculateTotalDiscountMore() {
        //     let totalDiscount = 0;

        //     $("#productTable tr").each(function() {
        //         let row = $(this).find(".discount").closest("td").attr("id");
        //         if (!row) return;

        //         let rowIndex = row.replace("discount", "");
        //         let qty = parseFloat($("input[name='po[" + rowIndex + "][quantity]']").val()) || 0;
        //         let rate = parseFloat($("input[name='po[" + rowIndex + "][rate]']").val()) || 0;
        //         let discount = parseFloat($("input[name='po[" + rowIndex + "][discount]']").val()) || 0;
        //         let discountType = $("select[name='po[" + rowIndex + "][discount_type]']").val();

        //         let baseAmount = qty * rate;

        //         if (discountType === "Per") {
        //             totalDiscount += (baseAmount * discount) / 100;
        //         } else if (discountType === "Amount") {
        //             totalDiscount += discount;
        //         }
        //     });

        //     $("#totaldiscount").val(totalDiscount.toFixed(2));

        //      changePrice();
        // }
    </script>
@endsection