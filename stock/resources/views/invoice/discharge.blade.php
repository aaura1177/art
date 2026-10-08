@extends('layouts.app')

@section('content')

      <!-- first row -->
      <div class="row mx-3 my-2">
        <h2>Port of Discharge List</h2>
        <div class="col">
          <a class="btn btn-primary float-end" style="color: #fff;" href="{{ url('/invoice/discharge/create')}}">Add Port of Discharge</a>
        </div>
      </div>

      <div class="card mb-3">
        <div class="card-body">
          <div class="table-responsive">
            <table class="table table-bordered" id="dataTables" width="100%" cellspacing="0">
              <thead>
                <tr>
                  <th> Name</th>
               
                  <th>Options</th>
                </tr>
              </thead>
              <tbody>
              	@if(isset($InvoiceDischarge)) @foreach($InvoiceDischarge as $key => $InvoiceDischarge)
                <tr>
                  <td>{{$InvoiceDischarge->name}}</td>
               
                  <td>
                     
          					
                      <span class="tool-tip1" data-bs-toggle="tooltip" data-bs-placement="top" title="Delete">
                        <button class="btn btn-danger" onclick="deleteModal('{{$InvoiceDischarge->id}}')">
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
      <div class="modal fade" id="deleteCategory" role="dialog">
        <div class="modal-dialog">
          <div class="modal-content">
            <div class="modal-header">
              <h4 class="modal-title">Delete Confirmation</h4>
              <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="deleteCategoryForm" method="POST" action="">
            @csrf
              @method('DELETE')
              <div class="modal-body">
                <p>Are You sure you want to Delete this?</p>
              </div>
              <div class="modal-footer">
                <button type="button" class="btn btn-default" data-bs-dismiss="modal" autofocus="">No</button>
                <button type="submit" id="deleteCategoryForm" class="btn btn-danger">Yes</button>
              </div>
            </form>
          </div>
        </div>
      </div>
@endsection

@section('footer')


<!-- Scripts Start For Category Delete/View -->
<script type="text/javascript">

  $(function () {
  $('[data-bs-toggle="tooltip"]').tooltip()
  });

 function deleteModal(id){
    $('#deleteCategory').modal('show');
    $('#deleteCategoryForm').attr('action', "{{ url('/invoice/discharge/delete') }}" + '/' +id);
}


  function updateCategory(id){

     location.href = "{{ url('/category/view') }}" + '/' +id;
  }

</script>
<!-- Scripts End -->

@endsection
