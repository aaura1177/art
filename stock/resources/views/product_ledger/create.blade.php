@extends('layouts.app')

@section('content')

  <div class="mx-2">
    <div class="row mx-0 my-2">
        <h2>Add Product Ledger</h2>
    </div>

    <form method="POST" action="{{ url('/product_ledger/create') }}">
    	@csrf
      
      <!-- Form Starts -->
    	<div class="form-group">
        <div class=" row col-4">
          <label class="control-label">{{ __('Product Ledger Name') }}</label>
          <input type="text" class="form-control" name="name" placeholder="Table, etc." required="required" />
          @if(isset($errors))
            <p class="mt-2 mb-0" style="color:red">{{$errors->first('name')}}</p>
            @endif
        </div>
            
        <div class="row col-4">
          <button type="submit" class="btn btn-primary mt-3">Add Product Ledger</button>
        </div>
      
      </div>    
    </form>  
  </div> 

@endsection