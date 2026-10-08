@extends('layouts.app')

@section('content')

  <div class="mx-2">
    <div class="row mx-0 my-2">
        <h2>Add Notification</h2>
    </div>

    <form method="POST" action="{{ url('/notifications/create') }}">
    	@csrf

      <!-- Form Starts -->
    	<div class="form-group">
        <div class=" row col-4">
          <label class="control-label">{{ __('Notification') }}</label>
          <input type="text" class="form-control" name="notification" required="required" />
          @if(isset($errors))
            <p class="mt-2 mb-0" style="color:red">{{$errors->first('name')}}</p>
            @endif
        </div>

        <div class=" row col-4 mt-3">
          <label class="control-label">{{ __('Supplier') }}</label>
          <select class="form-control selectpicker" data-live-search="true" name="supplier">
            @foreach($suppliers as $supplier)
            <option value="{{$supplier->id}}">{{$supplier->c_name}}</option>
            @endforeach
          </select>
          
        </div>

        <div class="row col-4">
          <button type="submit" class="btn btn-primary mt-3">Create</button>
        </div>

      </div>
    </form>
  </div>

@endsection