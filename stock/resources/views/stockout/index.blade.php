@extends('layouts.app')

@section('content')

    <div class="row mx-3 my-2">
      <h2>StockOut</h2>
      <div class="col"><a class="btn btn-primary float-end" style="color: #fff;" href="{{ url('/stockout/create')}}">StockOut</a></div>
    </div>
    
    <div class="card mb-3">
      <div class="card-body">
        <div class="table-responsive">
          <table class="table table-bordered" id="dataTable" width="100%" cellspacing="0">
            <thead>
              <tr>
                <th>Stock No.</th>
                <th>Invoice No.</th>
                <th>Buyer Ref. No.</th>
                <th>Date</th>
                <th>Options</th>
                <th>Products</th>
              </tr>
            </thead>
            <tbody>
              @if(isset($stockout)) @foreach($stockout as $key => $stockout)
              <tr>
                <td>{{$stockout->id}}</td>
                <td>
  @if($stockout->invoice_id == 0)
    {{$stockout->buyer_ref_no}}
  @else
    {{ optional($stockout->invoice)->invoiceno ?? $stockout->buyer_ref_no }}
  @endif
</td>

                <td>{{$stockout->buyer_ref_no}}</td>
                <td>{{date('d-M-Y',strtotime($stockout->created_at))}}</td>
                <td>
                  @if($stockout->stockoutRequestBackup)
        <span class="tool-tip1" data-bs-toggle="tooltip" data-bs-placement="top" title="Edit">
            <a class="btn btn-warning" style="color: #fff;" href="{{ URL::to('/stockout/edit/'.$stockout->invoice_id) }}">
                <i class="fa fa-edit"></i>
            </a>
        </span>
    @endif
                  <span class="tool-tip1" data-bs-toggle="tooltip" data-bs-placement="top" title="View">
                       <a class="btn btn-primary" style="color: #fff;" href="{{ URL::to('/stockout/modal/'.$stockout->id)}}" target="_blank"><i class="fa fa-eye"></i></a>
                  </span>
                </td>
                <td>
                  @foreach($stockout->stockoutTable as $stockoutTable)
                    {{(isset($stockoutTable->product->code))?$stockoutTable->product->code .'('. $stockoutTable->receiveqty .'/'. $stockoutTable->orderqty .')':''}} <br>
                  @endforeach
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
  
</script>
<!-- Script end -->

@endsection