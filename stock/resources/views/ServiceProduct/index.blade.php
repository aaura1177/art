@extends('layouts.app')

@section('content')

    <div class="row mx-3 my-2">
      <h2>Service Products List</h2>
      
      @hasrole('admin')
      <div class="col">
          <!-- <a class="btn btn-success float-end" style="color: #fff; margin-left: 5px;" data-bs-toggle="modal" data-bs-target="#mydateModal"  href="{{ url('/product/exportcsv')}}">Download CSV</a> -->
          <a class="btn btn-success float-end" style="color: #fff; margin-left: 5px;" data-bs-toggle="modal" data-bs-target="#modalImportExcel">Import Excel</a>
          <a class="btn btn-primary float-end" style="color: #fff; margin-left: 5px;" href="{{ url('/service/create/product')}}">Add Product</a>
      </div>
      @endhasrole
	  
	  @hasrole('factory')
	  <div class="col">
    <a class="btn btn-success float-end" style="color: #fff; margin-left: 5px;" href="{{ url('/product/families')}}">Families</a>
		<a class="btn btn-success float-end" style="color: #fff; margin-left: 5px;" data-bs-toggle="modal" data-bs-target="#modalPrint">Print</a>
	  </div>
	  @endhasrole
    </div>
    
    <div class="card mb-3">
      <div class="card-body">
        <div class="table-responsive">
          <table class="table table-bordered" id="dataTables" width="100%" cellspacing="0">
            <thead>
              <tr>
                <th>Code</th>                
                <th>Image</th>
                <th>Name</th>
                <th>Service Category</th>
             
                <th>Quantity</th>
                @hasrole('admin')
                <th>Options</th>
                @endhasrole
              </tr>
            </thead>
            <tbody>
            	@if(isset($products)) @foreach($products as $key => $product)
              <tr>
                <td>{{$product->code}}</td>
                <td onmouseover="fetchImage(this,'{{$product->code}}')"></td>
                <td>{{$product->name}}</td>
                <td>{{$product->serviceCategory->name}}</td>
                <td>{{$product->quantity}}</td>
				
                @hasrole('admin')
                <td>
                  <span class="tool-tip1" data-bs-toggle="tooltip" data-bs-placement="top" title="Edit">
              			<button class="btn btn-info" onclick="updateProduct('{{$product->id}}')">
              				<i class="fa fa-edit"></i>
              			</button>
                  </span>
                

               
                  <span class="tool-tip1" data-bs-toggle="tooltip" data-bs-placement="top" title="Delete">
                    <button class="btn btn-danger" data-bs-toggle="modal" onclick="deleteModal('{{$product->id}}')">
                      <i class="fa fa-trash"></i>
                    </button>
                  </span>
                
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
          <form method="POST" id="modalImportExcelForm" action="{{ url('/product/importCSV') }}" enctype="multipart/form-data">
          @csrf
            <div class="modal-body pb-0">
              <input type="file" name="importCSV" id="importCSV" />
              <p class="mt-3"><a href="{{ url('/stock/storage/productSample.xlsx') }}">Download Sample import file</a></p>
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
<!-- MODAL FOR IMPORT Finishing EXCEL -->
<div class="modal fade" id="modalFinishingExcel" role="dialog">
      <div class="modal-dialog">
        <div class="modal-content">
          <div class="modal-header">
            <h4 class="modal-title"> Upload finishing Excel File</h4>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <form method="POST" id="modalFinishingExcelForm" action="{{ url('/product/importCSVfinishing') }}" enctype="multipart/form-data">
          @csrf
            <div class="modal-body pb-0">
              <input type="file" name="finishingCSV" id="finishingCSV" />
              <p class="mt-3"><a href="{{ url('/stock/storage/productSample.xlsx') }}">Download Sample import file</a></p>
              <p class="mt-4 mb-0">Are You sure you want to Upload this?</p>
            </div>
            <div class="modal-footer">
              <button type="button" class="btn btn-default" data-bs-dismiss="modal">No</button>
              <button type="sumit" id="modalFinishingExcelForm" onclick="return validate()" class="btn btn-success">Yes</button>
            </div>
          </form>
        </div>
      </div>
    </div>

	<!-- MODAL FOR Print -->
    <div class="modal fade" id="modalPrint" role="dialog">
      <div class="modal-dialog">
        <div class="modal-content">
          <div class="modal-header">
            <h4 class="modal-title"> Print</h4>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <form method="POST" id="modalPrintForm" action="{{ url('/product/printList') }}" enctype="multipart/form-data">
          @csrf
            <div class="modal-body pb-0">
				<label>Select Code type</label>
				<select class="selectpicker" name="code" required>
					<option value="">Select Code type</option>
					<option value="All">All</option>
					<option value="IN">IN</option>
					<option value="BO">BO</option>
					<option value="AH">AH</option>
					<option value="ASB">ASB</option>
				</select>
            </div>
            <div class="modal-footer">
              <button type="submit" id="modalImportExcelForm" onclick="return printpdf()" class="btn btn-success">Print</button>
              <button type="submit" id="DownloadCSV" onclick="return downloadcsv()" class="btn btn-success">Download</button>
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

  
  $(function () {
  $('[data-bs-toggle="tooltip"]').tooltip()
  });

  function deleteModal(id)
    {
      $('#deleteProduct').modal('show');
      $('#deleteProductForm').attr('action', "{{ url('/service/product/delete') }}" + '/' +id);
    } 
 
  function updateProduct(id)
    {
      location.href = "{{ url('/service/product/view') }}" + '/' +id;
    } 
	
	function downloadcsv(){
	  $("#modalPrintForm").attr("action","{{ url('/product/exportCodeWiseExcel') }}");
	  return true;
	}
	
	function printpdf(){
	  $("#modalPrintForm").attr("action","{{ url('/product/printList') }}");
	  return true;
	}
  
    function fetchImage(obj, product) {
    if ($(obj).html() == "") {
        // Construct the image URL dynamically
        var img = "{{ asset('images') }}/" + product + "/" + product + "-1.jpg";

        // Insert an image with a modal trigger
        $(obj).html('<a href="javascript:void(0)" data-bs-toggle="modal" data-bs-target="#viewImage" onclick="updateSrc(\'' + product + '\')"><img class="img-thumbnail img-fluid product-img-100" src="' + img + '" alt="No Image" /></a>');
    }
}

  function updateSrc(product){
	  var img = "{{ asset('uploads/allproducts/') }}/"+product+"/"+product+"-1.jpg";
	  $("#bigProImage").attr('src',img);
	  $("#viewImage").find("h4").html(product);
  }
    
</script>
@endsection