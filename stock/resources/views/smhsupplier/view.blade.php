@extends('layouts.app')

@section('content')
    

<div class="mx-2">
    <div class="row mx-0 my-2">
        <h2>Edit Suppliers</h2>
    </div>

    <form method="POST" action="{{ url('/smhsupplier/view/'.$supplier->id)}}">
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
            <div class="col-3">
              <label class="control-label">{{ __('GST') }}</label>
              <select type="text" class="selectpicker" data-live-search="true" name="gst" id="gst" required="required">
                <option value="1" {{($supplier->gst==1)?'selected':''}}>Yes</option>
                <option value="0" {{($supplier->gst==0)?'selected':''}}>No</option>                        
              </select>
            </div>
            <div class="col-3">
              <label class="control-label">{{ __('GSTIN') }}</label>
              <input type="text" class="form-control toUpperCase" name="gstin" id="gstin" value="{{$supplier->gstin}}" required="required" />                     
            </div>
			<div class="col-3">
              <label class="control-label">{{ __('GST %') }}</label>
              <input type="text" class="form-control toUpperCase" name="gstpercent" id="gstpercent" value="{{$supplier->gstpercent}}"/>                     
            </div>
            <div class="col-3">
              <label class="control-label">{{ __('State Code') }}</label>
              <input type="text" class="form-control" name="statecode" id="statecode" value="{{$supplier->state_code}}" readonly/>
            </div>
          </div> 
		  
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
        'url': "{{ url('/smhsupplier/data') }}",
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

</script>
<!-- Script end -->

@endsection