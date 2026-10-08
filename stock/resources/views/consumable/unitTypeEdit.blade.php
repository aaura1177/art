@extends('layouts.app')

@section('content')

<div class="mx-2">
  <div class="row mx-0 my-2">
      <h2>Edit Unit Type</h2>
  </div>

  <form method="POST" action="{{ url('/unitType/update/' . $unitType->id) }}">
    @csrf
    @method('PUT') <!-- Use PUT for update -->

    <div class="form-group">
      <div class="row mt-3">
        <div class="col-4">
          <label class="control-label">{{ __('Name') }}</label>
          <input type="text" class="form-control" name="name" value="{{ $unitType->name }}" required />
          @if($errors->has('name'))
            <p class="mt-2 mb-0 text-danger">{{ $errors->first('name') }}</p>
          @endif
        </div>



            <div class="col-4">
      <label class="control-label">{{ __('Data Type') }}</label>
      <select class="form-control" name="data_type" required>
        <option value="">Select Data Type</option>
        <option value="int" {{ old('data_type', $unitType->data_type) == 'int' ? 'selected' : '' }}>Non Decimal</option>
        <option value="float" {{ old('data_type', $unitType->data_type) == 'float' ? 'selected' : '' }}>Decimal</option>
      </select>

      @if($errors->has('data_type'))
        <p class="mt-2 mb-0 text-danger">{{ $errors->first('data_type') }}</p>
      @endif
    </div>

      </div>

      <div class="col-4">
        <button type="submit" class="btn btn-primary mt-3">Update Unit Type</button>
      </div>
    </div>
  </form>
</div>

@endsection
