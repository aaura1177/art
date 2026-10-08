@extends('layouts.app')

@section('content')

      <!-- first row -->
      <div class="row mx-3 my-2">
        <h2>Category List</h2>
        <div class="col">
          <a class="btn btn-primary float-end" style="color: #fff;" href="{{ url('/category/create')}}">Add Category</a>
        </div>
      </div>

      <div class="card mb-3">
        <div class="card-body">
          <div class="table-responsive">
            <table class="table table-bordered" id="dataTables" width="100%" cellspacing="0">
              <thead>
                <tr>
                  <th>Category Name</th>
                  <th>Products</th>
                  <th>Options</th>
                </tr>
              </thead>
              <tbody>
              	@if(isset($category)) @foreach($category as $key => $category)
                <tr>
                  <td>{{$category->name}}</td>
                  <td>{{$category->product->count()}}</td>
                  <td>
                      <span class="tool-tip1" data-bs-toggle="tooltip" data-bs-placement="top" title="Edit">
                        <button class="btn btn-info" onclick="updateCategory('{{$category->id}}')">
            							<i class="fa fa-edit"></i>
            						</button>
                      </span>
          						@if($category->product->count() == 0)
                      <span class="tool-tip1" data-bs-toggle="tooltip" data-bs-placement="top" title="Delete">
                        <button class="btn btn-danger" onclick="deleteModal('{{$category->id}}')">
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
      <div class="modal fade" id="deleteCategory" role="dialog">
        <div class="modal-dialog">
          <div class="modal-content">
            <div class="modal-header">
              <h4 class="modal-title">Delete Confirmation</h4>
              <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="deleteCategoryForm" method="POST" action="">
            @csrf
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
    $('#deleteCategoryForm').attr('action', "{{ url('/category/delete') }}" + '/' +id);
  }

  function updateCategory(id){

     location.href = "{{ url('/category/view') }}" + '/' +id;
  }

</script>
<!-- Scripts End -->

@endsection
