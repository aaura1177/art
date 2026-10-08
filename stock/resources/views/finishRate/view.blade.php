@extends('layouts.app')

@section('content')

  <div class="mx-2">
    <div class="row mx-0 my-2">
        <h2>Edit Finish Rate</h2>
    </div>

    <form method="POST" action="{{ url('/finishing_rates/update', $finishRate->id) }}" enctype="multipart/form-data">
        @csrf
        @method('PUT') 
      

      <!-- Form Starts -->
      <div class="form-group">
        
        <!-- Name Input -->
        <div class="col-4">
          <label class="control-label">{{ __('Name') }}</label>
          <input type="text" class="form-control" name="name" required="required" value="{{$finishRate->name}}" />
          @if($errors->has('name'))
            <p class="mt-2 mb-0" style="color:red">{{ $errors->first('name') }}</p>
          @endif
        </div>

        <!-- Old Rate Input (readonly) -->
        <div class="col-4 mt-3">
          <label class="control-label">{{ __('Old Rate') }}</label>
          <input type="number" class="form-control" name="old_rate" step="any" readonly value="{{ $finishRate->old_rate}}" />
        </div>

        <!-- Rate Input -->
        <div class="col-4 mt-3">
          <label class="control-label">{{ __('Rate') }}</label>
          <input type="number" class="form-control" name="rate" step="any" required="required" value="{{$finishRate->rate}}" />
        </div>

        <!-- Submit Button -->
        <div class="col-4 mt-3">
          <button type="submit" class="btn btn-primary">Update Finish Rate</button>
        </div>

      </div>
    </form>
  </div>

@endsection
