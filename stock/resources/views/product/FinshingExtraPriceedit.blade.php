@extends('layouts.app')

@section('content')

  <div class="mx-2">
    <div class="row mx-0 my-2">
        <h2>Update Finshing Extra Price</h2>
    </div>

    <form method="POST" action="{{ url('/FinshingExtraPrice/update') }}" enctype="multipart/form-data">
    	@csrf

        <input type="hidden" name="id" value="{{$FinshingExtraPrice->id}}" id="">
      <!-- Form Starts -->
    	<div class="form-group">
        <div class="col-4">
          <label class="control-label">{{ __('Price') }}</label>
          <input type="number"  class="form-control" name="price" value="{{$FinshingExtraPrice->price}}" required="required" />
          @if(isset($errors))
            <p class="mt-2 mb-0" style="color:red">{{$errors->first('price')}}</p>
            @endif
        </div>

      
		  
	

        <div class="col-4">
          <button type="submit" class="btn btn-primary mt-3">Update Finshing Extra Price</button>
        </div>

      </div>
    </form>
  </div>

@endsection
