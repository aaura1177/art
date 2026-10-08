@extends('layouts.app')

@section('content')

  <div class="mx-2">
    <div class="row mx-0 my-2">
        <h2>Add Packing Sheet</h2>
    </div>

    <form method="POST" action="{{ url('/packingSheet/create') }}">
      @csrf

      <!-- Form Starts -->
      <div class="form-group"> 
          
          <!-- first row -->
          <div class="row mt-3">  
            <div class="col-4">
              <label class="control-label">{{ __('Invoice No.') }}</label>
              <select type="text" class="form-control" onchange="changeDetails(this)" name="invoice_id" required="required">
                <option value="" selected disabled>Select Invoice</option>
                  @if(isset($invoice)) @foreach($invoice as $key => $invoice)
                    <option value="{{$invoice->id}}">
                    {{$invoice->id}}
                    </option>
                  @endforeach @endif
              </select>
            </div>

            <div class="col-4">
              <label class="control-label">{{ __('Buyer\'s Order No.') }}</label>
              <input type="text" class="form-control" name="buyerorderno" id="buyerorderno" readonly />
            </div>

            <div class="col-4">
              <label class="control-label">{{ __('Total Box') }}</label>
              <input type="number" class="form-control" name="totalbox" id="totalbox" readonly/>
            </div>
          </div>

          <!-- second row -->
          <div class="row mt-3">  
            <div class="col-4">
              <label class="control-label">{{ __('Container No.') }}</label>
              <input type="text" class="form-control" name="containerno" id="containerno" readonly/>
            </div>
            <div class="col-4">
              <label class="control-label">{{ __('Vehicle No.') }}</label>
              <input type="text" class="form-control" name="vehicleno" id="vehicleno" readonly/>
            </div>
          </div>  

          <div class="row mt-3">
	          <table class="table table-hover">
	              <thead>
	                <tr id="mytable">
	                  <th scope="col" style="min-width:150px;">Product</th>
	                  <th scope="col">QTY</th>
	                  <th scope="col">Box</th>
	                  <th scope="col">Qty/Box</th>
	                  <th scope="col">Net Wt (Kg)</th>
	                  <th scope="col">Gross Wt (Kg)</th>
	                </tr>
	              </thead>
	              <tbody id="productInvoice">  
	              </tbody>
	          </table>
	        </div>

          <!-- eighth row -->
          <div class="row mt-3">
                <div class="col-4">
                    <label class="control-label">{{ __('Total Quantity') }}</label>
                    <input type="number" class="form-control" name="quantity" id="quantity" readonly />
                </div>
                <div class="col-4">
                    <label class="control-label">{{ __('Total Net Weight (kg)') }}</label>
                    <input type="number" class="form-control" name="weight" id="weight" readonly />
                </div>
                <div class="col-4">
                    <label class="control-label">{{ __('Total Gross Weight (kg)') }}</label>
                    <input type="number" class="form-control" name="grosswt" id="grosswt" readonly />
                </div>
          </div>

          <!-- ninth row -->
          <div class="row mt-3">
            <div class="col-4">
                  <label class="control-label">{{ __('Total Boxes') }}</label>
                  <input type="text" class="form-control" name="totalbox" id="Box" readonly />
            </div>
          </div>   

          <div class="row col-4">
                <button type="submit" id="hidden" hidden class="btn btn-primary mt-3" disabled>Create Purchase Bill</button>
                <button type="submit" class="btn btn-primary mt-3">Create Packing Sheet</button>
          </div>
      
      </div>  
    </form>  
  </div>

@endsection

@section('footer')
<!-- Script start for Statecode & GSTIN button -->
<script>
  $(document).ready(function(){
	  $.ajax({
	    'url': "{{ url('/packingSheet/data') }}",
	    'method': 'GET'
	  }).done(function(data){
	      if (data) {
	        psInvoice = data.invoice;
	        psProduct = data.product;
	    	}
	    });
	});

 
  var psInvoice = [];
  var psProduct = [];
  var invoicePoTable = [];
  var psInvoiceTable = [];
  
  function changeDetails(fer){

    var id = $(fer).val();

    $.ajax({
      'url': "{{ url('/packingSheet/data/psInvoiceTable') }}" + '/' +id,
      'method': 'GET',

    }).done(function(data){
        if (data) {
          psInvoiceTable = data.invoiceTable;
        }
        console.log(psInvoiceTable);
        function findInvoice(invoice){
          return invoice.id == id;
        };

        var invoice = psInvoice.find(findInvoice);
        $("#buyerorderno").val(invoice.buyerorderno);
        $("#totalbox").val(invoice.totalbox);
        $("#containerno").val(invoice.containerno);
        $("#vehicleno").val(invoice.vehicleno);

        console.log(psInvoiceTable.length);

        $("#productInvoice").children().remove();

        for(var i=0; i<psInvoiceTable.length ; i++){
          var $block = "";
          $block += '<tr>';
          $block += '<td><select type="text" class="form-control" name="ps['+i+'][product]" readonly><option value="'+psInvoiceTable[i].product_id+'">' +psInvoiceTable[i].product_id+ '</option></td>';
          $block += '<td><input type="number" class="form-control quantity" value="' +psInvoiceTable[i].quantity+ '" readonly/></td>'
          $block += '<td><input type="number" step="any" class="form-control box" onchange="calculateTotal();" data-len="' + i + '" name="ps['+i+'][box]" value="1" min="1" /></td>'
          $block += '<td><input type="text" class="form-control qtybox" name="ps['+i+'][qtybox]" value="1 Pc/Box"/></td>'
          $block += '<td><input type="number" class="form-control weight" value="' +psInvoiceTable[i].weight+ '" readonly/></td>'
          $block += '<td><input type="number" class="form-control grosswt" value="' +psInvoiceTable[i].grosswt+ '" readonly/></td>'
          $block += '</tr>';
          $("#productInvoice").append($block);
          calculateTotal();
        }
        
      });
  };

  function validateSubmit(){
    event.preventDefault();
  }

  function calculateTotal(){
    var arrbox = [];
    var arrquan = [];
    var arrweight = [];
    var arrgrosswt = [];
    var totBox = 0;
    var totweight = 0;
    var totquan = 0;
    var totgrosswt = 0;
    arrquan = $('.quantity');
    arrbox = $('.box');
    arrweight = $('.weight');
    arrgrosswt = $('.grosswt');

    $.each(arrbox,function(index, value){
      totBox += parseInt(arrbox[index].value,10);
    });
      $('#Box').val(totBox);

    $.each(arrquan,function(index, value){
      totquan += parseInt(arrquan[index].value,10);
    });
      $('#quantity').val(totquan);

    $.each(arrweight,function(index, value){
      totweight += parseInt(arrweight[index].value,10);
    });
      $('#weight').val(totweight);

     $.each(arrgrosswt,function(index, value){
      totgrosswt += parseInt(arrgrosswt[index].value,10);
    });
      $('#grosswt').val(totgrosswt);    
  }
</script>  
<!-- Script End -->

@endsection
