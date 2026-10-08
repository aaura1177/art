@extends('layouts.app')

@section('content')

    <div class="row mx-3 my-2">
      <h2>Temporary Buyer List</h2>
      <div class="col"><a class="btn btn-primary float-end" style="color: #fff;" href="{{ url('/temporaryBuyer/create')}}">Add Temporary Buyer</a></div>
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
            	@if(isset($tempBuyers)) @foreach($tempBuyers as $key => $tempBuyer)
              <tr>
                <td>{{++$key}}</td>
                <td>{{$tempBuyer->code}}</td>
                <td>{{$tempBuyer->c_name}}</td>
                <td>{{$tempBuyer->city}}</td>
                <td>
                  <span class="tool-tip1" data-bs-toggle="tooltip" data-bs-placement="top" title="Edit">
                    <button class="btn btn-info" onclick="updateTempBuyer('{{$tempBuyer->id}}')">
                      <i class="fa fa-edit"></i>
                    </button>
                  </span>
                  <span class="tool-tip1" data-bs-toggle="tooltip" data-bs-placement="top" title="Delete">
                    <button class="btn btn-danger" data-bs-toggle="modal" data-bs-target="#myModal{{$tempBuyer->id}}">
                      <i class="fa fa-trash"></i>
                    </button>
                  </span>

                  <!-- MODAL FOR DELETE -->
                  <div class="modal fade" id="myModal{{$tempBuyer->id}}" role="dialog">
                    <div class="modal-dialog">
                      <div class="modal-content">
                        <div class="modal-header">
                          <h4 class="modal-title">Delete Confirmation</h4>
                          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>  
                        </div>
                        <div class="modal-body">
                          <p>Are You sure you want to Delete this?</p>
                        </div>
                        <div class="modal-footer">
                          <button type="button" class="btn btn-default" data-bs-dismiss="modal">No</button>
                          <button onclick="deleteTempBuyer('{{$tempBuyer->id}}')" class="btn btn-danger">Yes</button>
                        </div>
                      </div>
                    </div>
                  </div>
                </td>
              </tr>
              @endforeach @endif
            </tbody>
          </table>
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

  function deleteTempBuyer(id){
    location.href = "{{ url('/temporaryBuyer/delete') }}" + '/' +id;
  } 
 
  function updateTempBuyer(id){
     location.href = "{{ url('/temporaryBuyer/view') }}" + '/' +id;
  } 
    
</script>
<!-- Scripts End -->
@endsection
