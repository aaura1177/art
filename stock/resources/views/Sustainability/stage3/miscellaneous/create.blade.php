@extends('layouts.app')

@section('content')

    
    <style>
        .form-label {
            font-weight: bold;
        }
    </style>
    <div class="row mx-1 my-2">
        <h2>Sustainability Stage-3 Miscellaneous</h2>
        <div class="col">
            <a class="btn btn-success float-end" style="color: #fff; margin-right:5px;" href="{{ route('sustainability.stage3.miscellaneous.index')}}"><i class="fa fa-arrow-left"></i>  Go Back </a>
        </div>
    </div>

    <div class="card mb-3">
        <div class="card-body">
        <form action="{{ route('sustainability.stage3.miscellaneous.store') }}" method="POST" data-parsley-validate>
            @csrf
            
            <div class="mb-3">
                <label for="exampleInputPassword1" class="form-label">User Name <span class="text-danger">*</span></label>
                <input type="text" class="form-control" name="user_name" id="user_name" value="{{ old('user_name') }}" data-parsley-required-message="This field is required." required>
                @error('user_name')
                    <span class="alert text-danger">{{ $message }}</span>
                @enderror
            </div>

            <div class="mb-3">
                <label for="exampleInputEmail1" class="form-label">Travel Mode <span class="text-danger">*</span></label>
                <select name="travel_mode" class="form-control" required data-parsley-required-message="This field is required.">
                    <option value="Air" selected>Air</option>
                    <option value="Train">Train</option>
                    <option value="Car">Car</option>
                    <option value="Bus">Bus</option>
                    <option value="Bike">Bike</option>
                </select>
                @error('travel_mode')
                    <span class="alert text-danger">{{ $message }}</span>
                @enderror
            </div>
           
            <div class="mb-3">
                <label for="exampleInputPassword1" class="form-label">Origin Name <span class="text-danger">*</span></label>
                <input type="text" class="form-control" name="origin_name" id="origin_name" value="{{ old('origin_name') }}" data-parsley-required-message="This field is required." required>
                @error('origin_name')
                    <span class="alert text-danger">{{ $message }}</span>
                @enderror
            </div>

            <div class="mb-3">
                <label for="exampleInputPassword1" class="form-label">Destination Name <span class="text-danger">*</span></label>
                <input type="text" class="form-control" name="destination_name" id="destination_name" value="{{ old('destination_name') }}" data-parsley-required-message="This field is required." required>
                @error('destination_name')
                    <span class="alert text-danger">{{ $message }}</span>
                @enderror
            </div>
           
            <div class="mb-3">
                <label for="exampleInputPassword1" class="form-label">Distance travelled <span class="text-danger">*</span></label>
                <input type="text" class="form-control" name="distance_travelled" id="distance_travelled" value="{{ old('distance_travelled') }}" onkeyup="allowDecimalOnly(this)" onfocus="allowDecimalOnly(this)"
                data-parsley-type="number" onchange="calculateCarbonEmission(`{{ (@$sustainabilityVariable->carbon_emission_rate) ?? '0' }}`)">
                @error('distance_travelled')
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

@push('styles')
<link href="{{ asset('ui-vendor/jquery-ui/css/jquery-ui.min.css') }}" rel="stylesheet">
@endpush
@push('scripts')
<script src="{{ asset('ui-vendor/jquery-ui/js/jquery-ui.min.js') }}"></script>
@endpush
<script>
function allowDecimalOnly(input) {
    input.value = input.value.replace(/[^0-9.]/g, '').replace(/(\..*?)\..*/g, '$1'); // Only allow one decimal
}

$(function() {
    $("#user_name").autocomplete({
        source: "{{ route('sustainability.stage3.miscellaneous.autocomplete.username') }}",
        minLength: 2
    });

    $("#origin_name").autocomplete({
        source: "{{ route('sustainability.stage3.miscellaneous.autocomplete.origin') }}",
        minLength: 2
    });

    $("#destination_name").autocomplete({
        source: "{{ route('sustainability.stage3.miscellaneous.autocomplete.destination') }}",
        minLength: 2
    });
});


function calculateCarbonEmission(carbon_emission_rate) {
    var distance = parseFloat(document.getElementById('distance_travelled').value) || 0;
    var carbonEmission = distance * carbon_emission_rate; 
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