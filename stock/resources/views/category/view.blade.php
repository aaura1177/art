@extends('layouts.app')

@section('content')

  <div class="mx-2">
    <div class="row mx-0 my-2">
        <h2>Update Category</h2>
    </div>

    <form method="POST" action="{{ url('/category/view/'.$category->id) }}">
      @csrf

      <!-- Form Starts -->
      <div class="form-group">
        <div class=" row col-4">
          <label class="control-label">{{ __('Category Name') }}</label>
          <input type="text" class="form-control" name="category_name" placeholder="Table, etc." required="required" value="{{$category->name}}" />
        </div>
            
        <div class="row col-4">
          <button type="submit" class="btn btn-primary mt-3">Update Category</button>
        </div>
      
      </div>
      
    </form>  
  </div> 

@endsection