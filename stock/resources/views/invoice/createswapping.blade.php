@extends('layouts.app')

@section('content')
<style>
#swappingInvoiceModal table.ref-qty-modal-table { table-layout: fixed; }
#swappingInvoiceModal .ref-qty-modal-table col.col-ref-qty-check { width: 3rem; }
#swappingInvoiceModal .ref-qty-modal-table th.ref-qty-check-cell,
#swappingInvoiceModal .ref-qty-modal-table td.ref-qty-check-cell {
  width: 3rem;
  min-width: 3rem;
  max-width: 3rem;
  box-sizing: border-box;
  vertical-align: middle !important;
  text-align: center;
  padding: 0.4rem 0.35rem !important;
  overflow: hidden;
}
#swappingInvoiceModal .ref-qty-modal-table .ref-qty-checkbox {
  float: none !important;
  position: static !important;
  margin: 0 !important;
  display: inline-block !important;
  vertical-align: middle !important;
  width: 1.125rem;
  height: 1.125rem;
  cursor: pointer;
  flex-shrink: 0;
}
#swappingInvoiceModal .ref-qty-modal-table .ref-qty-check-wrap {
  display: flex;
  align-items: center;
  justify-content: center;
  width: 100%;
  min-height: 1.25rem;
}
</style>
<div class="mx-2">
    <div class="row mx-0 my-2">
        <h2>Create Swapping</h2>
    </div>

    <form method="POST" action="{{ url('/invoice/createswapping') }}" id="myForm">
        @csrf

        <!-- Form Starts -->
        <div class="form-group">
            <!-- Second row -->
            <div class="row mt-3">
                <div class="col-4">
                    <label class="control-label">{{ __('Reference No.') }}</label>
                    <input type="text" class="form-control toUpperCase" name="invoice_no" required="required" />
                </div>
            </div>

            <!-- Product row -->
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
                            <th scope="col" style="min-width: 250px;">Product In</th>
                            <th scope="col" style="min-width: 250px;">Product Out</th>
                            <th scope="col" style="min-width: 250px;">Batch No.</th>
                            <th scope="col" style="min-width: 100px;">Qty</th>
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

            <div class="col-4">
                <button type="submit" id="submitBtn" class="btn btn-primary mt-3">Add Swapping</button>
            </div>
        </div>
    </form>
</div>

<!-- Modal -->
<div class="modal fade" id="swappingInvoiceModal" tabindex="-1">
  <div class="modal-dialog modal-lg modal-dialog-centered">
    <div class="modal-content">

      <div class="modal-header">
        <div>
          <h5 class="modal-title mb-0">Available ref quantity</h5>
          <small class="text-muted">Batch &rarr; supplier reference tables (supplier_referacne_number + supplier_referance_product)</small>
        </div>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>

      <div class="modal-body">
        <table class="table table-bordered table-sm table-hover ref-qty-modal-table mb-0">
          <colgroup>
            <col class="col-ref-qty-check" span="1">
            <col span="1"><col span="1"><col span="1">
          </colgroup>
          <thead class="table-light">
            <tr>
              <th class="ref-qty-check-cell">
                <span class="ref-qty-check-wrap">
                  <input type="checkbox" id="swappingInvoiceModalCheckAll" class="ref-qty-checkbox" title="Select all" aria-label="Select all lines">
                </span>
              </th>
              <th>Batch No</th>
              <th>Supplier Invoice No</th>
              <th>Total available qty</th>
            </tr>
          </thead>
          <tbody id="swappingInvoiceModalBody"></tbody>
        </table>
      </div>

      <div class="modal-footer">
        <button type="button" class="btn btn-primary" id="swappingInvoiceApplyBtn">Apply</button>
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
      </div>

    </div>
  </div>
</div>

@endsection

@section('footer')
<script type="text/javascript">
$(document).ready(function(){
    // Fetch products and batches data
    $.ajax({
        'url': "{{ url('/invoice/data') }}"
    }).done(function(data) {
        if (data) {
            products = data.product;
            batches = data.batch;
            console.log('Products:', products); // Debugging: log products
            console.log('Batches:', batches);   // Debugging: log batches
        }
    });
});

var productRows = 0;
var products = [];
var batches = [];

function normalizeBatchNos(batchNos) {
    if (batchNos === null || batchNos === undefined) return [];
    if (Array.isArray(batchNos)) return batchNos;
    if (batchNos === '') return [];
    return [batchNos];
}

// Handle form submission
$('#myForm').on('submit', function(event){
    if ($('#productTable tr').length < 1) {
        alert("No product added. Add at least 1 product.");
        event.preventDefault();
    } else {
        var productTableRow = 0;
        var submitFlag = 0;
        $('#productTable select').each(function(){
            productTableRow++;
            if (!$(this).val()) {
                alert("Product Row " + productTableRow + " is empty. Select a product or delete the row.");
                event.preventDefault();
                submitFlag++;
                return false;
            }
        });

        if (submitFlag === 0){
            $('#productTable tr').each(function(){
                var $sel = $(this).find('select.batch_select');
                if (!$sel.length) return;
                var rowId = $sel.data('row');
                var v = $sel.val();
                var batchVals = normalizeBatchNos(v);
                if (batchVals.length === 0) return;

                var js = ($('#ref_selection_json' + rowId).val() || '').trim();
                if (js === '' || js === '[]') {
                    // Allow submission; backend will decide whether ref selection is required.
                    $('#ref_selection_json' + rowId).val('[]');
                }
            });
            $('#submitBtn').prop('disabled', true);
        }
    }
});

// Function to delete a row
function deleteRow(ref) {
    $(ref).parents("tr").remove();
}

// Function to update quantity based on selected batch
function updateQuantity(ref) {
    var len = $(ref).data('len');
    var batchNo = $(ref).val();

    // Find the selected batch
    var selectedBatch = batches.find(batch => batch.batch_no == batchNo);
    if (selectedBatch) {
        $("#quan" + len + " input").attr("max", selectedBatch.batch_balance);
        $("#max" + len).html("Quantity available: " + selectedBatch.batch_balance);
    }
}

function getbatches(ref){
    var len = $(ref).data('len');
    var id = $(ref).val();
    var row = $(ref).data('row');
    console.log(row, id);

    $.ajax({
        'url': "{{ url('/getbatchesbyid/') }}"+'/'+id,
        'method': 'GET',
    }).done(function(data) {
        if (data) {
            batches = data;
            checking(batches, row);
        }
    });
}

function checking(batches, productRows) {
    const selectedProduct = $(`[name="po[${productRows}][product]"]`).val();
    const filteredBatches = batches.filter(batch => batch.product_id == selectedProduct);

    let batchOptions = '<option disabled>-- SELECT BATCH --</option>';
    filteredBatches.forEach(batch => {
        batchOptions += `<option value="${batch.batch_no}">${batch.batch_no} (Available: ${batch.batch_balance})</option>`;
    });

    const $batchSelect = $(`[name="po[${productRows}][batch_no]"]`);
    $batchSelect.html(batchOptions);
    $batchSelect.selectpicker('refresh');
    $('#swapping_invoice_ids' + productRows).val('');
    $('#ref_selection_json' + productRows).val('');
}

// Function to add a new product row
$("#addProduct").click(function() {
    var options = '<option value="" selected disabled>-- SELECT PRODUCT --</option>';
    var optionsIn = '<option value="" selected disabled>-- SELECT PRODUCT --</option>';
    var batchOptions = '<option value="" selected disabled>-- SELECT BATCH --</option>';

    productRows += 1;

    // Populate product options
    $.each(products, function(index, product) {
        options += `<option value="${product.id}">${product.code} - ${product.name} (${product.quantity})</option>`;
        optionsIn += `<option value="${product.id}">${product.code} - ${product.name}</option>`;
    });

    // Populate batch options
    $.each(batches, function(index, batch) {
        batchOptions += `<option value="${batch.batch_no}">${batch.batch_no} (Available: ${batch.batch_balance})</option>`;
    });

    var rowHtml = `
        <tr>
            <td>
                <select class="selectpicker product_select" data-live-search="true" data-len="${productRows}" data-row="${productRows}" name="po[${productRows}][swapped_with]">
                    ${optionsIn}
                </select>
            </td>
            <td>
                <select class="selectpicker product_select" onchange="getbatches(this);" data-live-search="true" data-row="${productRows}" data-len="${productRows}" name="po[${productRows}][product]">
                    ${options}
                </select>
            </td>
           <td id="batch${productRows}">
    <input type="hidden" name="po[${productRows}][supplier_invoice_ids]" id="swapping_invoice_ids${productRows}">
    <input type="hidden" name="po[${productRows}][ref_selection_json]" id="ref_selection_json${productRows}" class="ref_selection_json" value="">
    <select class="form-control batch_no selectpicker batch_select" 
            data-live-search="true" 
            data-len="${productRows}" 
            data-row="${productRows}" 
            data-container="body"
            title="-- SELECT BATCH --"
            required
            name="po[${productRows}][batch_no]">
        ${batchOptions}
    </select>
</td>

            <td id="quan${productRows}">
                <input type="number" min="1" class="form-control quantity" data-len="${productRows}" name="po[${productRows}][quantity]" value="1" />
                <span style="font-size:11px;" id="max${productRows}"></span>
            </td>
            <td>
                <button type="button" class="close" onclick="deleteRow(this);" data-bs-dismiss="alert" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </td>
        </tr>
    `;

    $("#productTable").append(rowHtml);
    $('.selectpicker').selectpicker();
});

$(document).on('changed.bs.select', '.batch_select', function(){
    // only skip when we explicitly set boolean true during programmatic set
    if ($(this).data('suppress-ref-modal') === true) return;

    var rowId = $(this).data('row');
    var batchNos = $(this).val();
    var productId = $(`[name="po[${rowId}][product]"]`).val();
    if (!batchNos || !productId) return;

    var batchKeyNorm = $.map($.makeArray(batchNos), function (b) { return String(b); }).sort().join('\0');
    var prevJson = $('#ref_selection_json' + rowId).val() || '';
    var prevBatchKey = '';
    try {
        var prevArr = JSON.parse(prevJson);
        if (Array.isArray(prevArr)) {
            prevBatchKey = $.map(prevArr, function (x) { return String(x.batch_no); }).sort().join('\0');
        }
    } catch (e2) {}
    if (batchKeyNorm !== prevBatchKey) {
        $('#ref_selection_json' + rowId).val('');
        $('#swapping_invoice_ids' + rowId).val('');
    }

    $('#swappingInvoiceModalBody').html('<tr><td colspan="4" class="text-center">Loading...</td></tr>');
    $.ajax({
        url: "{{ route('swapping.batch.details') }}",
        type: "POST",
        data: {
            _token: "{{ csrf_token() }}",
            batch_no: batchNos,
            product_id: productId
        },
        success: function(res) {
            let html = '';
            function escapeAttr(s) {
                return String(s == null ? '' : s)
                    .replace(/&/g, '&amp;')
                    .replace(/"/g, '&quot;')
                    .replace(/'/g, '&#39;');
            }

                var prevSel = [];
            try { prevSel = JSON.parse($('#ref_selection_json' + rowId).val() || '[]'); } catch (e) { prevSel = []; }
            if (!Array.isArray(prevSel)) prevSel = [];
            // New ref source: unique reference tables; keep selections by unique_referencenumber_id.
            var prevUrIdSet = new Set($.map(prevSel, function (x) { return String(x.unique_referencenumber_id); }));

            if (res.length === 0) {
                $('#swappingInvoiceModalCheckAll').prop('checked', false);
                html = '<tr><td colspan="4" class="text-center text-danger">No available ref qty for this product on selected batches</td></tr>';
            } else {
                // New behavior: one checkbox per unique reference row (urId) — because that's
                // what we store in ref_selection_json and validate server-side.
                $.each(res, function(i, row) {
                    var urId = row.unique_referencenumber_id != null ? String(row.unique_referencenumber_id) : '';
                    var bn = row.batch_no || '';
                    var tot = row.available_qty != null ? parseInt(row.available_qty, 10) : 0;
                    if (!urId || !bn) return;

                    var checked = prevUrIdSet.has(String(urId)) ? ' checked' : '';
                    html += '<tr data-unique-ref-id="' + escapeAttr(urId) + '" data-batch-no="' + escapeAttr(bn) + '" data-available-qty="' + escapeAttr(tot) + '">' +
                        '<td class="ref-qty-check-cell"><span class="ref-qty-check-wrap">' +
                        '<input type="checkbox" class="ref-qty-checkbox swapping-invoice-check"' + checked + ' aria-label="Select reference ' + escapeAttr(urId) + '">' +
                        '</span></td><td>' + escapeAttr(bn) + '</td><td>' + escapeAttr(row.supplier_invoice_number || (row.is_no_supplier_invoice ? 'No supplier invoice' : '')) + '</td><td><strong>' + tot + '</strong></td></tr>';
                });
            }
            $('#swappingInvoiceModalBody').html(html);
            $('#swappingInvoiceModalCheckAll').prop('checked',
                res.length > 0 && $('#swappingInvoiceModalBody .swapping-invoice-check').length === $('#swappingInvoiceModalBody .swapping-invoice-check:checked').length);
            $('#swappingInvoiceModal').data('row', rowId).modal('show');
        }
    });
});

$(document).on('change', '#swappingInvoiceModalCheckAll', function () {
    var on = $(this).prop('checked');
    $('#swappingInvoiceModalBody .swapping-invoice-check').prop('checked', on);
});

$(document).on('change', '#swappingInvoiceModalBody .swapping-invoice-check', function () {
    var $cbs = $('#swappingInvoiceModalBody .swapping-invoice-check');
    $('#swappingInvoiceModalCheckAll').prop('checked', $cbs.length > 0 && $cbs.length === $cbs.filter(':checked').length);
});

$(document).on('click', '#swappingInvoiceApplyBtn', function () {
    var rowId = $('#swappingInvoiceModal').data('row');
    var lines = [];
    var sumRef = 0;
    $('#swappingInvoiceModalBody tr[data-unique-ref-id]').each(function () {
        if (!$(this).find('.swapping-invoice-check').prop('checked')) return;
        var urId = $(this).data('unique-ref-id');
        var bn = String($(this).attr('data-batch-no') || '');
        var aq = parseInt($(this).data('available-qty'), 10) || 0;
        sumRef += aq;
        if (!bn) return;
        lines.push({
            unique_referencenumber_id: parseInt(urId, 10),
            batch_no: bn
        });
    });
    if (lines.length === 0) {
        alert('Please select at least one reference line.');
        return;
    }
    var swapQty = parseInt($('#quan' + rowId + ' input.quantity').val(), 10) || 0;
    if (swapQty > sumRef) {
        alert('Swapping quantity (' + swapQty + ') is greater than total ref from selected lines (' + sumRef + '). Select more lines or reduce quantity.');
        return;
    }

    var batchesUnique = [];
    var uniqueRefIds = [];
    lines.forEach(function (L) {
        if (batchesUnique.indexOf(L.batch_no) === -1) batchesUnique.push(L.batch_no);
        if (uniqueRefIds.indexOf(L.unique_referencenumber_id) === -1) uniqueRefIds.push(L.unique_referencenumber_id);
    });

    $('#ref_selection_json' + rowId).val(JSON.stringify(lines));
    // keep this hidden input for backward compatibility; now stores unique reference ids
    $('#swapping_invoice_ids' + rowId).val(uniqueRefIds.join(','));

    var $sel = $(`[name="po[${rowId}][batch_no]"]`);
    if (!$sel.length) return;
    $sel.data('suppress-ref-modal', true);
    $sel.selectpicker('val', batchesUnique);
    $sel.selectpicker('refresh');
    $('#swappingInvoiceModal').modal('hide');
    setTimeout(function () { $sel.removeData('suppress-ref-modal'); }, 0);
});

</script>
@endsection
