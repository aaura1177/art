@extends('layouts.app')

@section('content')
    <div class="mx-2">
        <div class="row mx-0 my-2">
            <h2>Add Unit Type</h2>
        </div>

        <form method="POST" action="{{ url('/unitType/create') }}" enctype="multipart/form-data">
            @csrf

            <!-- Form Starts -->
            <div class="form-group">
                <div class="row mt-3">
                    <div class="col-4">
                        <label class="control-label">{{ __('Name') }}</label>
                        <input type="text" class="form-control" name="name" required="required" />
                        @if (isset($errors))
                            <p class="mt-2 mb-0" style="color:red">{{ $errors->first('name') }}</p>
                        @endif
                    </div>

                    <div class="col-4">
                        <label class="control-label">{{ __('Data Type') }}</label>
                        <select class="form-control" name="data_type" required>
                            <option value="">Select  Type</option>
                            <option value="int" selected>Non Decimal</option>
                            <option value="float">Decimal</option>
                        </select>

                        @if (isset($errors))
                            <p class="mt-2 mb-0" style="color:red">{{ $errors->first('name') }}</p>
                        @endif
                    </div>





                </div>




                <!--<div class="col-4 mt-3">
              <label class="control-label">{{ __('Image') }}</label>
              <input type="file" class="form-control" name="payment_terms" step="any" />
            </div>-->

                <div class="col-4">
                    <button type="submit" class="btn btn-primary mt-3">Add Unit Type</button>
                </div>

            </div>
        </form>
    </div>
@endsection
