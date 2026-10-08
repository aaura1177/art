@extends('layouts.app')

@section('content')

  <div class="mx-2">
    <div class="row mx-0 my-2">
        <h2>Edit Packing Sheet</h2>
    </div>

    <form method="POST" action="{{ url('/packingSheet/view/'.$packingSheet->invoice_id)}}">
      @csrf

      <!-- Form Starts -->
      <div class="form-group"> 
          
          <!-- first row -->
          <div class="row mt-3">  
            <div class="col-6">
              <table class="table table-borderless">
                <tr>
                  <td>
                    <h5>Invoice No. #<span id="invoice_id">{{$packingSheet->invoice_id}}</span></h5>
                    <h6>
                      Total Ordered Quantity: {{$packingSheet->invoice->totalquantity}} <br>
                      Total Amount: ₹ {{$packingSheet->invoice->totalamount}}
                    </h6>
                  </td>
                </tr>
              </table>
            </div>

            <div class="col-6">
              <table class="table table-borderless">
                <tr>
                  <td>
                    Buyer Order No: {{$packingSheet->invoice->buyerorderno}} <br>
                    Totol Boxes: {{$packingSheet->invoice->totalbox}} <br>
                    Container No: {{$packingSheet->invoice->containerno}}<br>
                    Vehicle No: {{$packingSheet->invoice->vehicleno}}
                  </td>
                </tr>
              </table>
            </div>
          </div>

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
                    <th scope="col" style="min-width:150px;">Product</th>
                    <th scope="col">HSN/SAC</th>
                    <th scope="col">QTY</th>
                    <th scope="col">Box</th>
                    <th scope="col">Qty/Box</th>
                    <th scope="col">Net Wt (Kg)</th>
                    <th scope="col">Gross Wt (Kg)</th>
                  </tr>
                </thead>
                <tbody id="productInvoice">  
                  @if(isset($psTable)) @foreach($psTable as $key => $psTable)
                  <tr>
                    <td>
                      <select type="text" class="form-control" name="ps[{{$key}}][product]" readonly>
                        <option value="{{$psTable->product_id}}">{{$psTable->product->name}}</option>
                      </select>
                    </td>
                    <td>
                      <input type="text" class="form-control" name="ps[{{$key}}][EAN]" value="{{$psTable->EAN}}" readonly="" />
                    </td>
                    <td id="{{$psTable->EAN}}">
                      <input type="number" min="1" class="form-control quantity" data-len="{{$key}}" name="ps[{{$key}}][quantity]" value="{{$psTable->quantity}}" readonly/>
                      <a  href="javascript:;" onclick="reduceQuantity(this);">
                        <i class="fa fa-minus"></i>
                      </a>
                    </td>
                    <td>
                      <input type="number" step="any" class="form-control box" onchange="calculateTotal();" name="ps[{{$key}}][box]" value="{{$psTable->box}}" min="1" />
                    </td>
                    <td>
                      <input type="text" class="form-control qtybox" name="ps[{{$key}}][qtybox]" value="{{$psTable->qtybox}}"/>
                    </td>
                    <td>
                      <input type="number" step="any" class="form-control netwt" onchange="calculateTotal();" name="ps[{{$key}}][netwt]" value="{{$psTable->netwt}}" min="0" />
                    </td>
                    <td>
                      <input type="number" step="any" class="form-control grosswt" name="ps[{{$key}}][grosswt]" onchange="calculateTotal();" value="{{$psTable->grosswt}}" min="0" />
                    </td>
                  </tr>
                  @endforeach @endif
                </tbody>
            </table>
          </div>

          <!-- eighth row -->
          <div class="row mt-3">                     
                <div class="col-4">
                    <label class="control-label">{{ __('Total Boxes') }}</label>
                    <input type="text" class="form-control" step="any" name="totalbox" id="psBox" value="{{$packingSheet->totalbox}}" readonly />
                </div>
                <div class="col-4">
                    <label class="control-label">{{ __('Total Quantity') }}</label>
                    <input type="number" class="form-control" name="quantity" id="psQty" value="{{$packingSheet->quantity}}" readonly />
                </div>
                <div class="col-4">
                    <label class="control-label">{{ __('Total Net Weight (kg)') }}</label>
                    <input type="number" class="form-control" step="any" name="netwt" id="psNetWt" value="{{$packingSheet->netwt}}" readonly />
                </div>
          </div>

          <!-- ninth row -->
          <div class="row mt-3">                     
            <div class="col-4">
                <label class="control-label">{{ __('Total Gross Weight (kg)') }}</label>
                <input type="number" class="form-control" name="grosswt" id="psGrossWt" value="{{$packingSheet->grosswt}}" readonly />
            </div>
          </div>   

          <div class="row col-4">
                <button type="submit" id="hidden" hidden class="btn btn-primary mt-3" disabled>Update Packing Sheet</button>
                <button type="submit" class="btn btn-primary mt-3">Update Packing Sheet</button>
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
      'url': "{{ url('/packingSheet/viewdata') }}",
      'method': 'GET'
    }).done(function(data){
        if (data) {
          psProduct = data.product;
        }
      });

    var id = $('#invoice_id').text();

    $.ajax({
      'url': "{{ url('/packingSheet/data/psInvoiceTable')}}" + '/' +id,
      'method': 'GET'

    }).done(function(data){
        if (data) {
          psInvoiceTable = data.invoiceTable;
        }
      });
  });

  var trcount = $('#productInvoice tr').length;
  var productRows = trcount+1;
  var psProduct = [];
  var invoicePoTable = [];

  function validateSubmit(){
    event.preventDefault();
  }

  function reduceQuantity(ref){
    quantity = parseInt($(ref).siblings('input').val(),10);
    if(quantity>1){
      newQuantity = quantity-1;
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
    var arrbox = [];
    var arrNetWt = [];
    var arrGrossWt = [];
    var totQty = 0;
    var totBox = 0;
    var totNetWt = 0;
    var totGrossWt = 0;

    arrTotQty = $('.quantity');
    arrbox = $('.box');
    arrNetWt = $('.netwt');
    arrGrossWt = $('.grosswt');

    $.each(arrTotQty,function(index, value){
      totQty += parseInt(arrTotQty[index].value,10);
    });

    $.each(arrbox,function(index, value){
      totBox += parseFloat(arrbox[index].value,10);
    });

    $.each(arrNetWt,function(index, value){
      totNetWt += parseFloat(arrNetWt[index].value,10);
    });

    $.each(arrGrossWt,function(index, value){
      totGrossWt += parseFloat(arrGrossWt[index].value,10);
    });
    
    
   
    $('#psBox').val(totBox);
    $('#psQty').val(totQty);
    $('#psNetWt').val(totNetWt);
    $('#psGrossWt').val(totGrossWt);
  }


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
          var productCheckQuantity = productCheck.quantity;
          var quantity= 1;
          if($('#'+ean).length){
            quantity=parseInt($('#'+ean+' input').val(),10);
            var newQuantity = quantity+1;
            if(newQuantity<=productCheckQuantity){
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
              $block += '<td><select type="text" class="form-control" name="ps['+productRows+'][product]" readonly><option value="'+invoiceProduct.id+'">' +invoiceProduct.name+ '</option></td>';
              $block += '<td><input type="text" class="form-control" name="ps['+productRows+'][EAN]" value="'+invoiceProduct.EAN+'" readonly="" /></td>'
              $block += '<td id='+ean+'><input type="number" min="1" class="form-control quantity" name="ps['+productRows+'][quantity]" value="1" readonly/><a  href="javascript:;" onclick="reduceQuantity(this);"><i class="fa fa-minus"></i></a></td>'
              $block += '<td><input type="number" step="any" class="form-control box" onchange="calculateTotal();" name="ps['+productRows+'][box]" value="1" min="1" /></td>'
              $block += '<td><input type="text" class="form-control qtybox" name="ps['+productRows+'][qtybox]" value="1 Pc/Box"/></td>'
              $block += '<td><input type="number" step="any" class="form-control netwt" onchange="calculateTotal();" data-len="' + productRows + '" name="ps['+productRows+'][netwt]" value="0.00" min="0" /></td>'
              $block += '<td><input type="number" step="any" class="form-control grosswt" name="ps['+productRows+'][grosswt]" onchange="calculateTotal();" value="0.00" min="0" /></td>'
              $block += '<td><button type="button" class="close" onclick="deleterowInvoice(this);" data-bs-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button></td>';
              $block += '</tr>';
              $("#productInvoice").append($block);

              calculateTotal();
            
          }

        }

        else{
          alert('Product NOT Found in this Packing Sheet');
         
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
