@extends('layouts.app')

@section('content')

    <div class="row mx-4 my-2">
      <h2>Reject / Repair (Outward Challan)</h2>
      <div class="col">
        <a class="btn btn-primary float-end" style="color: #fff;margin-left:5px;" href="{{ url('/rejectRepair/create')}}">Add Reject/Repair</a>
        <a class="btn btn-danger float-end" style="color: #fff;margin-left:5px;" href="{{ url('/rejectRepair?filter=reject')}}">Reject</a>
        <a class="btn btn-warning float-end" style="color: #fff;margin-left:5px;" href="{{ url('/rejectRepair?filter=repair')}}">Repair</a>
        <a class="btn btn-danger float-end" style="color: #fff;margin-left:5px;" href="{{ url('/rejectRepair?filter=pending_debit')}}">Pending for Debit note</a>
        <a class="btn btn-danger float-end" style="color: #fff;margin-left:5px;" href="{{ url('/rejectRepair?filter=view_debit')}}">View Debit notes</a>
        <a class="btn btn-success float-end" style="color: #fff;margin-left:5px;" href="{{ url('/rejectRepair?filter=complete')}}">Completed</a>
        <a class="btn btn-success float-end" style="color: #fff;margin-left:5px;" href="{{ url('/rejectRepair?filter=convert')}}">Convert</a>
        <a class="btn btn-success float-end" style="color: #fff;margin-left:5px;" href="{{ url('/rejectRepair')}}">All</a>
      </div>
    </div>

    <div class="card mb-3">
      <div class="card-body">
        <div class="table-responsive">
          <table class="table table-bordered" id="dataTabless" width="100%" cellspacing="0">
            <thead>
              <tr>
                <th>#</th>
                <th>Product</th>
                <th>Supplier</th>
                 <th>Supplier Inv. No.</th>
                <th>Qty</th>
                <th>Received Qty</th>
                <th>Status</th>
                <th>Date</th>
                <th>Outward Challan No.</th>
                <th>Sent to</th>
                <th>Remarks</th>
                <th>Options</th>
              </tr>
            </thead>

            <tbody>
            	@if(isset($rejectRepair)) @foreach($rejectRepair as $key => $rejectRepair)
              
              <?php $reject=App\rejectRepair::find( $rejectRepair->reject_repair_id); ?>
          	  <tr>
                <td>{{++$key}}</td>
                <td>{{$rejectRepair->product->code}} - {{$rejectRepair->product->name}}</td>
                <td>{{$reject->supplier->c_name}}</td>
                <td>{{$rejectRepair->supplier_inv_no}}</td>
                <td>{{$rejectRepair->quantity}}</td>
                <td>{{$rejectRepair->receiveqty}}</td>
                <td>
                  @if($rejectRepair->status==1)
                  Reject

                  @elseif($rejectRepair->status==2)
                  Repair
                  @elseif($rejectRepair->status==3)
                  Convert
                  @elseif($rejectRepair->status==0)
                  Complete
                  @endif
                </td>
                <td>{{date('d M, Y',strtotime($rejectRepair->date))}}</td>
                <td>{{$rejectRepair->outward_challan_no}}</td>
                <td>{{($rejectRepair->send_to_supplier == 0)?$reject->supplier->c_name:$rejectRepair->sent_to_supplier->c_name}}</td>
                <td>{{$rejectRepair->remarks}}</td>
                <td>
                  @if($rejectRepair->status!=0 && $rejectRepair->status!=1)
                  <?php if($rejectRepair->is_debit_note){
                    $disabled = 'disabled';
                  }else{
                    $disabled = '';
                  } ?>
                  <span class="tool-tip1" data-bs-toggle="tooltip" data-bs-placement="top" title="{{($rejectRepair->status==0)?'Already marked complete':'Mark Complete'}}"  >
              			<button {{$disabled}} class="btn btn-info" onclick="markComplete('{{$rejectRepair->id}}','{{$rejectRepair->quantity}}',{{$rejectRepair->p_id}},'{{$rejectRepair->receiveqty}}')">
              				<i class="fa fa-check"></i>
              			</button>
                  </span>
                  
                  @endif
                  @if($rejectRepair->status==2 || $rejectRepair->status==1)
                    @if($rejectRepair->is_debit_note)
                      <a class="btn btn-success" href="{{ URL::to('/rejectRepair/viewDebitNote/'.$rejectRepair->id)}}" target="_blank" >View Debit Note</a>
                    @else
                      <?php $date_now = date('Y-m-d');
                          $created_date = date('Y-m-d', strtotime($rejectRepair->date));
                          $date_selected   = date("Y-m-d", strtotime('+360 hours', strtotime($created_date)));  ?>
                      @if($rejectRepair->status==1)
                            <a class="btn btn-danger" href="{{ URL::to('/rejectRepair/createDebitNote/'.$rejectRepair->id)}}" onclick="return confirm('Are you sure you want to create the debit note?')">Pending for Debit Note</a>
                      @else

                        @if($date_now > $date_selected)
                          <a class="btn btn-danger" href="{{ URL::to('/rejectRepair/createDebitNote/'.$rejectRepair->id)}}" onclick="return confirm('Are you sure you want to create the debit note?')">Pending for Debit Note</a>
                        @endif
                      @endif
                    @endif
                    @if($rejectRepair->is_challan_raised==1)
                      <a class="btn btn-primary" href="{{ URL::to('/rejectRepair/viewChallan/'.$rejectRepair->id)}}">View Challan</a>
                    @endif
                  @endif
                  <span class="tool-tip1" data-bs-toggle="tooltip" data-bs-placement="top" title="{{($rejectRepair->status!=0)?'Can\'t delete! Reject/Repair under process.':'Delete'}}">
                    <button class="btn btn-danger" data-bs-toggle="modal" onclick="deleteModal('{{$rejectRepair->id}}')" {{ $rejectRepair->status>0?'disabled':'' }} >
                      <i class="fa fa-trash"></i>
                    </button>
                  </span>
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
    $('#dataTabless').dataTable( {
      "ordering": false
    } );
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
