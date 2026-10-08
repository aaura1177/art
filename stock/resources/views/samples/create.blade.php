@extends('layouts.app')

@section('content')

  <div class="mx-2">
    <div class="row mx-0 my-2">
        <h2>Add Sample</h2>
    </div>

    <form method="POST" action="{{ url('/samples/create') }}" enctype="multipart/form-data">
    @csrf

    <!-- Form Starts -->
      <div class="form-group"> 
        <!-- first row -->
          <div class="row mt-3">
            <div class="col-4">
              <label class="control-label">{{ __('Product Category') }}</label><a href="{{ url('/category/create')}}" style="float: right;"  target="_blank"> (+New)</a>
              <select type="text" class="selectpicker" data-live-search="true" name="category" required="required">
                    @if(isset($category)) @foreach($category as $key => $category)
                    <option value="{{$category->id}}">
                    {{$category->name}}
                    </option>
                    @endforeach @endif        
              </select>
            </div>
            <div class="col-4">
              <label class="control-label">{{ __('Product Sub-Category') }}</label><a href="{{ url('/category/subCategory/create')}}" style="float: right;"  target="_blank"> (+New)</a>
              <select type="text" class="selectpicker" data-live-search="true" name="subcategory" required="required">
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

          

          <!-- third row -->
          <div class="row mt-3">  
            <div class="col-4">
              <label class="control-label">{{ __('Name') }}</label>
              <input type="text" class="form-control" name="name" required="required" value="{{ old('name') }}" />
            </div>
            <div class="col-4">
              <label class="control-label">{{ __('Finishing') }}</label>
			  <div class="row">
				<div class="col-12">
					<input placeholder="Finishing" type="text" class="form-control" name="finishing" value="{{ old('finishing') }}" />
				</div>
			  </div>
            </div>
            
          </div>

          <!-- fourth row -->
          <div class="row mt-3">
            <div class="col-4">
              <label class="control-label">{{ __('Product Height (cm)') }}</label>
              <input type="number" min="0" class="form-control" name="height" id="height" step="any" required="required" placeholder=" in cm" value="{{ old('height') }}" />
            </div>  
            <div class="col-4">
              <label class="control-label">{{ __('Product Width (cm)') }}</label>
              <input type="number" min="0" class="form-control" name="width" id="width" step="any" required="required" placeholder=" in cm" value="{{ old('width') }}" />
            </div>
            <div class="col-4">
              <label class="control-label">{{ __('Product Depth (cm)') }}</label>
              <input type="number" min="0" class="form-control" name="depth" id="depth" step="any" required="required" placeholder=" in cm" value="{{ old('depth') }}" />
            </div>
          </div>

          <!-- fourth row -->
          <div class="row mt-3">
            <div class="col-4">
              <label class="control-label">{{ __('Box Height (cm)') }}</label>
              <input type="number" min="0" class="form-control" name="boxheight" id="boxheight" step="any" placeholder=" in cm" value="{{ old('boxheight') }}" required="required"/>
            </div>  
            <div class="col-4">
              <label class="control-label">{{ __('Box Width (cm)') }}</label>
              <input type="number" min="0" class="form-control" name="boxwidth" id="boxwidth" step="any" placeholder=" in cm" value="{{ old('boxwidth') }}" required="required"/>
            </div>
            <div class="col-4">
              <label class="control-label">{{ __('Box Depth (cm)') }}</label>
              <input type="number" min="0" class="form-control" name="boxdepth" id="boxdepth" step="any" placeholder=" in cm" value="{{ old('boxdepth') }}" required="required"/>
            </div>
          </div>  

          <!-- fifth row -->
          <div class="row mt-3">
            <div class="col-4">
              <label class="control-label">{{ __('WholeSale Volume') }} <span>(m<sup>3</sup>)</span></label>
              <input type="number" min="0" class="form-control" name="wholesalevolume" id="wholesalevolume" step="any" placeholder="m3" value="{{ old('wholesalevolume') }}" readonly />
            </div>
            <div class="col-4">
              <label class="control-label">{{ __('DropShip Volume') }} <span>(m<sup>3</sup>)</span></label>
              <input type="number" min="0" class="form-control" name="dropshipvolume" id="dropshipvolume" step="any" placeholder="m3" value="{{ old('dropshipvolume') }}" readonly />
            </div>
            <div class="col-4 d-none">
              <label class="control-label">{{ __('Volume') }} <span>(m<sup>3</sup>)</span></label>
              <input type="number" min="0" class="form-control" name="volume" id="volume" step="any" required="required" placeholder="m3" value="{{ old('volume') }}" readonly />
            </div>
          </div>

          <!-- sixth row -->
          <div class="row mt-3">
            <div class="col-4">
              <label class="control-label">{{ __('Add-ons') }}</label>
              <textarea type="text" class="form-control" name="addons">{{ old('addons') }}</textarea>
            </div>
            <div class="col-4">
              <label class="control-label">{{ __('Remarks') }}</label>
              <textarea type="text" class="form-control" name="remarks">{{ old('remarks') }}</textarea>
            </div>
          </div> 
          <!-- Hardwares -->
           <!-- seventh row -->
           <div class="row mt-3">
            <div class="col-4">
				<label class="control-label">{{ __('Hardware 1') }}</label>
				<div class="row">
					<div class="col-9">
						  <select type="text" class="selectpicker" data-live-search="true" name="hardware1">
								<option value="0">Select</option>
								@if(isset($hardwares)) @foreach($hardwares as $key => $hardware)
								<option value="{{$hardware->id}}">
								{{$hardware->name}}
								</option>
								@endforeach @endif        
						  </select>
					</div>
					<div class="col-3">
						<input type="number" min="0" class="form-control" name="hardware1_quantity" id="hardware1_quantity" step="any" placeholder="Q" value="{{ old('hardware1_quantity') }}" />
					</div>
				</div>
            </div>
            <div class="col-4">
				<label class="control-label">{{ __('Hardware 2') }}</label>
				<div class="row">
					<div class="col-9">
						  <select type="text" class="selectpicker" data-live-search="true" name="hardware2">
								<option value="0">Select</option>
								@if(isset($hardwares)) @foreach($hardwares as $key => $hardware)
								<option value="{{$hardware->id}}">
								{{$hardware->name}}
								</option>
								@endforeach @endif        
						  </select>
					</div>
					<div class="col-3">
						<input type="number" min="0" class="form-control" name="hardware2_quantity" id="hardware2_quantity" step="any" placeholder="Q" value="{{ old('hardware2_quantity') }}" />
					</div>
				</div>
            </div>
            <div class="col-4">
				<label class="control-label">{{ __('Hardware 3') }}</label>
				<div class="row">
					<div class="col-9">
						  <select type="text" class="selectpicker" data-live-search="true" name="hardware3">
								<option value="0">Select</option>
								@if(isset($hardwares)) @foreach($hardwares as $key => $hardware)
								<option value="{{$hardware->id}}">
								{{$hardware->name}}
								</option>
								@endforeach @endif        
						  </select>
					</div>
					<div class="col-3">
						<input type="number" min="0" class="form-control" name="hardware3_quantity" id="hardware3_quantity" step="any" placeholder="Q" value="{{ old('hardware3_quantity') }}" />
					</div>
				</div>
            </div>
          </div>
          <!-- eighth row -->
          <div class="row mt-3">
            <div class="col-4">
				<label class="control-label">{{ __('Hardware 4') }}</label>
				<div class="row">
					<div class="col-9">
					  <select type="text" class="selectpicker" data-live-search="true" name="hardware4">
							<option value="0">Select</option>
							@if(isset($hardwares)) @foreach($hardwares as $key => $hardware)
							<option value="{{$hardware->id}}">
							{{$hardware->name}}
							</option>
							@endforeach @endif        
					  </select>
					</div>
					<div class="col-3">
						<input type="number" min="0" class="form-control" name="hardware4_quantity" id="hardware4_quantity" step="any" placeholder="Q" value="{{ old('hardware4_quantity') }}" />
					</div>
				</div>
            </div>
            <div class="col-4">
				<label class="control-label">{{ __('Hardware 5') }}</label>
				<div class="row">
					<div class="col-9">
					  <select type="text" class="selectpicker" data-live-search="true" name="hardware5">
							<option value="0">Select</option>
							@if(isset($hardwares)) @foreach($hardwares as $key => $hardware)
							<option value="{{$hardware->id}}">
							{{$hardware->name}}
							</option>
							@endforeach @endif        
					  </select>
					</div>
					<div class="col-3">
						<input type="number" min="0" class="form-control" name="hardware5_quantity" id="hardware5_quantity" step="any" placeholder="Q" value="{{ old('hardware5_quantity') }}" />
					</div>
				</div>
            </div>
            <div class="col-4">
              <label class="control-label">{{ __('Upholstry') }}</label>
              <textarea type="text-area" class="form-control" name="upholstry">{{ old('upholstry') }}</textarea>
            </div>
          </div>
          <!-- ninth row -->
          <div class="row mt-3">
            <div class="col-4">
              <label class="control-label">{{ __('Corner') }}</label>
              <textarea type="text-area" class="form-control" name="corner">{{ old('corner') }}</textarea>
            </div>
            <div class="col-4">
              <label class="control-label">{{ __('L') }}</label>
              <textarea type="text-area" class="form-control" name="lhardware">{{ old('lhardware') }}</textarea>
            </div>
           </div>
            
        <div class="row col-4">
          <button type="submit" class="btn btn-primary mt-3">Add Sample</button>
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