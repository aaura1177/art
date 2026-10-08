@extends('layouts.app')

@section('content')

    <div class="mx-2">
        <div class="row mx-0 my-2">
            <h2>Update Product</h2>
        </div>

        <form method="POST" action="{{ url('/product/view/' . $product->id) }}" enctype="multipart/form-data">
            @csrf
            <!-- Form Starts -->
            <div class="form-group">

                <!-- first row -->
                <div class="row mt-3">
                    <div class="col-3">

                        @foreach ($matchedImage as $matchedImages)
                            <img class="img-thumbnail img-fluid product-img-200" src="{{ $matchedImages }}"
                                alt="Product Image" />
                        @endforeach
                    </div>
                    <div class="col-4">
                        <label class="control-label">{{ __('Image URL') }}</label>
                        <input type="file" class="form-control-file" name="imgURL" />
                    </div>
                </div>

                <!-- second row -->
                <div class="row mt-3">
                    <div class="col-4">
                        <label class="control-label">{{ __('Product Category') }}</label> <a
                            href="{{ url('/category/create') }}" style="float: right;" target="_blank"> (+New)</a>
                        <select type="text" class="selectpicker" data-live-search="true" name="category"
                            required="required">
                            @if (isset($category))
                                @foreach ($category as $key => $category)
                                    <option value="{{ $category->id }}"
                                        {{ $product->category_id == $category->id ? 'selected' : '' }}>
                                        {{ $category->name }}
                                    </option>
                                @endforeach
                            @endif
                        </select>
                    </div>
                    <div class="col-4">
                        <label class="control-label">{{ __('Product Sub-Category') }}</label> <a
                            href="{{ url('/category/subCategory/create') }}" style="float: right;" target="_blank">
                            (+New)</a>
                        <select type="text" class="selectpicker" data-live-search="true" name="subcategory"
                            required="required">
                            @if (isset($subCategory))
                                @foreach ($subCategory as $key => $subCategories)
                                    <option value="{{ $subCategories->id }}"
                                        {{ $product->subcategory_id == $subCategories->id ? 'selected' : '' }}>
                                        {{ $subCategories->name }}
                                    </option>
                                @endforeach
                            @endif
                        </select>
                    </div>
                    <div class="col-4">
                        <label class="control-label">{{ __('Product Quantity') }}</label>
                        <input type="number" class="form-control" name="quantity" value="{{ $product->quantity }}" />
                    </div>
                    <div class="col-4">
                        <label class="control-label">{{ __('Product Ledger') }}</label> <a
                            href="{{ url('/product_ledger/create') }}" style="float: right;" target="_blank"> (+New)</a>
                        <select type="text" class="selectpicker" data-live-search="true" name="ProductLedger"
                            required="required">
                            <option value="" {{ empty($product->product_ledger_id) ? 'selected' : '' }}>Select a
                                Product Ledger</option>
                            @if (isset($ProductLedger))
                                @foreach ($ProductLedger as $key => $ProductLedger)
                                    <option value="{{ $ProductLedger->id }}"
                                        {{ $product->product_ledger_id == $ProductLedger->id ? 'selected' : '' }}>
                                        {{ $ProductLedger->name }}
                                    </option>
                                @endforeach
                            @endif
                        </select>
                    </div>
                </div>

                <!-- third row -->
                <div class="row mt-3">
                    <div class="col-4">
                        <label class="control-label">{{ __('Product Code') }}</label>
                        <input type="text" class="form-control toUpperCase" name="code" required="required"
                            value="{{ $product->code }}" disabled />
                    </div>
                    <div class="col-4">
                        <label class="control-label">{{ __('EAN Code') }}</label>
                        <input type="text" class="form-control toUpperCase" name="ean" required="required"
                            value="{{ $product->EAN }}" />
                    </div>
                    <div class="col-4">
                        <label class="control-label">{{ __('HSN Code') }}</label>
                        <input type="text" class="form-control toUpperCase" name="hsn" required="required"
                            value="{{ $product->HSN }}" />
                    </div>
                </div>

                <!-- fourth row -->
                <div class="row mt-3">
                    <div class="col-4">
                        <label class="control-label">{{ __('Product Name') }}</label>
                        <input type="text" class="form-control" name="name" required="required"
                            value="{{ $product->name }}" />
                    </div>
                    <div class="col-4">
                        <label class="control-label">{{ __('Finishing') }}</label>
                        <div class="row">
                            <div class="col-7">
                                <select id="finishDropdown" class="selectpicker" data-live-search="true"
                                    onchange="calFinish(this,1)">
                                    <option value="">Select Finish {{ old('finishing') }}</option>
                                    @foreach ($finishRates as $finishRate)
                                        <option value="{{ $finishRate->rate }}"
                                            {{ $product->finishing == $finishRate->name ? 'selected' : '' }}>
                                            {{ $finishRate->name }}</option>
                                    @endforeach
                                </select>
                                <input type="hidden" id="finishRate" />
                                <input type="hidden" id="finishing" name="finishing" />
                            </div>
                            <div class="col-5">
                                <!-- <input id="finishing_price" step="any" placeholder="Finishing Price" type="number" class="form-control" name="finishing_price" value="{{ $product->finishing_price }}" /> -->
                                <input id="finishing_price" step="any" placeholder="Finishing Price" type="number"
                                    class="form-control" name="finishing_price" value="0" />
                            </div>
                        </div>
                    </div>

<div class="col-4">
    <label class="control-label">Finshing Extra Price (₹)</label>

    <select class="selectpicker" data-live-search="true"
            name="finshing_extra_price_id" id="finshing_extra_price_id" required onchange="addExtraPriceToFinishing()">
        <option disabled selected>Select Finshing Extra Price</option>

        @foreach($FinshingExtraPrice as $item)
            <option value="{{ $item->id }}" data-price="{{ $item->price }}"
                {{ isset($product) && $product->finshing_extra_price_id == $item->id ? 'selected' : '' }}>
                ₹ {{ number_format($item->price, 2) }}
            </option>
        @endforeach
    </select>
</div>

                    <div class="col-4">
                        <label class="control-label">{{ __('GST Slab (%)') }}</label>
                        <select type="text" class="selectpicker" data-live-search="true" name="gstslab"
                            required="required">
                            <option value="0" {{ $product->gstslab == '0' ? 'selected' : '' }}>0</option>
                            <option value="5" {{ $product->gstslab == '5' ? 'selected' : '' }}>5</option>
                            <option value="12" {{ $product->gstslab == '12' ? 'selected' : '' }}>12</option>
                            <option value="18" {{ $product->gstslab == '18' ? 'selected' : '' }}>18</option>
                            <option value="28" {{ $product->gstslab == '28' ? 'selected' : '' }}>28</option>
                        </select>
                    </div>
                </div>


                <div class="row mt-3">
        <div class="col-4">
          <label class="control-label">{{ __('Gross Weight') }}</label>
          <input type="number" class="form-control " name="gross_weight" step="any" required="required" value="{{$product->gross_weight}}" />

        </div>
        <div class="col-4">
          <label class="control-label">{{ __('Net Weight') }}</label>
          <input type="number" class="form-control " name="net_weight" step="any" required="required" value="{{$product->net_weight}}" />

        </div>

      </div>

                <!-- fifth row -->
                <div class="row mt-3">
                    <div class="col-4">
                        <label class="control-label">{{ __('Product Height (cm)') }}</label>
                        <input type="number" min="0" class="form-control" name="height" id="height"
                            step="any" required="required" placeholder=" in cm" value="{{ $product->height }}"
                            onchange="calFinish(this,0)" />
                    </div>
                    <div class="col-4">
                        <label class="control-label">{{ __('Product Width (cm)') }}</label>
                        <input type="number" min="0" class="form-control" name="width" id="width"
                            step="any" required="required" placeholder=" in cm" value="{{ $product->width }}"
                            onchange="calFinish(this,0)" />
                    </div>
                    <div class="col-4">
                        <label class="control-label">{{ __('Product Depth (cm)') }}</label>
                        <input type="number" min="0" class="form-control" name="depth" id="depth"
                            step="any" required="required" placeholder=" in cm" value="{{ $product->depth }}"
                            onchange="calFinish(this,0)" />
                    </div>
                </div>

                <!-- fourth row -->
                <div class="row mt-3">
                    <div class="col-4">
                        <label class="control-label">{{ __('Box Height (cm)') }}</label>
                        <input type="number" min="0" class="form-control" name="boxheight" id="boxheight"
                            step="any" placeholder=" in cm" value="{{ $product->boxheight }}"
                            required="required" />
                    </div>
                    <div class="col-4">
                        <label class="control-label">{{ __('Box Width (cm)') }}</label>
                        <input type="number" min="0" class="form-control" name="boxwidth" id="boxwidth"
                            step="any" placeholder=" in cm" value="{{ $product->boxwidth }}" required="required" />
                    </div>
                    <div class="col-4">
                        <label class="control-label">{{ __('Box Depth (cm)') }}</label>
                        <input type="number" min="0" class="form-control" name="boxdepth" id="boxdepth"
                            step="any" placeholder=" in cm" value="{{ $product->boxdepth }}" required="required" />
                    </div>
                </div>

                <!-- sixth row -->
                <div class="row mt-3">
                    <div class="col-4">
                        <label class="control-label">{{ __('WholeSale Volume') }} <span>(m<sup>3</sup>)</span></label>
                        <input type="number" min="0" class="form-control" name="wholesalevolume"
                            id="wholesalevolume" step="any" placeholder="m3"
                            value="{{ $product->wholesalevolume }}" readonly />
                    </div>
                    <div class="col-4">
                        <label class="control-label">{{ __('DropShip Volume') }} <span>(m<sup>3</sup>)</span></label>
                        <input type="number" min="0" class="form-control" name="dropshipvolume"
                            id="dropshipvolume" step="any" placeholder="m3" value="{{ $product->dropshipvolume }}"
                            readonly />
                    </div>
                    <div class="col-4 d-none">
                        <label class="control-label">{{ __('Volume') }} <span>(m<sup>3</sup>)</span></label>
                        <input type="number" min="0" class="form-control" name="volume" id="volume"
                            step="any" required="required" placeholder="m3" value="{{ $product->volume }}"
                            readonly />
                    </div>
                </div>

                <!-- seventh row -->
                <div class="row mt-3">
                    <div class="col-4">
                        <label class="control-label">{{ __('Add-ons') }}</label>
                        <textarea type="text" class="form-control" name="addons">{{ $product->addons }}</textarea>
                    </div>
                    <div class="col-4">
                        <label class="control-label">{{ __('Remarks') }}</label>
                        <textarea type="text" class="form-control" name="remarks">{{ $product->remarks }}</textarea>
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
                                    @if (isset($hardwares))
                                        @foreach ($hardwares as $key => $hardware)
                                            <option value="{{ $hardware->id }}"
                                                {{ $product->hardware1 == $hardware->id ? 'selected' : '' }}>
                                                {{ $hardware->name }}
                                            </option>
                                        @endforeach
                                    @endif
                                </select>
                            </div>
                            <div class="col-3">
                                <input type="number" min="0" class="form-control" name="hardware1_quantity"
                                    id="hardware1_quantity" step="any" placeholder="Q"
                                    value="{{ $product->hardware1_quantity }}" />
                            </div>
                        </div>
                    </div>
                    <div class="col-4">
                        <label class="control-label">{{ __('Hardware 2') }}</label>
                        <div class="row">
                            <div class="col-9">
                                <select type="text" class="selectpicker" data-live-search="true" name="hardware2">
                                    <option value="0">Select</option>
                                    @if (isset($hardwares))
                                        @foreach ($hardwares as $key => $hardware)
                                            <option value="{{ $hardware->id }}"
                                                {{ $product->hardware2 == $hardware->id ? 'selected' : '' }}>
                                                {{ $hardware->name }}
                                            </option>
                                        @endforeach
                                    @endif
                                </select>
                            </div>
                            <div class="col-3">
                                <input type="number" min="0" class="form-control" name="hardware2_quantity"
                                    id="hardware2_quantity" step="any" placeholder="Q"
                                    value="{{ $product->hardware2_quantity }}" />
                            </div>
                        </div>
                    </div>
                    <div class="col-4">
                        <label class="control-label">{{ __('Hardware 3') }}</label>
                        <div class="row">
                            <div class="col-9">
                                <select type="text" class="selectpicker" data-live-search="true" name="hardware3">
                                    <option value="0">Select</option>
                                    @if (isset($hardwares))
                                        @foreach ($hardwares as $key => $hardware)
                                            <option value="{{ $hardware->id }}"
                                                {{ $product->hardware3 == $hardware->id ? 'selected' : '' }}>
                                                {{ $hardware->name }}
                                            </option>
                                        @endforeach
                                    @endif
                                </select>
                            </div>
                            <div class="col-3">
                                <input type="number" min="0" class="form-control" name="hardware3_quantity"
                                    id="hardware3_quantity" step="any" placeholder="Q"
                                    value="{{ $product->hardware3_quantity }}" />
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
                                    @if (isset($hardwares))
                                        @foreach ($hardwares as $key => $hardware)
                                            <option value="{{ $hardware->id }}"
                                                {{ $product->hardware4 == $hardware->id ? 'selected' : '' }}>
                                                {{ $hardware->name }}
                                            </option>
                                        @endforeach
                                    @endif
                                </select>
                            </div>
                            <div class="col-3">
                                <input type="number" min="0" class="form-control" name="hardware4_quantity"
                                    id="hardware4_quantity" step="any" placeholder="Q"
                                    value="{{ $product->hardware4_quantity }}" />
                            </div>
                        </div>
                    </div>
                    <div class="col-4">
                        <label class="control-label">{{ __('Hardware 5') }}</label>
                        <div class="row">
                            <div class="col-9">
                                <select type="text" class="selectpicker" data-live-search="true" name="hardware5">
                                    <option value="0">Select</option>
                                    @if (isset($hardwares))
                                        @foreach ($hardwares as $key => $hardware)
                                            <option value="{{ $hardware->id }}"
                                                {{ $product->hardware5 == $hardware->id ? 'selected' : '' }}>
                                                {{ $hardware->name }}
                                            </option>
                                        @endforeach
                                    @endif
                                </select>
                            </div>
                            <div class="col-3">
                                <input type="number" min="0" class="form-control" name="hardware5_quantity"
                                    id="hardware5_quantity" step="any" placeholder="Q"
                                    value="{{ $product->hardware5_quantity }}" />
                            </div>
                        </div>
                    </div>
                    <div class="col-4">
                        <label class="control-label">{{ __('Upholstry') }}</label>
                        <textarea type="text-area" class="form-control" name="upholstry">{{ $product->upholstry }}</textarea>
                    </div>
                </div>
                <!-- ninth row -->
                <div class="row mt-3">
                    <div class="col-4">
                        <label class="control-label">{{ __('Corner') }}</label>
                        <textarea type="text-area" class="form-control" name="corner">{{ $product->corner }}</textarea>
                    </div>
                    <div class="col-4">
                        <label class="control-label">{{ __('L') }}</label>
                        <textarea type="text-area" class="form-control" name="lhardware">{{ $product->lhardware }}</textarea>
                    </div>
                </div>
                <!-- tenth row -->
                @for ($i = 1; $i < 11; $i++)
                    <div class="row mt-3">
                        <div class="col-3">
                            <label class="control-label">{{ __('Small Hardware ' . $i) }}</label>
                            <select type="text" class="selectpicker" data-live-search="true"
                                name="small_hardware[{{ $i }}][id]">
                                <option value="0">Select</option>
                                @if (isset($smallhardwares))
                                    @foreach ($smallhardwares as $key => $smallhardware)
                                        <option value="{{ $smallhardware->id }}" <?php if (isset($smallhardwareproducts[$i - 1])) {
                                            if ($smallhardwareproducts[$i - 1]->small_hardware_id == $smallhardware->id) {
                                                echo 'selected';
                                            }
                                        } ?>>
                                            {{ $smallhardware->name }}
                                        </option>
                                    @endforeach
                                @endif
                            </select>
                        </div>
                        <div class="col-3">
                            <label class="control-label">{{ __('Quantity') }}</label>
                            <input type="number" min="0" class="form-control"
                                name="small_hardware[{{ $i }}][quantity]"
                                id="smallhardware{{ $i }}_quantity" step="any" placeholder="Q"
                                value="<?php if (isset($smallhardwareproducts[$i - 1])) {
                                    echo $smallhardwareproducts[$i - 1]->quantity;
                                } ?>" />
                        </div>
                        <div class="col-3">
                            <label class="control-label">{{ __('Size') }}</label>
                            <input type="text" class="form-control" name="small_hardware[{{ $i }}][size]"
                                id="smallhardware{{ $i }}_quantity" placeholder="S"
                                value="<?php if (isset($smallhardwareproducts[$i - 1])) {
                                    echo $smallhardwareproducts[$i - 1]->size;
                                } ?>" />
                        </div>
                        <div class="col-3">
                            <label class="control-label">{{ __('Price per piece') }}</label>
                            <input type="text" class="form-control"
                                name="small_hardware[{{ $i }}][price]"
                                id="smallhardware{{ $i }}_price" placeholder="$"
                                value="<?php if (isset($smallhardwareproducts[$i - 1])) {
                                    echo $smallhardwareproducts[$i - 1]->price;
                                } ?>" />
                        </div>
                    </div>
                @endfor

                <div class="row mt-3">
                      <div class="col-4">
                            <label class="control-label">{{ __('Carton Product') }}</label>
                            <input type="text"  class="form-control"
                                name="cartonname"
                                id="" 
                                value="{{ $product->name }}" readonly />
                        </div>
                        <div class="col-4">
                            <label class="control-label">{{ __('Box 1') }}</label>
                            <input type="number" min="0" class="form-control" name="quantity1"
                                id="" placeholder=""
                                value="{{$ProductCarton->quantity1 ?? 0}}" />
                        </div>
                        <div class="col-4">
                            <label class="control-label">{{ __('Box 2') }}</label>
                            <input type="number" min="0" class="form-control"
                                name="quantity2"
                                id=""
                                value="{{$ProductCarton->quantity2 ?? 0}}" />
                        </div>
                </div>


                @if ($WfConsumable->count() > 0)
                    <div id="consumableLabels" class="row mt-3">
                    @else
                        <div id="consumableLabels" class="row mt-3" style="display: none;">
                @endif
                <div class="col-4">
                    <label class="control-label">{{ __('Consumable product') }}</label>
                </div>
                <div class="col-4">
                    <label class="control-label">{{ __('Unit Type') }}</label>
                </div>
                <div class="col-4">
                    <label class="control-label">{{ __('Quantity') }}</label>
                </div>
            </div>

            <div id="unitTypeContainer" class="col-12">
                @php $consumableIndex = 0; @endphp
                @foreach ($WfConsumable as $wf)
                    <div class="row unitTypeRow mb-2 consumable-product-row" data-index="{{ $consumableIndex }}">
                        <div class="col-4">
                            <select class="form-control selectpicker"
                                name="consumables[{{ $consumableIndex }}][consumable_id]" data-live-search="true"
                                required onchange="handleUnit(this)">
                                <option value="" disabled>Select Consumable</option>
                                @foreach ($consumable as $item)
                                    <option value="{{ $item->id }}"
                                        {{ $item->id == $wf->consumables_id ? 'selected' : '' }}
                                        data-id="{{ $item->unit_type_id }}">
                                        {{ $item->sku_option_label }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-3">
                            <input type="hidden" class="unit-id-hidden"
                                name="consumables[{{ $consumableIndex }}][unit_type_id]"
                                value="{{ $wf->unit_type_id }}" />
                            <input type="hidden" class="unit-name-hidden"
                                name="consumables[{{ $consumableIndex }}][unit_type_name]"
                                value="{{ $wf->unitType->name ?? '' }}" />
                            <input type="text" class="form-control unit" value="{{ $wf->unitType->name ?? '' }}"
                                placeholder="Unit Type" disabled />
                        </div>
                        <div class="col-3">
                            <input type="number" name="consumables[{{ $consumableIndex }}][quantity]"
                                class="form-control" value="{{ $wf->qty }}" min="0" step=".1"
                                required />
                        </div>
                        <div class="col-2">
                            <button type="button" class="btn btn-danger removeRow">X</button>
                        </div>
                    </div>
                    @php $consumableIndex++; @endphp
                @endforeach
            </div>

            <div class="col-12 mt-2">
                <button type="button" id="addUnitTypeRow" class="btn btn-primary">+ Add Consumable</button>
            </div>



         


    </div>

    <div class="row col-4">
        <button type="submit" class="btn btn-primary mt-3">Update Product</button>
    </div>

    </div>
    </form>
    </div>

    <!-- Script Start -->
    <script>
        var baseFinishingPrice = 0;
        var lastExtraPrice = 0;

        $(document).ready(function() {
            // Check if an option is preselected in the specific dropdown
            var selectedOption = $('#finishDropdown option:selected').val();

            if (selectedOption) {
                // If there is a preselected option, trigger the calFinish function for the specific dropdown
                calFinish($('#finishDropdown')[0], 1);
            }

            // If extra price is preselected, make sure it's added
            var extraPriceSelected = $('#finshing_extra_price_id option:selected');
            if (extraPriceSelected.length > 0 && extraPriceSelected.data('price') && baseFinishingPrice > 0) {
                addExtraPriceToFinishing();
            }
        });
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
            // If base price not set yet, calculate it from current price minus last extra
            if (baseFinishingPrice === 0) {
                var currentPrice = parseFloat($('#finishing_price').val()) || 0;
                baseFinishingPrice = currentPrice - lastExtraPrice;
            }

            var selectedOption = $('#finshing_extra_price_id option:selected');
            var extraPrice = 0;
            if (selectedOption.length > 0 && selectedOption.data('price')) {
                extraPrice = parseFloat(selectedOption.data('price'));
            }

            // Set price as: base + only the selected extra price
            $('#finishing_price').val(baseFinishingPrice + extraPrice);
            lastExtraPrice = extraPrice;
        }

        $(document).ready(function() {
            let index = {{ $WfConsumable->count() }};
            let labelsShown = {{ $WfConsumable->count() > 0 ? 'true' : 'false' }};

            $('#addUnitTypeRow').click(function() {
                if (!labelsShown) {
                    $('#consumableLabels').show();
                    labelsShown = true;
                }

                let consumableOptions = `
            <option value="" disabled selected>Select Consumable</option>
            @foreach ($consumable as $item)
                <option value="{{ $item->id }}" data-id="{{ $item->unit_type_id }}">{{ $item->sku_option_label }}</option>
            @endforeach
        `;

                const newRow = `
            <div class="row unitTypeRow mb-2 consumable-product-row" data-index="${index}">
                <div class="col-4">
                    <select class="form-control selectpicker" data-live-search="true" onchange="handleUnit(this)" name="consumables[${index}][consumable_id]" required>
                        ${consumableOptions}
                    </select>
                </div>
                <div class="col-3">
                    <input type="hidden" class="unit-id-hidden" name="consumables[${index}][unit_type_id]" />
                    <input type="hidden" class="unit-name-hidden" name="consumables[${index}][unit_type_name]" />
                    <input type="text" class="form-control unit" placeholder="Unit Type" disabled />
                </div>
                <div class="col-3">
                    <input type="number" name="consumables[${index}][quantity]" class="form-control" placeholder="Quantity" min="0" required />
                </div>
                <div class="col-2">
                    <button type="button" class="btn btn-danger removeRow">X</button>
                </div>
            </div>
        `;

                $('#unitTypeContainer').append(newRow);
                $('.selectpicker').selectpicker('refresh');
                index++;
            });

            $(document).on('click', '.removeRow', function() {
                $(this).closest('.unitTypeRow').remove();
            });

            // Call handleUnit for existing rows
            $('.consumable-product-row select').each(function() {
                handleUnit(this);
            });
        });

        const handleUnit = async (ref) => {
            const selected = ref.options[ref.selectedIndex];
            const unitTypeId = selected.dataset.id;

            console.log(unitTypeId);



            if (!unitTypeId) return;

            const unit = await getUnit(unitTypeId);

            const row = ref.closest('.consumable-product-row');


            if (unit) {
                const quantityInput = row.querySelector('input[name^="consumables"][name$="[quantity]"]');

                quantityInput.step = unit.data_type === 'int' ? '1' : '0.1';
                quantityInput.oninput = null;
                if (unit.data_type === 'int') {
                    quantityInput.step = '1';
                    quantityInput.oninput = function() {
                        this.value = this.value.replace(/[^0-9]/g, '');
                    };
                } else {
                    quantityInput.step = '0.1';
                    quantityInput.oninput = null;
                }
                row.querySelector('.unit').value = unit.name;
                row.querySelector('.unit-id-hidden').value = unit.id;
                row.querySelector('.unit-name-hidden').value = unit.name;
            } else {
                row.querySelector('.unit').value = '';
                row.querySelector('.unit-id-hidden').value = '';
                row.querySelector('.unit-name-hidden').value = '';
            }
        };

        const getUnit = async (unitTypeId) => {
            const url = '/purchaseOrder/cunitType';

            try {
                let response = await fetch(url);
                response = await response.json();



                return response.unittype.find((item) => item.id == unitTypeId);
            } catch (error) {
                console.error('Error fetching unit type:', error);
                return null;
            }
        };






$(document).on('change', '.consumable-product-row select', function () {
    const selectedValue = $(this).val();
    const allSelects = $('.consumable-product-row select').not(this);

    let duplicateFound = false;
    allSelects.each(function () {
        if ($(this).val() === selectedValue) {
            duplicateFound = true;
            return false;
        }
    });

    if (duplicateFound) {
        alert('This consumable has already been selected. Please choose a different one.');
        $(this).val('').selectpicker('refresh');
        return;
    }

    handleUnit(this);
});









    </script>
    <!-- End -->

@endsection
