@extends('layouts.app')

@section('content')

    <div class="row mx-3 my-2">
      <h2>Purchase Order</h2>
      <div class="col">
        <a class="btn btn-success float-end" style="color: #fff; margin-left: 5px;" data-bs-toggle="modal" data-bs-target="#mydateModal"  href="{{ url('/purchaseOrder/exportcsv')}}">Download CSV</a>
        <a class="btn btn-success float-end" style="color: #fff; margin-left: 5px;" onclick="poForm()"  href="#">Export for Tally</a>
        <a class="btn btn-primary float-end" style="color: #fff; margin-left: 5px;" href="{{ url('/purchaseOrder/create?series=bo')}}">Add BO PO</a>
        <a class="btn btn-primary float-end" style="color: #fff;" href="{{ url('/purchaseOrder/create')}}">Add PO</a></div>
    </div>
    
    <div class="card mb-3">
      <div class="card-body">
        <form method="GET" action="" class="mb-4">
          <div class="flex flex-wrap gap-3 items-end">
            <div class="row">
              <!-- Search Input -->
              <div class="col-md-3">
                  <label for="search" class="block text-sm font-medium text-gray-700">Search</label>
                  <input type="text" name="search" id="search"
                         value="{{ request('search') }}"
                         placeholder="Keyword"
                         class="form-control"/>
              </div>
      
              <!-- Date From -->
              <div class="col-md-3">
                  <label for="date-from" class="block text-sm font-medium text-gray-700">From</label>
                  <input type="date" name="date-from" id="date-from"
                         value="{{ request('date-from') }}"
                         class="form-control"/>
              </div>
      
              <!-- Date To -->
              <div class="col-md-3">
                  <label for="date-to" class="block text-sm font-medium text-gray-700">To</label>
                  <input type="date" name="date-to" id="date-to" value="{{ request('date-to') }}"
                         class="form-control"/>
              </div>
      
              <!-- Submit Button -->
              <div class="col-md-3">
                  <label for="date-to" class="block text-sm font-medium text-gray-700 mb-2"></label>
                  <button type="submit" class="mt-4 btn btn-info"> Search </button>
                  <a href="{{ url('/purchaseOrder') }}" class="mt-4 btn btn-warning"> Reset </a>
              </div>
             
            </div>
          </div>
      </form>

      

        <div class="table-responsive">
          <table class="table table-bordered" id="dataTabless" width="100%" cellspacing="0">
            <thead>
              <tr>
                <th>PO No.</th>
				        <th  style="padding:0px;"><input type="checkbox" id="checkedAll" onclick="selectAllPo()" style="width:50px;height:50px"></th>
                <th>Supplier Name</th>
                <th>Supplier Ref. </th>
                <th>PO Date</th>
                <th>Delivery Date</th>
                <th>Total Quantity</th>
                <th>Total Amount</th>
                <th>Status</th>
                <th>Supplier</th>
                <th style="min-width:120px;">Pending Products</th>
                <th>All Products</th>
				<th style="min-width:120px;">Options</th>
				<th style="display:none"></th>
              </tr>

            </thead>
            <tbody>
              @if(isset($purchaseOrders)) @foreach($purchaseOrders as $key => $purchaseOrder)
              <tr>
                <td>
                  {{$purchaseOrder->pono}}
                  @php
                    $lv = $latestVersions[$purchaseOrder->id] ?? null;
                    $pv = $pendingVersions[$purchaseOrder->id] ?? 0;
                  @endphp
                  @if($lv)
                    <span class="badge bg-secondary">v{{ $lv }}</span>
                    @if($pv > 0)
                      <span class="badge bg-warning text-dark" title="Pending supplier version accept">{{ $pv }} pending</span>
                    @endif
                  @endif
                </td>
				        <td style="padding:0px;"><input class="checkSingle" type="checkbox" id="purchaseOrder_{{$purchaseOrder->id}}" onclick="selectPo($(this))" style="width:50px;height:50px"></td>
                <td>{{$purchaseOrder->supplier->c_name}}</td>
                <td>{{$purchaseOrder->ref_supplier}}</td>
                <td>{{date('d-M-Y',strtotime($purchaseOrder->podate))}}</td>
                <td>{{date('d-M-Y',strtotime($purchaseOrder->del_date))}}</td>
                <td>{{$purchaseOrder->tquantity}}</td>
                <td>{{$purchaseOrder->tamount}}</td>
                <td>@if($purchaseOrder->status == 0){{'Pending'}}@endif
                    @if($purchaseOrder->status == 1){{'Complete'}}@endif
                    @if($purchaseOrder->status == 2){{'Canceled'}}@endif
                </td>
                <td>
                    @include('purchaseOrder.partials.send_to_supplier_actions', [
                        'purchaseOrder' => $purchaseOrder,
                        'sendToSupplierUrl' => url('/purchaseOrder/' . $purchaseOrder->id . '/send-to-supplier'),
                    ])
                </td>
                <td>
                  @foreach($purchaseOrder->poTable as $potable)
                    @if($potable->remqty > 0)
                      {{$potable->product->code ?? 'Unknown Product'}} - {{$potable->remqty}}/{{$potable->quantity}} <br>
                    @endif
                  @endforeach
                </td>
                <td>
                  
                  @foreach($purchaseOrder->poTable as $potable)
                    {{$potable->product->code ?? 'Unknown Product'}} <br>
                  @endforeach
                </td>
				
                <td>
                  {{-- {{ $purchaseOrder->status }} --}}
                  @if($purchaseOrder->status == 0)
                      <span class="tool-tip1" data-bs-toggle="tooltip" data-bs-placement="top" title="Edit">
                          <button class="btn btn-info" onclick="updatepo('{{ $purchaseOrder->id }}')">
                              <i class="fa fa-edit"></i>
                          </button>
                      </span>
                  @endif
                  
                    <span class="tool-tip1" data-bs-toggle="tooltip" data-bs-placement="top" title="View">
                       <a class="btn btn-primary" style="color: #fff;" href="{{ URL::to('/purchaseOrder/modal/'.$purchaseOrder->id)}}" target="_blank"><i class="fa fa-eye"></i></a>
                    </span>
                    <span class="tool-tip1" data-bs-toggle="tooltip" data-bs-placement="top" title="Version history">
                       <a class="btn btn-outline-secondary" href="{{ url('/purchaseOrder/version-history/'.$purchaseOrder->id) }}"><i class="fa fa-history"></i></a>
                    </span>
                    
                    <span class="tool-tip1" data-bs-toggle="tooltip" data-bs-placement="top" title="Print">
                       <a class="btn btn-success" style="color: #fff;" href="{{ URL::to('/purchaseOrder/modal/'.$purchaseOrder->id.'?print=1')}}" target="_blank"><i class="fa fa-print"></i></a>
                    </span>
                    @php
                    $poRelationCount = $purchaseOrder->purchaseBill->count();
                    @endphp
                  {{-- {{$poRelationCount}} --}}
                    @if($poRelationCount == 0)
                        <!-- Cancel if purchase order status is pending and user has admin role or cancel permission -->
                        @if($purchaseOrder->status == 0 )
                          @if(auth()->user()->hasRole('admin') || auth()->user()->hasRole('Admin') || auth()->user()->can('cancel-purchase-order'))
                            <span class="tool-tip1" data-bs-toggle="tooltip" data-bs-placement="top" title="Cancel">
                              <button class="btn btn-warning" data-bs-toggle="modal" onclick="cancelModal('{{$purchaseOrder->id}}')">
                                  <i class="fa fa-times"></i>  
                              </button>
                            </span>
                          @endif
                      @endif
                
                    @endif
                    
                    @if($purchaseOrder->status != 2)
                    <span class="tool-tip1" data-bs-toggle="tooltip" data-bs-placement="top" title="Print">
                       <a class="btn btn-success" style="color: #fff;" href="javascript:void(0);" onclick="statusModal('{{$purchaseOrder->id}}')">Update Status</a>
                    </span>
                    @endif

                    <!-- Delete if purchase order status is pending and user has admin role or delete permission -->
                  @if($purchaseOrder->status == 0 )
                    @if(auth()->user()->hasRole('admin') || auth()->user()->can('delete-purchase-order'))
                      <form action="{{ route('purchaseOrder.delete', $purchaseOrder->id) }}" method="POST" style="display:inline-block;" onsubmit="return confirm('Are you sure you want to delete this record?');">
                          @csrf
                          @method('DELETE')
                          <button type="submit" class="btn btn-danger btn-sm">Delete</button>
                      </form>
                    @endif
                  @endif
                  
                </td>
				        <td style="display:none">{{$purchaseOrder->podate}}</td>
              </tr>
              @endforeach @endif
            </tbody>
          </table>

          <div class="mt-4">
            @if(isset($purchaseOrders))
            {{ $purchaseOrders->links() }}
            @endif
        </div>

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
              <form method="POST" action="{{ url('/purchaseOrder/exportcsv') }}">
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
  

    <!-- MODAL FOR STATUS -->
    <div class="modal fade" id="statuspo" role="dialog">
      <div class="modal-dialog">
        <div class="modal-content">
          <div class="modal-header">
            <h4 class="modal-title">Update Confirmation</h4>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>  
          </div>
          <form id="statusPOFORM" method="POST" action="">
          @csrf  
            <div class="modal-body">
              <div class="row">
                  <div class="col-6">
                      <select class="form-control" name="status" required>
                        <option value="">Select Status</option>
                        <option value="0">Pending</option>  
                        <option value="1">Completed</option>
                      </select>
                  </div>
              </div>
            </div>
            <div class="modal-footer">
              <button type="button" class="btn btn-default" data-bs-dismiss="modal">Cancel</button>
              <button type="submit" id="" class="btn btn-success">Update</button>
            </div>
          </form>  
        </div>
      </div>
    </div>


    
	
	<form id="poForm" method="POST" action="{{ url('/purchaseOrder/poTallyExport') }}" style="visibility:hidden;">
		@csrf
		<input type="hidden" id="po_ids" name="po_ids" />
		<input type="hidden" id="tally_date_from" name="date-from" />
		<input type="hidden" id="tally_date_to" name="date-to" />
		<input type="hidden" id="tally_search" name="search" />
		<button type="submit" onclick="return false" class="btn btn-success">Download</button>
	</form>
@endsection

@section('footer')


<!-- Script start for PO delete -->
<script type="text/javascript">
$(function(){
    $('#dataTabless').DataTable({
          paging: false,       // disable DataTables pagination
          info: false,         // hide "Showing 1 to X of Y"
          searching: false,    // disable search (optional)
      });
  });
  

  function validate(){
    var fsd = $('#fsd').val();
    var fed = $('#fed').val();
    if(fsd > fed){
        alert('Start Date should be less than End Date');
        return false;
    }
    return true;
  }

  $(function () {
    $('[data-bs-toggle="tooltip"]').tooltip()

    $("#checkedAll").change(function() {
        if (this.checked) {
            $(".checkSingle").each(function() {
                this.checked=true;
                selectPo($(this));
            });
        } else {
            $(".checkSingle").each(function() {
                this.checked=false;
                selectPo($(this));
            });
        }
    });

    $(".checkSingle").click(function () {
        if ($(this).is(":checked")) {
            var isAllChecked = 0;

            $(".checkSingle").each(function() {
                if (!this.checked)
                    isAllChecked = 1;
            });

            if (isAllChecked == 0) {
                $("#checkedAll").prop("checked", true);
            }     
        }
        else {
            $("#checkedAll").prop("checked", false);
        }
    });
  });

  function cancelModal(id) {
    $('#cancelpo').modal('show');
    $('#cancelPOFORM').attr('action', "{{ url('/purchaseOrder/cancel') }}" + '/' + id);
  }


  function statusModal(id){
    $('#statuspo').modal('show');
    $('#statusPOFORM').attr('action', "{{ url('/purchaseOrder/updateStatus') }}" + '/' +id);
  }

  function updatepo(id){
    location.href = "{{ url('/purchaseOrder/view') }}" + '/' +id;
  }
  
  function selectPo(chObj){
		if(chObj.is(":checked")){
			po_id = chObj.attr("id").split("_");
			po_ids = $('#po_ids').val();
			po_ids = po_ids+po_id[1]+",";
			$('#po_ids').val(po_ids);
		}else{
			po_id = chObj.attr("id").split("_");
			po_ids = $('#po_ids').val().replace(po_id[1]+",","");
			$('#po_ids').val(po_ids);
		}
	}
	
	function poForm(){
    var po_ids = $('#po_ids').val();
    var dateFrom = $('#date-from').val();
    var dateTo = $('#date-to').val();
    var search = $('#search').val();

    $('#tally_date_from').val(dateFrom);
    $('#tally_date_to').val(dateTo);
    $('#tally_search').val(search);

    // Prefer selected checkboxes; otherwise export all POs for From/To (+ search) filters
    if (po_ids == "" && (!dateFrom || !dateTo)) {
      alert("Please set From and To dates, or select at least 1 PO");
      return;
    }
    $("#poForm").submit();
  }
  
  $(function(){
	  var table = $("#dataTable").DataTable();
		$('#dataTable_filter').append( '<br><label>Search pending products: <input id="pending-product-search" class="form-control" type="text" placeholder="Search pending products" data-index="" /></label>');
		$('#dataTable_filter').append( '<br><label>Date From: <input type="date" id="date-from" class="form-control" type="text" placeholder="Search pending products" data-index="" /></label>');
		$('#dataTable_filter').append( '<br><label>Date To: <input type="date" id="date-to" class="form-control" type="text" placeholder="Search pending products" data-index="" /></label>');
		$( table.table().container() ).on( 'keyup', '#pending-product-search', function () {
			table
				.column(9)
				.search( this.value )
				.draw();
		} );
		
		ids = "";
		table.rows().data().toArray().forEach(function(item,i){
			ids += item[1] + ",";
		});
		$("#expids").val(ids);
		table.on('search.dt', function() {
			//number of filtered rows
			//console.log(table.rows( { filter : 'applied'} ).nodes().length);
			//filtered rows data as arrays
			ids = "";
			table.rows( { filter : 'applied'} ).data().toArray().forEach(function(item,i){
				ids += item[1] + ",";
			});   
			$("#expids").val(ids);
		})
		
		$('#date-from').change(function(){
			minDateFilter = new Date(this.value).getTime();
			table.draw();
		});
		
		$('#date-to').change(function(){
			maxDateFilter = new Date(this.value).getTime();
			table.draw();
		});
		minDateFilter = "";
		maxDateFilter = "";

		$.fn.dataTable.ext.search.push(
			function( settings, data, dataIndex ) {
				var createdAt = data[12] || 0; // Our date column in the table

				if  ( 
						( minDateFilter == "" || maxDateFilter == "" )
						|| 
						( moment(createdAt).isSameOrAfter(minDateFilter) && moment(createdAt).isSameOrBefore(maxDateFilter) ) 
					)
				{
					return true;
				}
				return false;
			}
		);
		
  });

</script>
<!-- Script end -->

@endsection