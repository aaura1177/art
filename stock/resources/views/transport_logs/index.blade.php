@extends('layouts.app')

@section('content')
<div class="row mx-3 my-2">
      <h2>Transport Logs</h2>
      <div class="col">
        <button class="btn btn-primary float-end" href="{{ route('transport_logs.create') }}" data-bs-toggle="modal" data-bs-target="#createLogModal">Create New Log</button>
      </div>  
</div>
<div class="card">
      <div class="card-body">
        <div class="table-responsive">
        
                <table id="invoiceTable" lass="table table-bordered display" width="100%" cellspacing="0">
                    <thead>
                        <tr>
                            <th>Distance Travelled (km)</th>
                            <th>Times Transport Occurred</th>
                            <th>Transport Date</th>
                            <th>Vehicle Type</th>
                            <th width="130px">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                    @foreach($logs as $log)
                        <tr>
                            <td>{{ $log->distance_travelled }}</td>
                            <td>{{ $log->times_transport_occurred }}</td>
                            <td>{{ $log->transport_date }}</td>
                            <td>{{ $log->vehicle_type }}</td>
                            <td rowspan="1" colspan="1" width="130px">
                                <span class="tool-tip1" title="Edit">
                                    <button class="btn btn-info" data-bs-toggle="modal" data-bs-target="#editLogModal-{{ $log->id }}"><i class="fa fa-edit"></i></button>
                                </span>
                    
                                <span class="tool-tip1" title="Delete">
                                    <form action="{{ route('transport_logs.destroy', $log->id) }}" method="POST">
                                        @csrf
                                        @method('DELETE')
                                        <button class="btn btn-danger" type="submit"><i class="fa fa-trash"></i></button>
                                    </form>
                                </span>

                                <div class="modal fade" id="editLogModal-{{ $log->id }}" tabindex="-1" aria-labelledby="editLogModalLabel-{{ $log->id }}" aria-hidden="true">
                                    <div class="modal-dialog">
                                        <div class="modal-content">
                                            <div class="modal-header">
                                                <h5 class="modal-title" id="editLogModalLabel-{{ $log->id }}">Edit Transport Log</h5>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                            </div>
                                            <div class="modal-body">
                                                <!-- Edit form -->
                                                <form action="{{ route('transport_logs.update', $log->id) }}" method="POST">
                                                    @csrf
                                                    @method('PUT')

                                                    <div class="mb-3">
                                                        <label for="distance_travelled-{{ $log->id }}" class="form-label">Distance Travelled (km) To Factory</label>
                                                        <input type="text" class="form-control" id="distance_travelled-{{ $log->id }}" name="distance_travelled" value="{{ $log->distance_travelled }}" required>
                                                    </div>

                                                    <div class="mb-3">
                                                        <label for="times_transport_occurred-{{ $log->id }}" class="form-label">Times Transport Occurred</label>
                                                        <input type="number" class="form-control" id="times_transport_occurred-{{ $log->id }}" name="times_transport_occurred" value="{{ $log->times_transport_occurred }}" required>
                                                    </div>

                                                    <div class="mb-3">
                                                        <label for="transport_date-{{ $log->id }}" class="form-label">Transport Date</label>
                                                        <input type="date" class="form-control" id="transport_date-{{ $log->id }}" name="transport_date" value="{{ $log->transport_date }}" required>
                                                    </div>

                                                    <div class="mb-3">
                                                        <label for="vehicle_type-{{ $log->id }}" class="form-label">Vehicle Type</label>
                                                        <select class="form-control" id="vehicle_type-{{ $log->id }}" name="vehicle_type" required>
                                                            <option value="Diesel" {{ $log->vehicle_type == 'Diesel' ? 'selected' : '' }}>Diesel</option>
                                                            <option value="Hybrid" {{ $log->vehicle_type == 'Hybrid' ? 'selected' : '' }}>Hybrid</option>
                                                            <option value="Petrol" {{ $log->vehicle_type == 'Petrol' ? 'selected' : '' }}>Petrol</option>
                                                            <option value="Electric" {{ $log->vehicle_type == 'Electric' ? 'selected' : '' }}>Electric</option>
                                                        </select>
                                                    </div>

                                                    <button type="submit" class="btn btn-primary">Update Log</button>
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
                <h5 class="modal-title" id="createLogModalLabel">Create Transport Log</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form action="{{ route('transport_logs.store') }}" method="POST">
                    @csrf
                    <div class="mb-3">
                        <label for="distance_travelled" class="form-label">Distance Travelled (km) To Factory</label>
                        <input type="text" class="form-control" id="distance_travelled" name="distance_travelled" required>
                    </div>
                    <div class="mb-3">
                        <label for="times_transport_occurred" class="form-label">Times Transport Occurred</label>
                        <input type="number" class="form-control" id="times_transport_occurred" name="times_transport_occurred" required>
                    </div>
                    <div class="mb-3">
                        <label for="transport_date" class="form-label">Transport Date</label>
                        <input type="date" class="form-control" id="transport_date" name="transport_date" required>
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
                    <button type="submit" class="btn btn-primary">Save Log</button>
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
