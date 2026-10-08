@extends('layouts.app')

@section('content')

  <div class="mx-2">
    <div class="row mx-0 my-2">
        <h2>Add Shipping Line</h2>
    </div>

    <form method="POST" action="{{ url('/shippingLines/create') }}">
    	@csrf

      <!-- Form Starts -->
    	<div class="form-group">
        <div class=" row col-4">
          <label class="control-label">{{ __('Name') }}</label>
          <input type="text" class="form-control" name="name" required="required" />
          @if(isset($errors))
            <p class="mt-2 mb-0" style="color:red">{{$errors->first('name')}}</p>
            @endif
        </div>
        <div class=" row col-4 mt-3">
          <label class="control-label">{{ __('Agent Name') }}</label>
          <input type="text" class="form-control" name="agent_name" required="required" />
        </div>
        <div class=" row col-4 mt-3">
          <label class="control-label">{{ __('UK Agent Name') }}</label>
          <input type="text" class="form-control" name="agent_uk" required="required"/>
        </div>
        <div class="row col-4">
          <button type="submit" class="btn btn-primary mt-3">Add Shipping Line</button>
        </div>

      </div>
    </form>
  </div>

@endsection