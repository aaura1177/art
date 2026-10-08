@extends('layouts.app')

@section('content')

<div class="mx-2">
    <div class="row mx-0 my-2">
        <h2>Reject & Repair</h2>
    </div>

    <form method="POST" id="myForm" action="{{ url('/rejectRepair/create') }}">
      @csrf

      <div class="form-group">

        <!-- First Row: Supplier & Status -->
        <div class="row mt-3">
            <div class="col-4">
              <label class="control-label">{{ __('Supplier') }}</label>
              <a href="{{ url('/supplier/create')}}" style="float: right;"> (+New)</a>
              <select type="text" class="selectpicker" data-live-search="true" id="supplier_id" name="supplier_id" required="required">
                <option value="" selected disabled>Select Supplier</option>
                @if(isset($supplier))
                  @foreach($supplier as $key => $supp)
                    <option value="{{$supp->id}}" @if(isset($supplierInvoice) && $supplierInvoice->user->supplier->id==$supp->id) selected @endif>
                      {{$supp->c_name}}
                    </option>
                  @endforeach
                @endif
              </select>
            </div>

            <div class="col-4">
              <label class="control-label">{{ __('Status') }}</label>
              <select id="status_select" type="text" class="selectpicker" data-live-search="true" name="status" required="required">
                <option value="1" selected>Reject</option>
                <option value="2">Repair</option>
                <option value="3">Convert</option>
              </select>
            </div>
        </div>

        <!-- Second Row: Invoice No. & Date -->
        <div class="row mt-3" id="inv_no_div" style="display:none;">
            <div class="col-4">
                <label class="control-label">{{ __('Supplier Invoice No.') }}</label>
                <a href="#" onclick="selsupplierInvoice()" style="float: right;"> (+Select)</a>
                <input id="supplier_inv_no" type="text" class="form-control toUpperCase"  name="supplier_inv_no" required="required" value="{{isset($supplierInvoice)?$supplierInvoice->supplier_invoice_number:null}}" readonly placeholder="Click on Select to enter"/>
                <input type="hidden" id="supplier_inv_id" name="supplier_inv_id" value="{{isset($supplierInvoice)?$supplierInvoice->id:null}}"/>
            </div>

            <div class="col-4">
                <label class="control-label">{{ __('Date') }}</label>
                <input type="date" class="form-control" value="{{isset($supplierInvoice)?date('Y-m-d'):null}}" name="date" required="required" />
            </div>
        </div>

        <!-- Third Row: Outward Challan & Send to Supplier -->
        <div class="row mt-3">
            <div class="col-4">
                <label class="control-label">{{ __('Outward Challan No.') }}</label>
                <input id="outward_challan_no" name="outward_challan_no" type="text" class="form-control toUpperCase" value="{{isset($supplierInvoice)?$supplierInvoice->outward_challan_no:null}}" placeholder=""/>
            </div>
            <div class="col-4">
              <label class="control-label">{{ __('Send to Supplier') }}</label>
              <select type="text" class="selectpicker" data-live-search="true" name="send_to_supplier" >
                <option value="0" selected disabled>Select Supplier</option>
                @if(isset($supplier))
                  @foreach($supplier as $key => $supp)
                    <option value="{{$supp->id}}" @if(isset($supplierInvoice) && $supplierInvoice->user->supplier->id==$supp->id) selected @endif>
                      {{$supp->c_name}}
                    </option>
                  @endforeach
                @endif
              </select>
            </div>
        </div>

        <!-- Product Table -->
        <div class="table-responsive mt-3" id="product_div" style="display:none;">
            <table class="table table-hover">
              <thead>
                <tr id="mytable">
                  <th scope="col" style="min-width: 320px;">Product <a href="{{ url('/product/create')}}" target="_blank"> (+New)</a></th>
                  <th scope="col" style="min-width: 120px;">Qty</th>
                  <th scope="col" style="min-width: 220px;">Remarks</th>
                  {{-- No visible PO column; we post po_id/po_no as hidden --}}
                  @if(isset($batch_flag) && $batch_flag == 0)
                    <th scope="col" style="min-width: 200px;">Batch</th>
                  @endif
                  <th style="width: 48px;"></th>
                </tr>
              </thead>
              <tbody id="productTable">
                @php $productRows = 0; @endphp
                {{-- Rows will be added by user via "Add Product" --}}
              </tbody>
            </table>
        </div>

        <div class="row mt-2">
            <div class="col">
                <input style="display:none;" type="button" id="addProduct" class="btn btn-primary" value="Add Product" />
            </div>
        </div>

        <div class="row col-4">
          <button type="button" onclick="this_submit()" id="submitBtn" class="btn btn-primary mt-3">Submit</button>
        </div>

      </div>
    </form>
</div>

<!-- Modal for selecting supplier invoice -->
<div class="modal fade" id="selsupplierInvoice" role="dialog">
    <div class="modal-dialog">
      <div class="modal-content">
        <div class="modal-header">
          <h4 class="modal-title">Supplier Invoice No</h4>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>

        @csrf
          <div class="modal-body">
              <div class="row">
                <div class="col-12">
                  <label>Select Supplier Invoice No.</label>
                  <select class="selectpicker" data-live-search="true" id="supplier_invoice_number" required></select>
                </div>
              </div>
          </div>
          <div class="modal-footer">
            <a onclick="selBuyerOrderNo()" class="btn btn-success" style="color: #fff;">Select</a>
          </div>
      </div>
    </div>
</div>

@endsection

@section('footer')
<script>
var productRows = 0;
// products from the selected invoice (user will choose from these)
var invoiceProducts = [];
// fallback full catalog (id + code + name) if needed
var catalogProducts = [];
var batch_flag = 0;

// --- helpers ---
function labelSkuName(code, name) {
  var sku = code ? String(code) : '';
  var nm  = name ? String(name) : '';
  if (sku && nm) return sku + ' - ' + nm;
  return sku || nm || '';
}
function labelWithPo(code, name, poNo) {
  var base = labelSkuName(code, name);
  if (poNo && String(poNo).trim() !== '') return base + ' (' + poNo + ')';
  return base;
}
function esc(t){
  return String(t || '').replace(/[&<>"']/g, s => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[s]));
}

$(document).ready(function(){

  // Load catalog (no supplier_inv_id) → returns id+code+name list under 'product'
  $.ajax({ url: "{{ url('/rejectRepair/data') }}" })
    .done(function(data) {
      if (data) {
        // When no invoice selected, your endpoint returns a list of products under 'product'
        // with id, product_code/code, product_name/name (depending on your controller)
        catalogProducts = (data.product || []).map(function(p){
          return {
            id: p.id,
            code: p.product_code || p.code || '',
            name: p.product_name || p.name || ''
          };
        });
        batch_flag = data.batch_flag || 0;
      }
    });

  $('#supplier_id').change(function(){
    $('#inv_no_div').fadeIn();
  });

  $('#status_select').change(function(){
    if($(this).val() == 3){
      $('#supplier_inv_no').removeAttr('readonly');
      $('#product_div').fadeIn();
      $('#addProduct').fadeIn();
    }else{
      $('#supplier_inv_no').attr('readonly',true);
      $('#product_div').fadeOut();
      $('#addProduct').fadeOut();
    }
  });
});

function this_submit() {
  $('.products').attr('disabled',false);
  $('#myForm').submit();
}

$('#myForm').on('submit', function(event){
  if($('#productTable tr').length<1) {
    alert("No product added. Add atleast 1 product.");
    event.preventDefault();
    return;
  }

  var submitFlag = 0;
  $('#productTable select.products').each(function(index){
      if(!$(this).val()){
        alert( "Product Row "+(index+1)+ " empty. Select a product or delete the row." );
        event.preventDefault();
        submitFlag++;
        return false;
      }
  });

  if(submitFlag == 0) $('#submitBtn').prop('disabled', 'true');
});

function selsupplierInvoice() {
  var supplier_id=$('#supplier_id').val();
  if(supplier_id) {
    $.ajax({
      url: "{{ url('/rejectRepair/create') }}/",
      method: 'GET',
      data:{'supplier_id':supplier_id},
      success: function(data){
          if(data.length>0) {
            $('#selsupplierInvoice').modal('show');
            $('#supplier_invoice_number').empty();
            for(var i=0;i < data.length; i++) {
                var id = data[i]['id'];
                var invoice = data[i]['supp_inv_no'] || data[i]['supplier_invoice_number'] || ('#'+id);
                $('#supplier_invoice_number').append('<option value="'+esc(id)+'">'+esc(invoice)+'</option>');
            }
            $('.selectpicker').selectpicker('refresh');
          } else {
            alert('No supplier invoices found for this supplier.');
          }
      }
    });
  }
}

function selBuyerOrderNo(){
  var buyer_order      = $('#supplier_invoice_number').find('option:selected').html();
  var supplier_inv_id  = $('#supplier_invoice_number').val();

  $('#supplier_inv_no').val(buyer_order);
  $('#supplier_inv_id').val(supplier_inv_id);
  $('#selsupplierInvoice').modal('hide');
  $('#product_div').fadeIn();
  $('#addProduct').fadeIn();

  // Fetch products for selected supplier invoice (user will select from these)
  $.ajax({
      url: "{{ url('/rejectRepair/data') }}",
      data: { supplier_inv_id: supplier_inv_id },
      method: 'GET'
  }).done(function(data) {
      batch_flag = data.batch_flag || 0;
      // The endpoint returns items under 'product'
      // Normalize to a consistent shape with id, code, name, po_id, po_no
      invoiceProducts = (data.product || []).map(function(it){
        return {
          id: it.id,
          code: it.product_code || it.code || '',
          name: it.product_name || it.name || '',
          po_id: it.po_id || it.mulitple_po_purchaseBill || '',
          po_no: it.po_no || '',
          quantity: it.quantity || 1,
          remarks: it.remarks || ''
        };
      });

      // Optional: start with one empty row so user can select
      addProductRow();
  }).fail(function(){
      alert('Error fetching products for selected invoice.');
  });
}

// Create a row whose dropdown lists ONLY the invoice's products for user to pick
function addProductRow(){
  productRows += 1;

  // Prefer invoiceProducts; if empty, fall back to catalogProducts
  var source = invoiceProducts.length ? invoiceProducts : catalogProducts;

  var options = '<option value="" selected disabled>-- SELECT PRODUCT --</option>';
  source.forEach(function(p){
    var label = labelWithPo(p.code, p.name, p.po_no);
    options += '<option value="'+esc(p.id)+'" data-po-id="'+esc(p.po_id || '')+'" data-po-no="'+esc(p.po_no || '')+'" data-code="'+esc(p.code || '')+'" data-name="'+esc(p.name || '')+'">'+ esc(label) +'</option>';
  });

  var row  = '<tr>';
  row +=   '<td id="pr">';
  row +=     '<select type="text" id="pro_'+productRows+'" class="selectpicker products" onchange="onProductSelect('+productRows+')" data-live-search="true" name="po['+productRows+'][product]" required>';
  row +=       options;
  row +=     '</select>';
  row +=   '</td>';

  row +=   '<td id="quan'+productRows+'">';
  row +=     '<input type="number" id="quan_'+productRows+'" min="1" class="form-control quantity" name="po['+productRows+'][quantity]" value="1" />';
  row +=   '</td>';

  row +=   '<td id="remarks'+productRows+'">';
  row +=     '<input type="text" class="form-control remarks" name="po['+productRows+'][remarks]" value="" />';
  row +=   '</td>';

  // Hidden PO id / PO no (filled when user selects a product)
  row +=   '<td >';
  row +=     '<input type="text" name="po['+productRows+'][po_id]" value="" readonly>';
  row +=     '<input type="hidden" name="po['+productRows+'][po_no]" value="">';
  row +=   '</td>';

  if (batch_flag == 0){
    row += '<td id="batch'+productRows+'"><select class="form-control selectpicker batch" name="po['+productRows+'][batch][]" multiple></select></td>';
  }

  row +=   '<td><button type="button" class="close" onclick="deleterowInvoice(this);" data-bs-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button></td>';
  row += '</tr>';

  $("#productTable").append(row);
  $('.selectpicker').selectpicker();
}

// When user picks a product, set hidden PO fields and (optionally) default qty/remarks
function onProductSelect(rowId){
  var $sel   = $('#pro_' + rowId + ' option:selected');
  var prodId = $('#pro_' + rowId).val();
  var poId   = $sel.data('po-id') || '';

  // Check for duplicate product + PO combination in other rows
  var duplicate = false;
  $('.products').not('#pro_'+rowId).each(function(){
      var otherProdId = $(this).val();
      var otherPoId   = $(this).find('option:selected').data('po-id') || '';
      if(otherProdId == prodId && otherPoId == poId){
          duplicate = true;
      }
  });

  if(duplicate){
      alert("This product with same PO is already selected in another row.");
      $('#pro_' + rowId).val('').selectpicker('refresh');
      return; // stop further processing
  }

  // Set hidden PO ID / PO No fields
  $('input[name="po['+rowId+'][po_id]"]').val(poId);
  $('input[name="po['+rowId+'][po_no]"]').val($sel.data('po-no') || '');

  // Prefill quantity / remarks if available
  var match = invoiceProducts.find(function(p){ 
      return String(p.id) === String(prodId) && String(p.po_id) === String(poId); 
  });
  if (match) {
      $('#quan_'+rowId).val(match.quantity || 1);
      $('input[name="po['+rowId+'][remarks]"]').val(match.remarks || '');
  }

  // Handle batch loading if enabled
  if (batch_flag == 0 && prodId){
      loadBatches(rowId, prodId);
  }
}

// Add row button
$("#addProduct").click(function() {
  addProductRow();
});

function deleterowInvoice(ref) { $(ref).parents("tr").remove(); }

function check_max(id) {
    var max = parseInt($('#quan_'+id).attr('max'));
    if (max && $('#quan_'+id).val() > max) $('#quan_'+id).val(max);
}

// Backward-compat: if something calls check_quan(rowId)
function check_quan(rowId){ onProductSelect(rowId); }

// Populate batches if batch_flag == 0
function loadBatches(rowId, product_id){
  $.ajax({
      url: '/getbatchesbyid/' + product_id,
      type: 'GET',
      success: function(batches) {
          var $batchSelect = $('select[name="po[' + rowId + '][batch][]"]');
          $batchSelect.empty();
          $.each(batches, function(index, batch) {
              $batchSelect.append('<option value="'+esc(batch.batch_no)+'">'+esc(batch.batch_no)+' (Available: '+esc(batch.batch_balance)+')</option>');
          });
          $batchSelect.selectpicker('refresh');
      },
      error: function() {
          alert('Error fetching batches for this product.');
      }
  });
}
</script>
@endsection
