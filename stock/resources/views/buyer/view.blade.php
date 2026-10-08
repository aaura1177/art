@extends('layouts.app')

@section('content')

  <div class="mx-2">
    <div class="row mx-0 my-2">
        <h2>Edit Buyer</h2>
    </div>

    <form method="POST" action="{{ url('/buyer/view/'.$buyer->id) }}">
      @csrf

      <!-- Form Starts -->
      <div class="form-group"> 
          
          <!-- first row -->
          <div class="row mt-3">  
            <div class="col-4">
              <label class="control-label">{{ __('Buyer Code') }}</label>
              <input type="text" class="form-control toUpperCase" name="code" required="required" value="{{$buyer->code}}" disabled />
            </div>
            <div class="col-4">
              <label class="control-label">{{ __('Company Name') }}</label>
              <input type="text" class="form-control" name="c_name" value="{{$buyer->c_name}}" required />
            </div>
            <div class="col-4">
              <label class="control-label">{{ __('Name') }}</label>
              <input type="text" class="form-control" name="name" value="{{$buyer->name}}" required />
            </div>
          </div>  

          <div class="row mt-3">  
         
            <div class="col-4">
              <label class="control-label">{{ __('Email') }}</label>
              <input type="email" class="form-control" name="email" value="{{$buyer->email}}" />
            </div>
            <div class="col-4">
              <label class="control-label">{{ __('Phone') }}</label>
              <input type="number" class="form-control" name="phone" value="{{$buyer->phone}}" />
            </div>
            <div class="col-4">
              <label class="control-label">{{ __('Tally Name') }}</label>
              <input type="text" class="form-control" name="tally_name" value="{{$buyer->tally_name}}" />
            </div> 
          </div> 
          <!-- second row -->
          <div class="row mt-3">
            <div class="col-4">
              <label class="control-label">{{ __('Address Line 1') }}</label>
              <input type="text" class="form-control" name="address1" value="{{$buyer->address1}}" required/>
            </div>
            <div class="col-4">
              <label class="control-label">{{ __('Address Line 2') }}</label>
              <input type="text" class="form-control" name="address2" value="{{$buyer->address2}}" />
            </div>
            <div class="col-4">
              <label class="control-label">{{ __('City') }}</label>
              <input type="text" class="form-control" name="city" value="{{$buyer->city}}" required />
            </div>
          </div>
              
          <!-- third row -->
          <div class="row mt-3">
            <div class="col-4">
              <label class="control-label">{{ __('State') }}</label>
              <input type="text" class="form-control" name="state" value="{{$buyer->state}}" required />
            </div>
            <div class="col-4">
              <label class="control-label">{{ __('Country') }}</label>
               <input type="text" class="form-control" name="country" value="{{$buyer->country}}" required />
            </div>
            <div class="col-4">
              <label class="control-label">{{ __('Postcode') }}</label>
              <input type="text" class="form-control" name="postcode" value="{{$buyer->postcode}}" required />
            </div>  
          </div>  

           <!-- fourth row -->
           <div class="row mt-3">
            <div class="col-4">
              <label class="control-label">{{ __('State Code') }}</label>
              <input type="text" class="form-control" name="state_code" value="{{$buyer->state_code}}" readonly />
            </div>
            <div class="col-4">
              <label class="control-label">{{ __('GST No') }}</label>
              <input type="text" class="form-control" name="gstno" value="{{$buyer->gstno}}"  />
            </div>
            <div class="col-4">
              <label class="control-label">{{ __('Type') }}</label>
              <select type="text" class="selectpicker" data-live-search="true" name="buyertype" required="required" >
                <option value="0" {{($buyer->buyertype == 0)?'selected':''}}>Global</option>
                <option value="1" {{($buyer->buyertype == 1)?'selected':''}}>Local</option>
              </select> 
            </div>
          </div>  
          
          <div class="row col-4">
            <button type="submit" class="btn btn-primary mt-3">Update Buyer</button>
          </div>

      </div>
    </form>  
  </div> 

@endsection