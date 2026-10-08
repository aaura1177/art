@extends('layouts.app')

@section('content')

    <style>
        .form-label {
            font-weight: bold;
        }
    </style>
    <div class="row mx-1 my-2">
      <h2>Sustainability Variable Management</h2>
    </div>
      
    <div class="card mb-3">
        <div class="card-body">
            <div class="row">
                <div class="col-md-6">  
                    <form action="{{ route('sustainability.variable.store') }}" method="POST" data-parsley-validate>
                        @csrf
                        <div class="mb-3">
                            <label for="exampleInputEmail1" class="form-label">Carbon Content <span class="text-danger">*</span></label>
                            <input type="text" name="carbon_content" class="form-control" value="{{ @$record->carbon_content }}" onkeyup="allowDecimalOnly(this)" onfocus="allowDecimalOnly(this)"
                            required data-parsley-type="number" data-parsley-required-message="This field is required."
                            data-parsley-type-message="Please enter a valid number." >
                            @error('carbon_content')
                                <span class="alert text-danger">{{ $message }}</span>
                            @enderror
                        </div>
                        <div class="mb-3">
                            <label for="exampleInputPassword1" class="form-label">Conversion Factor <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="conversion_factor" value="{{ @$record->conversion_factor }}" onkeyup="allowDecimalOnly(this)" onfocus="allowDecimalOnly(this)"
                            required data-parsley-type="number" data-parsley-required-message="This field is required."
                            data-parsley-type-message="Please enter a valid number.">
                            @error('conversion_factor')
                                <span class="alert text-danger">{{ $message }}</span>
                            @enderror
                        </div>
                        <div class="mb-3">
                            <label for="exampleInputPassword1" class="form-label">Carbon Emission Rate <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="carbon_emission_rate" value="{{ @$record->carbon_emission_rate }}" onkeyup="allowDecimalOnly(this)" onfocus="allowDecimalOnly(this)" required data-parsley-type="number" data-parsley-required-message="This field is required."
                            data-parsley-type-message="Please enter a valid number.">
                            @error('carbon_emission_rate')
                                <span class="alert text-danger">{{ $message }}</span>
                            @enderror
                        </div>
                        <div class="mb-3">
                            <label for="exampleInputPassword1" class="form-label">Carbon Emission Factor Per KWh <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="carbon_emission_factor_per_kwh" value="{{ @$record->carbon_emission_factor_per_kwh }}" onkeyup="allowDecimalOnly(this)" onfocus="allowDecimalOnly(this)" required data-parsley-type="number" data-parsley-required-message="This field is required."
                            data-parsley-type-message="Please enter a valid number.">
                            @error('carbon_emission_factor_per_kwh')
                                <span class="alert text-danger">{{ $message }}</span>
                            @enderror
                        </div>
                       

                        <div class="mb-3">
                        <label for="exampleInputPassword1" class="form-label">Mundra Port Distance (In Km) <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="mundra_port_distance" value="{{ @$record->mundra_port_distance }}" onkeyup="allowDecimalOnly(this)" onfocus="allowDecimalOnly(this)" required data-parsley-type="number" data-parsley-required-message="This field is required."
                            data-parsley-type-message="Please enter a valid number.">
                            @error('mundra_port_distance')
                                <span class="alert text-danger">{{ $message }}</span>
                            @enderror
                        </div>

                        <div class="mb-3">
                        <label for="exampleInputPassword1" class="form-label">Carbon Emission Rate For Electronic <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="carbon_emission_rate_for_electronic" value="{{ @$record->carbon_emission_rate_for_electronic }}" onkeyup="allowDecimalOnly(this)" onfocus="allowDecimalOnly(this)" required data-parsley-type="number" data-parsley-required-message="This field is required."
                            data-parsley-type-message="Please enter a valid number.">
                            @error('carbon_emission_rate_for_electronic')
                                <span class="alert text-danger">{{ $message }}</span>
                            @enderror
                        </div>

                        <div class="mb-3">
                        <label for="exampleInputPassword1" class="form-label">Carbon Emission Rate For Petrol <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="carbon_emission_rate_for_petrol" value="{{ @$record->carbon_emission_rate_for_petrol }}" onkeyup="allowDecimalOnly(this)" onfocus="allowDecimalOnly(this)" required data-parsley-type="number" data-parsley-required-message="This field is required."
                            data-parsley-type-message="Please enter a valid number.">
                            @error('carbon_emission_rate_for_petrol')
                                <span class="alert text-danger">{{ $message }}</span>
                            @enderror
                        </div>

                        <div class="mb-3">
                        <label for="exampleInputPassword1" class="form-label">Carbon Emission Rate For Diesel <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="carbon_emission_rate_for_diesel" value="{{ @$record->carbon_emission_rate_for_diesel }}" onkeyup="allowDecimalOnly(this)" onfocus="allowDecimalOnly(this)" required data-parsley-type="number" data-parsley-required-message="This field is required."
                            data-parsley-type-message="Please enter a valid number.">
                            @error('carbon_emission_rate_for_diesel')
                                <span class="alert text-danger">{{ $message }}</span>
                            @enderror
                        </div>

                        <div class="mb-3">
                        <label for="exampleInputPassword1" class="form-label">Carbon Emission Rate For Hybrid <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="carbon_emission_rate_for_hybrid" value="{{ @$record->carbon_emission_rate_for_hybrid }}" onkeyup="allowDecimalOnly(this)" onfocus="allowDecimalOnly(this)" required data-parsley-type="number" data-parsley-required-message="This field is required."
                            data-parsley-type-message="Please enter a valid number.">
                            @error('carbon_emission_rate_for_hybrid')
                                <span class="alert text-danger">{{ $message }}</span>
                            @enderror
                        </div>

                        <div class="mb-3">
                        <label for="exampleInputPassword1" class="form-label">Carbon Emission Rate For CNG <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="carbon_emission_rate_for_cng" value="{{ @$record->carbon_emission_rate_for_cng }}" onkeyup="allowDecimalOnly(this)" onfocus="allowDecimalOnly(this)" required data-parsley-type="number" data-parsley-required-message="This field is required."
                            data-parsley-type-message="Please enter a valid number.">
                            @error('carbon_emission_rate_for_cng')
                                <span class="alert text-danger">{{ $message }}</span>
                            @enderror
                        </div>

                        <div class="mb-3">
                        <label for="exampleInputPassword1" class="form-label">Carbon Emission Rate For Solar <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="carbon_emission_rate_for_solar" value="{{ @$record->carbon_emission_rate_for_solar }}" onkeyup="allowDecimalOnly(this)" onfocus="allowDecimalOnly(this)" required data-parsley-type="number" data-parsley-required-message="This field is required."
                            data-parsley-type-message="Please enter a valid number.">
                            @error('carbon_emission_rate_for_solar')
                                <span class="alert text-danger">{{ $message }}</span>
                            @enderror
                        </div>

                        <div class="mb-3">
                        <label for="exampleInputPassword1" class="form-label">Carbon Emission Per Kg Parcel US <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="carbon_emission_per_kg_parcel_us" value="{{ @$record->carbon_emission_per_kg_parcel_us }}" onkeyup="allowDecimalOnly(this)" onfocus="allowDecimalOnly(this)" required data-parsley-type="number" data-parsley-required-message="This field is required."
                            data-parsley-type-message="Please enter a valid number.">
                            @error('carbon_emission_per_kg_parcel_us')
                                <span class="alert text-danger">{{ $message }}</span>
                            @enderror
                        </div>

                        <div class="mb-3">
                        <label for="exampleInputPassword1" class="form-label">Carbon Emission Per Kg Parcel UK <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="carbon_emission_per_kg_parcel_uk" value="{{ @$record->carbon_emission_per_kg_parcel_uk }}" onkeyup="allowDecimalOnly(this)" onfocus="allowDecimalOnly(this)" required data-parsley-type="number" data-parsley-required-message="This field is required."
                            data-parsley-type-message="Please enter a valid number.">
                            @error('carbon_emission_per_kg_parcel_uk')
                                <span class="alert text-danger">{{ $message }}</span>
                            @enderror
                        </div>

                        <div class="mb-3">
                        <label for="exampleInputPassword1" class="form-label">Carbon Emission Per Kg Parcel EU <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="carbon_emission_per_kg_parcel_eu" value="{{ @$record->carbon_emission_per_kg_parcel_eu }}" onkeyup="allowDecimalOnly(this)" onfocus="allowDecimalOnly(this)" required data-parsley-type="number" data-parsley-required-message="This field is required."
                            data-parsley-type-message="Please enter a valid number.">
                            @error('carbon_emission_per_kg_parcel_eu')
                                <span class="alert text-danger">{{ $message }}</span>
                            @enderror
                        </div>

                        <div class="mb-3">
                        <label for="exampleInputPassword1" class="form-label">Carbon Emission Per Kg Parcel CA <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="carbon_emission_per_kg_parcel_ca" value="{{ @$record->carbon_emission_per_kg_parcel_ca }}" onkeyup="allowDecimalOnly(this)" onfocus="allowDecimalOnly(this)" required data-parsley-type="number" data-parsley-required-message="This field is required."
                            data-parsley-type-message="Please enter a valid number.">
                            @error('carbon_emission_per_kg_parcel_ca')
                                <span class="alert text-danger">{{ $message }}</span>
                            @enderror
                        </div>

                        <div class="mb-3">
                        <label for="exampleInputPassword1" class="form-label">Carbon Emission Per Kg Parcel IN <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="carbon_emission_per_kg_parcel_in" value="{{ @$record->carbon_emission_per_kg_parcel_in }}" onkeyup="allowDecimalOnly(this)" onfocus="allowDecimalOnly(this)" required data-parsley-type="number" data-parsley-required-message="This field is required."
                            data-parsley-type-message="Please enter a valid number.">
                            @error('carbon_emission_per_kg_parcel_in')
                                <span class="alert text-danger">{{ $message }}</span>
                            @enderror
                        </div>

                        
                        <button type="submit" class="btn btn-primary">Submit</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection
@section('footer')

<script>
function allowDecimalOnly(input) {
    input.value = input.value.replace(/[^0-9.]/g, '').replace(/(\..*?)\..*/g, '$1'); // Only allow one decimal
}
</script>

@endsection
