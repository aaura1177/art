@extends('layouts.app')

@section('content')

  <div class="mx-2">
    <div class="row mx-0 my-2">
        <h2>Add Small Hardware</h2>
    </div>

    <form method="POST" action="{{ url('/smallhardwares/create') }}">
    	@csrf

      <!-- Form Starts -->
    	<div class="form-group">
        <div class=" row col-4">
          <label class="control-label">{{ __('Small Hardware Name') }}</label>
          <input type="text" class="form-control" name="name" required="required" />
          @if(isset($errors))
            <p class="mt-2 mb-0" style="color:red">{{$errors->first('name')}}</p>
            @endif
        </div>

        <div class=" row col-4 mt-3">
          <label class="control-label">{{ __('Small Hardware Rate') }}</label>
          <input type="number" class="form-control" name="rate" step="any" required="required" />
        </div>
		
		    <div class=" row col-4 mt-3">
          <label class="control-label">{{ __('Small Hardware Supplier') }}</label>
          <select class="form-control selectpicker" data-live-search="true" name="smallhardware_supplier">
            @foreach($suppliers as $supplier)
            <option value="{{$supplier->id}}">{{$supplier->c_name}}</option>
            @endforeach
          </select>
          
        </div>
		    <div class=" row col-4 mt-3">
          <label class="control-label">{{ __('Buyer') }}</label>
          <select class="form-control selectpicker" name="buyer">
            
            <option value="1">UK-18</option>
            <option value="2">Non UK-18</option>
            <option value="3">Common</option>
           
          </select>
          
        </div>

        <div class="row col-4">
          <button type="submit" class="btn btn-primary mt-3">Add Small Hardware</button>
        </div>

      </div>
    </form>
  </div>

@endsection