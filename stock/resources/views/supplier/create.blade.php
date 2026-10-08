@extends('layouts.app')

@section('content')
    
<div class="mx-2">
    <div class="row mx-0 my-2">
        <h2>Add Supplier</h2>
    </div>

    <form method="POST" action="{{ url('/supplier/create') }}">
      @csrf

      <!-- Form Starts -->
      <div class="form-group"> 
          
          <!-- first row -->
          <div class="row mt-3">  
            <div class="col-4">
              <label class="control-label">{{ __('Company Name') }}</label>
              <input type="text" class="form-control" name="c_name" required="required" />
              @error('c_name')
                 <small class="text-danger">{{ $message }}</small>
               @enderror
            </div>
            <div class="col-4">
              <label class="control-label">{{ __('Short Name') }}</label>
              <input type="text" class="form-control" name="short_name" required="required" maxlength="4" />
              @error('short_name')
                 <small class="text-danger">{{ $message }}</small>
               @enderror
            </div>
            <div class="col-4">
              <label class="control-label">{{ __('Contact Person') }}</label>
              <input type="text" class="form-control" name="name" required="required" />
            </div>
            <div class="col-4">
              <label class="control-label">{{ __('PAN') }}</label>
              <input type="text" class="form-control toUpperCase" name="pan" required="required"/>
            </div>
          </div>

          <!-- second row -->
          <div class="row mt-3">  
            <div class="col-4">
              <label class="control-label">{{ __('Address Line 1') }}</label>
              <input type="text" class="form-control" name="address1" required="required" />
            </div>
            <div class="col-4">
              <label class="control-label">{{ __('Address Line 2') }}</label>
              <input type="text" class="form-control" name="address2" />
            </div>
            <div class="col-4">
              <label class="control-label">{{ __('City') }}</label>
              <input type="text" class="form-control" name="city" required="required" />
            </div>
          </div>  

          <!-- third row -->
          <div class="row mt-3">  
            <div class="col-4">
              <label class="control-label">{{ __('State') }}</label>
              <select type="text" class="selectpicker" data-live-search="true" onchange="changeState(this);" name="state" required="required">
              <option value="" selected disabled>Select State</option>
              @if(isset($states)) @foreach($states as $key => $state)
                <option value="{{$state->statename}}">
                {{$state->statename}}
                </option>
              @endforeach @endif
            </select>
            </div>
            <div class="col-4">
              <label class="control-label">{{ __('Country') }}</label>
              <input type="text" class="form-control" name="country" required="required" value="India" />
            </div>
            <div class="col-4">
              <label class="control-label">{{ __('Postcode') }}</label>
              <input type="text" min="0" class="form-control" name="postcode" required="required" />
            </div>
          </div>  

          <!-- fourth row -->
          <div class="row mt-3">  
            <div class="col-2">
              <label class="control-label">{{ __('GST') }}</label>
              <select type="text" class="selectpicker" data-live-search="true" name="gst" required="required">
                <option value="1" selected>Yes</option>
                <option value="0">No</option>                        
              </select>
            </div>
            <div class="col-2">
              <label class="control-label">{{ __('GSTIN') }}</label>
              <input type="text" class="form-control toUpperCase" name="gstin" id="gstin" required="required"/>                     
            </div>
			      <div class="col-2">
              <label class="control-label">{{ __('GST %') }}</label>
              <input type="text" class="form-control toUpperCase" name="gstpercent" id="gstpercent"/>                     
            </div>
            <div class="col-3">
              <label class="control-label">{{ __('State Code') }}</label>
              <input type="text" class="form-control" name="statecode" id="statecode" readonly />
            </div>
            <div class="col-3">
              <label class="control-label">{{ __('Monthly Invoice Limit') }}</label>
              <input type="text" class="form-control" name="monthly_invoice_limit" required="required" />
            </div>
          </div>

          @include('supplier.partials.po_monthly_limits', ['supplier' => null])

		  <!-- fifth row -->
          <div class="row mt-3">  
            <div class="col-3">
              <label class="control-label">{{ __('TDS') }}</label>
              <select type="text" class="selectpicker" data-live-search="true" name="tds">
                <option value="1" selected>Yes</option>
                <option value="0">No</option>                        
              </select>
            </div>
			      <div class="col-3">
              <label class="control-label">{{ __('TDS %') }}</label>
              <input type="text" class="form-control toUpperCase" name="tdspercent" id="tdspercent" />                     
            </div>

            <div class="col-3">
              <label class="control-label">{{ __('TDS Ledger') }}</label>
              <input type="text" class="form-control toUpperCase" name="tdsledger" id="tdsledger" />                     
            </div>

            <div class="col-3">
              <label class="control-label">{{ __('TDS Date') }}</label>
              <input type="date" class="form-control toUpperCase" name="tdsdate" id="tdsdate" />                     
            </div>
          </div>

          <!-- sixth row -->
          <div class="row mt-3">
            <div class="col-4">
                <label class="control-label">{{ __('Email') }}</label>
                <input type="email" class="form-control" name="email" required="required" />
            </div>
            <div class="col-4">
                    <label class="control-label">{{ __('Phone 1') }}</label>
                    <input type="number" class="form-control" name="phone1" required="required" />
            </div> 
            <div class="col-4">
                <label class="control-label">{{ __('Phone 2') }}</label>
                <input type="number" class="form-control" name="phone2" />
            </div>
          </div> 
		  
<br/>

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

              </tbody>
            </table>
          </div>
          
          <div class="row mt-2">
              <div class="col">
                  <input type="button" id="addProduct" class="btn btn-primary" value="Add Product" />
              </div>
          </div>

          <div class="row col-4">
            <button type="submit" class="btn btn-primary mt-3">Add Supplier</button>
          </div>
      
      </div>
    </form>
</div>
@endsection

@section('footer')


<!-- Script Start For Statecode & GSTIN fields disabled -->
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
  var productRows = 0;
  var products = [];
  var states  = [];

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

// for gst disable or enable
  var input = $('#gstin');
  var input1 = $('#gstpercent');
  $('select[name=gst]').on('change', function () {
      input.attr('disabled', $(this).val() != "1");
      input.attr('required', $(this).val() != "0" );
	    input1.attr('disabled', $(this).val() != "1");
      input1.attr('required', $(this).val() != "0" );
  });
  
  // for tds disable or enable
  var input2 = $('#tdspercent');
  var input3 = $('#tdsledger');
  var input4 = $('#tdsdate');
  $('select[name=tds]').on('change', function () {
	  input2.attr('disabled', $(this).val() != "1");
      input2.attr('required', $(this).val() != "0" );
      input3.attr('disabled', $(this).val() != "1");
      input3.attr('required', $(this).val() != "0" );
      input4.attr('disabled', $(this).val() != "1");
      input4.attr('required', $(this).val() != "0" );
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
<script>
$(document).ready(function () {
    // GST validation
    function validateGST(gstin) {
        const gstRegex = /^[0-9]{2}[A-Z]{5}[0-9]{4}[A-Z]{1}[A-Z0-9]{1}[Z]{1}[A-Z0-9]{1}$/;
        return gstRegex.test(gstin);
    }

    // PAN validation
    function validatePAN(pan) {
        const panRegex = /^[A-Z]{5}[0-9]{4}[A-Z]{1}$/;
        return panRegex.test(pan);
    }

    // Attach blur event for GSTIN validation
    $('#gstin').on('blur', function () {
        const gstin = $(this).val().toUpperCase();
        if (gstin && !validateGST(gstin)) {
            alert("Invalid GSTIN format. Please enter a valid GSTIN.");
            $(this).val('').focus();
        }
    });

    // Attach blur event for PAN validation
    $('input[name="pan"]').on('blur', function () {
        const pan = $(this).val().toUpperCase();
        if (pan && !validatePAN(pan)) {
            alert("Invalid PAN format. Please enter a valid PAN.");
            $(this).val('').focus();
        }
    });

    // Convert to uppercase automatically for PAN and GSTIN fields
    $('.toUpperCase').on('input', function () {
        this.value = this.value.toUpperCase();
    });

    // Form submission validation
    $('form').on('submit', function (e) {
        const gstin = $('#gstin').val();
        const pan = $('input[name="pan"]').val();

        if (gstin && !validateGST(gstin)) {
            alert("Invalid GSTIN format. Please fix it before submitting.");
            e.preventDefault();
            return false;
        }

        if (pan && !validatePAN(pan)) {
            alert("Invalid PAN format. Please fix it before submitting.");
            e.preventDefault();
            return false;
        }
    });
});
</script>

<!-- Script End -->

@include('supplier.partials.po_monthly_limits_script')
@endsection