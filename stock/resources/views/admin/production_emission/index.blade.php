@extends('layouts.app')

@section('content')
<div class="row mx-3 my-2">
      <h2>Production Supplier Emission</h2>
</div>
<div class="row mx-3 my-2">
    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addEmissionModal">
        Add Emission
    </button>
</div>

<div class="card">
      <div class="card-body">
        <div class="table-responsive">
            <table id="transportLogsTable" class="table table-bordered display" width="100%" cellspacing="0">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Supplier Name</th>
                        <th>Month</th>
                        <th>Percentage</th>
                        <th>Electricity Units (kWh)</th>
                        <th>Electricity Carbon Emission (kgCO2)</th>
                        <th>Distance Supplier to Artisan (kms)</th>
                        <th>Distance Carbon Emission (kgCO2)</th>
                        <th>Monthly Emission (kgCO2)</th>
                        <th>Eway Bill</th>
                        <th>Electricity Bill</th>
                        <th>Action </th>

                    </tr>
                </thead>
                <tbody>
                    @foreach($emissions as $emission)
                    <tr>
                        <td>{{ $emission['id'] }}</td>
                        <td>{{ $emission['supplier_name'] }}</td>
                        <td>{{ $emission['date'] }}</td>
                        <td>{{ $emission['percentage'] }}%</td>
                        <td>{{ $emission['electricity_unit'] }} KWh</td>
                        <td>{{ $emission['electricity_carbon_emission'] }} kg</td>
                        <td>{{ $emission['distance'] }} kms</td>
                        <td>{{ $emission['distance_emission'] }} kg</td>
                        <td>{{ $emission['monthly_emission'] }} kg</td>
                        <td>@if($emission['eway_attachement'])
                                <a href="{{ asset($emission['eway_attachement']) }}" target="_blank">Download Eway Bill</a>
                            @else
                                No Eway Bill
                            @endif</td>
                        <td>@if($emission['electricity_attachement'])
                                <a href="{{ asset($emission['electricity_attachement']) }}" target="_blank">Download Electricity Bill</a>
                            @else
                                No Electricity Bill
                            @endif</td>
                            <td>
    <button class="btn btn-sm btn-warning editEmission" 
        data-id="{{ $emission['id'] }}"
        data-supplier_id="{{ $emission['supplier_user_id'] }}"
        data-electricity_unit="{{ $emission['electricity_unit'] }}"
        data-percentage="{{ $emission['percentage'] }}"
        data-date="{{ \Carbon\Carbon::parse($emission['date'])->format('Y-m') }}"
        data-distance="{{ $emission['distance'] }}"
        data-vehicle_type="{{ $emission['vehicle_type'] }}"
        data-bs-toggle="modal" data-bs-target="#addEmissionModal">
        Edit
    </button>
</td>

                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
      </div>        
</div>

<!-- Add Emission Modal -->
<div class="modal fade" id="addEmissionModal" tabindex="-1" aria-labelledby="addEmissionModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="addEmissionModalLabel">Add Production Emission</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form action="{{route('admin.production_emission.store')}}" method="POST" enctype="multipart/form-data">
                    @csrf
                    <input type="hidden" name="emission_id" id="emission_id">

                    <div class="mb-3">
                        <label for="supplier_id" class="form-label">Supplier</label>
                        <select name="supplier_id" id="supplier_id" class="form-control selectpicker" data-live-search="true" required>
                            <option value="">Select Supplier</option>
                            @foreach($suppliers as $supplier)
                            <option value="{{ $supplier->id }}">{{ $supplier->firstname." ".$supplier->lastname }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="electricity_unit" class="form-label">Electricity Consumption (KWh)</label>
                        <input type="number" class="form-control" id="electricity_unit" name="electricity_unit" required>
                    </div>
                    <div class="mb-3">
                        <label for="percentage" class="form-label">Percentage</label>
                        <input type="number" class="form-control" id="percentage" name="percentage" required>
                    </div>
                    <div class="mb-3">
                        <label for="date" class="form-label">Month</label>
                        <input type="month" class="form-control" id="date" name="date" required>
                    </div>
                    <div class="mb-3">
                        <label for="distance" class="form-label">Distance travel (Supplier to Artisan)</label>
                        <input type="number" class="form-control" id="distance" name="distance" required>
                    </div>
                    <div class="mb-3">
                        <label for="electricity_bill" class="form-label">Electricity Bill</label>
                        <input type="file" class="form-control" id="electricity_bill" name="electricity_bill">
                    </div>
                    <div class="mb-3">
                        <label for="eway_bill" class="form-label">Eway Bill</label>
                        <input type="file" class="form-control" id="eway_bill" name="eway_bill">
                    </div>
                    <div class="mb-3">
                        <label for="vehicle_type" class="form-label">Vehicle Type</label>
                        <select class="form-control" id="vehicle_type" name="vehicle_type" required>
                            <option value="Diesel">Diesel</option>
                            <option value="Hybrid">Hybrid</option>
                            <option value="Petrol">Petrol</option>
                            <option value="Electric">Electric</option>
                        </select>
                    </div>
                    <button type="submit" class="btn btn-primary">Save Emission</button>
                </form>
            </div>
        </div>
    </div>
</div>

@endsection

@section('footer')

<script>
$(document).ready(function() {
    $('#transportLogsTable').DataTable({
        dom: 'Bfrtip',
        buttons: ['copy', 'csv', 'excel', 'pdf', 'print']
    });
    $('.selectpicker').selectpicker();
    $('#addEmissionBtn').on('click', function() {
        $('#emissionModalLabel').text("Add Production Emission");
        $('#emissionForm').attr('action', "{{ route('admin.production_emission.store') }}");
        $('#emission_id').val('');
        $('#supplier_id').val('').change();
        $('#electricity_unit').val('');
        $('#percentage').val('');
        $('#date').val('');
        $('#distance').val('');
        $('#vehicle_type').val('').change();
    });
    $(document).on('click', '.editEmission', function() {
        $('#emissionModalLabel').text("Edit Production Emission");
        $('#emissionForm').attr('action', "{{ route('admin.production_emission.store') }}");

        $('#emission_id').val($(this).data('id'));
        $('#supplier_id').val($(this).data('supplier_id')).change();
        $('#electricity_unit').val($(this).data('electricity_unit'));
        $('#percentage').val($(this).data('percentage'));
        $('#date').val($(this).data('date'));
        $('#distance').val($(this).data('distance'));
        $('#vehicle_type').val($(this).data('vehicle_type')).change();
    });
});
</script>
@endsection
