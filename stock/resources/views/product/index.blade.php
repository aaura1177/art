@extends('layouts.app')

@section('content')

<div class="row mx-3 my-2">
  <h2>Products List</h2>

  @hasrole('admin')
  <div class="col">
    <!-- <a class="btn btn-success float-end" style="color: #fff; margin-left: 5px;" data-bs-toggle="modal" data-bs-target="#mydateModal"  href="{{ url('/product/exportcsv')}}">Download CSV</a> -->

<a class="btn btn-success float-end" style="color: #fff; margin-left: 5px;" 
   href="{{ url('/product/hardwareProduct') }}">
   Download Hardware in Product
</a>
    <a class="btn btn-success float-end" style="color: #fff; margin-left: 5px;" href="{{ url('/product/families')}}">Families</a>
    <!-- <a class="btn btn-success float-end" style="color: #fff; margin-left: 5px;" href="{{ url('/product/batch')}}">Download Batch</a> -->
    <a class="btn btn-success float-end" style="color: #fff; margin-left: 5px;" href="{{ url('/product/smallhardware')}}">Download Product Small Hardware</a>
    <!-- <a class="btn btn-success float-end" style="color: #fff; margin-left: 5px;" href="{{ url('/product/batch/qty')}}">Download Batch product Qty</a> -->
    <a class="btn btn-success float-end" style="color: #fff; margin-left: 5px;" data-bs-toggle="modal" data-bs-target="#modalPrint">Print</a>
    <a class="btn btn-success float-end" style="color: #fff; margin-left: 5px;" data-bs-toggle="modal" data-bs-target="#modalImportExcel">Import Excel</a>
    <a class="btn btn-success float-end" style="color: #fff; margin-left: 5px;" data-bs-toggle="modal" data-bs-target="#ProductWeightLbsImport">Import Product Weight Lbs</a>
    <!-- <a class="btn btn-success float-end" style="color: #fff; margin-left: 5px;" data-bs-toggle="modal" data-bs-target="#modalbatchImportExcel">Import Batch</a> -->
    <a class="btn btn-success float-end" style="color: #fff; margin-left: 5px;" data-bs-toggle="modal" data-bs-target="#modalFinishingExcel">Import Finishing Excel</a>
    <a class="btn btn-success float-end" style="color: #fff; margin-left: 5px;" data-bs-toggle="modal" data-bs-target="#modalImportCartonExcel">Import Carton Qty</a>
    <a class="btn btn-success float-end" style="color: #fff; margin-left: 5px;" href="{{ url('/product/exportcsv')}}">Download CSV</a>
    <a class="btn btn-success float-end" style="color: #fff; margin-left: 5px;" data-bs-toggle="modal" data-bs-target="#modalImportExcelconsumble">Import Consumable</a>
    <a class="btn btn-primary float-end" style="color: #fff; margin-left: 5px;" href="{{ url('/product/create')}}">Add Product</a>
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
            <a href="{{ url('/product') }}" class="btn btn-warning"> Reset </a>
        </div>
      </form>

    <div class="table-responsive">
      <table class="table table-bordered" id="dataTabless" width="100%" cellspacing="0">
        <thead>
          <tr>
            <th>Code</th>
            <th>Image</th>
            <th>Name</th>
            <th>Category</th>
            <th>Sub-Category</th>
            <th>EAN</th>
            <th>Quantity</th>
            <th>Location</th>
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
            <td>{{$product->category->name}}</td>
            <td>{{$product->subCategory->name}}</td>
            <td>{{$product->EAN}}</td>
            <td>{{$product->quantity}}</td>
            <td>
              @if(isset($product->productLocations)) @foreach($product->productLocations as $key => $productLocation)
              {{$productLocation->location}} - {{$productLocation->quantity}}
              @endforeach @endif
            </td>
            @hasrole('admin')
            <td>
              <span class="tool-tip1" data-bs-toggle="tooltip" data-bs-placement="top" title="Edit">
                <button class="btn btn-info" onclick="updateProduct('{{$product->id}}')">
                  <i class="fa fa-edit"></i>
                </button>
              </span>
              @php
              $productRelationsCount = $product->rejectRepair->count() + $product->poTable->count() + $product->pbTable->count() + $product->invoiceTable->count() + $product->stockoutTable->count() + $product->allocation->count() + $product->quantity;
              @endphp

              @if($productRelationsCount==0)
              <span class="tool-tip1" data-bs-toggle="tooltip" data-bs-placement="top" title="Delete">
                <button class="btn btn-danger" data-bs-toggle="modal" onclick="deleteModal('{{$product->id}}')">
                  <i class="fa fa-trash"></i>
                </button>
              </span>
              @endif
            </td>
            @endhasrole
          </tr>
          @endforeach @endif
        </tbody>
      </table>
       <div class="mt-4">
              @if(isset($products))
              {{ $products->links() }}
              @endif
          </div>
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


<div class="modal fade" id="modalImportExcelconsumble" role="dialog">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h4 class="modal-title">Import product consumables</h4>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <form method="POST" id="modalImportConsumableForm" action="{{ url('/product/ConsumableimportCSV') }}" enctype="multipart/form-data">
        @csrf
        <div class="modal-body pb-0">
          <p class="mb-3">
            <a class="btn btn-sm btn-outline-success" href="{{ url('/product/consumable-import-template') }}" download>
              <i class="fa fa-download"></i> Download Excel template
            </a>
          </p>
          <label class="d-block fw-bold mb-1" for="ConsumableimportCSV">Filled file upload</label>
          <input type="file" name="ConsumableimportCSV" id="ConsumableimportCSV" accept=".xlsx,.xls,.csv,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet,application/vnd.ms-excel,text/csv" />
          <p class="mt-4 mb-0">Are you sure you want to upload this file?</p>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-default" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" id="modalImportConsumableSubmit" onclick="return validateConsumableImport()" class="btn btn-success">Upload &amp; import</button>
        </div>
      </form>
    </div>
  </div>
</div>

<div class="modal fade" id="ProductWeightLbsImport" role="dialog">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h4 class="modal-title"> Upload Excel File</h4>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <form method="POST" id="modalImportExcelForm" action="{{ url('/product/ProductWeightLbsImport/importCSV') }}" enctype="multipart/form-data">
        @csrf
        <div class="modal-body pb-0">
          <input type="file" name="importCSV" id="importProductWeightLbsCSV" accept=".xlsx,.xls,.csv" />
          <p class="mt-3 mb-0"><a href="{{ url('/product/product-weight-lbs-import-template') }}">Download sample Excel</a></p>
          <p class="mt-3 mb-0">Columns: <strong>sku</strong>, <strong>product_weight</strong> (net, kg), <strong>boxedweight</strong> (gross, kg).</p>
          <p class="mt-3 mb-0">Are you sure you want to upload this?</p>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-default" data-bs-dismiss="modal">No</button>
          <button type="submit" class="btn btn-success">Yes</button>
        </div>
      </form>
    </div>
  </div>
</div>

<div class="modal fade" id="modalbatchImportExcel" role="dialog">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h4 class="modal-title"> Upload Excel File</h4>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <form method="POST" id="modalbatchImportExcelForm" action="{{ url('/product/batchimportCSV') }}" enctype="multipart/form-data">
        @csrf
        <div class="modal-body pb-0">
          <input type="file" name="batchimportCSV" id="batchimportCSV" />
          {{-- <p class="mt-3"><a href="{{ Storage::disk('s3')->url('stock/productSample.xlsx') }}">Download Sample import file</a></p> --}}
          <p class="mt-4 mb-0">Are You sure you want to Upload this?</p>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-default" data-bs-dismiss="modal">No</button>
          <button type="sumit" id="modalbatchImportExcelForm" onclick="return validate()" class="btn btn-success">Yes</button>
        </div>
      </form>
    </div>
  </div>
</div>


<!-- MODAL FOR IMPORT CARTON (product_carton quantity1 / quantity2) -->
<div class="modal fade" id="modalImportCartonExcel" role="dialog">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h4 class="modal-title">Import carton quantities</h4>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <form method="POST" action="{{ url('/product/importCSVcarton') }}" enctype="multipart/form-data">
        @csrf
        <div class="modal-body pb-0">
          <input type="file" name="importCSV" id="importCartonCSV" accept=".xlsx,.xls,.csv" required />
          <p class="mt-3 mb-0"><a href="{{ url('/product/carton-import-template') }}">Download sample Excel</a></p>
          <p class="mt-3 mb-0">Upload now?</p>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-default" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-success">Upload</button>
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
  $(function() {
    $('[data-bs-toggle="tooltip"]').tooltip();

    $('#modalImportExcelconsumble').on('hidden.bs.modal', function () {
      var f = document.getElementById('ConsumableimportCSV');
      if (f) {
        f.value = '';
      }
    });
  });

  function deleteModal(id) {
    $('#deleteProduct').modal('show');
    $('#deleteProductForm').attr('action', "{{ url('/product/delete') }}" + '/' + id);
  }

  function updateProduct(id) {
    location.href = "{{ url('/product/view') }}" + '/' + id;
  }

  function downloadcsv() {
    $("#modalPrintForm").attr("action", "{{ url('/product/exportCodeWiseExcel') }}");
    return true;
  }

  function printpdf() {
    $("#modalPrintForm").attr("action", "{{ url('/product/printList') }}");
    return true;
  }

  function fetchImage(obj, product) {
    if ($(obj).html() == "") {
      var img = "{{ Storage::disk('s3')->url('stock/allproducts/') }}" + product + "/" + product + "-1.jpg";

      $(obj).html('<a href="javascript:void(0)" data-bs-toggle="modal" data-bs-target="#viewImage" onclick="updateSrc(\'' + product + '\')"><img class="img-thumbnail img-fluid product-img-100" src="' + img + '" alt="No Image" /></a>');
    }
  }

  function updateSrc(product) {
    var img = "{{ Storage::disk('s3')->url('stock/allproducts/') }}" + product + "/" + product + "-1.jpg";
    $("#bigProImage").attr('src', img);
    $("#viewImage").find("h4").html(product);
  }

  /** Consumable import modal: require file before POST */
  function validateConsumableImport() {
    var input = document.getElementById('ConsumableimportCSV');
    if (!input || !input.files || !input.files.length) {
      alert('Please select an Excel (.xlsx / .xls) or CSV file.');
      return false;
    }
    return true;
  }
</script>
@endsection