@extends('layouts.app')

@section('content')

      <!-- first row -->
      <div class="row mx-3 my-2">
        <h2>Hardware List</h2>
        <div class="col">
          <a class="btn btn-primary float-end" style="color: #fff;" href="{{ url('/hardwares/create')}}">Add Hardware</a>
        </div>
      </div>

      <div class="card mb-3">
        <div class="card-body">
          <div class="table-responsive">
            <table class="table table-bordered" id="dataTables" width="100%" cellspacing="0">
              <thead>
                <tr>
                  <th>Hardware Name</th>
                  <th>Hardware Rate</th>
                  <th>Hardware Supplier</th>
                  <th>Options</th>
                </tr>
              </thead>
              <tbody>
              	@if(isset($hardwares)) @foreach($hardwares as $key => $hardware)
                <tr>
                  <td>{{$hardware->name}}</td>
                  <td>{{$hardware->rate}}</td>
                  <td>
				  @if($hardware->hardwareSuppliers)
					{{$hardware->hardwareSuppliers->name}}
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
@endsection

@section('footer')


<!-- Scripts Start For Hardware Delete/View -->
<script type="text/javascript">

  $(function () {
  $('[data-bs-toggle="tooltip"]').tooltip()
  });

  function updateCourier(id){

     location.href = "{{ url('/hardwares/view') }}" + '/' +id;
  }
  
  function deleteModal(id)
    {
      $('#deleteHardware').modal('show');
      $('#deleteHardwareForm').attr('action', "{{ url('/hardwares/delete') }}" + '/' +id);
    } 

</script>
<!-- Scripts End -->

@endsection
