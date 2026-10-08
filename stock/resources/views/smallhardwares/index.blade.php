@extends('layouts.app')

@section('content')

      <!-- first row -->
      <div class="row mx-3 my-2">
        <h2>Small Hardware List</h2>
        <div class="col">
          <a class="btn btn-success float-end" style="color: #fff; margin-left: 5px;" data-bs-toggle="modal" data-bs-target="#modalImportExcel">Import Small Hardware</a>
                    <a class="btn btn-success float-end" style="color: #fff; margin-left: 5px;" href="{{route('smallhardwares.download')}}" >Download small Hardware</a>

          <a class="btn btn-primary float-end" style="color: #fff;" href="{{ url('/smallhardwares/create')}}">Add Small Hardware</a>
        </div>
      </div>

      <div class="card mb-3">
        <div class="card-body">
          <div class="table-responsive">
            <table class="table table-bordered" id="dataTables" width="100%" cellspacing="0">
              <thead>
                <tr>
                  <th>Small Hardware Name</th>
                  <th>Buyer</th>
                  <th>Small Hardware Rate</th>
                  <th>Small Hardware Supplier</th>
                  <th>Options</th>
                </tr>
              </thead>
              <tbody>
              	@if(isset($smallhardwares)) @foreach($smallhardwares as $key => $hardware)
                <tr>
                  <td>{{$hardware->name}}</td>
                  <td>@if($hardware->buyer == 1) UK-18 @endif  @if($hardware->buyer == 2) Non UK-18 @endif  @if($hardware->buyer == 3) Common @endif</td>
                  <td>{{$hardware->rate}}</td>
                  <td>
				  @if($hardware->smallhardwareSupplier)
					{{$hardware->smallhardwareSupplier->c_name}}
				  @endif
				  </td>
                  <td>
                      <span class="tool-tip1" data-bs-toggle="tooltip" data-bs-placement="top" title="Edit">
                        <button class="btn btn-info" onclick="updateCourier('{{$hardware->id}}')">
            							<i class="fa fa-edit"></i>
            						</button>
                      </span>
					  <span class="tool-tip1" data-bs-toggle="tooltip" data-bs-placement="top" title="Delete">
                        <button class="btn btn-danger" onclick="deleteModal('{{$hardware->id}}')">
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
    <div class="modal fade" id="deleteHardware" role="dialog">
      <div class="modal-dialog">
        <div class="modal-content">
          <div class="modal-header">
            <h4 class="modal-title">Delete Confirmation</h4>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>  
          </div>
          <form id="deleteHardwareForm" method="POST" action="">
          @csrf  
            <div class="modal-body">
              <p>Are You sure you want to Delete this?</p>
            </div>
            <div class="modal-footer">
              <button type="button" class="btn btn-default" data-bs-dismiss="modal" autofocus="">No</button>
              <button type="submit" id="deleteHardwareForm" class="btn btn-danger">Yes</button>
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
          <form method="POST" id="modalImportExcelForm" action="{{ url('/smallhardwares/importCSV') }}" enctype="multipart/form-data">
          @csrf
            <div class="modal-body pb-0">
              <input type="file" name="importCSV" id="importCSV" />
              <p class="mt-3"><a href="{{ Storage::disk('s3')->url('stock/smhSample.xlsx') }}" target="_blank">
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
@endsection

@section('footer')


<!-- Scripts Start For Hardware Delete/View -->
<script type="text/javascript">

  $(function () {
  $('[data-bs-toggle="tooltip"]').tooltip()
  });

  function updateCourier(id){

     location.href = "{{ url('/smallhardwares/view') }}" + '/' +id;
  }
  
  function deleteModal(id)
    {
      $('#deleteHardware').modal('show');
      $('#deleteHardwareForm').attr('action', "{{ url('/smallhardwares/delete') }}" + '/' +id);
    } 

</script>
<!-- Scripts End -->

@endsection
