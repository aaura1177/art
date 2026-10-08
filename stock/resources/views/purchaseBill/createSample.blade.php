@extends('layouts.app')

@section('content')

  <div class="mx-2">
    <div class="row mx-0 my-2">
        <h2>Add Sample Inward Supply</h2>
    </div>

    <form id='createPurchaseBill' method="POST" action="{{ url('/purchaseBill/createSample') }}">
      @csrf
  
      <!-- Form Starts -->
      <div class="form-group"> 
          
        <!-- first row -->
          <div class="row mt-3">  
            <div class="col-4">
              <label class="control-label">{{ __('PO No.') }}</label> <a href="{{ url('/purchaseOrder/createSamplePo')}}" style="float: right;" target="_blank"> (+New)</a>
              <select type="text" class="selectpicker" data-live-search="true" name="purchaseOrder_id" onchange="changeDetails(this);" required="required">
                <option value="" selected disabled>Select PO No.</option>
                @if(isset($samplePurchaseOrder)) @foreach($samplePurchaseOrder as $key => $samplePurchaseOrder)
                  <option value="{{$samplePurchaseOrder->id}}">
                  {{$samplePurchaseOrder->pono}}
                  </option>
                @endforeach @endif
              </select>
            </div>
            <div class="col-4">
              <label class="control-label">{{ __('Supplier Name') }}</label>
              <input type="text" class="form-control" name="supplierName" id="supplierName" readonly />
            </div>
            <div class="col-4">
              <label class="control-label">{{ __('Delivery Date') }}</label>
              <input type="date" class="form-control" name="del_date"  id="del_date" readonly />
            </div> 
          </div>

          <!-- second row -->
          <div class="row mt-3">                     
            <div class="col-4">
                <label class="control-label">{{ __('Supplier Ref.') }}</label>
                <input type="text" class="form-control" name="Supplier_ref" id="Supplier_ref" readonly />
            </div>
            <div class="col-4">
                <label class="control-label">{{ __('Ordered Quantity') }}</label>
                <input type="number" class="form-control" name="poQty" id="total_qty" readonly />
            </div>
            <div class="col-4">
                <label class="control-label">{{ __('Sub Total Amount') }}</label>
                <input type="number" class="form-control" name="poAmount" id="total_amount" readonly />
            </div>                                    
          </div>
            
          <!-- forth row -->
          <div class="row mt-3">
            <div class="col-8">
                <label class="control-label">{{ __('Remark') }}</label>
                <textarea class="form-control" name="remark" id="remarks" readonly></textarea>
            </div>
          </div>

          <!-- second row -->
          <div class="row mt-3">                     
            <div class="col-4">
                <label class="control-label">{{ __('Supplier Invoice No.') }}</label>
                <input type="text" class="form-control toUpperCase" id="supinv" name="supp_inv_no" required />
            </div>
            <div class="col-4">
                <label class="control-label">{{ __('Supplier Invoice Date.') }}</label>
                <input type="date" class="form-control" name="supp_inv_date" required />
            </div> 
            <div class="col-4">
                <label class="control-label">{{ __('E-Way Bill No.') }}</label>
                <input type="text" class="form-control toUpperCase" name="ewaybill" id="ewaybill"/>
            </div>                                    
          </div>
          
          <!-- forth row -->
          <div class="row mt-3">
            <div class="col-8">
              <div class="input-group">
                <span class="input-group-addon" style="border: 1px solid #ccc; padding: 0.4rem;"><i class="fa fa-barcode"></i></span>
                <input id="myInput" type="text" class="form-control" name="enterproductname" onkeyup="searchProduct(this);" placeholder="Enter Sample Product name / SKU / Scan bar code" disabled autocomplete="off"/>
              </div>
            </div>
          </div>
          <div class="row mt-3">
            <table class="table table-hover">
              <thead>
                <tr id="mytable">
                  <th scope="col" style="min-width: 300px;">Product</th>
                  <th scope="col">Code</th>
                  <th scope="col">Remaining QTY</th>
                  <th scope="col">Receive QTY</th>
                  <th scope="col">Rate/Item (₹)</th>
                  <th scope="col">Amount (₹)</th>
                  <th scope="col">GSTSLAB</th>
                  <th scope="col">GST (₹)</th>
                  <th scope="col">Location</th>
                  <th></th>
                </tr>
              </thead>
              <tbody id="productTable">

              </tbody>
            </table>
          </div>

            <!-- forth row -->
          <div class="row mt-3">
            <div class="col-4">
                <label class="control-label">{{ __('Total Quantity') }}</label>
                <input type="number" class="form-control" name="pbQty" id="pbQty" readonly />
            </div>
            <div class="col-4">
                <label class="control-label">{{ __('Sub-Total Amount') }}</label>
                <input type="number" class="form-control" name="pbSubTotal" id="pbSubTotal" readonly />
            </div>
            <div class="col-4">
                <label class="control-label">{{ __('GST') }}</label>
                <input type="number" class="form-control" name="pbGST" id="pbGST" readonly />
            </div>
          </div>

          <div class="row mt-3">
            <div class="col-4">
                <label class="control-label">{{ __('Freight') }}</label>
                <input type="number" class="form-control" onchange="calculateTotal();" name="freight" id="freight" value='0' step="any" min='0' required/>
            </div>
            <div class="col-4">
                <label class="control-label">{{ __('Total Amount') }}</label>
                <input type="number" class="form-control" name="pbTotal" id="pbTotal" readonly />
            </div>
          </div>
        <div class="row col-4">
          <button type="submit" id="hidden" hidden class="btn btn-primary mt-3" disabled>Create Purchase Bill</button>
          <button type="submit" id="formSubmit" form="createPurchaseBill" class="btn btn-primary mt-3" >Create Inward Supply</button>
        </div>

      </div>
    </form>  
</div>
@endsection

@section('footer')
hi
<!-- Script start for get data from database -->
<script type="text/javascript">
  
  $(document).ready(function(){

    $.ajax({
      'url': "{{ url('/purchaseBill/dataSample') }}",
      'method': 'GET'

    }).done(function(data){
        if (data) {
          purchaseOrder = data.purchaseOrder;
          supplier = data.supplier;
          pbProduct = data.sample; 
               console.log(pbProduct)  ;  
        }
      });
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
      'url': "{{ url('/purchaseBill/data/pbPosTable')}}" + '/' +id,
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

  function reduceQuantity(ref){
    quantity = parseInt($(ref).siblings('input').val(),10);
    var rqean = $(ref).parent().attr('id');
    if(quantity>1){
        newQuantity = quantity-1;
        $(ref).siblings('input').val(newQuantity);
        var remqty = $('#remqty'+rqean+' input').val(); //MYcode
        var rate = $('#rate'+rqean+' input').val();
        var gstslab = $('#gstslab'+rqean+' input').val();
        var remainingqty = remqty - newQuantity;
        var oldrate = rate * newQuantity;
        $("#remainingqty"+rqean+" input").val(remainingqty);
        $("#amount"+rqean+" input").val(oldrate);
        var gstamount = (oldrate * gstslab)/100;
        $('#gstamount'+rqean+' input').val(gstamount.toFixed(2));
        $("#myInput").select().focus();
        calculateTotal();
    }

    else{
      alert("Quantity can not be less than 1.");
      $("#myInput").select().focus();
    }
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
      totQty += parseInt(arrTotQty[index].value,10);
    });

    $.each(arrSubTot,function(index, value){
      subTot += parseFloat(arrSubTot[index].value,10);
    });

    $.each(arrgstamount,function(index, value){
      gstamount += parseFloat(arrgstamount[index].value,10);
    });
    
    var  freightRate = parseFloat($('#freight').val(),10);
    var pbTotal = subTot+gstamount+freightRate;
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
        return poProduct.code == ean;        
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
              if(poProduct.ean == null){
                poProduct.ean = '';
              }

              var $block = "";
                $block += '<tr>';
                $block += '<td><select type="text" class="selectpicker" data-live-search="true" name="pb['+productRows+'][product]" readonly><option value="'+poProduct.id+'">' +poProduct.name+ '</option></td>';
                $block += '<td><input type="text" class="form-control" name="pb['+productRows+'][EAN]" readonly value="'+poProduct.code+'" /></td>';
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