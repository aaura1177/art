@extends('layouts.app')

@section('content')

    <div class="row mx-3 my-2">
      <h2>Buyer List</h2>
      <div class="col"><a class="btn btn-primary float-end" style="color: #fff;" href="{{ url('/buyer/create')}}">Add buyer</a></div>
    </div>
      
    <div class="card mb-3">
      <div class="card-body">
        <div class="table-responsive">
          <table class="table table-bordered" id="dataTable" width="100%" cellspacing="0">
            <thead>
              <tr>
                <th>#</th>
                <th>Buyer Code</th>
                <th>Buyer Name</th>
                <th>City</th>
                <th>Options</th>
              </tr>
            </thead>
            <tbody>
            	@if(isset($buyer)) @foreach($buyer as $key => $buyer)
              <tr>
                <td>{{++$key}}</td>
                <td>{{$buyer->code}}</td>
                <td>{{$buyer->c_name}}</td>
                <td>{{$buyer->city}}</td>
                <td>
                  <span class="tool-tip1" data-bs-toggle="tooltip" data-bs-placement="top" title="Edit">
                    <button class="btn btn-info" onclick="updatebuyer('{{$buyer->id}}')">
                      <i class="fa fa-edit"></i>
                    </button>
                  </span>

                  @php
                  $buyerRelationCount = $buyer->invoice->count();
                  @endphp
                  @if($buyerRelationCount == 0)
                  <span class="tool-tip1" data-bs-toggle="tooltip" data-bs-placement="top" title="Delete">
                    <button class="btn btn-danger" data-bs-toggle="modal" onclick="deleteModal('{{$buyer->id}}')">
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
    <div class="modal fade" id="deleteBuyer" role="dialog">
      <div class="modal-dialog">
        <div class="modal-content">
          <div class="modal-header">
            <h4 class="modal-title">Delete Confirmation</h4>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>  
          </div>
          <form id="deleteBuyerForm" method="POST" action="">
          @csrf  
            <div class="modal-body">
              <p>Are You sure you want to Delete this?</p>
            </div>
            <div class="modal-footer">
              <button type="button" class="btn btn-default" data-bs-dismiss="modal">No</button>
              <button type="submit" id="deleteBuyerForm" class="btn btn-danger">Yes</button>
            </div>
          </form>            
        </div>
      </div>
    </div>
@endsection

@section('footer')


<!-- Scripts Starts For Buyer Delete/View -->
<script type="text/javascript">
  
  $(function () {
    $('[data-bs-toggle="tooltip"]').tooltip()
  });

  function deleteModal(id){
    $('#deleteBuyer').modal('show');
    $('#deleteBuyerForm').attr('action', "{{ url('/buyer/delete') }}" + '/' +id);
  } 
 
  function updatebuyer(id){
     location.href = "{{ url('/buyer/view') }}" + '/' +id;
  } 
    
</script>
<!-- Scripts End -->
@endsection
