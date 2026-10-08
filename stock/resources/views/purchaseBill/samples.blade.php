@extends('layouts.app')

@section('content')

    <div class="row mx-3 my-2">
      <h2>Sample Inward Supply</h2>
      <div class="col">
        <a class="btn btn-success float-end" style="color: #fff; margin-left: 5px;" data-bs-toggle="modal" data-bs-target="#mydateModal"  href="{{ url('/purchaseBill/exportcsvsample')}}">Download CSV</a>
         <!--<a class="btn btn-success float-end" style="color: #fff; margin-left: 5px;" onclick="poForm()"  href="#">Export for Tally</a>-->
        <a class="btn btn-primary float-end" style="color: #fff;" href="{{ url('/purchaseBill/createSample')}}">Add Inward Supply</a></div>
    </div>
    
    <div class="card mb-3">
      <div class="card-body">
        <div class="table-responsive">
          <table class="table table-bordered" id="dataTable" width="100%" cellspacing="0">
            <thead>
              <tr>
                <th>PO No.</th>
                <th data-orderable="false" style="padding:0px;"><input type="checkbox" id="checkedAll" onclick="selectAllPb()" style="width:50px;height:50px"></th>
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
            
              @if(isset($purchaseBill))  @foreach($purchaseBill as $key => $purchaseBill)
              @if(isset($purchaseBill->samplePurchaseOrder->pono))
              <?php //echo "<pre>"; print_r($purchaseBill); ?>
              <tr>
                <td>{{(isset($purchaseBill->samplePurchaseOrder->pono))?$purchaseBill->samplePurchaseOrder->pono:'--'}}</td>
                <td style="padding:0px;"><input class="checkSingle" type="checkbox" id="purchaseOrder_{{$purchaseBill->id}}" onclick="selectPo($(this))" style="width:50px;height:50px"></td>
                <td>{{$purchaseBill->samplePurchaseOrder->ref_supplier}}</td>
                <td>{{$purchaseBill->supp_inv_no}}</td>
                <td>{{$purchaseBill->samplePurchaseOrder->supplier->c_name}}</td>
                <td>{{date('d-M-Y',strtotime($purchaseBill->supp_inv_date))}}</td>
                <td>{{$purchaseBill->quantity}}</td>
                <td>{{$purchaseBill->total}}</td>
                <td id="verifyStat{{$purchaseBill->id}}">@if($purchaseBill->is_checked == 0){{'Pending'}}@endif
                    @if($purchaseBill->is_checked == 1){{'Verified'}}@endif
                </td>
                <td>
                    <span class="tool-tip1" data-bs-toggle="tooltip" data-bs-placement="top" title="View">
                       <a class="btn btn-primary" style="color: #fff;" href="{{ URL::to('/purchaseBill/modalSample/'.$purchaseBill->id)}}" target="_blank"><i class="fa fa-eye"></i></a>
                    </span>
                    <span class="tool-tip1" data-bs-toggle="tooltip" data-bs-placement="top" title="Print">
                       <a class="btn btn-success" style="color: #fff;" href="{{ URL::to('/purchaseBill/modalSample/'.$purchaseBill->id.'?print=1')}}" target="_blank"><i class="fa fa-print"></i></a>
                    </span>   
                    @if($purchaseBill->is_checked == 0)
                    <!--<span class="tool-tip1" data-bs-toggle="tooltip" data-bs-placement="top" title="Verify" id="verifyBtn{{$purchaseBill->id}}">
                        <button class="btn btn-warning" data-bs-toggle="modal" onclick="verifyModal('{{$purchaseBill->id}}')">
                          <i class="fas fa-check-circle"></i>
                        </button>
                    </span>-->
                    @endif
                </td>
                <td>
                
                  @foreach($purchaseBill->spbTable as $spbTable)
                  <?php // echo "<pre>"; print_r($spbTable); ?>
                    {{(isset($spbTable->sample->code))?$spbTable->sample->code .'('. $spbTable->receiveqty .'/'. $spbTable->orderqty .')':''}} <br>
                  @endforeach
                </td>
              </tr>
              @endif
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
              <form method="POST" action="{{ url('/purchaseBill/exportcsvsample') }}">
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
  

</script>
<!-- Script end -->

@endsection