@extends('layouts.app')

@section('content')

    <div class="row mx-3 my-2">
      <h2>Unit Type</h2>
      
      @hasrole('admin')
      <div class="col">
          <!-- <a class="btn btn-success float-end" style="color: #fff; margin-left: 5px;" data-bs-toggle="modal" data-bs-target="#mydateModal"  href="{{ url('/product/exportcsv')}}">Download CSV</a> -->
         <!-- <a class="btn btn-success float-end" style="color: #fff; margin-left: 5px;" href="{{ url('/consumables/exportcsv')}}">Download CSV</a>-->
          <a class="btn btn-primary float-end" style="color: #fff; margin-left: 5px;" href="{{ url('/unitType/create')}}">Add Unit Type</a>
      </div>
      @endhasrole
	  

    </div>
    
    <div class="card mb-3">
      <div class="card-body">
        <div class="table-responsive">
          <table class="table table-bordered" id="dataTables" width="100%" cellspacing="0">
            <thead>
              <tr>
                <th>Item</th>
                <th>Type</th>
              
                @hasrole('admin')
                <th>Options</th>
                @endhasrole
              </tr>
            </thead>
            <tbody>
            	@if(isset($unitType)) @foreach($unitType as $key => $unitType)
              <tr>
                <td>{{$unitType->name}}</td>
                <td>
                  @if ($unitType->data_type == 'int')
                  Non Decimal
                  @else
                          Decimal
                  @endif
                </td>
              
                @hasrole('admin')
                <td>
                  <span class="tool-tip1" data-bs-toggle="tooltip" data-bs-placement="top" title="Edit">
              			<button class="btn btn-info" onclick="updateProduct('{{$unitType->id}}')">
              				<i class="fa fa-edit"></i>
              			</button>
                  </span>
                  <span class="tool-tip1" data-bs-toggle="tooltip" data-bs-placement="top" title="Delete">
                    <button class="btn btn-danger" data-bs-toggle="modal" onclick="deleteModal('{{$unitType->id}}')">
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
          <form method="POST" id="modalImportExcelForm" action="{{ url('/consumables/importConsumable') }}" enctype="multipart/form-data">
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
<script type="text/javascript">

  
  $(function () {
  $('[data-bs-toggle="tooltip"]').tooltip()
  });

  function deleteModal(id)
    {
      $('#deleteProduct').modal('show');
      $('#deleteProductForm').attr('action', "{{ url('/unitType/delete') }}" + '/' +id);
    } 
 
  function updateProduct(id)
    {
      location.href = "{{ url('/unitType/view') }}" + '/' +id;
    } 
	
	function downloadcsv(){
	  $("#modalPrintForm").attr("action","{{ url('/consumables/exportCodeWiseExcel') }}");
	  return true;
	}
  
    
</script>
@endsection