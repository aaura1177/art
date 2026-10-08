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
        <h2>Sustainability Stage-2</h2>
        <div class="col"><a class="btn btn-success float-end" style="color: #fff;" href="{{ route('sustainability.stage2.fuel-consumption')}}"><i class="fa fa-arrow-left"></i> Go Back To Fuel Consumption </a></div>
    </div>

    <div class="card mb-3">
        <div class="card-body">
        <form action="{{ route('sustainability.stage2.update',$record->id) }}" method="POST" data-parsley-validate>
            @csrf
            <div class="mb-3">
                <label for="exampleInputEmail1" class="form-label">Supplier Name <span class="text-danger">*</span></label>
                <select name="supplier_id" class="form-control select2" required data-parsley-required-message="This field is required.">
                    <option value="">Select Supplier</option>
                    @foreach($suppliers as $supplier)
                        <option value="{{ $supplier->id }}" @if($record->supplier_id == $supplier->id ) selected @endif>{{ $supplier->c_name }}</option>
                    @endforeach
                </select>
                @error('supplier_id')
                    <span class="alert text-danger">{{ $message }}</span>
                @enderror
            </div>

            <div class="mb-3">
                <label for="exampleInputEmail1" class="form-label">Type <span class="text-danger">*</span></label>
                <select name="type" class="form-control select2" required data-parsley-required-message="This field is required.">
                    <option value="Electricity" @if($record->type == "Electricity" ) selected @endif>Electricity</option>
                    <option value="Transport" @if($record->type == "Transport" ) selected @endif>Transport</option>
                </select>
                @error('type')
                    <span class="alert text-danger">{{ $message }}</span>
                @enderror
            </div>
            
             <!-- For Non Solar Bill -->
            <input type="hidden" id="carbon_emission_factor_per_kwh" value="{{ @$sustainabilityVariable->carbon_emission_factor_per_kwh ?? 0}}">
            <!-- For Solar -->
            <input type="hidden" id="carbon_emission_factor_for_solar" value="{{ @$sustainabilityVariable->carbon_emission_rate_for_solar ?? 0 }}">
            <!-- For Transport -->
            @php 
                if($record->fuel_type == "Petrol" ) 
                {
                    $value= (@$sustainabilityVariable->carbon_emission_rate_for_petrol) ?? 0 ;
                }
                elseif($record->fuel_type == "Diesel" ) 
                {
                    $value= (@$sustainabilityVariable->carbon_emission_rate_for_diesel) ?? 0 ;
                }
                elseif($record->fuel_type == "CNG" ) 
                {
                    $value= (@$sustainabilityVariable->carbon_emission_rate_for_cng) ?? 0 ;
                }
                elseif($record->fuel_type == "Electricity" ) 
                {
                    $value= (@$sustainabilityVariable->carbon_emission_rate_for_electronic) ?? 0 ;
                }
                elseif($record->fuel_type == "Hybrid" ) 
                {
                    $value= (@$sustainabilityVariable->carbon_emission_rate_for_hybrid) ?? 0 ;
                }
            @endphp
            <input type="hidden" id="carbon_emission_rate_selected" value="{{ $value ?? 0 }}">

            <div class="{{ ($record->type == 'Transport' ) ? 'd-none' : '' }}" id="power_div">
                 <div class="mb-3">
                    <label for="exampleInputPassword1" class="form-label">Is Value In Percentage <span class="text-danger">*</span></label>
                    <select name="is_value_in_percentage" id="is_value_in_percentage" class="form-control" >
                        <option value="1" @if($record->is_value_in_percentage == "1" ) selected @endif>Yes</option>
                        <option value="0" @if($record->is_value_in_percentage == "0" ) selected @endif>No</option>
                    </select>
                    @error('is_value_in_percentage')
                        <span class="alert text-danger">{{ $message }}</span>
                    @enderror
                </div>
                 <div class="mb-3">
                    <label for="exampleInputPassword1" class="form-label">Is Solar <span class="text-danger">*</span></label>
                    <select name="is_solar" id="is_solar" class="form-control">
                        <option value="1" @if($record->is_solar == "1" ) selected @endif>Yes</option>
                        <option value="0" @if($record->is_solar == "0" ) selected @endif>No</option>
                    </select>
                    @error('is_solar')
                        <span class="alert text-danger">{{ $message }}</span>
                    @enderror
                </div>

                <div class="mb-3">
                    <label for="exampleInputPassword1" class="form-label"> 
                        Actual Value Without Percentage <span class="text-danger">*</span>
                    </label>
                    <input type="text" class="form-control" name="actual_value" id="actual_value" value="{{ $record->actual_value }}" onkeyup="allowDecimalOnly(this)" onfocus="allowDecimalOnly(this)"
                    data-parsley-type="number" 
                    data-parsley-type-message="Please enter a valid number." >
                    @error('actual_value')
                        <span class="alert text-danger">{{ $message }}</span>
                    @enderror
                </div>
                <div class="mb-3 d-none" id="percentage_div">
                    <label for="exampleInputPassword1" class="form-label">Percentage</label>
                    <input type="text" class="form-control" name="percentage_value" id="percentage_value" value="{{ $record->percentage_value }}" onkeyup="allowDecimalOnly(this)" onfocus="allowDecimalOnly(this)"
                   data-parsley-type="number" data-parsley-type-message="Please enter a valid number." onchange="calculateCarbonEmission2()" >
                    @error('percentage_value')
                        <span class="alert text-danger">{{ $message }}</span>
                    @enderror
                </div>

                <div class="mb-3">
                    <label for="exampleInputPassword1" class="form-label">Power Consumed <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" name="power_consumed" id="power_consumed" value="{{ $record->power_consumed }}" onkeyup="allowDecimalOnly(this)" onfocus="allowDecimalOnly(this)"
                    onchange="calculateCarbonEmission2()">
                    @error('power_consumed')
                        <span class="alert text-danger">{{ $message }}</span>
                    @enderror
                </div>

            </div>
            <div class="{{ ($record->type == 'Electricity' ) ? 'd-none' : '' }}" id="fuel_div">
                <div class="mb-3">
                    <label for="exampleInputEmail1" class="form-label">Vehicle Type <span class="text-danger">*</span></label>
                    <select name="vehicle_type" class="form-control" >
                        <option value="Car" @if($record->vehicle_type == "Car" ) selected @endif>Car</option>
                        <option value="Bike" @if($record->vehicle_type == "Bike" ) selected @endif>Bike</option>
                        <option value="Scooty" @if($record->vehicle_type == "Scooty" ) selected @endif>Scooty</option>
                        <option value="Truck" @if($record->vehicle_type == "Truck" ) selected @endif>Truck</option>
                        <option value="Trolley" @if($record->vehicle_type == "Trolley" ) selected @endif>Trolley</option>
                        <option value="Bus" @if($record->vehicle_type == "Bus" ) selected @endif>Bus</option>
                        <option value="Auto" @if($record->vehicle_type == "Auto" ) selected @endif>Auto</option>
                    </select>
                    @error('vehicle_type')
                        <span class="alert text-danger">{{ $message }}</span>
                    @enderror
                </div>

                <div class="mb-3">
                    <label for="exampleInputEmail1" class="form-label">Fuel Type <span class="text-danger">*</span></label>
                    <select name="fuel_type" id="fuel_type" class="form-control" required data-parsley-required-message="This field is required.">
                    <option value="Petrol" @if($record->fuel_type == "Petrol" ) selected @endif data-cv="{{ (@$sustainabilityVariable->carbon_emission_rate_for_petrol) ?? 0 }}">Petrol</option>

                    <option value="Diesel" @if($record->fuel_type == "Diesel" ) selected @endif data-cv="{{ (@$sustainabilityVariable->carbon_emission_rate_for_diesel) ?? 0 }}">Diesel</option>

                    <option value="CNG" @if($record->fuel_type == "CNG" ) selected @endif data-cv="{{ (@$sustainabilityVariable->carbon_emission_rate_for_cng) ?? 0 }}">CNG</option>

                    <option value="Electricity" @if($record->fuel_type == "Electricity" ) selected @endif data-cv="{{ (@$sustainabilityVariable->carbon_emission_rate_for_electronic) ?? 0 }}">Electricity</option>

                    <option value="Hybrid" @if($record->fuel_type == "Hybrid" ) selected @endif data-cv="{{ (@$sustainabilityVariable->carbon_emission_rate_for_hybrid) ?? 0 }}">Hybrid</option>
                </select>
                    @error('fuel_type')
                        <span class="alert text-danger">{{ $message }}</span>
                    @enderror
                </div>
                
                <div class="mb-3">
                    <label for="exampleInputPassword1" class="form-label">Distance travelled <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" name="distance_travelled" id="distance_travelled" value="{{ $record->distance_travelled }}" onkeyup="allowDecimalOnly(this)" onfocus="allowDecimalOnly(this)"
                    onchange="calculateCarbonEmission()">
                    @error('distance_travelled')
                        <span class="alert text-danger">{{ $message }}</span>
                    @enderror
                </div>
                <div class="mb-3">
                    <label for="exampleInputPassword1" class="form-label">No. Of Rounds <span class="text-danger">*</span></label>
                    <input type="number" min="0" class="form-control" name="number_of_rounds" value="{{ $record->number_of_rounds }}" id="no_of_rounds" onkeyup="allowDecimalOnly(this)" onfocus="allowDecimalOnly(this)" onchange="calculateCarbonEmission()">
                    @error('number_of_rounds')
                        <span class="alert text-danger">{{ $message }}</span>
                    @enderror
                </div>
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

$(document).ready(function() {
    $('.select2').select2({
        placeholder: "Select Supplier",
        allowClear: true
    });
});


$("#is_value_in_percentage").change(function() {
    var selectedValue = $(this).val();
    if(selectedValue == '1') {
         $("#power_consumed").attr('readonly', 'readonly');
        $("#percentage_div").removeClass("d-none");
        $("#percentage_value").attr("required", "required");
    } else {
        $("#percentage_div").addClass("d-none");
        $("#percentage_value").removeAttr("required").val("");
         $("#power_consumed").removeAttr('readonly').val("");
    }
});

$("#is_solar").change(function() {
    var selectedValue = $(this).val();
    if(selectedValue == '1') {
        calculateCarbonEmission2();
    } else {
       calculateCarbonEmission2();
    }
});




$("#percentage_value").change(function() {
    var selectedValue = $(this).val();
    var actual_value = $("#actual_value").val();
    if(selectedValue > 0 && actual_value > 0) {
        var percentage = (actual_value * selectedValue) / 100;
        $("#power_consumed").val(percentage);
    }

    if(selectedValue > 100) {
        alert("Percentage value cannot be greater than 100.");
        $(this).val(0);
         $("#power_consumed").val(0);
    } else if(selectedValue < 0) {
        alert("Percentage value cannot be less than 0.");
        $(this).val(0);
         $("#power_consumed").val(0);
    }
    calculateCarbonEmission2();
});


$("select[name='type']").change(function() {
    var selectedType = $(this).val();
   if(selectedType == 'Electricity') {
        $("#power_div").removeClass("d-none");
        $("#fuel_div").addClass("d-none");
        $("#distance_travelled").removeAttr("required").val(0);
        $("#no_of_rounds").removeAttr("required").val(0);
        $("#power_consumed").attr("required", "required");
        $("#actual_value").attr("required");
    } else {
        $("#power_div").addClass("d-none");
        $("#fuel_div").removeClass("d-none");
        $("#distance_travelled").attr("required", "required");
        $("#no_of_rounds").attr("required", "required");
        $("#power_consumed").removeAttr("required").val(0);
        $("#actual_value").removeAttr("required").val(0);
    }
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


function calculateCarbonEmission2() {
    var isSolar = document.getElementById('is_solar').value;
    if(isSolar == '1') {
       var carbon_emission_factor = document.getElementById('carbon_emission_factor_for_solar').value;
    } else {
       var carbon_emission_factor = document.getElementById('carbon_emission_factor_per_kwh').value;
    }

    var power_consumed = parseFloat(document.getElementById('power_consumed').value) || 0;
    var carbonEmission = power_consumed * carbon_emission_factor; 

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