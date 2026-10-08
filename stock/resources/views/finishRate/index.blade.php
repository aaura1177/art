@extends('layouts.app')

@section('content')

    <div class="row mx-3 my-2">
      <h2>Finish Rate List</h2>
      
      @hasrole('admin')
      <div class="col">
          <!-- <a class="btn btn-success float-end" style="color: #fff; margin-left: 5px;" data-bs-toggle="modal" data-bs-target="#mydateModal"  href="{{ url('/product/exportcsv')}}">Download CSV</a> -->
         <!-- <a class="btn btn-success float-end" style="color: #fff; margin-left: 5px;" href="{{ url('/consumables/exportcsv')}}">Download CSV</a>-->
          <a class="btn btn-primary float-end" style="color: #fff; margin-left: 5px;" href="{{ url('/finishing_rates/add')}}">Add Finish Rate</a>
      </div>
      @endhasrole
	  
	  @hasrole('factory')
	
	  @endhasrole
    </div>
    
    <div class="card mb-3">
      <div class="card-body">
        <div class="table-responsive">
          <table class="table table-bordered" id="dataTables" width="100%" cellspacing="0">
            <thead>
              <tr>
                <th>SR.NO</th> 
                <th>Item</th> 
              <th>Price</th>
             
                @hasrole('admin')
                <th>Options</th>
                @endhasrole
              </tr>
            </thead>
            <tbody>
                @php #d
                $i=1;
                @endphp
            	@if(isset($finishRate)) @foreach($finishRate as $key => $finishRate)
              <tr>
                <td>{{$i++}}</td>
                <td>{{$finishRate->name}}</td>
                <td>{{$finishRate->rate}}</td>
             
                @hasrole('admin')
                <td>
                  <span class="tool-tip1" data-bs-toggle="tooltip" data-bs-placement="top" title="Edit">
              			<button class="btn btn-info" onclick="updateProduct('{{$finishRate->id}}')">
              				<i class="fa fa-edit"></i>
              			</button>
                  </span>
                  <span class="tool-tip1" data-bs-toggle="tooltip" data-bs-placement="top" title="Delete">
                    <button class="btn btn-danger" data-bs-toggle="modal" onclick="deleteModal('{{$finishRate->id}}')">
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
    <div class="modal" id="deleteProduct" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <form id="deleteProductForm" method="POST" action="">
                @csrf
                @method('DELETE') 
                <div class="modal-body">
                    <p>Are you sure you want to delete this finish rate?</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-bs-dismiss="modal">No</button>
                    <button type="submit" class="btn btn-danger">Yes</button>
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

    $('#deleteProductForm').attr('action', '/finishing_rates/delete/' + id);
}

 
  function updateProduct(id)
    {
      location.href = "{{ url('/finishing_rates/view') }}" + '/' +id;
    } 
	
	function downloadcsv(){
	  $("#modalPrintForm").attr("action","{{ url('/consumables/exportCodeWiseExcel') }}");
	  return true;
	}
  
    
</script>
@endsection