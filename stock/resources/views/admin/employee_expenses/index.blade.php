@extends('layouts.app')

@section('content')
<div class="row mx-3 my-2">
    <h2>Employee Travel Records</h2>
    <div class="col">
        <button class="btn btn-primary float-end" data-bs-toggle="modal" data-bs-target="#addExpenseModal">Create New Travel Record</button>
    </div>
</div>
<div class="row mx-3 my-2">
@if($errors->any())
    <div class="alert alert-danger">
        <ul>
            @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif
    <div class="col-md-12">
        <form action="{{ route('employee_expenses.import') }}" method="POST" enctype="multipart/form-data">
            @csrf
            <div class="form-group">
                <label for="file">Import Employee Travel Records (Excel):</label>
                <input type="file" name="file" class="form-control" required>
            </div>
            <button type="submit" class="btn btn-success">Upload</button>
        </form>
    </div>
</div>

<div class="card">
    <div class="card-body">
    <form method="GET" action="{{ route('employee_expenses.index') }}">
      <div class="row"  >
        <div class="col-md-5">
            <!-- Filter by Month -->
            <div class="form-group">
                <label for="month">Filter by Month</label>
                <input type="month" name="month" class="form-control" value="{{ request('month') }}">
            </div>
        </div>
        <div class="col-md-5">
            <!-- Filter by Employee -->
            <div class="form-group">
                <label for="employeedetail_id">Filter by Employee Name</label>
                <select name="employeedetail_id" class="form-control">
                    <option value="">-- Select Employee --</option>
                    @foreach($employees as $employee)
                        <option value="{{ $employee->id }}" {{ request('employeedetail_id') == $employee->id ? 'selected' : '' }}>
                            {{ $employee->name }}
                        </option>
                    @endforeach
                </select>
            </div>
        </div>
        <div class="col-md-2">
        <div class="form-group">
        <label for="month"></label>

            <button type="submit" class="btn btn-primary form-control">Filter</button>
        </div>
        </div>
      </div>
        
    </form>
</div>
</div>
<div class="card">
    <div class="card-body">
        <div class="table-responsive">
            <!-- Table displaying Employee Travel Records -->
            <table id="expensesTable" class="table table-bordered display" width="100%" cellspacing="0">
                <thead>
                    <tr>
                        <th>Employee Name</th>
                        <th>Total Days Present</th>
                        <th>Total KMs (Home-Office-Home)</th>
                        <th>Month</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($expenses as $expense)
                    <tr>
                        <td>{{ $expense->employeedetail->name }}</td>
                        <td>{{ $expense->days_present }}</td>
                        <td>{{ $expense->total_kms }} km</td>
                        <td>{{ date('F Y', strtotime($expense->month)) }}</td>
                        <td>
                            <button class="btn btn-sm btn-warning edit-btn" 
                                    data-id="{{ $expense->id }}" 
                                    data-employee_id="{{ $expense->employeedetail_id }}" 
                                    data-days_present="{{ $expense->days_present }}" 
                                    data-month="{{ date('Y-m', strtotime($expense->month)) }}">
                                    Edit
                            </button>
                            <form action="{{ route('employee_expenses.destroy', $expense->id) }}" method="POST" style="display:inline;">
                                @csrf
                                @method('DELETE')
                                <button class="btn btn-sm btn-danger" type="submit">Delete</button>
                            </form>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Add Expense Modal -->
<div class="modal fade" id="addExpenseModal" tabindex="-1" aria-labelledby="addExpenseModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="{{ route('employee_expenses.store') }}">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title" id="addExpenseModalLabel">Add Employee Travel Record</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="form-group">
                        <label for="employeedetail_id">Select Employee</label>
                        <select name="employeedetail_id" id="employeeSelect" class="form-control" required>
                            <option value="">-- Select Employee --</option>
                            @foreach($employees as $employee)
                                <option value="{{ $employee->id }}" data-distance="{{ $employee->distance_from_office }}">
                                    {{ $employee->name }} ({{ $employee->distance_from_office }} km)
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="days_present">Days Present</label>
                        <input type="number" name="days_present" id="daysPresent" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label for="total_kms">Total KMs</label>
                        <input type="text" name="total_kms" id="totalKms" class="form-control" readonly>
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

<!-- Edit Expense Modal -->
<div class="modal fade" id="editExpenseModal" tabindex="-1" aria-labelledby="editExpenseModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" id="editForm">
                @csrf
                @method('PUT')
                <div class="modal-header">
                    <h5 class="modal-title" id="editExpenseModalLabel">Edit Employee Travel Record</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="form-group">
                        <label for="edit_employeedetail_id">Select Employee</label>
                        <select name="employeedetail_id" id="editEmployeeSelect" class="form-control" required>
                            @foreach($employees as $employee)
                                <option value="{{ $employee->id }}" data-distance="{{ $employee->distance_from_office }}">
                                    {{ $employee->name }} ({{ $employee->distance_from_office }} km)
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="edit_days_present">Days Present</label>
                        <input type="number" name="days_present" id="editDaysPresent" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label for="edit_total_kms">Total KMs</label>
                        <input type="text" name="total_kms" id="editTotalKms" class="form-control" readonly>
                    </div>
                    <div class="form-group">
                        <label for="edit_month">Month</label>
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
@endsection

@section('footer')
<script>
    $(document).ready(function() {
        // Initialize DataTable
        $('#expensesTable').DataTable();

        // Calculate total KMs when selecting employee or days present
        $('#employeeSelect, #daysPresent').on('change', function() {
            let distance = $('#employeeSelect option:selected').data('distance');
            let daysPresent = $('#daysPresent').val();
            if (distance && daysPresent) {
                $('#totalKms').val(2 * distance * daysPresent);
            } else {
                $('#totalKms').val('');
            }
        });

        // Trigger Edit Modal
        $('.edit-btn').on('click', function() {
            let id = $(this).data('id');
            let employeeId = $(this).data('employee_id');
            let daysPresent = $(this).data('days_present');
            let month = $(this).data('month');

            // Set the values in the modal
            $('#editEmployeeSelect').val(employeeId).change();
            $('#editDaysPresent').val(daysPresent);
            $('#editMonth').val(month);

            let distance = $('#editEmployeeSelect option:selected').data('distance');
            $('#editTotalKms').val(2 * distance * daysPresent);

            // Update the form action dynamically
            $('#editForm').attr('action', '{{ route("employee_expenses.update", ":id") }}'.replace(':id', id));

           

            // Show the edit modal
            $('#editExpenseModal').modal('show');
        });

        // Calculate total KMs in edit form
        $('#editEmployeeSelect, #editDaysPresent').on('change', function() {
            let distance = $('#editEmployeeSelect option:selected').data('distance');
            let daysPresent = $('#editDaysPresent').val();
            if (distance && daysPresent) {
                $('#editTotalKms').val(2 * distance * daysPresent);
            } else {
                $('#editTotalKms').val('');
            }
        });
    });
</script>
@endsection
