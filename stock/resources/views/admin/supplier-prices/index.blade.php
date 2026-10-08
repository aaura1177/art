@extends('layouts.app')

@section('content')
<div class="row mx-3 my-2">
    <h2>Assigned Products</h2>
    <div class="col">
        <a class="btn btn-success float-end" style="color: #fff; margin-left: 5px;" data-bs-toggle="modal" data-bs-target="#mydateModal" href="{{ url('/supplier-dashboard/exportcsv') }}">Download CSV</a>
    </div>
</div>
<div class="card mb-3">
    <div class="card-body">
        <div class="table-responsive">
            <table id="supplierProductsTable" class="table table-bordered">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Product Name</th>
                        <th>Supplier Name</th>
                        <th>Product Price</th>
                        <th>Effective Price</th>
                        <th>Effective Date</th>
                        <th>Status</th>
                        <!-- Add other columns if needed -->
                    </tr>
                </thead>
                <tbody>
                    @foreach($products_id as $key => $supplierProduct)
                        <tr data-product-id="{{ $supplierProduct->id }}" data-supplier-id="{{ $supplierProduct->supplier->id }}" >
                            <td>{{ $key + 1 }}</td>
                            <td>{{ $supplierProduct->product->name }}</td>
                            <td>{{ $supplierProduct->supplier->name }}</td>
                            <td>{{ $supplierProduct->rate }}</td>
                            <td>{{ $supplierProduct->pending_rate ?? 'No Pending rate' }}</td>
                            <td>{{ $supplierProduct->effective_date ?? 'No Pendig Date'}}</td>

                            <td>
                                @if($supplierProduct->admin_approved==1)
                                   <button class="btn btn-success">Approved</button>
                               
                                
                                @else
                                <button class="btn btn-warning">Pending Approval</button>
                                @endif   
                            </td>
                            <!-- Add other data if needed -->
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal for Revising Price -->

@endsection

<!-- DataTables Initialization Script -->
@section('footer')
<script>
    $(document).ready(function() {
        $('#supplierProductsTable').DataTable();

        // Handle click on "Revise Price" button
        $('.btn-warning').on('click', function() {
            const button = $(this);
            const productId = button.closest('tr').data('product-id');
            const supplier_id = button.closest('tr').data('supplier-id');
            // Assuming each row has data-product-id attribute
            
            if (confirm('Are you sure you want to approve this price?')) {
                $.ajax({
                    url: "{{ route('supplier.approveProduct') }}",
                    type: 'POST',
                    data: {
                        _token: '{{ csrf_token() }}',
                        product_id: productId,
                        supplier_id:supplier_id,
                    },
                    success: function(response) {
                        if (response.status === 'success') {
                            button.removeClass('btn-warning').addClass('btn-success').text('Approved');
                        } else {
                            alert(response.message);
                        }
                    },
                    error: function() {
                        alert('An error occurred. Please try again.');
                    }
                });
            }
        });
    });
</script>
@endsection
