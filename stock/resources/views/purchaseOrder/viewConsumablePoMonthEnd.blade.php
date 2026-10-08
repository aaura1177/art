@extends('layouts.app')

@section('content')
<div class="mx-2">
    <div class="row mx-0 my-2">
        <h2>Update PO MonthEnd</h2>
    </div>

    <form id="myForm" method="POST" action="{{ url('/purchaseOrder/viewConsumablePo/monthend/'.$purchaseOrder->id)}}">
        @csrf

        <!-- first row -->
        <div class="row mt-3">
            <div class="col-4">
                <label class="control-label">{{ __('PO No.') }}</label>
                <input type="text" class="form-control" name="pono" required value="{{ $purchaseOrder->pono }}" />
            </div>
            <div class="col-4">
                <label class="control-label">{{ __('Supplier') }}</label>
                <a href="{{ url('/supplier/create') }}" style="float: right;" target="_blank">(+New)</a>
                <select class="selectpicker" data-live-search="true" name="supplier_id" required onchange="handleSelectSupplier(this)">
                    <option value="" selected disabled>Select Supplier</option>
                    @if(isset($supplier))
                        @foreach($supplier as $sup)
                            <option value="{{ $sup->id }}" data-gst="{{ $sup->gst }}" {{ ($purchaseOrder->supplier_id == $sup->id) ? 'selected' : '' }}>
                                {{ $sup->c_name }}
                            </option>
                        @endforeach
                    @endif
                </select>
            </div>
        </div>

        <!-- second row -->
        <div class="row mt-3">
            <div class="col-4">
                <label class="control-label">{{ __('Date of PO') }}</label>
                <input type="date" class="form-control" name="podate" value="{{ $purchaseOrder->podate }}" required />
            </div>
            <div class="col-4">
                <label class="control-label">{{ __('Delivery Date') }}</label>
                <input type="date" class="form-control" name="del_date" value="{{ $purchaseOrder->del_date }}" required />
            </div>
        </div>

        <!-- third row -->
        <div class="row mt-3">
            <div class="col-4">
                <label class="control-label">{{ __('Supplier Month') }}</label>
                <select class="selectpicker" name="month[]" required multiple>
                    <option value="{{ date('m-Y') }}" {{ in_array(date('m-Y'), explode(',', $purchaseOrder->month)) ? 'selected' : '' }}>{{ date('F Y') }}</option>
                    <option value="{{ date('m-Y', strtotime('previous month')) }}" {{ in_array(date('m-Y', strtotime('previous month')), explode(',', $purchaseOrder->month)) ? 'selected' : '' }}>{{ date('F Y', strtotime('previous month')) }}</option>
                </select>
            </div>
            <div class="col-4">
                <label class="control-label">{{ __('Buyer Order Number') }}</label>
                <input type="text" class="form-control toUpperCase" name="buyer_orderno" value="{{ $purchaseOrder->buyer_orderno }}" required />
            </div>
            <div class="col-4">
                <label class="control-label">{{ __('Address to') }}</label>
                <select name="address_option" class="form-control">
                    <option value="2" {{ ($purchaseOrder->address_option == 2) ? 'selected' : '' }}>Factory</option>
                    <option value="1" {{ ($purchaseOrder->address_option == 1) ? 'selected' : '' }}>Office</option>
                </select>
            </div>
        </div>

        <!-- fourth row -->
        <div class="row mt-3">
            <div class="col-4">
                <label class="control-label">{{ __('Terms of Payment') }}</label>
                <textarea class="form-control" name="payterms">{{ $purchaseOrder->payterms }}</textarea>
            </div>
            <div class="col-8">
                <label class="control-label">{{ __('Remarks') }}</label>
                <textarea class="form-control" name="remarks">{{ $purchaseOrder->remarks }}</textarea>
            </div>
        </div>

        <div class="row mt-3">
            <div class="col-4">
                <label class="control-label">{{ __('PO Revise Date') }}</label>
                <input type="date" class="form-control" name="po_revise_date" /> (Leave empty for today)
            </div>
        </div>

        <!-- Consumables List -->
        <div class="row mt-5">
            <div class="col-6"><h5>Consumables List</h5></div>
        </div>

        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr id="mytable">
                        <th>Consumable</th>
                        <th>Qty</th>
                        <th>Unit</th>
                        <th>Rate/Item (₹)</th>
                        <th>Amount (₹)</th>
                        <th>GST Slab (%)</th>
                        <th>GST (₹)</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody id="productTable">
                    @php $productIndex = 1; @endphp
                    @if(isset($poTable))
                        @foreach($poTable as $row)
                        @php
                            $unitDataType = optional($row->consumable->unitType)->data_type ?? 'float';
                            $qtyStep = $unitDataType === 'int' ? '1' : '0.01';
                        @endphp
                        <tr>
                            <td id="pr{{ $row->consumable->id }}">
                                <select class="selectpicker" data-live-search="true" data-len="{{ $productIndex }}" name="po[{{ $productIndex }}][consumable]" onchange="changeHSN(this)" required>
                                    <option value="{{ $row->consumable->id }}" data-id="{{ $row->consumable->unit_type_id }}">{{ $row->consumable->name }}</option>
                                </select>
                                <input type="text" class="form-control mt-2" name="po[{{ $productIndex }}][description]" placeholder="Description(Optional)" value="{{ $row->description }}" />
                            </td>
                            <td id="quan{{ $productIndex }}">
                                <input type="number" class="form-control quantity" onchange="changePrice(this)" data-len="{{ $productIndex }}" step="{{ $qtyStep }}" data-type="{{ $unitDataType }}" name="po[{{ $productIndex }}][quantity]" value="{{ $row->quantity }}" />
                                <input type="hidden" name="po[{{ $productIndex }}][consumed]" value="{{ $row->quantity - $row->remqty }}" />
                            </td>
                            <td id="unit{{ $productIndex }}"> 
                                <input type="text" class="form-control unit" name="po[{{ $productIndex }}][unit]" value="{{ $row->unit }}" required />
                            </td>
                            <td id="rate{{ $productIndex }}">
                                <input type="number" class="form-control rate" min="0" step="any" onchange="changePrice(this)" data-len="{{ $productIndex }}" name="po[{{ $productIndex }}][rate]" value="{{ $row->rate }}" />
                            </td>
                            <td id="amount{{ $productIndex }}">
                                <input type="number" class="form-control amount" name="po[{{ $productIndex }}][amount]" value="{{ $row->amount }}" readonly/>
                            </td>
                            <td id="gstslab{{ $productIndex }}">
                                <input type="number" class="form-control gstslab" min="0" onchange="changePrice(this)" data-len="{{ $productIndex }}" name="po[{{ $productIndex }}][gstslab]" value="{{ $row->gstslab }}" />
                            </td>
                            <td id="gstamount{{ $productIndex }}">
                                <input type="number" class="form-control gstamount" name="po[{{ $productIndex }}][gstamount]" value="{{ $row->gstamount }}" readonly />
                            </td>
                            <td>
                                <button type="button" class="close" onclick="deleteRow(this)">&times;</button>
                            </td>
                        </tr>
                        @php $productIndex++; @endphp
                        @endforeach
                    @endif
                </tbody>
            </table>
        </div>

        <div class="row mt-2">
            <div class="col">
                <input type="button" id="addProduct" class="btn btn-primary" value="Add Product" />
            </div>
        </div>

        <!-- Totals -->
        <div class="row mt-3">
            <div class="col-4">
                <label>Total GST (₹)</label>
                <input type="text" class="form-control" name="tgst" id="totalgst" value="{{ $purchaseOrder->tgst }}" readonly />
            </div>
            <div class="col-4">
                <label>Total Quantity</label>
                <input type="number" class="form-control" name="tquantity" id="tquantity" value="{{ $purchaseOrder->tquantity }}" readonly />
            </div>
            <div class="col-4">
                <label>Sub Total Amount (₹)</label>
                <input type="number" class="form-control" name="subtotalamount" id="subtotalamount" value="{{ $purchaseOrder->subTotal }}" readonly />
            </div>
        </div>

        <div class="row mt-3">
            <div class="col-4">
                <label>Total Amount (₹)</label>
                <input type="number" class="form-control" name="tamount" id="totalamount" value="{{ $purchaseOrder->tamount }}" readonly />
            </div>
        </div>

        <div class="row col-4">
            <button id="submitBtn" type="submit" class="btn btn-primary mt-3">Update PO</button>
        </div>
    </form>
</div>
@endsection

@section('footer')
<script>
var productRows = {{ isset($poTable) ? count($poTable) : 0 }};
var products = [];
var selectedSupplierId = null;

$(document).ready(function() {
    $.ajax({
        url: "{{ url('/purchaseOrder/cdata') }}"
    }).done(function(data) {
        if (data) products = data.product;
    });
});

function handleSelectSupplier(ref) {
    selectedSupplierId = $(ref).val();
    var gst = $(ref).find('option:selected').data('gst');
    $('#supplier_gst').val(gst);
    handleGstSlab();
}

// Async changeHSN
async function changeHSN(ref) {
    const selected = ref.options[ref.selectedIndex];
    const hsn = selected.dataset.id;

    const unitInfo = await changeUnitName(hsn);

    const row = ref.closest('tr');

    if (unitInfo) {
        row.querySelector('.unit').value = unitInfo.name;
        const quantityInput = row.querySelector('.quantity');
        quantityInput.setAttribute('data-type', unitInfo.data_type);

        if (unitInfo.data_type === 'int') {
            quantityInput.value = Math.floor(quantityInput.value);
            quantityInput.step = '1';
        } else if (unitInfo.data_type === 'float') {
            quantityInput.step = '0.01';
        } else {
            quantityInput.step = '1';
        }
    } else {
        row.querySelector('.unit').value = '';
    }

    // Set rate & GST
    const productId = ref.value;
    const len = $(ref).data('len');
    const product = products.find(p => p.id == productId);
    let rate = 0, gstslab = 0;
    if(product){
        let supplierList = Array.isArray(product.supplier) ? product.supplier : [product.supplier];
        if(supplierList.map(String).includes(String(selectedSupplierId))){
            rate = product.rate;
            gstslab = product.gst;
        }
    }

    $(`#rate${len} input`).val(rate);
    $(`#gstslab${len} input`).val(gstslab);
    changePrice($(`#rate${len} input`));
}

function changeUnitName(hsn) {
    return fetch(`/purchaseOrder/cunitType?id=${hsn}`)
        .then(res => res.json())
        .then(data => data.unittype ? data.unittype.find(u => u.id == hsn) : null)
        .catch(() => null);
}

function deleteRow(ref){ $(ref).closest('tr').remove(); changePrice(); }

function changePrice(ref=null){
    var arrq = document.getElementsByClassName('quantity');
    var arrgstamt = document.getElementsByClassName('gstamount');
    var arrgtotamt = document.getElementsByClassName('amount');
    var totq=0, gstamount=0, subamount=0, totamt=0;

    for(var i=0;i<arrq.length;i++){ if(parseFloat(arrq[i].value)) totq+=parseFloat(arrq[i].value); }
    document.getElementById('tquantity').value = totq.toFixed(2);

    for(var i=0;i<arrgstamt.length;i++){ if(parseFloat(arrgstamt[i].value)) gstamount+=parseFloat(arrgstamt[i].value); }
    document.getElementById('totalgst').value = gstamount.toFixed(2);

    for(var i=0;i<arrgtotamt.length;i++){ if(parseFloat(arrgtotamt[i].value)) subamount+=parseFloat(arrgtotamt[i].value); }
    totamt = subamount + gstamount;
    document.getElementById('subtotalamount').value = subamount.toFixed(2);
    document.getElementById('totalamount').value = totamt.toFixed(2);

    if(ref){
        const len = $(ref).data('len');
        const quantity = parseFloat($(`#quan${len} input`).val()) || 0;
        const rate = parseFloat($(`#rate${len} input`).val()) || 0;
        const gstslab = parseFloat($(`#gstslab${len} input`).val()) || 0;
        $(`#amount${len} input`).val((quantity*rate).toFixed(2));
        $(`#gstamount${len} input`).val(((quantity*rate*gstslab)/100).toFixed(2));
    }
}

$('#myForm').on('submit', function(e){
    if($('#productTable tr').length<1){ alert("Add at least 1 product."); e.preventDefault(); return; }
    var submitFlag=0;
    $('#productTable select').each(function(i){
        if(!$(this).val()){ alert("Product Row "+(i+1)+" empty."); e.preventDefault(); submitFlag++; return false; }
    });
    if(submitFlag==0) $('#submitBtn').prop('disabled',true);
});

$("#addProduct").click(function(){
    productRows++;
    var options = '<option value="" selected disabled>-- SELECT CONSUMABLE --</option>';
    $.each(products, function(i,v){ options+='<option value="'+v.id+'" data-id="'+v.unit_type_id+'">'+v.name+'</option>'; });

    var $row = `<tr>
        <td id="pr">
            <select class="selectpicker" data-live-search="true" onchange="changeHSN(this)" data-len="${productRows}" name="po[${productRows}][consumable]">${options}</select>
            <input type="text" class="form-control mt-2" name="po[${productRows}][description]" placeholder="Description(Optional)" />
        </td>
        <td id="quan${productRows}"><input type="number" step="0.01" min="1" class="form-control quantity" onchange="changePrice(this)" data-len="${productRows}" name="po[${productRows}][quantity]" value="1" /></td>
        <td id="unit${productRows}"><input type="text" class="form-control unit" name="po[${productRows}][unit]" /></td>
        <td id="rate${productRows}"><input type="number" step="any" min="0" class="form-control" onchange="changePrice(this)" data-len="${productRows}" name="po[${productRows}][rate]" value="0.00" required /></td>
        <td id="amount${productRows}"><input type="number" class="form-control amount" name="po[${productRows}][amount]" value="0.00" readonly /></td>
        <td id="gstslab${productRows}"><input type="number" class="form-control gstslab" min="0" onchange="changePrice(this)" data-len="${productRows}" name="po[${productRows}][gstslab]" /></td>
        <td id="gstamount${productRows}"><input type="number" class="form-control gstamount" name="po[${productRows}][gstamount]" value="0.00" readonly /></td>
        <td><button type="button" class="close" onclick="deleteRow(this)">&times;</button></td>
    </tr>`;
    $("#productTable").append($row);
    $('.selectpicker').selectpicker('refresh');
});

function handleGstSlab(){
    var gst = parseFloat($('#supplier_gst').val());
    $('#productTable tr').each(function(){
        let gstInput = $(this).find('.gstslab');
        if(!isNaN(gst) && gst!=0) gstInput.attr('required',true); else gstInput.removeAttr('required');
    });
}
</script>
@endsection
