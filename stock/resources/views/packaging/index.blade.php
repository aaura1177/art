@extends('layouts.app')

@section('content')

<!-- first row -->
<div class="row mx-3 my-2 align-items-center">
  <div class="col">
    <h2 class="mb-0">Packaging (Cartons)</h2>
  </div>
  <div class="col-auto">
    <a class="btn btn-warning" style="color: #fff; border-color:#f0ad4e;" data-bs-toggle="modal" data-bs-target="#modalUpdateQuantityExcel">Update Quantity</a>
    <a class="btn btn-success" style="color: #fff;" data-bs-toggle="modal" data-bs-target="#modalImportExcel">Import Excel</a>
    <a class="btn btn-primary" style="color: #fff;" href="{{ url('/packaging/exportExcel') }}">Download Excel</a>
  </div>
</div>

<div class="row mx-3 my-2 align-items-center">
  <div class="col-md-6 col-lg-5">
    <form method="GET" action="{{ url('/packaging') }}" class="d-flex flex-wrap align-items-center">
      <input type="hidden" name="per_page" value="{{ (int) request('per_page', 50) }}">
      <input
        type="text"
        name="code"
        class="form-control w-auto me-2"
        style="width: 220px;"
        placeholder="Search product code"
        value="{{ request('code') }}"
      />
      <button type="submit" class="btn btn-primary me-2">Search</button>
      <a href="{{ url('/packaging') }}" class="btn btn-secondary">Clear</a>
    </form>
  </div>
  <div class="col-md-6 col-lg-4 mt-2 mt-md-0">
    <form method="GET" action="{{ url('/packaging') }}" class="d-flex flex-wrap align-items-center">
      <input type="hidden" name="code" value="{{ request('code') }}">
      <label for="per_page" class="me-2 mb-0">Items:</label>
      <select id="per_page" name="per_page" class="form-select w-auto" onchange="this.form.submit()">
        @php $selectedPerPage = (int) request('per_page', 50); @endphp
        <option value="10" {{ $selectedPerPage === 10 ? 'selected' : '' }}>10</option>
        <option value="20" {{ $selectedPerPage === 20 ? 'selected' : '' }}>20</option>
        <option value="50" {{ $selectedPerPage === 50 ? 'selected' : '' }}>50</option>
        <option value="100" {{ $selectedPerPage === 100 ? 'selected' : '' }}>100</option>
      </select>
    </form>
  </div>
</div>

<div class="card mb-3">
  <div class="card-body">
    <div class="table-responsive">
      <table class="table table-bordered" id="packagingTable" width="100%" cellspacing="0">
        <thead>
          <tr>
            <th>Prodcut Code</th>
            <th>Image</th>
            <th>Prodcut Name</th>
            <th>Number of boxes</th>
            <th width="200">Box 1 Size</th>
            <th>Box 1 Type</th>
            <th>Box 1 Sq. Inches</th>
            <th>Box 1 Ply</th>
            <th width="200">Box 2 Size</th>
            <th>Box 2 Type</th>
            <th>Box 2 Sq. Inches</th>
            <th>Box 2 Ply</th>
            <th>Box 1 Quantity</th>
            <th>Box 2 Quantity</th>
          </tr>
        </thead>
        <tbody>
          @if(isset($packaging)) @foreach($packaging as $key => $packagingItem)
          @if(isset($packagingItem->product->code))
          <tr>
            <form method="POST" action="{{ url('/packaging/updatePackaging') }}" enctype="multipart/form-data">
              @csrf
              <td>{{$packagingItem->product->code}}</td>
              <td onmouseover="fetchImage(this,'{{$packagingItem->product->code}}')"></td>
              <td>{{$packagingItem->product->name}}</td>
              <td>
                <input name="id" value="{{$packagingItem->id}}" type="hidden">
                <select name="no_of_boxes" class="no_of_boxes form-control" onchange="changeNo(this)">
                  <option value="1" {{ ($packagingItem->no_of_boxes == 1)? 'selected':'' }}>1</option>
                  <option value="2" {{ ($packagingItem->no_of_boxes == 2)? 'selected':'' }}>2</option>
                </select>
              </td>
              <td>
                <input name="box1_height" value="{{$packagingItem->box1_height}}" class="box1_height form-control" style="width:50px;display:inline;" onchange="savePackage(this)">X<input name="box1_width" value="{{$packagingItem->box1_width}}" class="box1_width form-control" style="width:50px;display:inline;" onchange="savePackage(this)">X<input name="box1_depth" value="{{$packagingItem->box1_depth}}" class="box1_depth form-control" style="width:50px;display:inline;" onchange="savePackage(this)">
              </td>
              <td>
                <select name="box1_type" value="{{$packagingItem->box1_type}}" class="box1_type form-control" onchange="savePackage(this)">
                  <option value="Standard Box" {{ ($packagingItem->box1_type == 'Standard Box')? 'selected':'' }}>Standard Box</option>
                  <option value="Lateral Box" {{ ($packagingItem->box1_type == 'Lateral Box')? 'selected':'' }}>Lateral Box</option>
                  <option value="Over Flap" {{ ($packagingItem->box1_type == 'Over Flap')? 'selected':'' }}>Over Flap</option>
                </select>
              </td>
              <td><input name="box1_sqinch" value="{{$packagingItem->box1_sqinch}}" class="box1_sqinch form-control" style="width:50px;" onchange="savePackage(this)" readonly=""></td>
              <td><input name="box1_ply" value="{{$packagingItem->box1_ply}}" class="box1_ply form-control" style="width:50px;" onchange="savePackage(this)"></td>
              <td><input name="box2_height" value="{{$packagingItem->box2_height}}" class="box2_height form-control" style="width:50px;display:inline;" onchange="savePackage(this)" {{ ($packagingItem->no_of_boxes > 1)? '':'readonly=""' }}>X<input name="box2_width" value="{{$packagingItem->box2_width}}" class="box2_width form-control" style="width:50px;display:inline;" onchange="savePackage(this)" {{ ($packagingItem->no_of_boxes > 1)? '':'readonly=""' }}>X<input name="box2_depth" value="{{$packagingItem->box2_depth}}" class="box2_depth form-control" style="width:50px;display:inline;" onchange="savePackage(this)" {{ ($packagingItem->no_of_boxes > 1)? '':'readonly=""' }}></td>
              <td>
                <select name="box2_type" value="{{$packagingItem->box2_type}}" class="box2_type form-control" onchange="savePackage(this)" {{ ($packagingItem->no_of_boxes > 1)? '':'readonly=""' }}>
                  <option value="Standard Box" {{ ($packagingItem->box2_type == 'Standard Box')? 'selected':'' }}>Standard Box</option>
                  <option value="Lateral Box" {{ ($packagingItem->box2_type == 'Lateral Box')? 'selected':'' }}>Lateral Box</option>
                  <option value="Over Flap" {{ ($packagingItem->box2_type == 'Over Flap')? 'selected':'' }}>Over Flap</option>
                </select>
              </td>
              <td><input name="box2_sqinch" value="{{$packagingItem->box2_sqinch}}" class="box2_sqinch form-control" style="width:50px;" onchange="savePackage(this)" readonly=""></td>
              <td><input name="box2_ply" value="{{$packagingItem->box2_ply}}" class="box2_ply form-control" style="width:50px;" onchange="savePackage(this)" {{ ($packagingItem->no_of_boxes > 1)? '':'readonly=""' }}></td>


             <td>
    <div class="qty-wrapper">
        <input type="number"
               name="box_1_qty"
               max="{{ $packagingItem->box_1_qty }}"
               min="0"
               value="{{ $packagingItem->box_1_qty }}"
               class="form-control"
               style="width:100px;">

        <div class="qty-icons mt-2">
            <!-- Box 1 - -->
            <button type="button" class="qty-btn"
                data-bs-toggle="modal"
                data-bs-target="#box1MinusModal_{{ $packagingItem->id }}">-</button>

            <!-- Box 1 + -->
            <button type="button" class="qty-btn"
                data-bs-toggle="modal"
                data-bs-target="#box1PlusModal_{{ $packagingItem->id }}">+</button>
        </div>
    </div>
</td>
  </form>

<td>
    <div class="qty-wrapper">
        <input type="number"
               name="box_2_qty"
               max="{{ $packagingItem->box_2_qty }}"
               min="0"
               value="{{ $packagingItem->box_2_qty }}"
               class="form-control"
               style="width:100px;">

        <div class="qty-icons mt-2">
            <!-- Box 2 - -->
            <button type="button" class="qty-btn"
                data-bs-toggle="modal"
                data-bs-target="#box2MinusModal_{{ $packagingItem->id }}">-</button>

            <!-- Box 2 + -->
            <button type="button" class="qty-btn"
                data-bs-toggle="modal"
                data-bs-target="#box2PlusModal_{{ $packagingItem->id }}">+</button>
        </div>
    </div>
</td>


<!-- =============== BOX 1 MINUS MODAL =============== -->
<div class="modal fade" id="box1MinusModal_{{ $packagingItem->id }}" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <form action="{{ route('packaging.updateBoxQty') }}" method="POST">
        @csrf
        <input type="hidden" name="packaging_id" value="{{ $packagingItem->id }}">
        <input type="hidden" name="type" value="box1_minus">

        <div class="modal-header">
          <h5 class="modal-title">Decrease Box 1 Quantity</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>

        <div class="modal-body text-center">
          <input type="number" name="qty" class="form-control w-50 mx-auto" min="1" value="1">
        </div>

        <div class="modal-footer">
          <button class="btn btn-success">Save</button>
        </div>
      </form>
    </div>
  </div>
</div>


<!-- =============== BOX 1 PLUS MODAL =============== -->
<div class="modal fade" id="box1PlusModal_{{ $packagingItem->id }}" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <form action="{{ route('packaging.updateBoxQty') }}" method="POST">
        @csrf
        <input type="hidden" name="packaging_id" value="{{ $packagingItem->id }}">
        <input type="hidden" name="type" value="box1_plus">

        <div class="modal-header">
          <h5 class="modal-title">Increase Box 1 Quantity</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>

        <div class="modal-body text-center">
          <input type="number" name="qty" class="form-control w-50 mx-auto" min="1" value="1">
        </div>

        <div class="modal-footer">
          <button class="btn btn-success">Save</button>
        </div>
      </form>
    </div>
  </div>
</div>


<!-- =============== BOX 2 MINUS MODAL =============== -->
<div class="modal fade" id="box2MinusModal_{{ $packagingItem->id }}" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <form action="{{ route('packaging.updateBoxQty') }}" method="POST">
        @csrf
        <input type="hidden" name="packaging_id" value="{{ $packagingItem->id }}">
        <input type="hidden" name="type" value="box2_minus">

        <div class="modal-header">
          <h5 class="modal-title">Decrease Box 2 Quantity</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>

        <div class="modal-body text-center">
          <input type="number" name="qty" class="form-control w-50 mx-auto" min="1" value="1">
        </div>

        <div class="modal-footer">
          <button class="btn btn-success">Save</button>
        </div>
      </form>
    </div>
  </div>
</div>


<!-- =============== BOX 2 PLUS MODAL =============== -->
<div class="modal fade" id="box2PlusModal_{{ $packagingItem->id }}" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <form action="{{ route('packaging.updateBoxQty') }}" method="POST">
        @csrf
        <input type="hidden" name="packaging_id" value="{{ $packagingItem->id }}">
        <input type="hidden" name="type" value="box2_plus">

        <div class="modal-header">
          <h5 class="modal-title">Increase Box 2 Quantity</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>

        <div class="modal-body text-center">
          <input type="number" name="qty" class="form-control w-50 mx-auto" min="1" value="1">
        </div>

        <div class="modal-footer">
          <button class="btn btn-success">Save</button>
        </div>
      </form>
    </div>
  </div>
</div>

          
          </tr>
          @endif
          @endforeach @endif
        </tbody>
      </table>
    </div>
  </div>
</div>
<div class="mx-3 mb-3">
  {{ $packaging->links() }}
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
          <p class="mt-3">                            <a href="{{ Storage::disk('s3')->url('stock/packagingSample.xlsx') }}" target="_blank">
                                Download Sample import file
                            </a></p>
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

<!-- MODAL FOR UPDATE QUANTITY (ONLY quantity1/quantity2) -->
<div class="modal fade" id="modalUpdateQuantityExcel" role="dialog">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h4 class="modal-title">Update Product Quantities</h4>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <form method="POST" id="modalUpdateQuantityExcelForm" action="{{ route('packaging.importQuantityCSV') }}" enctype="multipart/form-data">
        @csrf
        <div class="modal-body pb-0">
          <p class="mb-2">
            <a href="{{ route('packaging.quantityTemplate') }}" target="_blank">Download Dummy Quantity Excel</a>
          </p>
          <small class="text-muted d-block mb-3">Allowed columns: product_code, quantity1, quantity2</small>
          <input type="file" name="importQuantityCSV" id="importQuantityCSV" accept=".xlsx,.xls,.csv" required />
          <p class="mt-4 mb-0">Only quantity fields will be updated.</p>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-default" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-success">Upload & Update</button>
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
<style>
input.no-increment::-webkit-inner-spin-button {
    width: 14px;
    height: 14px;
    transform: translateY(50%);
    clip-path: inset(50% 0 0 0);
}

input.no-increment::-webkit-outer-spin-button {
    display: none;
}

input.no-increment {
    -moz-appearance: textfield; 
}

</style>
@endsection

@section('footer')


<!-- Scripts Start For Hardware Delete/View -->
<script type="text/javascript">
  $(function() {
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

  function updateCourier(id) {

    location.href = "{{ url('/packaging/view') }}" + '/' + id;
  }

  function deleteModal(id) {
    $('#deletePackaging').modal('show');
    $('#deletePackageForm').attr('action', "{{ url('/packaging/delete') }}" + '/' + id);
  }

  function fetchImage(obj, product) {
    if ($(obj).html() == "") {
      var img = "{{  Storage::disk('s3')->url('stock/allproducts/') }}" + product + "/" + product + "-1.jpg";
      $(obj).html('<a href="javascript:void(0)" data-bs-toggle="modal" data-bs-target="#viewImage" onclick="updateSrc(\'' + product + '\')"><img class="img-thumbnail img-fluid product-img-100" src="' + img + '" alt="No Image" /></a>');
    }
  }

  function updateSrc(product) {
    var img = "{{ Storage::disk('s3')->url('stock/allproducts/') }}" + product + "/" + product + "-1.jpg";
    $("#bigProImage").attr('src', img);
    $("#viewImage").find("h4").html(product);
  }

  function changeNo(obj) {
    var no_of_boxes = $(obj).val();
    if (no_of_boxes > 1) {
      $(obj).parent().parent().find('.box2_height').removeAttr('readonly');
      $(obj).parent().parent().find('.box2_width').removeAttr('readonly');
      $(obj).parent().parent().find('.box2_depth').removeAttr('readonly');
      $(obj).parent().parent().find('.box2_ply').removeAttr('readonly');
      $(obj).parent().parent().find('.box2_type').removeAttr('readonly');
    } else {
      $(obj).parent().parent().find('.box2_height').attr('readonly', '').val('');
      $(obj).parent().parent().find('.box2_width').attr('readonly', '').val('');
      $(obj).parent().parent().find('.box2_depth').attr('readonly', '').val('');
      $(obj).parent().parent().find('.box2_sqinch').attr('readonly', '').val('');
      $(obj).parent().parent().find('.box2_ply').attr('readonly', '').val('');
      $(obj).parent().parent().find('.box2_type').attr('readonly', '').val('');
    }
    data = $(obj).parent().parent().find('form').serialize();
    $.ajax({
      'url': "{{ url('/packaging/updatePackaging') }}",
      'method': 'POST',
      'data': data,
      success: function(r) {

      }
    });
  }

  function savePackage(obj) {
    data = $(obj).parent().parent().find('form').serialize();
    $.ajax({
      'url': "{{ url('/packaging/updatePackaging') }}",
      'method': 'POST',
      'data': data,
      success: function(r) {
        $(obj).parent().parent().find('.box1_sqinch').val(r.box1_sqinch);
        $(obj).parent().parent().find('.box2_sqinch').val(r.box2_sqinch);
      }
    });
  }



  document.querySelectorAll('.no-increment').forEach(input => {
    input.addEventListener('input', function() {
      const original = parseInt(this.getAttribute('max'));
      if (parseInt(this.value) > original) {
        this.value = original;
      }
    });
  });
</script>
<!-- Scripts End -->

@endsection