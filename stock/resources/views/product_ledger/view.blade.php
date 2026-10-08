@extends('layouts.app')

@section('content')

  <div class="mx-2">
    <div class="row mx-0 my-2">
        <h2>Update Product Ledger</h2>
    </div>

    <form method="POST" action="{{ url('/product_ledger/view/'.$ProductLedger->id) }}">
      @csrf

      <!-- Form Starts -->
      <div class="form-group">
        <div class=" row col-4">
          <label class="control-label">{{ __('Product Ledger') }}</label>
          <input type="text" class="form-control" name="name" placeholder="Table, etc." required="required" value="{{$ProductLedger->name}}" />
        </div>
            
        <div class="row col-4">
          <button type="submit" class="btn btn-primary mt-3">Update Product Ledger</button>
        </div>
      
      </div>
      
    </form>  
  </div> 

@endsection