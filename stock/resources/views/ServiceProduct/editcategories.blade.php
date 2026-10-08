@extends('layouts.app')

@section('content')
  <div class="mx-2">
    <div class="row mx-0 my-2">
        <h2>Edit Service Category</h2>
    </div>

    <form method="POST" action="{{ url('/service/categories/'.$serviceCategory->id) }}">
      @csrf
      @method('PUT') <!-- To indicate it's an update -->

      <!-- Form Starts -->
      <div class="form-group">
        <div class="row col-4">
          <label class="control-label">{{ __('Service Category Name') }}</label>
          <input type="text" class="form-control" name="name" placeholder="Service Category" required="required" value="{{ old('name', $serviceCategory->name) }}"/>
          
          @if(isset($errors))
            <p class="mt-2 mb-0" style="color:red">{{$errors->first('name')}}</p>
          @endif
        </div>
            
        <div class="row col-4">
          <button type="submit" class="btn btn-primary mt-3">Update Service Category</button>
        </div>
      </div>    
    </form>  
  </div> 
@endsection
