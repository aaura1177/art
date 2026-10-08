@extends('layouts.app')

@section('content')

  <div class="mx-2">
    <div class="row mx-0 my-2">
        <h2>Add Finish Rate</h2>
    </div>

    <form method="POST" action="{{ url('/finishing_rates/create') }}" enctype="multipart/form-data">
    	@csrf

      <!-- Form Starts -->
    	<div class="form-group">
        <div class="col-4">
          <label class="control-label">{{ __('Name') }}</label>
          <input type="text" class="form-control" name="name" required="required" />
          @if(isset($errors))
            <p class="mt-2 mb-0" style="color:red">{{$errors->first('name')}}</p>
            @endif
        </div>

    
		
		<div class="col-4 mt-3">
          <label class="control-label">{{ __('Rate') }}</label>
          <input type="number" class="form-control" name="rate" step="any" required="required" />
        </div>
		
		

        <div class="col-4">
          <button type="submit" class="btn btn-primary mt-3">Add Finish Rate</button>
        </div>

      </div>
    </form>
  </div>

@endsection