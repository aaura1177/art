@extends('layouts.app')

@section('content')


    <style>
        .form-label {
            font-weight: bold;
        }
    </style>
    <div class="row mx-1 my-2">
        <h2>Sustainability Stage-4</h2>
        <div class="col"><a class="btn btn-success float-end" style="color: #fff;" href="{{ route('sustainability.stage4.index')}}"><i class="fa fa-arrow-left"></i> Go Back </a></div>
    </div>

    <div class="card mb-3">
        <div class="card-body">
        <form action="{{ route('sustainability.stage4.update',$record->id) }}" method="POST" data-parsley-validate>
            @csrf
            
            <div class="mb-3">
                <label for="exampleInputPassword1" class="form-label">Number of Containers <span class="text-danger">*</span></label>
                <input type="text" class="form-control" name="number_of_containers" id="number_of_containers" value="{{ $record->number_of_containers }}" onkeyup="allowDecimalOnly(this)" onfocus="allowDecimalOnly(this)"
                onchange="calculateCarbonEmission2(`{{ (@$sustainabilityVariable->carbon_emission_rate) ?? '0' }}`,`{{ (@$sustainabilityVariable->mundra_port_distance) ?? '864' }}`)" required data-parsley-type="number" data-parsley-required-message="This field is required."
                data-parsley-type-message="Please enter a valid number.">
                @error('number_of_containers')
                    <span class="alert text-danger">{{ $message }}</span>
                @enderror
            </div>

            <div class="mb-3">
                <label for="exampleInputPassword1" class="form-label">Mundra Port Distance <span class="text-danger">*</span></label>
                <input type="text" class="form-control" value="{{ (@$sustainabilityVariable->mundra_port_distance) ?? '864' }}" readonly >
            </div>

            <div class="mb-3">
                <label for="exampleInputPassword1" class="form-label">Carbon Emission <span class="text-danger">*</span></label>
                <input type="text" class="form-control" name="carbon_emission" id="carbon_emission" value="{{ $record->carbon_emission }}" readonly required data-parsley-type="number" data-parsley-required-message="This field is required."  data-parsley-type-message="Please enter a valid number.">
                @error('carbon_emission')
                    <span class="alert text-danger">{{ $message }}</span>
                @enderror
            </div>
            
            <div class="mb-3">
                <label for="exampleInputPassword1" class="form-label">Month/Year <span class="text-danger">*</span></label>
                <input type="month" class="form-control" name="month_year" value="{{ date('Y-m', strtotime($record->month_year)) }}" required data-parsley-required-message="This field is required.">
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

function calculateCarbonEmission2(carbon_emission_rate,mundra_port_distance) {
    var number_of_containers = parseFloat(document.getElementById('number_of_containers').value) || 0;
    var carbonEmission = number_of_containers * carbon_emission_rate * mundra_port_distance; 
    document.getElementById('carbon_emission').value = carbonEmission.toFixed(2);
}


</script>

@endsection