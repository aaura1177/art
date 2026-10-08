@extends('layouts.app')

@section('content')
<div class="row mx-3 my-2">
      <h2>Country</h2>
      <div class="col">
        <button class="btn btn-primary float-end"  data-bs-toggle="modal" data-bs-target="#addModal">Create New</button>
      </div>
</div>

<div class="card">
      <div class="card-body">
        <div class="table-responsive">
   

    <!-- Table displaying carbon emissions -->
            <table id="emissionsTable" class="table table-bordered display" width="100%" cellspacing="0">
                <thead>
                    <tr>
                        <th>Country</th>
                        <th>Country Code</th>
                      
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($countries as $country)
                    <tr>
                        <td>{{ $country->name }}</td>
                        <td>{{ $country->code }}</td>
                      
                        <td>
                            <!-- Edit Button -->
                            <button class="btn btn-sm btn-warning edit-btn" 
                                    data-id="{{ $country->id }}" 
                                    data-name="{{ $country->name  }}"
                                    data-code="{{ $country->code  }}"  
                                    >
                                    Edit
                            </button>
                            <!-- Delete Button -->
                            <form action="{{ route('artisan_emissions.destroy', $country->id) }}" method="POST" style="display:inline;">
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
                        <form method="POST" action="{{ route('countries.store') }}">
                            @csrf
                            <div class="modal-header">
                                <h5 class="modal-title" id="addModalLabel">Add Country Record</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <div class="modal-body">
                                <div class="form-group">
                                    <label for="electricity">Country Name</label>
                                    <input type="text" name="name" class="form-control" required>
                                </div>
                                <div class="form-group">
                                    <label for="month">Country Code</label>
                                    <input type="text" name="code" class="form-control" required>
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
                                <h5 class="modal-title" id="editModalLabel">Edit Country Record</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <div class="modal-body">
                                <div class="form-group">
                                    <label for="electricity">Country Name</label>
                                    <input type="text" name="name" id="editName" class="form-control" required>
                                </div>
                                <div class="form-group">
                                    <label for="month">Country Code</label>
                                    <input type="text" name="code" id="editCode" class="form-control" required>
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
            const name = $(this).data('name');
            const code = $(this).data('code');

            // Set the values in the modal
            $('#editName').val(name);
            $('#editCode').val(code);

            // Update the form action dynamically
            
            $('#editForm').attr('action', '{{ route("countries.update", ":id") }}'.replace(':id', id));

            // Show the edit modal
            $('#editModal').modal('show');
        });
    });
</script>
@endsection
