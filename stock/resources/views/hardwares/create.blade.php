@extends('layouts.app')

@section('content')

  <div class="mx-2">
    <div class="row mx-0 my-2">
        <h2>Add Hardware</h2>
    </div>

    <form method="POST" action="{{ url('/hardwares/create') }}">
    	@csrf

      <!-- Form Starts -->
    	<div class="form-group">
        <div class=" row col-4">
          <label class="control-label">{{ __('Hardware Name') }}</label>
          <input type="text" class="form-control" name="name" required="required" />
          @if(isset($errors))
            <p class="mt-2 mb-0" style="color:red">{{$errors->first('name')}}</p>
            @endif
        </div>

        <div class=" row col-4 mt-3">
          <label class="control-label">{{ __('Hardware Rate') }}</label>
          <input type="number" class="form-control" name="rate" step="any" required="required" />
        </div>
		
		<div class=" row col-4 mt-3">
          <label class="control-label">{{ __('Hardware Supplier') }}</label>
          <input type="text" class="form-control" name="hardware_supplier" step="any" />
        </div>

        <div class="row col-4">
          <button type="submit" class="btn btn-primary mt-3">Add Hardware</button>
        </div>

      </div>
    </form>
  </div>

@endsection