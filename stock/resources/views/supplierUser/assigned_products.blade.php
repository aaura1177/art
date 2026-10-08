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
                        <th>Product Price</th>
                        <th>Effective Price</th>
                        <th>Effective Date</th>
                        <th>Action</th>
                        <!-- Add other columns if needed -->
                    </tr>
                </thead>
                <tbody>
                    @foreach($products_id as $key => $supplierProduct)
                        <tr>
                            <td>{{ $key + 1 }}</td>
                            <td>{{ $supplierProduct->product->name }}</td>
                            <td>{{ $supplierProduct->rate }}</td>
                            <td>{{ $supplierProduct->pending_rate ?? 'No Pending rate' }}</td>
                            <td>{{ $supplierProduct->effective_date ?? 'No Pendig Date'}}</td>

                            <td>
                                <button class="btn btn-primary revise-price-btn" data-bs-toggle="modal" data-bs-target="#revisePriceModal" data-id="{{ $supplierProduct->id }}" data-name="{{ $supplierProduct->product->name }}" data-price="{{ $supplierProduct->rate }}">
                                    Revise Price
                                </button>
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
<div class="modal fade" id="revisePriceModal" tabindex="-1" role="dialog" aria-labelledby="revisePriceModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <form id="revisePriceForm" method="POST" action="{{ route('supplier.revise-price') }}">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title" id="revisePriceModalLabel">Revise Product Price</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="product_id" id="product_id">
                    <div class="form-group">
                        <label for="product_name">Product Name</label>
                        <input type="text" id="product_name" class="form-control" readonly>
                    </div>
                    <div class="form-group">
                        <label for="current_price">Current Price</label>
                        <input type="text" id="current_price" class="form-control" readonly>
                    </div>
                    <div class="form-group">
                        <label for="new_price">New Price</label>
                        <input type="number" id="new_price" name="new_price" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label for="effective_date">Effective Date</label>
                        <input type="date" id="effective_date" name="effective_date" class="form-control" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save Changes</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

<!-- DataTables Initialization Script -->
@section('footer')
<script>
    $(document).ready(function() {
        $('#supplierProductsTable').DataTable();

        // Handle click on "Revise Price" button
        $('.revise-price-btn').on('click', function() {
            const productId = $(this).data('id');
            const productName = $(this).data('name');
            const currentPrice = $(this).data('price');

            // Populate modal fields with data
            $('#product_id').val(productId);
            $('#product_name').val(productName);
            $('#current_price').val(currentPrice);
        });
    });
</script>
@endsection
