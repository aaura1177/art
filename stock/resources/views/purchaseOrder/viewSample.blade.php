@extends('layouts.app')

@section('content')

  <div class="mx-2">
    <div class="row mx-0 my-2 align-items-center">
        <h2 class="mb-0">Update Sample PO</h2>
        <div class="ms-3">
            @include('purchaseOrder.partials.send_to_supplier_actions', [
                'purchaseOrder' => $purchaseOrder,
                'sendToSupplierUrl' => url('/purchaseOrder/sample/' . $purchaseOrder->id . '/send-to-supplier'),
            ])
        </div>
    </div>

    <form id="myForm" method="POST" action="{{ url('/purchaseOrder/viewSample/'.$purchaseOrder->id)}}">
    @csrf

      <!-- Form Starts -->
      <div class="form-group">
              
            <!-- first row -->
            <div class="row mt-3">
              <div class="col-4">
                <label class="control-label">{{ __('PO No.') }}</label>
                <input type="text" class="form-control" name="pono" required="required" value="{{$purchaseOrder->pono}}" />
              </div>
              <div class="col-4">
                  <label class="control-label">{{ __('Supplier') }}</label><a href="{{ url('/supplier/create')}}" style="float: right;" target="_blank"> (+New)</a>
                  <select type="text" class="selectpicker" data-live-search="true" name="supplier_id" required="required" id='supplier_id'>
                    <option value="" selected disabled>Select Supplier</option>
                    @if(isset($supplier)) @foreach($supplier as $key => $supplier)
                      <option value="{{$supplier->id}}" {{($purchaseOrder->supplier_id == $supplier->id)?'selected':''}}>
                      {{$supplier->c_name}}
                      </option>
                    @endforeach @endif
                  </select>
              </div>
            </div>

            <div class="row mt-3">
              <div class="col-4">
                  <label class="control-label">{{ __('Date of PO') }}</label>
                  <input type="date" class="form-control" name="podate" value="{{$purchaseOrder->podate}}" required="required" />
              </div>
              <div class="col-4">
                  <label class="control-label">{{ __('Delivery Date') }}</label>
                  <input type="date" class="form-control" name="del_date" value="{{$purchaseOrder->del_date}}" required="required" />
              </div>
            </div>  

            <!-- second row -->
            <div class="row mt-3">                     
              <div class="col-4">
                  <label class="control-label">{{ __('Supplier Ref. No.') }}</label>
                  <input type="text" class="form-control toUpperCase" name="ref_supplier" value="{{$purchaseOrder->ref_supplier}}" required="required" />
              </div>
              <div class="col-4">
                  <label class="control-label">{{ __('Buyer Order Number') }}</label>
                  <input type="text" class="form-control toUpperCase" name="buyer_orderno" value="{{$purchaseOrder->buyer_orderno}}" required="required" />
              </div>
            </div>

            <!-- third row -->
            <div class="row mt-3">                                       
              <div class="col-4">
                  <label class="control-label">{{ __('Terms of Payment') }}</label>
                  <textarea class="form-control" name="payterms">{{$purchaseOrder->payterms}}</textarea>
              </div>
              <div class="col-8">
                <label class="control-label">{{ __('Remarks') }}</label>
                <textarea class="form-control" name="remarks">{{$purchaseOrder->remarks}}</textarea>
              </div>
            </div>

            <!-- forth Product row -->
            <div class="row mt-5 my-3">                                       
              <div class="col-6">
                <h5>Samples List</h5>
              </div>
            </div>

            <!-- Product details -->
            <div class="table-responsive">
              <table class="table table-hover">
                <thead>
                  <tr id="mytable">
                    <th scope="col" style="min-width: 250px;">Sample <a href="{{ url('/samples/create')}}" target="_blank"> (+New)</a></th>
                    <th scope="col" style="min-width: 100px;">Qty</th>
                    <th scope="col" style="min-width: 100px;">Unit</th>
                    <th scope="col" style="min-width: 100px;">Rate/Item (₹)</th>
                    <th scope="col" style="min-width: 150px;">Amount (₹)</th>
                    <th scope="col" style="min-width: 100px;">GST Slab (%)</th>
                    <th scope="col" style="min-width: 130px;">GST (₹)</th>
                    <th scope="col" style="min-width: 130px;">Priority</th>
                    <th scope="col" style="min-width: 130px;">Delivery Point</th>
                    <th scope="col" style="min-width: 130px;">Legs</th>
                    <th></th>
                  </tr>
                </thead>
                <tbody id="productTable">
                    @if(isset($posTable)) @foreach($posTable as $key => $posTable)
                    <tr>
                      <td id="pr{{$posTable->sample->id}}">
                        <select type="text" class="selectpicker" data-live-search="true" data-len="{{$key+1}}" name="po[{{$key+1}}][product]" required="required">
                          <option value="{{$posTable->sample->id}}"> 
                            {{$posTable->sample->code}} - {{$posTable->sample->name}}
                          </option>
                        </select>
                      </td>
                      
                      <td id="quan{{$key+1}}">
                        <input type="number" min="1" style="width:60px;" class="form-control quantity" onchange="changePrice(this);" name="po[{{$key+1}}][quantity]" data-len="{{$key+1}}" value="{{$posTable->quantity}}" />
                        <input type="hidden" data-len="{{$key+1}}" name="po[{{$key+1}}][consumed]" value="{{$posTable->quantity-$posTable->remqty}}" />
                      </td>
                      <td id="unit{{$key+1}}">
                        <select type="text" class="selectpicker" data-live-search="true" name="po[{{$key+1}}][unit]" required="required">
                          <option value="" disabled>Select</option>
                          <option value="No." {{($posTable->unit == "No.")?'selected':''}}>No.</option>
                          <option value="Kg." {{($posTable->unit == "Kg.")?'selected':''}}>Kg.</option>
                          <option value="Lt." {{($posTable->unit == "Lt.")?'selected':''}}>Lt.</option>
                          <option value="M3" {{($posTable->unit == "M3")?'selected':''}}>M3</option>
                        </select>
                      </td>
                      <td id="rate{{$key+1}}">
                        <input type="number" class="form-control rate" min="0" step="any" onchange="changePrice(this);" name="po[{{$key+1}}][rate]" data-len="{{$key+1}}" value="{{$posTable->rate}}" />
                      </td>
                      <td id="amount{{$key+1}}">
                        <input type="number" class="form-control amount" name="po[{{$key+1}}][amount]" data-len="{{$key+1}}" value="{{$posTable->amount}}" readonly/>
                      </td>
                      <td id="gstslab{{$key+1}}">
                        <input type="number" class="form-control gstslab" min="0" onchange="changePrice(this);" name="po[{{$key+1}}][gstslab]" data-len="{{$key+1}}" value="{{$posTable->gstslab}}" />
                      </td>
                      <td id="gstamount{{$key+1}}">
                        <input type="number" class="form-control gstamount" name="po[{{$key+1}}][gstamount]" data-len="{{$key+1}}" value="{{$posTable->gstamount}}" readonly />
                      </td>
					            <td id="priority{{$key+1}}">
                        <select class="form-control" name="po[{{$key+1}}][priority]">
                          <option value="1" {{($posTable->priority == 1)?'selected':''}}>1</option>
                          <option value="2" {{($posTable->priority == 2)?'selected':''}}>2</option>
                          <option value="3" {{($posTable->priority == 3)?'selected':''}}>3</option>
                          <option value="" {{($posTable->priority == "")?'selected':''}}>None</option>
						            </select>
                      </td>
					            <td id="delpoint{{$key+1}}">
                        <select class="form-control" name="po[{{$key+1}}][delivery_point]">
                          <option value="Unit 1" {{($posTable->delivery_point == 'Unit 1')?'selected':''}}>Unit 1</option>
                          <option value="Unit 2" {{($posTable->delivery_point == 'Unit 2')?'selected':''}}>Unit 2</option>
                          <option value="Unit 3" {{($posTable->delivery_point == 'Unit 3')?'selected':''}}>Unit 3</option>
                          <option value="" {{($posTable->delivery_point == "")?'selected':''}}>None</option>
						            </select>
                      </td>
					            <td id="legs{{$key+1}}">
                        <select class="form-control" name="po[{{$key+1}}][legs]">
                          <option value="1" {{($posTable->legs == 1)?'selected':''}}>With Legs</option>
                          <option value="2" {{($posTable->legs == 2)?'selected':''}}>Without Legs</option>
						            </select>
                      </td>
                      <td>
                          <button type="button" class="close" onclick="deleteRow(this);" data-bs-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                      </td>
                    </tr>
                  @endforeach @endif
                </tbody>
              </table>
            </div>
            
            <div class="row mt-2">
                <div class="col">
                    <input type="button" id="addSample" class="btn btn-primary" value="Add Sample" />
                </div>
            </div>    
          

            <!-- fifth row -->
            <div class="row mt-3">                     
                <div class="col-4">
                    <label class="control-label">{{ __('Total GST (₹)') }}</label>
                    <input type="text" class="form-control" name="tgst" id="totalgst" value="{{$purchaseOrder->tgst}}" readonly />
                </div>
                <div class="col-4">
                    <label class="control-label">{{ __('Total Quantity') }}</label>
                    <input type="number" class="form-control" name="tquantity" id="tquantity" value="{{$purchaseOrder->tquantity}}" readonly />
                </div>
                <div class="col-4">
                      <label class="control-label">{{ __('Sub Total Amount (₹)') }}</label>
                      <input type="number" class="form-control" name="subtotalamount" id="subtotalamount" value="{{$purchaseOrder->subTotal}}" readonly />
                </div>
            </div>


            <!-- sixth row  -->
            <div class="row mt-3">                     
                <div class="col-4">
                        <label class="control-label">{{ __('Total Amount (₹)') }}</label>
                        <input type="number" class="form-control" name="tamount" id="totalamount" value="{{$purchaseOrder->tamount}}"readonly />
                </div>
            </div>

            <div class="row col-4">
                <button id="submitBtn" type="submit" onclick="validateSubmit();" class="btn btn-primary mt-3">Update PO</button>
            </div>
      </div>
    </form>
  </div>
@endsection

@section('footer')

<!-- Script start for get data from database -->
<script type="text/javascript">

  $(document).ready(function(){
    
    $.ajax({
        'url': "{{ url('/purchaseOrder/sampleData') }}",
        'method': 'GET'
    }).done(function(data) {
        if (data) {
            samples = data.sample;
        }
      });
  });

  var trcount = $('#productTable tr').length;
  console.log(trcount);
  var sampleRows = trcount;
  var samples = [];

  function changeHSN(ref) {
    var len = $(ref).data('len');
    var id = $(ref).val();

    if($('#pr'+id).length){
      alert('sample already added');
      $(ref).prop('selectedIndex',0);
      $(ref).parent().attr("id",'pr');
    }

    else{
      $(ref).parent().attr("id",'pr'+id);
      function findProduct(product) {
        return product.id == id;
      }

      var product = samples.find(findProduct);
      $("#hsn"+len+" input").val(product.HSN);
      $("#ean"+len+" input").val(product.EAN);
      $("#gstslab"+len+" input").val(product.gstslab);
      supplier_id = $('#supplier_id').val();
     /* $.ajax({
        'url': "{{ url('/purchaseOrder/spdata') }}",
        'method': 'GET',
        'data': {'supplier_id':supplier_id,'product_id':id}
      }).done(function(data) {
        if (data) {
            supplierProduct = data.sp;
            $('#rate'+len+" input").val(data.sp.rate);
            changePrice('#rate'+len+" input");
        }
      });*/
    }
  }

  function validateSubmit(){
    if($('#productTable tr').length<1) {
      alert( "No sample added. Add atleast 1 sample." );
      event.preventDefault();
    }

    else{
      var productTableRow = 0;
      $('#productTable select[class="selectpicker"]').each(function(){
        productTableRow++
        console.log($(this).val());
        if(!$(this).val()){
          alert( "Sample Row "+productTableRow+ " empty. Select a sample or delete the row." );
          event.preventDefault();
          return false;
        }
      });
    }  
  };

  function deleteRow(ref) {
    $(ref).parents("tr").remove();
    changePrice();
  }

  function changePrice(ref) {
    var len = $(ref).data('len');
    var quantity = $("#quan"+len+" input").val();
    var rate = $("#rate"+len+" input").val();
    var amount = rate*quantity;
    var gstslab = $("#gstslab"+len+" input").val();
    var gst = (amount*gstslab)/100;
      
      $("#amount"+len+" input").val(amount.toFixed(2));
      $("#gstamount"+len+" input").val(gst);

    var arrq = document.getElementsByClassName('quantity');
    var arrgsta = document.getElementsByClassName('gst');
    var arrgtotamt = document.getElementsByClassName('amount');
    var arrgstamt = document.getElementsByClassName('gstamount');
    
    var totq = 0;
    // var totgsta = 0;
    var totamt = 0;
    var subamount = 0;
    var gstamount = 0;
    

      for(var i=0;i<arrq.length;i++){
          if(parseFloat(arrq[i].value))
              totq += parseInt(arrq[i].value);
      }
          document.getElementById('tquantity').value = totq;

      for(var i=0;i<arrgstamt.length;i++){
          if(parseFloat(arrgstamt[i].value))
              gstamount += parseFloat(arrgstamt[i].value);
      }

        document.getElementById('totalgst').value = gstamount.toFixed(2);

          

      for(var i=0;i<arrgtotamt.length;i++){
          if(parseFloat(arrgtotamt[i].value))
              subamount += parseFloat(arrgtotamt[i].value);
              // totgsta = (subamount*18)/100;
              totamt = subamount + gstamount;
      }
          document.getElementById('subtotalamount').value = subamount.toFixed(2);
          // document.getElementById('totalgst').value = totgsta.toFixed(2);
          document.getElementById('totalamount').value = totamt.toFixed(2);
  }

  $("#addSample").click(function() {    

      var options = '<option value="" selected disabled>-- SELECT SAMPLE --</option>';
    
      sampleRows += 1;
  
      $.each(samples, function(index, value) {
          options += '<option value="' + value.id + '">' + value.code + " - " + value.name + '</option>';
      });

      var $block = "";
          $block += '<tr>';
          $block += '<td id="pr">';
          $block += '<select type="text" class="selectpicker" data-live-search="true" class="form-control" onchange="changeHSN(this);" data-len="' + sampleRows + '" name="po['+sampleRows+'][product]">';
          $block += options + '</td>';
         
          $block += '<td id="quan'+sampleRows+'"><input type="number" min="1" class="form-control quantity" onchange="changePrice(this);" data-len="' + sampleRows + '" name="po['+sampleRows+'][quantity]" value="1" /></td><input type="hidden" data-len="' + sampleRows + '" name="po['+sampleRows+'][consumed]" value="" /></td>'
          $block += '<td><select type="text" class="selectpicker" data-live-search="true" onchange="changePrice(this);" data-len="' + sampleRows + '" name="po['+sampleRows+'][unit]">';
          $block += '<option value="No.">No.</option><option value="Kg.">Kg.</option><option value="Lt.">Lt.</option><option value="M3.">M3.</option></select></td>';
          $block += '<td id="rate'+sampleRows+'"><input type="number" step="any" min="0" class="form-control" onchange="changePrice(this);" data-len="' + sampleRows + '" name="po['+sampleRows+'][rate]" value="0.00" required /></td>'
          $block += '<td id="amount'+sampleRows+'"><input type="number" class="form-control amount" name="po['+sampleRows+'][amount]" value="0.00" readonly/></td>'
          $block += '<td id="gstslab'+sampleRows+'"><input type="number" class="form-control" min="0" onchange="changePrice(this);" data-len="' + sampleRows + '" name="po['+sampleRows+'][gstslab]" value="0.00" required /></td>'
          $block += '<td id="gstamount'+sampleRows+'"><input type="number" class="form-control gstamount" name="po['+sampleRows+'][gstamount]" value="0.00" readonly/></td>'
		      $block += '<td id="priority'+sampleRows+'"><select class="form-control" name="po['+sampleRows+'][priority]"><option value="1">1</option><option value="2">2</option><option value="3">3</option><option value="">None</option></select></td>'
          $block += '<td id="delpoint'+sampleRows+'"><select class="form-control" name="po['+sampleRows+'][delivery_point]"><option value="Unit 1">Unit 1</option><option value="Unit 2">Unit 2</option><option value="Unit 3">Unit 3</option><option value="">None</option></select></td>'
          $block += '<td id="legs'+sampleRows+'"><select class="form-control" name="po['+sampleRows+'][legs]"><option value="1">With Legs</option><option value="2">Without Legs</option></select></td>'
          $block += '<td><button type="button" class="close" onclick="deleteRow(this);" data-bs-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button></td>';
          $block += '</tr>';
      $("#productTable").append($block);
      $('.selectpicker').selectpicker();
  });

</script>

@endsection