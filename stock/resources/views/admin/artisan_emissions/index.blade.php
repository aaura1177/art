@extends('layouts.app')

@section('content')
<div class="row mx-3 my-2">
      <h2>Artisan Electrcity Consumptions</h2>
      <div class="col">
        <button class="btn btn-primary float-end"  data-bs-toggle="modal" data-bs-target="#addModal">Create New</button>
      </div>
</div>
<div class="row mx-3 my-2">

   
  
<!-- Filter form for month -->
    <form method="GET" action="{{ route('artisan_emissions.index') }}">
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
   

    <!-- Table displaying carbon emissions -->
            <table id="emissionsTable" class="table table-bordered display" width="100%" cellspacing="0">
                <thead>
                    <tr>
                        <th>Total Electricity Consumed (kWh)</th>
                        <th>Factory Electricity Consumed (kWh)</th>
                        <th>Office Electricity Consumed (kWh)</th>
                        <th>Uk Office Electricity Consumed (kWh)</th>
                        <th>Carbon Emissions (kgCO2)</th>
                        <th>Month</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($emissions as $emission)
                    <tr>
                        <td>{{ $emission->electricity_consumed }}</td>
                        <td>{{ $emission->factory_electricity }}</td>
                        <td>{{ $emission->office_electricity }}</td>
                        <td>{{ $emission->uk_electricity }}</td>
                        <td>{{ $emission->carbon_emissions }}</td>
                        <td>{{ date('F Y', strtotime($emission->month)) }}</td>
                        <td>
                            <!-- Edit Button -->
                            <button class="btn btn-sm btn-warning edit-btn" 
                                    data-id="{{ $emission->id }}" 
                                    data-factory="{{ $emission->factory_electricity }}"
                                    data-office="{{ $emission->office_electricity }}" 
                                    data-uk="{{ $emission->uk_electricity }}"  
                                    data-month="{{ date('Y-m', strtotime($emission->month)) }}">
                                    Edit
                            </button>
                            <!-- Delete Button -->
                            <form action="{{ route('artisan_emissions.destroy', $emission->id) }}" method="POST" style="display:inline;">
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
                        <form method="POST" action="{{ route('artisan_emissions.store') }}">
                            @csrf
                            <div class="modal-header">
                                <h5 class="modal-title" id="addModalLabel">Add Emission Record</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <div class="modal-body">
                                <div class="form-group">
                                    <label for="electricity">Factory Consumption</label>
                                    <input type="number" name="factory_electricity" class="form-control" required>
                                </div>
                                <div class="form-group">
                                    <label for="electricity">Office Consumption</label>
                                    <input type="number" name="office_electricity" class="form-control" required>
                                </div>
                                <div class="form-group">
                                    <label for="electricity">Uk Consumption</label>
                                    <input type="number" name="uk_electricity" class="form-control" required>
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
                        <form method="POST" id="editForm" >
                            @csrf
                            @method('PUT')
                            <div class="modal-header">
                                <h5 class="modal-title" id="editModalLabel">Edit Emission Record</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <div class="modal-body">
                            <div class="form-group">
                                    <label for="electricity">Factory Consumption</label>
                                    <input type="number" name="factory_electricity" id="factory"class="form-control" required>
                                </div>
                                <div class="form-group">
                                    <label for="electricity">Office Consumption</label>
                                    <input type="number" name="office_electricity" id="office"class="form-control" required>
                                </div>
                                <div class="form-group">
                                    <label for="electricity">Uk Consumption</label>
                                    <input type="number" name="uk_electricity" id="uk" class="form-control" required>
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
            const factory = $(this).data('factory');
            const office = $(this).data('office');
            const uk = $(this).data('uk');
            const month = $(this).data('month');

            // Set the values in the modal
            $('#factory').val(factory);
            $('#office').val(office);
            $('#uk').val(uk);
            $('#editMonth').val(month);

            // Update the form action dynamically
            
            $('#editForm').attr('action', '{{ route("artisan_emissions.update", ":id") }}'.replace(':id', id));

            // Show the edit modal
            $('#editModal').modal('show');
        });
    });
</script>
@endsection
