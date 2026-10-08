<?php 
use App\Helpers\Common;
?>
@extends('layouts.app')

@section('content')

    <div class="row mx-1 my-2">
      <!-- <h2>Container Allocation</h2> -->
      <!-- <div class="col"><a class="btn btn-primary float-end" style="color: #fff;" href="{{ route('allocation-management.create')}}">Add New</a></div> -->
    </div>
      
    <div class="card mb-3">
        <div class="card-header">
            <h3 class="card-title">Allocation list From Purchase Software</h3>
        </div>
        <div class="card-body">
             <!-- Filter For Records -->
            <div class="row align-items-end mb-3 pt-3">
                <div class="col-md-8">
                    <form class="row g-2">
                        <div class="col-md-3">
                            <label class="form-label">Search</label>
                            <input type="text" name="search" id="search" value="{{ request('search') }}" placeholder="Keyword" class="form-control"/>
                        </div>

                        <div class="col-md-3">
                            <label for="inputtypelabel" class="form-label">From</label>
                            <div class="input-group">
                                <input type="date" name="date-from" id="date-from" value="{{ request('date-from') }}" class="form-control ui-autocomplete-input"/>
                            </div>
                        </div>

                        <div class="col-md-3">
                            <label for="inputtypelabel" class="form-label">To</label>
                            <div class="input-group">
                                <input type="date" name="date-to" id="date-to" value="{{ request('date-to') }}" class="form-control ui-autocomplete-input"/>
                            </div>
                        </div>

                        <div class="col-md-3 d-flex align-items-end">
                            <button class="btn btn-info me-2"><i class="fa fa-filter"></i> Filter</button>
                            <a href="{{ route('allocation-management.index') }}" class="btn btn-danger me-2">
                            <i class="fa fa-retweet" aria-hidden="true"></i> Reset</a>
                        </div>
                    </form>
                </div>
            </div>
            <!-- End Filter  Records -->
           
            <table class="table table-bordered table-striped table-hover">
                <thead class="table-dark">
                    <tr>
                        <th>Sr.No</th>
                        <th>Buyer Order Number</th>
                        <th>Planned Date</th>
                        <th>Received Date</th>
                        <th>SKUs ( Asked Qty / Un-Allocated Qty )</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($records as $record)
                        @php $total_unallocated_qty = 0 ; @endphp
                        <tr>
                            <td>{{ $loop->iteration }}</td>
                            <td>{{ $record->buyer_order_number }}</td>
                            <td>{{ $record->planned_date }}</td>
                            <td>{{ date('Y-m-d', strtotime($record->created_at)) }}</td>
                            <td>
                                <ul>
                                    <?php 
                                        $total_qty = 0;
                                        $total_allocated_qty = 0;
                                        $total_unallocated_qty = 0;
                                    ?>
                                    @foreach($record->containerAllocationItems as $sku_record)

                                        @php
                                        $unallocated_qty = $sku_record->qty - $sku_record->current_allocation;
                                        $total_unallocated_qty+=$unallocated_qty;
                                        $total_qty+= $sku_record->qty;
                                        $total_allocated_qty+= $sku_record->current_allocation;
                                        @endphp
                                        <li>{{$sku_record->sku." - ".$sku_record->qty." / "}} 
                                            <span class='{{ $unallocated_qty > 0 ? 'text-danger fw-bold': '' }} '> {{ $unallocated_qty }} </span>
                                         </li>
                                    @endforeach
                                    
                                </ul>
                            </td>
                            <td>
                                <a href="{{ route('allocation-management.view', $record->id) }}" class="btn btn-info btn-sm">View Item List</a>
                                
                                @if($record->push_to_inventory == 0)
                                    @if(auth()->user()->hasRole('admin') || auth()->user()->can('push-allocation-to-inventory'))
                                        <a href="{{ route('allocation-management.pushToInventory', $record->id) }}" class="btn btn-warning btn-sm">Push to Inventory</a>
                                    @endif
                                @else
                                    @if(auth()->user()->hasRole('admin') || auth()->user()->hasRole('factory') )
                                        <a href="{{ route('allocation-management.view-supplier', $record->id) }}" class="btn btn-success btn-sm">
                                            Allocate Supplier
                                        </a>
                                    @endif    
                                @endif  
                                
                                @if($record->package_list_generated == 0 && $total_unallocated_qty == 0)
                                    @if(auth()->user()->hasRole('admin') || auth()->user()->hasRole('Procurement Manager'))
                                        <a href="{{ route('allocation-management.generate_packing_list', $record->id) }}" class="btn btn-warning btn-sm">
                                            Generate Packing List
                                        </a>
                                    @endif
                                @elseif($record->package_list_generated == 1)
                                    <a href="{{ route('invoice.packing-view', $record->package_list_id) }}" class="btn btn-warning btn-sm" target="_blank">
                                        View Packing List
                                    </a>
                                    
                                @endif
                                @if($record->push_to_inventory == 1 && (auth()->user()->hasRole('admin') || auth()->user()->hasRole('Procurement Manager')))
                                    @if($total_allocated_qty == $total_qty)
                                        <a href="{{ route('allocation-management.mark_completed_and_dispatched', $record->id) }}" class="btn btn-secondary btn-sm" target="_blank">
                                            Mark As Completed and Dispatched . 
                                        </a>
                                    @endif
                                @endif
                            </td>
                        </tr>
                    @endforeach 

                    @if($records->isEmpty())
                        <tr>
                            <td colspan="6" class="text-center">No records found</td>
                        </tr>
                    @endif
                </tbody>
               
            </table>
            <div class="d-flex justify-content-center mt-3">
                {{ $records->links() }}
            </div>
        </div>
    </div>
@endsection
@section('footer')

@endsection
