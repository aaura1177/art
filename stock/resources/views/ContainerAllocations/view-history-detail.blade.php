@extends('layouts.app')
@section('content')

    <div class="row mx-1 my-2">
      <h2>Container Allocation</h2>
      <div class="col">
        <a class="btn btn-secondary float-end" style="color: #fff;" href="{{ route('allocation-management.history')}}"> <i class="fa fa-arrow-left"></i> Go Back</a>
        @if($allocation->push_to_inventory == 1)
            <a class="btn btn-success float-end me-2" style="color: #fff;" href="{{ route('allocation-management.view_suppliers_detailed_history',$allocation->id) }}">View Supplier History</a>
        @endif
        </div>
    </div>
      
    <div class="card mb-3">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h3 class="card-title mb-0">Container Item List</h3>
           <div class="d-flex gap-2">
                <a class="btn btn-secondary text-white me-2{{ $allocation->package_list_generated != 1 ? 'disabled' : '' }}"
                href="{{ $allocation->package_list_generated == 1 ? route('allocation-management.print_packaging_list', $allocation->id) : '#' }}"
                onclick="{{ $allocation->package_list_generated != 1 ? 'return false;' : '' }}">
                    <i class="fa fa-print"></i> Print Packaging List
                </a>

                <a class="btn btn-secondary text-white  {{ $allocation->package_list_generated != 1 ? 'disabled' : '' }}"
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
                <li class="fw-bold"> Completed and Dispatched Date : {{ $allocation->completed_and_dispatched_on }}</li>
                <li class="fw-bold"> Total Container Volume Consumed : {{ $allocation->containerAllocationItems->sum('drop_ship_volume') }}</li>
                <li class="fw-bold"> Total Physical Volume Consumed: {{ $allocation->containerAllocationItems->sum('physical_volume') }}</li>
            </ul>

            <table class="table table-bordered table-striped table-hover">
                <thead class="table-dark">
                    <tr>
                        <th>Sr.No</th>
                        <th>SKUs</th>
                        <th>Qty</th>
                        <th>Volume</th>
                        <th>Physical Volume</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($allocation->containerAllocationItems as $item)
                        <tr @if($item->qty == 0) class="table-danger" @endif>
                            <td>{{ $loop->iteration }}</td>
                            <td>{{ $item->sku." - ".$item->productInfo->name }}</td>
                            <td>{{ $item->qty }}</td>
                            <td>{{ $item->drop_ship_volume }}</td>
                            <td>{{ $item->physical_volume }}</td>
                        </tr>
                    @endforeach
                </tbody>
               
            </table>
           
        </div>
    </div>

@endsection
@section('footer')

@endsection
