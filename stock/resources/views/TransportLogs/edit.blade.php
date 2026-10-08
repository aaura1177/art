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
        <h2>Transport Logs</h2>
        <div class="col"><a class="btn btn-success float-end" style="color: #fff;" href="{{ route('transport.logs.index')}}"><i class="fa fa-arrow-left"></i> Go Back</a></div>
    </div>

    <div class="card mb-3">
        <div class="card-body">
        <form action="{{ route('transport.logs.update',$record->id) }}" method="POST" data-parsley-validate>
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
                <label for="exampleInputEmail1" class="form-label">Vehicle Type <span class="text-danger">*</span></label>
                <select name="vehicle_type" class="form-control" required data-parsley-required-message="This field is required.">
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
                <select name="fuel_type" class="form-control" required data-parsley-required-message="This field is required.">
                    <option value="Petrol" @if($record->fuel_type == "Petrol" ) selected @endif>Petrol</option>
                    <option value="Diesel" @if($record->fuel_type == "Diesel" ) selected @endif>Diesel</option>
                    <option value="CNG" @if($record->fuel_type == "CNG" ) selected @endif>CNG</option>
                    <option value="Electricity" @if($record->fuel_type == "Electricity" ) selected @endif>Electricity</option>
                    <option value="Hybrid" @if($record->fuel_type == "Hybrid" ) selected @endif>Hybrid</option>
                </select>
                @error('fuel_type')
                    <span class="alert text-danger">{{ $message }}</span>
                @enderror
            </div>
            
            <div class="mb-3">
                <label for="exampleInputPassword1" class="form-label">Distance Travelled <span class="text-danger">*</span></label>
                <input type="text" class="form-control" name="distance_travelled" id="distance_travelled" value="{{ $record->distance_travelled }}" onkeyup="allowDecimalOnly(this)" onfocus="allowDecimalOnly(this)"
                required data-parsley-type="number" data-parsley-required-message="This field is required."
                data-parsley-type-message="Please enter a valid number.">
                @error('distance_travelled')
                    <span class="alert text-danger">{{ $message }}</span>
                @enderror
            </div>
            <div class="mb-3">
                <label for="exampleInputPassword1" class="form-label">No. Of Rounds <span class="text-danger">*</span></label>
                <input type="number" min="1" class="form-control" name="number_of_rounds" value="{{ $record->number_of_rounds }}" id="no_of_rounds" onkeyup="allowDecimalOnly(this)" onfocus="allowDecimalOnly(this)" required data-parsley-type="number" data-parsley-required-message="This field is required."
                data-parsley-type-message="Please enter a valid number." >
                @error('no_of_rounds')
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