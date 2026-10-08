@extends('layouts.app')

@section('content')

    <div class="row mx-3 my-2">
      <h2>Products Families</h2>
      
      @hasrole('admin')
      <div class="col">
          <a class="btn btn-success float-end" style="color: #fff; margin-left: 5px;" data-bs-toggle="modal" data-bs-target="#modalGrouping">Create Product Family</a>
		  <a class="btn btn-success float-end" style="color: #fff; margin-left: 5px;" data-bs-toggle="modal" data-bs-target="#modalImportExcel">Import Excel</a>
      </div>
      @endhasrole

       @hasrole('factory')
      <div class="col">
      <a class="btn btn-success float-end" style="color: #fff; margin-left: 5px;" data-bs-toggle="modal" data-bs-target="#modalGrouping">Create Product Family</a>
      </div>
      @endhasrole
	 
    </div>
    
    <div class="card mb-3">
      <div class="card-body">
        <div class="table-responsive">
          <table class="table table-bordered" id="dataTables" width="100%" cellspacing="0">
			<thead>
              <tr>
                <th></th>
                <th>Code</th>
                <th>Parent</th>
                <th>Image</th>
                <th>Name</th>
                <th>Quantity</th>
                <th>Options</th>
              </tr>
            </thead>
			<tbody>
		  @if(isset($fam)) @foreach($fam as $pid => $f)
			
				
              <tr>
                <td ></td>
                <td ><strong>{{ $f['code'] }}</strong></td>
                <td ></td>
                <td onmouseover="fetchImage(this,'{{$f['code']}}')"></td>
                <td><strong>{{ $f['name'] }}</strong></td>
                <td><strong>{{ $f['quantity'] }}</strong></td>
                <td></td>
              </tr>
            
            	@if(isset($f['children'])) @foreach($f['children'] as $key => $product)
              <tr>
                <td></td>
                <td>{{$product->code}}</td>
                <td>{{ $f['code'] }}</td>
                <td onmouseover="fetchImage(this,'{{$product->code}}')"></td>
                <td>{{$product->name}}</td>
                <td>{{$product->quantity}}</td>
				<td>
					<span class="tool-tip1" data-bs-toggle="tooltip" data-bs-placement="top" title="Delete">
						<button class="btn btn-danger" data-bs-toggle="modal" onclick="deleteModal('{{$product->id}}','{{$pid}}')">
						  <i class="fa fa-trash"></i>
						</button>
					</span>
				</td>
              </tr>
              @endforeach @endif
			<tr>
				<td></td>
				<td></td>
				<td></td>
				<td></td>
				<td></td>
				<td></td>
				<td></td>
			</tr>
			<tr>
				<td></td>
				<td></td>
				<td></td>
				<td></td>
				<td></td>
				<td></td>
				<td></td>
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
          <form method="POST" id="modalImportExcelForm" action="{{ url('/product/importGrouping') }}" enctype="multipart/form-data">
          @csrf
            <div class="modal-body pb-0">
              <input type="file" name="importCSV" id="importCSV" />
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

	
	
	
	<!-- MODAL FOR Grouping -->
    <div class="modal fade" id="modalGrouping" role="dialog">
      <div class="modal-dialog">
        <div class="modal-content">
          <div class="modal-header">
            <h4 class="modal-title">Create Product Family</h4>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <form method="POST" id="modalPrintForm" action="{{ url('/product/createGrouping') }}" enctype="multipart/form-data">
          @csrf
            <div class="modal-body pb-0">
				<div class="col-12">
				  <label class="control-label">{{ __('Select Parent') }}</label> <a href="{{ url('/temporaryProduct/create')}}" target="_blank" style="float: right;"> (+New)</a>
				  <select type="text" class="selectpicker" data-live-search="true" onchange="changeDetails(this)" name="parent_id" id="selectProduct" required>
					<option selected>Select Product</option>
					@if(isset($products)) @foreach($products as $key => $product)
					  <option value="{{$product->id}}">
					  {{$product->code}} - {{$product->name}}
					  </option>
					@endforeach @endif
				  </select>
				</div>
				
				<div class="col-12 mt-3">
				  <label class="control-label">{{ __('Select Children') }}</label> <a href="{{ url('/temporaryProduct/create')}}" target="_blank" style="float: right;"> (+New)</a>
				  <select type="text" class="selectpicker" data-live-search="true" onchange="changeDetails(this)" name="child_id[]" id="selectProduct" required multiple>
					<option value=""  disabled>Select Multiple Products</option>
					@if(isset($products)) @foreach($products as $key => $product)
					  <option value="{{$product->id}}">
					  {{$product->code}} - {{$product->name}}
					  </option>
					@endforeach @endif
				  </select>
				</div>
            </div>
            <div class="modal-footer">
              <button type="submit" id="modalImportExcelForm" class="btn btn-success">Save</button>
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
	
	$('#dataTables').dataTable({
		"paging": false
	});
  function deleteModal(child_id,parent_id)
    {
      $('#deleteProduct').modal('show');
      $('#deleteProductForm').attr('action', "{{ url('/product/delete_family') }}" + '/' +child_id + '/' + parent_id);
    } 
 
  function updateProduct(id)
    {
      location.href = "{{ url('/product/view') }}" + '/' +id;
    } 
	
	function downloadcsv(){
	  $("#modalPrintForm").attr("action","{{ url('/product/exportCodeWiseExcel') }}");
	  return true;
	}
	
	function printpdf(){
	  $("#modalPrintForm").attr("action","{{ url('/product/printList') }}");
	  return true;
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
    
</script>
@endsection