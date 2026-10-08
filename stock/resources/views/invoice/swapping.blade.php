@extends('layouts.app')

@section('content')

      <!-- first row -->
      <div class="row mx-3 my-2">
        <h2>Swapping List</h2>
        <div class="col">
          <a class="btn btn-primary float-end" style="color: #fff;" href="{{ url('/invoice/createswapping')}}">Create Swapping</a>
        </div>
      </div>

      <div class="card mb-3">
        <div class="card-body">
          <div class="table-responsive">
            <table class="table table-bordered" id="swappingTable" width="100%" cellspacing="0">
              <thead>
                <tr>
                  <th>Reference No</th>
                  <th>Product Out</th>
                  <th>Product In</th>
                  <th>Quantity</th>
                  <th>Date</th>
                </tr>
              </thead>
              <tbody>
              	@if(isset($swaps)) @foreach($swaps as $key => $swap)
                <tr>
                  <td>{{$swap->invoice_no}}</td>
                  <td>{{$swap->product->code}} - {{$swap->product->name}}</td>
                  <td>{{$swap->swappedWith->code}} - {{$swap->swappedWith->name}}</td>
                  <td>{{$swap->quantity}}</td>
                  <td data-order="{{ $swap->created_at }}">{{date('d F Y',strtotime($swap->created_at))}}</td>
                </tr>
                @endforeach @endif
              </tbody>
            </table>
          </div>
        </div>
      </div>
@endsection

@section('footer')


<!-- Scripts Start For Hardware Delete/View -->
<script type="text/javascript">

  $(function () {
    $('[data-bs-toggle="tooltip"]').tooltip();

    // Use a page-specific table id so global #dataTables init does not re-sort by Reference No
    $('#swappingTable').DataTable({
      order: [[4, 'desc']]
    });
  });

</script>
<!-- Scripts End -->

@endsection
