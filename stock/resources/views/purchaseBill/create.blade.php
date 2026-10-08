@extends('layouts.app')

@section('content')

<div class="mx-2">
  <div class="row mx-0 my-2">
      <h2>Add Inward Supply</h2>
  </div>
   @if(session('error'))
        <div class="alert alert-danger">
            {{ session('error') }}
        </div>
    @endif
  <form id='createPurchaseBill' method="POST" action="{{ url('/purchaseBill/create') }}">
    @csrf

    <div class="form-group">

      <!-- first row -->
      <div class="row mt-3">
        <div class="col-4">
          <label class="control-label">{{ __('PO No.') }}</label>
          <a href="{{ url('/purchaseOrder/create')}}" style="float: right;" target="_blank"> (+New)</a>
          <select type="text" class="selectpicker" data-live-search="true" name="purchaseOrder_id" onchange="changeDetails(this);" required="required">
            <option value="" selected disabled>Select PO No.</option>
            @if(isset($purchaseOrder))
              @foreach($purchaseOrder as $key => $purchaseOrder)
                <option value="{{$purchaseOrder->id}}">
                {{$purchaseOrder->pono}}
                </option>
              @endforeach
            @endif
          </select>
        </div>
        <div class="col-4">
          <label class="control-label">{{ __('Supplier Name') }}</label>
          <input type="text" class="form-control" name="supplierName" id="supplierName" readonly />
        </div>
        <div class="col-4">
          <label class="control-label">{{ __('Delivery Date') }}</label>
          <input type="date" class="form-control" name="del_date"  id="del_date" readonly />
        </div>
      </div>

      <!-- second row -->
      <div class="row mt-3">
        <div class="col-4">
            <label class="control-label">{{ __('Supplier Ref.') }}</label>
            <input type="text" class="form-control" name="Supplier_ref" id="Supplier_ref" readonly />
        </div>
        <div class="col-4">
            <label class="control-label">{{ __('Ordered Quantity') }}</label>
            <input type="number" class="form-control" name="poQty" id="total_qty" readonly />
        </div>
        <div class="col-4">
            <label class="control-label">{{ __('Sub Total Amount') }}</label>
            <input type="number" class="form-control" name="poAmount" id="total_amount" readonly />
        </div>
      </div>

      <!-- remarks row -->
      <div class="row mt-3">
        <div class="col-8">
            <label class="control-label">{{ __('Remark') }}</label>
            <textarea class="form-control" name="remark" id="remarks" readonly></textarea>
        </div>
      <div class="col-4">
                        <label class="control-label">Get In Serial No.</label>
                        <input type="text" class="form-control" name="getinserailno" id="getinserailno" required/>
                    </div>

      </div>

      <!-- supplier invoice row -->
      <div class="row mt-3">
        <div class="col-4">
            <label class="control-label">{{ __('Supplier Invoice No.') }}</label>
            <input type="text" class="form-control toUpperCase" id="supinv" name="supp_inv_no" required />
        </div>
        <div class="col-4">
            <label class="control-label">{{ __('Supplier Invoice Date.') }}</label>
            <input type="date" class="form-control" name="supp_inv_date" required />
        </div>
        <div class="col-4">
            <label class="control-label">{{ __('E-Way Bill No.') }}</label>
            <input type="text" class="form-control toUpperCase" name="ewaybill" id="ewaybill"/>
<p style="color: red; font-weight: 500;">
    E-Way Bill is optional (if the Total amount is below ₹1,00,000 for a single day)
</p>
        </div>
      </div>

      <!-- product search -->
      <div class="row mt-3">
        <div class="col-8">
          <div class="input-group">
            <span class="input-group-addon" style="border: 1px solid #ccc; padding: 0.4rem;"><i class="fa fa-barcode"></i></span>
            <input id="myInput" type="text" class="form-control" name="enterproductname" onkeyup="searchProduct(this);" placeholder="Enter Product name / SKU / Scan bar code" disabled autocomplete="off"/>
          </div>
        </div>
      </div>

      <div class="row mt-3">
        <table class="table table-hover">
          <thead>
            <tr id="mytable">
              <th scope="col" style="min-width: 300px;">Product</th>
              <th scope="col">EAN</th>
              <th scope="col">Remaining QTY</th>
              <th scope="col">Receive QTY</th>
              <th scope="col">Rate/Item (₹)</th>
              <th scope="col">Amount (₹)</th>
              <th scope="col">GSTSLAB</th>
              <th scope="col">GST (₹)</th>
              <th scope="col"> Discount Per Item</th>
              <th scope="col"> Discount(₹)</th>
              <th scope="col">Po Discount(₹)</th>
              <th scope="col">Location</th>
              <th></th>
            </tr>
          </thead>
          <tbody id="productTable">

          </tbody>
        </table>
      </div>

      <!-- totals -->
      <div class="row mt-3">
        <div class="col-4">
            <label class="control-label">{{ __('Total Quantity') }}</label>
            <input type="number" class="form-control" name="pbQty" id="pbQty" readonly />
        </div>
        <div class="col-4">
            <label class="control-label">{{ __('Sub-Total Amount') }}</label>
            <input type="number" class="form-control" name="pbSubTotal" id="pbSubTotal" readonly />
        </div>
        <div class="col-4">
            <label class="control-label">{{ __('GST') }}</label>
            <input type="number" class="form-control" name="pbGST" id="pbGST" readonly />
        </div>
      </div>

      <div class="row mt-3">
        <div class="col-4">
            <label class="control-label">{{ __('Freight') }}</label>
            <input type="number" class="form-control" onchange="calculateTotal();" name="freight" id="freight" value='0' step="any" min='0' required/>
        </div>
        <div class="col-4">
            <label class="control-label">{{ __('Total Amount') }}</label>
            <input type="number" class="form-control" name="pbTotal" id="pbTotal" readonly />
        </div>

        <div class="col-4">
            <label class="control-label">{{ __('Po Discount') }}</label>
            <input type="number" class="form-control" name="totaldiscount" id="totaldiscount" readonly />
        </div>
      </div>

      <div class="row col-4">
        <button type="submit" id="hidden" hidden class="btn btn-primary mt-3" disabled>Create Purchase Bill</button>
        <button type="submit" id="formSubmit" form="createPurchaseBill" class="btn btn-primary mt-3" >Create Inward Supply</button>
      </div>

    </div>
  </form>
</div>
@endsection

@section('footer')
<script type="text/javascript">
$(document).ready(function() {
    $.ajax({
        url: "{{ url('/purchaseBill/data') }}",
        method: 'GET'
    }).done(function(data) {
        if (data) {
            purchaseOrder = data.purchaseOrder;
            supplier = data.supplier;
            pbProduct = data.product;
        }
    });
});

var purchaseOrder = [];
var supplier = [];
var pbProduct = [];
var pbPoTable = [];
var productRows = 0;

function changeDetails(ref) {
    var id = $(ref).val();
    $("#productTable tr").remove();

    $.ajax({
        url: "{{ url('/purchaseBill/data/pbPoTable') }}/" + id,
        method: 'GET'
    }).done(function(data) {
        if (data) {
            pbPoTable = data.poTable;
        }
    });

    var purchaseBill = purchaseOrder.find(pb => pb.id == id);
    var supplierName = supplier.find(s => s.id == purchaseBill.supplier_id);

    $("#supplierName").val(supplierName.c_name);
    $("#del_date").val(purchaseBill.del_date);
    $("#Supplier_ref").val(purchaseBill.ref_supplier);
    $("#remarks").val(purchaseBill.remarks);
    $("#total_qty").val(purchaseBill.tquantity);
    $("#total_amount").val(purchaseBill.tamount);
    $("#myInput").removeAttr('disabled').focus();
}

function reduceQuantity(ref) {
    let quantity = parseInt($(ref).siblings('input').val(), 10);
    let rqean = $(ref).parent().attr('id');

    if (quantity > 1) {
        let newQuantity = quantity - 1;
        $(ref).siblings('input').val(newQuantity);

        let remqty = parseFloat($('#remqty' + rqean + ' input').val()) || 0;
        let discount = parseFloat($('#discount' + rqean + ' input').val()) || 0;
        let rate = parseFloat($('#rate' + rqean + ' input').val()) || 0;
        let gstslab = parseFloat($('#gstslab' + rqean + ' input').val()) || 0;

        let perquantity = remqty > 0 ? discount / remqty : 0;
        let grossAmount = rate * newQuantity;
        let totalDiscount = perquantity * newQuantity;
        let netAmount = grossAmount - totalDiscount;
        let gstamount = (grossAmount * gstslab) / 100;
        let remainingqty = remqty - newQuantity;

        $("#remainingqty" + rqean + " input").val(remainingqty.toFixed(2));
        $("#producttotalDiscount" + rqean + " input").val(totalDiscount.toFixed(2));
        $("#perquantity_discount" + rqean + " input").val(perquantity.toFixed(2));
        $("#amount" + rqean + " input").val(netAmount.toFixed(2));
        $("#gstamount" + rqean + " input").val(gstamount.toFixed(2));
        $("#myInput").focus();
        calculateTotal();
    } else {
        alert("Quantity can not be less than 1.");
        $("#myInput").focus();
    }
}

function deleteRow(ref) {
    $(ref).closest("tr").remove();
    $("#myInput").focus();
    calculateTotal();
}

function calculateTotal() {
    let totQty = 0, subTot = 0, gstamount = 0, totalDiscount = 0;

    $('.receiveqty').each(function() { totQty += parseInt($(this).val(),10) || 0; });
    $('.amount').each(function() { subTot += parseFloat($(this).val()) || 0; });
    $('.gstamount').each(function() { gstamount += parseFloat($(this).val()) || 0; });
    $('.producttotalDiscount input, .producttotalDiscount').each(function() { totalDiscount += parseFloat($(this).val()) || 0; });

    let freightRate = parseFloat($('#freight').val()) || 0;
    let pbTotal = subTot + gstamount + freightRate;

    $('#pbSubTotal').val(subTot.toFixed(2));
    $('#pbQty').val(totQty);
    $('#pbGST').val(gstamount.toFixed(2));
    $('#totaldiscount').val(totalDiscount.toFixed(2));
    $('#pbTotal').val(pbTotal.toFixed(2));

    if (pbTotal > 100000) { $('#ewaybill').attr('required', true); }
    else { $('#ewaybill').removeAttr('required'); }
}

$('#createPurchaseBill').on('submit', function(e) {
    if ($('#productTable tr').length < 1) {
        alert("No product added. Add at least 1 product.");
        e.preventDefault();
    } else { $('#formSubmit').prop('disabled', true); }
});

function searchProduct(ref) {
    if (event.keyCode === 13) {
        var ean = $(ref).val();
        var poProduct = pbProduct.find(p => p.EAN == ean);

        if (!poProduct) { alert('Product NOT Found'); $("#myInput").focus(); return; }

        var productCheck = pbPoTable.find(p => p.product_id == poProduct.id);
        if (!productCheck) { alert('Product NOT Found in this Inward Supply'); $("#myInput").focus(); return; }
        if (productCheck.remqty <= 0) { alert('Product Remaining Quantity is Zero'); $("#myInput").focus(); return; }

        let eanRow = $('#' + ean);
        let remqty = productCheck.remqty;
        let rate = parseFloat(productCheck.rate);
        let gstslab = parseFloat(productCheck.gstslab);
        let totalDiscount = parseFloat(productCheck.remaining_discount) || 0;
        let perquantity = remqty > 0 ? totalDiscount / remqty : 0;

        if (eanRow.length) {
            let quantity = parseInt($('#' + ean + ' input').val(), 10);
            let newQuantity = quantity + 1;
            if (newQuantity > remqty) { alert("Maximum Ordered Quantity Reached."); $("#myInput").focus(); return; }

            $('#' + ean + ' input').val(newQuantity);
            let grossAmount = rate * newQuantity;
            let lineDiscount = perquantity * newQuantity;
            let netAmount = grossAmount - lineDiscount;
            let gstamount = (grossAmount * gstslab)/100;

            $("#amount" + ean + " input").val(netAmount.toFixed(2));
            $("#gstamount" + ean + " input").val(gstamount.toFixed(2));
            $("#perquantity_discount" + ean + " input").val(perquantity.toFixed(2));
            $("#producttotalDiscount" + ean + " input").val(lineDiscount.toFixed(2));
            calculateTotal();
        } else {
            productRows += 1;
            let gstAmountInit = (gstslab * rate / 100).toFixed(2);
            let initialTotalDiscount = (perquantity * 1).toFixed(2); // quantity = 1

            let block = `
            <tr>
                <td><select type="text" class="selectpicker" name="pb[${productRows}][product]" readonly>
                    <option value="${poProduct.id}">${poProduct.name}</option>
                </select></td>
                <td><input type="text" class="form-control" name="pb[${productRows}][EAN]" readonly value="${poProduct.EAN}" /></td>
                <td id="remqty${ean}"><input type="number" class="form-control remqty" name="pb[${productRows}][remqty]" value="${remqty}" readonly/></td>
                <td id="${ean}"><input type="number" min="1" class="form-control receiveqty" name="pb[${productRows}][receiveqty]" value="1" readonly/>
                    <a href="javascript:;" onclick="reduceQuantity(this);"><i class="fa fa-minus"></i></a></td>
                <td id="rate${ean}"><input type="number" class="form-control rate" name="pb[${productRows}][rate]" readonly value="${rate}" /></td>
                <td id="amount${ean}"><input type="number" class="form-control amount" name="pb[${productRows}][amount]" readonly value="${rate - perquantity}" /></td>
                <td id="gstslab${ean}"><input type="number" class="form-control gstslab" name="pb[${productRows}][gstslab]" readonly value="${gstslab}" /></td>
                <td id="gstamount${ean}"><input type="number" class="form-control gstamount" name="pb[${productRows}][gstamount]" readonly value="${gstAmountInit}" /></td>
                <td id="perquantity_discount${ean}"><input type="number" class="form-control perquantity_discount" name="pb[${productRows}][perquantity_discount]" value="${perquantity}" readonly /></td>
                <td id="producttotalDiscount${ean}"><input  class="form-control producttotalDiscount" name="pb[${productRows}][producttotalDiscount]" value="${initialTotalDiscount}" readonly /></td>
                <td id="discount${ean}"><input type="number" class="form-control discount" name="pb[${productRows}][discount]" readonly value="${totalDiscount}" /></td>
                <td id="location${ean}"><input type="text" class="form-control location" name="pb[${productRows}][location]" value="" /></td>
                <td id="discount_type${ean}"><input type="hidden" class="form-control discount_type" name="pb[${productRows}][discount_type]" value="${productCheck.discount_type}" readonly /></td>
                <td><button type="button" class="close" onclick="deleteRow(this);" data-bs-dismiss="alert"><span>&times;</span></button></td>
            </tr>`;
            $("#productTable").append(block);
            $('.selectpicker').selectpicker();
            calculateTotal();
        }

//        $(ref).val('');
        $("#myInput").focus();
    }
}
</script>
@endsection