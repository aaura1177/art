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
        <h2>Production Logs</h2>
        <div class="col">
            <a class="btn btn-primary float-end" style="color: #fff;" href="{{ route('production.logs.fuel-consumption')}}"> Go To Fuel Consumption </a>
            <a class="btn btn-success float-end" style="color: #fff; margin-right:5px;" href="{{ route('production.logs.power-consumption')}}"> Go To Power Consumption </a>
        </div>
    </div>

    <div class="card mb-3">
        <div class="card-body">
        <form action="{{ route('production.logs.store') }}" method="POST" data-parsley-validate enctype="multipart/form-data">
            @csrf
            <div class="mb-3">
                <label for="exampleInputEmail1" class="form-label">Supplier Name <span class="text-danger">*</span></label>
                 <select name="supplier_id" class="form-control" required data-parsley-required-message="This field is required." readonly>
                    <option value="{{ $supplier->id }}" selected>{{ $supplier->c_name }}</option>
                </select>
                @error('supplier_id')
                    <span class="alert text-danger">{{ $message }}</span>
                @enderror
            </div>

            <div class="mb-3">
                <label for="exampleInputEmail1" class="form-label">Type <span class="text-danger">*</span></label>
                <select name="type" class="form-control" required data-parsley-required-message="This field is required.">
                    <option value="Electricity" selected>Electricity</option>
                    <option value="Transport">Transport</option>
                </select>
                @error('type')
                    <span class="alert text-danger">{{ $message }}</span>
                @enderror
            </div>
           
            <div class="" id="power_div">
                <div class="mb-3">
                    <label for="exampleInputPassword1" class="form-label">Is Value In Percentage <span class="text-danger">*</span></label>
                    <select name="is_value_in_percentage" id="is_value_in_percentage" class="form-control">
                        <option value="1">Yes</option>
                        <option value="0" selected>No</option>
                    </select>
                    @error('is_value_in_percentage')
                        <span class="alert text-danger">{{ $message }}</span>
                    @enderror
                </div>
                <div class="mb-3">
                    <label for="exampleInputPassword1" class="form-label">Is Solar <span class="text-danger">*</span></label>
                    <select name="is_solar" id="is_solar" class="form-control">
                        <option value="1">Yes</option>
                        <option value="0" selected>No</option>
                    </select>
                    @error('is_solar')
                        <span class="alert text-danger">{{ $message }}</span>
                    @enderror
                </div>

                <div class="mb-3">
                    <label for="exampleInputPassword1" class="form-label"> 
                        Actual Value Without Percentage <span class="text-danger">*</span>
                    </label>
                    <input type="text" class="form-control" name="actual_value" id="actual_value" value="{{ old('actual_value') }}" onkeyup="allowDecimalOnly(this)" onfocus="allowDecimalOnly(this)"
                    required data-parsley-type="number" data-parsley-required-message="This field is required."
                    data-parsley-type-message="Please enter a valid number." >
                    @error('actual_value')
                        <span class="alert text-danger">{{ $message }}</span>
                    @enderror
                </div>
                <div class="mb-3 d-none" id="percentage_div">
                    <label for="exampleInputPassword1" class="form-label">Percentage</label>
                    <input type="text" class="form-control" name="percentage_value" id="percentage_value" value="{{ old('percentage_value') }}" onkeyup="allowDecimalOnly(this)" onfocus="allowDecimalOnly(this)"
                   data-parsley-type="number" data-parsley-type-message="Please enter a valid number." >
                    @error('percentage_value')
                        <span class="alert text-danger">{{ $message }}</span>
                    @enderror
                </div>
                <div class="mb-3">
                    <label for="exampleInputPassword1" class="form-label">Power Consumed <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" name="power_consumed" id="power_consumed" value="{{ old('power_consumed') }}" onkeyup="allowDecimalOnly(this)" onfocus="allowDecimalOnly(this)"
                    required data-parsley-type="number" data-parsley-required-message="This field is required."
                    data-parsley-type-message="Please enter a valid number." >
                    @error('power_consumed')
                        <span class="alert text-danger">{{ $message }}</span>
                    @enderror
                </div>

                <div class="mb-3">
                    <label for="exampleInputPassword1" class="form-label">Upload Challan</label>
                    <input type="file" class="form-control" name="chalan_url" id="chalan_url">
                    @error('chalan_url')
                        <span class="alert text-danger">{{ $message }}</span>
                    @enderror
                </div>
                <div class="mb-3">
                    <label for="exampleInputPassword1" class="form-label">Upload Bill</label>
                    <input type="file" class="form-control" name="e-bill" id="e-bill">
                    @error('e-bill')
                        <span class="alert text-danger">{{ $message }}</span>
                    @enderror
                </div>                
            </div>
           
            <div class="d-none" id="fuel_div">
                <div class="mb-3">
                    <label for="exampleInputEmail1" class="form-label">Vehicle Type <span class="text-danger">*</span></label>
                    <select name="vehicle_type" class="form-control">
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
                    <select name="fuel_type" class="form-control" >
                        <option value="Petrol" selected>Petrol</option>
                        <option value="Diesel">Diesel</option>
                        <option value="CNG">CNG</option>
                        <option value="Electricity">Electricity</option>
                        <option value="Hybrid">Hybrid</option>
                    </select>
                    @error('fuel_type')
                        <span class="alert text-danger">{{ $message }}</span>
                    @enderror
                </div>
                
                <div class="mb-3">
                    <label for="exampleInputPassword1" class="form-label">Distance travelled <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" name="distance_travelled" id="distance_travelled" value="{{ old('distance_travelled') }}" onkeyup="allowDecimalOnly(this)" onfocus="allowDecimalOnly(this)"
                    data-parsley-type="number" >
                    @error('distance_travelled')
                        <span class="alert text-danger">{{ $message }}</span>
                    @enderror
                </div>
                
                <div class="mb-3">
                    <label for="exampleInputPassword1" class="form-label">No. Of Rounds <span class="text-danger">*</span></label>
                    <input type="number" min="0" class="form-control" name="number_of_rounds" value="{{ old('number_of_rounds') }}" id="no_of_rounds" onkeyup="allowDecimalOnly(this)" onfocus="allowDecimalOnly(this)" data-parsley-type="number">
                    @error('number_of_rounds')
                        <span class="alert text-danger">{{ $message }}</span>
                    @enderror
                </div>

                <div class="mb-3">
                    <label for="transport_chalan_url" class="form-label">Upload Challan</label>
                    <input type="file" class="form-control" name="chalan_url" id="transport_chalan_url">
                    @error('chalan_url')
                        <span class="alert text-danger">{{ $message }}</span>
                    @enderror
                </div>
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
    } else if(selectedValue < 0) {
        alert("Percentage value cannot be less than 0.");
        $(this).val(0);
    }
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