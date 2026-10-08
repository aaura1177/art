@extends('layouts.app')

@section('content')
<div class="row mx-3 my-2">
    <h2>Production Consumption (Electricity & Fuel)</h2>
    <div class="col">
        <button class="btn btn-primary float-end" data-bs-toggle="modal" data-bs-target="#createLogModal">Create New</button>
    </div>  
</div>

<div class="card">
    <div class="card-body">
        <div class="table-responsive">
            <table id="invoiceTable" class="table table-bordered display" width="100%" cellspacing="0">
                <thead>
                    <tr>
                        <th>Electricity Consumed</th>
                        <th>Percentage</th>
                        <th>Distance</th>
                        <th>Month</th>
                        <th>Electricity Bill</th> <!-- Renamed -->
                        <th>Eway Bill</th> <!-- New column for Eway Bill -->
                        <th>Vehicle Type</th>
                        <th width="130px">Action</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($logs as $log)
                    <tr>
                        <td>{{ $log->electricity_unit }}Kwh</td>
                        <td>{{ $log->percentage }}%</td>
                        <td>{{ $log->distance }} kms</td>
                        <td>{{ $log->date }}</td>
                        <td>
                            @if($log->electricity_attachement)
                                <a href="{{ asset($log->electricity_attachement) }}" target="_blank">Download Electricity Bill</a>
                            @else
                                No Electricity Bill
                            @endif
                        </td>
                        <td>
                            @if($log->eway_attachement)
                                <a href="{{ asset($log->eway_attachement) }}" target="_blank">Download Eway Bill</a>
                            @else
                                No Eway Bill
                            @endif
                        </td>
                        <td>{{ $log->vehicle_type }}</td>
                        <td width="130px">
                            <button class="btn btn-info" data-bs-toggle="modal" data-bs-target="#editLogModal-{{ $log->id }}"><i class="fa fa-edit"></i></button>
                            <form action="{{ route('production_emissions.destroy', $log->id) }}" method="POST" style="display:inline;">
                                @csrf
                                @method('DELETE')
                                <button class="btn btn-danger" type="submit"><i class="fa fa-trash"></i></button>
                            </form>

                            <!-- Edit Modal -->
                            <div class="modal fade" id="editLogModal-{{ $log->id }}" tabindex="-1" aria-labelledby="editLogModalLabel-{{ $log->id }}" aria-hidden="true">
                                <div class="modal-dialog">
                                    <div class="modal-content">
                                        <div class="modal-header">
                                            <h5 class="modal-title" id="editLogModalLabel-{{ $log->id }}">Edit Production Consumption</h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                        </div>
                                        <div class="modal-body">
                                            <form action="{{ route('production_emissions.update', $log->id) }}" method="POST" enctype="multipart/form-data">
                                                @csrf
                                                @method('PUT')
                                                <div class="mb-3">
                                                    <label for="electricity_unit-{{ $log->id }}" class="form-label">Electricity Consumption (KWh)</label>
                                                    <input type="number" class="form-control" id="electricity_unit-{{ $log->id }}" name="electricity_unit" value="{{ $log->electricity_unit }}" required>
                                                </div>
                                                <div class="mb-3">
                                                    <label for="percentage-{{ $log->id }}" class="form-label">Percentage</label>
                                                    <input type="number" class="form-control" id="percentage-{{ $log->id }}" name="percentage" value="{{ $log->percentage }}" required>
                                                </div>
                                                <div class="mb-3">
                                                    <label for="date-{{ $log->id }}" class="form-label">Month</label>
                                                    <input type="month" class="form-control" id="date-{{ $log->id }}" name="date" value="{{ \Carbon\Carbon::createFromFormat('F-Y', $log->date)->format('Y-m') }}" required>
                                                </div>
                                                <div class="mb-3">
                                                    <label for="distance-{{ $log->id }}" class="form-label">Distance Travelled</label>
                                                    <input type="number" class="form-control" id="distance-{{ $log->id }}" name="distance" value="{{ $log->distance }}" required>
                                                </div>
                                                <div class="mb-3">
                                                    <label for="electricity_bill" class="form-label">Electricity Bill</label>
                                                    <input type="file" class="form-control" id="electricity_bill" name="electricity_bill">
                                                    @if($log->electricity_attachement)
                                                        <p>Current file: <a href="{{ asset($log->electricity_attachement) }}" target="_blank">Download Electricity Bill</a></p>
                                                    @endif
                                                </div>
                                                <div class="mb-3">
                                                    <label for="eway_bill" class="form-label">Eway Bill</label>
                                                    <input type="file" class="form-control" id="eway_bill" name="eway_bill">
                                                    @if($log->eway_bill)
                                                        <p>Current file: <a href="{{ asset($log->eway_attachement) }}" target="_blank">Download Eway Bill</a></p>
                                                    @endif
                                                </div>
                                                <button type="submit" class="btn btn-primary">Update</button>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>        
</div>

<!-- Create Modal -->
<div class="modal fade" id="createLogModal" tabindex="-1" aria-labelledby="createLogModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="createLogModalLabel">Create Production Consumption</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form action="{{ route('production_emissions.store') }}" method="POST" enctype="multipart/form-data">
                    @csrf
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
                    <button type="submit" class="btn btn-primary">Save</button>
                </form>
            </div>
        </div>
    </div>
</div>

@endsection

@section('footer')

<script>
    $(document).ready(function() {
        $('#invoiceTable').DataTable({
            dom: 'Bfrtip',
            "order": [[ 0, "desc" ]]
        });
    });
</script>
@endsection
