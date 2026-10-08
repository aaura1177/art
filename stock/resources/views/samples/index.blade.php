@extends('layouts.app')

@section('content')

    <div class="row mx-3 my-2">
      <h2>Samples List</h2>
      
      @hasrole('admin')
      <div class="col">
          <!-- <a class="btn btn-success float-end" style="color: #fff; margin-left: 5px;" data-bs-toggle="modal" data-bs-target="#mydateModal"  href="{{ url('/product/exportcsv')}}">Download CSV</a> -->
          
        <a class="btn btn-success float-end" style="color: #fff; margin-left: 5px;" data-bs-toggle="modal" data-bs-target="#modalImportExcel">Import Excel</a>
        <a class="btn btn-success float-end" style="color: #fff; margin-left: 5px;" href="{{ url('/samples/exportcsv')}}">Download CSV</a>
        <a class="btn btn-primary float-end" style="color: #fff; margin-left: 5px;" href="{{ url('/samples/create')}}">Add Sample</a>
      </div>
      @endhasrole
	  
	  @hasrole('factory')
	  <div class="col">
	  </div>
	  @endhasrole
    </div>
    
    <div class="card mb-3">
      <div class="card-body">
        <div class="table-responsive">
          <table class="table table-bordered" id="emissionTable" width="100%" cellspacing="0">
            <thead>
              <tr>
                <th>Code</th>                
                <th>Image</th>
                <th>Name</th>
                <th>Category</th>
                <th>Sub-Category</th>
                @hasrole('admin')
                <th>Options</th>
                @endhasrole
              </tr>
            </thead>
            <tbody>
            	@if(isset($samples)) @foreach($samples as $key => $sample)
              <tr>
                <td>{{$sample->code}}</td>
                <td onmouseover="fetchImage(this,'{{$sample->imageURL}}')"></td>
                <td>{{$sample->name}}</td>
                <td>{{$sample->category->name}}</td>
                <td>{{$sample->subCategory->name}}</td>
				
                @hasrole('admin')
                <td>
					@if($sample->status != 1)
                  <span class="tool-tip1" data-bs-toggle="tooltip" data-bs-placement="top" title="Edit">
              			<button class="btn btn-info" onclick="updateProduct('{{$sample->id}}')">
              				<i class="fa fa-edit"></i>
              			</button>
                  </span>
                 
                  
                  <span class="tool-tip1" data-bs-toggle="tooltip" data-bs-placement="top" title="Delete">
                    <button class="btn btn-danger" data-bs-toggle="modal" onclick="deleteModal('{{$sample->id}}')">
                      <i class="fa fa-trash"></i>
                    </button>
                  </span>
					@else
					Product Created <br><b>{{$sample->prod_code}}</b>
					@endif
                </td>
                @endhasrole
              </tr>
              @endforeach @endif
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <!-- MODAL FOR DELETE -->
    <div class="modal fade" id="deleteProduct" role="dialog">
      <div class="modal-dialog">
        <div class="modal-content">
          <div class="modal-header">
            <h4 class="modal-title">Delete Confirmation</h4>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>  
          </div>
          <form id="deleteProductForm" method="POST" action="">
          @csrf  
            <div class="modal-body">
              <p>Are You sure you want to Delete this?</p>
            </div>
            <div class="modal-footer">
              <button type="button" class="btn btn-default" data-bs-dismiss="modal" autofocus="">No</button>
              <button type="submit" id="deleteProductForm" class="btn btn-danger">Yes</button>
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
          <form method="POST" id="modalImportExcelForm" action="{{ url('/samples/importCSV') }}" enctype="multipart/form-data">
          @csrf
            <div class="modal-body pb-0">
              <input type="file" name="importCSV" id="importCSV" />
              <p class="mt-3"><a href="{{ Storage::disk('s3')->url('stock/productSample.xlsx') }}">Download Sample import file</a></p>
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
@endsection

@section('footer')
<script type="text/javascript">

$('#emissionTable').DataTable({
        "ordering": false, // This enables ordering globally
        
    });
  $(function () {
  $('[data-bs-toggle="tooltip"]').tooltip()
  });

  function deleteModal(id)
    {
      $('#deleteProduct').modal('show');
      $('#deleteProductForm').attr('action', "{{ url('/samples/delete') }}" + '/' +id);
    } 
 
  function updateProduct(id)
    {
      location.href = "{{ url('/samples/view') }}" + '/' +id;
    } 
	
	function downloadcsv(){
	  $("#modalPrintForm").attr("action","{{ url('/samples/exportCodeWiseExcel') }}");
	  return true;
	}
	
	function printpdf(){
	  $("#modalPrintForm").attr("action","{{ url('/samples/printList') }}");
	  return true;
	}
  
  function fetchImage(obj, product) {
    if ($(obj).html() == "") {
        var imgBase = "{{ rtrim(Storage::disk('s3')->url('stock/samples'), '/') }}";
        var img = imgBase + "/" + product;
        $(obj).html(
            '<a href="javascript:void(0)" data-bs-toggle="modal" data-bs-target="#viewImage" onclick="updateSrc(\'' + product + '\')">' +
            '<img class="img-thumbnail img-fluid product-img-100" src="' + img + '" alt="No Image" />' +
            '</a>'
        );
    }
}

function updateSrc(product) {
    var imgBase = "{{ rtrim(Storage::disk('s3')->url('stock/samples'), '/') }}";
    var img = imgBase + "/" + product;
    $("#bigProImage").attr('src', img);
    $("#viewImage").find("h4").html(product);
}

    
</script>
@endsection
