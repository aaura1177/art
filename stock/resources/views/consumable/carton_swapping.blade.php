@extends('layouts.app')

@section('content')
<div class="row mx-3 my-2">
    <h2>Carton Swapping List</h2>
    <div class="col">
        <a class="btn btn-primary float-end" style="color: #fff;" href="{{ url('/carton-swapping/create') }}">
            Create Carton Swapping
        </a>
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
                        <th>Carton Type</th>
                        <th>Quantity</th>
                        <th>Date</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($swaps as $swap)
                        <tr>
                            <td>{{ $swap->invoice_no }}</td>
                            <td>{{ optional(optional($swap->packagingOut)->product)->code }} - {{ optional(optional($swap->packagingOut)->product)->name }}</td>
                            <td>{{ optional(optional($swap->packagingIn)->product)->code }} - {{ optional(optional($swap->packagingIn)->product)->name }}</td>
                            <td>{{ $swap->carton_type === 'box_1_qty' ? 'Box 1' : 'Box 2' }}</td>
                            <td>{{ $swap->quantity }}</td>
                            <td>{{ date('d F Y', strtotime($swap->created_at)) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
