@extends('layouts.app')

@section('content')

    <div class="row mx-3 my-2">
      <h2>Supplier Invoices</h2>
      <div class="col">
        <a class="btn btn-success float-end" style="color: #fff; margin-left: 5px;" data-bs-toggle="modal" data-bs-target="#mydateModal"  href="{{ url('/supplierInvoice/exportcsv')}}">Download CSV</a>
        <!-- <a class="btn btn-success float-end" style="color: #fff; margin-left: 5px;" onclick="poForm()"  href="#">Export for Tally</a> -->

    </div>
    </div>

    <div class="card mb-3">
      <div class="card-body">
        <form method="GET" action="" class="mb-4">
          <div class="flex items-center">
            <input
                type="text"
                name="search"
                value="{{ request('search') }}"
                placeholder="Search"
                class="form-input rounded-lg border p-2 me-2"
            />
            <button type="submit" class="btn btn-primary">Search</button>
            <a href="{{ url('/supplierInvoice') }}" class="btn btn-warning"> Reset </a>
        </div>
      </form>

        <div class="table-responsive">
          <table class="table table-bordered" id="dataTabless" width="100%" cellspacing="0">
            <thead>
              <tr>
                <th>PO No.</th>
                 <th>Supplier Invoice Date.</th>
                <th>Supplier Invoice No. </th>
                <th>Supplier Name</th>
                <th>Internal Invoice No.</th>
                <th>Eway Bill No.</th>
                <th>Vehicle No.</th>
                <th>Total GST</th>
                <th>Sub Total</th>
                <th>Total Amount</th>
        				<th style="min-width:120px;">Options</th>
        				<th style="display:none"></th>
              </tr>
            </thead>
            <tbody>
              @if(isset($supplierInvoices)) @foreach($supplierInvoices as $key => $supplierInvoice)
              <tr>
                <td>{{($supplierInvoice->purchase_order_type == 'Furniture')? (isset($supplierInvoice->purchaseOrder->pono)?$supplierInvoice->purchaseOrder->pono:''):(isset($supplierInvoice->purchaseOrderConsumable->pono)?$supplierInvoice->purchaseOrderConsumable->pono:'')}}</td>
              <td>{{ \Carbon\Carbon::parse($supplierInvoice->invoice_date)->format('d-M-Y') }}</td>
                <td>{{$supplierInvoice->supplier_invoice_number}}</td>
                <td>{{ optional(optional($supplierInvoice->user)->supplier)->c_name ?? optional($supplierInvoice->supplierData)->c_name ?? '' }}</td>
                <td>{{$supplierInvoice->internal_invoice_number}}</td>
                <td>

                  @if($supplierInvoice->eway_bill_pdf == ''){{$supplierInvoice->eway_bill_no}}@endif
                    @if($supplierInvoice->eway_bill_pdf != '')<a href="{{ URL::to('/supplierInvoice/download/'.$supplierInvoice->id)}}">{{$supplierInvoice->eway_bill_no}}</a>@endif

                </td>
                <td>{{$supplierInvoice->vehicle_no}}</td>
                <td>{{$supplierInvoice->tamount - $supplierInvoice->subTotal}}</td>
                <td>{{$supplierInvoice->subTotal}}</td>
                <td>{{$supplierInvoice->tamount}}</td>
				        <td>
                @if($supplierInvoice->status == 2)
                  Cancelled by Supplier
                @else
                    <span class="tool-tip1" data-bs-toggle="tooltip" data-bs-placement="top" title="View">
                       <a class="btn btn-primary" style="color: #fff;" href="{{ URL::to('/supplierInvoice/modal/'.$supplierInvoice->id)}}" target="_blank"><i class="fa fa-eye"></i></a>
                    </span>
                    <span class="tool-tip1" data-bs-toggle="tooltip" data-bs-placement="top" title="Returns">
                      <a class="btn btn-warning" style="color: #fff;" href="{{url('/rejectRepair/create?id='.$supplierInvoice->id)}}"  ><i class="fa fa-undo"></i></a>

                       <!-- <a class="btn btn-warning style="color: #fff;" href="#" data-bs-toggle="modal" data-bs-target="#shippingBill" onclick="supplierInvReturn({{$supplierInvoice->id}} )" ><i class="fa fa-undo"></i></a> -->
                    </span>
                    @if($supplierInvoice->is_approved==0)
                    <span class="tool-tip1" data-bs-toggle="tooltip" data-bs-placement="top" title="Approve">
                       <a class="btn btn-success" style="color: #fff;" href="{{ URL::to('/supplierInvoice/approve/'.$supplierInvoice->id)}}" ><i class="fas fa-check-circle"></i></a>
                    </span>
                    @endif
                  @endif
                </td>
				        <td style="display:none">{{$supplierInvoice->podate}}</td>
              </tr>
              @endforeach @endif
            </tbody>
          </table>
          <div class="mt-4">
              @if(isset($supplierInvoices))
              {{ $supplierInvoices->links() }}
              @endif
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
              <form method="POST" action="{{ url('/supplierInvoice/exportcsv') }}">
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

    <!-- MODAL FOR Shipping Bill -->
    <div class="modal fade" id="shippingBill" role="dialog">
      <div class="modal-dialog">
        <div class="modal-content">
          <div class="modal-header">
            <h4 class="modal-title">Return Supplier Invoice Products</h4>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <form method="POST" action="{{ url('/supplierInvoice/updateSupplierReturn') }}">
          @csrf
            <div class="modal-body">
                <div class="row">
                  <input id="s_inv_id" type="hidden" class="form-control" name="id" />
                  <div class="col-6">
                    <label>Return Type</label>
                    <select class="form-control"  name="type"><option value="" disabled selected hidden>Select</option><option value="full">Full</option><option value="partial" >Partial</option></select>
                  </div>
                </div>

            </div>
                <div class="modal-footer">
                  <button type="submit" class="btn btn-success">Save</button>
                </div>
          </form>
        </div>
      </div>
    </div>

	<form id="poForm" method="POST" action="{{ url('/supplierInvoice/poTallyExport') }}" style="visibility:hidden;">
		@csrf
		<input type="hidden" id="po_ids" name="po_ids" />
		<button type="submit" onclick="return false" class="btn btn-success">Download</button>
	</form>
@endsection

@section('footer')


<!-- Script start for PO delete -->
<script type="text/javascript">

  function supplierInvReturn(inv_id){
    $("#s_inv_id").val(inv_id);
    //let type_val = $(this).val();
   // alert($("#type").val(''));

  }
  /*function changeHSN(ref) {
    alert(11);
  }*/

  $("[name='type']").on("change", function (e) {
    let type_val = $(this).val();

    let si_id = $('#s_inv_id').val();
    if(type_val == 'partial'){

      location.href = "{{ url('/supplierInvoice/create') }}" + '/' +si_id + '/' +type_val;
    }
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
  });

  function deleteModal(id){
    $('#deletepo').modal('show');
    $('#deletePOFORM').attr('action', "{{ url('/supplierInvoice/delete') }}" + '/' +id);
  }

  function updatepo(id){
    location.href = "{{ url('/supplierInvoice/view') }}" + '/' +id;
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
		/*$('#dataTable_filter').append( '<br><label>Search pending products: <input id="pending-product-search" class="form-control" type="text" placeholder="Search pending products" data-index="" /></label>');
		$('#dataTable_filter').append( '<br><label>Date From: <input type="date" id="date-from" class="form-control" type="text" placeholder="Search pending products" data-index="" /></label>');
		$('#dataTable_filter').append( '<br><label>Date To: <input type="date" id="date-to" class="form-control" type="text" placeholder="Search pending products" data-index="" /></label>');*/
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
