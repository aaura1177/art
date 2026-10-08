@extends('layouts.app')

@section('content')
<div class="row mx-3 my-2">
    <h2>Product-Supplier Assignment</h2>
    <div class="col">
        <button class="btn btn-primary float-end" data-bs-toggle="modal" data-bs-target="#addModal">Create New Product Assignment</button>
    </div>
</div>

<div class="card">
    <div class="card-body">
        <div class="table-responsive">
            <!-- Table displaying product-supplier ProductSuppliers -->
            <table id="ProductSuppliersTable" class="table table-bordered display" width="100%" cellspacing="0">
                <thead>
                    <tr>
                        <th>Product</th>
                        <th>Supplier</th>
                        <th>Price</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($ProductSuppliers as $assignment)
                    <tr>
                        <td>{{ $assignment->product->name }}</td>
                        <td>{{ $assignment->supplier->name }}</td>
                        <td>{{ $assignment->price }}</td>
                        <td>
                            <!-- Edit Button -->
                            <button class="btn btn-sm btn-warning edit-btn" 
                                    data-id="{{ $assignment->id }}" 
                                    data-product="{{ $assignment->product_id }}" 
                                    data-supplier="{{ $assignment->supplier_id }}" 
                                    data-price="{{ $assignment->price }}">
                                    Edit
                            </button>
                            <!-- Delete Button -->
                            <form action="{{ route('ProductSuppliers.destroy', $assignment->id) }}" method="POST" style="display:inline;">
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
                        <form method="POST" action="{{ route('ProductSuppliers.store') }}">
                            @csrf
                            <div class="modal-header">
                                <h5 class="modal-title" id="addModalLabel">Add Product-Supplier Assignment</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <div class="modal-body">
                                <div class="form-group">
                                    <label for="product">Product</label>
                                    <select name="product_id" class="form-control selectpicker" data-live-search="true"required>
                                        @foreach($products as $product)
                                            <option value="{{ $product->id }}">{{ $product->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label for="supplier">Supplier</label>
                                    <select name="supplier_id" class="form-control selectpicker" data-live-search="true" required>
                                        @foreach($suppliers as $supplier)
                                            <option value="{{ $supplier->id }}">{{ $supplier->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label for="price">Price</label>
                                    <input type="number" step="0.01" name="price" class="form-control" required>
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
                                <h5 class="modal-title" id="editModalLabel">Edit Product-Supplier Assignment</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <div class="modal-body">
                                <div class="form-group">
                                    <label for="product">Product</label>
                                    <select name="product_id" id="editProduct" class="form-control" required>
                                        @foreach($products as $product)
                                            <option value="{{ $product->id }}">{{ $product->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label for="supplier">Supplier</label>
                                    <select name="supplier_id" id="editSupplier" class="form-control" required>
                                        @foreach($suppliers as $supplier)
                                            <option value="{{ $supplier->id }}">{{ $supplier->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label for="price">Price</label>
                                    <input type="number" step="0.01" name="price" id="editPrice" class="form-control" required>
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
        $('#ProductSuppliersTable').DataTable();

        // Trigger Edit Modal
        $('.edit-btn').on('click', function() {
            const id = $(this).data('id');
            const product = $(this).data('product');
            const supplier = $(this).data('supplier');
            const price = $(this).data('price');

            // Set the values in the modal
            $('#editProduct').val(product);
            $('#editSupplier').val(supplier);
            $('#editPrice').val(price);

            // Update the form action dynamically
            $('#editForm').attr('action', '{{ route("ProductSuppliers.update", ":id") }}'.replace(':id', id));

            // Show the edit modal
            $('#editModal').modal('show');
        });
    });
</script>
@endsection
