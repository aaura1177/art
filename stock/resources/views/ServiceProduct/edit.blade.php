@extends('layouts.app')

@section('content')

<div class="mx-2">
    <div class="row mx-0 my-2">
        <h2>Edit Service Product</h2>
    </div>

    <form method="POST" action="{{ url('/service/product/update/'.$product->id) }}" enctype="multipart/form-data">
        @csrf
        @method('PUT') 
    
        <!-- Form Starts -->
        <div class="form-group">
            <!-- first row -->
            <div class="row mt-3">
                <div class="col-4">
                    <label class="control-label">{{ __('Service Category') }}</label>
                    <a href="{{ url('/purchaseOrder/service/categories') }}" style="float: right;" target="_blank"> (+New)</a>
                    <select class="selectpicker" data-live-search="true" name="category" required="required">
                        @if(isset($serviceCategories))
                            @foreach($serviceCategories as $category)
                                <option value="{{$category->id}}" @if($category->id == $product->service_category_id) selected @endif>
                                    {{$category->name}}
                                </option>
                            @endforeach
                        @endif
                    </select>
                </div>
    
                <div class="col-4">
                    <label class="control-label">{{ __('Select Image') }}</label>
                    <!-- Display existing image if available -->
                    @if($product->imageURL)
                    <div>
                        <!-- Displaying the image with the correct path -->
                        <img src="{{ $product->imageURL ? asset('images/'.$product->imageURL) : asset('images/default.jpg') }}" class="img-thumbnail" style="max-width: 150px; max-height: 150px;" alt="Product Image">
                    </div>
                @else
                    <p>No image available</p>
                @endif
                
                    <input type="file" class="form-control-file" name="imgURL" value="{{ old('imgURL', $product->imageURL) }}" />
                </div>
            </div>
    
            <!-- second row -->
          
    
            <!-- third row -->
            <div class="row mt-3">
                <div class="col-4">
                    <label class="control-label">{{ __('Product Name') }}</label>
                    <input type="text" class="form-control" name="name" required="required" value="{{ old('name', $product->name) }}" />
                </div>
    
                <div class="col-4">
                    <label class="control-label">{{ __('GST Slab (%)') }}</label>
                    <select class="selectpicker" data-live-search="true" name="gstslab" required="required">
                        <option value="0" @if($product->gstslab == 0) selected @endif>0</option>
                        <option value="5" @if($product->gstslab == 5) selected @endif>5</option>
                        <option value="12" @if($product->gstslab == 12) selected @endif>12</option>
                        <option value="18" @if($product->gstslab == 18) selected @endif>18</option>
                        <option value="28" @if($product->gstslab == 28) selected @endif>28</option>
                    </select>
                </div>
            </div>
    
            <!-- sixth row -->
            <div class="row mt-3">
                <div class="col-4">
                    <label class="control-label">{{ __('Quantity') }}</label>
                    <input type="number" class="form-control" name="quantity" value="{{ old('quantity', $product->quantity) }}"/>
                </div>
                <div class="col-4">
                    <label class="control-label">{{ __('Remarks') }}</label>
                    <textarea class="form-control" name="remarks">{{ old('remarks', $product->remarks) }}</textarea>
                </div>
            </div>
    
            <div class="row mt-3">
                <div class="col-4">
                    <button type="submit" class="btn btn-primary mt-3">Update Product</button>
                </div>
            </div>
        </div>
    </form>
</div>


<!-- Script Start -->
<script>
    $("#width, #height, #depth").on('change', function() {
        var width = $("#width").val();
        var height = $("#height").val();
        var depth = $("#depth").val();
        var output = (width * height * depth) / 1000000;
        $("#volume").val(output.toFixed(4));
    });

    $("#boxwidth, #boxheight, #boxdepth").on('change', function() {
        var boxwidth = $("#boxwidth").val();
        var boxheight = $("#boxheight").val();
        var boxdepth = $("#boxdepth").val();
        var boxoutput = ((boxwidth - 5) * (boxheight - 5) * (boxdepth - 5)) / 1000000;
        var boxdshipoutput = (boxwidth * boxheight * boxdepth) / 1000000;
        $("#wholesalevolume").val(boxoutput.toFixed(4));
        $("#dropshipvolume").val(boxdshipoutput.toFixed(4));
    });

    function calFinish(obj, sel) {
        if (sel) {
            $('#finishRate').val($(obj).val());
            $('#finishing').val($(obj).find('option:selected').html());
        }
        var rate = $('#finishRate').val();
        var h = $('#height').val();
        var w = $('#width').val();
        var d = $('#depth').val();
        var x = (h * w * d) / 1000000;
        var frate = Math.round((rate / 60) * x);
        var rd = frate % 5;
        if (rd < 3) {
            frate = frate - rd;
        } else {
            dif = 5 - rd;
            frate = frate + dif;
        }
        $('#finishing_price').val(frate);
    }
</script>
<!-- End -->

@endsection