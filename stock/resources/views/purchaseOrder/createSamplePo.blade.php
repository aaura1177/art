@extends('layouts.app')

@section('content')

  <div class="mx-2">
    <div class="row mx-0 my-2">
        <h2>Add Sample PO</h2>
    </div>

    <form id="myForm" method="POST" action="{{ url('/purchaseOrder/createSamplePo') }}">
    @csrf

      <!-- Form Starts -->
      <div class="form-group">
        @if(session('error'))
        <div class="alert alert-danger">
            {{ session('error') }}
        </div>
    @endif  
            <!-- first row -->
            <div class="row mt-3">
              <div class="col-4">
                <label class="control-label">{{ __('PO No.') }}</label>
                <input type="text" class="form-control toUpperCase" name="pono" required="required" value="S/{{$companyDetails->spo_no + 1}}" />
              </div>  
              <div class="col-4">
                  <label class="control-label">{{ __('Supplier') }}</label><a href="{{ url('/supplier/create')}}" style="float: right;" target="_blank"> (+New)</a>
                  <select type="text" class="selectpicker" data-live-search="true" onchange="handleSelectSupplier(this)" name="supplier_id" required="required" id='supplier_id'>
                    <option value="" selected disabled>Select Supplier</option>
                    @if(isset($supplier)) @foreach($supplier as $key => $supplier)
                      <option value="{{$supplier->id}}" id="{{$supplier->id}}" data-gst="{{$supplier->gst}}">
                      {{$supplier->c_name}}
                      </option>
                    @endforeach @endif
                  </select>
                  <input type="number" value="" id="supplier_gst" hidden>

              </div>
            </div>
            <div class="row mt-3">
              <div class="col-4">
                  <label class="control-label">{{ __('Date of PO') }}</label>
                  <input type="date" class="form-control" name="podate" required="required" value = "{{date('Y-m-d')}}" />
              </div>
              <div class="col-4">
                  <label class="control-label">{{ __('Delivery Date') }}</label>
                  <input type="date" class="form-control" name="del_date" required="required" />
              </div>
            </div>

            <!-- second row -->
            <div class="row mt-3">                     
              <div class="col-4">
                  <label class="control-label">{{ __('Supplier Ref. No.') }}</label>
                  <input type="text" class="form-control toUpperCase" name="ref_supplier" required="required" />
              </div>
              <div class="col-4">
                  <label class="control-label">{{ __('Buyer Order Number') }}</label>
                  <input type="text" class="form-control toUpperCase" name="buyer_orderno" required="required" />
              </div>
            </div>

            <!-- third row -->
            <div class="row mt-3">
              <div class="col-4">
                  <label class="control-label">{{ __('Terms of Payment') }}</label>
                  <select class="form-control" name="payterms">
        <option value="30-45 Days" selected>30-45 Days</option>
        <option value="30 Days">30 Days</option>
        <option value="45 Days">45 Days</option>
    </select>
              </div>
              <div class="col-8">
                <label class="control-label">{{ __('Remarks') }}</label>
                <textarea class="form-control" name="remarks">
1. Wood must be seasoned & chemically treated.
2. Timber MUST be sourced from regulated & legal plantations only.
                </textarea>
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
                    <!-- <th scope="col" style="min-width: 250px;">EAN</th> -->
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
                <tbody id="sampleTable">

                </tbody>
              </table>
            </div>
            
            <div class="row mt-2">
                <div class="col">
                    <input type="button" id="addProduct" class="btn btn-primary" value="Add Sample" />
                </div>
            </div>    

            <!-- fifth row -->
            <div class="row mt-3">                     
                <div class="col-4">
                    <label class="control-label">{{ __('Total GST (₹)') }}</label>
                    <input type="text" class="form-control" name="tgst" id="totalgst" readonly />
                </div>
                <div class="col-4">
                    <label class="control-label">{{ __('Total Quantity') }}</label>
                    <input type="number" class="form-control" name="tquantity" id="tquantity" readonly />
                </div>
                <div class="col-4">
                    <label class="control-label">{{ __('Sub Total Amount (₹)') }}</label>
                    <input type="number" class="form-control" name="subtotalamount" id="subtotalamount" readonly />
                </div>
            </div>


            <!-- sixth row  -->
            <div class="row mt-3">                     
                <div class="col-4">
                        <label class="control-label">{{ __('Total Amount (₹)') }}</label>
                        <input type="number" class="form-control" name="tamount" id="totalamount" readonly />
                </div>
            </div>

            <div class="row col-4">
                <button id="submitBtn" type="submit" form='myForm' class="btn btn-primary mt-3">Create PO</button>
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
        'url': "{{ url('/purchaseOrder/sampleData') }}"
    }).done(function(data) {
        if (data) {
            samples = data.sample;
        }
      });
  });

  var sampleRows = 0;
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
        }
      });*/
    }
  }

  $('#myForm').on('submit', function(){
    if($('#sampleTable tr').length<1) {
      alert( "No sample added. Add atleast 1 sample." );
      event.preventDefault();
    } else{
        var sampleTableRow = 0;
        var submitFlag = 0;
        $('#sampleTable select[class="selectpicker"]').each(function(){
            sampleTableRow++;
            if(!$(this).val()){
              alert( "Sample Row "+sampleTableRow+ " empty. Select a sample or delete the row." );
              event.preventDefault();
              submitFlag++;
              return false;
            }
        });
        
        if(submitFlag == 0){
            $('#submitBtn').prop('disabled', 'true');
        }
    }
  });

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
      $("#gstamount"+len+" input").val(gst.toFixed(2));

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

  $("#addProduct").click(function() {    

      var options = '<option value="" selected disabled>-- SELECT SAMPLE --</option>';
    
      sampleRows += 1;
  
      $.each(samples, function(index, value) {
          options += '<option value="' + value.id + '">' + value.code + " - " + value.name + '</option>';
      });

      var $block = "";
          $block += '<tr>';
          $block += '<td id="pr">';
          $block += '<select type="text" class="selectpicker" data-live-search="true" onchange="changeHSN(this);" data-len="' + sampleRows + '" name="po['+sampleRows+'][product]">';
          $block += options + '</select></td>';
          /*$block += '<td id="ean'+sampleRows+'">'
          $block += '<input type="text" class="form-control" name="po['+sampleRows+'][EAN]" readonly /></td>'*/
          $block += '<td id="quan'+sampleRows+'"><input type="number" min="1" class="form-control quantity" onchange="changePrice(this);" data-len="' + sampleRows + '" name="po['+sampleRows+'][quantity]" value="1" /></td>'
          $block += '<td><select type="text" class="selectpicker" data-live-search="true" onchange="changePrice(this);" data-len="' + sampleRows + '" name="po['+sampleRows+'][unit]">';
          $block += '<option value="No.">No.</option><option value="Kg.">Kg.</option><option value="Lt.">Lt.</option><option value="M3.">M3.</option></select></td>';
          $block += '<td id="rate'+sampleRows+'"><input type="number" step="any" min="1" class="form-control" onchange="changePrice(this);" data-len="' + sampleRows + '" name="po['+sampleRows+'][rate]" value="0.00" required /></td>'
          $block += '<td id="amount'+sampleRows+'"><input type="number" class="form-control amount" name="po['+sampleRows+'][amount]" value="0.00" readonly/></td>'
          $block += '<td id="gstslab'+sampleRows+'"><input type="number" min="0" class="form-control" onchange="changePrice(this);" data-len="' + sampleRows + '" name="po['+sampleRows+'][gstslab]"  /></td>'
          $block += '<td id="gstamount'+sampleRows+'"><input type="number" class="form-control gstamount" name="po['+sampleRows+'][gstamount]" value="0.00" readonly/></td>'
          $block += '<td id="priority'+sampleRows+'"><select class="form-control" name="po['+sampleRows+'][priority]"><option value="1">1</option><option value="2">2</option><option value="3">3</option><option value="">None</option></select></td>'
          $block += '<td id="delpoint'+sampleRows+'"><select class="form-control" name="po['+sampleRows+'][delivery_point]"><option value="Unit 1">Unit 1</option><option value="Unit 2">Unit 2</option><option value="Unit 3">Unit 3</option><option value="">None</option></select></td>'
          $block += '<td id="legs'+sampleRows+'"><select class="form-control" name="po['+sampleRows+'][legs]"><option value="1">With Legs</option><option value="2">Without Legs</option></select></td>'
          $block += '<td><button type="button" class="close" onclick="deleteRow(this);" data-bs-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button></td>';
          $block += '</tr>';
      $("#sampleTable").append($block);
      $('.selectpicker').selectpicker();
      handleGstSlab()
  });


  function handleGstSlab() {
  var gst = parseFloat($('#supplier_gst').val());  // Ensure it's a number
  
  console.log(gst);
  let trLength = document.getElementsByTagName('tr').length - 1;  // Subtract 1 to exclude any non-relevant rows (like header rows)
  
  for (let i = 1; i <= trLength; i++) {
    let gstTr = document.getElementById('gstslab' + i);  // Get the specific row for GST slab
    
    console.log(gstTr);
    
    if (gstTr) {
      let gstInput = gstTr.getElementsByTagName('input')[0];  // Get the input field inside the row
      if (gst !== 0 && !isNaN(gst)) {
        gstInput.setAttribute('required', 'required');  // Add the required attribute
      } else {
        gstInput.removeAttribute('required');  // Remove the required attribute
      }

      // Set the minimum value of the input field based on supplier GST
      if (gst === 1) {
        gstInput.setAttribute('min', '1');  // Set min to 1 if GST is 1
      } else {
        gstInput.removeAttribute('min');  // Remove min attribute if GST is not 1
      }
    }
  }
}

function handleSelectSupplier(ref) {
  var id = $(ref).val();
  var gst = $(ref).find('option:selected').data('gst');
  $('#supplier_gst').val(gst);
  handleGstSlab();  // Call the function to adjust the min attribute when supplier is selected
}


</script>

@endsection