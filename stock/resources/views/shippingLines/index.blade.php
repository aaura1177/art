@extends('layouts.app')

@section('content')

      <!-- first row -->
      <div class="row mx-3 my-2">
        <h2>Shipping Line List</h2>
        <div class="col">
          <a class="btn btn-primary float-end" style="color: #fff;" href="{{ url('/shippingLines/create')}}">Add Shipping Line</a>
        </div>
      </div>

      <div class="card mb-3">
        <div class="card-body">
          <div class="table-responsive">
            <table class="table table-bordered" id="dataTables" width="100%" cellspacing="0">
              <thead>
                <tr>
                  <th>Name</th>
                  <th>Agent Name</th>
                  <th>UK Agent Name</th>                  
                  <th>Options</th>
                </tr>
              </thead>
              <tbody>
              	@if(isset($shippingLines)) @foreach($shippingLines as $key => $shippingLine)
                <tr>
                  <td>{{$shippingLine->name}}</td>
                  <td>{{$shippingLine->agent_name}}</td>   
                  <td>{{$shippingLine->agent_uk}}</td>               
                  <td>
                      <span class="tool-tip1" data-bs-toggle="tooltip" data-bs-placement="top" title="Edit">
                        <button class="btn btn-info" onclick="updateCourier('{{$shippingLine->id}}')">
            							<i class="fa fa-edit"></i>
            						</button>
                      </span>
					  <span class="tool-tip1" data-bs-toggle="tooltip" data-bs-placement="top" title="Delete">
                        <button class="btn btn-danger" onclick="deleteModal('{{$shippingLine->id}}')">
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
    <div class="modal fade" id="deleteShippingLine" role="dialog">
      <div class="modal-dialog">
        <div class="modal-content">
          <div class="modal-header">
            <h4 class="modal-title">Delete Confirmation</h4>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>  
          </div>
          <form id="deleteShippingLineForm" method="POST" action="">
          @csrf  
            <div class="modal-body">
              <p>Are You sure you want to Delete this?</p>
            </div>
            <div class="modal-footer">
              <button type="button" class="btn btn-default" data-bs-dismiss="modal" autofocus="">No</button>
              <button type="submit" id="deleteShippingLineForm" class="btn btn-danger">Yes</button>
            </div>
          </form>  
        </div>
      </div>
    </div>
@endsection

@section('footer')


<!-- Scripts Start For Shipping Line Delete/View -->
<script type="text/javascript">

  $(function () {
  $('[data-bs-toggle="tooltip"]').tooltip()
  });

  function updateCourier(id){

     location.href = "{{ url('/shippingLines/view') }}" + '/' +id;
  }
  
  function deleteModal(id)
    {
      $('#deleteShippingLine').modal('show');
      $('#deleteShippingLineForm').attr('action', "{{ url('/shippingLines/delete') }}" + '/' +id);
    } 

</script>
<!-- Scripts End -->

@endsection
