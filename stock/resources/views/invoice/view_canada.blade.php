@extends('layouts.app')

@section('content')

  <div class="mx-2">
    <div class="row mx-0 my-2">
        <h2>Update Canada invoice</h2>
    </div>

    <form method="POST" action="{{ url('/invoice/view-canada/'.$invoice->id)}}">
      @csrf
      <input type="hidden" name="invoice_id" value="{{$invoice->id}}" />
      <!-- Form Starts -->
      <div class="form-group"> 
          
          <!-- first row -->
          <div class="row mt-3">
            <div class="col-4">
              <label class="control-label">{{ __('Invoice No.') }}</label>
              <input type="text" class="form-control" name="invoiceno" required="required" value="{{$invoice->invoiceno}}"  />
            </div>
            <div class="col-4">
              <label class="control-label">{{ __('Invoice Date') }}</label>
              <input type="date" class="form-control" name="date" value="{{$invoice->date}}" required="required" />                     
            </div>
            <div class="col-4">
              <label class="control-label">{{ __('Invoice Type') }}</label>
              <select type="text" class="selectpicker" data-live-search="true" onchange="invtype(this);" name="invoicetype" required="required">
                  <option value="0" {{($invoice->invoicetype == 0)?'selected':''}}>Export Invoice</option>
                  <option value="1" {{($invoice->invoicetype == 1)?'selected':''}}>Commercial Invoice</option>
              </select> 
            </div>
          </div>

          

          <!-- second row -->
          <div class="row mt-3">  
            <div class="col-4">
              <label class="control-label">{{ __('BuyerOrder No.') }}</label>
              <input type="text" class="form-control toUpperCase" name="buyerorderno" value="{{$invoice->buyerorderno}}" required="required" readonly />
            </div>
            <div class="col-4">
              <label class="control-label">{{ __('Container No.') }}</label>
              <input type="text" class="form-control toUpperCase" name="containerno" value="{{$invoice->containerno}}" required="required" />
            </div>
            
            <div class="col-4">
              <label class="control-label">{{ __('Container Size') }}</label>
              <input type="text" class="form-control" name="container_size" value="{{$invoice->container_size}}" required="required" />
            </div>
          </div>  

          <!-- third row -->
          <div class="row mt-3">
            <div class="col-4">
              <label class="control-label">{{ __('Consignee') }}</label><a href="{{ url('/buyer/create')}}" style="float: right;" target="_blank"> (+New)</a>
              <select type="text" class="selectpicker" data-live-search="true" name="consignee" required="required">
                <option value="" selected disabled>Select Consignee</option>
                  @if(isset($consignee)) @foreach($consignee as $key => $consignee)
                    <option value="{{$consignee->id}}" {{($invoice->consignee_id == $consignee->id)?'selected':''}} >
                    {{$consignee->c_name}}
                    </option>
                  @endforeach @endif
              </select>
            </div>
            <div class="col-4">
              <label class="control-label">{{ __('Buyer') }}</label><a href="{{ url('/buyer/create')}}" style="float: right;" target="_blank"> (+New)</a>
              <select type="text" class="selectpicker" data-live-search="true" name="buyer_id" required="required">
                <option value="" selected disabled>Select Buyer</option>
                  @if(isset($buyer)) @foreach($buyer as $key => $buyer)
                    <option value="{{$buyer->id}}" {{($invoice->buyer_id == $buyer->id)?'selected':''}}>
                    {{$buyer->c_name}}
                    </option>
                  @endforeach @endif
              </select>
            </div>
            <div class="col-4">
              <label class="control-label">{{ __('Kind of Pkgs') }}</label>
              <input type="text" class="form-control" name="pkgs" value="{{$invoice->pkgs}}" required="required" />
            </div>
            
          </div>

          <!-- third row -->
          <div class="row mt-3">
            <div class="col-4">
              <label class="control-label">{{ __('Deposit received on') }}</label>
              <input type="date" class="form-control" name="deposit_date" value="{{$invoice->deposit_date}}" />
            </div>
            <div class="col-4">
              <label class="control-label">{{ __('Deposit') }}</label>
              <input type="number" step="any" class="form-control" name="deposit" value="{{$invoice->deposit}}" />
            </div>
            <div class="col-4">
              <label class="control-label">{{ __('Shipping Agent') }}</label>
              <input type="text" class="form-control" name="agent_name" value="{{$invoice->agent_name}}" />
            </div>
          </div>
          
          <!-- third row -->
          <div class="row mt-3">  
            <div class="col-4">
                <label class="control-label">{{ __('Currency') }}</label>
                <select type="text" class="selectpicker" data-live-search="true" id="changecurrency" name="currency" required="required">
                  <option value="" disabled>Select Currency</option>
                  <option value="$" {{($invoice->currency == "$")?'selected':''}}>$</option>
                  <option value="€" {{($invoice->currency == "€")?'selected':''}}>€</option>
                  <option value="£" {{($invoice->currency == "£")?'selected':''}}>£</option>
                  <option value="₹" {{($invoice->currency == "₹")?'selected':''}}>₹</option>
                  <option value="C$" {{($invoice->currency == "C$")?'selected':''}}>C$</option>
                </select>                  
            </div>
              
          </div>
              
          <!-- Separator -->    
          <div class="row mt-4">
            <div class="col-12 mt-4"><h4>Terms of Delivery<hr></h4></div>
          </div>

          <!-- fourth row -->
          <div class="row mt-0">  
            
            <div class="col-4">
                <label class="control-label">{{ __('Payment Term') }}</label>
                <input type="text" class="form-control" name="payterms" required="required" value="{{$invoice->payterms}}" />
            </div>
            <div class="col-4">
                <label class="control-label">{{ __('Shipment') }}</label>
                <input type="text" class="form-control" name="shipmentby" required="required" value="{{$invoice->shipmentby}}" />
            </div>                           
          </div>  

          <!-- fifth row -->
          <div class="row mt-3">  
            <div class="col-8">
                <label class="control-label">{{ __('Description of Goods') }}</label>
                <textarea class="form-control" name="desgoods" required="required">{{$invoice->desgoods}}</textarea>
            </div>                           
          </div> 

          <!-- fifth row -->
          <div class="row mt-3">  
            <div class="col-8">
                <label class="control-label">{{ __('Delivery Terms') }}</label>
                <textarea class="form-control" name="delivery_term" required="required">{{$invoice->delivery_term}}</textarea>
            </div>
          </div> 

          <!-- Separator -->
          <div class="row mt-4">
            <div class="col-12 mt-4"><h4>Shipping Details<hr></h4></div>
          </div>

          <!-- sixth row -->
          <div class="row mt-0">  
            <div class="col-4">
                <label class="control-label">{{ __('Pre-Carriage by') }}</label>
                <input type="text" class="form-control" name="carriage" value="{{$invoice->carriage}}" required="required" />
            </div> 
            <div class="col-4">
                <label class="control-label">{{ __('Place of receipt by PreCarrier') }}</label>
                <input type="text" class="form-control" name="receipt" value="{{$invoice->receipt}}" required="required" />
            </div>
            <div class="col-4">
                <label class="control-label">{{ __('Shipment Under') }}</label>
                <input type="text" class="form-control" name="shipment" value="{{$invoice->shipment}}" readonly />
            </div>                           
          </div> 
              
           <!-- seventh row -->
          <div class="row mt-3">  
            <div class="col-4">
                <label class="control-label">{{ __('Port of Loading') }}</label>
                <input type="text" class="form-control" name="postloading" value="{{$invoice->postloading}}" required="required" />
            </div> 
            <div class="col-4">
                <label class="control-label">{{ __('Port of Discharge') }}</label>
                <input type="text" class="form-control" name="discharge" value="{{$invoice->discharge}}" required="required" />
            </div>
            <div class="col-4">
                <label class="control-label">{{ __('Final Destination') }}</label>
                <input type="text" class="form-control" name="destination" value="{{$invoice->destination}}" required="required" />
            </div>                           
          </div>

			<div class="row mt-3">  
				<div class="col-6">
					<label class="control-label">{{ __('Additional Information') }}</label>
					<input type="text" class="form-control" name="additional_info" value="{{$invoice->additional_info}}" />
				</div> 
			</div>		  

          <!-- Product Details-->
          <div class="row mt-5 my-3">                                       
              <div class="col-6">
                <h5>Products Details</h5>
              </div>
          </div>
          <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                  <tr id="mytable">
                    <th scope="col" style="min-width: 300px;">Product <a href="{{ url('/product/create')}}" target="_blank"> (+New)</a></th>
                    <th scope="col" style="min-width: 100px;">QTY</th>
                    <th scope="col" style="min-width: 130px;">Rate</th>
                    <th scope="col" style="min-width: 150px;">Amount</th>
                    <th scope="col" style="min-width: 120px;">Net Wt (Kg)</th>
                    <th scope="col" style="min-width: 150px;">Sub Total Net Wt (Kg)</th>
                    <th scope="col" style="min-width: 120px;">Gross Wt (Kg)</th>
                    <th scope="col" style="min-width: 150px;">Sub Total Gross Wt (Kg)</th>
                    <th scope="col" style="min-width: 100px;">Start Box</th>
                    <th scope="col" style="min-width: 100px;">End Box</th>
                    <th scope="col" style="min-width: 100px;">Sub Total Box</th>
                    <th scope="col" style="min-width: 100px;">PC/Box</th>
                    
                  </tr>
                </thead>
                <tbody id="productinvoice">  
                  @if(isset($invoiceTable)) @foreach($invoiceTable as $key => $invoiceTable)
                    <tr>
                      <td id="pr{{$invoiceTable->product->id}}">
                        <select type="text" class="selectpicker" data-live-search="true" data-len="{{$key+1}}" name="inv[{{$key+1}}][product]" required="required">
                          <option value="{{$invoiceTable->product_id}}"> 
                            {{$invoiceTable->product->code}} - {{$invoiceTable->product->name}}
                          </option>'
                        </select>
                        <textarea class="form-control" name="inv[{{$key+1}}][descriptionBox]" placeholder="Enter your description here...">{{$invoiceTable->descriptionBox}}</textarea>
                      </td>
                      <td id="quan{{$key+1}}">
                        <input type="number" min="1" style="width:60px;" class="form-control quantity" onchange="changeWeight(this);" data-len="{{$key+1}}" name="inv[{{$key+1}}][quantity]" value="{{$invoiceTable->quantity}}" />
                        <input type="hidden" data-len="{{$key+1}}" name="inv[{{$key+1}}][consumed]" value="{{$invoiceTable->quantity-$invoiceTable->remqty}}" />
                      </td>
                      <td id="rate{{$key+1}}">
                        <input type="number" min="0" step="any" class="form-control rate" onchange="changeWeight(this);" name="inv[{{$key+1}}][rate]" data-len="{{$key+1}}" value="{{$invoiceTable->rate}}" />
                      </td>
                      <td id="amount{{$key+1}}">
                        <input type="number" class="form-control amount" name="inv[{{$key+1}}][amount]" readonly value="{{$invoiceTable->amount}}" />
                      </td>
                      <td id="wt{{$key+1}}">
                        <input type="number" min="0" step="any" class="form-control weight" onchange="changeWeight(this);" data-len="{{$key+1}}" name="inv[{{$key+1}}][weight]" value="{{$invoiceTable->weight}}" />
                      </td>
                      <td id="subtotalnetwt{{$key+1}}">
                        <input type="number" min="0" step="any" class="form-control subtotalnetwt" onchange="changeWeight(this);" name="inv[{{$key+1}}][subtotalnetwt]" value="{{$invoiceTable->subtotalnetwt}}" readonly/>
                      </td>
                      <td id="grosswt{{$key+1}}">
                        <input type="number" min="0" step="any" class="form-control grosswt" onchange="changeWeight(this);" data-len="{{$key+1}}" name="inv[{{$key+1}}][grosswt]" value="{{$invoiceTable->grosswt}}" />
                      </td>
                      <td id="subTotalGrossWT{{$key+1}}">
                        <input type="number" min="0" step="any" class="form-control subTotalGrossWT" onchange="changeWeight(this);" name="inv[{{$key+1}}][subTotalGrossWT]" value="{{$invoiceTable->subtotalgrosswt}}" readonly/>
                      </td>
                      <td id="box{{$key+1}}">
                        <input type="number" min="0" class="form-control box" onchange="changeWeight(this);" data-len="{{$key+1}}" name="inv[{{$key+1}}][box]" value="{{$invoiceTable->box}}" />
                      </td>
                      <td id="endBox{{$key+1}}">
                        <input type="number" min="0" class="form-control endBox" onchange="changeWeight(this);" data-len="{{$key+1}}" name="inv[{{$key+1}}][endBox]" value="{{$invoiceTable->endbox}}" />
                      </td>
                      <td id="subTotalBox{{$key+1}}">
                        <input type="number" min="0" class="form-control subTotalBox" onchange="changeWeight(this);" name="inv[{{$key+1}}][subTotalBox]" value="{{$invoiceTable->subtotalbox}}" readonly/>
                      </td>
                       <td id="qtybox{{$key+1}}">
                        <input type="text" min="0" class="form-control qtybox" name="inv[{{$key+1}}][qtybox]" value="{{$invoiceTable->qtybox}}" />
                      </td>
                      
                      <td>
                        <button type="button" class="close" onclick="deleterowinvoice(this);" data-bs-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                      </td>
                    </tr>
                  @endforeach @endif
                </tbody>
            </table>
          </div>
          
          <div class="row mt-2">
              <div class="col">
                <input type="button" id="addProductinvoice" class="btn btn-primary" value="Add Product" />
              </div>
          </div>      

          <!-- eighth row -->
          <div class="row mt-3">
                <div class="col-4">
                  <label class="control-label">{{ __('Additional Shipping Charges')}}&nbsp;(<span class="changeCur">{{$invoice->currency}}</span>)</label>
                  <input type="number" class="form-control" name="shipping_charges" id="shipping_charges" step="any" min='0' onchange="changeWeight(this);" value="{{$invoice->shipping_charges}}" required/>
                </div>
                <div class="col-4">
                  <label class="control-label">{{ __('Additional Packing Charges')}}&nbsp;(<span class="changeCur">{{$invoice->currency}}</span>)</label>
                  <input type="number" class="form-control" name="packing_charges" id="packing_charges" step="any" min='0' onchange="changeWeight(this);" value="{{$invoice->packing_charges}}" required/>
                </div>
                <div class="col-4">
                  <label class="control-label">{{ __('Discount')}}&nbsp;(<span class="changeCur">{{$invoice->currency}}</span>)</label>
                  <input type="number" class="form-control" name="discount" id="discount" step="any" min='0' onchange="changeWeight(this);" value="{{$invoice->discount}}" required/>
                </div>
          </div>
          
          <!-- eighth row -->
          <div class="row mt-3">
                <div class="col-4">
                    <label class="control-label">{{ __('Total Amount') }}&nbsp;(<span class="changeCur">{{$invoice->currency}}</span>)</label>
                    <input type="number" class="form-control" name="totalamount" id="totalamount" readonly value="{{$invoice->totalamount}}" />
                </div>
                
          </div>

          <!-- eighth row -->
          <div class="row mt-3">
            <div class="col-4">
              <label class="control-label">{{ __('VAT') }}</label>
              <select id="vatoption" class="form-control">
                <option  value="Yes" {{($invoice->vatoption == "Yes")?'selected':''}}>Yes</option>
                <option value="No" {{($invoice->vatoption == "No")?'selected':''}}>No</option>
              </select>
            </div>
            <div class="col-4">
                <label class="control-label">{{ __('VAT') }}&nbsp;(<span class="changeCur">{{$invoice->currency}}</span>)</label>
                <input type="number" class="form-control" name="vat" id="vat" value="{{$invoice->vat}}" />
            </div>

            <div class="col-4">
              <label class="control-label">{{ __('Total Amount after VAT') }}&nbsp;(<span class="changeCur">{{$invoice->currency}}</span>)</label>
              <input type="number" step="any" class="form-control" name="total_after_vat" id="total_after_vat" value="{{$invoice->vat + $invoice->totalamount}}" readonly />
            </div>
            
          </div>

          <!-- ninth row -->
          <div class="row mt-3">                     
                <div class="col-4">
                    <label class="control-label">{{ __('Total Quantity') }}</label>
                    <input type="number" class="form-control" name="tquantity" id="tquantity" value="{{$invoice->totalquantity}}" readonly />
                </div>
                <div class="col-4">
                    <label class="control-label">{{ __('Net Weight (kg)') }}</label>
                    <input type="number" class="form-control" step="any" name="totalwt" id="totalwt" value="{{$invoice->totalwt}}" readonly />
                </div>
                <div class="col-4">
                    <label class="control-label">{{ __('Gross Wt(kg)') }}</label>
                    <input type="number" class="form-control" step="any" name="grosswt" id="grosswt" value="{{$invoice->totalgrosswt}}" readonly />
                </div>
          </div>

           <!-- tenth row -->
          <div class="row mt-3">                     
                <div class="col-4">
                  <label class="control-label">{{ __('Total Box') }}</label>
                  <input type="number" class="form-control" name="totalbox" id="totalbox" value="{{$invoice->totalbox}}" readonly />
                </div>
          </div>

          

          <div class="row col-4">
                <button type="submit" onclick="validateSubmit();" class="btn btn-primary mt-3">Update invoice</button>
          </div>
      
      </div>  
    </form>  
  </div>

@endsection

@section('footer')
<!-- Script start for Statecode & GSTIN button -->
<script>

    function invtype(reff) {
       var invtype = $(reff).val();
       
       if(invtype == '1'){
            $('select[name=currency]').val("₹").attr("readonly", "readonly");
            $('.selectpicker').selectpicker('refresh');
       }
       else{
            $('select[name=currency]').val("").removeAttr("readonly", "readonly");
            $('.selectpicker').selectpicker('refresh');
       }
   }

function showDescription(ref) {
  $('#descriptionBox'+ref).toggle();
  $('#plus'+ref).toggle();
  $('#minus'+ref).toggle();
}

$(document).ready(function() {

  $('#vatoption').on('change',function(){
    if($(this).val() == 'Yes'){
      $('#vat').removeAttr('readonly');
    }
    if($(this).val() == 'No'){
      $('#vat').attr('readonly','readonly');
      $('#vat').val('0');
    }
  });

  $('#vat').on('change',function(){
    var totvat = $('#totalamount').val() * ($('#vat').val()/100);
    var totamountaftervat = parseFloat($('#totalamount').val()) + parseFloat(totvat);
    $('#total_after_vat').val(parseFloat(totamountaftervat));
  });

  $('#totalamount').on('change',function(){
    var totvat = $('#totalamount').val() * ($('#vat').val()/100);
    var totamountaftervat = parseFloat($('#totalamount').val()) + parseFloat(totvat);
    $('#total_after_vat').val(parseFloat(totamountaftervat));
  });

  $.ajax({
      'url': "{{ url('/purchaseOrder/data') }}",
      'method': 'GET'
  }).done(function(data) {
      if (data) {
          products = data.product;
      }
    });
});

var trcount = $('#productinvoice tr').length;
var productRows = trcount;
var products = [];

function validateSubmit(){
  if($('#productinvoice tr').length<1) {
    alert( "No product added. Add atleast 1 product." );
    event.preventDefault();
  }

  else{
    var productTableRow = 0;
    $('#productinvoice select').each(function(){
      productTableRow++
      if(!$(this).val()){
        alert( "Product Row "+productTableRow+ " empty. Select a product or delete the row." );
        event.preventDefault();
        return false;
      }
    });
  }  
};

function changeHSN(ref) {
  var len = $(ref).data('len');
  var id = $(ref).val();

  function findProduct(product) {
    return product.id == id;
  }

   if($('#pr'+id).length){
      alert('product already added');
      $(ref).prop('selectedIndex',0);
      $(ref).parent().attr("id",'pr');
    }

    else{
      $(ref).parent().attr("id",'pr'+id);
      function findProduct(product) {
        return product.id == id;
      }

      var product = products.find(findProduct);
      $("#quan"+len+" input").attr("max", product.quantity);
    }
}

//Add Table
  function changeWeight(ref) {
    var len = $(ref).data('len');
    var quantity = $("#quan"+len+" input").val();
    var rate = $("#rate"+len+" input").val();
    var grosswt = $("#grosswt"+len+" input").val();
    var box = $("#box"+len+" input").val();
    var endBox = $("#endBox"+len+" input").val();
    var subTotalBox = (endBox-box)+1;
    var pcbox = quantity/subTotalBox;
    var netwt = $("#wt"+len+" input").val();
    var subtotalnetwt = netwt*quantity;
    subtotalnetwt = subtotalnetwt.toFixed(2);
    var subTotalGrossWT = grosswt*quantity;
    subTotalGrossWT = subTotalGrossWT.toFixed(2);
    var amount = rate*quantity;
    var amount = amount.toFixed(2);

    $("#amount"+len+" input").val(amount);
    $("#subtotalnetwt"+len+" input").val(subtotalnetwt);
    $("#subTotalBox"+len+" input").val(subTotalBox);
    $("#qtybox"+len+" input").val(pcbox);
    $("#subTotalGrossWT"+len+" input").val(subTotalGrossWT);

    var arrq = document.getElementsByClassName('quantity');
    var arrgtotamt = document.getElementsByClassName('amount');
    var arrwt = document.getElementsByClassName('subtotalnetwt');
    var arrgrosswt = document.getElementsByClassName('subTotalGrossWT');;
    var arrbox = document.getElementsByClassName('subTotalBox');

    var totq = 0;
    var totamt = 0;
    var totalamount = 0;
    var totwt = 0;
    var totgrosswt = 0;
    var totalbox = 0;
    

      for(var i=0;i<arrq.length;i++){
          if(parseFloat(arrq[i].value))
              totq += parseInt(arrq[i].value);
      }
          document.getElementById('tquantity').value = totq;

      

      for(var i=0;i<arrgrosswt.length;i++){
          if(parseFloat(arrgrosswt[i].value))
              totgrosswt += parseFloat(arrgrosswt[i].value);
      }
          document.getElementById('grosswt').value = totgrosswt.toFixed(2);

      

      for(var i=0;i<arrgtotamt.length;i++){
          if(parseFloat(arrgtotamt[i].value))
              totalamount += parseFloat(arrgtotamt[i].value);
              totamt = totalamount;
      }
          var shipping_charges = parseFloat($('#shipping_charges').val());
          var packing_charges = parseFloat($('#packing_charges').val());
          var discount = parseFloat($('#discount').val());
          var totalamount = (shipping_charges + packing_charges + totalamount) - discount;
          document.getElementById('totalamount').value = totalamount.toFixed(2);
          $('#totalamount').trigger('change');

      for(var i=0;i<arrwt.length;i++){
          if(parseFloat(arrwt[i].value))
              totwt += parseFloat(arrwt[i].value);
      }
          document.getElementById('totalwt').value = totwt.toFixed(2);

       for(var i=0;i<arrbox.length;i++){
          if(parseInt(arrbox[i].value))
              totalbox += parseInt(arrbox[i].value);
      }
          document.getElementById('totalbox').value = totalbox;    

      var totalamount = $("#totalamount").val();
      var output = parseFloat(totalamount);
      var totalamount = parseFloat(output).toFixed(2);
      
  }

  function recalculate(){
    var arrtr = $('#productinvoice tr').length;
    console.log(arrtr);
    if(arrtr>0){
      for( var i=1;i<=arrtr;i++){ 
         var amount = $("#amount"+i+" input").val();
         amount = parseFloat(amount).toFixed(2);
      }
      
      var totalAmount = parseFloat($('#totalamount').val()).toFixed(2);
      var totalAmountInr = parseFloat(totalAmount).toFixed(2);
      
    }
  }

function deleterowinvoice(ref) {
  $(ref).parents("tr").remove();
  changeWeight();
}

$("#addProductinvoice").click(function() {
  var options = '<option selected disabled>-- SELECT PRODUCT --</option>';
  
    productRows += 1;

    $.each(products, function(index, value) {
        options += '<option value="' + value.id + '">' + value.code + " - " + value.name + '</option>';
    });

  var $block = "";
      $block += '<tr>';
      $block += '<td id="pr">'
      $block += '<select type="text" class="selectpicker" data-live-search="true" onchange="changeHSN(this);" data-len="' + productRows + '" name="inv['+productRows+'][product]" required="required">';
      $block += options + '</select><span id="plus'+productRows+'" onclick="showDescription('+productRows+')" style="cursor: pointer;">(+)</span><span id="minus'+productRows+'" onclick="showDescription('+productRows+')" style="cursor:pointer; display:none;">(-)</span><textarea class="form-control" id="descriptionBox'+productRows+'" style="display:none;" rows="2" name="inv['+productRows+'][descriptionBox]"/></textarea></td>';
      $block += '<td id="quan'+productRows+'"><input type="number" min="1" style="width:60px;" class="form-control quantity" onchange="changeWeight(this);" data-len="' + productRows + '" name="inv['+productRows+'][quantity]" value="1" /></td>'
      $block += '<td id="rate'+productRows+'"><input type="number" step="any" min="0" class="form-control" onchange="changeWeight(this);" data-len="' + productRows + '" name="inv['+productRows+'][rate]" value="0.00" required /></td>'
      $block += '<td id="amount'+productRows+'"><input type="number" class="form-control amount" name="inv['+productRows+'][amount]" value="0.00" readonly/></td>'
      $block += '<td id="wt'+productRows+'"><input type="number" step="any" min="0" class="form-control weight" onchange="changeWeight(this);" data-len="' + productRows + '" name="inv['+productRows+'][weight]" value="0" required /></td>'
      $block += '<td id="subtotalnetwt'+productRows+'"><input type="number" step="any" min="0" class="form-control subtotalnetwt" onchange="changeWeight(this);" data-len="' + productRows + '" name="inv['+productRows+'][subtotalnetwt]" value="0" readonly/></td>'
      $block += '<td id="grosswt'+productRows+'"><input type="number" min="0" step="any" class="form-control grosswt" onchange="changeWeight(this);" data-len="' + productRows + '" name="inv['+productRows+'][grosswt]" value="0" required /></td>'
      $block += '<td id="subTotalGrossWT'+productRows+'"><input type="number" step="any" min="0" class="form-control subTotalGrossWT" onchange="changeWeight(this);" data-len="' + productRows + '" name="inv['+productRows+'][subTotalGrossWT]" value="0" readonly/></td>' 
      $block += '<td id="box'+productRows+'"><input type="number" step="any" class="form-control box" onchange="changeWeight(this);" data-len="' + productRows + '" name="inv['+productRows+'][box]" value="0" min="0" required /></td>'
      $block += '<td id="endBox'+productRows+'"><input type="number" step="any" class="form-control endBox" onchange="changeWeight(this);" data-len="' + productRows + '" name="inv['+productRows+'][endBox]" value="0" min="0" required/></td>'
      $block += '<td id="subTotalBox'+productRows+'"><input type="number" step="any" class="form-control subTotalBox" onchange="changeWeight(this);" data-len="' + productRows + '" name="inv['+productRows+'][subTotalBox]" value="0" min="0" readonly/></td>'
      $block += '<td id="qtybox'+productRows+'"><input type="text" step="any" class="form-control qtybox" name="inv['+productRows+'][qtybox]" value="1 Pc/Box" min="0" /></td>'
      
      $block += '<td><button type="button" class="close" onclick="deleterowinvoice(this);" data-bs-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button><input type="hidden" data-len="' + productRows + '" name="inv['+productRows+'][consumed]" value="" /></td>';
      $block += '</tr>';
      $("#productinvoice").append($block);
      $('.selectpicker').selectpicker();
});

$("#changecurrency").change(function () {
  $(".changeCur").html($(this).val());
});

</script>  
<!-- Script End -->

@endsection
