@extends('layouts.app')

@section('content')
  <style>
    .carton-qty-cell .carton-qty-btn { margin-left: 6px; cursor: pointer; color: #333; }
    .carton-qty-cell .carton-qty-btn:hover { color: #007bff; }
    .carton-qty-cell input.receiveqty1,
    .carton-qty-cell input.receiveqty2 { display: inline-block; max-width: 88px; vertical-align: middle; }
  </style>
  <div class="mx-2">
    <div class="row mx-0 my-2">
        <h2>Supplier Invoice Approval Carton</h2>
        {{-- {{$userSplier->supplier->tdspercent}} --}}
    </div>

    <!-- <form id='createPurchaseBill' method="POST" action="{{ url('/supplier-dashboard/raise-invoice') }}/{{$purchaseOrder->id}}">
      @csrf
      <input type="hidden" name="purchase_order_id" value="{{$purchaseOrder->id}}" /> -->
      <form id='createPurchaseBill' method="POST"
          action="{{ $approveUrl ?? url('/supplierInvoice/approve/carton') . '/' . ($supplierInvoicecar->id ?? '') }}"
          multiform enctype="multipart/form-data">
        @csrf
        <input type="hidden" name="purchase_order_id" value="{{ $id }}" />  
       
      <!-- Form Starts -->
      <div class="form-group"> 
          
        <!-- first row -->
          <div class="row mt-3">  
            <div class="col-4">
                        <label class="control-label">{{ __('PO No.') }}</label>
                        <input type="text" class="form-control" name="po_no" id="po_no"
                            value="{{ $multiPoNumbers ?? $purchaseOrder->pono }}" readonly />

                    </div>
            <div class="col-4">
              <label class="control-label">{{ __('Supplier Name') }}</label>
              <input type="text" class="form-control" name="supplierName" id="supplierName" readonly value = "{{$purchaseOrder->supplier->c_name}}" />
            </div>
            <div class="col-4">
              <label class="control-label">{{ __('Delivery Date') }}</label>
              <input type="date" class="form-control" name="del_date"  id="del_date" readonly value = "{{$purchaseOrder->del_date}}" />
            </div> 
          </div>

          <!-- second row -->
          <div class="row mt-3">                     
            <div class="col-4">
                <label class="control-label">{{ __('Supplier Ref.') }}</label>
                <input type="text" class="form-control" name="Supplier_ref" id="Supplier_ref" readonly value = "{{$purchaseOrder->ref_supplier}}" />
            </div>
            <div class="col-4">
                <label class="control-label">{{ __('Ordered Quantity') }}</label>
                <input type="number" class="form-control" name="poQty" id="total_qty" readonly value = "{{$supplierInvoicecar->tquantity}}" />
            </div>
            <div class="col-4">
                <label class="control-label">{{ __('Sub Total Amount') }}</label>
                <input type="number" class="form-control" name="poAmount" id="total_amount" readonly value = "{{$supplierInvoicecar->subTotal}}" />
            </div>                                    
          </div>
            
          <!-- forth row -->
          <div class="row mt-3">
            <div class="col-8">
                <label class="control-label">{{ __('Remark') }}</label>
                <textarea class="form-control" name="remark" id="remarks" readonly>{{ !empty($isMultiPo) ? ($multiPoRemarks ?? '') : $purchaseOrder->remarks }}</textarea>
            </div>
          </div>

          <!-- second row -->
          <div class="row mt-3">                     
            <div class="col-4">
                <label class="control-label">{{ __('Supplier Invoice No.') }}</label>
                <input type="text" class="form-control toUpperCase" readonly  value="{{$supplierInvoicecar->supplier_invoice_number}}" name="supp_inv_no" required />
            </div>
            
            <div class="col-4">
                <label class="control-label">{{ __('E-Way Bill No.') }}</label>
                <input type="text" class="form-control toUpperCase"readonly name="ewaybill" value="{{$supplierInvoicecar->eway_bill_no}}"/>
            </div>   
            
     <div class="col-4">
              <label class="control-label">{{ __('Supplier Invoice Date') }}</label>
              <input type="date" class="form-control" required name="invoice_date"
                  value="{{ $supplierInvoicecar->invoice_date ? \Carbon\Carbon::parse($supplierInvoicecar->invoice_date)->format('Y-m-d') : '' }}"
                  readonly/>
            </div>
          </div>
          <!-- third row -->
         <div class="row mt-3">
         </div>
          
          
          <!-- forth row -->
          <!--<div class="row mt-3">
            <div class="col-8">
              <div class="input-group">
                <span class="input-group-addon" style="border: 1px solid #ccc; padding: 0.4rem;"><i class="fa fa-barcode"></i></span>
                <input id="myInput" type="text" class="form-control" name="enterproductname" onkeyup="searchProduct(this);" placeholder="Enter Product name / SKU / Scan bar code" disabled autocomplete="off"/>
              </div>
            </div>
          </div>-->
          <div class="row mt-3">
            <table class="table table-hover">
              <thead>
                <tr id="mytable">
                  @if (!empty($isMultiPo))
                    <th scope="col">PO No.</th>
                  @endif
                  <th scope="col" style="min-width: 300px;">Product</th>
                  @if($type == 1)
                    <th scope="col">EAN</th>
                  @endif
                  @if($type == 1 || $type == 2)
                    <th scope="col">Remaining QTY</th>
                    <th scope="col">QTY</th>

                       @if ($type == 2)                    
                    <th scope="col">Unit</th>
                  @endif
                    <th scope="col">Rate/Item (₹)</th>
                    @endif
                 
                  @if($type == 3)
                    <th scope="col"> QTY Box 1</th>
                    <th scope="col"> QTY Box 2</th>
                    <th scope="col">QTY Box 1</th>
                    <th scope="col">QTY Box 2</th>
                    <th scope="col">Rate/Item (₹) (Box 1)</th>
                    <th scope="col">Rate/Item (₹) (Box 2)</th>
                  @endif
                  <th scope="col">Amount (₹)</th>
                  <th scope="col">GSTSLAB</th>
                  <th scope="col">GST (₹)</th>
                </tr>
              </thead>
              <tbody id="productTable">
                <?php $productRows = 0; ?>
                @foreach($supplierInvoiceProduct as $p=>$pots)
                  @php
                    $rowKey = md5((string) $p);
                    $linePo = ! empty($isMultiPo)
                        ? (($allPurchaseOrders[$pots->purchase_order_id] ?? null) ?: $purchaseOrder)
                        : $purchaseOrder;
                  @endphp
                <tr class="supplier-invoice-product-row" data-row-key="{{ $rowKey }}">
                  <?php $productRows++; ?>
                  @if (!empty($isMultiPo))
                    <td scope="col">{{ optional($linePo)->pono }}</td>
                  @endif
                  <td scope="col">
                    @if(isset($pots->product->code)) 
                      {{$pots->product->code}} - {{$pots->product->name}} 
                    @else 
                      {{$pots->consumable->name}} 
                    @endif

                    @if(isset($pots->product_id)) 
                      <input name="pb[{{$productRows}}][product]" type="hidden" value="{{$pots->product_id}}" />
                    @else
                      <input name="pb[{{$productRows}}][product]" type="hidden" value="{{$pots->consumable_id}}" />
                    @endif
                    @if (!empty($isMultiPo))
                      <input name="pb[{{$productRows}}][poid]" type="hidden" value="{{ $pots->purchase_order_id }}" />
                    @endif
                  </td>
                  @if($type == 1)
                    <td scope="col">{{$pots->EAN}}</td>
                  @endif
                  @if($type == 1 || $type == 2)
                    <td scope="col">{{$pots->remqty}}</td>
                    @if($type == 2)
                     <td scope="col" id="{{md5($p)}}">
                      <input class="form-control receiveqty" value="0" step="{{ $pots->data_type == 'float' ? '0.1' : '1' }}"
                      min="0" max="{{$pots->remqty}}" name="pb[{{$productRows}}][receiveqty]" type="number" onchange="recalQuantity(this);"
                        oninput="handleTypeRestriction(this, '{{ $pots->data_type }}')"

                      />
                     
                    </td>   
                                         <td scope="col" id="{{md5($p)}}">
                                          <input id="rec1{{md5($p)}}" class="form-control receiveqty1" value="{{$pots->unit}}" min="0"  readonly  name="pb[{{$productRows}}][unit]" type="text" onchange="recalQuantityCarton(this);" />
                                        </td>

                     
     @else
                     <td scope="col" id="{{md5($p)}}"><input class="form-control receiveqty" value="0"  min="0" max="{{$pots->remqty}}" name="pb[{{$productRows}}][receiveqty]" type="number" onchange="recalQuantity(this);" /></td>          

                    @endif
                      
                    <td scope="col" id="rate{{md5($p)}}"><input class="form-control" value="{{$pots->rate}}" name="pb[{{$productRows}}][rate]" readonly /></td>
                    @endif
                
                  @if($type == 3)
                    @php
                      $q1 = (int) $pots->quantity;
                      $q2 = (int) $pots->quantity2;
                      $min1 = $q1 > 0 ? 1 : 0;
                      $min2 = $q2 > 0 ? 1 : 0;
                    @endphp
                    <td scope="col">{{ $pots->quantity }}</td>
                    <td scope="col">{{ $pots->quantity2 }}</td>
                    <td scope="col" class="carton-qty-cell" id="td-b1-{{ $rowKey }}">
                      <input id="rec1{{ $rowKey }}" class="form-control receiveqty1" type="number"
                        min="{{ $min1 }}" max="{{ $q1 }}" value="{{ $q1 }}" readonly
                        name="pb[{{ $productRows }}][receiveqty_box_1]" />
                      @if($q1 > 0)
                        <a href="javascript:;" class="carton-qty-btn" onclick="cartonAdjustBox(this, 1, -1);" title="Decrease"><i class="fa fa-minus"></i></a>
                        <a href="javascript:;" class="carton-qty-btn" onclick="cartonAdjustBox(this, 1, 1);" title="Increase"><i class="fa fa-plus"></i></a>
                      @endif
                    </td>
                    <td scope="col" class="carton-qty-cell" id="td-b2-{{ $rowKey }}">
                      <input id="rec2{{ $rowKey }}" class="form-control receiveqty2" type="number"
                        min="{{ $min2 }}" max="{{ $q2 }}" value="{{ $q2 }}" readonly
                        name="pb[{{ $productRows }}][receiveqty_box_2]" />
                      @if($q2 > 0)
                        <a href="javascript:;" class="carton-qty-btn" onclick="cartonAdjustBox(this, 2, -1);" title="Decrease"><i class="fa fa-minus"></i></a>
                        <a href="javascript:;" class="carton-qty-btn" onclick="cartonAdjustBox(this, 2, 1);" title="Increase"><i class="fa fa-plus"></i></a>
                      @endif
                    </td>
                    <td scope="col" id="rate1{{ $rowKey }}"><input class="form-control" value="{{ $pots->potable->box1_rate }}" name="pb[{{ $productRows }}][box1_rate]" readonly /></td>
                    <td scope="col" id="rate2{{ $rowKey }}"><input class="form-control" value="{{ $pots->potable->box2_rate }}" name="pb[{{ $productRows }}][box2_rate]" readonly /></td>
                  @endif
                  <td scope="col" id="amount{{ $rowKey }}"><input value="{{ $pots->amount }}" class="form-control amount" name="pb[{{ $productRows }}][amount]" readonly /></td>
                  <td scope="col" id="gstslab{{ $rowKey }}"><input class="form-control" value="{{ $pots->potable->gstslab }}" name="pb[{{ $productRows }}][gstslab]" readonly /></td>
                  <td scope="col" id="gstamount{{ $rowKey }}"><input value="0" class="form-control gstamount" name="pb[{{ $productRows }}][gstamount]" readonly /></td>
                </tr>
                @endforeach
              </tbody>
            </table>
          </div>

            <!-- forth row -->
          <div class="row mt-3">
            <div class="col-4">
                <label class="control-label">{{ __('Total Quantity') }}</label>
                <input type="number" class="form-control" name="pbQty" id="pbQty" value="{{$supplierInvoicecar->tquantity}}" readonly />
            </div>
            <div class="col-4">
                <label class="control-label">{{ __('Sub-Total Amount') }}</label>
                <input type="number" class="form-control" name="pbSubTotal" id="pbSubTotal"  value="{{$supplierInvoicecar->subTotal}}" readonly />
            </div>
            <div class="col-4">
                <label class="control-label">{{ __('GST') }}</label>
                <input type="number" class="form-control" name="pbGST" id="pbGST" value="{{$supplierInvoicecar->tgst}}" readonly />
            </div>
          </div>

          <div class="row mt-3">
            
            <div class="col-4">
                <label class="control-label">{{ __('Total Amount') }}</label>
                <input type="number" class="form-control" name="pbTotal" id="pbTotal"  value="{{$supplierInvoicecar->tamount}}"  readonly />
                <input type="text" name="type" id='invoiceTotal' value="" hidden/>
                {{-- <input type="text" id="print"> --}}
            </div>

            
            <div class="col-4">
              <label class="control-label">{{ __('Total TDS') }}</label>
              <input type="number" class="form-control" name="tdsTotal" id="tdsToal" readonly />
              <input type="number" class="form-control" name="" id="tdsPercent" value="{{$userSplier->tdspercent}}" hidden />
              <input type="date" class="form-control" name="" id="tdsDate" value="{{$userSplier->tdsdate}}" hidden />
   
              {{-- <input type="text" id="print"> --}}
          </div>
          </div>
        <div class="row col-4">
          <button type="submit" id="hidden" hidden class="btn btn-primary mt-3" disabled>Create Purchase Bill</button>
          <button type="submit" id="formSubmit" form="createPurchaseBill" class="btn btn-primary mt-3">Approve</button>
        </div>

      </div>
    </form>  
</div>
@endsection

@section('footer')
@include('partials.supplier-invoice-tds-js')
<!-- Script start for get data from database -->
<script type="text/javascript">
  
  $(document).ready(function(){

    $.ajax({
      'url': "{{ url('/supplier-dashboard/data') }}",
      'method': 'GET'

    }).done(function(data){
        if (data) {
          purchaseOrder = data.purchaseOrder;
          supplier = data.supplier;
          pbProduct = data.product;
        }
      });

    @if ($type == 3)
    $('#productTable tr.supplier-invoice-product-row').each(function () {
      var rk = $(this).data('row-key');
      if (rk && $('#rec1' + rk).length) {
        updateCartonRowAmounts(rk);
      }
    });
    calculateTotalCarton();
    @endif
    SupplierInvoiceTds.bindInvoiceDate();
  });

  var purchaseOrder = [];
  var supplier = [];
  var pbProduct = [];
  var pbPoTable = [];
  var productRows = 0;
  
  function changeDetails(ref){
    var id = $(ref).val();
    $("#productTable tr").remove();

    $.ajax({
      'url': "{{ url('/supplier-dashboard/data/pbPoTable')}}" + '/' +id,
      'method': 'GET'

    }).done(function(data){
        if (data) {
          pbPoTable = data.poTable;
        }
      });

    function findPo(purchaseBill){
      return purchaseBill.id == id;
    };

    function findSupplier(supplierName){
      return supplierName.id == supplierId;
    };

    var purchaseBill = purchaseOrder.find(findPo);
    var supplierId = purchaseBill.supplier_id;
    var supplierName = supplier.find(findSupplier);
    $("#supplierName").val(supplierName.c_name);
    $("#del_date").val(purchaseBill.del_date);
    $("#Supplier_ref").val(purchaseBill.ref_supplier);
    $("#remarks").val(purchaseBill.remarks);
    $("#total_qty").val(purchaseBill.tquantity);
    $("#total_amount").val(purchaseBill.tamount);
    $("#myInput").removeAttr('disabled');
    $("#supinv").select().focus();

  };


  function handleTypeRestriction(input, dataType) {
    let val = input.value;

    if (dataType === 'int') {
        if (val.includes('.')) {
            input.value = Math.floor(val); 
        }
    }
}

  function recalQuantity(ref){

    let currentDate = new Date($('#invoice_date').val());
    console.log($('#invoice_date').val(),'currentDate');
    
    if(!currentDate || currentDate == 'Invalid Date' || currentDate == ''){
      alert('Please select Invoice Date');
      ref.value = 0;
      return;
    }
    quantity = Number($(ref).val(),10);
    var rqean = $(ref).parent().attr('id');
    //if(quantity>0){
        var remqty = $('#remqty'+rqean+' input').val(); //MYcode
        var rate = $('#rate'+rqean+' input').val();
        var gstslab = $('#gstslab'+rqean+' input').val();
        var oldrate = rate * quantity;
        $("#amount"+rqean+" input").val(oldrate);
        var gstamount = (oldrate * gstslab)/100;
        $('#gstamount'+rqean+' input').val(gstamount.toFixed(2));
        $("#myInput").select().focus();
        
        calculateTotal();
        
    //}

    // else{
    //   alert("Quantity can not be less than 1.");
    //   $("#myInput").select().focus();
    // }
  };

  function deleteRow(ref) {
    $(ref).parents("tr").remove();
    $("#myInput").select().focus();
    calculateTotal();
  };

  function calculateTotal(){
    var arrTotQty = [];
    var arrSubTot = [];
    var arrgstamount = [];
    var totQty = 0;
    var subTot = 0;
    var gstamount = 0;

    arrTotQty = $('.receiveqty');
    arrSubTot = $('.amount');
    arrgstamount = $('.gstamount');

    $.each(arrTotQty,function(index, value){
      // totQty += parseInt(arrTotQty[index].value,10);
        const val = parseFloat(arrTotQty[index].value);
  if (!isNaN(val)) {
    totQty += val;

  }
    });
    totQty = Number(totQty.toFixed(10)).toString();


    $.each(arrSubTot,function(index, value){
      subTot += parseFloat(arrSubTot[index].value,10);
    });

    $.each(arrgstamount,function(index, value){
      gstamount += parseFloat(arrgstamount[index].value,10);
    });
    
    //var  freightRate = parseFloat($('#freight').val(),10);
    var pbTotal = subTot+gstamount;
    gstamount = gstamount.toFixed(2);
    subTot = subTot.toFixed(2);
    pbTotal = parseFloat(pbTotal).toFixed(2);
    $('#pbSubTotal').val(subTot);
    $('#pbQty').val(totQty);
    $('#pbGST').val(gstamount);
    $('#pbTotal').val(pbTotal);

    
    var potamount = $('#pbTotal').val();
    console.log(potamount);
    console.log(document.getElementById('invoiceTotal').value);
    
    
    let totalAmomunt = Number(document.getElementById('invoiceTotal').value) + Number(potamount);
    // document.getElementById('print').value = totalAmomunt;
      if(totalAmomunt > 100000){
        $('#ewaybill').attr('required', true);
        $('#eway_file').attr('required', true);
      }
      else{
          $('#ewaybill').removeAttr('required', false);
      }
      handleTds(document.getElementById('pbSubTotal').value);
  }

  function updateCartonRowAmounts(rowKey) {
    var quantity1 = parseInt($('#rec1' + rowKey).val(), 10);
    if (isNaN(quantity1)) {
      quantity1 = 0;
    }
    var quantity2 = parseInt($('#rec2' + rowKey).val(), 10);
    if (isNaN(quantity2)) {
      quantity2 = 0;
    }
    var rate1 = parseFloat($('#rate1' + rowKey + ' input').val()) || 0;
    var rate2 = parseFloat($('#rate2' + rowKey + ' input').val()) || 0;
    var gstslab = parseFloat($('#gstslab' + rowKey + ' input').val()) || 0;
    var totoldrate = rate1 * quantity1 + rate2 * quantity2;
    $('#amount' + rowKey + ' input').val(totoldrate.toFixed(2));
    var gstamount = (totoldrate * gstslab) / 100;
    $('#gstamount' + rowKey + ' input').val(gstamount.toFixed(2));
  }

  function cartonAdjustBox(anchor, box, delta) {
    var $tr = $(anchor).closest('tr');
    var rowKey = $tr.data('row-key');
    if (!rowKey) {
      return;
    }
    var $inp = box === 1 ? $('#rec1' + rowKey) : $('#rec2' + rowKey);
    if (!$inp.length) {
      return;
    }
    var max = parseInt($inp.attr('max'), 10);
    if (isNaN(max)) {
      max = 0;
    }
    var v = parseInt($inp.val(), 10);
    if (isNaN(v)) {
      v = max > 0 ? 1 : 0;
    }
    var min = max > 0 ? 1 : 0;
    var nv = v + delta;
    if (nv > max) {
      nv = max;
    }
    if (nv < min) {
      nv = min;
    }
    $inp.val(nv);
    recalQuantityCarton($inp[0]);
  }

  function recalQuantityCarton(ref){
    var rowKey = $(ref).closest('tr').data('row-key');
    if (!rowKey) {
      return;
    }
    updateCartonRowAmounts(rowKey);
    calculateTotalCarton();
  };

  function calculateTotalCarton(){
    var arrTotQty1 = [];
    var arrTotQty2 = [];
    var arrSubTot = [];
    var arrgstamount = [];
    var totQty = 0;
    var subTot = 0;
    var gstamount = 0;

    arrTotQty1 = $('#productTable input.receiveqty1[type="number"]');
    arrTotQty2 = $('#productTable input.receiveqty2[type="number"]');
    arrSubTot = $('#productTable .amount');
    arrgstamount = $('#productTable .gstamount');

    $.each(arrTotQty1,function(index, value){
      var n = parseInt(arrTotQty1[index].value, 10);
      if (!isNaN(n)) {
        totQty += n;
      }
    });

    $.each(arrTotQty2,function(index, value){
      var n = parseInt(arrTotQty2[index].value, 10);
      if (!isNaN(n)) {
        totQty += n;
      }
    });

    $.each(arrSubTot,function(index, value){
      var f = parseFloat(arrSubTot[index].value, 10);
      if (!isNaN(f)) {
        subTot += f;
      }
    });

    $.each(arrgstamount,function(index, value){
      var f = parseFloat(arrgstamount[index].value, 10);
      if (!isNaN(f)) {
        gstamount += f;
      }
    });
    
    //var  freightRate = parseFloat($('#freight').val(),10);
    var pbTotal = subTot+gstamount;
    gstamount = gstamount.toFixed(2);
    subTot = subTot.toFixed(2);
    pbTotal = parseFloat(pbTotal).toFixed(2);
    $('#pbSubTotal').val(subTot);
    $('#pbQty').val(totQty);
    $('#pbGST').val(gstamount);
    $('#pbTotal').val(pbTotal);
    
     var potamount = parseFloat($('#pbTotal').val(), 10) || 0;
      var invEl = document.getElementById('invoiceTotal');
      var invExtra = invEl && invEl.value !== '' ? parseFloat(invEl.value, 10) || 0 : 0;
      var totalForEway = invExtra + potamount;
      if (totalForEway > 100000) {
        $('#ewaybill').attr('required', true);
        if ($('#eway_file').length) {
          $('#eway_file').attr('required', true);
        }
      } else {
        $('#ewaybill').removeAttr('required');
        if ($('#eway_file').length) {
          $('#eway_file').removeAttr('required');
        }
      }
      if (document.getElementById('pbSubTotal')) {
        handleTds(document.getElementById('pbSubTotal').value);
      }
  }

  $('#createPurchaseBill').on('submit', function(){
    if($('#productTable tr').length<1) {
      alert( "No product added. Add atleast 1 product." );
      event.preventDefault();
    } else{
        $('#formSubmit').prop('disabled', 'true');
    }
  });

  function searchProduct(ref){
    
    if (event.keyCode === 13) {
      var ean = $(ref).val();

      function findPbProduct(poProduct){
        return poProduct.EAN == ean;
      };

      function checkProduct(productCheck){
        return productCheck.product_id == poProductId;
      };

      var poProduct = pbProduct.find(findPbProduct);

      if(poProduct){
        var poProductId = poProduct.id;
        var productCheck = pbPoTable.find(checkProduct);
        

        if(productCheck){
          var productCheckRemQty = productCheck.remqty;
          if(productCheckRemQty>0)
          {
            var productCheckQuantity = productCheck.quantity;
            var quantity= 1;
            if($('#'+ean).length){
              quantity=parseInt($('#'+ean+' input').val(),10);
              var gstslab = $('#gstslab'+ean+' input').val();
              var newQuantity = quantity+1;
              if(newQuantity<=productCheckRemQty){
                $('#'+ean+' input').val(newQuantity);
                $('#amount'+ean+' input').val(newQuantity*productCheck.rate);
                var amount = $('#amount'+ean+' input').val();
                var gstamount = (amount * gstslab)/100;
                $('#gstamount'+ean+' input').val(gstamount.toFixed(2));
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
                $block += '<td><select type="text" class="selectpicker" data-live-search="true" name="pb['+productRows+'][product]" readonly><option value="'+poProduct.id+'">' +poProduct.name+ '</option></td>';
                $block += '<td><input type="text" class="form-control" name="pb['+productRows+'][EAN]" readonly value="'+poProduct.EAN+'" /></td>';
                $block += '<td id="remqty'+ean+'"><input type="number" class="form-control remqty" data-len="'+productRows+'" name="pb['+productRows+'][remqty]" value="'+productCheck.remqty+'" readonly/></td>'
                $block += '<td id='+ean+'><input type="number" min="1" class="form-control receiveqty" data-len="'+productRows+'" name="pb['+productRows+'][receiveqty]" value="1" readonly/><a  href="javascript:;" onclick="reduceQuantity(this);"><i class="fa fa-minus"></i></a></td>'
                $block += '<td id="rate'+ean+'"><input type="number" class="form-control rate" name="pb['+productRows+'][rate]" readonly value="'+productCheck.rate+'" /></td>';
                $block += '<td id=amount'+ean+'><input type="number" class="form-control amount" name="pb['+productRows+'][amount]" readonly value="'+productCheck.rate+'" /></td>';
                $block += '<td id="gstslab'+ean+'"><input type="number" class="form-control gstslab" name="pb['+productRows+'][gstslab]" readonly value="'+productCheck.gstslab+'" /></td>';
                $block += '<td id="gstamount'+ean+'"><input type="number" class="form-control gstamount" name="pb['+productRows+'][gstamount]" readonly value="'+((productCheck.gstslab*productCheck.rate/100).toFixed(2))+'" /></td>';
				$block += '<td id="location'+ean+'"><input type="text" class="form-control location" name="pb['+productRows+'][location]" value="" /></td>';
                $block += '<td><button type="button" class="close" onclick="deleteRow(this);" data-bs-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button></td>';
                $block += '</tr>';
                $("#productTable").append($block);
                $('.selectpicker').selectpicker();
                calculateTotal();
              
            }
          }
          else{
            alert('Product Remaining Quantity is Zero');
          }
        }

        else{
          alert('Product NOT Found in this Inward Supply');
         
        } 
      }

      else{
          alert('Product NOT Found');
          
      }

      $("#myInput").select().focus();
    };
  };


</script>
<!-- Script end -->
@endsection