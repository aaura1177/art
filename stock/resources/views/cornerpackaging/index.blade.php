@extends('layouts.app')

@section('content')

      <!-- first row -->
      <div class="row mx-3 my-2">
        <h2>Packaging by Staff</h2>
        <div class="col">
           <a class="btn btn-primary float-end" style="color: #fff;" href="{{ url('/cornerpackaging/create')}}">Add Corner Packaging</a>
        </div>
      </div>

      <div class="card mb-3">
        <div class="card-body">
          <div class="table-responsive">
            <table class="table table-bordered" id="dataTables" width="100%" cellspacing="0">
              <thead>
                <tr>
                  <th>Month</th>
				  <th>Po No.</th>
                  <th>Options</th>
                </tr>
              </thead>
              <tbody>
              	@if(isset($cornerpackaging)) @foreach($cornerpackaging as $key => $packaging)
                <tr>
                  <td>{{$packaging->month}}</td>
                  <td>{{$packaging->po_no}}</td>
                  
                  <td>
					@hasrole('admin')
                      <!--<span class="tool-tip1" data-bs-toggle="tooltip" data-bs-placement="top" title="Create PO">
                        <a class="btn btn-success" href="{{ url('/packaging/create_po') }}">
							<i class="fa fa-clipboard"></i>
						</a>
                      </span>-->
					@endhasrole
					
					  <span class="tool-tip1" data-bs-toggle="tooltip" data-bs-placement="top" title="Download">
						<a class="btn btn-primary" href="#" data-bs-toggle="modal" data-bs-target="#myCornerBill" onclick="cornerBill({{$packaging->id}}, '{{$packaging->po_no}}')">
							<i class="fa fa-download"></i>
						</a>
					  </span>
					  <span class="tool-tip1" data-bs-toggle="tooltip" data-bs-placement="top" title="Edit">
						<a class="btn btn-primary" href='{{url("/cornerpackaging/view/$packaging->id")}}' >
							<i class="fa fa-edit"></i>
						</a>
					  </span>
					  
					  <span class="tool-tip1" data-bs-toggle="tooltip" data-bs-placement="top" title="Delete">
                        <button class="btn btn-danger" onclick="deleteModal('{{$packaging->id}}')">
							<i class="fa fa-trash"></i>
						</button>
                      </span>
                  </td>
                </tr>
                @endforeach @endif
              </tbody>
            </table>
          </div>
        </div>
      </div>
	  <!-- MODAL FOR DELETE -->
    <div class="modal fade" id="deletePackaging" role="dialog">
      <div class="modal-dialog">
        <div class="modal-content">
          <div class="modal-header">
            <h4 class="modal-title">Delete Confirmation</h4>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>  
          </div>
          <form id="deletePackageForm" method="POST" action="">
          @csrf  
            <div class="modal-body">
              <p>Are You sure you want to Delete this?</p>
            </div>
            <div class="modal-footer">
              <button type="button" class="btn btn-default" data-bs-dismiss="modal" autofocus="">No</button>
              <button type="submit" id="deletePackageForm" class="btn btn-danger">Yes</button>
            </div>
          </form>  
        </div>
      </div>
    </div>
	
	<!-- MODAL FOR IMPORT EXCEL -->
    <div class="modal fade" id="modalImportExcel" role="dialog">
      <div class="modal-dialog">
        <div class="modal-content">
          <div class="modal-header">
            <h4 class="modal-title"> Upload Excel File</h4>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <form method="POST" id="modalImportExcelForm" action="{{ url('/packaging/importCSV') }}" enctype="multipart/form-data">
          @csrf
            <div class="modal-body pb-0">
              <input type="file" name="importCSV" id="importCSV" />
              <p class="mt-3"><a href="{{ url('/stock/storage/packagingSample.xlsx') }}">Download Sample import file</a></p>
			  <p class="mt-4 mb-0">Are You sure you want to Upload this?</p>
            </div>
            <div class="modal-footer">
              <button type="button" class="btn btn-default" data-bs-dismiss="modal">No</button>
              <button type="sumit" id="modalImportExcelForm" onclick="return validate()" class="btn btn-success">Yes</button>
            </div>
          </form>
        </div>
      </div>
    </div>
	
	<!-- MODAL FOR Image View -->
    <div class="modal fade" id="viewImage" role="dialog">
      <div class="modal-dialog">
        <div class="modal-content">
          <div class="modal-header">
            <h4 class="modal-title"></h4>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
			<div class="modal-body pb-0">
				<img style="max-width:100%" id="bigProImage" alt="No Image" src="" />
			</div>
        </div>
      </div>
    </div>
	
	<!-- MODAL FOR PO -->
    <div class="modal fade" id="myCornerBill" role="dialog">
      <div class="modal-dialog">
        <div class="modal-content">
          <div class="modal-header">
            <h4 class="modal-title">PO Generation</h4>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>  
          </div>
          <form method="POST" action="{{ url('/cornerpackaging/download') }}">
          @csrf
            <div class="modal-body">
                <div class="row">
					<input id="iifhb" name="id" type="hidden">
					<div class="col-6">
						<label>Date</label>
						<input type="date" name="date" class="form-control">
						<label>Select Suppliers</label>
						<select class="selectpicker" data-live-search="true" onchange="changeDetails(this)" name="supplier" required>
							<option value="" disabled>Select Suppliers</option>
							@if(isset($supplier)) @foreach($supplier as $key => $supplier)
								<option value="{{$supplier->id}}">
								{{$supplier->c_name}}
								</option>
							@endforeach @endif
						</select>
					</div>
					<div class="col-6">
					  <label class="control-label">{{ __('Po No.') }}</label>
					  <input id="po_no" type="text" class="form-control" name="po_no" step="any" value="O/{{($companyDetails->opo_no + 1 < 10)?'00':''}}{{$companyDetails->opo_no + 1}}" required />
					</div>
                </div>
            </div>
            <div class="modal-footer">
              <button type="submit" onclick="return submitHardwareBill()" class="btn btn-success">Download</button>
            </div>
          </form>
        </div>
      </div>
    </div>
@endsection

@section('footer')


<!-- Scripts Start For Hardware Delete/View -->
<script type="text/javascript">

  $(function () {
  $('[data-bs-toggle="tooltip"]').tooltip()
  });

  function updateCourier(id){

     location.href = "{{ url('/packaging/view') }}" + '/' +id;
  }
  
  function deleteModal(id)
    {
      $('#deletePackaging').modal('show');
      $('#deletePackageForm').attr('action', "{{ url('/cornerpackaging/delete') }}" + '/' +id);
    } 
	
	function fetchImage(obj,product){
	  if($(obj).html() == ""){
		var img = "{{ asset('uploads/allproducts/') }}/"+product+"/"+product+"-1.jpg";
		$(obj).html('<a href="javascript:void(0)" data-bs-toggle="modal" data-bs-target="#viewImage" onclick="updateSrc(\''+product+'\')"><img class="img-thumbnail img-fluid product-img-100" src="'+img+'" alt="No Image" /></a>');
	  }
  }
  
  function updateSrc(product){
	  var img = "{{ asset('uploads/allproducts/') }}/"+product+"/"+product+"-1.jpg";
	  $("#bigProImage").attr('src',img);
	  $("#viewImage").find("h4").html(product);
  }
  
  function cornerBill(inv_id,po_no){
	   $("#iifhb").val(inv_id);
	   if(po_no){
		   $("#po_no").val(po_no);
	   }
  }

</script>
<!-- Scripts End -->

@endsection
