@extends('layouts.app')

@section('content')

    <div class="row mx-3 my-2">
      <h2>Shut Out Report</h2>

    </div>
    
    <div class="card mb-3">
      <div class="card-body">
        <div class="table-responsive">
          <table class="table table-bordered" id="dataTable" width="100%" cellspacing="0">
            <thead>
              <tr>
                <th>#</th>
                <th>Product</th>
                <th>Invoice No.</th>
                <th>Buyer Ref. No.</th>
                <th>Order Quantity</th>
                <th>Remaining Quantity</th>
              </tr>
            </thead>
            <tbody>
            	@if(isset($invoiceTable)) @foreach($invoiceTable as $key => $invTable)
              <tr>
               <td>{{++$key}}</td>
                <td>{{$invTable->product->code}} - {{$invTable->product->name}}</td>
                <td>{{$invTable->invoice->invoiceno}}</td>
                <td>{{$invTable->invoice->buyerorderno}}</td>
                <td>{{$invTable->quantity}}</td>
                <td>{{$invTable->remqty}}</td>
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
              <form>
                <input type="date" name="fromstartDate" id="fsd" value="" />
                <input type="date" name="fromendDate" id="fed" value="" />
              </form>
              </div>
              <div class="modal-footer">
                <button type="button" class="btn btn-default" data-bs-dismiss="modal">No</button>
                <button onclick="stockoutTableDate()" class="btn btn-danger">Yes</button>
              </div>
            </div>
          </div>
        </div>
    </div>
@endsection

@section('footer')

@endsection