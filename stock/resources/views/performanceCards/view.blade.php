@extends('layouts.app')

@section('content')

  <div class="mx-2">
    <div class="row mx-0 my-2">
        <h2>Update Performance card</h2>
    </div>

    <form id='myForm' method="POST" action="{{ url('/performanceCards/view') . '/' . $performanceCard->id}}">
      @csrf

      <!-- Form Starts -->
      <div class="form-group"> 
          

          <!-- First row -->
          <div class="row mt-3">  
			<div class="col-4">
				<label class="control-label">{{ __('Sr. No.') }}</label>
				<input type="text" name="job" class="form-control" value="{{$performanceCard->job}}" />
			</div>
            <div class="col-4">
              <label class="control-label">{{ __('Contractor') }}</label>
			  
				<label>Select Contractors</label>
				<select class="selectpicker" data-live-search="true" name="contractor_id" required>
					<option value="" disabled>Select Contractors</option>
					  @if(isset($contractor)) @foreach($contractor as $key => $cont)
						<option value="{{$cont->id}}" {{($cont->id == $performanceCard->contractor_id)?'selected':''}}>
						{{$cont->name}}
						</option>
					  @endforeach @endif
				</select>
            </div>
			
			<div class="col-4">
				<label>DATE</label>
				<input id="date" type="date" class="form-control" name="date" value="{{$performanceCard->date}}" />
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
                    <th scope="col" style="min-width: 100px;">QTY Received</th>
                    <th scope="col" style="min-width: 100px;">QTY Rejected</th>
                    <th scope="col" style="min-width: 120px;">Net QTY</th>
                    <th scope="col" style="min-width: 120px;">Remarks</th>
                  </tr>
                </thead>
                <tbody id="productInvoice">
					@if(isset($performanceCardProduct)) @foreach($performanceCardProduct as $key => $performanceCardProduct)
					<tr>
						<td id="pr{{$performanceCardProduct->product}}">
							<select type="text" class="selectpicker" data-live-search="true" data-len="{{$key+1}}" name="inv[{{$key+1}}][product_id]" required="required" >
								<option value="{{$performanceCardProduct->product_id}}"> 
									{{$performanceCardProduct->product->code}} - {{$performanceCardProduct->product->name}}
								</option>
							</select>
						</td>
						<td id="quan{{$key+1}}"><input type="number" min="1" class="form-control quantity" onchange="changeQuant(this);" data-len="{{$key+1}}" name="inv[{{$key+1}}][qty_received]" value="{{$performanceCardProduct->qty_received}}" /></td>
						<td id="qty_rejected{{$key+1}}"><input type="number" min="0" class="form-control qty_rejected" onchange="changeQuant(this);" data-len="{{$key+1}}" name="inv[{{$key+1}}][qty_rejected]" value="{{$performanceCardProduct->qty_rejected}}" /></td>
						<td id="net_qty{{$key+1}}"><input type="number" min="1" class="form-control net_qty" onchange="changeWeight(this);" data-len="{{$key+1}}" name="inv[{{$key+1}}][net_qty]" value="{{$performanceCardProduct->net_qty}}" readonly /></td>
						<td id="remarks{{$key+1}}"><input type="text" class="form-control remarks" name="inv[{{$key+1}}][remarks]" value="{{$performanceCardProduct->remarks}}" /></td>
						<td><button type="button" class="close" onclick="deleterowInvoice(this);" data-bs-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button></td>
					</tr>
					@endforeach @endif
                </tbody>
            </table>
          </div>
          
          <div class="row mt-2">
              <div class="col">
                <input type="button" id="addProductInvoice" class="btn btn-primary" value="Add Product" />
              </div>
          </div>  

         
          <!-- ninth row -->
          

          <div class="row col-4">
                <button id='submitBtn' type="submit" form='myForm' class="btn btn-primary mt-3">Update Performance</button>
          </div>
      </div>
    </form>
  </div>

@endsection

@section('footer')

<script>
    
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

var trcount = $('#productInvoice tr').length;
var productRows = trcount;
var products = [];

function changeQuant(ref){
	var len = $(ref).data('len');
	var qty_rec = $("#quan"+len+" input").val();
	var qty_rejected = $("#qty_rejected"+len+" input").val();
	var net_qty = qty_rec - qty_rejected;
	$("#net_qty"+len+" input").val(net_qty);
	$("#qty_rejected"+len+" input").attr('max',qty_rec);
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
      $block += '<select type="text" class="selectpicker" data-live-search="true" data-len="' + productRows + '" name="inv['+productRows+'][product_id]" required="required">';
      $block += options + '</select></td>';
      $block += '<td id="quan'+productRows+'"><input type="number" min="1" class="form-control quantity" onchange="changeQuant(this);" data-len="' + productRows + '" name="inv['+productRows+'][qty_received]" value="1" /></td>'
      $block += '<td id="qty_rejected'+productRows+'"><input type="number" min="0" class="form-control qty_rejected" onchange="changeQuant(this);" data-len="' + productRows + '" name="inv['+productRows+'][qty_rejected]" value="0" /></td>'
      $block += '<td id="net_qty'+productRows+'"><input type="number" min="1" class="form-control net_qty" onchange="changeWeight(this);" data-len="' + productRows + '" name="inv['+productRows+'][net_qty]" value="1" readonly /></td>'
      $block += '<td id="remarks'+productRows+'"><input type="text" class="form-control remarks" name="inv['+productRows+'][remarks]" /></td>'
      $block += '<td><button type="button" class="close" onclick="deleterowInvoice(this);" data-bs-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button></td>';
      $block += '</tr>';
      $("#productInvoice").append($block);
      $('.selectpicker').selectpicker();
});

$("#changecurrency").change(function () {
  $(".changeCur").html($(this).val());
});

</script>  
<!-- Script End -->

@endsection
