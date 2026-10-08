@extends('layouts.app')

@section('content')

    <div class="mx-2">
        <div class="row mx-0 my-2">
            <h2>Update Product</h2>
        </div>

        <form method="POST" action="{{ url('/samples/view/' . $sample->id) }}" enctype="multipart/form-data">
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
                        <label class="control-label">{{ __('Sample Image URL') }}</label>
                        <input type="file" class="form-control-file" name="imgURL" />
                    </div>
                </div>

                <div class="row mt-3">
                    <div class="col-3">
                        <img class="img-thumbnail img-fluid product-img-200"
                            src="{{ asset('uploads/allproducts/' . $sample->prod_code . '/' . $sample->product_image_url) }}"
                            alt="Product Image" />
                    </div>
                    <div class="col-4">
                        <label class="control-label">{{ __('Product Image URL') }}</label>
                        <input type="file" class="form-control-file" name="prod_image_url" />
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
                                        {{ $sample->category_id == $category->id ? 'selected' : '' }}>
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
                                        {{ $sample->subcategory_id == $subCategories->id ? 'selected' : '' }}>
                                        {{ $subCategories->name }}
                                    </option>
                                @endforeach
                            @endif
                        </select>
                    </div>
                    <div class="col-4">
                        <label class="control-label">{{ __('Product Quantity') }}</label>
                        <input type="number" class="form-control" name="quantity" value="{{ $sample->quantity }}" />
                    </div>
                </div>

                <!-- third row -->
                <div class="row mt-3">
                    <div class="col-4">
                        <label class="control-label">{{ __('Sample Code') }}</label>
                        <input type="text" class="form-control toUpperCase" name="code" required="required"
                            value="{{ $sample->code }}" disabled />
                    </div>
                    <div class="col-4">
                        <label class="control-label">{{ __('Product Code') }}</label>
                        <input type="text" class="form-control toUpperCase" name="prod_code" id="prod_code"
                            value="{{ $sample->prod_code }}" />
                    </div>
                    <div class="col-4">
                        <label class="control-label">{{ __('EAN Code') }}</label>
                        <input type="text" class="form-control toUpperCase" id="ean" name="ean"
                            value="{{ $sample->ean }}" />
                    </div>
                    <div class="col-4">
                        <label class="control-label">{{ __('HSN Code') }}</label>
                        <input type="text" class="form-control toUpperCase" id="hsn" name="hsn"
                            value="{{ $sample->hsn }}" />
                    </div>
                </div>

                <!-- fourth row -->
                <div class="row mt-3">
                    <div class="col-4">
                        <label class="control-label">{{ __('Product Name') }}</label>
                        <input type="text" class="form-control" name="name" required="required"
                            value="{{ $sample->name }}" />
                    </div>
                    <div class="col-4">
                        <label class="control-label">{{ __('Finishing') }}</label>
                        <div class="row">
                            <div class="col-7">
                                <input placeholder="Finishing" type="text" class="form-control" name="finishing"
                                    value="{{ $sample->finishing }}" />
                            </div>
                            <div class="col-5">
                                <input placeholder="Finishing Price" type="number" class="form-control"
                                    name="finishing_price" value="{{ $sample->finishing_price }}" />
                            </div>
                        </div>
                    </div>
                    <div class="col-4">
                        <label class="control-label">{{ __('GST Slab (%)') }}</label>
                        <select type="text" class="selectpicker" data-live-search="true" name="gstslab"
                            id="gstslab">
                            <option value="0" {{ $sample->gstslab == '0' ? 'selected' : '' }}>0</option>
                            <option value="5" {{ $sample->gstslab == '5' ? 'selected' : '' }}>5</option>
                            <option value="12" {{ $sample->gstslab == '12' ? 'selected' : '' }}>12</option>
                            <option value="18" {{ $sample->gstslab == '18' ? 'selected' : '' }}>18</option>
                            <option value="28" {{ $sample->gstslab == '28' ? 'selected' : '' }}>28</option>
                        </select>
                    </div>
                </div>

                <!-- fifth row -->
                <div class="row mt-3">
                    <div class="col-4">
                        <label class="control-label">{{ __('Product Height (cm)') }}</label>
                        <input type="number" min="0" class="form-control" name="height" id="height"
                            step="any" required="required" placeholder=" in cm" value="{{ $sample->height }}" />
                    </div>
                    <div class="col-4">
                        <label class="control-label">{{ __('Product Width (cm)') }}</label>
                        <input type="number" min="0" class="form-control" name="width" id="width"
                            step="any" required="required" placeholder=" in cm" value="{{ $sample->width }}" />
                    </div>
                    <div class="col-4">
                        <label class="control-label">{{ __('Product Depth (cm)') }}</label>
                        <input type="number" min="0" class="form-control" name="depth" id="depth"
                            step="any" required="required" placeholder=" in cm" value="{{ $sample->depth }}" />
                    </div>
                </div>

                <!-- fourth row -->
                <div class="row mt-3">
                    <div class="col-4">
                        <label class="control-label">{{ __('Box Height (cm)') }}</label>
                        <input type="number" min="0" class="form-control" name="boxheight" id="boxheight"
                            step="any" placeholder=" in cm" value="{{ $sample->boxheight }}" required="required" />
                    </div>
                    <div class="col-4">
                        <label class="control-label">{{ __('Box Width (cm)') }}</label>
                        <input type="number" min="0" class="form-control" name="boxwidth" id="boxwidth"
                            step="any" placeholder=" in cm" value="{{ $sample->boxwidth }}" required="required" />
                    </div>
                    <div class="col-4">
                        <label class="control-label">{{ __('Box Depth (cm)') }}</label>
                        <input type="number" min="0" class="form-control" name="boxdepth" id="boxdepth"
                            step="any" placeholder=" in cm" value="{{ $sample->boxdepth }}" required="required" />
                    </div>
                </div>

                <!-- sixth row -->
                <div class="row mt-3">
                    <div class="col-4">
                        <label class="control-label">{{ __('WholeSale Volume') }} <span>(m<sup>3</sup>)</span></label>
                        <input type="number" min="0" class="form-control" name="wholesalevolume"
                            id="wholesalevolume" step="any" placeholder="m3" value="{{ $sample->wholesalevolume }}"
                            readonly />
                    </div>
                    <div class="col-4">
                        <label class="control-label">{{ __('DropShip Volume') }} <span>(m<sup>3</sup>)</span></label>
                        <input type="number" min="0" class="form-control" name="dropshipvolume"
                            id="dropshipvolume" step="any" placeholder="m3" value="{{ $sample->dropshipvolume }}"
                            readonly />
                    </div>
                    <div class="col-4 d-none">
                        <label class="control-label">{{ __('Volume') }} <span>(m<sup>3</sup>)</span></label>
                        <input type="number" min="0" class="form-control" name="volume" id="volume"
                            step="any" required="required" placeholder="m3" value="{{ $sample->volume }}"
                            readonly />
                    </div>
                </div>

                <!-- seventh row -->
                <div class="row mt-3">
                    <div class="col-4">
                        <label class="control-label">{{ __('Add-ons') }}</label>
                        <textarea type="text" class="form-control" name="addons">{{ $sample->addons }}</textarea>
                    </div>
                    <div class="col-4">
                        <label class="control-label">{{ __('Remarks') }}</label>
                        <textarea type="text" class="form-control" name="remarks">{{ $sample->remarks }}</textarea>
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
                                                {{ $sample->hardware1 == $hardware->id ? 'selected' : '' }}>
                                                {{ $hardware->name }}
                                            </option>
                                        @endforeach
                                    @endif
                                </select>
                            </div>
                            <div class="col-3">
                                <input type="number" min="0" class="form-control" name="hardware1_quantity"
                                    id="hardware1_quantity" step="any" placeholder="Q"
                                    value="{{ $sample->hardware1_quantity }}" />
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
                                                {{ $sample->hardware2 == $hardware->id ? 'selected' : '' }}>
                                                {{ $hardware->name }}
                                            </option>
                                        @endforeach
                                    @endif
                                </select>
                            </div>
                            <div class="col-3">
                                <input type="number" min="0" class="form-control" name="hardware2_quantity"
                                    id="hardware2_quantity" step="any" placeholder="Q"
                                    value="{{ $sample->hardware2_quantity }}" />
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
                                                {{ $sample->hardware3 == $hardware->id ? 'selected' : '' }}>
                                                {{ $hardware->name }}
                                            </option>
                                        @endforeach
                                    @endif
                                </select>
                            </div>
                            <div class="col-3">
                                <input type="number" min="0" class="form-control" name="hardware3_quantity"
                                    id="hardware3_quantity" step="any" placeholder="Q"
                                    value="{{ $sample->hardware3_quantity }}" />
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
                                                {{ $sample->hardware4 == $hardware->id ? 'selected' : '' }}>
                                                {{ $hardware->name }}
                                            </option>
                                        @endforeach
                                    @endif
                                </select>
                            </div>
                            <div class="col-3">
                                <input type="number" min="0" class="form-control" name="hardware4_quantity"
                                    id="hardware4_quantity" step="any" placeholder="Q"
                                    value="{{ $sample->hardware4_quantity }}" />
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
                                                {{ $sample->hardware5 == $hardware->id ? 'selected' : '' }}>
                                                {{ $hardware->name }}
                                            </option>
                                        @endforeach
                                    @endif
                                </select>
                            </div>
                            <div class="col-3">
                                <input type="number" min="0" class="form-control" name="hardware5_quantity"
                                    id="hardware5_quantity" step="any" placeholder="Q"
                                    value="{{ $sample->hardware5_quantity }}" />
                            </div>
                        </div>
                    </div>
                    <div class="col-4">
                        <label class="control-label">{{ __('Upholstry') }}</label>
                        <textarea type="text-area" class="form-control" name="upholstry">{{ $sample->upholstry }}</textarea>
                    </div>
                </div>
                <!-- ninth row -->
                <div class="row mt-3">
                    <div class="col-4">
                        <label class="control-label">{{ __('Corner') }}</label>
                        <textarea type="text-area" class="form-control" name="corner">{{ $sample->corner }}</textarea>
                    </div>
                    <div class="col-4">
                        <label class="control-label">{{ __('L') }}</label>
                        <textarea type="text-area" class="form-control" name="lhardware">{{ $sample->lhardware }}</textarea>
                    </div>
                </div>

                <div class="row" style="margin-top:60px;margin-bottom:50px;">
                    <div class="col-6">
                        <div class="form-check d-inline-flex align-items-center">
                            <input class="form-check-input me-2" type="checkbox" name="create_prod" id="create_prod"
                                style="width:1.25rem;height:1.25rem;" />
                            <label class="form-check-label mb-0" for="create_prod">
                                <h4 class="mb-0">{{ __('Create New Product') }}</h4>
                            </label>
                        </div>
                    </div>
                </div>

                <div class="row col-4">
                    <button type="submit" class="btn btn-primary mt-3">Update Sample</button>
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

        $('#create_prod').click(function() {
            if ($(this).is(':checked')) {
                $('#prod_code').attr('required', true);
                $('#ean').attr('required', true);
                $('#hsn').attr('required', true);
                $('#gstslab').attr('required', true);
            } else {
                $('#prod_code').removeAttr('required', true);
                $('#ean').removeAttr('required', true);
                $('#hsn').removeAttr('required', true);
                $('#gstslab').removeAttr('required', true);
            }
        });
    </script>
    <!-- End -->

@endsection
