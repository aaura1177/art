@extends('layouts.app')

@section('content')
    

<div class="mx-2">
    <div class="row mx-0 my-2">
        <h2>Edit Suppliers</h2>
    </div>

    <form method="POST" action="{{ url('/supplier/view/'.$supplier->id)}}">
      @csrf

      <!-- Form Starts -->
      <div class="form-group"> 
          
          <!-- first row -->
          <div class="row mt-3">  
            <div class="col-4">
              <label class="control-label">{{ __('Company Name') }}</label>
              <input type="text" class="form-control" name="c_name" required="required" value="{{$supplier->c_name}}" />
            </div>
            <div class="col-4">
              <label class="control-label">{{ __('Short Name') }}</label>
              <input type="text" class="form-control" name="short_name" value="{{$supplier->short_name}}"required="required" maxlength="4" />
              @error('short_name')
                 <small class="text-danger">{{ $message }}</small>
               @enderror
            </div>

            <div class="col-4">
              <label class="control-label">{{ __('Contact Person') }}</label>
              <input type="text" class="form-control" name="name" required="required" value="{{$supplier->name}}" />
            </div>
            <div class="col-4">
              <label class="control-label">{{ __('PAN') }}</label>
              <input type="text" class="form-control toUpperCase" name="pan" value="{{$supplier->pan}}" required/>
            </div>
          </div>

          <!-- second row -->
          <div class="row mt-3">  
            <div class="col-4">
              <label class="control-label">{{ __('Address Line 1') }}</label>
              <input type="text" class="form-control" name="address1" required="required" value="{{$supplier->address1}}" />
            </div>
            <div class="col-4">
              <label class="control-label">{{ __('Address Line 2') }}</label>
              <input type="text" class="form-control" name="address2" value="{{$supplier->address2}}" />
            </div>
            <div class="col-4">
              <label class="control-label">{{ __('City') }}</label>
              <input type="text" class="form-control" name="city" required="required" value="{{$supplier->city}}" />
            </div>
          </div>  

          <!-- third row -->
          <div class="row mt-3">  
            <div class="col-4">
              <label class="control-label">{{ __('State') }}</label>
              <select type="text" class="selectpicker" data-live-search="true" onchange="changeState(this);" name="state" required="required">
                <option value="" selected disabled>Select State</option>
                @if(isset($states)) @foreach($states as $key => $state)
                  <option value="{{$state->statename}}" {{($supplier->state == $state->statename)?'selected':''}}>
                  {{$state->statename}}
                  </option>
                @endforeach @endif
              </select>
            </div>
            <div class="col-4">
              <label class="control-label">{{ __('Country') }}</label>
              <input type="text" class="form-control" name="country" required="required" value="{{$supplier->country}}" />
            </div>
            <div class="col-4">
              <label class="control-label">{{ __('Postcode') }}</label>
              <input type="text" class="form-control" name="postcode" required="required" value="{{$supplier->postcode}}" />
            </div>
          </div>  

          <!-- fourth row -->
          <div class="row mt-3">  
            <div class="col-2">
              <label class="control-label">{{ __('GST') }}</label>
              <select type="text" class="selectpicker" data-live-search="true" name="gst" id="gst" required="required">
                <option value="1" {{($supplier->gst==1)?'selected':''}}>Yes</option>
                <option value="0" {{($supplier->gst==0)?'selected':''}}>No</option>                        
              </select>
            </div>
            <div class="col-2">
              <label class="control-label">{{ __('GSTIN') }}</label>
              <input type="text" class="form-control toUpperCase" name="gstin" id="gstin" value="{{$supplier->gstin}}" required="required" />                     
            </div>
			      <div class="col-2">
              <label class="control-label">{{ __('GST %') }}</label>
              <input type="text" class="form-control toUpperCase" name="gstpercent" id="gstpercent" value="{{$supplier->gstpercent}}"/>                     
            </div>
            <div class="col-3">
              <label class="control-label">{{ __('State Code') }}</label>
              <input type="text" class="form-control" name="statecode" id="statecode" value="{{$supplier->state_code}}" readonly/>
            </div>
            <div class="col-3">
              <label class="control-label">{{ __('Monthly Invoice Limit') }}</label>
              <input type="text" class="form-control" name="monthly_invoice_limit" value="{{$supplier->monthly_invoice_limit}}" required="required" />
            </div>
          </div>
          @include('supplier.partials.po_monthly_limits', ['supplier' => $supplier])

		  <!-- fifth row -->
          <div class="row mt-3">  
            <div class="col-3">
              <label class="control-label">{{ __('TDS') }}</label>
              <select type="text" class="selectpicker" data-live-search="true" name="tds" value="{{$supplier->tds}}">
                <option value="1" selected>Yes</option>
                <option value="0">No</option>                        
              </select>
            </div>
			<div class="col-3">
              <label class="control-label">{{ __('TDS %') }}</label>
              <input type="text" class="form-control toUpperCase" name="tdspercent" id="tdspercent" value="{{$supplier->tdspercent}}" />                     
            </div>
            <div class="col-3">
              <label class="control-label">{{ __('TDS Ledger') }}</label>
              <input type="text" class="form-control toUpperCase" value="{{$supplier->tdsledger}}" name="tdsledger" id="tdsledger" />                     
            </div>

            <div class="col-3">
              <label class="control-label">{{ __('TDS Date') }}</label>
              <input type="date" class="form-control toUpperCase" value="{{$supplier->tdsdate}}" name="tdsdate" id="tdsdate" />                     
            </div>
          </div>


          <!-- sixth row -->
          <div class="row mt-3">
            <div class="col-4">
              <label class="control-label">{{ __('Email') }}</label>
              <input type="email" class="form-control" name="email" required="required" value="{{$supplier->email}}" />
            </div>
            <div class="col-4">
              <label class="control-label">{{ __('Phone 1') }}</label>
              <input type="number" minlength="6" class="form-control" name="phone1" required="required" value="{{$supplier->phone1}}" />
            </div> 
            <div class="col-4">
              <label class="control-label">{{ __('Phone 2') }}</label>
              <input type="number" minlength="6" class="form-control" name="phone2" value="{{$supplier->phone2}}" />
            </div>
          </div> 

      @if(isset($packaging_pricing->id))
      <h4 class="mt-3">Packaging Price</h4>
		  <div class="row mt-3" style="margin-bottom:20px;">
        
        <div class="col-4">
          
          <label class="control-label">{{ __('3 Ply') }}</label>
          <input class="form-control" type="number" step="any" name = "packaging_pricing[3ply]" value="{{$packaging_pricing->{'3ply'} }}" />
        </div>
        <div class="col-4">
          <label class="control-label">{{ __('5 Ply') }}</label>
          <input class="form-control" type="number" step="any" name = "packaging_pricing[5ply]" value="{{$packaging_pricing->{'5ply'} }}" />
        </div>
        <div class="col-4">
          <label class="control-label">{{ __('7 Ply') }}</label>
          <input class="form-control" type="number" step="any" name = "packaging_pricing[7ply]" value="{{$packaging_pricing->{'7ply'} }}" />
        </div>
		  </div>
      @endif

      <!-- Product details -->
      <div class="table-responsive">
        <table class="table table-hover">
          <thead>
            <tr id="mytable">
              <th scope="col" style="min-width: 250px;">Product <a href="{{ url('/product/create')}}" target="_blank"> (+New)</a></th>
              <th scope="col" style="min-width: 100px;">Rate/Item (₹)</th>
              <th scope="col" style="min-width: 100px;">UK 45 Price</th>
              <th></th>
            </tr>
          </thead>
          <tbody id="productTable">
            @foreach ($poProduct as $k=>$item)
              @if(isset($item->product->id))
              <tr>
                <td id="pr">
                  <select type="text" class="selectpicker" data-live-search="true" data-len="{{$k+1}}" name="po[{{$k+1}}][product]">
                    <option value="{{$item->product->id}}"> 
                      {{$item->product->code}} - {{$item->product->name}}
                    </option>
                  </select>
                </td>
                <td id="rate{{$k+1}}"><input type="number" step="any" min="1" class="form-control" data-len="{{$k+1}}" name="po[{{$k+1}}][rate]" value="{{$item->rate}}" required /></td>
                <td id="uk45rate{{$k+1}}"><input type="number" step="any" min="0" class="form-control" data-len="{{$k+1}}" name="po[{{$k+1}}][uk_45_rate]" value="{{ $item->uk_45_rate }}" /></td>
                <td><button type="button" class="close" onclick="deleteRow(this);" data-bs-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button></td>
              </tr>
              @endif
            @endforeach
          </tbody>
        </table>
      </div>

      <div class="row mt-2">
        <div class="col">
            <input type="button" id="addProduct" class="btn btn-primary" value="Add Product" />
        </div>
      </div>

          <div class="row col-4">
            <button type="submit" class="btn btn-primary mt-3">Update Supplier</button>
          </div>
      
      </div>   
    </form>  
</div>
@endsection

@section('footer')


<!-- Script start for Statecode & GSTIN  disabled/enabled -->
<script>

$(document).ready(function(){
    
    $.ajax({
        'url': "{{ url('/supplier/data') }}",
        'method': 'GET'
    }).done(function(data) {
        if (data) {
            states = data.states;
        }
      });
});

$(document).ready(function(){
    
    $.ajax({
        'url': "{{ url('/purchaseOrder/data') }}"
    }).done(function(data) {
        if (data) {
            products = data.product;
        }
      });
  });

  var trcount = $('#productTable tr').length;
  console.log(trcount);
  var productRows = trcount;
  var states  = [];
  var products = [];

  console.log(states);

  function changeState(ref) {
    var id = $(ref).val();

    function findState(states) {
      return states.statename == id;
    }

    var stateCode = states.find(findState);
      $("#statecode").val(stateCode.statecode);
  }

  function changeHSN(ref) {
    var len = $(ref).data('len');
    var id = $(ref).val();

    if($('#pr'+id).length){
      alert('product already added');
      $(ref).prop('selectedIndex',0);
      $(ref).parent().attr("id",'pr');
    }else{
      $(ref).parent().attr("id",'pr'+id);
    }
  }

  var gst = document.getElementById("gst").value;
    console.log(gst);
    if(gst == 0){
       document.getElementById("gstin").disabled = true;
    }
    else{
      document.getElementById("gstin").disabled = false;
    }
  
  var $input = $('#gstin');
  var input1 = $('#gstpercent');
  $('select[name=gst]').on('change', function () {
      $input.attr('disabled', $(this).val() != "1");
      $input.attr('value', '', $(this).val() != "1" );
      $input.attr('required', $(this).val() != "0" );
	    input1.attr('disabled', $(this).val() != "1");
	    $input1.attr('value', '', $(this).val() != "1" );
      input1.attr('required', $(this).val() != "0" );
  });
  
  // for tds disable or enable
  var input2 = $('#tdspercent');
  $('select[name=tds]').on('change', function () {
	  input2.attr('disabled', $(this).val() != "1");
      input2.attr('required', $(this).val() != "0" );
  });

  function deleteRow(ref) {
    $(ref).parents("tr").remove();
    changePrice();
  }

  $("#addProduct").click(function() {    

    var options = '<option value="" selected disabled>-- SELECT PRODUCT --</option>';

    productRows += 1;

    $.each(products, function(index, value) {
        options += '<option value="' + value.id + '">' + value.code + " - " + value.name + '</option>';
    });

    var $block = "";
        $block += '<tr>';
        $block += '<td id="pr">';
        $block += '<select type="text" class="selectpicker" data-live-search="true" onchange="changeHSN(this);" data-len="' + productRows + '" name="po['+productRows+'][product]">';
        $block += options + '</select></td>';
        
        $block += '<td id="rate'+productRows+'"><input type="number" step="any" min="1" class="form-control" onchange="changePrice(this);" data-len="' + productRows + '" name="po['+productRows+'][rate]" value="0.00" required /></td>'
        $block += '<td id="uk45rate'+productRows+'"><input type="number" step="any" min="0" class="form-control" data-len="' + productRows + '" name="po['+productRows+'][uk_45_rate]" value="" /></td>'
        $block += '<td><button type="button" class="close" onclick="deleteRow(this);" data-bs-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button></td>';
        $block += '</tr>';
    $("#productTable").append($block);
    $('.selectpicker').selectpicker();
  });

</script>
<!-- Script end -->

@include('supplier.partials.po_monthly_limits_script')
@endsection