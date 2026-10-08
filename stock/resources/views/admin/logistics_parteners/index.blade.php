@extends('layouts.app')

@section('content')
<div class="row mx-3 my-2">
      <h2>Country</h2>
      <div class="col">
        <button class="btn btn-primary float-end"  id="addPartnerBtn">Create New</button>
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
                        <th>Logistic Patener Name</th>
                         <th>Carbon Emission Factor</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                @foreach ($logisticsPartners as $partner)
             
                    <tr>
                        <td>{{ $partner->country->name }}</td>
                        <td>{{ $partner->name}}</td>
                         <td>{{$partner->carbon_emission_value}}</td>
                        <td>
                            <!-- Edit Button -->
                            <button class="btn btn-secondary editPartnerBtn" data-id="{{ $partner->id }}" data-name="{{ $partner->name }}" data-country-id="{{ $partner->country_id }}" data-emission="{{ $partner->carbon_emission_value }}">Edit</button>
                            
                            <!-- Delete Button -->
                            <form action="{{ route('logistics_partners.update', $partner->id) }}" method="POST" style="display:inline;">
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
    <div class="modal fade" id="partnerModal" tabindex="-1" role="dialog" aria-labelledby="partnerModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <form id="partnerForm" method="POST">
                    @csrf
                    <div id="methodField"></div> <!-- Method field for PUT requests -->

                    <div class="modal-header">
                        <h5 class="modal-title" id="partnerModalLabel">Add Logistics Partner</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" name="id" id="partnerId">

                        <div class="form-group">
                            <label for="name">Name:</label>
                            <input type="text" class="form-control" name="name" id="name" required>
                        </div>

                        <div class="form-group">
                            <label for="country_id">Country:</label>
                            <select name="country_id" id="country_id" class="form-control" required>
                                @foreach($countries as $country)
                                    <option value="{{ $country->id }}">{{ $country->name }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="carbon_emission_value">Carbon Emission Value:</label>
                            <input type="number" step="0.01" class="form-control" name="carbon_emission_value" id="carbon_emission_value" required>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-primary">Save changes</button>
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
    $('#emissionsTable').DataTable();
    // Open Add Partner Modal
    $('#addPartnerBtn').on('click', function() {
        $('#partnerForm').trigger('reset');
        $('#partnerModalLabel').text('Add Logistics Partner');
        $('#methodField').html('');  // Remove method field for POST
        $('#partnerForm').attr('action', '{{ route('logistics_partners.store') }}');  // Set form action to store
        $('#partnerModal').modal('show');
    });

    // Open Edit Partner Modal
    $('.editPartnerBtn').on('click', function() {
        const id = $(this).data('id');
        const name = $(this).data('name');
        const country_id = $(this).data('country-id');
        const emission = $(this).data('emission');

        $('#partnerModalLabel').text('Edit Logistics Partner');
        $('#name').val(name);
        $('#country_id').val(country_id);
        $('#carbon_emission_value').val(emission);
        $('#methodField').html('@method("PUT")');  // Add PUT method field
           
        $('#partnerForm').attr('action', '{{ route("logistics_partners.update", ":id") }}'.replace(':id', id));
         // Set form action to update
        $('#partnerModal').modal('show');
    });
});
   
</script>
@endsection
