@extends('layouts.app')

@section('content')

  <div class="mx-2">
    <div class="row mx-0 my-2">
        <h2>Add Credt Note</h2>
    </div>

    <form id='createStockOut' method="POST" action="{{ url('/invoice/credit_note_store') }}">
      @csrf

      <!-- Form Starts -->
      <div class="form-group">

          <!-- first row -->
          <div class="row mt-3">
            <div class="col-4">
              <label class="control-label">{{ __('Invoice No.') }}</label>
              <select type="text" class="selectpicker" data-live-search="true" onchange="changeDetails(this)" name="invoice_id" required="required">
                <option value="" selected disabled>Select Invoice</option>
                  @if(isset($invoice)) @foreach($invoice as $key => $invoice)
                    <option value="{{$invoice->id}}">
                    {{$invoice->invoiceno}}
                    </option>
                  @endforeach @endif
              </select>
            </div>


          </div>



          <div class="row mt-3">
            <div class="col-8">
              <div class="input-group">
                <span class="input-group-addon" style="border: 1px solid #ccc; padding: 0.4rem;"><i class="fa fa-barcode"></i></span>
                <input id="myInput" type="text" class="form-control" name="enterproductname" onkeyup="searchProduct(this);" placeholder="Scan Product Barcode" disabled autocomplete="off"/>
              </div>
            </div>
          </div>
          <div class="row mt-3">
            <table class="table table-hover">
                <thead>
                  <tr id="mytable">
                    <th scope="col" style="min-width:300px;">Product</th>
                    <th scope="col">EAN</th>
                    <th scope="col">QTY</th>
                    <th scope="col">Returned QTY</th>
                  </tr>
                </thead>
                <tbody id="productInvoice">
                </tbody>
            </table>
          </div>

          <div class="row col-4">
                <button type="submit" id="hidden" hidden class="btn btn-primary mt-3" disabled>Create Purchase Bill</button>
                <button type="submit" class="btn btn-primary mt-3" id='formSubmit' form='createStockOut' >Submit</button>
          </div>

      </div>
    </form>
  </div>

<div class="modal fade" id="supplierInvoiceModal" tabindex="-1">
  <div class="modal-dialog modal-lg modal-dialog-centered">
    <div class="modal-content">

      <div class="modal-header">
        <div>
          <h5 class="modal-title mb-0">Available ref quantity</h5>
          <small class="text-muted">Batch &rarr; supplier invoice &rarr; unique reference pool (remaining qty from approved receive)</small>
        </div>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>

      <div class="modal-body">
        <table class="table table-bordered table-sm table-hover ref-qty-modal-table mb-0">
          <colgroup>
            <col style="width:3rem" span="1">
            <col span="1">
            <col span="1">
            <col span="1">
          </colgroup>
          <thead class="table-light">
            <tr>
              <th scope="col" style="width:3rem; text-align:center;">
                <input type="checkbox" id="supplierInvoiceModalCheckAll" title="Select all" aria-label="Select all lines">
              </th>
              <th scope="col">Batch No</th>
              <th scope="col">Supplier Invoice No</th>
              <th scope="col">Total available qty</th>
            </tr>
          </thead>
          <tbody id="supplierInvoiceModalBody"></tbody>
        </table>
      </div>

      <div class="modal-footer">
        <button type="button" class="btn btn-primary" id="supplierInvoiceModalApply">Apply</button>
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
      </div>

    </div>
  </div>
</div>

@endsection

@section('footer')
<!-- Script start for Statecode & GSTIN button -->
<script>
  $(document).ready(function(){
    $.ajax({
      'url': "{{ url('/packingSheet/data') }}",
      'method': 'GET'
    }).done(function(data){
        if (data) {
          psInvoice = data.invoice;
          psProduct = data.product;
        }
      });
  });


  var psInvoice = [];
  var productRows = 0;
  var psProduct = [];
  var invoicePoTable = [];

  function escapeAttr(s) {
    return String(s == null ? '' : s)
      .replace(/&/g, '&amp;')
      .replace(/"/g, '&quot;')
      .replace(/'/g, '&#39;');
  }

  function mergeRefQtyRowsForPackingModal(rows) {
    var merged = [];
    var byKey = Object.create(null);
    if (!rows || !rows.length) {
      return merged;
    }
    for (var i = 0; i < rows.length; i++) {
      var row = rows[i];
      var uid = parseInt(row.unique_referencenumber_id, 10) || 0;
      var qty = parseInt(row.available_qty, 10) || 0;
      if (row.is_no_supplier_invoice) {
        if (uid <= 0 || qty <= 0) {
          continue;
        }
        merged.push({
          batch_no: row.batch_no,
          supplier_invoice_number: row.supplier_invoice_number || 'No supplier invoice',
          available_qty: qty,
          is_no_supplier_invoice: true,
          ur_ids: [uid]
        });
        continue;
      }
      var bn = String(row.batch_no || '');
      var sid = parseInt(row.supplier_invoice_id, 10) || 0;
      if (uid <= 0 || qty <= 0 || !bn) {
        continue;
      }
      var key = bn + '\u0000' + sid;
      if (!byKey[key]) {
        byKey[key] = {
          batch_no: row.batch_no,
          supplier_invoice_number: row.supplier_invoice_number || '',
          available_qty: 0,
          is_no_supplier_invoice: false,
          ur_ids: []
        };
        merged.push(byKey[key]);
      }
      byKey[key].available_qty += qty;
      byKey[key].ur_ids.push(uid);
    }
    return merged;
  }

  function openRefModalForCN(invoiceId, productId, batchNos, desiredQty, onDone) {
    $('#supplierInvoiceModal')
      .data('invoice_id', invoiceId)
      .data('product_id', productId)
      .data('desired_qty', desiredQty || 1)
      .data('on_done', onDone);

    $('#supplierInvoiceModalBody').html(
      '<tr><td colspan="4" class="text-center">Loading...</td></tr>'
    );
    $('#supplierInvoiceModalCheckAll').prop('checked', false);

    $.ajax({
      url: "{{ route('supplier.invoice.batch.details') }}",
      type: "POST",
      data: {
        _token: "{{ csrf_token() }}",
        batch_no: batchNos,
        product_id: productId
      },
      success: function (res) {
        var html = '';
        var displayRows = mergeRefQtyRowsForPackingModal(res || []);
        if (displayRows.length === 0) {
          html = '<tr><td colspan="4" class="text-center text-danger">No available ref qty for this product on selected batches</td></tr>';
        } else {
          displayRows.forEach(function (row) {
            var bn = row.batch_no || '';
            var tot = row.available_qty != null ? parseInt(row.available_qty, 10) : 0;
            var urIds = row.ur_ids || [];
            var urIdsAttr = urIds.join(',');
            var labelIds = urIds.length > 1 ? urIdsAttr : String(urIds[0] || '');
            html += (
              '<tr data-ur-ids="' + escapeAttr(urIdsAttr) + '" data-batch-no="' + escapeAttr(bn) + '" data-available-qty="' + escapeAttr(tot) + '">' +
                '<td style="text-align:center;"><input type="checkbox" class="ref-batch-cb" aria-label="Select reference ' + escapeAttr(labelIds) + '"></td>' +
                '<td>' + escapeAttr(bn) + '</td>' +
                '<td>' + escapeAttr(row.supplier_invoice_number || '') + '</td>' +
                '<td><strong>' + (Number.isFinite(tot) ? tot : 0) + '</strong></td>' +
              '</tr>'
            );
          });
        }
        $('#supplierInvoiceModalBody').html(html);
        $('#supplierInvoiceModal').modal('show');
      }
    });
  }

  $(document).on('change', '#supplierInvoiceModalCheckAll', function () {
    var on = $(this).prop('checked');
    $('#supplierInvoiceModalBody .ref-batch-cb').prop('checked', on);
  });

  $(document).on('click', '#supplierInvoiceModalApply', function () {
    var invoiceId = $('#supplierInvoiceModal').data('invoice_id');
    var productId = $('#supplierInvoiceModal').data('product_id');
    var desiredQty = parseInt($('#supplierInvoiceModal').data('desired_qty'), 10) || 1;

    var lines = [];
    var sumRef = 0;
    $('#supplierInvoiceModalBody tr[data-ur-ids]').each(function () {
      if (!$(this).find('.ref-batch-cb').prop('checked')) return;
      var bn = String($(this).attr('data-batch-no') || '');
      var aq = parseInt($(this).data('available-qty'), 10) || 0;
      sumRef += aq;
      var idsRaw = String($(this).attr('data-ur-ids') || '');
      var parts = idsRaw.split(',').map(function (s) { return parseInt(String(s || '').trim(), 10) || 0; }).filter(function (n) { return n > 0; });
      for (var pi = 0; pi < parts.length; pi++) {
        if (bn) {
          lines.push({ unique_referencenumber_id: parts[pi], batch_no: bn });
        }
      }
    });

    if (lines.length === 0) {
      alert('Please select at least one reference line.');
      return;
    }
    if (desiredQty > sumRef) {
      alert('Returned quantity (' + desiredQty + ') is greater than total ref from selected lines (' + sumRef + '). Select more lines or reduce quantity.');
      return;
    }

    $.ajax({
      url: "{{ route('credit.note.ref_snapshot.save') }}",
      type: "POST",
      data: {
        _token: "{{ csrf_token() }}",
        invoice_id: invoiceId,
        product_id: productId,
        lines: lines
      },
      success: function (res) {
        if (!res || res.ok !== true) {
          alert((res && res.message) ? res.message : 'Failed to save reference snapshot.');
          return;
        }
        var cb = $('#supplierInvoiceModal').data('on_done');
        $('#supplierInvoiceModal').modal('hide');
        if (typeof cb === 'function') {
          cb({ sumRef: sumRef, count: lines.length });
        }
      },
      error: function (xhr) {
        var msg = 'Failed to save reference snapshot.';
        try {
          var r = xhr.responseJSON;
          if (r && r.message) msg = r.message;
        } catch (e) {}
        alert(msg);
      }
    });
  });

  function changeDetails(ref){
    var id = $(ref).val();
    $("#productInvoice tr").remove();

    $.ajax({
      'url': "{{ url('/packingSheet/data/psInvoiceTableForCN') }}"+"/"+id,
      'method': 'GET'

    }).done(function(data){
        if (data) {
          psInvoiceTable = data.invoiceTable;
        }
      });

    function findInvoice(invoice){
      return invoice.id == id;
    };

    var invoice = psInvoice.find(findInvoice);

    $("#totalbox").val(invoice.totalbox);

    $("#myInput").removeAttr('disabled');
    $("#myInput").select().focus();
  };

  function validateSubmit(){
    if($('#productInvoice tr').length<1) {
      alert( "No product added. Add atleast 1 product." );
      event.preventDefault();
    }
  }

  $('#createStockOut').on('submit', function(){
    if($('#productInvoice tr').length<1) {
      alert( "No product added. Add atleast 1 product." );
      event.preventDefault();
    } else{
        $('#formSubmit').prop('disabled', 'true');
    }
  });

  function reduceQuantity(ref){
    quantity = parseInt($(ref).siblings('input').val(),10);
    if(quantity>1){
        newQuantity = quantity-1;
        var orderqty = $('#orderqty'+productRows+' input').val(); //MYcode
        var remainingqty = orderqty - newQuantity;
        $("#remainingqty"+productRows+" input").val(remainingqty);
        $(ref).siblings('input').val(newQuantity);
        $("#myInput").select().focus();
        calculateTotal();
    }

    else{
      alert("Quantity can not be less than 1.");
      $("#myInput").select().focus();
    }

  };

   function deleterowInvoice(ref) {
    $(ref).parents("tr").remove();
    $("#myInput").select().focus();
  };

  function calculateTotal(){
    var arrTotQty = [];
    var arrNetWt = [];
    var arrGrossWt = [];
    var totQty = 0;

    arrTotQty = $('.receiveqty');

    $.each(arrTotQty,function(index, value){
      totQty += parseInt(arrTotQty[index].value,10);
    });

    $('#psQty').val(totQty);
  }

  $( ".receiveqty" ).change(function() {
    alert( "call function" );
  });

  function searchProduct(ref){

    if (event.keyCode === 13) {
      var ean = $(ref).val();

      function findInvoiceProduct(invoiceProduct){
        return invoiceProduct.EAN == ean;
      };

      function findInvoiceProductByCode(invoiceProduct){
        return invoiceProduct.code == ean;
      };

      function checkProduct(productCheck){
        return productCheck.product_id == psProductId;
      };


      var invoiceProduct = psProduct.find(findInvoiceProduct);

      if(!invoiceProduct){
        var invoiceProduct = psProduct.find(findInvoiceProductByCode);
      }

        console.log(invoiceProduct)

      if(invoiceProduct){
        var psProductId = invoiceProduct.id;

        var invoiceId = $('select[name="invoice_id"]').val();
        if (!invoiceId) {
          alert('Select Invoice first.');
          $("#myInput").select().focus();
          return;
        }

        function proceedAfterSnapshot(sumRef) {
          var productCheck = psInvoiceTable.find(checkProduct);
          if(productCheck){
            var productCheckRemQty = productCheck.remqty;
            if(productCheckRemQty>0)
            {
              var quantity= 1;

              if($('#'+ean).length){
                quantity=parseInt($('#'+ean+' input.receiveqty').val(),10);
                var newQuantity = quantity+1;
                var maxRef = parseInt($('#refsum-'+ean).val() || '0', 10) || 0;
                if (maxRef > 0 && newQuantity > maxRef) {
                  // If snapshot saved earlier but quantity now exceeds selected sumRef, force reselect.
                  // We can't know batch list here cheaply; user can rescan and re-open by saving more lines.
                  alert('Returned quantity exceeds selected ref lines. Please re-select ref lines for this product.');
                }
                if(newQuantity<=productCheckRemQty){
                  $('#'+ean+' input.receiveqty').val(newQuantity);
                  calculateTotal();
                }
                else{
                  alert("Maximum Ordered Quantity Reached.");
                }
              }

              else{

                productRows += 1;

                var $block = "";
                  $block += '<tr>';
                  $block += '<td><select type="text" class="selectpicker" data-live-search="true" name="ps['+productRows+'][product]" readonly><option value="'+invoiceProduct.id+'">' +invoiceProduct.name+ '</option></td>';
                  $block += '<td><input type="text" class="form-control" name="ps['+productRows+'][EAN]" value="'+invoiceProduct.EAN+'" readonly="" /></td>'
                  $block += '<td id="remqty'+ean+'"><input type="number" class="form-control remqty" data-len="' + productRows + '" name="ps['+productRows+'][remqty]" value="'+productCheck.remqty+'" readonly/></td>'
                  $block += '<td id='+ean+'><input type="number" min="1" class="form-control receiveqty" data-len="' + productRows + '" name="ps['+productRows+'][receiveqty]" value="1" readonly/><a  href="javascript:;" onclick="reduceQuantity(this);"><i class="fa fa-minus"></i></a></td>'
                  $block += '<input type="hidden" id="refsum-'+ean+'" value="'+(sumRef || 0)+'" />';
                  $block += '<td><button type="button" class="close" onclick="deleterowInvoice(this);" data-bs-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button></td>';
                  $block += '</tr>';
                  $("#productInvoice").append($block);
                  $('.selectpicker').selectpicker();
                  calculateTotal();

              }
            }
            else{
              alert('Product Remaining Quantity is Zero');
            }
          }

          else{
            alert('Product NOT Found in this Stock');
          }
        }

        $.ajax({
          url: "{{ route('credit.note.ref_snapshot.status') }}",
          type: "POST",
          data: {
            _token: "{{ csrf_token() }}",
            invoice_id: invoiceId,
            product_id: psProductId
          },
          success: function (res) {
            if (!res || res.ok !== true) {
              alert((res && res.message) ? res.message : 'Unable to validate batch/ref snapshot.');
              return;
            }
            if (res.code === 'BATCH_NO_MISSING') {
              alert('Batch no missing for this product in packing list. Please fix packing list first.');
              return;
            }
            var batchNos = res.batch_nos || [];
            if (res.needs_selection === true) {
              openRefModalForCN(parseInt(invoiceId,10), psProductId, batchNos, 1, function(meta){
                proceedAfterSnapshot(meta && meta.sumRef ? meta.sumRef : 0);
              });
            } else {
              proceedAfterSnapshot(0);
            }
          },
          error: function (xhr) {
            var msg = 'Unable to validate batch/ref snapshot.';
            try {
              var r = xhr.responseJSON;
              if (r && r.message) msg = r.message;
            } catch (e) {}
            alert(msg);
          }
        });

      }

      else{
          alert('Product NOT Found');

      }

      $("#myInput").select().focus();
    };
  };

</script>
<!-- Script End -->

@endsection