@extends('layouts.app')

@section('content')
<div class="row mx-3 my-2">
    <h2>Transport Logs</h2>
    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#transportLogModal">Add Transport Log</button>
</div>

<div class="row mx-3 my-2">
    <form method="GET" action="{{ url('admin.transportlogs') }}"></form>
        <div class="row mb-6">
            <!-- Supplier Filter -->
            <div class="col-md-4">
                <label for="supplier_user_id" class="form-label">Filter by Supplier</label>
                <select name="supplier_user_id" id="supplier_user_id" class="form-control selectpicker" data-live-search="true">
                    <option value="">All Suppliers</option>
                    @foreach($suppliers as $supplier)
                    <option value="{{ $supplier->id }}" {{ request('supplier_user_id') == $supplier->id ? 'selected' : '' }}>
                        {{ $supplier->firstname." ".$supplier->lastname }}
                    </option>
                    @endforeach
                </select>
            </div>

            <!-- Month Filter -->
            <div class="col-md-4">
                <label for="month" class="form-label">Filter by Month</label>
                <select name="month" id="month" class="form-control">
                    <option value="">All Months</option>
                    @for ($m = 1; $m <= 12; $m++)
                    <option value="{{ $m }}" {{ request('month') == $m ? 'selected' : '' }}>
                        {{ date('F', mktime(0, 0, 0, $m, 1)) }}
                    </option>
                    @endfor
                </select>
            </div>

            <!-- Submit Button -->
            <div class="col-md-4">
                <label class="form-label">&nbsp;</label>
                <button type="submit" class="btn btn-primary form-control">Filter</button>
            </div>
        </div>
    </form>
</div>

<div class="card">
    <div class="card-body">
        <div class="table-responsive">
            <table id="transportLogsTable" class="table table-bordered display" width="100%" cellspacing="0">
                <thead>
                    <tr>
                        <th>Supplier Name</th>
                        <th>Total Kilometers (km)</th>
                        <th>Total Rounds</th>
                        <th>Carbon Emission</th>
                        <th>Month</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($logs as $log)
                    <tr>
                        <td>{{ $log->supplier_name }}</td>
                        <td>{{ number_format($log->total_km, 2) }}</td>
                        <td>{{ $log->total_rounds }}</td>
                        <td>{{ number_format($log->carbon_emission, 2) }}</td>
                        <td>{{ $log->month }}</td>
                        <td>
                            <button class="btn btn-sm btn-warning edit-btn" 
                                    data-id="{{ $log->id }}" 
                                    data-supplier="{{ $log->supplier_id }}" 
                                    data-date="{{ $log->date }}" 
                                    data-km="{{ $log->total_km }}" 
                                    data-rounds="{{ $log->total_rounds }}" 
                                    data-bs-toggle="modal" 
                                    data-bs-target="#transportLogModal">
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

<!-- Transport Log Modal -->
<div class="modal fade" id="transportLogModal" tabindex="-1" aria-labelledby="transportLogModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="transportLogModalLabel">Add/Edit Transport Log</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="transportLogForm" method="POST" action="{{ url('admin.transportlogs.save') }}">
                @csrf
                <div class="modal-body">
                    <input type="hidden" name="log_id" id="log_id">

                    <!-- Select Supplier -->
                    <div class="mb-3">
                        <label for="supplier_id" class="form-label">Select Supplier</label>
                        <select name="supplier_id" id="supplier_id" class="form-control selectpicker" data-live-search="true" required>
                            <option value="">Select Supplier</option>
                            @foreach($suppliers as $supplier)
                            <option value="{{ $supplier->id }}">{{ $supplier->firstname." ".$supplier->lastname }}</option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Date Field -->
                    <div class="mb-3">
                        <label for="date" class="form-label">Date</label>
                        <input type="date" name="date" id="date" class="form-control" required>
                    </div>

                    <!-- Total Kilometers -->
                    <div class="mb-3">
                        <label for="total_km" class="form-label">Total Kilometers</label>
                        <input type="number" step="0.01" name="total_km" id="total_km" class="form-control" required>
                    </div>

                    <!-- Total Rounds -->
                    <div class="mb-3">
                        <label for="total_rounds" class="form-label">Total Rounds</label>
                        <input type="number" name="total_rounds" id="total_rounds" class="form-control" required>
                    </div>
                    <div class="mb-3">
    <label for="vehicle_type" class="form-label">Vehicle Type</label>
    <select name="vehicle_type" id="vehicle_type" class="form-control selectpicker" data-live-search="true" required>
        <option value="">Select Vehicle Type</option>
        <option value="Petrol">Petrol</option>
        <option value="Diesel">Diesel</option>
        <option value="EV">EV</option>
        
    </select>
</div>
                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn btn-success">Save</button>
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </form>
        </div>
    </div>
</div>

@endsection

@section('footer')

<script>
$(document).ready(function() {
    $('.selectpicker').selectpicker(); // Initialize selectpicker

    var table = $('#transportLogsTable').DataTable({
        "paging": true,
        "ordering": true,
        "searching": true,
        "info": true,
        "autoWidth": false,
        "pageLength": 10
    });

    // Handle edit button click
    $('.edit-btn').on('click', function() {
        $('#log_id').val($(this).data('id'));
        $('#supplier_id').val($(this).data('supplier')).change();
        $('#date').val($(this).data('date'));
        $('#total_km').val($(this).data('km'));
        $('#total_rounds').val($(this).data('rounds'));
        $('.selectpicker').selectpicker('refresh');
    });
});
</script>
@endsection
