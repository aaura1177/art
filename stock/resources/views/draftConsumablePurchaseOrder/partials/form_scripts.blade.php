<script>
(function () {
  var productRows = 0;
  var products = [];
  var initialLines = @json($initialLinesJson ?? []);
  var selectedSupplierId = $('#supplier_id').val() || null;
  const MOQ_EPSILON = 1e-6;

  $(document).ready(function () {
    $.ajax({ url: "{{ url('/purchaseOrder/cdata') }}" }).done(function (data) {
      if (data) products = data.product || [];
      if (initialLines.length) {
        initialLines.forEach(function (line) { appendRow(line); });
      }
      recalcTotals();
      handleGstSlab();
    });
  });

  function getRowErrorElement(row) {
    var err = row.querySelector('.qty-rule-error');
    if (!err) {
      err = document.createElement('span');
      err.className = 'qty-rule-error';
      err.style.color = 'red';
      err.style.display = 'none';
      var qtyCell = row.querySelector('td[id^="quan"]');
      if (qtyCell) {
        qtyCell.appendChild(document.createElement('br'));
        qtyCell.appendChild(err);
      }
    }
    return err;
  }

  function setRowError(row, message) {
    var err = getRowErrorElement(row);
    if (!err) return;
    err.style.display = message ? 'block' : 'none';
    err.textContent = message || '';
  }

  function isMultipleOfMoq(quantity, moqQty) {
    if (!(moqQty > 0)) return false;
    var ratio = quantity / moqQty;
    return Math.abs(ratio - Math.round(ratio)) <= MOQ_EPSILON;
  }

  function qtyStepForDataType(dataType) {
    if (dataType === 'float') return '0.01';
    if (dataType === 'int') return '1';
    return null;
  }

  function applyMoqRulesToRow(row, product) {
    var quantityInput = row.querySelector('.quantity');
    if (!quantityInput) return;

    var rawIsMoq = product && product.is_moq;
    var isMoq = rawIsMoq === 1 || rawIsMoq === '1' || rawIsMoq === true || rawIsMoq === 'true';
    var moqQty = parseFloat((product && product.moq_qty) || 0);
    var dataType = quantityInput.getAttribute('data-type');

    quantityInput.removeAttribute('data-moq-enabled');
    quantityInput.removeAttribute('data-moq-qty');
    setRowError(row, '');

    if (!isMoq) {
      quantityInput.min = '1';
      var step = qtyStepForDataType(dataType);
      if (step !== null) quantityInput.step = step;
      return;
    }

    if (!(moqQty > 0)) {
      setRowError(row, 'MOQ is enabled but MOQ Qty is missing on consumable master.');
      return;
    }

    quantityInput.setAttribute('data-moq-enabled', '1');
    quantityInput.setAttribute('data-moq-qty', String(moqQty));
    quantityInput.min = String(moqQty);
    quantityInput.step = (dataType === 'int' && Number.isInteger(moqQty)) ? String(Math.max(1, Math.trunc(moqQty))) : String(moqQty);

    var qty = parseFloat(quantityInput.value || '0');
    if (!(qty >= moqQty) || !isMultipleOfMoq(qty, moqQty)) {
      quantityInput.value = String(moqQty);
    }

    setRowError(row, 'MOQ ' + moqQty + ': allowed ' + moqQty + ', ' + (moqQty * 2) + ', ' + (moqQty * 3) + '...');
  }

  function validateMoqRowsBeforeSubmit() {
    var rows = document.querySelectorAll('#productTable tr');
    for (var i = 0; i < rows.length; i++) {
      var row = rows[i];
      var qtyInput = row.querySelector('.quantity');
      if (!qtyInput) continue;

      if (qtyInput.getAttribute('data-moq-enabled') !== '1') continue;

      var moqQty = parseFloat(qtyInput.getAttribute('data-moq-qty') || '0');
      var qty = parseFloat(qtyInput.value || '0');
      if (!(moqQty > 0)) {
        return 'Row ' + (i + 1) + ': MOQ is enabled but MOQ Qty is missing on consumable master.';
      }
      if (qty + MOQ_EPSILON < moqQty) {
        return 'Row ' + (i + 1) + ': Qty must be at least ' + moqQty + '.';
      }
      if (!isMultipleOfMoq(qty, moqQty)) {
        return 'Row ' + (i + 1) + ': Qty must be a multiple of ' + moqQty + '.';
      }
    }
    return null;
  }

  var changeUnitName = async function (hsn) {
    if (!hsn) return null;
    var response = await fetch("{{ url('/purchaseOrder/cunitType') }}");
    var data = await response.json();
    var unittype = (data.unittype || []).find(function (item) { return String(item.id) === String(hsn); });
    return unittype ? { name: unittype.name, data_type: unittype.data_type } : null;
  };

  function recalcTotals() {
    var totQty = 0, subAmount = 0, gstTotal = 0;
    $('#productTable .quantity').each(function () { totQty += parseFloat($(this).val()) || 0; });
    $('#productTable .amount').each(function () { subAmount += parseFloat($(this).val()) || 0; });
    $('#productTable .gstamount').each(function () { gstTotal += parseFloat($(this).val()) || 0; });
    $('#tquantity').val(parseFloat(totQty.toFixed(2)));
    $('#subtotalamount').val(subAmount.toFixed(2));
    $('#totalgst').val(gstTotal.toFixed(2));
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
    var gst = (amount * gstslab) / 100;
    $row.find('#amount' + len + ' input').val(amount.toFixed(2));
    $row.find('#gstamount' + len + ' input').val(gst.toFixed(2));
    recalcTotals();
  };

  window.changeHSN = async function (ref) {
    var selected = ref.options[ref.selectedIndex];
    var hsn = selected ? selected.dataset.id : null;
    var unitInfo = await changeUnitName(hsn);
    var row = ref.closest('tr');

    if (unitInfo && row) {
      row.querySelector('.unit').value = unitInfo.name;
      var quantityInput = row.querySelector('.quantity');
      quantityInput.setAttribute('data-type', unitInfo.data_type);
      if (unitInfo.data_type === 'int') {
        quantityInput.value = Math.floor(parseFloat(quantityInput.value) || 0);
        quantityInput.step = '1';
      } else if (unitInfo.data_type === 'float') {
        quantityInput.step = '0.01';
      } else {
        quantityInput.step = '1';
      }
    } else if (row) {
      row.querySelector('.unit').value = '';
    }

    var productId = ref.value;
    var len = ref.dataset.len;
    if ($('#pr' + productId).length && !$(ref).parent().is('#pr' + productId)) {
      alert('Consumable already added');
      $(ref).prop('selectedIndex', 0).selectpicker('refresh');
      return;
    }
    $(ref).parent().attr('id', productId ? 'pr' + productId : 'pr_tmp_' + len);

    var product = (products || []).find(function (p) { return String(p.id) === String(productId); });
    var descriptionInput = row ? row.querySelector('input[name$="[description]"]') : null;
    if (descriptionInput) {
      descriptionInput.value = product && product.description ? product.description : '';
    }

    var rate = 0;
    var gstslab = 0;
    if (product) {
      gstslab = product.gst || 0;
      var supplierList = product.supplier || [];
      if (typeof supplierList === 'string') {
        try {
          var parsed = JSON.parse(supplierList);
          supplierList = Array.isArray(parsed) ? parsed : [parsed];
        } catch (e) {
          supplierList = [supplierList];
        }
      } else if (typeof supplierList === 'number') {
        supplierList = [supplierList];
      } else if (!Array.isArray(supplierList)) {
        supplierList = [supplierList];
      }
      if (supplierList.map(String).includes(String(selectedSupplierId))) {
        rate = product.rate || 0;
      }
    }

    setTimeout(function () {
      $('#rate' + len + ' input').val(rate);
      $('#gstslab' + len + ' input').val(gstslab);
      changePrice($('#rate' + len + ' input'));
      if (row) applyMoqRulesToRow(row, product);
    }, 50);
  };

  window.deleteRow = function (ref) {
    $(ref).closest('tr').remove();
    recalcTotals();
  };

  window.handleSelectSupplier = function (ref) {
    selectedSupplierId = $(ref).val();
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
    var options = '<option value="" disabled>-- SELECT CONSUMABLE --</option>';
    $.each(products, function (_, value) {
      var sel = String(selectedId) === String(value.id) ? ' selected' : '';
      options += '<option value="' + value.id + '" data-id="' + value.unit_type_id + '"' + sel + '>' + value.name + '</option>';
    });
    return options;
  }

  function appendRow(prefill) {
    productRows += 1;
    var r = productRows;
    var cid = prefill ? prefill.consumable_id : '';
    var $block = '<tr class="po-row" data-len="' + r + '">';
    $block += '<td id="' + (cid ? 'pr' + cid : 'pr_tmp_' + r) + '">';
    $block += '<select class="selectpicker" data-live-search="true" onchange="changeHSN(this);" data-len="' + r + '" name="po[' + r + '][consumable]">' + buildOptions(cid) + '</select>';
    $block += '<input type="text" class="form-control mt-2" name="po[' + r + '][description]" placeholder="Description(Optional)" value="' + (prefill && prefill.description ? String(prefill.description).replace(/"/g, '&quot;') : '') + '" />';
    $block += '</td>';
    $block += '<td id="quan' + r + '"><input type="number" step="0.01" min="1" class="form-control quantity" onchange="changePrice(this);" data-len="' + r + '" name="po[' + r + '][quantity]" value="' + (prefill ? prefill.quantity : 1) + '" oninput="restrictDecimal(this)" /><br><span class="qty-rule-error" style="color:red;display:none;"></span></td>';
    $block += '<td id="unit' + r + '"><input type="text" class="form-control unit" readonly required data-len="' + r + '" name="po[' + r + '][unit]" value="' + (prefill && prefill.unit ? prefill.unit : '') + '" /></td>';
    $block += '<td id="rate' + r + '"><input type="number" step="any" min="0" class="form-control" onchange="changePrice(this);" data-len="' + r + '" name="po[' + r + '][rate]" value="' + (prefill ? prefill.rate : '0.00') + '" required /></td>';
    $block += '<td id="amount' + r + '"><input type="number" class="form-control amount" name="po[' + r + '][amount]" readonly value="' + (prefill ? prefill.amount : '0.00') + '" /></td>';
    $block += '<td id="gstslab' + r + '"><input type="number" min="0" class="form-control" onchange="changePrice(this);" data-len="' + r + '" name="po[' + r + '][gstslab]" value="' + (prefill ? prefill.gstslab : 0) + '" /></td>';
    $block += '<td id="gstamount' + r + '"><input type="number" class="form-control gstamount" name="po[' + r + '][gstamount]" readonly value="' + (prefill ? prefill.gstamount : '0.00') + '" /></td>';
    $block += '<td><button type="button" class="close" onclick="deleteRow(this);"><span>&times;</span></button></td>';
    $block += '</tr>';
    $('#productTable').append($block);
    $('.selectpicker').selectpicker('refresh');
    handleGstSlab();
    if (prefill && cid) {
      var prod = (products || []).find(function (p) { return String(p.id) === String(cid); });
      if (prod) applyMoqRulesToRow($('#productTable tr:last')[0], prod);
      changePrice($('#productTable tr:last').find('#rate' + r + ' input'));
    }
  }

  window.restrictDecimal = function (input) {
    var err = input.parentElement.querySelector('.qty-rule-error');
    var isInt = input.getAttribute('data-type') === 'int';
    if (isInt && input.value.includes('.')) {
      if (err) { err.style.display = 'block'; err.textContent = 'Only integer values allowed!'; }
      input.value = input.value.split('.')[0];
    } else if (err && !input.getAttribute('data-moq-enabled')) {
      err.style.display = 'none';
      err.textContent = '';
    }
  };

  $('#addProduct').on('click', function () { appendRow(null); });

  $('#draftConsumablePoForm').on('submit', function (e) {
    if (typeof window.isDraftAmountConfirmed === 'function' && window.isDraftAmountConfirmed()) {
      $('#submitBtn').prop('disabled', true);
      return;
    }
    e.preventDefault();
    if ($('#productTable tr').length < 1) {
      alert('Add at least one consumable.');
      return;
    }
    var moqErr = validateMoqRowsBeforeSubmit();
    if (moqErr) {
      alert(moqErr);
      return;
    }
    var ok = true;
    $('#productTable select[name*="[consumable]"]').each(function (i) {
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
