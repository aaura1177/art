@extends('layouts.app')

@section('content')
<div class="row mx-3 my-2">
    <h2>Travel Expenses</h2>
    <div class="col">
        <button class="btn btn-primary float-end" data-bs-toggle="modal" data-bs-target="#addModal">Create New</button>
    </div>
</div>

<div class="card">
    <div class="card-body">
        <div class="table-responsive">
            <!-- Travel Expenses Table -->
            <table id="expensesTable" class="table table-bordered display" width="100%" cellspacing="0">
                <thead>
                    <tr>
                        <th>Departure</th>
                        <th>Destination</th>
                        <th>Date</th>
                        <th>Vehicle Type</th>
                        <th>Distance</th>
                        <th>Amount</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($expenses as $expense)
                    <tr>
                        <td>{{ $expense->departure }}</td>
                        <td>{{ $expense->destination }}</td>
                        <td>{{ $expense->date }}</td>
                        <td>{{ $expense->vehicle_type }}</td>
                        <td>{{ $expense->distance }}</td>
                        <td>{{ $expense->amount }}</td>
                        <td>
                            <!-- Edit Button -->
                            <button class="btn btn-sm btn-warning edit-btn" 
                                    data-id="{{ $expense->id }}" 
                                    data-departure="{{ $expense->departure }}" 
                                    data-destination="{{ $expense->destination }}"
                                    data-date="{{ date('Y-m-d', strtotime($expense->date)) }}"
                                    data-vehicle_type="{{ $expense->vehicle_type }}"
                                    data-distance="{{ $expense->distance }}">
                                    Edit
                            </button>
                            <!-- Delete Button -->
                            <form action="{{ route('expenses.destroy', $expense->id) }}" method="POST" style="display:inline;">
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
                        <form method="POST" action="{{ route('expenses.store') }}">
                            @csrf
                            <div class="modal-header">
                                <h5 class="modal-title" id="addModalLabel">Add Travel Expense</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <div class="modal-body">
                                <div class="form-group">
                                    <label for="departure">Departure</label>
                                    <input type="text" name="departure" class="form-control" required>
                                </div>
                                <div class="form-group">
                                    <label for="destination">Destination</label>
                                    <input type="text" name="destination" class="form-control" required>
                                </div>
                                <div class="form-group">
                                    <label for="date">Date</label>
                                    <input type="date" name="date" class="form-control" required>
                                </div>
                                <div class="form-group">
                                    <label for="vehicle_type">Vehicle Type</label>
                                    <select class="form-control" name="vehicle_type">
                                        <option value="Petrol">Petrol</option>
                                        <option value="Diesel">Diesel</option>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label for="distance">Distance</label>
                                    <input type="number" name="distance" class="form-control" required>
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
                                <h5 class="modal-title" id="editModalLabel">Edit Travel Expense</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <div class="modal-body">
                                <div class="form-group">
                                    <label for="departure">Departure</label>
                                    <input type="text" name="departure" id="editDeparture" class="form-control" required>
                                </div>
                                <div class="form-group">
                                    <label for="destination">Destination</label>
                                    <input type="text" name="destination" id="editDestination" class="form-control" required>
                                </div>
                                <div class="form-group">
                                    <label for="date">Date</label>
                                    <input type="date" name="date" id="editDate" class="form-control" required>
                                </div>
                                <div class="form-group">
                                    <label for="vehicle_type">Vehicle Type</label>
                                    <select class="form-control" name="vehicle_type" id="editVehicleType">
                                        <option value="Petrol">Petrol</option>
                                        <option value="Diesel">Diesel</option>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label for="distance">Distance</label>
                                    <input type="number" name="distance" id="editDistance" class="form-control" required>
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
<!-- DataTables and Buttons extension CSS -->

<!-- DataTables and Buttons extension JS -->

<script>
    $(document).ready(function() {
        // Initialize DataTable
        $('#expensesTable').DataTable({
            dom: 'Bfrtip',
            buttons: [
                {
                    extend: 'csvHtml5',
                    text: '<i class="fa fa-download"></i> Download CSV', // Custom text with icon
                    titleAttr: 'CSV',
                    className: 'btn btn-warning', // Apply your custom classes here
                    exportOptions: {
                        columns: [0, 1, 2, 3, 4,5] // Specify which columns to export
                    }
                }
            ]
        });


        // Trigger Edit Modal
        $('.edit-btn').on('click', function() {
            const id = $(this).data('id');
            const departure = $(this).data('departure');
            const destination = $(this).data('destination');
            const date = $(this).data('date');
            const vehicle_type = $(this).data('vehicle_type');
            const distance = $(this).data('distance');

            // Set the values in the modal
            $('#editDeparture').val(departure);
            $('#editDestination').val(destination);
            $('#editDate').val(date);
            $('#editVehicleType').val(vehicle_type);
            $('#editDistance').val(distance);

            // Update the form action dynamically
            $('#editForm').attr('action', '{{ route("expenses.update", ":id") }}'.replace(':id', id));

            // Show the edit modal
            $('#editModal').modal('show');
        });
    });
</script>
@endsection
