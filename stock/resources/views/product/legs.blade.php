@extends('layouts.app')

@section('content')

    <div class="row mx-3 my-2">
      <h2>Legs</h2>
      
      @hasrole('admin')
      <div class="col">
			<a class="btn btn-success float-end" style="color: #fff; margin-left: 5px;" data-bs-toggle="modal" data-bs-target="#modalImportExcel">Import Excel</a>
			<!--<a class="btn btn-success float-end" style="color: #fff; margin-left: 5px;" href="{{ url('/product/exportLegCsv')}}">Download CSV</a>
			<a class="btn btn-primary float-end" style="color: #fff; margin-left: 5px;" href="{{ url('/product/leg_create')}}">Add Leg</a>-->
      </div>
      @endhasrole
	  
	  @hasrole('factory')
	  <div class="col">
	  <a class="btn btn-success float-end" style="color: #fff; margin-left: 5px;" data-bs-toggle="modal" data-bs-target="#modalImportExcel">Import Excel</a>
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
                <th>Leg Design</th>
                <th>Set</th>
                <th width="200">Size</th>
                <th>Price</th>
                <th>Options</th>
              </tr>
            </thead>
            <tbody>
            	@if(isset($legs)) @foreach($legs as $key => $leg)
				
				 
              <tr>
				<form method="POST" >
				@csrf
					<td>{{$leg->product->code}}</td>
					<td onmouseover="fetchImage(this,'{{$leg->product->code}}')"></td>
					<td>{{$leg->product->name}}</td>
					<td>
						<input name="id" type="hidden" class="leg_id" value="{{$leg->id}}" />
						<select name="leg_design" class='form-control' onchange="changeLeg(this)" class="leg_design">
							<option value="">Select Leg Design</option>
							<option value="Bridge" {{ ($leg->leg_design == "Bridge")? 'selected':'' }} >Bridge</option>
							<option value="Island" {{ ($leg->leg_design == "Island")? 'selected':'' }} >Island</option>
							<option value="Other" {{ ($leg->leg_design == "Other")? 'selected':'' }} >Other</option>
						</select>
					</td>
					<td><input name="qty" type="text" class="leg_qty form-control" value="{{$leg->qty}}" onchange = "saveLeg(this)" /></td>
					<td>
						<input style="width:60px;display:inline;" name="height" type="number" class="leg_size form-control" value="{{$leg->height}}" onchange = "saveLeg(this)" />
						<input style="width:60px;display:inline;" name="width" type="number" class="leg_size form-control" value="{{$leg->width}}" onchange = "saveLeg(this)" />
						<input style="width:60px;display:inline;" name="depth" type="number" class="leg_size form-control" value="{{$leg->depth}}" onchange = "saveLeg(this)" />
					</td>
					<td><input name="price" type="number" class="leg_price form-control" value="{{$leg->price}}" onchange = "saveLeg(this)" /></td>
					<td>
						
					</td>
                </form>
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
          <form method="POST" id="modalImportExcelForm" action="{{ url('/product/importLegsCSV') }}" enctype="multipart/form-data">
          @csrf
          <div class="modal-body pb-0">
            <input type="file" name="importCSV" id="importCSV" />
            <p class="mt-3"><a href="{{ Storage::disk('s3')->url('stock/legsSample.xlsx') }}">Download Sample import file</a></p>
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

  
  $(function () {
  $('[data-bs-toggle="tooltip"]').tooltip()
  });

  function deleteModal(id)
    {
      $('#deleteProduct').modal('show');
      $('#deleteProductForm').attr('action', "{{ url('/product/leg_delete') }}" + '/' +id);
    } 
 
  function updateProduct(id)
    {
      location.href = "{{ url('/product/leg_update') }}" + '/' +id;
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
  
  function changeLeg(obj){
	  if($(obj).val() == 'Bridge'){
		$(obj).parent().parent().find('.leg_qty').val('2 Pairs');
		$(obj).parent().parent().find('.leg_qty').attr('readonly','true');
	  }
	  if($(obj).val() == 'Island'){
		  $(obj).parent().parent().find('.leg_qty').removeAttr('readonly');
		  if($(obj).parent().parent().find('.leg_qty').val() == "" || $(obj).parent().parent().find('.leg_qty').val() == "2 Pairs"){
				$(obj).parent().parent().find('.leg_qty').val('Set of 4');
		  }
	  }
	  if($(obj).val() == 'Other'){
		  $(obj).parent().parent().find('.leg_qty').removeAttr('readonly');
		  if($(obj).parent().parent().find('.leg_qty').val() == "" || $(obj).parent().parent().find('.leg_qty').val() == "2 Pairs"){
				$(obj).parent().parent().find('.leg_qty').val('Set of 4');
		  }
	  }
	  
		data = $(obj).parent().parent().find('form').serialize();
		$.ajax({
			'url': "{{ url('/product/updateLegs') }}",
			'method': 'POST',
			'data': data,
			success:function(r){
				
			}
		});
  }
  
	function saveLeg(obj){
		data = $(obj).parent().parent().find('form').serialize();
		$.ajax({
			'url': "{{ url('/product/updateLegs') }}",
			'method': 'POST',
			'data': data,
			success:function(r){
				
			}
		});
	}
    
</script>
@endsection