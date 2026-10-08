@extends('layouts.app')

@section('content')

    <style>
        .form-label {
            font-weight: bold;
        }
    </style>
    <div class="row mx-1 my-2">
        <h2>Sustainability Stage-6</h2>
        <div class="col"><a class="btn btn-success float-end" style="color: #fff;" href="{{ route('sustainability.stage6.index')}}"><i class="fa fa-arrow-left"></i> Go Back </a></div>
    </div>

    <div class="card mb-3">
        <div class="card-body">
        <form action="{{ route('sustainability.stage6.update',$record->id) }}" method="POST" data-parsley-validate>
            @csrf
            <div class="mb-3">
                <label for="exampleInputEmail1" class="form-label">Location <span class="text-danger">*</span></label>
                <select name="location" id="location" class="form-control" required data-parsley-required-message="This field is required.">
                    <option value="">Select Location</option>
                    <option value="UK" @if($record->location == 'UK') selected @endif data-carbon-value="{{ @$sustainabilityVariable->carbon_emission_per_kg_parcel_uk }}">UK</option>
                    <option value="EU" @if($record->location == 'EU') selected @endif data-carbon-value="{{ @$sustainabilityVariable->carbon_emission_per_kg_parcel_eu }}">EU</option>
                    <option value="US" @if($record->location == 'US') selected @endif data-carbon-value="{{ @$sustainabilityVariable->carbon_emission_per_kg_parcel_us }}">US</option>
                    <option value="CA" @if($record->location == 'CA') selected @endif data-carbon-value="{{ @$sustainabilityVariable->carbon_emission_per_kg_parcel_ca }}">CA</option>
                    <option value="IN" @if($record->location == 'IN') selected @endif data-carbon-value="{{ @$sustainabilityVariable->carbon_emission_per_kg_parcel_in }}">IN</option>
                </select>
                @error('location')
                    <span class="alert text-danger">{{ $message }}</span>
                @enderror
            </div>

            <div class="mb-3">
                <label for="exampleInputPassword1" class="form-label">Parcel Delivered <span class="text-danger">*</span></label>
                <input type="text" class="form-control" name="parcel_delivered" id="parcel_delivered" value="{{ $record->parcel_delivered }}" onkeyup="allowDecimalOnly(this)" onfocus="allowDecimalOnly(this)"
                onchange="calculateCarbonEmission2()" required data-parsley-type="number" data-parsley-required-message="This field is required." data-parsley-type-message="Please enter a valid number.">
                @error('parcel_delivered')
                    <span class="alert text-danger">{{ $message }}</span>
                @enderror
            </div>

            <?php
                if($record->location == 'UK')
                {
                    $ce_value = $sustainabilityVariable->carbon_emission_per_kg_parcel_uk;
                }
                if($record->location == 'US')
                {
                    $ce_value = $sustainabilityVariable->carbon_emission_per_kg_parcel_us;
                }
                if($record->location == 'EU')
                {
                    $ce_value = $sustainabilityVariable->carbon_emission_per_kg_parcel_eu;
                }
                if($record->location == 'CA')
                {
                    $ce_value = $sustainabilityVariable->carbon_emission_per_kg_parcel_ca;
                }
                if($record->location == 'IN')
                {
                    $ce_value = $sustainabilityVariable->carbon_emission_per_kg_parcel_in;
                }
            ?>
            <div class="mb-3">
                <label for="exampleInputPassword1" class="form-label">Carbon Emission Per KG Parcel <span class="text-danger">*</span></label>
                <input type="text" class="form-control" id="carbon_emission_per_kg_parcel"  value="{{ @$ce_value }}" readonly>
                
            </div>

            <div class="mb-3">
                <label for="exampleInputPassword1" class="form-label">Carbon Emission <span class="text-danger">*</span></label>
                <input type="text" class="form-control" name="carbon_emission" id="carbon_emission" value="{{ $record->carbon_emission }}" readonly required data-parsley-type="number" data-parsley-required-message="This field is required." data-parsley-type-message="Please enter a valid number.">
                @error('carbon_emission')
                    <span class="alert text-danger">{{ $message }}</span>
                @enderror
            </div>
            
             <div class="mb-3">
                <label for="monthPicker" class="form-label">Month/Year <span class="text-danger">*</span></label>
                <input type="text" id="monthPicker" class="form-control" name="month_year" required data-parsley-required-message="This field is required." value="{{ date('Y-m', strtotime($record->month_year)) }}">
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

$('#location').on('change', function () {
    var carbonValue = $(this).find(':selected').data('carbon-value');
    $("#carbon_emission_per_kg_parcel").val(carbonValue);
    calculateCarbonEmission2();
});

function calculateCarbonEmission2() {
    var carbon_emission_per_kg_parcel = parseFloat($("#carbon_emission_per_kg_parcel").val()) || 0;
    var parcel_delivered = parseFloat(document.getElementById('parcel_delivered').value) || 0;
    var carbonEmission = parcel_delivered * carbon_emission_per_kg_parcel; 
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