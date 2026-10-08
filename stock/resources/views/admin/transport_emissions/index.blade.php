@extends('layouts.app')

@section('content')
<div class="row mx-3 my-2">
    <h2>Transport and Maritime Emissions</h2>
    <div class="col">
        <button class="btn btn-primary float-end" data-bs-toggle="modal" data-bs-target="#addModal">Create New</button>
    </div>
</div>

<div class="row mx-3 my-2">
    <!-- Filter form for month -->
    <form method="GET" action="{{ route('emissions.index') }}">
        <div class="col">
            <div class="form-group">
                <label for="month">Filter by Month</label>
                <input type="month" name="month" class="form-control" value="{{ request('month') }}">
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
            <!-- Table displaying emissions -->
            <table id="emissionsTable" class="table table-bordered display" width="100%" cellspacing="0">
                <thead>
                    <tr>
                        <th>Port</th>
                        <th>Distance (KM)</th>
                        <th>Total Emissions (kgCO2)</th>
                        <th>Month</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($emissions as $emission)
                    <tr>
                        <td>{{ $emission->route }}</td>
                        <td>{{ $emission->distance_km }}</td>
                        <td>{{ $emission->total_emissions }}</td>
                        <td>{{ date('F Y', strtotime($emission->month)) }}</td>
                        <td>
                            <!-- Edit Button -->
                            <button class="btn btn-sm btn-warning edit-btn" 
                                    data-id="{{ $emission->id }}" 
                                    data-route="{{ $emission->route }}" 
                                    data-distance="{{ $emission->distance_km }}" 
                                    data-month="{{ date('Y-m', strtotime($emission->month)) }}">
                                    Edit
                            </button>
                            <!-- Delete Button -->
                            <form action="{{ route('emissions.destroy', $emission->id) }}" method="POST" style="display:inline;">
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
                        <form method="POST" action="{{ route('emissions.store') }}">
                            @csrf
                            <div class="modal-header">
                                <h5 class="modal-title" id="addModalLabel">Add Emission Record</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <div class="modal-body">
                                <div class="form-group">
                                    <label for="route">Port</label>
                                    <input type="text" name="route" class="form-control" required>
                                </div>
                                <div class="form-group">
                                    <label for="distance_km">Distance (KM)</label>
                                    <input type="number" name="distance_km" class="form-control" required>
                                </div>
                                <div class="form-group">
                                    <label for="month">Month</label>
                                    <input type="month" name="month" class="form-control" required>
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
                                <h5 class="modal-title" id="editModalLabel">Edit Emission Record</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <div class="modal-body">
                                <div class="form-group">
                                    <label for="route">Route</label>
                                    <input type="text" name="route" id="editRoute" class="form-control" required>
                                </div>
                                <div class="form-group">
                                    <label for="distance_km">Distance (KM)</label>
                                    <input type="number" name="distance_km" id="editDistance" class="form-control" required>
                                </div>
                                <div class="form-group">
                                    <label for="month">Month</label>
                                    <input type="month" name="month" id="editMonth" class="form-control" required>
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
        $('#emissionsTable').DataTable();

        // Trigger Edit Modal
        $('.edit-btn').on('click', function() {
            const id = $(this).data('id');
            const route = $(this).data('route');
            const distance = $(this).data('distance');
            const month = $(this).data('month');

            // Set the values in the modal
            $('#editRoute').val(route);
            $('#editDistance').val(distance);
            $('#editMonth').val(month);

            // Update the form action dynamically
            $('#editForm').attr('action', '{{ route("emissions.update", ":id") }}'.replace(':id', id));

            // Show the edit modal
            $('#editModal').modal('show');
        });
    });
</script>
@endsection
