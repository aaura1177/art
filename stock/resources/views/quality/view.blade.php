@extends('layouts.app')

@section('content')

<div class="mx-2">
    <div class="row mx-0 my-2">
        <h2>Quality</h2>
    </div>

    <form method="POST" action="{{ url('/quality/view') }}/{{$quality->id}}" enctype="multipart/form-data">
      @csrf

      <!-- Form Starts -->
      <div class="form-group"> 
          
          <!-- first row -->
          <div class="row mt-3">  
            <div class="col-4">
              <label class="control-label">{{ __('Product Code') }}</label> <a href="{{ url('/product/create')}}" style="float: right;"> (+New)</a>
              <select type="text" class="selectpicker" data-live-search="true" name="product_id" required="required">
                <option value="" selected >Select Product Code</option>
                @if(isset($product)) @foreach($product as $key => $product)
                  <option value="{{$product->id}}" {{($quality->product_id == $product->id)?'selected':''}}>
                  {{$product->code}} - {{$product->name}}
                  </option>
                @endforeach @endif
                <div class="dropdown-divider"></div>
                
              </select>
            </div>
            <div class="col-4">
              <label class="control-label">{{ __('Channel') }}</label> <a href="{{ url('/supplier/create')}}" style="float: right;"> (+New)</a>
              <select type="text" class="selectpicker" data-live-search="true" name="channel_id" required="required" >
                <option value="" selected disabled>Select Channel</option>
                @if(isset($channel)) @foreach($channel as $key => $channel)
                <option value="{{$channel->id}}" {{($quality->channel_id == $channel->id)?'selected':''}}>
                  {{$channel->name}}
                  </option>
                @endforeach @endif
              </select>
            </div>
            
          </div>

          <!-- second row -->
          <div class="row mt-3">  
            <div class="col-4">
                <label class="control-label">{{ __('Artisan Order No.') }}</label>
                <input type="text" class="form-control toUpperCase" name="supplier_inv_no" required="required" value="{{$quality->supplier_inv_no}}" />
            </div>
            <div class="col-4">
              <label class="control-label">{{ __('Quantity') }}</label>
              <input type="number" min="1" class="form-control" name="quantity" required="required" value="{{$quality->quantity}}" />
            </div>
            <div class="col-4">
                <label class="control-label">{{ __('Date') }}</label>
                <input type="date" class="form-control" name="date" required="required" value="{{$quality->date}}" />
            </div>
          </div> 

          <!-- third row -->
          <div class="row mt-3">
            <div class="col-4">
                <label class="control-label">{{ __('Remarks') }}</label>
                <textarea class="form-control" name="remarks" required="required" height="20px">{{$quality->remarks}}</textarea>
            </div>
			
			<div class="col-4">
              <label class="control-label">{{ __('Select Image') }}</label>
              <input type="file" class="form-control-file" name="imgURL[]" value="{{ old('imgURL') }}" multiple />
            </div>
          </div> 

        <div class="row col-4">
          <button type="submit" class="btn btn-primary mt-3">Submit</button>
        </div>

      </div>
    </form>  
</div>
@endsection

@section('footer')

@endsection