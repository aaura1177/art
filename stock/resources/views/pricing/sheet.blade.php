@extends('layouts.app')

@section('content')

<div class="mx-2">
    <div class="row mx-0 my-2">
        <h2>Add Excel Sheet</h2>
         <div class="col">

        
        <a class="btn btn-primary float-end" style="color: #fff; margin-left: 5px;" href="{{ url('/pricing/Excel/sheetFormat')}}">Download Buying Cost</a>
      </div>
    </div>
        
    <form method="POST" action="{{ url('/pricing/Excel/sheet/store') }}" enctype="multipart/form-data">
        @csrf

        <div class="form-group">
            <div class="row">
                <div class="col-6">
                    <label class="control-label">{{ __('Sub-Category Sheet') }}</label>
                    <input type="file" class="form-control" name="sheet" required />
                    @error('sheet')
                    <p class="mt-2 mb-0 text-danger">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <div class="row col-4">
                <button type="submit" class="btn btn-primary mt-3">Add Excel</button>
            </div>

        </div>
    </form>
</div>

@endsection
