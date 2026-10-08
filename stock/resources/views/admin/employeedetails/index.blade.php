@extends('layouts.app')

@section('content')
<div class="row mx-3 my-2">
      <h2>Employeedetails</h2>
      <div class="col">
        <button class="btn btn-primary float-end" data-bs-toggle="modal" data-bs-target="#addModal">Create New</button>
      </div>
</div>
<div class="row mx-3 my-2">
    <!-- Filter form -->
    <form method="GET" action="{{ route('employeedetails.index') }}">
        <div class="col">
            <div class="form-group">
                <label for="month">Filter by Vehicle Type</label>
                <input type="text" name="vehicle_type" class="form-control" value="{{ request('vehicle_type') }}">
            </div>
        </div>
        <div class="col">
            <div class="form-group">
                <button type="submit" class="btn btn-primary">Filter</button>
            </div>
        </div>   
    </form>      
</div>

<div class="card">
    <div class="card-body">
        <div class="table-responsive">
            <!-- Table displaying Employeedetails -->
            <table id="employeedetailsTable" class="table table-bordered display" width="100%" cellspacing="0">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Vehicle Type</th>
                        <th>Fuel Type</th>
                        <th>Distance From Office</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($employeedetails as $employeedetail)
                    <tr>
                        <td>{{ $employeedetail->name }}</td>
                        <td>{{ $employeedetail->vehicle_type }}</td>
                        <td>{{ $employeedetail->fuel_type }}</td>
                        <td>{{ $employeedetail->distance_from_office }} km</td>
                        <td>
                            <!-- Edit Button -->
                            <button class="btn btn-sm btn-warning edit-btn" 
                                    data-id="{{ $employeedetail->id }}" 
                                    data-name="{{ $employeedetail->name }}" 
                                    data-vehicle="{{ $employeedetail->vehicle_type }}" 
                                    data-fuel="{{ $employeedetail->fuel_type }}"
                                    data-distance="{{ $employeedetail->distance_from_office }}">
                                    Edit
                            </button>
                            <!-- Delete Button -->
                            <form action="{{ route('employeedetails.destroy', $employeedetail->id) }}" method="POST" style="display:inline;">
                                @csrf
                                @method('DELETE')
                                <button class="btn btn-sm btn-danger" type="submit">Delete</button>
                            </form>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>

            <!-- Add Modal -->
            <div class="modal fade" id="addModal" tabindex="-1" aria-labelledby="addModalLabel" aria-hidden="true">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <form method="POST" action="{{ route('employeedetails.store') }}">
                            @csrf
                            <div class="modal-header">
                                <h5 class="modal-title" id="addModalLabel">Add Employeedetail</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <div class="modal-body">
                                <div class="form-group">
                                    <label for="name">Name</label>
                                    <input type="text" name="name" class="form-control" required>
                                </div>
                                <div class="form-group">
                                    <label for="vehicle_type">Vehicle Type</label>
                                    <input type="text" name="vehicle_type" class="form-control" required>
                                </div>
                                <div class="form-group">
                                    <label for="fuel_type">Fuel Type</label>
                                    <input type="text" name="fuel_type" class="form-control" required>
                                </div>
                                <div class="form-group">
                                    <label for="distance_from_office">Distance From Office (km)</label>
                                    <input type="number" name="distance_from_office" class="form-control" required>
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                <button type="submit" class="btn btn-primary">Save</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Edit Modal -->
            <div class="modal fade" id="editModal" tabindex="-1" aria-labelledby="editModalLabel" aria-hidden="true">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <form method="POST" id="editForm">
                            @csrf
                            @method('PUT')
                            <div class="modal-header">
                                <h5 class="modal-title" id="editModalLabel">Edit Employeedetail</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <div class="modal-body">
                                <div class="form-group">
                                    <label for="name">Name</label>
                                    <input type="text" name="name" id="editName" class="form-control" required>
                                </div>
                                <div class="form-group">
                                    <label for="vehicle_type">Vehicle Type</label>
                                    <input type="text" name="vehicle_type" id="editVehicleType" class="form-control" required>
                                </div>
                                <div class="form-group">
                                    <label for="fuel_type">Fuel Type</label>
                                    <input type="text" name="fuel_type" id="editFuelType" class="form-control" required>
                                </div>
                                <div class="form-group">
                                    <label for="distance_from_office">Distance From Office (km)</label>
                                    <input type="number" name="distance_from_office" id="editDistanceFromOffice" class="form-control" required>
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                <button type="submit" class="btn btn-primary">Update</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>
@endsection

@section('footer')
<script>
    $(document).ready(function() {
        // Initialize DataTable
        $('#employeedetailsTable').DataTable();

        // Trigger Edit Modal
        $('.edit-btn').on('click', function() {
            const id = $(this).data('id');
            const name = $(this).data('name');
            const vehicle_type = $(this).data('vehicle');
            const fuel_type = $(this).data('fuel');
            const distance = $(this).data('distance');

            // Set the values in the modal
            $('#editName').val(name);
            $('#editVehicleType').val(vehicle_type);
            $('#editFuelType').val(fuel_type);
            $('#editDistanceFromOffice').val(distance);

            // Update the form action dynamically
            $('#editForm').attr('action', '{{ route("employeedetails.update", ":id") }}'.replace(':id', id));

            // Show the edit modal
            $('#editModal').modal('show');
        });
    });
</script>
@endsection
