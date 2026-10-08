<script>
(function () {
  var productRows = 0;
  var products = [];
  var initialLines = @json($initialLinesJson ?? []);

  $(document).ready(function () {
    $.ajax({ url: "{{ url('/purchaseOrder/data') }}" }).done(function (data) {
      if (data) products = data.product || [];
      if (initialLines.length) {
        initialLines.forEach(function (line) { appendRow(line); });
      }
      recalcTotals();
      handleGstSlab();
    });
  });

  function recalcTotals() {
    var totQty = 0, subAmount = 0, gstTotal = 0, totalRowDiscount = 0;
    $('#productTable .quantity').each(function () { totQty += parseFloat($(this).val()) || 0; });
    $('#productTable .amountdiscount').each(function () { subAmount += parseFloat($(this).val()) || 0; });
    $('#productTable .gstamount').each(function () { gstTotal += parseFloat($(this).val()) || 0; });
    $('#productTable .rowDiscountAmount').each(function () { totalRowDiscount += parseFloat($(this).val()) || 0; });
    $('#tquantity').val(totQty);
    $('#subtotalamount').val(subAmount.toFixed(2));
    $('#totalgst').val(gstTotal.toFixed(2));
    $('#totaldiscount').val(totalRowDiscount.toFixed(2));
    $('#totalamount').val((subAmount + gstTotal).toFixed(2));
  }

  window.changePrice = function (ref) {
    if (!ref) { recalcTotals(); return; }
    var $row = $(ref).closest('tr');
    var len = $(ref).data('len') ?? $row.data('len');
    var quantity = parseFloat($row.find('#quan' + len + ' input').val()) || 0;
    var rate = parseFloat($row.find('#rate' + len + ' input').val()) || 0;
    var amount = quantity * rate;
    var gstslab = parseFloat($row.find('#gstslab' + len + ' input').val()) || 0;
    var discountVal = parseFloat($row.find('#discount' + len + ' input').val()) || 0;
    var discountType = $row.find("select[name='po[" + len + "][discount_type]']").val();
    var rowDiscount = 0;
    if (discountType === 'Per') rowDiscount = (amount * discountVal) / 100;
    else if (discountType === 'Amount') rowDiscount = discountVal;
    rowDiscount = Math.max(0, Math.min(rowDiscount, amount));
    var amountAfterDiscount = amount - rowDiscount;
    var gst = (amountAfterDiscount * gstslab) / 100;
    $row.find('#amount' + len + ' input').val(amount.toFixed(2));
    $row.find('#amountdiscount' + len + ' input').val(amountAfterDiscount.toFixed(2));
    $row.find('#rowDiscountAmount' + len + ' input').val(rowDiscount.toFixed(2));
    $row.find('#gstamount' + len + ' input').val(gst.toFixed(2));
    recalcTotals();
  };

  window.changeHSN = function (ref) {
    var $sel = $(ref);
    var len = $sel.data('len');
    var id = $sel.val();
    if ($('#pr' + id).length && !$sel.parent().is('#pr' + id)) {
      alert('Product already added');
      $sel.prop('selectedIndex', 0).selectpicker('refresh');
      return;
    }
    $sel.parent().attr('id', 'pr' + id);
    var prod = (products || []).find(function (p) { return String(p.id) === String(id); }) || {};
    var EAN = prod.EAN ?? prod.ean ?? prod.barcode ?? '';
    var GST = parseFloat(prod.gstslab ?? prod.gst ?? prod.gstPercent ?? 0) || 0;
    $('#ean' + len + ' input').val(EAN);
    $('#gstslab' + len + ' input').val(GST);
    var supplier_id = $('#supplier_id').val();
    $.get("{{ url('/purchaseOrder/spdata') }}", { supplier_id: supplier_id, product_id: id }).done(function (data) {
      if (data && data.sp) $('#rate' + len + ' input').val(data.sp.rate || 0);
      changePrice(ref);
    }).fail(function () { changePrice(ref); });
  };

  window.deleteRow = function (ref) {
    $(ref).closest('tr').remove();
    recalcTotals();
  };

  window.handleSelectSupplier = function (ref) {
    var gstFlag = Number($(ref).find('option:selected').data('gst')) || 0;
    $('#supplier_gst').val(gstFlag);
    handleGstSlab();
    if (gstFlag === 0) {
      $('#productTable [id^="gstslab"] input').val(0);
      $('#productTable tr').each(function () { changePrice(this); });
    }
  };

  function handleGstSlab() {
    var gstFlag = Number($('#supplier_gst').val()) || 0;
    $('#productTable [id^="gstslab"] input').each(function () {
      if (gstFlag !== 0) $(this).prop('required', true).attr('min', '1');
      else $(this).prop('required', false).removeAttr('min');
    });
  }

  function buildOptions(selectedId) {
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
    var $block = '<tr class="po-row" data-len="' + r + '">';
    $block += '<td id="' + (pid ? 'pr' + pid : 'pr_tmp_' + r) + '">';
    $block += '<select class="selectpicker" data-live-search="true" onchange="changeHSN(this);" data-len="' + r + '" name="po[' + r + '][product]">' + buildOptions(pid) + '</select>';
    $block += '<input type="text" class="form-control mt-2" name="po[' + r + '][description]" placeholder="Description(Optional)" value="' + (prefill && prefill.description ? String(prefill.description).replace(/"/g, '&quot;') : '') + '" />';
    $block += '</td>';
    $block += '<td id="ean' + r + '"><input type="text" class="form-control" name="po[' + r + '][EAN]" readonly value="' + (prefill && prefill.EAN ? prefill.EAN : '') + '" /></td>';
    $block += '<td id="quan' + r + '"><input type="number" min="1" class="form-control quantity" onchange="changePrice(this);" data-len="' + r + '" name="po[' + r + '][quantity]" value="' + (prefill ? prefill.quantity : 1) + '" /></td>';
    $block += '<td><select class="selectpicker" onchange="changePrice(this);" data-len="' + r + '" name="po[' + r + '][unit]">';
    ['No.', 'Kg.', 'Lt.', 'M3.'].forEach(function (u) {
      var sel = (prefill && prefill.unit === u) || (!prefill && u === 'No.') ? ' selected' : '';
      $block += '<option value="' + u + '"' + sel + '>' + u + '</option>';
    });
    $block += '</select></td>';
    $block += '<td id="rate' + r + '"><input type="number" step="any" min="0" class="form-control" onchange="changePrice(this);" data-len="' + r + '" name="po[' + r + '][rate]" value="' + (prefill ? prefill.rate : '0.00') + '" required /></td>';
    $block += '<td id="amount' + r + '"><input type="number" class="form-control amount" name="po[' + r + '][amount]" readonly value="' + (prefill ? prefill.amount : '0.00') + '" /></td>';
    $block += '<td id="gstslab' + r + '"><input type="number" min="0" class="form-control" onchange="changePrice(this);" data-len="' + r + '" name="po[' + r + '][gstslab]" value="' + (prefill ? prefill.gstslab : 0) + '" /></td>';
    $block += '<td id="gstamount' + r + '"><input type="number" class="form-control gstamount" name="po[' + r + '][gstamount]" readonly value="' + (prefill ? prefill.gstamount : '0.00') + '" /></td>';
    $block += '<td><select onchange="changePrice(this);" data-len="' + r + '" name="po[' + r + '][discount_type]">';
    $block += '<option value="Per"' + (prefill && prefill.discount_type === 'Per' ? ' selected' : '') + '>Per</option>';
    $block += '<option value="Amount"' + (prefill && prefill.discount_type === 'Amount' ? ' selected' : '') + '>Amount</option></select></td>';
    $block += '<td id="discount' + r + '"><input type="number" min="0" class="form-control discount" name="po[' + r + '][discount]" onchange="changePrice(this);" value="' + (prefill ? prefill.discount : '0.00') + '" /></td>';
    $block += '<td id="priority' + r + '"><select class="form-control" name="po[' + r + '][priority]">';
    [1,2,3,''].forEach(function (p) {
      var lbl = p === '' ? 'None' : p;
      var sel = prefill && String(prefill.priority) === String(p) ? ' selected' : '';
      $block += '<option value="' + p + '"' + sel + '>' + lbl + '</option>';
    });
    $block += '</select></td>';
    $block += '<td id="delpoint' + r + '"><select class="form-control" name="po[' + r + '][delivery_point]">';
    ['Unit 1','Unit 2','Unit 3',''].forEach(function (d) {
      var lbl = d === '' ? 'None' : d;
      var sel = prefill && prefill.delivery_point === d ? ' selected' : '';
      $block += '<option value="' + d + '"' + sel + '>' + lbl + '</option>';
    });
    $block += '</select></td>';
    $block += '<td id="legs' + r + '"><select class="form-control" name="po[' + r + '][legs]">';
    $block += '<option value="1"' + (prefill && String(prefill.legs) === '1' ? ' selected' : '') + '>With Legs</option>';
    $block += '<option value="2"' + (prefill && String(prefill.legs) === '2' ? ' selected' : '') + '>Without Legs</option></select></td>';
    $block += '<td><button type="button" class="close" onclick="deleteRow(this);"><span>&times;</span></button></td>';
    $block += '<td id="amountdiscount' + r + '" style="display:none;"><input type="hidden" class="form-control amountdiscount" name="po[' + r + '][amountdiscount]" /></td>';
    $block += '<td id="rowDiscountAmount' + r + '" style="display:none;"><input type="hidden" class="form-control rowDiscountAmount" name="po[' + r + '][rowDiscountAmount]" /></td>';
    $block += '</tr>';
    $('#productTable').append($block);
    $('.selectpicker').selectpicker('refresh');
    if (prefill) changePrice($('#productTable tr:last')[0]);
  }

  $('#addProduct').on('click', function () { appendRow(null); });

  $('#draftPoForm').on('submit', function (e) {
    if (typeof window.isDraftAmountConfirmed === 'function' && window.isDraftAmountConfirmed()) {
      $('#submitBtn').prop('disabled', true);
      return;
    }
    e.preventDefault();
    if ($('#productTable tr').length < 1) {
      alert('Add at least one product.');
      return;
    }
    var ok = true;
    $('#productTable select[name*="[product]"]').each(function (i) {
      if (!$(this).val()) {
        alert('Product row ' + (i + 1) + ' is empty.');
        ok = false;
        return false;
      }
    });
    if (!ok) return;
    if (typeof window.openDraftMonthlyAmountConfirm === 'function') {
      window.openDraftMonthlyAmountConfirm();
    } else {
      $('#submitBtn').prop('disabled', true);
      this.submit();
    }
  });
})();
</script>
