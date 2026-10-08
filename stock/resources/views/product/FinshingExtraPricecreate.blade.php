@extends('layouts.app')

@section('content')

  <div class="mx-2">
    <div class="row mx-0 my-2">
        <h2>Add Finshing Extra Price</h2>
    </div>

    <form method="POST" action="{{ url('/FinshingExtraPrice/store') }}" enctype="multipart/form-data">
    	@csrf

      <!-- Form Starts -->
    	<div class="form-group">
        <div class="col-4">
          <label class="control-label">{{ __('Price') }}</label>
          <input type="number"  class="form-control" name="price" required="required" />
          @if(isset($errors))
            <p class="mt-2 mb-0" style="color:red">{{$errors->first('name')}}</p>
            @endif
        </div>

      
		  
	

        <div class="col-4">
          <button type="submit" class="btn btn-primary mt-3">Add Finshing Extra Price</button>
        </div>

      </div>
    </form>
  </div>

@endsection
