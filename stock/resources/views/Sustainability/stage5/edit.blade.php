@extends('layouts.app')

@section('content')


    <style>
        .form-label {
            font-weight: bold;
        }
    </style>
    <div class="row mx-1 my-2">
        <h2>Sustainability Stage-5</h2>
        <div class="col"><a class="btn btn-success float-end" style="color: #fff;" href="{{ route('sustainability.stage5.index')}}"><i class="fa fa-arrow-left"></i> Go Back </a></div>
    </div>

    <div class="card mb-3">
        <div class="card-body">
        <form action="{{ route('sustainability.stage5.update',$record->id) }}" method="POST" data-parsley-validate>
            @csrf
            <div class="mb-3">
                <label for="exampleInputEmail1" class="form-label">Port <span class="text-danger">*</span></label>
                <select name="port_id" class="form-control" required data-parsley-required-message="This field is required.">
                    <option value="">Select Port</option>
                    @foreach($ports as $port)
                        <option value="{{ $port->id }}" @if($record->port_id == $port->id ) selected @endif data-carbon-emission-per-container="{{ $port->carbon_emission_per_container }}">{{ $port->name }}</option>
                    @endforeach
                </select>
                @error('port_id')
                    <span class="alert text-danger">{{ $message }}</span>
                @enderror
            </div>

            <div class="mb-3">
                <label for="exampleInputPassword1" class="form-label">Container Sent <span class="text-danger">*</span></label>
                <input type="text" class="form-control" name="container_sent" id="container_sent" value="{{ $record->container_sent }}" onkeyup="allowDecimalOnly(this)" onfocus="allowDecimalOnly(this)"
                onchange="calculateCarbonEmission2()" required data-parsley-type="number" data-parsley-required-message="This field is required." data-parsley-type-message="Please enter a valid number.">
                @error('container_sent')
                    <span class="alert text-danger">{{ $message }}</span>
                @enderror
            </div>

            <div class="mb-3">
                <label for="exampleInputPassword1" class="form-label">Carbon Emission Per Container <span class="text-danger">*</span></label>
                <input type="text" class="form-control" id="carbon_emission_per_container"  value="{{ $record->carbon_emission_per_container }}" readonly>
                @error('carbon_emission_per_container')
                    <span class="alert text-danger">{{ $message }}</span>
                @enderror
            </div>

            <div class="mb-3">
                <label for="exampleInputPassword1" class="form-label">Carbon Emission <span class="text-danger">*</span></label>
                <input type="text" class="form-control" name="carbon_emission" id="carbon_emission" value="{{ $record->carbon_emission }}" readonly required data-parsley-type="number" data-parsley-required-message="This field is required." data-parsley-type-message="Please enter a valid number.">
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

function calculateCarbonEmission2() {
    var container_sent = parseFloat(document.getElementById('container_sent').value) || 0;
    var carbon_emission_per_container = parseFloat(document.getElementById('carbon_emission_per_container').value) || 0;
    var carbonEmission = container_sent * carbon_emission_per_container; 
    document.getElementById('carbon_emission').value = carbonEmission.toFixed(2);
}

function updateCarbonEmission() {
    var emission = $('select[name="port_id"]').find(':selected').data('carbon-emission-per-container');
    $('#carbon_emission_per_container').val(emission);
    calculateCarbonEmission2() ;
}

// Update on change
$('select[name="port_id"]').on('change', updateCarbonEmission);

// Update on page load
$(document).ready(function() {
    updateCarbonEmission();
});

</script>

@endsection