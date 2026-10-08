@extends('layouts.app')

@section('content')

  <div class="row mx-3 my-2">
    <h2>Purchase Order (Consumables)</h2>
    <div class="col">
    <a class="btn btn-success float-end" style="color: #fff; margin-left: 5px;" data-bs-toggle="modal"
      data-bs-target="#mydateModal" href="{{ url('/purchaseOrder/exportConsumablePocsv')}}">Download CSV</a>
    <a class="btn btn-primary float-end" style="color: #fff;margin-left: 5px;"
      href="{{ url('/purchaseOrder/createConsumablePo')}}">Add Consumables PO</a>
    <a class="btn btn-primary float-end" style="color: #fff;" href="{{ url('/purchaseOrder/createCartonPo')}}">Add
      Cartons PO</a>
    </div>
  </div>

  <div class="card mb-3">
    <div class="card-body">
    <form method="GET" action="" class="mb-4">
      <div class="flex flex-wrap gap-3 items-end">
      <div class="row">
        <!-- Search Input -->
        <div class="col-md-3">
        <label for="search" class="block text-sm font-medium text-gray-700">Search</label>
        <input type="text" name="search" id="search" value="{{ request('search') }}" placeholder="Keyword"
          class="form-control" />
        </div>

        <!-- Date From -->
        <div class="col-md-3">
        <label for="date-from" class="block text-sm font-medium text-gray-700">From</label>
        <input type="date" name="date-from" id="date-from" value="{{ request('date-from') }}"
          class="form-control" />
        </div>

        <!-- Date To -->
        <div class="col-md-3">
        <label for="date-to" class="block text-sm font-medium text-gray-700">To</label>
        <input type="date" name="date-to" id="date-to" value="{{ request('date-to') }}" class="form-control" />
        </div>

        <!-- Submit Button -->
        <div class="col-md-3">
        <label for="date-to" class="block text-sm font-medium text-gray-700 mb-2"></label>
        <button type="submit" class="mt-4 btn btn-info"> Search </button>
        <a href="{{ url('/purchaseOrder/consumablePos') }}" class="mt-4 btn btn-warning"> Reset </a>
        </div>

      </div>
      </div>
    </form>

    <div class="table-responsive">
      <table class="table table-bordered" id="dataTablesss" width="100%" cellspacing="0">
        <thead>
          <tr>
          <th>PO No.</th>
          <th>Supplier Name</th>
          <th>PO Date</th>
          <th>Delivery Date</th>
          <th>Total Quantity</th>
          <th>Total Amount</th>
          <th>Status</th>
          <th>Supplier</th>
          <th style="min-width:170px;">Options</th>
          <th style="display:none;"></th>
          <th style="min-width:120px;">Pending Products</th>
          </tr>
        </thead>
        <tbody>
          @if(isset($purchaseOrders)) 
              @foreach($purchaseOrders as $key => $purchaseOrder)
                <tr>
                  <td>{{$purchaseOrder->pono}}</td>
                  <td>{{(isset($purchaseOrder->supplier->c_name)) ? $purchaseOrder->supplier->c_name : ''}}</td>
                  <td>{{date('d-M-Y', strtotime($purchaseOrder->podate))}}</td>
                  <td>{{date('d-M-Y', strtotime($purchaseOrder->del_date))}}</td>
                  <td>{{$purchaseOrder->tquantity}}</td>
                  <td>{{$purchaseOrder->tamount}}</td>
                  <td>
                    
                    @if($purchaseOrder->status == 0){{'Pending'}}@endif
                      @if($purchaseOrder->status == 1){{'Complete'}}@endif
                        @if($purchaseOrder->status == 2){{'Canceled'}}@endif
                  </td>
                  <td>
                    @include('purchaseOrder.partials.send_to_supplier_actions', [
                        'purchaseOrder' => $purchaseOrder,
                        'sendToSupplierUrl' => url('/purchaseOrder/consumable/' . $purchaseOrder->id . '/send-to-supplier'),
                    ])
                  </td>
                  <td>
                    @if($purchaseOrder->type == 1)
                      @if($purchaseOrder->status != 1)
                        <span class="tool-tip1" data-bs-toggle="tooltip" data-bs-placement="top" title="Edit">
                          <button class="btn btn-info" onclick="updatepo('{{$purchaseOrder->id}}')">
                            <i class="fa fa-edit"></i>
                          </button>
                        </span>
                      @endif
                      <span class="tool-tip1" data-bs-toggle="tooltip" data-bs-placement="top" title="View">
                        <a class="btn btn-primary" style="color: #fff;"
                        href="{{ URL::to('/purchaseOrder/modalConsumablePo/' . $purchaseOrder->id)}}" target="_blank"><i
                        class="fa fa-eye"></i></a>
                      </span>

                      <span class="tool-tip1" data-bs-toggle="tooltip" data-bs-placement="top" title="Print">
                        <a class="btn btn-success" style="color: #fff;"
                        href="{{ URL::to('/purchaseOrder/modalConsumablePo/' . $purchaseOrder->id . '?print=1')}}"
                        target="_blank"><i class="fa fa-print"></i></a>
                      </span>
                    @endif
                    @if($purchaseOrder->type == 2)
                      @if($purchaseOrder->status != 1)
                        <span class="tool-tip1" data-bs-toggle="tooltip" data-bs-placement="top" title="Edit">
                          <button class="btn btn-info" onclick="updateCartonpo('{{$purchaseOrder->id}}')">
                            <i class="fa fa-edit"></i>
                          </button>
                        </span>
                      @endif
                      <span class="tool-tip1" data-bs-toggle="tooltip" data-bs-placement="top" title="View">
                      <a class="btn btn-primary" style="color: #fff;"
                      href="{{ URL::to('/purchaseOrder/modalCartonPo/' . $purchaseOrder->id)}}" target="_blank"><i
                      class="fa fa-eye"></i></a>
                      </span>
                      <span class="tool-tip1" data-bs-toggle="tooltip" data-bs-placement="top" title="Print">
                      <a class="btn btn-success" style="color: #fff;"
                      href="{{ URL::to('/purchaseOrder/modalCartonPo/' . $purchaseOrder->id . '?print=1')}}" target="_blank"><i
                      class="fa fa-print"></i></a>
                      </span>
                    @endif
                    @php
                    $poRelationCount = $purchaseOrder->purchaseBill->count();
                    @endphp

                      @if($purchaseOrder->type == 1 || $purchaseOrder->type == 2)
                        <!-- Cancel if order status is pending and user has cancel permission   -->
                        @if($purchaseOrder->status == 0 )
                          @if(auth()->user()->hasRole('admin') || auth()->user()->can('cancel-consumables-po-order'))
                            <span class="tool-tip1" data-bs-toggle="tooltip" data-bs-placement="top" title="Cancel">
                            <button class="btn btn-warning" data-bs-toggle="modal" onclick="cancelModal('{{$purchaseOrder->id}}')">
                            <i class="fa fa-times"></i>
                            </button>
                            </span>
                          @endif
                        @endif
                      @endif
                      <!-- Delete if order status is pending and user has delete permission   -->
                      @if($purchaseOrder->status == 0 )
                        @if(auth()->user()->hasRole('admin') || auth()->user()->can('cancel-consumables-po-order'))
                          <form action="{{ route('purchaseOrder.ConsumablePo.delete', $purchaseOrder->id) }}" method="POST" style="display:inline-block;" onsubmit="return confirm('Are you sure you want to delete this record?');">
                            @csrf
                              @method('DELETE')
                              <button type="submit" class="btn btn-danger btn-sm">Delete</button>
                          </form>
                        @endif
                      @endif
                  </td>
                  <td style="display:none;">
                   
                    @foreach($purchaseOrder->poTable as $potable)
                    {{(isset($potable->consumable->name)) ? $potable->consumable->name : ''}}
                    @endforeach
                  </td>
                  <td>
                    @if ($purchaseOrder->type == 1)
                    
                    @foreach($purchaseOrder->poTable as $potable)
                      @if($potable->remqty > 0)
                        {{(isset($potable->consumable->name)) ? $potable->consumable->name : ''}} -
                        {{$potable->remqty}}/{{$potable->quantity}} <br>
                      @endif
                    @endforeach
                    @else
                      @foreach ($purchaseOrder->popTable as $popTable)
                                            
                                                @if ($popTable->remqty_box1 > 0 || $popTable->remqty_box2 > 0)
                                                   
                                                    {{ $popTable->product->code }}
                                                    <br>
                                                @endif
                                            @endforeach
                    @endif
                </td>
              </tr>
            @endforeach 
          @endif
        </tbody>
      </table>
    </div>

    <div class="mt-4">
      @if(isset($purchaseOrders))
      {{ $purchaseOrders->links() }}
    @endif
    </div>

    </div>
    <div class="modal" id="cancelpo" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
      <div class="modal-content">
      <form id="cancelPOFORM" method="POST">
        @csrf
        @method('POST')
        <div class="modal-header">
        <h5 class="modal-title">Cancel Purchase Order</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
        Are you sure you want to cancel this Purchase Order?
        </div>
        <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
        <button type="submit" class="btn btn-danger">Yes, Cancel</button>
        </div>
      </form>
      </div>
    </div>
    </div>

    <!-- MODAL FOR DATE -->
    <div class="modal fade" id="mydateModal" role="dialog">
    <div class="modal-dialog">
      <div class="modal-content">
      <div class="modal-header">
        <h4 class="modal-title">Enter Date</h4>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <form method="POST" action="{{ url('/purchaseOrder/exportConsumablePocsv') }}">
        <div class="modal-body">
          @csrf
          <div class="row">
          <div class="col-6">
            <input class="form-control" type="date" name="fsd" id="fsd" required value="" />
          </div>
          <div class="col-6">
            <input class="form-control" type="date" name="fed" id="fed" required value="" />
          </div>
          </div>
        </div>
        <div class="modal-footer">
          <button type="submit" onclick="return validate()" class="btn btn-success">Download</button>
        </div>
        </form>
      </div>
      </div>
    </div>
    </div>

    <!-- MODAL FOR DELETE -->
    <div class="modal fade" id="deletepo" role="dialog">
    <div class="modal-dialog">
      <div class="modal-content">
      <div class="modal-header">
        <h4 class="modal-title">Delete Confirmation</h4>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <form id="deletePOFORM" method="POST" action="">
        @csrf
        <div class="modal-body">
        <p>Are You sure you want to Delete this?</p>
        </div>
        <div class="modal-footer">
        <button type="button" class="btn btn-default" data-bs-dismiss="modal">No</button>
        <button type="submit" id="deletePOFORM" class="btn btn-danger">Yes</button>
        </div>
      </form>
      </div>
    </div>
    </div>
  @endsection

  @section('footer')


    <!-- Script start for PO delete -->
    <script type="text/javascript">

    function validate() {
      var fsd = $('#fsd').val();
      var fed = $('#fed').val();
      if (fsd > fed) {
      alert('Start Date should be less than End Date');
      return false;
      }
      return true;
    }

    $(function () {
      $('[data-bs-toggle="tooltip"]').tooltip()
    });

    function deleteModal(id) {
      $('#deletepo').modal('show');
      $('#deletePOFORM').attr('action', "{{ url('/purchaseOrder/deleteConsumablePo') }}" + '/' + id);
    }

    function updatepo(id) {
      location.href = "{{ url('/purchaseOrder/viewConsumablePo') }}" + '/' + id;
    }

    function deleteCartonModal(id) {
      $('#deletepo').modal('show');
      $('#deletePOFORM').attr('action', "{{ url('/purchaseOrder/deleteCartonPo') }}" + '/' + id);
    }

    function updateCartonpo(id) {
      location.href = "{{ url('/purchaseOrder/viewCartonPo') }}" + '/' + id;
    }

    $(function () {
      var table = $("#dataTable").DataTable();
      $('#dataTable_filter').append('<br><label>Search pending products: <input id="pending-product-search" class="form-control" type="text" placeholder="Search pending products" data-index="" /></label>');
      $('#dataTable_filter').append('<br><label>Date From: <input type="date" id="date-from" class="form-control" type="text" placeholder="Search pending products" data-index="" /></label>');
      $('#dataTable_filter').append('<br><label>Date To: <input type="date" id="date-to" class="form-control" type="text" placeholder="Search pending products" data-index="" /></label>');
      $(table.table().container()).on('keyup', '#pending-product-search', function () {
      table
        .column(9)
        .search(this.value)
        .draw();
      });

      ids = "";
      table.rows().data().toArray().forEach(function (item, i) {
      ids += item[1] + ",";
      });
      $("#expids").val(ids);
      table.on('search.dt', function () {
      //number of filtered rows
      //console.log(table.rows( { filter : 'applied'} ).nodes().length);
      //filtered rows data as arrays
      ids = "";
      table.rows({ filter: 'applied' }).data().toArray().forEach(function (item, i) {
        ids += item[1] + ",";
      });
      $("#expids").val(ids);
      })

      $('#date-from').change(function () {
      minDateFilter = new Date(this.value).getTime();
      table.draw();
      });

      $('#date-to').change(function () {
      maxDateFilter = new Date(this.value).getTime();
      table.draw();
      });
      minDateFilter = "";
      maxDateFilter = "";

      $.fn.dataTable.ext.search.push(
      function (settings, data, dataIndex) {
        var createdAt = data[2] || 0; // Our date column in the table

        if (
        (minDateFilter == "" || maxDateFilter == "")
        ||
        (moment(createdAt).isSameOrAfter(minDateFilter) && moment(createdAt).isSameOrBefore(maxDateFilter))
        ) {
        return true;
        }
        return false;
      }
      );

    });

    function cancelModal(id) {
      $('#cancelpo').modal('show');
      $('#cancelPOFORM').attr('action', "{{ url('/purchaseOrder/ConsumablePo/cancel') }}" + '/' + id);
    }

    </script>
    <!-- Script end -->

  @endsection