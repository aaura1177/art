@extends('layouts.app')

@section('content')

    <div class="row mx-3 my-2 align-items-center">
      <div class="col">
        <h2 class="mb-0">Purchase Bills Multiple</h2>
      </div>
      <div class="col-auto">
        <a class="btn btn-success" style="color: #fff;" data-bs-toggle="modal" data-bs-target="#mydateModal" href="{{ url('/purchaseBill/billstracking')}}">Download Bills Due Tracking</a>
      </div>
    </div>

    <div class="card mb-3">
      <div class="card-body">
        <form method="GET" action="" class="row g-2 justify-content-end mb-3">
          <div class="col-auto">
            <input
              type="text"
              name="search"
              value="{{ request('search') }}"
              placeholder="Search"
              class="form-control"
            />
          </div>
          <div class="col-auto d-flex gap-2">
            <button type="submit" class="btn btn-primary">Search</button>
            <a href="{{ url('/purchaseBill/condition/Multi') }}" class="btn btn-warning">Reset</a>
          </div>
        </form>
        <div class="table-responsive">
          <table class="table table-bordered" id="purchaseBillsMultiTable" width="100%" cellspacing="0">
            <thead>
              <tr>
                <th>PO No.</th>
                <th>Supplier Ref. No.</th>
                <th>Supplier Inv. No.</th>
                <th>Supplier Name</th>
                <th>Supplier Inv. Date</th>
                <th style="min-width: 11rem;">Payment Status</th>
                <th>Total Quantity</th>
                <th>Total Amount</th>
                <th>Verified</th>
                <th>Tally status</th>
                <th>Due Date</th>
                <th style="min-width: 7rem;">Verify</th>
                <th style="min-width: 8rem;">Products</th>
              </tr>
            </thead>
            @php
    use App\purchaseOrder;
@endphp
            <tbody>
              @if(isset($purchaseBills)) @foreach($purchaseBills as $key => $purchaseBill)
               @php
$poNos = array_filter(array_map('trim', explode(',', (string) $purchaseBill->mulitple_po_purchaseBill)));

$purchaseOrders = \App\purchaseOrder::whereIn('pono', $poNos)->get();

$supplierRefs = $purchaseOrders->pluck('ref_supplier')->unique()->values();
@endphp

              @if(isset($purchaseBill->purchaseOrder_id ))
              <tr 
                <?php if($purchaseBill->payment_status == 2) { ?>style="background: #bbb;" <?php } ?>
                <?php if($purchaseBill->payment_status == 3) { ?>style="background: #F4C7C3;" <?php } ?>
                <?php if($purchaseBill->payment_status == 4) { ?>style="background:#FCE8B2;" <?php } ?>
                <?php if($purchaseBill->payment_status == 5) { ?>style="background: rgb(183, 225, 205);" <?php } ?>
                >
                <td>{{ $purchaseBill->mulitple_po_purchaseBill}}</td>
               <td>{{ $supplierRefs->join(', ') }}</td>

                <td>{{$purchaseBill->supp_inv_no}}</td>
                <td>{{$purchaseBill->supplier->c_name ?? 'N/A'}}</td>
                <td>{{date('d-M-Y',strtotime($purchaseBill->supp_inv_date))}}</td>
                <td>
                  <span id="payment_status_{{ $purchaseBill->id }}" class="d-none">
                  <?php
                  if($purchaseBill->payment_status == 1){
                    echo "UnShipped + UnPaid";
                  }
                  if($purchaseBill->payment_status == 2){
                    echo "Payment When Due";
                  }
                  if($purchaseBill->payment_status == 3){
                    echo "UnShipped + Paid";
                  }
                  if($purchaseBill->payment_status == 4){
                    echo "Shipped + UnPaid";
                  }
                  if($purchaseBill->payment_status == 5){
                    echo "Shipped + Paid";
                  }
                  ?>
                  </span>
                  <select class="form-select form-select-sm" style="width: auto; min-width: 11rem;" onchange="updatePaymentStatus({{ $purchaseBill->id }},this)">
                    <option value="1" {{ ($purchaseBill->payment_status == 1)?'selected':'' }}>UnShipped + UnPaid</option>
                    <option value="2" {{ ($purchaseBill->payment_status == 2)?'selected':'' }}>Payment When Due</option>
                    <option value="3" {{ ($purchaseBill->payment_status == 3)?'selected':'' }}>UnShipped + Paid</option>
                    <option value="4" {{ ($purchaseBill->payment_status == 4)?'selected':'' }}>Shipped + UnPaid</option>
                    <option value="5" {{ ($purchaseBill->payment_status == 5)?'selected':'' }}>Shipped + Paid</option>
                  </select>
              </td>
                <td>{{$purchaseBill->quantity}}</td>
                <td>{{$purchaseBill->total}}</td>
                <td id="verifyStat{{$purchaseBill->id}}">@if($purchaseBill->is_checked == 0){{'Pending'}}@endif
                    @if($purchaseBill->is_checked == 1){{'Verified'}}@endif
                </td>
                <td>
          @if($purchaseBill->status == 0){{'Pending'}}@endif
          @if($purchaseBill->status == 1){{'Complete'}}@endif
      </td>
                <td>
                  <?php echo date('d M Y',strtotime($purchaseBill->created_at . ' + 30 days')); ?>
                </td>
                <td>
                  <div class="d-inline-flex flex-nowrap gap-1">
                  <span class="tool-tip1" data-bs-toggle="tooltip" data-bs-placement="top" title="View">
                     <a class="btn btn-primary btn-sm" style="color: #fff;" href="{{ URL::to('/purchaseBill/modal/multi/'.$purchaseBill->id)}}" target="_blank"><i class="fa fa-eye"></i></a>
                  </span>
                  
                  @if($purchaseBill->is_checked == 0)                 
                  <span class="tool-tip1" data-bs-toggle="tooltip" data-bs-placement="top" title="Verify" id="verifyBtn{{$purchaseBill->id}}">
                      <button type="button" class="btn btn-warning btn-sm" data-bs-toggle="modal" onclick="verifyModal('{{$purchaseBill->id}}')">
                        <i class="fas fa-check-circle"></i>
                      </button>
                  </span>
                  @endif
                  <span class="tool-tip1" data-bs-toggle="tooltip" data-bs-placement="top" title="Update Tally Status">
            <a class="btn btn-success btn-sm" style="color: #fff;" href="javascript:void(0);"
              onclick="tallyStatus('{{$purchaseBill->id}}')"><i class="fas fa-balance-scale"></i></a>
            </span>
                  </div>
              </td>
              
                <td>
                  @foreach($purchaseBill->pbTable as $pbTable)
                    {{(isset($pbTable->product->code))?$pbTable->product->code .'('. $pbTable->receiveqty .'/'. $pbTable->orderqty .')':''}} <br>
                  @endforeach
                </td>
              </tr>
              @endif
              @endforeach @endif
            </tbody>
          </table>
          <div class="mt-4">
              @if(isset($purchaseBills))
              {{ $purchaseBills->links() }}
              @endif
          </div>
          
        </div>
      </div>

      <!-- MODAL FOR TALLY STATUS -->
  <div class="modal fade" id="statusTally" role="dialog">
    <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
      <h4 class="modal-title">Update Confirmation</h4>
      <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <form id="statusTallyFORM" method="POST" action="">
      @csrf
      <div class="modal-body">
        <div class="row">
        <div class="col-6">
          <select class="form-control" name="tallystatus" required>
          <option value="">Select Status</option>
          <option value="0">Pending</option>
          <option value="1">Completed</option>
          </select>
        </div>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-default" data-bs-dismiss="modal">Cancel</button>
        <button type="submit" id="" class="btn btn-success">Update</button>
      </div>
      </form>
    </div>
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
              <form method="POST" action="{{ url('/purchaseBill/billstracking') }}">
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
              <input type="hidden" id="verifyId" />
              <div class="modal-body">
                <p>Are You sure you want to verify this?</p>
              </div>
              <div class="modal-footer">
                <button type="button" class="btn btn-default" data-bs-dismiss="modal">No</button>
                <button type="submit" class="btn btn-danger">Yes</button>
              </div>
            </form>
          </div>
        </div>
      </div>

    </div>

  <form id="poForm" method="POST" action="{{ url('/purchaseBill/isTallyExport') }}" style="visibility:hidden;">
    @csrf
    <input type="hidden" id="pb_ids" name="pb_ids" />
    <button type="submit" onclick="return false" class="btn btn-success">Download</button>
  </form>
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
    if ($.fn.DataTable && $('#purchaseBillsMultiTable').length) {
      $('#purchaseBillsMultiTable').DataTable({
        paging: false,
        info: false,
        searching: false,
        ordering: false,
      });
    }

    $('[data-bs-toggle="tooltip"]').tooltip()
    $("#checkedAll").change(function() {
        if (this.checked) {
            $(".checkSingle").each(function() {
                this.checked=true;
                selectPo($(this));
            });
        } else {
            $(".checkSingle").each(function() {
                this.checked=false;
                selectPo($(this));
            });
        }
    });

    $(".checkSingle").click(function () {
        if ($(this).is(":checked")) {
            var isAllChecked = 0;

            $(".checkSingle").each(function() {
                if (!this.checked)
                    isAllChecked = 1;
            });

            if (isAllChecked == 0) {
                $("#checkedAll").prop("checked", true);
            }
        }
        else {
            $("#checkedAll").prop("checked", false);
        }
    });

    $('#verifyInwardForm').submit(function(){
      link = $(this).attr('action');
      $.ajax({
        url: link,
        success: function(){
          $('#verifyInward').modal('hide');
          id = $('#verifyId').val();
          $('#verifyBtn'+id).hide();
          $('#verifyStat'+id).html('Verified');
        }
      });
      return false;
    });
  });

  function updatePaymentStatus(id,obj){
    stat = $(obj).val();
    $.ajax({
        url: "<?php echo url('/purchaseBill/updatePaymentStatus')  ?>" + '/' +id + '/' +stat,
        success: function(r){
          $('#payment_status_'+id).html(r);
          if(stat == 1){
            $(obj).parent().parent().css('background','#fff');
          }
          if(stat == 2){
            $(obj).parent().parent().css('background','#bbb');
          }
          if(stat == 3){
            $(obj).parent().parent().css('background','#F4C7C3');
          }
          if(stat == 4){
            $(obj).parent().parent().css('background','#FCE8B2');
          }
          if(stat == 5){
            $(obj).parent().parent().css('background','rgb(183, 225, 205)');
          }
        }
    });
  }

  // function deletepb(id){
  //   location.href = "{{ url('/purchaseBill/delete') }}" + '/' +id;
  // }

  function updatePurchaseBill(id)
    {
      location.href = "{{ url('/purchaseBill/view') }}" + '/' +id;
    }

  function selectPo(chObj){
    if(chObj.is(":checked")){
      pb_id = chObj.attr("id").split("_");
      pb_ids = $('#pb_ids').val();
      pb_ids = pb_ids+pb_id[1]+",";
      $('#pb_ids').val(pb_ids);
    }else{
      pb_id = chObj.attr("id").split("_");
      pb_ids = $('#pb_ids').val().replace(pb_id[1]+",","");
      $('#pb_ids').val(pb_ids);
    }
  }

  function poForm(){
    //$('#invoice_ids').val("");
     if($('#pb_ids').val() == ""){
      alert("Please select atleast 1 Inward Supply");
     }else{
      $("#poForm").submit();
     }
  }

  function verifyModal(id){
    $('#verifyInward').modal('show');
    $('#verifyInwardForm').attr('action', "{{ url('/purchaseBill/verify') }}" + '/' +id);
    $('#verifyId').val(id);
  }

function tallyStatus(id) {
    $('#statusTally').modal('show');
    $('#statusTallyFORM').attr('action', "{{ url('/purchaseBill-multi/updateTallyStatus') }}" + '/' + id);
    }
</script>
<!-- Script end -->

@endsection
