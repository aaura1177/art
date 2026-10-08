@extends('layouts.app')

@section('content')
<div class="row mx-3 my-2">
  <h2>Carton Swapping List</h2>
  <div class="col">
    <a class="btn btn-primary float-end" style="color: #fff;" href="{{ url('/invoice/createcartonswapping') }}">Create Carton Swapping</a>
  </div>
</div>

<div class="card mb-3">
  <div class="card-body">
    <div class="table-responsive">
      <table class="table table-bordered" id="dataTables" width="100%" cellspacing="0">
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
          @if(isset($swaps))
            @foreach($swaps as $swap)
              <tr>
                <td>{{ $swap->invoice_no }}</td>
                <td>{{ optional($swap->product)->code }} - {{ optional($swap->product)->name }}</td>
                <td>{{ optional($swap->swappedWith)->code }} - {{ optional($swap->swappedWith)->name }}</td>
                <td>{{ $swap->quantity }}</td>
                <td>{{ date('d F Y', strtotime($swap->created_at)) }}</td>
              </tr>
            @endforeach
          @endif
        </tbody>
      </table>
    </div>
  </div>
</div>
@endsection
