@extends('layouts.app')

@section('content')

  <div class="mx-2">
    <div class="row mx-0 my-2">
        <h2>Update Shipping Line</h2>
    </div>

    <form method="POST" action="{{ url('/shippingLines/view/'.$shippingLine->id) }}">
    	@csrf

      <!-- Form Starts -->
    	<div class="form-group">
        <div class=" row col-4">
          <label class="control-label">{{ __('Name') }}</label>
          <input type="text" class="form-control" name="name" required="required" value="{{$shippingLine->name}}" />
        </div>

        <div class=" row col-4 mt-3">
          <label class="control-label">{{ __('Agent Name') }}</label>
          <input type="text" class="form-control" name="agent_name" step="any" required="required" value="{{$shippingLine->agent_name}}"/>
        </div>

        <div class=" row col-4 mt-3">
          <label class="control-label">{{ __('UK Agent Name') }}</label>
          <input type="text" class="form-control" name="agent_uk" step="any" required="required" value="{{$shippingLine->agent_uk}}"/>
        </div>

        <div class="row col-4">
          <button type="submit" class="btn btn-primary mt-3">Update Shipping Line</button>
        </div>

      </div>
    </form>
  </div>

@endsection