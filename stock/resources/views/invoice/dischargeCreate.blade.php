@extends('layouts.app')

@section('content')

  <div class="mx-2">
    <div class="row mx-0 my-2">
        <h2>Add Port of Discharge</h2>
    </div>

    <form method="POST" action="{{ url('/invoice/discharge/store') }}">
    	@csrf
      
      <!-- Form Starts -->
    	<div class="form-group">
        <div class=" row col-4">
          <label class="control-label">{{ __('Port of Discharge Name') }}</label>
          <input type="text" class="form-control" name="name" placeholder="Name" required="required" />
          @if(isset($errors))
            <p class="mt-2 mb-0" style="color:red">{{$errors->first('name')}}</p>
            @endif
        </div>
            
        <div class="row col-4">
          <button type="submit" class="btn btn-primary mt-3">Add Discharge</button>
        </div>
      
      </div>    
    </form>  
  </div> 

@endsection