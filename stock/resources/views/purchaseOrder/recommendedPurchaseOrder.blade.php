@extends('layouts.app')

@section('content')

    <div class="row mx-3 my-2">
      <h2>Recommended Purchase Orders</h2>
      <div class="col">
    </div>
    
    <div class="card mb-3">
      <div class="card-body">
        <div class="table-responsive">
          <table class="table table-bordered" id="dataTable" width="100%" cellspacing="0">
            <thead>
              <tr>
                <th>PO No.</th>
				        <th data-orderable="false" style="padding:0px;"><input type="checkbox" id="checkedAll" onclick="selectAllPo()" style="width:50px;height:50px"></th>
                <th>Supplier Name</th>
                <th>Supplier Ref. </th>
                <th>PO Date</th>
                <th>Delivery Date</th>
                <th>Total Quantity</th>
                <th>Total Amount</th>
                <th>Status</th>
                <th>All Products</th>
				<th style="min-width:120px;">Options</th>
				<th style="display:none"></th>
              </tr>
            </thead>
            <tbody>
              @if(isset($purchaseOrder)) @foreach($purchaseOrder as $key => $purchaseOrder)
              <tr>
                <td>{{$purchaseOrder->pono}}</td>
				        <td style="padding:0px;"><input class="checkSingle" type="checkbox" id="purchaseOrder_{{$purchaseOrder->id}}" onclick="selectPo($(this))" style="width:50px;height:50px"></td>
                <td>{{$purchaseOrder->supplier->c_name}}</td>
                <td>{{$purchaseOrder->ref_supplier}}</td>
                <td>{{date('d-M-Y',strtotime($purchaseOrder->podate))}}</td>
                <td>{{date('d-M-Y',strtotime($purchaseOrder->del_date))}}</td>
                <td>{{$purchaseOrder->tquantity}}</td>
                <td>{{$purchaseOrder->tamount}}</td>
                <td>@if($purchaseOrder->status == 0){{'Pending'}}@endif
                    @if($purchaseOrder->status == 1){{'Complete'}}@endif
                </td>
                
                
                <td>
                  @foreach($purchaseOrder->poTable as $potable)
                    {{$potable->product->code}} <br>
                  @endforeach
                </td>
				
				        <td>
                    @if($purchaseOrder->status != 1)
                    <span class="tool-tip1" data-bs-toggle="tooltip" data-bs-placement="top" title="Edit">
                      <button class="btn btn-info" onclick="updatepo('{{$purchaseOrder->id}}')">
                        <i class="fa fa-edit"></i>
                      </button>
                    </span>
                    @endif
                    <span class="tool-tip1" data-bs-toggle="tooltip" data-bs-placement="top" title="View">
                       <a class="btn btn-primary" style="color: #fff;" href="{{ URL::to('/purchaseOrder/modalRecommendedPurchaseOrder/'.$purchaseOrder->id)}}" target="_blank"><i class="fa fa-eye"></i></a>
                    </span>
                    <span class="tool-tip1" data-bs-toggle="tooltip" data-bs-placement="top" title="Print">
                       <a class="btn btn-success" style="color: #fff;" href="{{ URL::to('/purchaseOrder/modalRecommendedPurchaseOrder/'.$purchaseOrder->id.'?print=1')}}" target="_blank"><i class="fa fa-print"></i></a>
                    </span>
                    
                    @php
                    $poRelationCount = $purchaseOrder->purchaseBill->count();
                    @endphp
                    @if($poRelationCount == 0)
                    <span class="tool-tip1" data-bs-toggle="tooltip" data-bs-placement="top" title="Delete">
                      <button class="btn btn-danger" data-bs-toggle="modal" onclick="deleteModal('{{$purchaseOrder->id}}')">
                        <i class="fa fa-trash"></i>
                      </button>
                    </span>
                    @endif
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

  function deleteModal(id){
    $('#deletepo').modal('show');
    $('#deletePOFORM').attr('action', "{{ url('/purchaseOrder/deleteRecommendedPo') }}" + '/' +id);
  }

  function updatepo(id){
    location.href = "{{ url('/purchaseOrder/viewRecommendedPo') }}" + '/' +id;
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
		
		$('#dataTable_filter').append( '<br><label>Date From: <input type="date" id="date-from" class="form-control" type="text" placeholder="Search pending products" data-index="" /></label>');
		$('#dataTable_filter').append( '<br><label>Date To: <input type="date" id="date-to" class="form-control" type="text" placeholder="Search pending products" data-index="" /></label>');
		
		
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