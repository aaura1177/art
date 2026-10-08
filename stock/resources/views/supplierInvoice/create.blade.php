@extends('layouts.app')

@section('content')

      <!-- first row -->

    <form id="myForm" method="POST" action="{{ url('/supplierInvoice/saveInvoiceReturn/'.$supplierInvoice->id)}}">
    @csrf
      <div class="row mx-3 my-2">
        <h2>Supplier Invoice Products</h2>    
        <div class="col">
        
        <!-- <a class="btn btn-success float-end" style="color: #fff; margin-left: 5px;" onclick="poForm()"  href="#">Save</a> -->
        <button id="submitBtn" type="submit" onclick="validateSubmit();" class="btn btn-success float-end">Save</button>
        </div>    
      </div>

      <div class="card mb-3">
        <div class="card-body">
          <div class="table-responsive">
            <table class="table table-bordered" id="dataTables1" width="100%" cellspacing="0">
              <thead>
                <tr>
                  <th>S. No.</th>
                  <th>Product Name</th>
                  <th>Quantity</th>
                  <th>Add Quantity to Return</th>
                  <th>Options</th>
                </tr>
              </thead>
              <tbody>
                @if(isset($supplierInvoiceProduct)) @foreach($supplierInvoiceProduct as $key => $supplierInvoiceProduct)

                
                <tr>
                  <td>{{$key+1}}</td>
                  <td>{{$supplierInvoiceProduct->product->code}} - {{$supplierInvoiceProduct->product->name}}</td>
                  <td>{{$supplierInvoiceProduct->quantity}} {{$supplierInvoiceProduct->unit}}</td>
                  <td>
                    <input type="hidden" data-len="{{$key+1}}" name="po[{{$key+1}}][product_id]" value="{{$supplierInvoiceProduct->product->id}}" />
                    <input type="number"  max="{{$supplierInvoiceProduct->quantity}}" style="width:60px;" class="form-control quantity" onchange="changePrice(this);" name="po[{{$key+1}}][quantity]" data-len="{{$key+1}}" value="" /></td>
                  <td style="padding:0px;"><input class="checkSingle" type="checkbox" id="purchaseOrder_{{$supplierInvoiceProduct->id}}" onclick="selectPo($(this))" style="width:50px;height:50px"></td>
                </tr>
                @endforeach @endif
              </tbody>
            </table>
          </div>
        </div>
      </div>

      

 <!--  <form id="poForm" method="POST" action="{{ url('/supplierInvoice/saveInvoiceReturn') }}" style="visibility:hidden;">
    @csrf
    <input type="hidden" id="sir_ids" name="sir_ids" />
    <input type="hidden" id="si_id" name="si_id"  />
    <button type="submit" onclick="return false" class="btn btn-success">Download</button>
  </form> -->
@endsection

@section('footer')


<!-- Scripts Start For Category Delete/View -->
<script type="text/javascript">

  $(function () {
  $('[data-bs-toggle="tooltip"]').tooltip()
  });

  function selectPo(chObj){
    if(chObj.is(":checked")){
      po_id = chObj.attr("id").split("_");
      sir_ids = $('#sir_ids').val();
      sir_ids = sir_ids+po_id[1]+",";
      $('#sir_ids').val(sir_ids);
    }else{
      po_id = chObj.attr("id").split("_");
      sir_ids = $('#sir_ids').val().replace(po_id[1]+",","");
      $('#sir_ids').val(sir_ids);
    }
  }

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

  function deleteModal(id){
    $('#deleteCategory').modal('show');
    $('#deleteCategoryForm').attr('action', "{{ url('/category/delete') }}" + '/' +id);
  }

  function updateCategory(id){

     location.href = "{{ url('/category/view') }}" + '/' +id;
  }

  function poForm(){
    //$('#invoice_ids').val("");
     if($('#sir_ids').val() == ""){
      alert("Please select atleast 1 Product");
     }else{
      $("#poForm").submit();
     }
  }

</script>
<!-- Scripts End -->

@endsection
