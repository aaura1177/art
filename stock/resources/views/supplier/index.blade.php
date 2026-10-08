@extends('layouts.app')

@section('content')

      <div class="row mx-3 my-2">
        <h2>Supplier List</h2>
        <div class="col">
          <a class="btn btn-primary float-end" style="color: #fff; margin-left: 5px;" href="{{ url('/supplier/create')}}">Add supplier</a>
		  <a class="btn btn-success float-end" style="color: #fff; margin-left: 5px;" data-bs-toggle="modal" data-bs-target="#modalImportExcel">Import Products</a>
        </div>
      </div>
      
      <div class="card mb-3">
        <div class="card-body">
          <div class="table-responsive">
            <table class="table table-bordered" id="dataTable" width="100%" cellspacing="0">
              <thead>
                <tr>
                  <th>#</th>
                  <th>Supplier Name</th>
                  <th>Contact Person</th>
                  <th>Phone</th>
                  <th>Type</th>
                  <th>Options</th>
                </tr>
              </thead>
              <tbody>
              	@if(isset($supplier)) @foreach($supplier as $key => $supplier)
                <tr>
                  <td>{{++$key}}</td>
                  <td>{{$supplier->c_name}}</td>
                  <td>{{$supplier->name}}</td>
                  <td>{{$supplier->phone1}}</td>
                  <td>{{$supplier->type}}</td>
                  <td>
                      <span class="tool-tip1" data-bs-toggle="tooltip" data-bs-placement="top" title="View">
                        <button class="btn btn-info" onclick="updatesupplier('{{$supplier->id}}')">
            							<i class="fa fa-edit"></i>
            						</button>
                      </span>

                      @php
                      $supplierRelationCount = $supplier->rejectRepair->count() + $supplier->purchaseOrder->count();
                      @endphp
                      @if($supplierRelationCount == 0)
                      <span class="tool-tip1" data-bs-toggle="tooltip" data-bs-placement="top" title="Delete">
                        <button class="btn btn-danger" data-bs-toggle="modal" onclick="deleteModal('{{$supplier->id}}')">
                          <i class="fa fa-trash"></i>
                        </button>
                      </span>
                      @endif
                  </td>
                </tr>
                @endforeach @endif
              </tbody>
            </table>
          </div>
        </div>
      </div>

      <!-- MODAL FOR DELETE -->
      <div class="modal fade" id="deletesupplier" role="dialog">
        <div class="modal-dialog">
          <div class="modal-content">
            <div class="modal-header">
              <h4 class="modal-title">Delete Confirmation</h4>
              <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>  
            </div>
            <form id="deletesupplierForm" method="POST" action="">
            @csrf  
              <div class="modal-body">
                <p>Are You sure you want to Delete this?</p>
              </div>
              <div class="modal-footer">
                <button type="button" class="btn btn-default" data-bs-dismiss="modal">No</button>
                <button type="submit" id="deletesupplierForm" class="btn btn-danger">Yes</button>
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
          <form method="POST" id="modalImportExcelForm" action="{{ url('/supplier/importCSV') }}" enctype="multipart/form-data">
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
@endsection

@section('footer')


<!-- Script start for Supplier view/delete -->
<script type="text/javascript">
  
  $(function () {
    $('[data-bs-toggle="tooltip"]').tooltip()
  });

  function deleteModal(id){
    $('#deletesupplier').modal('show');
    $('#deletesupplierForm').attr('action', "{{ url('/supplier/delete') }}" + '/' +id);
  } 
 
  function updatesupplier(id){
     location.href = "{{ url('/supplier/view') }}" + '/' +id;
  } 
    
</script>
<!-- Script end -->
@endsection
