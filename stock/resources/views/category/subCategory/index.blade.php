@extends('layouts.app')

@section('content')

      <!-- first row -->
      <div class="row mx-3 my-2">
        <h2>Sub-Category List</h2>
        <div class="col">
          <a class="btn btn-primary float-end" style="color: #fff;" href="{{ url('/category/subCategory/create')}}">Add Sub-Category</a>
        </div>
      </div>
      
      <div class="card mb-3">
        <div class="card-body">
          <div class="table-responsive">
            <table class="table table-bordered" id="dataTables" width="100%" cellspacing="0">
              <thead>
                <tr>
                  <th>Sub-Category Name</th>
                  <th>Products</th>
                  <th>Options</th>
                </tr>
              </thead>
              <tbody>
              	@if(isset($subCategory)) @foreach($subCategory as $key => $subCategories)
                <tr>
                  <td>{{$subCategories->name}}</td>
                  <td>{{$subCategories->product->count()}}</td>
                  <td>
                      <span class="tool-tip1" data-bs-toggle="tooltip" data-bs-placement="top" title="Edit">
                        <button class="btn btn-info" onclick="updateSubCategory('{{$subCategories->id}}')">
            							<i class="fa fa-edit"></i>
            						</button>
                      </span>  
          						@if($subCategories->product->count() == 0)
                        <span class="tool-tip1" data-bs-toggle="tooltip" data-bs-placement="top" title="">
                          <button class="btn btn-danger" onclick="deleteModal('{{$subCategories->id}}')">
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
      <div class="modal fade" id="deleteSubCategory" role="dialog">
        <div class="modal-dialog">
          <div class="modal-content">
            <div class="modal-header">
              <h4 class="modal-title">Delete Confirmation</h4>
              <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>  
            </div>
            <form id="deleteSubCategoryForm" method="POST" action="">
            @csrf
              <div class="modal-body">
                <p>Are You sure you want to Delete this?</p>
              </div>
              <div class="modal-footer">
                <button type="button" class="btn btn-default" data-bs-dismiss="modal" autofocus="">No</button>
                <button type="submit" id="deleteSubCategoryForm" class="btn btn-danger">Yes</button>
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
    $('#deleteSubCategory').modal('show');
    $('#deleteSubCategoryForm').attr('action', "{{ url('/category/subCategory/delete') }}" + '/' +id);
  } 
 
  function updateSubCategory(id){

     location.href = "{{ url('/category/subCategory/view') }}" + '/' +id;
  } 
    
</script>
<!-- Scripts End -->

@endsection
