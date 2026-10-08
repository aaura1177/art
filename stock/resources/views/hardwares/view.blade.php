@extends('layouts.app')

@section('content')

  <div class="mx-2">
    <div class="row mx-0 my-2">
        <h2>Update Hardware</h2>
    </div>

    <form method="POST" action="{{ url('/hardwares/view/'.$hardwares->id) }}">
    	@csrf

      <!-- Form Starts -->
    	<div class="form-group">
        <div class=" row col-4">
          <label class="control-label">{{ __('Hardware Name') }}</label>
          <input type="text" class="form-control" name="name" required="required" value="{{$hardwares->name}}" />
        </div>

        <div class=" row col-4 mt-3">
          <label class="control-label">{{ __('Hardware Rate') }}</label>
          <input type="number" class="form-control" name="rate" step="any" required="required" value="{{$hardwares->rate}}"/>
        </div>
		
		<div class=" row col-4 mt-3">
          <label class="control-label">{{ __('Hardware Supplier') }}</label>
          <input type="text" class="form-control" name="hardware_supplier" step="any" value="{{$hardwares->hardwareSuppliers ? $hardwares->hardwareSuppliers->name : ''}}"/>
        </div>

        <div class="row col-4">
          <button type="submit" class="btn btn-primary mt-3">Update Hardware</button>
        </div>

      </div>
    </form>
  </div>

@endsection