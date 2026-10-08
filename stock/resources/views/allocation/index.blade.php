@extends('layouts.app')

@section('content')

    <div class="row mx-3 my-2">
      <h2>Allocations List</h2>
      <div class="col">
        <a class="btn btn-primary float-end" style="color: #fff;" href="{{ url('/allocation/create')}}">Add Allocation</a>
      </div>
    </div>
    
    <div class="card mb-3">
      <div class="card-body">
        <div class="table-responsive">
          <table class="table table-bordered" id="dataTable" width="100%" cellspacing="0">
            <thead>
              <tr>
                <th>Allocation No.</th>
                <th>Ref. No.</th>
                <th>Contractor</th>
                <th>Product</th>
                <th>Volume</th>
                <th>Total Cost (₹)</th>
                <th>Options</th>
              </tr>
            </thead>

            <tbody>
            	@if(isset($allocation)) @foreach($allocation as $key => $allocation)
          	  <tr>
                <td>{{$allocation->id}}</td>
                <td>{{$allocation->refno}}</td>
                <td>{{$allocation->contractor->c_name}}</td>
                <td>{{$allocation->product->name}}</td>
                <td>{{$allocation->tvol}}</td>
                <td>{{$allocation->tcost}}</td>
                <td>
                  <span class="tool-tip1" data-bs-toggle="tooltip" data-bs-placement="top" title="View">
              			<button class="btn btn-info" onclick="updateallocation('{{$allocation->id}}')">
              				<i class="fa fa-edit"></i>
              			</button>
                  </span>
                  <span class="tool-tip1" data-bs-toggle="tooltip" data-bs-placement="top" title="Delete">
                    <button class="btn btn-danger" data-bs-toggle="modal" data-bs-target="#myModal{{$allocation->id}}">
                      <i class="fa fa-trash"></i>
                    </button>
                  </span>
            			<!-- <span class="tool-tip1" data-bs-toggle="tooltip" data-bs-placement="top" title="Delete">
                    <button class="btn btn-danger" onclick="deleteallocation('{{$allocation->id}}')">
                      <i class="fa fa-trash"></i>
                    </button>
                  </span> -->
                  <!-- MODAL FOR DELETE -->
                  <div class="modal fade" id="myModal{{$allocation->id}}" role="dialog">
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
                          <button onclick="deleteallocation('{{$allocation->id}}')" class="btn btn-danger">Yes</button>
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


<!-- Script start for allocation delete -->
<script type="text/javascript">
  
  $(function (){
    $('[data-bs-toggle="tooltip"]').tooltip()
  });

  function deleteallocation(id){
      location.href = "{{ url('/allocation/delete') }}" + '/' +id;
  } 
 
  function updateallocation(id){
      location.href = "{{ url('/allocation/view') }}" + '/' +id;
  }

  var products = [];

    $('#ucost, #tqty, #pVolume').change(function () {
        var pvol = $('#pVolume').val();
        console.log(pvol);
        var tqty = $('#tqty').val();
        console.log(tqty);
        var tvol = (pvol*tqty).toFixed(4);
        $('#tvol').val(tvol);
        console.log(tvol);
        var ucost = $('#ucost').val();
        var subtotal = parseFloat(ucost) * parseFloat(tvol);
        var total =subtotal.toFixed(2);
        $('#tcost').val(total);
    });


</script>
<!-- Script end -->

@endsection