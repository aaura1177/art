@extends('layouts.app')

@section('content')

<style>
  #po-service-filters .form-label {
    font-weight: 500;
  }
  #dataTable_wrapper .dataTables_filter label {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    margin-bottom: 0;
  }
  #dataTable_wrapper .dataTables_filter input[type="search"] {
    display: inline-block;
    width: auto;
    min-width: 220px;
  }
</style>

<div class="row mx-3 my-2 align-items-center">
  <div class="col-md-6">
    <h2 class="mb-0">Purchase Order</h2>
  </div>
  <div class="col-md-6 text-md-end">
      <a class="btn btn-success" style="color: #fff; margin-left: 5px;" data-bs-toggle="modal" data-bs-target="#mydateModal" href="{{ url('/purchaseOrder/exportcsv') }}">Download CSV</a>
      <a class="btn btn-primary" style="color: #fff;" href="{{ url('/purchaseOrder/create/service') }}">Add Service</a>
  </div>
</div>

<div class="row mx-3 my-2 g-3 align-items-end" id="po-service-filters">
  <div class="col-md-3">
      <form method="GET" action="{{ route('purchaseOrder.index') }}">
          <label for="category-select" class="form-label mb-1">Select Category</label>
          <select class="form-select" id="category-select" name="category_id" onchange="this.form.submit()">
              <option value="" selected>Select Category</option>
              @foreach($category as $cat)
                  <option value="{{ $cat->id }}" {{ request('category_id') == $cat->id ? 'selected' : '' }}>{{ $cat->name }}</option>
              @endforeach
          </select>
      </form>
  </div>
  <div class="col-md-3">
      <label for="pending-product-search" class="form-label mb-1">Search pending products</label>
      <input id="pending-product-search" class="form-control" type="text" placeholder="Search pending products" />
  </div>
  <div class="col-md-3">
      <label for="date-from" class="form-label mb-1">Date From</label>
      <input type="date" id="date-from" class="form-control" />
  </div>
  <div class="col-md-3">
      <label for="date-to" class="form-label mb-1">Date To</label>
      <input type="date" id="date-to" class="form-control" />
  </div>
</div>

    
    <div class="card mb-3">
      <div class="card-body">
        <div class="table-responsive">
          <table class="table table-bordered" id="dataTable" width="100%" cellspacing="0">
            <thead>
              <tr>
                <th>PO No.</th>
				        <th data-orderable="false" class="text-center align-middle"><input type="checkbox" id="checkedAll" onclick="selectAllPo()" style="width:18px;height:18px"></th>
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
              @if(isset($purchaseOrder)) @foreach($purchaseOrder as $key => $purchaseOrder)
              <tr>
                <td>{{$purchaseOrder->pono}}</td>
				        <td class="text-center align-middle"><input class="checkSingle" type="checkbox" id="purchaseOrder_{{$purchaseOrder->id}}" onclick="selectPo($(this))" style="width:18px;height:18px"></td>
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
                        'sendToSupplierUrl' => url('/purchaseOrder/service/' . $purchaseOrder->id . '/send-to-supplier'),
                    ])
                </td>
                <td>
                  @foreach($purchaseOrder->poTable as $potable)
                    
                 
                    (₹){{$potable->remaining_amount}}<br>
             
                  @endforeach
                </td>
                <td>
                  @foreach($purchaseOrder->poTable as $potable)
                    {{$potable->product->name}} <br>
                  @endforeach
                </td>
				
                <td>
                  <div class="d-flex flex-wrap gap-1 align-items-center">
                  {{-- {{ $purchaseOrder->status }} --}}
                  @if($purchaseOrder->status == 0)
                      <span class="tool-tip1" data-bs-toggle="tooltip" data-bs-placement="top" title="Edit">
                          <button class="btn btn-sm btn-info" onclick="updatepo('{{ $purchaseOrder->id }}')">
                              <i class="fa fa-edit"></i>
                          </button>
                      </span>
                  @endif

                    <span class="tool-tip1" data-bs-toggle="tooltip" data-bs-placement="top" title="View">
                       <a class="btn btn-sm btn-primary" style="color: #fff;" href="{{ URL::to('/purchaseOrder/service/modal/'.$purchaseOrder->id)}}" target="_blank"><i class="fa fa-eye"></i></a>
                    </span>

                    <span class="tool-tip1" data-bs-toggle="tooltip" data-bs-placement="top" title="Print">
                       <a class="btn btn-sm btn-success" style="color: #fff;" href="{{ URL::to('/purchaseOrder/service/modal/'.$purchaseOrder->id.'?print=1')}}" target="_blank"><i class="fa fa-print"></i></a>
                    </span>
                    @php
                    $poRelationCount = $purchaseOrder->servicepurchaseBill->count();
                    @endphp
                  {{-- {{$poRelationCount}} --}}
                    @if($poRelationCount == 0)
                    @if($purchaseOrder->status == 0 )
                    <span class="tool-tip1" data-bs-toggle="tooltip" data-bs-placement="top" title="Cancel">
                      <button class="btn btn-sm btn-warning" data-bs-toggle="modal" onclick="cancelModal('{{$purchaseOrder->id}}')">
                          <i class="fa fa-times"></i>
                      </button>
                  </span>
                  @endif


                    @endif
                    @if($purchaseOrder->status != 2)
                    <span class="tool-tip1" data-bs-toggle="tooltip" data-bs-placement="top" title="Update Status">
                       <a class="btn btn-sm btn-success" style="color: #fff;" href="javascript:void(0);" onclick="statusModal('{{$purchaseOrder->id}}')">Update Status</a>
                    </span>
                    @endif
                  </div>
                </td>
				        <td style="display:none">{{$purchaseOrder->podate}}</td>
              </tr>
              @endforeach @endif
            </tbody>
          </table>
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
		<button type="submit" onclick="return false" class="btn btn-success">Download</button>
	</form>
@endsection

@section('footer')


<!-- Script start for PO delete -->
<script type="text/javascript">

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
    $('#cancelPOFORM').attr('action', "{{ url('/purchaseOrder/service/cancel') }}" + '/' + id);
}


  function statusModal(id){
    $('#statuspo').modal('show');
    $('#statusPOFORM').attr('action', "{{ url('/purchaseOrder/service/updateStatus') }}" + '/' +id);
  }

  function updatepo(id){
    location.href = "{{ url('/purchaseOrder/service/view') }}" + '/' +id;
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
    //$('#invoice_ids').val("");
     if($('#po_ids').val() == ""){
      alert("Please select atleast 1 PO");
     }else{
      $("#poForm").submit();
     }
  }
  
  $(function(){
	  var table = $("#dataTable").DataTable();
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