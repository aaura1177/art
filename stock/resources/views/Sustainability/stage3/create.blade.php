@extends('layouts.app')

@section('content')

    <style>
        .form-label {
            font-weight: bold;
        }
    </style>
    <div class="row mx-1 my-2">
        <h2>Sustainability Stage-3</h2>
        <div class="col">
            <!-- <a class="btn btn-primary float-end" style="color: #fff;" href="{{ route('sustainability.stage2.fuel-consumption')}}"> Go To Fuel Consumption </a> -->
            <a class="btn btn-success float-end" style="color: #fff;" href="{{ route('sustainability.stage3.power-consumption')}}"><i class="fa fa-arrow-left"></i> Go Back </a>
        </div>
    </div>

    <div class="card mb-3">
        <div class="card-body">
        <form action="{{ route('sustainability.stage3.store') }}" method="POST" data-parsley-validate>
            @csrf
            <div class="mb-3">
                <label for="exampleInputEmail1" class="form-label">Office Type <span class="text-danger">*</span></label>
                <select name="office_type" class="form-control" required data-parsley-required-message="This field is required.">
                    <option value="">Select Office</option>
                    <option value="Factory"> Factory </option>
                    <option value="Office"> Office </option>
                    <option value="Uk Office"> Uk Office </option>
                </select>
                @error('office_type')
                    <span class="alert text-danger">{{ $message }}</span>
                @enderror
            </div>

            <div class="mb-3">
                <label for="exampleInputEmail1" class="form-label">Type <span class="text-danger">*</span></label>
                <select name="type" class="form-control" required data-parsley-required-message="This field is required.">
                    <option value="Electricity" selected>Electricity</option>
                    <!-- <option value="Transport">Transport</option> -->
                </select>
                @error('type')
                    <span class="alert text-danger">{{ $message }}</span>
                @enderror
            </div>
           
            <div class="mb-3">
                <label for="exampleInputPassword1" class="form-label">Power Consumed <span class="text-danger">*</span></label>
                <input type="text" class="form-control" name="power_consumed" id="power_consumed" value="{{ old('power_consumed') }}" onkeyup="allowDecimalOnly(this)" onfocus="allowDecimalOnly(this)"
                required data-parsley-type="number" data-parsley-required-message="This field is required."
                data-parsley-type-message="Please enter a valid number." onchange="calculateCarbonEmission2(`{{ (@$sustainabilityVariable->carbon_emission_factor_per_kwh) ?? '0' }}`)">
                @error('power_consumed')
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

function calculateCarbonEmission2(carbon_emission_factor_per_kwh) {
    var power_consumed = parseFloat(document.getElementById('power_consumed').value) || 0;
    var carbonEmission = power_consumed * carbon_emission_factor_per_kwh; 
    document.getElementById('carbon_emission').value = carbonEmission.toFixed(2);
}

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