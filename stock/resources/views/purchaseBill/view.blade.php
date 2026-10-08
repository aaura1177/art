@extends('layouts.app')

@section('content')

  <div class="mx-2">
    <div class="row mx-0 my-2">
        <h2>Edit Inward Supply</h2>
    </div>

    <form method="POST" action="{{ url('/purchaseBill/view/'.$purchaseBill->purchaseOrder_id)}}">
      @csrf
  
      <!-- Form Starts -->
      <div class="form-group"> 
          
        <!-- first row -->
          <div class="row mt-3">  
            <div class="col-6">
              <table class="table table-borderless">
                <tr>
                  <td>
                    <h5>Purchase Order No. #<span id="poid">{{$purchaseBill->purchaseOrder_id}}</span></h5>
                    <h6>
                      Total Ordered Quantity: {{$purchaseBill->purchaseOrder->tquantity}} <br>
                      Sub-total Amount: ₹ {{$purchaseBill->purchaseOrder->subTotal}}
                    </h6>
                  </td>
                </tr>
              </table>
            </div>

            <div class="col-6">
              <table class="table table-borderless">
                <tr>
                  <td>
                    Supplier Name: {{$purchaseBill->purchaseOrder->supplier->c_name}} <br>
                    Supplier Reference: {{$purchaseBill->purchaseOrder->ref_supplier}} <br>
                    E-Way Bill No: {{$purchaseBill->ewaybill}}<br>
                    Delivery Date: {{$purchaseBill->purchaseOrder->del_date}}
                  </td>
                </tr>
              </table>
            </div>
          </div>
          

          <!-- forth row -->
          <div class="row mt-3">
            <div class="col-8">
              <div class="input-group">
                <span class="input-group-addon" style="border: 1px solid #ccc; padding: 0.4rem;"><i class="fa fa-barcode"></i></span>
                <input id="myInput" type="text" class="form-control" name="enterproductname" onkeyup="searchProduct(this);" placeholder="Enter Product name / SKU / Scan bar code" autofocus autocomplete="off"/>
              </div>
            </div>
          </div>
          <div class="row mt-3">
            <table class="table table-hover">
              <thead>
                <tr id="mytable">
                  <th scope="col" style="min-width: 200px;" >Product</th>
                  <th scope="col">EAN</th>
                  <th scope="col">Quantity</th>
                  <th scope="col">Rate/Item (₹)</th>
                  <th scope="col">Amount (₹)</th>
                  <th></th>
                </tr>
              </thead>
              <tbody id="productTable">
                @if(isset($pbTable)) @foreach($pbTable as $key => $pbTable)
                  <tr>
                    <td>
                      <select type="text" class="selectpicker" data-live-search="true" name="pb[{{$key}}][product]" readonly>
                        <option value="{{$pbTable->product_id}}">{{$pbTable->product->name}}</option>
                      </select>
                    </td>
                    <td>
                      <input type="text" class="form-control" name="pb[{{$key}}][EAN]" readonly value="{{$pbTable->EAN}}" />
                    </td>
                    <td id={{$pbTable->EAN}}>
                      <input type="number" class="form-control quantity" name="pb[{{$key}}][quantity]" readonly value="{{$pbTable->quantity}}" />
                      <a  href="javascript:;" onclick="reduceQuantity(this);">
                        <i class="fa fa-minus"></i>
                      </a>
                    </td>
                    <td>
                      <input type="number" class="form-control" name="pb[{{$key}}][rate]" readonly value="{{$pbTable->rate}}" />
                    </td>
                    <td id=amount{{$pbTable->EAN}}>
                      <input type="number" class="form-control amount" name="pb[{{$key}}][amount]" readonly value="{{$pbTable->amount}}" />
                    </td>
                  </tr>

                @endforeach @endif

              </tbody>
            </table>
          </div>

            <!-- forth row -->
          <div class="row mt-3">
            <div class="col-4">
                <label class="control-label">{{ __('Total Quantity') }}</label>
                <input type="number" class="form-control" name="pbQty" id="pbQty" value="{{$purchaseBill->quantity}}" readonly />
            </div>
            <div class="col-4">
                <label class="control-label">{{ __('Sub-Total Amount') }}</label>
                <input type="number" class="form-control" name="pbSubTotal" id="pbSubTotal" value="{{$purchaseBill->subtotal}}" readonly />
            </div>
            <div class="col-4">
                <label class="control-label">{{ __('GST') }}</label>
                <input type="number" class="form-control" name="pbGST" id="pbGST" value="{{$purchaseBill->gst}}" readonly />
            </div>
          </div>

          <div class="row mt-3">
            <div class="col-4">
                <label class="control-label">{{ __('Total Amount') }}</label>
                <input type="number" class="form-control" name="pbTotal" id="pbTotal" value="{{$purchaseBill->total}}" readonly />
            </div>
            <!-- <div class="col-4">
                <input type="number" hidden class="form-control" name="pbOldQty" id="pbOldQty" value="{{$purchaseBill->quantity}}"/>
            </div> -->
          </div>
        <div class="row col-4">
          <button type="submit" id="hidden" hidden class="btn btn-primary mt-3" disabled>Update Purchase Bill</button>
          <button type="submit" id="formSubmit" class="btn btn-primary mt-3">Update Inward Supply</button>
        </div>

      </div>
    </form>  
</div>
@endsection

@section('footer')

<!-- Script start for get data from database -->
<script type="text/javascript">
  
  $(document).ready(function(){

    $.ajax({
      'url': "{{ url('/purchaseBill/viewdata') }}",
      'method': 'GET'

    }).done(function(data){
        if (data) {
          pbProduct = data.product;
        }
      });

    var id = $('#poid').text();
    

    $.ajax({
      'url': "{{ url('/purchaseBill/data/pbPoTable')}}" + '/' +id,
      'method': 'GET'

    }).done(function(data){
        if (data) {
          pbPoTable = data.poTable;
        }
      });

  });

  var pbProduct = [];
  var pbPoTable = [];
  var trcount = $('#productTable tr').length;
  var productRows = trcount+1;
  
  function reduceQuantity(ref){
    quantity = parseInt($(ref).siblings('input').val(),10);
    if(quantity>1){
      newQuantity = quantity-1;
      $(ref).siblings('input').val(newQuantity);
      var getRate = $(ref).parent().next().children('input').val();
      $(ref).parent().next().next().children('input').val(newQuantity*getRate);
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
  };

  function calculateTotal(){
    var arrTotQty = [];
    var arrSubTot = [];
    var totQty = 0;
    var subTot = 0;

    arrTotQty = $('.quantity');
    arrSubTot = $('.amount');

    $.each(arrTotQty,function(index, value){
      totQty += parseInt(arrTotQty[index].value,10);
    });

    $.each(arrSubTot,function(index, value){
      subTot += parseFloat(arrSubTot[index].value,10);
    });
    
    var gst = subTot*0.18;
    var pbTotal = subTot+gst;
    gst = gst.toFixed(2);
    subTot = subTot.toFixed(2);
    pbTotal = pbTotal.toFixed(2);
    $('#pbSubTotal').val(subTot);
    $('#pbQty').val(totQty);
    $('#pbGST').val(gst);
    $('#pbTotal').val(pbTotal);
  }

  function validateSubmit(){
    event.preventDefault();
  }


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
          var productCheckQuantity = productCheck.quantity;
          var quantity= 1;
          if($('#'+ean).length){
            quantity=parseInt($('#'+ean+' input').val(),10);
            var newQuantity = quantity+1;
            if(newQuantity<=productCheckQuantity){
              $('#'+ean+' input').val(newQuantity);
              $('#amount'+ean+' input').val(newQuantity*productCheck.rate);
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
              $block += '<td id='+ean+'><input type="number" class="form-control quantity" name="pb['+productRows+'][quantity]" readonly value="'+quantity+'" /><a  href="javascript:;" onclick="reduceQuantity(this);"><i class="fa fa-minus"></i></a></td>';
              $block += '<td><input type="number" class="form-control" name="pb['+productRows+'][rate]" readonly value="'+productCheck.rate+'" /></td>';
              $block += '<td id=amount'+ean+'><input type="number" class="form-control amount" name="pb['+productRows+'][amount]" readonly value="'+productCheck.rate*quantity+'" /></td>';
              $block += '<td><button type="button" class="close" onclick="deleteRow(this);" data-bs-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button></td>';
              $block += '</tr>';
              $("#productTable").append($block);

              calculateTotal();
            
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

