@extends('layouts.app')

@section('content')

    <div class="row mx-1 my-2">
      <h2>Container Allocation</h2>
      <div class="col">
        <a class="btn btn-secondary float-end" style="color: #fff;" href="{{ route('allocation-management.index')}}"> <i class="fa fa-arrow-left"></i> Go Back </a>
        @if($allocation->push_to_inventory == 1)
            @if(auth()->user()->hasRole('admin') || auth()->user()->hasRole('Procurement Manager'))
                <a class="btn btn-success float-end me-2" style="color: #fff;" href="{{ route('allocation-management.po_generation',$allocation->id) }}"> PO Generation </a>

                <a class="btn btn-success float-end me-2" style="color: #fff;" href="{{ route('allocation-management.carton_po_generation',$allocation->id) }}"> Carton PO Generation </a>

            @endif

            <a class="btn btn-success float-end me-2" style="color: #fff;" href="{{ route('allocation-management.view_allocated_suppliers',$allocation->id) }}">View Allocated Suppliers</a>
        @endif
        </div>
    </div>
      
    <div class="card mb-3">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h3 class="card-title mb-0">Container Item List</h3>
           <div class="d-flex gap-2">
                <a class="btn btn-secondary text-white {{ $allocation->package_list_generated != 1 ? 'disabled' : '' }}"
                href="{{ $allocation->package_list_generated == 1 ? route('allocation-management.print_packaging_list', $allocation->id) : '#' }}"
                onclick="{{ $allocation->package_list_generated != 1 ? 'return false;' : '' }}">
                    <i class="fa fa-print"></i> Print Packaging List
                </a>

                <a class="btn btn-secondary text-white {{ $allocation->package_list_generated != 1 ? 'disabled' : '' }}"
                href="{{ $allocation->package_list_generated == 1 ? route('allocation-management.print_allocation_list', $allocation->id) : '#' }}"
                onclick="{{ $allocation->package_list_generated != 1 ? 'return false;' : '' }}">
                    <i class="fa fa-print"></i> Print Allocation Item List
                </a>
            </div>
        </div>
        <div class="card-body">
            <ul>
                <li class="fw-bold"> Buyer Order number : {{ $allocation->buyer_order_number }}</li>
                <li class="fw-bold"> Planned Date : {{ $allocation->planned_date }}</li>
                <li class="fw-bold"> Total Container Volume Consumed : {{ $allocation->containerAllocationItems->sum('drop_ship_volume') }}</li>
                <li class="fw-bold"> Total Physical Volume Consumed: {{ $allocation->containerAllocationItems->sum('physical_volume') }}</li>
            </ul>

            <table class="table table-bordered table-striped table-hover">
                <thead class="table-dark">
                    <tr>
                        <th>Sr.No</th>
                        <th>SKUs</th>
                        <th>Qty</th>
                        <th>Allocated Qty</th>
                        <th>Volume</th>
                        <th>Physical Volume</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($allocation->containerAllocationItems as $item)
                        <tr @if($item->qty == 0) class="table-danger" @endif>
                            <td>{{ $loop->iteration }}</td>
                            <td>{{ $item->sku." - ".$item->productInfo->name }}</td>
                            <td>{{ $item->qty }}</td>
                            <td> <span class="{{ ($item->current_allocation != $item->qty) ? 'text-danger fw-bold' : '' }}">{{ $item->current_allocation }}</span></td>
                            <td>{{ $item->drop_ship_volume }}</td>
                            <td>{{ $item->physical_volume }}</td>
                            <td>
                                @if(auth()->user()->hasRole('admin') || auth()->user()->can('edit-container-volume'))
                                    <button type="button" class="btn btn-success btn-sm" data-bs-toggle="modal" data-bs-target="#volumeModal" data-product_id="{{ $item->product_id }}" data-item_name="{{ $item->sku." - ".$item->productInfo->name }}" data-container_id="{{ $item->container_allocation_id }}" data-action="{{ route('allocation-management.updateVolume', $item->id) }}" data-volume="{{ $item->physical_volume }}">
                                        Edit Volume
                                    </button>
                                @endhasrole
                            </td>
                        </tr>
                    @endforeach
                </tbody>
               
            </table>
           
        </div>
    </div>

    <div class="modal fade" id="volumeModal" tabindex="-1" aria-labelledby="volumeModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="volumeModalLabel">Modal title</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form action="" id="volumeModelForm" method="POST">
                        @csrf
                        <div class="mb-3">
                            <label for="volume" class="form-label">Volume</label>
                            <input type="text" class="form-control" id="volume" name="volume" required>
                            <input type="hidden" class="form-control" id="allocation_container_id" name="allocation_container_id" required>
                            <input type="hidden" class="form-control" id="product_id" name="product_id" required>
                        </div>
                        <button type="submit" class="btn btn-primary">Update Volume</button>
                    </form>
                </div>

            </div>
        </div>
    </div>

@endsection
@section('footer')

<script>
    $(document).ready(function() {
        $('.btn-success').on('click', function() {
            var itemId = $(this).data('product_id');
            var itemName = $(this).data('item_name');
            var container_id = $(this).data('container_id');
            var action = $(this).data('action');
            var volume = $(this).data('volume');

            $('#volumeModalLabel').text('Edit Volume for Item: ' + itemName);
            $('#volumeModelForm').attr('action', action);
            $('#volume').val(volume);
            $('#allocation_container_id').val(container_id);
            $('#product_id').val(itemId);
        });
    });
</script>
@endsection
