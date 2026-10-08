@extends('layouts.app')

@section('content')

  <div class="mx-2">
    <div class="row mx-0 my-2">
        <h2>Add Contractor</h2>
    </div>

    <form method="POST" action="{{ url('/contractor/create') }}">
    @csrf

      <!-- Form Starts -->
      <div class="form-group"> 
        
        <!-- first row -->
        <div class="row mt-3">  
          <div class="col-4">
            <label class="control-label">{{ __('Company Name') }}</label>
            <input type="text" class="form-control" name="c_name" required="required" />
          </div>
          <div class="col-4">
            <label class="control-label">{{ __('Contact Person') }}</label>
            <input type="text" class="form-control" name="name" required="required" />
          </div>
          <div class="col-4">
            <label class="control-label">{{ __('PAN') }}</label>
            <input type="text" class="form-control toUpperCase" name="pan" required="required" />
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
            <input type="text" class="form-control" name="postcode" required="required" />
          </div>
        </div>  

        <!-- fourth row -->
        <div class="row mt-3">  
          <div class="col-4">
            <label class="control-label">{{ __('GST') }}</label>
            <select type="text" class="selectpicker" data-live-search="true" name="gst" required="required">
              <option value="1" selected>Yes</option>
              <option value="0">No</option>                        
            </select>
          </div>
          <div class="col-4">
            <label class="control-label">{{ __('GSTIN') }}</label>
            <input type="text" class="form-control toUpperCase" name="gstin" id="gstin" required="required" />                     
          </div>
          <div class="col-4">
            <label class="control-label">{{ __('State Code') }}</label>
            <input type="text" class="form-control" name="statecode" id="statecode" readonly/>
          </div>
        </div> 

        <!-- fifth row -->
        <div class="row mt-3">
          <div class="col-4">
              <label class="control-label">{{ __('Email') }}</label>
              <input type="email" class="form-control" name="email" required="required" />
          </div>
          <div class="col-4">
              <label class="control-label">{{ __('Phone 1') }}</label>
              <input type="number" minlength="6" class="form-control" name="phone1" required="required" />
          </div> 
          <div class="col-4">
              <label class="control-label">{{ __('Phone 2') }}</label>
              <input type="number" minlength="6" class="form-control" name="phone2" />
          </div>
        </div>  

        <div class="row col-4">
          <button type="submit" class="btn btn-primary mt-3">Add Contractor</button>
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
      'url': "{{ url('/contractor/data') }}",
      'method': 'GET'
  }).done(function(data) {
      if (data) {
          states = data.states;
      }
    });
  });

  var states  = [];

  function changeState(ref) {
    var id = $(ref).val();

    function findState(states) {
      return states.statename == id;
    }

    var stateCode = states.find(findState);
      $("#statecode").val(stateCode.statecode);
  }

  var input = $('#gstin');
  $('select[name=gst]').on('change', function () {
      input.attr('disabled', $(this).val() != "1");
      input.attr('required', $(this).val() != "0" );
  });

</script>
<!-- Script End -->

@endsection