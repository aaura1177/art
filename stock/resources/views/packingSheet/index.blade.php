@extends('layouts.app')

@section('content')

    <div class="row mx-3 my-2">
      <h2>Packing Sheet</h2>
      <div class="col"><a class="btn btn-primary float-end" style="color: #fff;" href="{{ url('/packingSheet/create')}}">Add Packing Sheet</a></div>
    </div>
    
    <div class="card mb-3">
      <div class="card-body">
        <div class="table-responsive">
          <table class="table table-bordered" id="dataTable" width="100%" cellspacing="0">
            <thead>
              <tr>
                <th>#</th>
                <th>PS No.</th>
                <th>Invoice No.</th>
                <th>Total Quantity</th>
                <th>Total Boxes</th>
                <th>Net Wt.</th>
                <th>Gross Wt.</th>
                <th>Options</th>
              </tr>
            </thead>
            <tbody>
              @if(isset($packingSheet)) @foreach($packingSheet as $key => $packingSheet)
              <tr>
                <td>{{++$key}}</td>
                <td>{{$packingSheet->invoice_id}}</td>
                <td>{{$packingSheet->invoice_id}}</td>
                <td>{{$packingSheet->quantity}}</td>
                <td>{{$packingSheet->totalbox}}</td>
                <td>{{$packingSheet->netwt}}</td>
                <td>{{$packingSheet->grosswt}}</td>
                <td>
                    <span class="tool-tip1" data-bs-toggle="tooltip" data-bs-placement="top" title="Edit">
                      <button class="btn btn-info" onclick="updatePackingSheet('{{$packingSheet->invoice_id}}')">
                        <i class="fa fa-edit"></i>
                      </button>
                    </span>
                    <span class="tool-tip1" data-bs-toggle="tooltip" data-bs-placement="top" title="View">
                       <a class="btn btn-primary" style="color: #fff;" href="{{ URL::to('/purchaseOrder/modal/'.$packingSheet->id)}}" target="_blank"><i class="fa fa-eye"></i></a>
                    </span>
                    <span class="tool-tip1" data-bs-toggle="tooltip" data-bs-placement="top" title="Print">
                       <a class="btn btn-success" style="color: #fff;" href="{{ URL::to('/purchaseOrder/modal/'.$packingSheet->id.'?print=1')}}" target="_blank"><i class="fa fa-print"></i></a>
                    </span>
                    <!-- <span class="tool-tip1" data-bs-toggle="tooltip" data-bs-placement="top" title="Delete">
                      <button class="btn btn-danger" onclick="deletepb('{{$packingSheet->id}}')" >
                      <i class="fa fa-trash"></i>
                      </button>
                    </span> -->
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


<!-- Script start for PO delete -->
<script type="text/javascript">

  $(function () {
    $('[data-bs-toggle="tooltip"]').tooltip()
  });

  function updatePackingSheet(id)
    {
      location.href = "{{ url('/packingSheet/view') }}" + '/' +id;
    }


  // function deletepb(id){
  //   location.href = "{{ url('/packingSheet/delete') }}" + '/' +id;
  // }

</script>
<!-- Script end -->

@endsection