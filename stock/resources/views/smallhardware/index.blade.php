@extends('layouts.app')

@section('content')

      <!-- first row -->
      <div class="row mx-3 my-2">
        <h2>Small Hardwares</h2>
        <div class="col">
          <div class="float-end" style="width:200px;margin-left: 5px;">
            <select class="selectpicker" data-live-search="true" onchange="reDir(this.value)">
              @foreach($suppliers as $supplier)
                <option {{ ($supplier_id == $supplier->id)?'selected':'' }} value="{{$supplier->id}}">{{$supplier->c_name}}</option>
              @endforeach
            </select>
          </div>
          <a class="btn btn-success float-end" style="color: #fff; margin-left: 5px;" data-bs-toggle="modal" data-bs-target="#modalImportExcel">Import Excel</a>
          <a class="btn btn-success float-end" style="color: #fff; margin-left: 5px;" data-bs-toggle="modal" data-bs-target="#modalUpdatePouchPrice">Update Pouch Price for all</a>
        </div>
      </div>

      <div class="card mb-3">
        <div class="card-body">
          <div class="table-responsive">
            <table class="table table-bordered" id="dataTables" width="100%" cellspacing="0">
              <thead>
                <tr>
                  <th>Prodcut Code</th>
				          <th>Image</th>
                  <th>Prodcut Name</th>
                  <th>Dust cover size</th>
                  <th>Dust cover price</th>
				          <th>Pouch</th>
                  <th>Pouch Price</th>
                </tr>
              </thead>
              <tbody>
              	@if(isset($smallhardware)) @foreach($smallhardware as $key => $smallhardware)
				
                <tr>
				<form method="POST" action="{{ url('/smallhardware/updateSmallhardware') }}" enctype="multipart/form-data" >
				@csrf
                  <td>{{$smallhardware->product->code}}</td>
				          <td onmouseover="fetchImage(this,'{{$smallhardware->product->code}}')"></td>
                  <td>{{$smallhardware->product->name}}</td>
                  <td>
					          <input name="id" value="{{$smallhardware->id}}" type="hidden">
				            <input name="dust_cover_size" value="{{$smallhardware->dust_cover_size}}" class="dust_cover_size form-control" onchange = "savePackage(this)" >
                  </td>
                  <td><input name="dust_cover_price" value="{{$smallhardware->dust_cover_price}}" class="dust_cover_price form-control" onchange = "savePackage(this)"></td>
				          <td><input name="pouch" value="{{$smallhardware->pouch}}" class="pouch form-control" onchange = "savePackage(this)" ></td>
                  <td><input name="pouch_price" value="{{$smallhardware->pouch_price}}" class="pouch_price form-control" onchange = "savePackage(this)"></td>
                  
				  </form>
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
          <form method="POST" id="modalImportExcelForm" action="{{ url('/smallhardware/importCSV') }}" enctype="multipart/form-data">
          @csrf
            <div class="modal-body pb-0">
              <input type="file" name="importCSV" id="importCSV" />
              <p class="mt-3"><a href="{{ url('/stock/storage/smallhardwareSample.xlsx') }}">Download Sample import file</a></p>
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

    <!-- MODAL FOR Pouch Price EXCEL -->
    <div class="modal fade" id="modalUpdatePouchPrice" role="dialog">
      <div class="modal-dialog">
        <div class="modal-content">
          <div class="modal-header">
            <h4 class="modal-title"> Upload Excel File</h4>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <form method="POST" id="modalUpdatePouchPriceForm" action="{{ url('/smallhardware/updatePouchPrice') }}" enctype="multipart/form-data">
          @csrf
            <div class="modal-body pb-0">
              <input type="number" step="any" name="pouch_price" />
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


<!-- Scripts Start For Hardware Delete/View -->
<script type="text/javascript">

  $(function () {
	$('[data-bs-toggle="tooltip"]').tooltip();
	
	// $('.no_of_boxes').each(function(){
		// if($(this).val() == 1){
			// $(this).parent().parent().find('.box2_height').attr('readonly','').val('');
			// $(this).parent().parent().find('.box2_width').attr('readonly','').val('');
			// $(this).parent().parent().find('.box2_depth').attr('readonly','').val('');
			// $(this).parent().parent().find('.box2_sqinch').attr('readonly','').val('');
			// $(this).parent().parent().find('.box2_ply').attr('readonly','').val('');
		// }
	// });
  });
	
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
  
	
  
	function savePackage(obj){
		data = $(obj).parent().parent().find('form').serialize();
		$.ajax({
			'url': "{{ url('/smallhardware/updateSmallhardware') }}",
			'method': 'POST',
			'data': data,
			success:function(r){
			
			}
		});
	}

  function reDir(v){
    window.location.href = "{{url('/smallhardware/')}}/" + v;
  }

</script>
<!-- Scripts End -->

@endsection
