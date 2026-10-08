@extends('layouts.app')

@section('content')

    <style>
        .form-label {
            font-weight: bold;
        }
    </style>
    <div class="row mx-1 my-2">
        <h2>Sustainability Stage-3 Employee</h2>
        <div class="col">
            <a class="btn btn-success float-end" style="color: #fff;" href="{{ route('sustainability.stage3.employee.index')}}"><i class="fa fa-arrow-left"></i> Go Back </a>
        </div>
    </div>

    <div class="card mb-3">
        <div class="card-body">
        <form action="{{ route('sustainability.stage3.employee.store') }}" method="POST" data-parsley-validate>
            @csrf
            <div class="mb-3">
                <label for="exampleInputPassword1" class="form-label">User Name <span class="text-danger">*</span></label>
                <input type="text" class="form-control" name="user_name" id="user_name" value="{{ old('user_name') }}" data-parsley-required-message="This field is required." required>
                @error('user_name')
                    <span class="alert text-danger">{{ $message }}</span>
                @enderror
                <span class="text-danger">Note: During Autocomplete, data will be fetched from HRMS Portal if there is transport detailed saved for that user. </span>
            </div>
            <input type="hidden" name="emp_id" id="emp_id">

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

            <div class="mb-3">
                <label for="exampleInputEmail1" class="form-label">Fuel Type <span class="text-danger">*</span></label>
                <select name="fuel_type" id="fuel_type" class="form-control" required data-parsley-required-message="This field is required.">
                    <option value="Petrol" data-cv="{{ (@$sustainabilityVariable->carbon_emission_rate_for_petrol) ?? 0 }}">Petrol</option>
                    <option value="Diesel" data-cv="{{ (@$sustainabilityVariable->carbon_emission_rate_for_diesel) ?? 0 }}">Diesel</option>
                    <option value="CNG" data-cv="{{ (@$sustainabilityVariable->carbon_emission_rate_for_cng) ?? 0 }}">CNG</option>
                    <option value="Electricity" data-cv="{{ (@$sustainabilityVariable->carbon_emission_rate_for_electronic) ?? 0 }}">Electricity</option>
                    <option value="Hybrid" data-cv="{{ (@$sustainabilityVariable->carbon_emission_rate_for_hybrid) ?? 0 }}">Hybrid</option>
                </select>
                @error('fuel_type')
                    <span class="alert text-danger">{{ $message }}</span>
                @enderror
            </div>

            <input type="hidden" id="carbon_emission_rate_selected"  value="{{ @$sustainabilityVariable->carbon_emission_rate_for_petrol ?? 0 }}">
            
            <div class="mb-3">
                <label for="exampleInputPassword1" class="form-label">Distance travelled ( One Way ) <span class="text-danger">*</span></label>
                <input type="text" class="form-control" name="distance_travelled" id="distance_travelled" value="{{ old('distance_travelled') }}" onkeyup="allowDecimalOnly(this)" onfocus="allowDecimalOnly(this)"
                data-parsley-type="number" onchange="calculateCarbonEmission()">
                @error('distance_travelled')
                    <span class="alert text-danger">{{ $message }}</span>
                @enderror
            </div>

            <div class="mb-3">
                <label for="exampleInputPassword1" class="form-label">No. Of Rounds <span class="text-danger">*</span></label>
                <input type="number" min="1" class="form-control" name="number_of_rounds" value="{{ old('number_of_rounds') }}" id="no_of_rounds" onkeyup="allowDecimalOnly(this)" onfocus="allowDecimalOnly(this)" data-parsley-type="number" onchange="calculateCarbonEmission()" required data-parsley-type="number" data-parsley-required-message="This field is required." data-parsley-type-message="Please enter a valid number.">
                @error('number_of_rounds')
                    <span class="alert text-danger">{{ $message }}</span>
                @enderror
            </div>

            <div class="mb-3">
                <label for="exampleInputPassword1" class="form-label">Days <span class="text-danger">*</span></label>
                <input type="number" min="1" class="form-control" name="days" value="{{ old('number_of_rounds') }}" id="no_of_days" onkeyup="allowDecimalOnly(this)" onfocus="allowDecimalOnly(this)" data-parsley-type="number" onchange="calculateCarbonEmission()" required data-parsley-type="number" data-parsley-required-message="This field is required." data-parsley-type-message="Please enter a valid number.">
                @error('days')
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
        source: "{{ route('sustainability.stage3.employee.autocomplete.username') }}",
        minLength: 2,
        select: function(event, ui) {
            $('#emp_id').val(ui.item.emp_id);
            $('#vehicle_type').val(ui.item.vehicle_type).trigger('change');
            $('#fuel_type').val(ui.item.fuel_type).trigger('change');
            $('#distance_travelled').val(ui.item.one_way_distance);
            $('#no_of_rounds').val(ui.item.number_of_rounds);
            $('#no_of_days').val(ui.item.attendance_count);
            calculateCarbonEmission();
        }
    });

   $("#month_year").on('change', function() {
        var selectedDate = $(this).val();
        var term = $("#user_name").val();

        $.ajax({
            url: "{{ route('sustainability.stage3.employee.autocomplete.username') }}",
            type: "GET",
            data: {
                monthYear: selectedDate,
                term: term,
            },
            success: function(response) {
                if (response.length > 0) {
                    $('#emp_id').val(response[0].emp_id);
                    $('#vehicle_type').val(response[0].vehicle_type).trigger('change');
                    $('#fuel_type').val(response[0].fuel_type).trigger('change');
                    $('#distance_travelled').val(response[0].one_way_distance);
                    $('#no_of_rounds').val(response[0].number_of_rounds);
                    $('#no_of_days').val(response[0].attendance_count);

                    calculateCarbonEmission();
                } else {
                    alert("No data found for the selected month/year.");
                }
            }
        });
    });
});

function calculateCarbonEmission() {
    var carbon_emission_rate = parseFloat(document.getElementById('carbon_emission_rate_selected').value) || 0;
    var distance = parseFloat(document.getElementById('distance_travelled').value) || 0;
    var rounds = parseInt(document.getElementById('no_of_rounds').value) || 1;
    var no_of_days = parseInt(document.getElementById('no_of_days').value) || 1;

    var carbonEmission = distance * rounds * no_of_days * carbon_emission_rate; 
    
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