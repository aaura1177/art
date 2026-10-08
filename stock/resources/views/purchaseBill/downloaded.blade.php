@extends('layouts.app')

@section('content')

    <div class="row mx-3 my-2">
      <h2>Downloaded Inward Supply</h2>
      <div class="col">
         <a class="btn btn-success float-end" style="color: #fff; margin-left: 5px;"  href="{{ url('/purchaseBill/verified') }}">Go Back</a>
        <!-- <a class="btn btn-primary float-end" style="color: #fff;" href="{{ url('/purchaseBill/create')}}">Add Inward Supply</a></div> -->
    </div>
    
    <div class="card mb-3">
      <div class="card-body">
        <div class="table-responsive">
          <table class="table table-bordered" id="dataTable" width="100%" cellspacing="0">
            <thead>
              <tr>
                <th>PO No.</th>
                <th>Supplier Ref. No.</th>
                <th>Supplier Inv. No.</th>
                <th>Supplier Name</th>
                <th>Supplier Inv. Date</th>
                <th>Total Quantity</th>
                <th>Total Amount</th>
                <th>Verified</th>
                <th style="width: 80px;">Options</th>
                <th>Products</th>
              </tr>
            </thead>
            <tbody>
              @if(isset($purchaseBill)) @foreach($purchaseBill as $key => $purchaseBill)
              <tr>
                <td>{{$purchaseBill->purchaseOrder->pono}}</td>
                <td>{{$purchaseBill->purchaseOrder->ref_supplier}}</td>
                <td>{{$purchaseBill->supp_inv_no}}</td>
                <td>{{$purchaseBill->purchaseOrder->supplier->c_name}}</td>
                <td>{{date('d-M-Y',strtotime($purchaseBill->supp_inv_date))}}</td>
                <td>{{$purchaseBill->quantity}}</td>
                <td>{{$purchaseBill->total}}</td>
                <td>@if($purchaseBill->is_checked == 0){{'Pending'}}@endif
                    @if($purchaseBill->is_checked == 1){{'Verified'}}@endif
                </td>
                <td>
                    <span class="tool-tip1" data-bs-toggle="tooltip" data-bs-placement="top" title="View">
                       <a class="btn btn-primary" style="color: #fff;" href="{{ URL::to('/purchaseBill/modal/'.$purchaseBill->id)}}" target="_blank"><i class="fa fa-eye"></i></a>
                    </span>
                    <span class="tool-tip1" data-bs-toggle="tooltip" data-bs-placement="top" title="Print">
                       <a class="btn btn-success" style="color: #fff;" href="{{ URL::to('/purchaseBill/modal/'.$purchaseBill->id.'?print=1')}}" target="_blank"><i class="fa fa-print"></i></a>
                    </span>   
                    @if($purchaseBill->is_checked == 0)                 
                    <span class="tool-tip1" data-bs-toggle="tooltip" data-bs-placement="top" title="Verifiy">
                        <button class="btn btn-warning" data-bs-toggle="modal" onclick="verifyModal('{{$purchaseBill->id}}')">
                          <i class="fas fa-check-circle"></i>
                        </button>
                    </span>
                    @endif
                </td>
                <td>
                  @foreach($purchaseBill->pbTable as $pbTable)
                    {{(isset($pbTable->product->code))?$pbTable->product->code .'('. $pbTable->receiveqty .'/'. $pbTable->orderqty .')':''}} <br>
                  @endforeach
                </td>
              </tr>
              @endforeach @endif
            </tbody>
          </table>
        </div>
      </div>
        
      <!-- MODAL FOR DATE -->
        <div class="modal fade" id="mydateModal" role="dialog">
          <div class="modal-dialog">
            <div class="modal-content">
              <div class="modal-header">
                <h4 class="modal-title">Enter Date</h4>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>  
              </div>
              <div class="modal-body">
              <form method="POST" action="{{ url('/purchaseBill/exportcsv') }}">
                @csrf
                  <div class="row">
                    <div class="col-6">
                        <input class="form-control" type="date" name="fsd" id="fsd" required value="" />
                    </div>
                    <div class="col-6">
                        <input class="form-control" type="date" name="fed" id="fed" required value="" />
                    </div>
                  </div>
              </div>
              <div class="modal-footer">
                <button type="submit" onclick="return validate()" class="btn btn-success">Download</button>
              </div>
              </form>
            </div>
          </div>
        </div>

        <!-- MODAL FOR DELETE -->
      <div class="modal fade" id="verifyInward" role="dialog">
        <div class="modal-dialog">
          <div class="modal-content">
            <div class="modal-header">
              <h4 class="modal-title">Verify Confirmation</h4>
              <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>  
            </div>
            <form id="verifyInwardForm" method="POST" action="">
            @csrf  
              <div class="modal-body">
                <p>Are You sure you want to verify this?</p>
              </div>
              <div class="modal-footer">
                <button type="button" class="btn btn-default" data-bs-dismiss="modal">No</button>
                <button type="submit" id="verifyInwardForm" class="btn btn-danger">Yes</button>
              </div>
            </form>  
          </div>
        </div>
      </div>

    </div>
@endsection

@section('footer')


<!-- Script start for PO delete -->
<script type="text/javascript">

  function validate(){
    var fsd = $('#fsd').val();
    var fed = $('#fed').val();
    if(fsd > fed){
        alert('Start Date should be less than End Date');
        return false;
    }
    return true;
  }

  $(function () {
    $('[data-bs-toggle="tooltip"]').tooltip()
  });

  // function deletepb(id){
  //   location.href = "{{ url('/purchaseBill/delete') }}" + '/' +id;
  // }

  function updatePurchaseBill(id)
    {
      location.href = "{{ url('/purchaseBill/view') }}" + '/' +id;
    }

</script>
<!-- Script end -->

@endsection