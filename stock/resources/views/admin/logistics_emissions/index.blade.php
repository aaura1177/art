@extends('layouts.app')

@section('content')
<div class="row mx-3 my-2">
    <h2>Logistics Emission Records</h2>
    <div class="col">
        <!-- <button class="btn btn-primary float-end" data-bs-toggle="modal" data-bs-target="#addEmissionModal">Create New</button> -->
    </div>
</div>
<div class="card">
    <div class="card-body">
    <form method="GET" action="{{ route('logistics_emission.index') }}">
      <div class="row">
        <div class="col-md-5">
            <!-- Filter by Month -->
            <div class="form-group">
                <label for="month">Filter by Month</label>
                <input type="month" name="month" class="form-control" value="{{ request('month') }}">


            </div>
        </div>
        <div class="col-md-5">
            <!-- Filter by Logistics Partner -->
            <div class="form-group">
                <label for="logistics_partner_id">Filter by Logistics Partner</label>
                <select name="logistics_partner_id" class="form-control">
                    <option value="">-- Select Partner --</option>
                    @foreach($logisticsPartners as $partner)
                        <option value="{{ $partner->id }}" {{ request('logistics_partner_id') == $partner->id ? 'selected' : '' }}>
                            {{ $partner->name }}
                        </option>
                    @endforeach
                </select>
            </div>
        </div>
        <div class="col-md-2">
            <div class="form-group">
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
            <!-- Table displaying Carbon Emission Records -->
            <table id="emissionTable" class="table table-bordered display" width="100%" cellspacing="0">
                <thead>
                    <tr>
                        <th>Logistics Partner</th>
                        <th>Number of Containers</th>
                        
                        <th>Calculated Emission (kg)</th>
                        <th>Month</th>
                       
                    </tr>
                </thead>
                <tbody>
                    
                @foreach($groupedRecords as $record)
                <tr>
                    <td>{{ $record['logisticPartner'] }}</td>
                    <td>{{ $record['quantity'] }}</td>
                    <td>{{ number_format($record['carbon_emission'], 2) *$record['quantity'] }} kg</td>
                    <td>{{ $record['month'] }}</td>
                </tr>
            @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>


@endsection

@section('footer')
<script>
    $(document).ready(function() {
        // Initialize DataTable
        $('#emissionTable').DataTable({
        "ordering": true, // This enables ordering globally
        "order": [], // This disables initial sorting on any column
        "columnDefs": [
            { "orderable": false, "targets": 0 } // Disable sorting on the first column (index 0)
        ]
    });
        // Calculate emission based on number of containers and carbon emission factor
        $('#logisticsPartnerSelect, #numberOfContainers').on('change', function() {
            let carbonEmissionFactor = $('#logisticsPartnerSelect option:selected').data('carbon_emission');
            let numberOfContainers = $('#numberOfContainers').val();
            if (carbonEmissionFactor && numberOfContainers) {
                $('#calculatedEmission').val(carbonEmissionFactor * numberOfContainers);
            } else {
                $('#calculatedEmission').val('');
            }
        });

        // Trigger Edit Modal
        $('.edit-btn').on('click', function() {
            let id = $(this).data('id');
            let logisticsPartnerId = $(this).data('logistics_partner_id');
            let numberOfContainers = $(this).data('number_of_containers');
            let month = $(this).data('month');

            // Set the values in the modal
            $('#editLogisticsPartnerSelect').val(logisticsPartnerId).change();
            $('#editNumberOfContainers').val(numberOfContainers);
            $('#editMonth').val(month);

            let carbonEmissionFactor = $('#editLogisticsPartnerSelect option:selected').data('carbon_emission');
            $('#editCalculatedEmission').val(carbonEmissionFactor * numberOfContainers);

            // Update the form action dynamically
            $('#editForm').attr('action', '{{ route("logistics_emission.update", ":id") }}'.replace(':id', id));

            // Show the edit modal
            $('#editEmissionModal').modal('show');
        });

        // Calculate emission in edit form
        $('#editLogisticsPartnerSelect, #editNumberOfContainers').on('change', function() {
            let carbonEmissionFactor = $('#editLogisticsPartnerSelect option:selected').data('carbon_emission');
            let numberOfContainers = $('#editNumberOfContainers').val();
            if (carbonEmissionFactor && numberOfContainers) {
                $('#editCalculatedEmission').val(carbonEmissionFactor * numberOfContainers);
            } else {
                $('#editCalculatedEmission').val('');
            }
        });
    });
</script>
@endsection
