@extends('layouts.app')

@section('content')

  <div class="mx-2">
    <div class="row mx-0 my-2">
        <h2>Update Small Hardware</h2>
    </div>

    <form method="POST" action="{{ url('/smallhardwares/view/'.$smallhardwares->id) }}">
    	@csrf

      <!-- Form Starts -->
    	<div class="form-group">
        <div class=" row col-4">
          <label class="control-label">{{ __('Small Hardware Name') }}</label>
          <input type="text" class="form-control" name="name" required="required" value="{{$smallhardwares->name}}" />
        </div>

        <div class=" row col-4 mt-3">
          <label class="control-label">{{ __('Small Hardware Rate') }}</label>
          <input type="number" class="form-control" name="rate" step="any" required="required" value="{{$smallhardwares->rate}}"/>
        </div>
		
		<div class=" row col-4 mt-3">
          <label class="control-label">{{ __('Small Hardware Supplier') }}</label>
          <select class="form-control selectpicker" data-live-search="true" name="smallhardware_supplier">
            @foreach($suppliers as $supplier)
            <option value="{{$supplier->id}}" @if($smallhardwares->supplier == $supplier->id) selected @endif>{{$supplier->c_name}}</option>
            @endforeach
          </select>
          
        </div>

        <div class=" row col-4 mt-3">
          <label class="control-label">{{ __('Buyer') }}</label>
          <select class="form-control selectpicker" name="buyer">
            
            <option value="1" @if($smallhardwares->buyer == 1) selected @endif>UK-18</option>
            <option value="2" @if($smallhardwares->buyer == 2) selected @endif>Non UK-18</option>
            <option value="3" @if($smallhardwares->buyer == 3) selected @endif>Common</option>
           
          </select>
          
        </div>

        <div class="row col-4">
          <button type="submit" class="btn btn-primary mt-3">Update Hardware</button>
        </div>

      </div>
    </form>
  </div>

@endsection