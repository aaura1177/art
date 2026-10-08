@extends('layouts.app')

@section('content')

      <!-- first row -->
      <div class="row mx-3 my-2">
        <h2>Logs Book</h2>
        <form action="{{ url('/notifications/index') }}" method="GET">
    <div class="row mb-3">
        <div class="col-md-3">
            <label for="fromFilter">From Date:</label>
            <input type="date" id="fromFilter" name="from" value="{{ request('from') }}" class="form-control">
        </div>
        <div class="col-md-3">
            <label for="toFilter">To Date:</label>
            <input type="date" id="toFilter" name="to" value="{{ request('to') }}" class="form-control">
        </div>
       
        <div class="col-md-4">
            <label>&nbsp;</label><br>
            <button type="submit" class="btn btn-primary">Filter</button>
        </div>
    </div>
</form>
      </div>

      <div class="card mb-3">
        <div class="card-body">
          <div class="table-responsive">
            <table class="table table-bordered" id="dataTables" width="100%" cellspacing="0">
              <thead>
                <tr>
                  <th>User Logs</th>
                  <th>Date</th>
                  <!-- <th>Supplier</th>
                  <th>Options</th> -->
                </tr>
              </thead>
              <tbody>
              	@if(!empty($notifications)) @foreach($notifications as $key => $notification)
                <tr>
                  <td>{{$notification->notification}}</td>
                  <td>{{$notification->created_at}}</td>
                  
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
$('#dataTables').DataTable({
            processing: true,  // Show processing indicator
         // Enable server-side processing
            ordering: false,    // Disable column ordering
           
        });

  $(function () {
  $('[data-bs-toggle="tooltip"]').tooltip()
  });
  
  function deleteModal(id)
    {
      $('#deleteHardware').modal('show');
      $('#deleteHardwareForm').attr('action', "{{ url('/notifications/delete') }}" + '/' +id);
    } 

</script>
<!-- Scripts End -->

@endsection
