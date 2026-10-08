@extends('layouts.app')

@section('content')

    <div class="row mx-3 my-2">
      <h2>Purchase Order</h2>
      <div class="col">
        <a class="btn btn-success float-end" style="color: #fff; margin-left: 5px;" data-bs-toggle="modal" data-bs-target="#mydateModal"  href="{{ url('/supplier-dashboard/exportcsv')}}">Download CSV</a>
      </div>
    </div>
    
    <div class="card mb-3">
      <div class="card-body">
        <div class="table-responsive">
          <table class="table table-bordered" id="dataTable" width="100%" cellspacing="0">
            <thead>
              <tr>
                <th>ID</th>
                <th>PO No.</th>
                <th>Supplier Ref. </th>
                <th>PO Date</th>
                <th>Delivery Date</th>
                <th>Total Quantity</th>
                <th>Total Amount</th>
                <th>Status</th>
                <th style="min-width:120px;">Pending Amount</th>
                <th>All Products</th>
                <th style="min-width:120px;">Options</th>
                <th style="display:none"></th>
              </tr>
            </thead>
            <tbody>
              @if(isset($purchaseOrder)) @foreach($purchaseOrder as $key => $purchaseOrder)
              <tr>
                <td>{{$purchaseOrder->id}}</td>
                <td>{{$purchaseOrder->pono}}</td>
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
                    (₹){{$potable->remaining_amount}}<br>
                 
                  @endforeach
                </td>
                <td>
                  @foreach($purchaseOrder->poTable as $potable)
                    {{$potable->product->name}} <br>
                  @endforeach
                </td>
				
				        <td>
                    <div class="d-inline-flex flex-wrap gap-1 align-items-center">
                    @if($purchaseOrder->supplier_status != 1)
                    <span class="tool-tip1" data-bs-toggle="tooltip" data-bs-placement="top" title="Accept" id="{{$purchaseOrder->id}}_po" >
                      <button type="button" class="btn btn-info btn-sm" onclick="acceptePo('{{$purchaseOrder->id}}')">
                        <i class="fa fa-check"></i>
                      </button>
                    </span>
                    @endif
                    @if($purchaseOrder->address_option==100)
                      <span class="tool-tip1" data-bs-toggle="tooltip" data-bs-placement="top" title="View">
                        <a class="btn btn-primary btn-sm" style="color: #fff;" href="{{url('uploads/monthend'.$purchaseOrder->pdf_path)}}" target="_blank"><i class="fa fa-eye"></i></a>
                      </span>
                      <span class="tool-tip1" data-bs-toggle="tooltip" data-bs-placement="top" title="Print">
                        <a class="btn btn-success btn-sm" style="color: #fff;" href="{{ url('uploads/monthend'.$purchaseOrder->pdf_path)}}" target="_blank"><i class="fa fa-print"></i></a>
                      </span>
                    @else
                      <span class="tool-tip1" data-bs-toggle="tooltip" data-bs-placement="top" title="View">
                        <a class="btn btn-primary btn-sm" style="color: #fff;" href="{{ URL::to('/supplier-dashboard/service-modal/'.$purchaseOrder->id)}}" target="_blank"><i class="fa fa-eye"></i></a>
                      </span>
                      <span class="tool-tip1" data-bs-toggle="tooltip" data-bs-placement="top" title="Print">
                        <a class="btn btn-success btn-sm" style="color: #fff;" href="{{ URL::to('/supplier-dashboard/service-modal/'.$purchaseOrder->id.'?print=1')}}" target="_blank"><i class="fa fa-print"></i></a>
                      </span>
                    @endif
                    @if($purchaseOrder->supplier_status == 1 && $purchaseOrder->status != 1)
                    <span class="tool-tip1" data-bs-toggle="tooltip" data-bs-placement="top" title="Raise Invoice">
                       <a class="btn btn-success btn-sm" style="color: #fff;" href="{{ URL::to('/supplier-dashboard/raise-Serviceinvoice/'.$purchaseOrder->id)}}" target="_blank"><i class="fa fa-clipboard"></i> Raise Invoice</a>
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
              <form method="POST" action="{{ url('/supplier-dashboard/exportcsv') }}">
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
  });

  
  
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

  function acceptePo(id){
    window.location.href = "{{ url('/supplier-dashboard/accept-service-order') }}" + '/' +id;
    // $.ajax({
    //   'url': "{{ url('/supplier-dashboard/accept-purchase-order') }}" + '/' +id,
    //   'method': 'get',
    //   success:function(r){
    //     if(r.states == true) {
    //       $("#"+id+ "_po").remove();
    //     }
    //     alert(r.msg);
    //   }
    // });
  }

</script>
<!-- Script end -->

@endsection