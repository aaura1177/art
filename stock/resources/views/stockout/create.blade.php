@extends('layouts.app')

@section('content')

  <div class="mx-2">
    <div class="row mx-0 my-2">
        <h2>Add StockOut</h2>
    </div>

    <form id='createStockOut' method="POST" action="{{ url('/stockout/create') }}">
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

            <div class="col-4">
              <label class="control-label">{{ __('Buyer Ref. No.') }}</label>
              <input type="text" class="form-control" name="buyerorderno" id="buyerorderno" readonly />
            </div>
          </div>

          <!-- second row -->
          <div class="row mt-3">  
            <div class="col-4">
              <label class="control-label">{{ __('Container No.') }}</label>
              <input type="text" class="form-control" name="containerno" id="containerno" readonly/>
            </div>
            <div class="col-4">
              <label class="control-label">{{ __('Vehicle No.') }}</label>
              <input type="text" class="form-control" name="vehicleno" id="vehicleno" readonly/>
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
                    <th scope="col">Remaining QTY</th>
                    <th scope="col">Receive QTY</th>
                    <th scope="col">Location</th>
                  </tr>
                </thead>
                <tbody id="productInvoice">  
                </tbody>
            </table>
          </div>

          <div class="row col-4">
                <button type="submit" id="hidden" hidden class="btn btn-primary mt-3" disabled>Create Purchase Bill</button>
                <button type="submit" class="btn btn-primary mt-3" id='formSubmit' form='createStockOut' >StockOut</button>
          </div>
      
      </div>  
    </form>  
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
  
  function changeDetails(ref){
    var id = $(ref).val();
    $("#productInvoice tr").remove();

    $.ajax({
      'url': "{{ url('/packingSheet/data/psInvoiceTable') }}"+"/"+id,
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
    $("#buyerorderno").val(invoice.buyerorderno);
    $("#totalbox").val(invoice.totalbox);
    $("#containerno").val(invoice.containerno);
    $("#vehicleno").val(invoice.vehicleno);

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

      function checkProduct(productCheck){
        return productCheck.product_id == psProductId;
      };


      var invoiceProduct = psProduct.find(findInvoiceProduct);


      if(invoiceProduct){
        var psProductId = invoiceProduct.id;
 
        
        var productCheck = psInvoiceTable.find(checkProduct);


        if(productCheck){
          var productCheckRemQty = productCheck.remqty;
          if(productCheckRemQty>0)
          {
            var productCheckQuantity = productCheck.quantity;
            var quantity= 1;

            if($('#'+ean).length){
              quantity=parseInt($('#'+ean+' input').val(),10);
              var newQuantity = quantity+1;
              if(newQuantity<=productCheckRemQty){
                $('#'+ean+' input').val(newQuantity);
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
                $block += '<td id="location-'+ean+'"><input type="text" min="1" class="form-control location" data-len="' + productRows + '" name="ps['+productRows+'][location]"  /></td>'
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

      else{
          alert('Product NOT Found');
          
      }

      $("#myInput").select().focus();
    };
  };

</script>  
<!-- Script End -->

@endsection