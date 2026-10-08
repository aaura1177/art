@extends('layouts.app')

@section('content')

  <div class="mx-2">
    <div class="row mx-0 my-2">
        <h2>Add Temporary Product</h2>
    </div>

    <form method="POST" action="{{ url('/temporaryProduct/create') }}" enctype="multipart/form-data">
    @csrf

    <!-- Form Starts -->
      <div class="form-group"> 
        <!-- first row -->
          <div class="row mt-3">
            <div class="col-4">
              <label class="control-label">{{ __('Product Category') }}</label><a href="{{ url('/category/create')}}" style="float: right;"  target="_blank"> (+New)</a>
              <select type="text" class="selectpicker" data-live-search="true" name="category">
                    @if(isset($category)) @foreach($category as $key => $category)
                    <option value="{{$category->id}}">
                    {{$category->name}}
                    </option>
                    @endforeach @endif        
              </select>
            </div>
            <div class="col-4">
              <label class="control-label">{{ __('Product Sub-Category') }}</label><a href="{{ url('/category/subCategory/create')}}" style="float: right;"  target="_blank"> (+New)</a>
              <select type="text" class="selectpicker" data-live-search="true" name="subcategory">
                    @if(isset($subCategory)) @foreach($subCategory as $key => $subCategories)
                    <option value="{{$subCategories->id}}">
                    {{$subCategories->name}}
                    </option>
                    @endforeach @endif        
              </select>
            </div>
            <div class="col-4">
              <label class="control-label">{{ __('Select Image') }}</label>
              <input type="file" class="form-control-file" name="imgURL" value="{{ old('imgURL') }}" />
            </div>
          </div>

          <!-- second row -->
          <div class="row mt-3">
            <div class="col-4">
              <label class="control-label">{{ __('Product Code') }}</label>
              <input type="text" class="form-control toUpperCase"  name="code" required="required" value="{{ old('code') }}" />
              @if(isset($errors))
              <p class="mt-2 mb-0" style="color:red">{{$errors->first('code')}}</p>
              @endif
            </div>
            <div class="col-4">
              <label class="control-label">{{ __('EAN Code') }}</label>
              <input type="text" class="form-control toUpperCase" name="ean" step="any" value="{{ old('ean') }}" />
              @if(isset($errors))
              <p class="mt-2 mb-0" style="color:red">{{$errors->first('ean')}}</p>
              @endif
            </div>
            <div class="col-4">
              <label class="control-label">{{ __('HSN Code') }}</label>
              <input type="text" class="form-control toUpperCase" name="hsn" step="any" value="{{ old('hsn') }}" />
            </div>
          </div>

          <!-- third row -->
          <div class="row mt-3">  
            <div class="col-4">
              <label class="control-label">{{ __('Product Name') }}</label>
              <input type="text" class="form-control" name="name" value="{{ old('name') }}" required="required" />
            </div>
            <div class="col-4">
              <label class="control-label">{{ __('Finishing') }}</label>
              <input type="text" class="form-control" name="finishing" value="{{ old('finishing') }}" />
            </div>
            <div class="col-4">
              <label class="control-label">{{ __('GST Slab (%)') }}</label>
              <select type="text" class="selectpicker" data-live-search="true" name="gstslab">
                    <option value="0">0</option>
                    <option value="5">5</option>
                    <option value="12">12</option>
                    <option value="18">18</option>
                    <option value="28">28</option>
              </select>
            </div>
          </div>

          <!-- fourth row -->
          <div class="row mt-3">  
            <div class="col-4">
              <label class="control-label">{{ __('Width') }}</label>
              <input type="number" min="0" class="form-control" name="width" id="width" step="any" placeholder=" in cm" value="{{ old('width') }}" />
            </div>
            <div class="col-4">
              <label class="control-label">{{ __('Height') }}</label>
              <input type="number" min="0" class="form-control" name="height" id="height" step="any" placeholder=" in cm" value="{{ old('height') }}" />
            </div>
            <div class="col-4">
              <label class="control-label">{{ __('Depth') }}</label>
              <input type="number" min="0" class="form-control" name="depth" id="depth" step="any" placeholder=" in cm" value="{{ old('depth') }}" />
            </div>
          </div>

          <!-- fourth row -->
          <div class="row mt-3">  
            <div class="col-4">
              <label class="control-label">{{ __('Box Width') }}</label>
              <input type="number" min="0" class="form-control" name="boxwidth" id="boxwidth" step="any" placeholder=" in cm" value="{{ old('boxwidth') }}" />
            </div>
            <div class="col-4">
              <label class="control-label">{{ __('Box Height') }}</label>
              <input type="number" min="0" class="form-control" name="boxheight" id="boxheight" step="any" placeholder=" in cm" value="{{ old('boxheight') }}" />
            </div>
            <div class="col-4">
              <label class="control-label">{{ __('Box Depth') }}</label>
              <input type="number" min="0" class="form-control" name="boxdepth" id="boxdepth" step="any" placeholder=" in cm" value="{{ old('boxdepth') }}" />
            </div>
          </div>  

          <!-- fifth row -->
          <div class="row mt-3">
            <div class="col-4">
              <label class="control-label">{{ __('WholeSale Volume') }} <span>(m<sup>3</sup>)</span></label>
              <input type="number" min="0" class="form-control" name="wholesalevolume" id="wholesalevolume" step="any" placeholder="m3" value="{{ old('wholesalevolume') }}" readonly />
            </div>
            <div class="col-4">
              <label class="control-label">{{ __('Dropship Volume') }} <span>(m<sup>3</sup>)</span></label>
              <input type="number" min="0" class="form-control" name="dropshipvolume" id="dropshipvolume" step="any" placeholder="m3" value="{{ old('dropshipvolume') }}" readonly />
            </div>
            <div class="col-4" style="display: none;">
              <label class="control-label">{{ __('Volume') }} <span>(m<sup>3</sup>)</span></label>
              <input type="number" min="0" class="form-control" name="volume" id="volume" step="any" placeholder="m3" value="{{ old('volume') }}" readonly />
            </div>
          </div>

          <!-- sixth row -->
          <div class="row mt-3">
            <div class="col-4">
              <label class="control-label">{{ __('Hardware') }}</label>
              <textarea type="text-area" class="form-control" name="hardware">{{ old('hardware') }}</textarea>
            </div>
            <div class="col-4">
              <label class="control-label">{{ __('Add-ons') }}</label>
              <textarea type="text" class="form-control" name="addons">{{ old('addons') }}</textarea>
            </div>
            <div class="col-4">
              <label class="control-label">{{ __('Remarks') }}</label>
              <textarea type="text" class="form-control" name="remarks">{{ old('remarks') }}</textarea>
            </div>
          </div>  
            
        <div class="row col-4">
          <button type="submit" class="btn btn-primary mt-3">Add Temporary Product</button>
        </div>
      
      </div>
    </form>
</div>

<!-- Script Start -->
<script>
$("#width, #height, #depth").on('change',function() {
    var width = $("#width").val();
    var height = $("#height").val();
    var depth = $("#depth").val();
    var output = (width*height*depth)/1000000;   
    $("#volume").val(output.toFixed(4));
});

$("#boxwidth, #boxheight, #boxdepth").on('change',function() {
    var boxwidth = $("#boxwidth").val();
    var boxheight = $("#boxheight").val();
    var boxdepth = $("#boxdepth").val();
    var boxoutput = ((boxwidth-5)*(boxheight-5)*(boxdepth-5))/1000000;
    var boxdshipoutput = (boxwidth*boxheight*boxdepth)/1000000;
    $("#wholesalevolume").val(boxoutput.toFixed(4));
    $("#dropshipvolume").val(boxdshipoutput.toFixed(4));
});
</script>
<!-- End -->

@endsection