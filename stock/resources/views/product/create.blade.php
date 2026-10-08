@extends('layouts.app')

@section('content')

  <div class="mx-2">
    <div class="row mx-0 my-2">
        <h2>Add Product</h2>
    </div>

    <form method="POST" action="{{ url('/product/create') }}" enctype="multipart/form-data">
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
            <div class="col-4">
              <label class="control-label">{{ __('Product Ledger') }}</label><a href="{{ url('/product_ledger/create')}}" style="float: right;"  target="_blank"> (+New)</a>

              <select type="text" class="selectpicker" data-live-search="true" name="ProductLedgers" required="required">
                   <option value=""selected disabled>Select Product Ledger </option>
                    @if(isset($ProductLedger)) @foreach($ProductLedger as $key => $ProductLedgers)
                    <option value="{{$ProductLedgers->id}}">
                    {{$ProductLedgers->name}}
                    </option>
                    @endforeach @endif        
             
              </select>
            </div>
             <div class="col-4">
              <label class="control-label">{{ __('Unit') }}</label></a>

              <select type="text" class="selectpicker" data-live-search="true" name="unit" required="required">
                  <option value="" selected disabled> Select Unit </option>
                  <option value="No.">No.</option>       
                  <option value="Kg.">Kg.</option>       
                  <option value="Lt.">Lt.</option>       
                  <option value="M3.">M3.</option>       
              </select>
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
              <input type="text" class="form-control toUpperCase" name="ean" step="any" required="required" value="{{ old('ean') }}" />
              @if(isset($errors))
              <p class="mt-2 mb-0" style="color:red">{{$errors->first('ean')}}</p>
              @endif
            </div>
            <div class="col-4">
              <label class="control-label">{{ __('HSN Code') }}</label>
              <input type="text" class="form-control toUpperCase" name="hsn" step="any" required="required" value="{{ old('hsn') }}" />
            </div>
          </div>

          <!-- third row -->
          <div class="row mt-3">  
            <div class="col-4">
              <label class="control-label">{{ __('Product Name') }}</label>
              <input type="text" class="form-control" name="name" required="required" value="{{ old('name') }}" />
            </div>
            <div class="col-4">
              <label class="control-label">{{ __('Finishing') }}</label>
			  <div class="row">
				<div class="col-7">
					<select class="selectpicker"  data-live-search="true"  onchange="calFinish(this,1)">
						<option value="">Select Finish</option>
						@foreach($finishRates as $finishRate)
						<option value="{{$finishRate->rate}}" {{(old('finishing') == $finishRate->name)?'selected':''}}>{{$finishRate->name}}</option>
						@endforeach
					</select>
					<input type="hidden" id="finishRate" />
					<input type="hidden" id="finishing" name="finishing" />
				</div>
				<div class="col-5">
					<input id="finishing_price" step="any" placeholder="Finishing Price" type="number" class="form-control" name="finishing_price" value="{{ old('finishing_price') }}" />
				</div>
			  </div>
            </div>

<div class="col-4">
          <label class="control-label">{{ __('Finshing Extra Price (₹)') }}</label>
          <select class="selectpicker" data-live-search="true" name="finshing_extra_price_id" id="finshing_extra_price_id" required="required" onchange="addExtraPriceToFinishing()">
            <option selected disabled>Select Finshing Extra Price</option>

            @foreach($FinshingExtraPrice as $FinshingExtraPrice)
            <option value="{{$FinshingExtraPrice->id}}" data-price="{{$FinshingExtraPrice->price}}">{{$FinshingExtraPrice->price}}</option>
            @endforeach

          </select>
        </div>

            <div class="col-4">
              <label class="control-label">{{ __('GST Slab (%)') }}</label>
              <select class="selectpicker" data-live-search="true" name="gstslab" required="required">
                    <option value="0">0</option>
                    <option value="5">5</option>
                    <option value="12">12</option>
                    <option value="18">18</option>
                    <option value="28">28</option>
              </select>
            </div>
          </div>

          <div class="row mt-3">
        <div class="col-4">
          <label class="control-label">{{ __('Gross Weight') }}</label>
          <input type="number" class="form-control " name="gross_weight" step="any" required="required" value="{{ old('gross_weight') }}" />
          @if(isset($errors))
          <p class="mt-2 mb-0" style="color:red">{{$errors->first('gross_weight')}}</p>
          @endif
        </div>
        <div class="col-4">
          <label class="control-label">{{ __('Net Weight') }}</label>
          <input type="number" class="form-control " name="net_weight" step="any" required="required" value="{{ old('net_weight') }}" />

        </div>

      </div>


          <!-- fourth row -->
          <div class="row mt-3">
            <div class="col-4">
              <label class="control-label">{{ __('Product Height (cm)') }}</label>
              <input type="number" min="0" class="form-control" name="height" id="height" step="any" required="required" placeholder=" in cm" value="{{ old('height') }}" onchange="calFinish(this,0)" />
            </div>  
            <div class="col-4">
              <label class="control-label">{{ __('Product Width (cm)') }}</label>
              <input type="number" min="0" class="form-control" name="width" id="width" step="any" required="required" placeholder=" in cm" value="{{ old('width') }}" onchange="calFinish(this,0)" />
            </div>
            <div class="col-4">
              <label class="control-label">{{ __('Product Depth (cm)') }}</label>
              <input type="number" min="0" class="form-control" name="depth" id="depth" step="any" required="required" placeholder=" in cm" value="{{ old('depth') }}" onchange="calFinish(this,0)" />
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
          <button type="submit" class="btn btn-primary mt-3">Add Product</button>
        </div>
      
      </div>
    </form>
</div>

<!-- Script Start -->
<script>
var baseFinishingPrice = 0;
var lastExtraPrice = 0;

$(document).ready(function() {
    var selectedOption = $('#finshing_extra_price_id option:selected');
    if (selectedOption.length > 0 && selectedOption.data('price') && baseFinishingPrice > 0) {
        addExtraPriceToFinishing();
    }
});

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

function calFinish(obj,sel){
	if(sel){
		$('#finishRate').val($(obj).val());
		$('#finishing').val($(obj).find('option:selected').html());
	}
	var rate = $('#finishRate').val();
	var h = $('#height').val();
	var w = $('#width').val();
	var d = $('#depth').val();
	var x = (h * w * d)/1000000;
	var frate = Math.round((rate/60)*x);
	var rd = frate%5;
	if(rd < 3){
		frate = frate - rd;
	}else{
		var dif = 5 - rd;
		frate = frate + dif;
	}
	baseFinishingPrice = frate;

	var selectedOption = $('#finshing_extra_price_id option:selected');
	var extraPrice = 0;
	if (selectedOption.length > 0 && selectedOption.data('price')) {
		extraPrice = parseFloat(selectedOption.data('price'));
	}
	$('#finishing_price').val(baseFinishingPrice + extraPrice);
	lastExtraPrice = extraPrice;
}

function addExtraPriceToFinishing() {
	if (baseFinishingPrice === 0) {
		var currentPrice = parseFloat($('#finishing_price').val()) || 0;
		baseFinishingPrice = currentPrice - lastExtraPrice;
	}

	var selectedOption = $('#finshing_extra_price_id option:selected');
	var extraPrice = 0;
	if (selectedOption.length > 0 && selectedOption.data('price')) {
		extraPrice = parseFloat(selectedOption.data('price'));
	}

	$('#finishing_price').val(baseFinishingPrice + extraPrice);
	lastExtraPrice = extraPrice;
}
</script>
<!-- End -->

@endsection