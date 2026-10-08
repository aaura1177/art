@extends('layouts.app')

@section('content')

    <div class="row mx-3 my-2">
      <h2>Reject / Repair</h2>
      <div class="col">
        
      </div>
    </div>

    <div class="card mb-3">
      <div class="card-body">
        <div class="table-responsive">
          <table class="table table-bordered" id="dataTable" width="100%" cellspacing="0">
            <thead>
              <tr>
                <th>#</th>
                <th>Product</th>
                 <th>Supplier Inv. No.</th>
                <th>Qty</th>
                <th>Received Qty</th>
                <th>Status</th>
                <th>Date</th>
                <th>Remarks</th>
                <th>Action</th>
              </tr>
            </thead>

            <tbody>
            	@if(isset($rejectRepair)) @foreach($rejectRepair as $key => $rejectRepair)
              <?php $reject=App\rejectRepair::find( $rejectRepair->reject_repair_id); ?>
          	  <tr>
                <td>{{++$key}}</td>
                <td>{{$rejectRepair->product->code}} - {{$rejectRepair->product->name}}</td>
                <td>{{$rejectRepair->supplier_inv_no}}</td>
                <td>{{$rejectRepair->quantity}}</td>
                <td>{{$rejectRepair->receiveqty}}</td>
                <td>
                  @if($rejectRepair->status==1)
                  Reject

                  @elseif($rejectRepair->status==2)
                  Repair
                  @elseif($rejectRepair->status==0)
                  Complete
                  @endif
                </td>
                <td>{{date('d M, Y',strtotime($rejectRepair->date))}}</td>
                <td>{{$rejectRepair->remarks}}</td>
                <td>
                  @if($rejectRepair->status==2)
                    @if($rejectRepair->is_challan_raised==1)
                      <a class="btn btn-primary" href="{{ URL::to('/supplier-dashboard/viewChallan/'.$rejectRepair->id)}}">View Challan</a>
                    @else
                      <a class="btn btn-primary" href="{{ URL::to('/supplier-dashboard/raiseChallan/'.$rejectRepair->id)}}">Raise Challan</a>
                    @endif
                  @endif
                </td>
              </tr>
              @endforeach @endif
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <!-- MODAL FOR DELETE -->
    <div class="modal fade" id="deleteRejectRepair" role="dialog">
      <div class="modal-dialog">
        <div class="modal-content">
          <div class="modal-header">
            <h4 class="modal-title">Delete Confirmation</h4>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <form id="deleteRejectRepairForm" method="POST" action="">
          @csrf
            <div class="modal-body">
              <p>Are You sure you want to Delete this?</p>
            </div>
            <div class="modal-footer">
              <button type="button" class="btn btn-default" data-bs-dismiss="modal">No</button>
              <button type="submit" id="deleteRejectRepairForm" class="btn btn-danger">Yes</button>
            </div>
          </form>
        </div>
      </div>
    </div>


	<!-- MODAL FOR Complete -->
    <div class="modal fade" id="completeRejectRepair" role="dialog">
      <div class="modal-dialog">
        <div class="modal-content">
          <div class="modal-header">
            <h4 class="modal-title">Complete Confirmation</h4>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <form id="completeRejectRepairForm" method="POST" action="">
          @csrf
            <div class="modal-body">
				<input id="receiveqty" type="number" name="receiveqty" min ="1" />
        <input id="p_id" type="hidden" name="p_id" />


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


<!-- Script start for RejectRepair complete/delete -->
<script type="text/javascript">

  $(function () {
    $('[data-bs-toggle="tooltip"]').tooltip()
  });

  function deleteModal(id){
    $('#deleteRejectRepair').modal('show');
    $('#deleteRejectRepairForm').attr('action', "{{ url('/rejectRepair/delete') }}" + '/' +id);
  }

  function markComplete(id,qty,p_id,rec){
	  $('#completeRejectRepair').modal('show');
	  $('#completeRejectRepairForm').attr('action', "{{ url('/rejectRepair/complete') }}" + '/' +id);
    mqty = qty - rec;
	  $('#receiveqty').attr('max', mqty);
    $('#p_id').val(p_id);
    //location.href = "{{ url('/rejectRepair/complete') }}" + '/' +id;
  }

</script>
<!-- Script end -->

@endsection
