@extends('layouts.app')

@section('content')

  <div class="mx-2">
    <div class="row mx-0 my-2">
        <h2>Sales Register</h2>
    </div>

    <form id='myForm' method="POST" action="{{ url('/invoice/store_sale_register') }}">
      @csrf
<input type="hidden" name="invoice_id" name="invoice_id" value="{{$invoices->id}}">
      <!-- Form Starts -->
      <div class="form-group">

          <!-- first row -->
          <div class="row mt-3">
            <div class="col-4">
              <label class="control-label">{{ __('Invoice No.') }} : {{$invoices->invoiceno}}</label>

            </div>


          </div>



          <!-- third row -->
          <div class="row mt-3">
            <div class="col-4">
              <label class="control-label">{{ __('BL No') }}</label>
              <input type="text" class="form-control" name="bl_no" value="{{$invoices->bl_no}}" />
            </div>
            <div class="col-4">
              <label class="control-label">{{ __('BL date') }}</label>
              <input type="date" class="form-control" name="bl_date" value="{{$invoices->bl_date}}" />
            </div>
            <div class="col-4">
              <label class="control-label">{{ __('Shipping Bill no') }}</label>
              <input type="text" class="form-control" name="shipping_bill_no" value="{{$invoices->shipping_bill_no}}" />
            </div>
          </div>

          <div class="row mt-3">

            <div class="col-4">
              <label class="control-label">{{ __('Shipping Bill date') }}</label>
              <input type="date" class="form-control" name="shipping_bill_date" value="{{$invoices->shipping_bill_date}}" />
            </div>
            <div class="col-4">
              <label class="control-label">{{ __('EGM No.') }}</label>
              <input type="text" class="form-control" name="egm_no" value="{{$invoices->egm_no}}" />
            </div>
            <div class="col-4">
              <label class="control-label">{{ __('EGM Date') }}</label>
              <input type="date" class="form-control" name="egm_date"  value="{{$invoices->egm_date}}"/>
            </div>
          </div>


          <div class="row mt-3">
            <div class="col-4">
              <label class="control-label">{{ __('Container ') }}</label>
              <input type="text" class="form-control" name="containerno" value="{{$invoices->containerno}}"/>
            </div>
            <div class="col-4">
              <label class="control-label">{{ __('Agent\'s Name') }}</label>
              <input type="text" class="form-control" name="agent_name"  value="{{$invoices->agent_name}}"/>
            </div>
            <div class="col-4">
              <label class="control-label">{{ __('ETA ') }}</label>
              <input type="date" class="form-control" name="eta"  value="{{$invoices->eta}}"/>
            </div>
          </div>
          <div class="row mt-3">

            <div class="col-4">
              <label class="control-label">{{ __('ETD ') }}</label>
              <input type="date" class="form-control" name="etd"  value="{{$invoices->etd}}"/>
            </div>
          </div>

          <!-- fifth row -->

          <!-- sixth row -->


          <!-- Separator -->


          <!-- fourth row -->


          <!-- fifth row -->


          <!-- Separator -->

          <!-- sixth row -->
          <br>
  <h4>Invoice Export Fields</h4>
  <hr>
           <!-- seventh row -->
           <div class="row">
             <input id="sb_inv_id_val{{$invoices->id}}" value="{{$invoices->id}}" type="hidden" class="form-control" name="id" />
             <div class="col-6">
               <label>BOOKING VALUE</label>
               <input id="booking_value{{$invoices->id}}" value="{{$invoices->booking_value}}" step="any" type="number" class="form-control" name="booking_value" />
             </div>
             <div class="col-6">
               <label>FBC</label>
               <input id="fbc{{$invoices->id}}" value="{{$invoices->fbc}}" type="text" class="form-control" name="fbc" />
             </div>
           </div>
           <div class="row">
                 <div class="col-6">
                   <label>SHIPPED ON</label>
                   <input id="shipdawn{{$invoices->id}}" type="date" value="{{$invoices->shipdawn}}" class="form-control" name="shipdawn" />
                 </div>
                 <div class="col-6">
                   <label>TAX TYPE</label>
                   <input id="tax_type{{$invoices->id}}" type="text" value="{{$invoices->tax_type}}" class="form-control" name="tax_type" value="IGST" />
                 </div>
           </div>

              <!-- Product details -->
           <div class="table-responsive" style="margin-top:20px;">
           <table class="table table-hover">
             <thead>
               <tr id="mytable{{$invoices->id}}">
                 <th scope="col" style="min-width: 100px;">Realisation Date</th>
                 <th scope="col" style="min-width: 100px;">Realisation FC</th>
                 <th scope="col" style="min-width: 100px;">Rate</th>
                 <th scope="col" style="min-width: 100px;">Bank Reference</th>
                 <th></th>
               </tr>
             </thead>

           <tbody id="invoiceTable{{$invoices->id}}">

              @if(isset($invoices->invexport)) @foreach($invoices->invexport as $key => $invExp)

                 <tr>
                   <td>
                     <input type="date" class="form-control realisation_date" name="ie[{{$key+1}}][realisation_date]" value="{{$invExp->realisation_date}}" />
                   </td>

                   <td id="realisation_fc{{$key+1}}">
                     <input type="text" class="form-control realisation_fc" name="ie[{{$key+1}}][realisation_fc]"  data-len="{{$key+1}}" value="{{$invExp->realisation_fc}}" />
                   </td>
                    <td>
                     <input type="number" class="form-control rate" name="ie[{{$key+1}}][rate]" value="{{$invExp->rate}}" />
                   </td>
                   <td>
                     <input type="text" class="form-control bank_reference" name="ie[{{$key+1}}][bank_reference]" value="{{$invExp->bank_reference}}" />
                   </td>
                   <td>
                       <a href="javascript:void(0);" class="close" onclick="deleteRow(this);" >&times;</a>
                   </td>

                 </tr>
              @endforeach @endif
             </tbody>

           </table>
           </div>
           <div class="row mt-2">
             <div class="col">
                 <input type="button" id="addMore{{$invoices->id}}" class="btn btn-primary" value="Add More" />
             </div>
 </div>

          <!-- Product Details-->




          <!-- eighth row -->


          <!-- eighth row -->


          <!-- ninth row -->


          <!-- tenth row -->


          <div class="row col-4">
                <button id='submitBtn' type="submit" form='myForm' class="btn btn-primary mt-3">Save</button>
          </div>
      </div>
    </form>
  </div>



@endsection

@section('footer')

<script>

function selBuyerOrderNo(){
	var packing = $('#buyer_order_sel').val();
	var buyer_order = $('#buyer_order_sel').find('option:selected').html();
	$('input[name=buyerorderno]').val(buyer_order);
	$('#selBuyerOrderNo').modal('hide');

	$.ajax({
		url: "{{ url('/invoice/packing_detail') }}/"+packing,
		method: 'GET',
		success: function(data){
			prods = data;
			productRows = 0;
			var $block = "";
			$.each(prods, function(index, value) {
				productRows++;
				  $block += '<tr>';
				  $block += '<td id="pr">'
				  $block += '<select type="text" class="selectpicker" data-live-search="true" onchange="changeHSN(this);" data-len="' + productRows + '" name="inv['+productRows+'][product]" required="required">';
				  $block += '<option value="' + value.product.id + '">' + value.product.code + " - " + value.product.name + '</option></select><span id="plus'+productRows+'" onclick="showDescription('+productRows+')" style="cursor: pointer;">(+)</span><span id="minus'+productRows+'" onclick="showDescription('+productRows+')" style="cursor:pointer; display:none;">(-)</span><textarea class="form-control" id="descriptionBox'+productRows+'" style="display:none;" rows="2" name="inv['+productRows+'][descriptionBox]"/></textarea></td>';
				  $block += '<td id="quan'+productRows+'"><input type="number" min="1" class="form-control quantity" onchange="changeWeight(this);" data-len="' + productRows + '" name="inv['+productRows+'][quantity]" value="'+value.quantity+'" /></td>'
				  $block += '<td id="rate'+productRows+'"><input type="number" step="any" min="0" class="form-control" onchange="changeWeight(this);" data-len="' + productRows + '" name="inv['+productRows+'][rate]" value="0.00" required /></td>'
				  $block += '<td id="amount'+productRows+'"><input type="number" class="form-control amount" name="inv['+productRows+'][amount]" value="0.00" readonly/></td>'
				  $block += '<td id="wt'+productRows+'"><input type="number" step="any" min="0" class="form-control weight" onchange="changeWeight(this);" data-len="' + productRows + '" name="inv['+productRows+'][weight]" required value="'+value.weight+'" /></td>'
				  $block += '<td id="subtotalnetwt'+productRows+'"><input type="number" step="any" min="0" class="form-control subtotalnetwt" onchange="changeWeight(this);" data-len="' + productRows + '" name="inv['+productRows+'][subtotalnetwt]" value="'+value.subtotalnetwt+'" readonly/></td>'
				  $block += '<td id="grosswt'+productRows+'"><input type="number" step="any" min="0" class="form-control grosswt" onchange="changeWeight(this);" data-len="' + productRows + '" name="inv['+productRows+'][grosswt]" required value="'+value.grosswt+'" /></td>'
				  $block += '<td id="subTotalGrossWT'+productRows+'"><input type="number" step="any" min="0" class="form-control subTotalGrossWT" onchange="changeWeight(this);" data-len="' + productRows + '" name="inv['+productRows+'][subTotalGrossWT]" readonly value="'+value.subtotalgrosswt+'" /></td>'
				  $block += '<td id="box'+productRows+'"><input type="number" step="any" class="form-control box" onchange="changeWeight(this);" data-len="' + productRows + '" name="inv['+productRows+'][box]" min="0" required value="'+value.box+'" /></td>'
				  $block += '<td id="endBox'+productRows+'"><input type="number" step="any" class="form-control endBox" onchange="changeWeight(this);" data-len="' + productRows + '" name="inv['+productRows+'][endBox]" min="0" required value="'+value.endBox+'" /></td>'
				  $block += '<td id="subTotalBox'+productRows+'"><input type="number" step="any" class="form-control subTotalBox" onchange="changeWeight(this);" data-len="' + productRows + '" name="inv['+productRows+'][subTotalBox]" min="0" readonly value="'+value.subTotalBox+'" /></td>'
				  $block += '<td id="qtybox'+productRows+'"><input type="text" step="any" class="form-control qtybox" name="inv['+productRows+'][qtybox]" min="0" readonly value="'+value.qtybox+'" /></td>'
				  $block += '<td id="gstslab'+productRows+'"><input type="number" min="0" class="form-control" onchange="changeWeight(this);" data-len="' + productRows + '" name="inv['+productRows+'][gstslab]" required value="18" /></td>'
				  $block += '<td id="gstamount'+productRows+'"><input type="number" class="form-control gstamount" name="inv['+productRows+'][gstamount]" value="0.00" readonly/></td>'
				  $block += '<td><button type="button" class="close" onclick="deleterowInvoice(this);" data-bs-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button></td>';
				  $block += '</tr>';

			});
			$("#productInvoice").html($block);
			$('.selectpicker').selectpicker();
		}
	});
}

   function invtype(reff) {
       var invtype = $(reff).val();

       if(invtype == '1'){
            $('#conrate').val('1').attr("readonly", "readonly");
            $('select[name=currency]').val("₹").attr("readonly", "readonly");
            $('.selectpicker').selectpicker('refresh');
       }
       else{
            $('#conrate').val('').removeAttr("readonly", "readonly");
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

	$.ajax({
      'url': "{{ url('/purchaseOrder/data') }}",
      'method': 'GET'
	}).done(function(data) {
      if (data) {
		products = data.product;
      }
    });
});

var productRows = 0;
var products = [];

$("#conrate, #totalamount").on('change',function() {
      var totalamount = $("#totalamount").val();
      var conrate = $("#conrate").val();
      var output = parseFloat(totalamount) * parseFloat(conrate);
      var totalamount = parseFloat(output).toFixed(2);
      if (!isNaN(output)) {
        $("#rateamount").val(totalamount);
      }
    });

function validateSubmit(){
  if($('#productInvoice tr').length<1) {
    alert( "No product added. Add atleast 1 product." );
    event.preventDefault();
  }

  else{
    var productTableRow = 0;
    $('#productInvoice select').each(function(){
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
      $("#gstslab"+len+" input").val(product.gstslab);
      $("#quan"+len+" input").attr("max", product.quantity);
      $("#finishingPrice"+len+" input").val(product.finishing_price);
    }
}
//Add Table
  function changeWeight(ref) {
    var len = $(ref).data('len');
    var conrate = $('#conrate').val();
    var quantity = $("#quan"+len+" input").val();
    var rate = $("#rate"+len+" input").val();
    var gstslab = $("#gstslab"+len+" input").val();
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
    var gst = (amount*gstslab*conrate)/100;
    gst = parseFloat(gst).toFixed(2);

    $("#amount"+len+" input").val(amount);
    $("#gstamount"+len+" input").val(gst);
    $("#subtotalnetwt"+len+" input").val(subtotalnetwt);
    $("#subTotalBox"+len+" input").val(subTotalBox);
    $("#qtybox"+len+" input").val(pcbox);
    $("#subTotalGrossWT"+len+" input").val(subTotalGrossWT);

    var arrq = document.getElementsByClassName('quantity');
    var arrgsta = document.getElementsByClassName('igst');
    var arrgtotamt = document.getElementsByClassName('amount');
    var arrwt = document.getElementsByClassName('subtotalnetwt');
    var arrgrosswt = document.getElementsByClassName('subTotalGrossWT');
    var arrgstamt = document.getElementsByClassName('gstamount');
    var arrbox = document.getElementsByClassName('subTotalBox');

    var totq = 0;
    var totgsta = 0;
    var totamt = 0;
    var totalamount = 0;
    var totwt = 0;
    var totgrosswt = 0;
    var gstamount = 0;
    var totalbox = 0;


      for(var i=0;i<arrq.length;i++){
          if(parseFloat(arrq[i].value))
              totq += parseInt(arrq[i].value);
      }
          document.getElementById('tquantity').value = totq;

      for(var i=0;i<arrgsta.length;i++){
          if(parseFloat(arrgsta[i].value))
              totgsta += parseFloat(arrgsta[i].value);
      }
          document.getElementById('totalgst').value = totgsta;

      for(var i=0;i<arrgrosswt.length;i++){
          if(parseFloat(arrgrosswt[i].value))
              totgrosswt += parseFloat(arrgrosswt[i].value);
      }
          document.getElementById('grosswt').value = totgrosswt.toFixed(2);

      for(var i=0;i<arrgstamt.length;i++){
          if(parseFloat(arrgstamt[i].value))
              gstamount += parseFloat(arrgstamt[i].value);
      }

        document.getElementById('totalgst').value = gstamount.toFixed(2);

      for(var i=0;i<arrgtotamt.length;i++){
          if(parseFloat(arrgtotamt[i].value))
              totalamount += parseFloat(arrgtotamt[i].value);
              totamt = totalamount + totgsta;
      }

          var shipping_charges = parseFloat($('#shipping_charges').val());
          var packing_charges = parseFloat($('#packing_charges').val());
          var discount = parseFloat($('#discount').val());
          var totalamount = (shipping_charges + packing_charges + totalamount) - discount;
          document.getElementById('totalamount').value = totalamount.toFixed(2);

      for(var i=0;i<arrwt.length;i++){
          if(parseFloat(arrwt[i].value))
              totwt += parseFloat(arrwt[i].value);
      }
          document.getElementById('totalwt').value = totwt.toFixed(2);

      for(var i=0;i<arrbox.length;i++){
          if(parseFloat(arrbox[i].value))
              totalbox += parseInt(arrbox[i].value);
      }
          document.getElementById('totalbox').value = totalbox;

      var totalamount = $("#totalamount").val();
      var conrate = $("#conrate").val();
      var output = parseFloat(totalamount) * parseFloat(conrate);
      var totalamount = parseFloat(output).toFixed(2);
      if (!isNaN(output)) {
        $("#rateamount").val(totalamount);
      }
  }

  function recalculate(){
    var arrtr = $('#productInvoice tr').length;
    var conrate = parseFloat($("#conrate").val()).toFixed(2);
    var totalgst = 0;
    if(arrtr>0){
      for( var i=1;i<=arrtr;i++){
         var amount = $("#amount"+i+" input").val();
         amount = parseFloat(amount).toFixed(2);
         var gstslab = $("#gstslab"+i+" input").val();
         gstslab = parseFloat(gstslab).toFixed(2);
         var gst = parseFloat(amount*gstslab*conrate/100).toFixed(2);
         totalgst = gst;
         $("#gstamount"+i+" input").val(gst);
      }

      var totalAmount = parseFloat($('#totalamount').val()).toFixed(2);
      var totalAmountInr = parseFloat(totalamount*conrate).toFixed(2);
      $('#rateamount').val(totalAmountInr);
      $('#totalgst').val(totalgst);
    }
  }

function deleterowInvoice(ref) {
  $(ref).parents("tr").remove();
  changeWeight();
}

$("#addProductInvoice").click(function() {
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
      $block += '<td id="quan'+productRows+'"><input type="number" min="1" class="form-control quantity" onchange="changeWeight(this);" data-len="' + productRows + '" name="inv['+productRows+'][quantity]" value="1" /></td>'
      $block += '<td id="rate'+productRows+'"><input type="number" step="any" min="0" class="form-control" onchange="changeWeight(this);" data-len="' + productRows + '" name="inv['+productRows+'][rate]" value="0.00" required /></td>'
      $block += '<td id="amount'+productRows+'"><input type="number" class="form-control amount" name="inv['+productRows+'][amount]" value="0.00" readonly/></td>'
      $block += '<td id="wt'+productRows+'"><input type="number" step="any" min="0" class="form-control weight" onchange="changeWeight(this);" data-len="' + productRows + '" name="inv['+productRows+'][weight]" value="0" required /></td>'
      $block += '<td id="subtotalnetwt'+productRows+'"><input type="number" step="any" min="0" class="form-control subtotalnetwt" onchange="changeWeight(this);" data-len="' + productRows + '" name="inv['+productRows+'][subtotalnetwt]" value="0" readonly/></td>'
      $block += '<td id="grosswt'+productRows+'"><input type="number" step="any" min="0" class="form-control grosswt" onchange="changeWeight(this);" data-len="' + productRows + '" name="inv['+productRows+'][grosswt]" value="0" required /></td>'
      $block += '<td id="subTotalGrossWT'+productRows+'"><input type="number" step="any" min="0" class="form-control subTotalGrossWT" onchange="changeWeight(this);" data-len="' + productRows + '" name="inv['+productRows+'][subTotalGrossWT]" value="0" readonly/></td>'
      $block += '<td id="box'+productRows+'"><input type="number" step="any" class="form-control box" onchange="changeWeight(this);" data-len="' + productRows + '" name="inv['+productRows+'][box]" value="0" min="0" required /></td>'
      $block += '<td id="endBox'+productRows+'"><input type="number" step="any" class="form-control endBox" onchange="changeWeight(this);" data-len="' + productRows + '" name="inv['+productRows+'][endBox]" value="0" min="0" required /></td>'
      $block += '<td id="subTotalBox'+productRows+'"><input type="number" step="any" class="form-control subTotalBox" onchange="changeWeight(this);" data-len="' + productRows + '" name="inv['+productRows+'][subTotalBox]" value="0" min="0" readonly/></td>'
      $block += '<td id="qtybox'+productRows+'"><input type="text" step="any" class="form-control qtybox" name="inv['+productRows+'][qtybox]" value="1 Pc/Box" min="0" readonly/></td>'
      $block += '<td id="gstslab'+productRows+'"><input type="number" min="0" class="form-control" onchange="changeWeight(this);" data-len="' + productRows + '" name="inv['+productRows+'][gstslab]" required /></td>'
      $block += '<td id="gstamount'+productRows+'"><input type="number" class="form-control gstamount" name="inv['+productRows+'][gstamount]" value="0.00" readonly/></td>'
      $block += '<td><button type="button" class="close" onclick="deleterowInvoice(this);" data-bs-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button></td>';
      $block += '</tr>';
      $("#productInvoice").append($block);
      $('.selectpicker').selectpicker();
});

$("#changecurrency").change(function () {
  $(".changeCur").html($(this).val());
});
var inv_id='{{$invoices->id}}';
var trcount = $('#invoiceTable'+inv_id+' tr').length;

var invoiceRows = trcount;
var products = [];
$("#addMore"+inv_id).click(function() {

    invoiceRows += 1;
    console.log(invoiceRows);
    var options = '<option selected disabled>-- SELECT PRODUCT --</option>';
    $.each(products, function(index, value) {
        options += '<option value="' + value.id + '">' + value.code + " - " + value.name + '</option>';
    });

    var $block = "";
        $block += '<tr>';
        $block += '<td id="realisation_date'+invoiceRows+'"><input type="date" step="any" min="0" class="form-control"  data-len="' + invoiceRows + '" name="ie['+invoiceRows+'][realisation_date]" value=""  /></td>'
        $block += '<td id="realisation_fc'+invoiceRows+'"><input type="text" step="any" min="0" class="form-control"  data-len="' + invoiceRows + '" name="ie['+invoiceRows+'][realisation_fc]" value=""  /></td>'
        $block += '<td id="rate'+invoiceRows+'"><input type="number" step="any" min="0" class="form-control"  data-len="' + invoiceRows + '" name="ie['+invoiceRows+'][rate]" value=""  /></td>'
        $block += '<td id="bank_reference'+invoiceRows+'"><input type="text" step="any" min="0" class="form-control"  data-len="' + invoiceRows + '" name="ie['+invoiceRows+'][bank_reference]" value=""  /></td>'
        $block += '<td><a href="javascript:void(0);" class="close" onclick="deleteRow(this);" >&times;</a></td>';
        $block += '</tr>';
    $("#invoiceTable"+inv_id).append($block);

});
function deleteRow(ref) {
  $(ref).parent().parent().remove();

}
</script>
<!-- Script End -->

@endsection
