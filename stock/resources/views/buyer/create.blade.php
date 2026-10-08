@extends('layouts.app')

@section('content')

  <div class="mx-2">
    <div class="row mx-0 my-2">
        <h2>Add Buyer</h2>
    </div>

    <form method="POST" action="{{ url('/buyer/create') }}">
      @csrf

    <!-- Form Starts -->
      <div class="form-group"> 
          
        <!-- first row -->
          <div class="row mt-3">  
            <div class="col-4">
              <label class="control-label">{{ __('Buyer Code') }}</label>
              <input type="text" class="form-control toUpperCase" name="code" required="required" value="{{ old('code') }}" />
              @if(isset($errors))
              <p class="mt-2 mb-0" style="color:red">{{$errors->first('code')}}</p>
              @endif
            </div>
            <div class="col-4">
              <label class="control-label">{{ __('Company Name') }}</label>
              <input type="text" class="form-control" name="c_name" value="{{ old('c_name') }}" required="required" />
            </div> 
            <div class="col-4">
              <label class="control-label">{{ __('Name') }}</label>
              <input type="text" class="form-control" name="name" value="{{ old('name') }}" required="required" />
            </div>
          </div>  

          <div class="row mt-3">  
            <div class="col-4">
              <label class="control-label">{{ __('Email') }}</label>
              <input type="email" class="form-control" name="email" value="{{ old('email') }}" />
            
            </div>
            <div class="col-4">
              <label class="control-label">{{ __('Phone') }}</label>
              <input type="number" class="form-control" name="phone" value="{{ old('phone') }}" />
            </div> 
             <div class="col-4">
              <label class="control-label">{{ __('Tally Name') }}</label>
              <input type="text" class="form-control" name="tally_name" value="{{ old('tally_name') }}" />
            </div> 
          
          </div> 
          <!-- second row -->
          <div class="row mt-3">
            <div class="col-4">
              <label class="control-label">{{ __('Address Line 1') }}</label>
              <input type="text" class="form-control" name="address1" value="{{ old('address1') }}" required="required" />
            </div>
            <div class="col-4">
              <label class="control-label">{{ __('Address Line 2') }}</label>
              <input type="text" class="form-control" name="address2" value="{{ old('address2') }}" />
            </div>
            <div class="col-4">
              <label class="control-label">{{ __('City') }}</label>
              <input type="text" class="form-control" name="city" value="{{ old('city') }}" required="required" />
            </div>
          </div>  

          <!-- third row -->
          <div class="row mt-3">
            <div class="col-4">
              <label class="control-label">{{ __('State') }}</label>
              @if($country == 'india')
              <select class="selectpicker" data-live-search="true" onchange="changeState(this);" name="state" required="required">
                <option value="" selected disabled>Select State</option>
                @if(isset($states)) @foreach($states as $key => $state)
                  <option value="{{$state->statename}}">
                  {{$state->statename}}
                  </option>
                @endforeach @endif
              </select>
              @else
              <input type="text" class="form-control" name="state" required="required" />
              @endif
            </div>
            <div class="col-4">
              <label class="control-label">{{ __('Country') }}</label>
                <input type="text" class="form-control" name="country" value="{{ old('country') }}" required="required" />
            </div>
            
            <div class="col-4">
              <label class="control-label">{{ __('Postcode') }}</label>
              <input type="text" class="form-control" name="postcode" value="{{ old('postcode') }}" required="required" />
            </div>
          </div>  
          <!-- fourth row -->
          <div class="row mt-3">
            @if($country == 'india')
            <div class="col-4">
              <label class="control-label">{{ __('State Code') }}</label>
              <input id="statecode" type="text" class="form-control" name="state_code" value="{{ old('state_code') }}" readonly />
            </div>
            @endif
            <div class="col-4">
              <label class="control-label">{{ __('GST No') }}</label>
              <input type="text" class="form-control" name="gstno" value="{{ old('gstno') }}"  />
            </div>
            <div class="col-4">
              <label class="control-label">{{ __('Type') }}</label>
              <select type="text" class="selectpicker" data-live-search="true" name="buyertype" required="required" >
                <option value="0">Global</option>
                <option value="1">Local</option>
              </select> 
            </div>
          </div>  
          <h1>Password Expiry Configuration</h1>
            <div class="col-12">
              <label class="control-label">{{ __('Password Expiry Time (in days)') }}</label>
              <input id="expiry" type="number" class="form-control" name="expiry_day" value=""/>
            </div>
          <div class="row col-4">
            <button type="submit" class="btn btn-primary mt-3">Add Buyer</button>
          </div>
      
      </div> 
    </form>  
  </div> 

@endsection
@section('footer')
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
  var states  = [];
  function changeState(ref) {
    var id = $(ref).val();

    function findState(states) {
      return states.statename == id;
    }

    var stateCode = states.find(findState);
      $("#statecode").val(stateCode.statecode);
  }
</script>
@endsection