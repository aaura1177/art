@extends('layouts.app')

@section('content')

  <div class="mx-2">
    <div class="row mx-0 my-2">
        <h2>Edit  Invoice</h2>
        {{-- {{$userSplier->supplier->tdspercent}} --}}
    </div>

    <style>
        .carton-product-label {
            max-width: 240px;
            white-space: pre-line;
            word-break: break-word;
            overflow-wrap: anywhere;
            vertical-align: top;
        }
    </style>

 
      <form id='createPurchaseBill' method="POST" action="{{ url('/supplier-dashboard/update_invoice_carton') }}/{{$supplierInvoice->id }}" multiform enctype="multipart/form-data">
        @csrf
        <input type="hidden" name="purchase_order_id" value="{{ $id }}" />  
        <input type="hidden" name="typeUnit" value="{{$type}}" />  
      <!-- Form Starts -->
      <div class="form-group"> 
          
        <!-- first row -->
          <div class="row mt-3">  
            <div class="col-4">
              <label class="control-label">{{ __('PO No.') }}</label> 
              <select type="text" class="selectpicker" data-live-search="true" name="purchaseOrder_id" onchange="changeDetails(this);" required="required">
                <option value="" disabled>Select PO No.</option>
                @if(isset($purchaseOrder))
                  <option selected value="{{$purchaseOrder->id}}">
                  {{$purchaseOrder->pono}}
                  </option>
                @endif
              </select>
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
                <input type="number" class="form-control" name="poQty" id="total_qty" readonly value = "{{$supplierInvoice->tquantity}}" />
            </div>
            <div class="col-4">
                <label class="control-label">{{ __('Sub Total Amount') }}</label>
                <input type="number" class="form-control" name="poAmount" id="total_amount" readonly value = "{{$supplierInvoice->subTotal}}" />
            </div>                                    
          </div>
            
          <!-- forth row -->
          <div class="row mt-3">
            <div class="col-8">
                <label class="control-label">{{ __('Remark') }}</label>
                <textarea class="form-control" name="remark" id="remarks" readonly >{{$purchaseOrder->remarks}}</textarea>
            </div>
          </div>

          <!-- second row -->
          <div class="row mt-3">                     
            <div class="col-4">
                <label class="control-label">{{ __('Supplier Invoice No.') }}</label>
                <input type="text" class="form-control toUpperCase" value="{{$supplierInvoice->supplier_invoice_number}}"   id="supinv" name="supp_inv_no" required />
            </div>
            
            <div class="col-4">
                <label class="control-label">{{ __('E-Way Bill No.') }}</label>
                <input type="text" class="form-control toUpperCase" value="{{$supplierInvoice->eway_bill_no}}"   name="ewaybill" id="ewaybill"/>
            </div>   
            
            <div class="col-4">
              <label class="control-label">{{ __('Vehicle No.') }}</label>
              <input type="text" class="form-control toUpperCase" value="{{$supplierInvoice->vehicle_no}}"  name="vehicle_no" id="vehicle_no"/>
            </div>      
          </div>

          <!-- third row -->
          <div class="row mt-3">                    
            
            <div class="col-4">
              <label class="control-label">{{ __('Supplier Invoice Date') }}</label>
              <input type="date" class="form-control"  required name="invoice_date" value="{{$supplierInvoice->invoice_date}}" id="invoice_date" max="<?php echo date('Y-m-d') ?>" min="<?php echo date('Y-m-d', strtotime('-30 days')) ?>"/>
            </div>      
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
                    <th scope="col">Remaining QTY Box 1</th>
                    <th scope="col">Remaining QTY Box 2</th>
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
                  $popTable = \App\popTable::where('poid', $pots->purchase_order_id)
                      ->where('product_id', $pots->product_id)
                      ->first();
                  $box1max = $pots->quantity + ($popTable->remqty_box1 ?? 0);
                  $box2max = $pots->quantity2 + ($popTable->remqty_box2 ?? 0);
                @endphp
                <tr id="mytable">
                  <?php $productRows++; ?>
                  <td scope="col" class="carton-product-label">
                    @if(isset($pots->product_id))
                      {{ \App\Support\CartonProductLabel::fromPopTable($popTable, $pots->product) }}
                    @endif

                    @if(isset($pots->product_id)) 
                      <input name="pb[{{$productRows}}][product]" type="hidden" value="{{$pots->product_id}}" />
                    @else
                      <input name="pb[{{$productRows}}][product]" type="hidden" value="{{$pots->consumable_id}}" />
                    @endif
                  </td>
            @if($type == 3)
                    <td scope="col">{{$popTable->remqty_box1}}</td>
                    <td scope="col">{{$popTable->remqty_box2}}</td>
                    <td scope="col" id="{{md5($p)}}"><input id="rec1{{md5($p)}}" class="form-control receiveqty1" value="{{$pots->quantity}}" min="0" max="{{$box1max}}" name="pb[{{$productRows}}][receiveqty_box_1]" type="number" onchange="recalQuantityCarton(this);" /></td>
                    <td scope="col" id="{{md5($p)}}"><input id="rec2{{md5($p)}}" class="form-control receiveqty2" value="{{$pots->quantity2}}" min="0" max="{{$box2max}}" name="pb[{{$productRows}}][receiveqty_box_2]" type="number" onchange="recalQuantityCarton(this);" /></td>
                    <td scope="col" id="rate1{{md5($p)}}"><input class="form-control" value="{{$popTable->box1_rate}}" name="pb[{{$productRows}}][box1_rate]" readonly /></td>
                    <td scope="col" id="rate2{{md5($p)}}"><input class="form-control" value="{{$popTable->box2_rate}}" name="pb[{{$productRows}}][box2_rate]" readonly /></td>
                  @endif
                  <td scope="col" id="amount{{md5($p)}}"><input class="form-control amount" value="{{$pots->amount}}" name="pb[{{$productRows}}][amount]" readonly /></td>
                  <td scope="col" id="gstslab{{md5($p)}}"><input class="form-control" value="{{$pots->gst}}" name="pb[{{$productRows}}][gstslab]" readonly /></td>
                  <td scope="col" id="gstamount{{md5($p)}}"><input value="{{ number_format((($pots->amount ?? 0) * ($pots->gst ?? 0)) / 100, 2, '.', '') }}" class="form-control gstamount" name="pb[{{$productRows}}][gstamount]" readonly /></td>
                </tr>
                @endforeach
              </tbody>
            </table>
          </div>

            <!-- forth row -->
          <div class="row mt-3">
            <div class="col-4">
                <label class="control-label">{{ __('Total Quantity') }}</label>
                <input type="number" class="form-control" name="pbQty" id="pbQty" value="{{$supplierInvoice->pbQty}}" readonly />
            </div>
            <div class="col-4">
                <label class="control-label">{{ __('Sub-Total Amount') }}</label>
                <input type="number" class="form-control" name="pbSubTotal" value="{{$supplierInvoice->pbSubTotal}}" id="pbSubTotal" readonly />
            </div>
            <div class="col-4">
                <label class="control-label">{{ __('GST') }}</label>
                <input type="number" class="form-control" name="pbGST" value="{{$supplierInvoice->pbGST}}" id="pbGST" readonly />
            </div>
          </div>

          <div class="row mt-3">
            
            <div class="col-4">
                <label class="control-label">{{ __('Total Amount') }}</label>
                <input type="number" class="form-control" name="pbTotal" id="pbTotal" value="{{$supplierInvoice->tamount}}" readonly />
                <input type="text" name="type" id='invoiceTotal' value="{{$invoiceTotal}}" hidden/>
                {{-- <input type="text" id="print"> --}}
            </div>

            
            <div class="col-4">
              <label class="control-label">{{ __('Total TDS') }}</label>
              <input type="number" class="form-control" name="tdsTotal" id="tdsToal" readonly />
              <input type="number" class="form-control" name="" id="tdsPercent" value="{{$userSplier->supplier->tdspercent}}" hidden />
              <input type="date" class="form-control" name="" id="tdsDate" value="{{$userSplier->supplier->tdsdate}}" hidden />
   
              {{-- <input type="text" id="print"> --}}
          </div>
          </div>
        <div class="row col-4">
          <button type="submit" id="hidden" hidden class="btn btn-primary mt-3" disabled>Create Purchase Bill</button>
          <button type="submit" id="formSubmit" form="createPurchaseBill" class="btn btn-primary mt-3" >Raise Invoice</button>
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

    // Recompute row + footer totals for preloaded carton lines.
    calculateTotalCarton();
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

  function recalQuantityCarton(ref){
    quantity = parseInt($(ref).val(),10);
    var rqean = $(ref).parent().attr('id');
    quantity1 = parseInt($('#rec1'+rqean).val(),10);
    quantity2 = parseInt($('#rec2'+rqean).val(),10);
    //if(quantity>0){
        var remqty = $('#remqty'+rqean+' input').val(); //MYcode
        var rate1 = $('#rate1'+rqean+' input').val();
        var rate2 = $('#rate2'+rqean+' input').val();
        var gstslab = $('#gstslab'+rqean+' input').val();
        var oldrate1 = rate1 * quantity1;
        var oldrate2 = rate2 * quantity2;
        var totoldrate = oldrate1 + oldrate2;
        $("#amount"+rqean+" input").val(totoldrate.toFixed(2));
        var gstamount = (totoldrate * gstslab)/100;
        $('#gstamount'+rqean+' input').val(gstamount.toFixed(2));
        $("#myInput").select().focus();
        
        calculateTotalCarton();
    //}

    // else{
    //   alert("Quantity can not be less than 1.");
    //   $("#myInput").select().focus();
    // }
  };

  function calculateTotalCarton(){
    var arrTotQty1 = [];
    var arrTotQty2 = [];
    var arrSubTot = [];
    var arrgstamount = [];
    var totQty = 0;
    var subTot = 0;
    var gstamount = 0;

    arrTotQty1 = $('.receiveqty1');
    arrTotQty2 = $('.receiveqty2');
    arrSubTot = $('.amount');
    arrgstamount = $('.gstamount');

    $.each(arrTotQty1,function(index, value){
      totQty += parseInt(arrTotQty1[index].value,10);
    });

    $.each(arrTotQty2,function(index, value){
      totQty += parseInt(arrTotQty2[index].value,10);
    });

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
      if(potamount > 100000){
        $('#ewaybill').attr('required', true);
      }
      else{
          $('#ewaybill').removeAttr('required', false);
      }
      handleTds($('#pbSubTotal').val() || 0);
  }

  $('#createPurchaseBill').on('submit', function(event) {
    if (typeof calculateTotalCarton === 'function') {
      calculateTotalCarton();
    }
    var qtyExceeded = false;

    $('#productTable input[name*="[receiveqty]"], #productTable input[name*="[receiveqty_box_1]"], #productTable input[name*="[receiveqty_box_2]"]').each(function() {
      var max = parseFloat($(this).attr('max'));
      var val = parseFloat($(this).val()) || 0;
      if (!isNaN(max) && val > max) {
        qtyExceeded = true;
        return false;
      }
    });

    if (qtyExceeded) {
      alert("Receive quantity cannot be greater than remaining quantity.");
      event.preventDefault();
      return;
    }

    if ($('#productTable tr').length < 1) {
      alert("No product added. Add atleast 1 product.");
      event.preventDefault();
    } else {
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