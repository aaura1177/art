@extends('layouts.app')

@section('content')

    <style>
        .form-label {
            font-weight: bold;
        }
        .select2-container .select2-selection--single {
            height:38px;
        }
        .select2-container--default .select2-selection--single .select2-selection__rendered
        {
            line-height: 38px;
        }
        .select2-container--default .select2-selection--single .select2-selection__arrow
        {
            height: 38px;
        }
    </style>
    <div class="row mx-1 my-2">
        <h2>Sustainability Stage-1</h2>
        <div class="col"><a class="btn btn-success float-end" style="color: #fff;" href="{{ route('sustainability.stage1.index')}}"><i class="fa fa-arrow-left"></i> Go Back</a></div>
    </div>

    <div class="card mb-3">
        <div class="card-body">
        <form action="{{ route('sustainability.stage1.store') }}" method="POST" data-parsley-validate enctype="multipart/form-data">
            @csrf
            <div class="mb-3">
                <label for="exampleInputEmail1" class="form-label">Supplier Name <span class="text-danger">*</span></label>
                <select name="supplier_id" class="form-control select2" required data-parsley-required-message="This field is required.">
                    <option value="">Select Supplier</option>
                    @foreach($suppliers as $supplier)
                        <option value="{{ $supplier->id }}">{{ $supplier->c_name }}</option>
                    @endforeach
                </select>
                @error('supplier_id')
                    <span class="alert text-danger">{{ $message }}</span>
                @enderror
            </div>
            <div class="mb-3">
                <label for="exampleInputEmail1" class="form-label">Vehicle Type <span class="text-danger">*</span></label>
                <select name="vehicle_type" class="form-control" required data-parsley-required-message="This field is required.">
                    <option value="Car" selected>Car</option>
                    <option value="Bike">Bike</option>
                    <option value="Scooty">Scooty</option>
                    <option value="Truck">Truck</option>
                    <option value="Trolley">Trolley</option>
                    <option value="Bus">Bus</option>
                    <option value="Auto">Auto</option>
                </select>
                @error('vehicle_type')
                    <span class="alert text-danger">{{ $message }}</span>
                @enderror
            </div>

            <input type="hidden" id="carbon_emission_rate_selected" value="{{ (@$sustainabilityVariable->carbon_emission_rate_for_petrol) ?? 0 }}">
            
            <div class="mb-3">
                <label for="exampleInputEmail1" class="form-label">Fuel Type <span class="text-danger">*</span></label>
                <select name="fuel_type" class="form-control" required data-parsley-required-message="This field is required.">
                    <option value="Petrol" selected data-cv="{{ (@$sustainabilityVariable->carbon_emission_rate_for_petrol) ?? 0 }}">Petrol</option>
                    <option value="Diesel" data-cv="{{ (@$sustainabilityVariable->carbon_emission_rate_for_diesel) ?? 0 }}">Diesel</option>
                    <option value="CNG" data-cv="{{ (@$sustainabilityVariable->carbon_emission_rate_for_cng) ?? 0 }}">CNG</option>
                    <option value="Electricity" data-cv="{{ (@$sustainabilityVariable->carbon_emission_rate_for_electronic) ?? 0 }}">Electricity</option>
                    <option value="Hybrid" data-cv="{{ (@$sustainabilityVariable->carbon_emission_rate_for_hybrid) ?? 0 }}">Hybrid</option>
                </select>
                @error('fuel_type')
                    <span class="alert text-danger">{{ $message }}</span>
                @enderror
            </div>

            <div class="mb-3">
                <label for="exampleInputPassword1" class="form-label">Distance Travelled <span class="text-danger">*</span></label>
                <input type="text" class="form-control" name="distance_travelled" id="distance_travelled" value="{{ old('distance_travelled') }}" onkeyup="allowDecimalOnly(this)" onfocus="allowDecimalOnly(this)"
                required data-parsley-type="number" data-parsley-required-message="This field is required."
                data-parsley-type-message="Please enter a valid number." onchange="calculateCarbonEmission()">
                @error('distance_travelled')
                    <span class="alert text-danger">{{ $message }}</span>
                @enderror
            </div>
            <div class="mb-3">
                <label for="exampleInputPassword1" class="form-label">No. Of Rounds <span class="text-danger">*</span></label>
                <input type="number" min="1" class="form-control" name="number_of_rounds" value="{{ old('number_of_rounds') }}" id="no_of_rounds" onkeyup="allowDecimalOnly(this)" onfocus="allowDecimalOnly(this)" required data-parsley-type="number" data-parsley-required-message="This field is required."
                data-parsley-type-message="Please enter a valid number." onchange="calculateCarbonEmission()">
                @error('number_of_rounds')
                    <span class="alert text-danger">{{ $message }}</span>
                @enderror
            </div>
            <div class="mb-3">
                <label for="exampleInputPassword1" class="form-label">Carbon Emission <span class="text-danger">*</span></label>
                <input type="text" class="form-control" name="carbon_emission" id="carbon_emission" value="{{ old('carbon_emission') }}" readonly required data-parsley-type="number" data-parsley-required-message="This field is required." data-parsley-type-message="Please enter a valid number.">
                @error('carbon_emission')
                    <span class="alert text-danger">{{ $message }}</span>
                @enderror
            </div>
            <div class="mb-3">
                <label for="monthPicker" class="form-label">Month/Year <span class="text-danger">*</span></label>
                <input type="text" id="monthPicker" class="form-control" name="month_year" required data-parsley-required-message="This field is required." value="{{ old('month_year') }}">
                @error('month_year')
                    <span class="alert text-danger">{{ $message }}</span>
                @enderror
            </div>

            <div class="mb-3">
                <label for="chalan_url" class="form-label">Upload Challan</label>
                <input type="file" class="form-control" name="chalan_url" id="chalan_url">
                @error('chalan_url')
                    <span class="alert text-danger">{{ $message }}</span>
                @enderror
            </div>

            <button type="submit" class="btn btn-primary">Submit</button>
        </form>
        
        </div>
    </div>
@endsection
@section('footer')

<script>
function allowDecimalOnly(input) {
    input.value = input.value.replace(/[^0-9.]/g, '').replace(/(\..*?)\..*/g, '$1'); // Only allow one decimal
}

$(document).ready(function() {
    $('.select2').select2({
        placeholder: "Select Supplier",
        allowClear: true
    });
});

function calculateCarbonEmission() {
    var carbon_emission_rate = parseFloat(document.getElementById('carbon_emission_rate_selected').value) || 0;
    var distance = parseFloat(document.getElementById('distance_travelled').value) || 0;
    var rounds = parseInt(document.getElementById('no_of_rounds').value) || 1;
    var carbonEmission = distance * rounds * carbon_emission_rate; 
    document.getElementById('carbon_emission').value = carbonEmission.toFixed(2);
}

$(document).ready(function () {
    $('select[name="fuel_type"]').on('change', function () {
        let cvValue = $(this).find(':selected').data('cv');
        $("#carbon_emission_rate_selected").val(cvValue);
        calculateCarbonEmission();
    });
});

flatpickr("#monthPicker", {
    plugins: [
        new monthSelectPlugin({
            shorthand: true, // show shorthand months (e.g. Jan, Feb)
            dateFormat: "Y-m", // output format
            altFormat: "F Y",  // visible format
            theme: "light"
        })
    ]
});

</script>

@endsection