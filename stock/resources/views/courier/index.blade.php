@extends('layouts.app')

@section('content')

      <!-- first row -->
      <div class="row mx-3 my-2">
        <h2>Courier List</h2>
        <div class="col">
          <a class="btn btn-secondary float-end ms-2" style="color: #fff;" href="{{ url('/courier/history')}}">Change history</a>
          <a class="btn btn-primary float-end" style="color: #fff;" href="{{ url('/courier/create')}}">Add Courier</a>
        </div>
      </div>

      <div class="card mb-3">
        <div class="card-body">
          <div class="table-responsive">
            <table class="table table-bordered" id="dataTables" width="100%" cellspacing="0">
              <thead>
                <tr>
                  <th>Courier Name</th>
                  <th>Courier Rate</th>
                  <th>Default Courier</th>
                  <th>Courier Country</th>
                  <th>Options</th>
                </tr>
              </thead>
              <tbody>
              	@if(isset($courier)) @foreach($courier as $key => $couriers)
                <tr>
                  <td>{{$couriers->name}}</td>
                  <td>{{$couriers->rate}}</td>
                  <td>{{$couriers->is_default == 1 ? 'Yes' : 'No'}}</td>
                  <td>{{$couriers->country}}</td>
                  <td>
                      <span class="tool-tip1" data-bs-toggle="tooltip" data-bs-placement="top" title="Edit">
                        <button class="btn btn-info" onclick="updateCourier('{{$couriers->id}}')">
            							<i class="fa fa-edit"></i>
            						</button>
                      </span>
                      <span class="tool-tip1" data-bs-toggle="tooltip" data-bs-placement="top" title="Delete">
                        <a href="{{ url('/courier/delete/'.$couriers->id) }}" onclick="return confirm('Are you sure you want to delete?');"><button class="btn btn-danger">
            							<i class="fa fa-trash"></i>
            						</button></a>
                      </span>
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


<!-- Scripts Start For Courier Delete/View -->
<script type="text/javascript">

  $(function () {
  $('[data-bs-toggle="tooltip"]').tooltip()
  });

  function updateCourier(id){

     location.href = "{{ url('/courier/view') }}" + '/' +id;
  }

</script>
<!-- Scripts End -->

@endsection
