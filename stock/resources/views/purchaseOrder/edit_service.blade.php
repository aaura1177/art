@extends('layouts.app')

@section('content')

    <div class="mx-2">
        <div class="row mx-0 my-2">
            <h2>Update Service PO</h2>
        </div>

        <form id="myForm" method="POST" action="{{ url('/purchaseOrder/service/view/' . $purchaseOrder->id) }}">
            @csrf

            <!-- Form Starts -->
            <div class="form-group">

                <!-- first row -->
                <div class="row mt-3">
                    <div class="col-4">
                        <label class="control-label">{{ __('PO No.') }}</label>
                        <input type="text" class="form-control" name="pono" required="required"
                            value="{{ $purchaseOrder->pono }}" readonly/>
                    </div>
                    <div class="col-4">
                        <label class="control-label">{{ __('Supplier') }}</label><a href="{{ url('/supplier/create') }}"
                            style="float: right;" target="_blank"> (+New)</a>
                        <select type="text" class="selectpicker" data-live-search="true" name="supplier_id"
                            onchange="handleSelectSupplier(this)" required="required" id='supplier_id'>

                            <option value="" selected disabled>Select Supplier</option>
                            @if (isset($supplier))
                                @foreach ($supplier as $key => $supplier)
                                    <option value="{{ $supplier->id }}"
                                        {{ $purchaseOrder->supplier_id == $supplier->id ? 'selected' : '' }}
                                        data-gstpercent="{{ $supplier->gstpercent }}">
                                        {{ $supplier->c_name }}

                                    </option>
                                @endforeach
                            @endif
                        </select>
                         <input type="number" value="{{ $supplier->gstpercent }}"
                                                id="supplier_gstpercent" hidden>

                    </div>
                </div>

                <div class="row mt-3">
                    <div class="col-4">
                        <label class="control-label">{{ __('Date of PO') }}</label>
                        <input type="date" class="form-control" name="podate" value="{{ $purchaseOrder->podate }}"
                            required="required" />
                    </div>
                    <div class="col-4">
                        <label class="control-label">{{ __('Delivery Date') }}</label>
                        <input type="date" class="form-control" name="del_date" value="{{ $purchaseOrder->del_date }}"
                            required="required" />
                    </div>
                </div>

                <!-- second row -->
                <div class="row mt-3">
                    <div class="col-4">
                        <label class="control-label">{{ __('Supplier Ref. No.') }}</label>
                        <input type="text" class="form-control toUpperCase" name="ref_supplier"
                            value="{{ $purchaseOrder->ref_supplier }}" required="required" />
                    </div>
                    <div class="col-4">
                        <label class="control-label">{{ __('Buyer Order Number') }}</label>
                        <input type="text" class="form-control toUpperCase" name="buyer_orderno"
                            value="{{ $purchaseOrder->buyer_orderno }}" required="required" />
                    </div>
                    <div class="col-4">
                        <label class="control-label">{{ __('Address to') }}</label>
                        <select name="address_option" class="form-control">
                           <option value="1" {{ $purchaseOrder->address_option == '1' ? 'selected' : '' }}>Office
                            </option>
                        </select>
                    </div>
                </div>

                <!-- third row -->
                <div class="row mt-3">
                   <div class="col-4">
    <label class="control-label">{{ __('Terms of Payment') }}</label>
    <select class="form-control" name="payterms">
        <option value="30-45 Days" {{ $purchaseOrder->payterms == '30-45 Days' ? 'selected' : '' }}>30-45 Days</option>
        <option value="30 Days" {{ $purchaseOrder->payterms == '30 Days' ? 'selected' : '' }}>30 Days</option>
        <option value="45 Days" {{ $purchaseOrder->payterms == '45 Days' ? 'selected' : '' }}>45 Days</option>
    </select>
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

                <!-- forth Product row -->
                <div class="row mt-5 my-3">
                    <div class="col-6">
                        <h5>Products List</h5>
                    </div>
                </div>

                <!-- Product details -->
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr id="mytable">
                                <th scope="col" style="min-width: 250px;">Product <a href="{{ url('/service/create/product') }}"
                                        target="_blank">
                                        (+New)</a></th>
                                <th scope="col" style="min-width: 100px;">Qty</th>
                                <th scope="col" style="min-width: 100px;">Unit</th>
                                <th scope="col" style="min-width: 100px;">Rate/Item (₹)</th>
                                <th scope="col" style="min-width: 150px;">Amount (₹)</th>
                                <th scope="col" style="min-width: 100px;">GST Slab (%)</th>
                                <th scope="col" style="min-width: 130px;">GST (₹)</th>

                                <th></th>
                            </tr>
                        </thead>
                        <tbody id="productTable">
                            @if (isset($poTable))
                                @foreach ($poTable as $key => $poTable)
                                    <tr>
                                        <td id="pr{{ $poTable->product->id }}">
                                            <select type="text" class="selectpicker service-product-select" onchange="changeHSN(this);"
                                                data-live-search="true" data-len="{{ $key + 1 }}"
                                                name="po[{{ $key + 1 }}][product]" required="required">
                                                <option value="{{ $poTable->product->id }}">
                                                     {{ $poTable->product->name }}
                                                </option>
                                            </select>



                                        </td>

                                        <td id="quan{{ $key + 1 }}">
                                            @if ($poTable->unit == 'Count')
                                                <input type="number" min="1" step="1" max="1" style="width:60px;"
                                                    class="form-control quantity" onchange="changePrice(this);"
                                                    name="po[{{ $key + 1 }}][quantity]"
                                                    data-len="{{ $key + 1 }}" value="{{ $poTable->quantity }}" readonly />
                                            @else
                                                <input type="number" min="0.1" step="0.1" style="width:60px;"
                                                    class="form-control quantity" onchange="changePrice(this);"
                                                    name="po[{{ $key + 1 }}][quantity]"
                                                    data-len="{{ $key + 1 }}" value="{{ number_format($poTable->quantity, 1, '.', '') }}" />
                                            @endif

                                            <input type="hidden" data-len="{{ $key + 1 }}"
                                                name="po[{{ $key + 1 }}][consumed]"
                                                value="{{ $poTable->quantity - $poTable->remqty }}" />
                                        </td>
                                        <td id="unit{{ $key + 1 }}">
                                            <select type="text" class="selectpicker unit-hours unit-product"
                                                data-live-search="true" name="po[{{ $key + 1 }}][unit]"
                                                onchange="changePrice(this);" required="required">
                                                <option value="" disabled>Select</option>
                                                <option value="Count"
                                                    {{ $poTable->unit == 'Count' ? 'selected' : '' }}>Count</option>
                                                <option value="Hours"
                                                    {{ $poTable->unit == 'Hours' ? 'selected' : '' }}>Hours</option>

                                            </select>
                                        </td>
                                        <td id="rate{{ $key + 1 }}">
                                            <input type="number" class="form-control rate" min="0"
                                                step="any" onchange="changePrice(this);"
                                                name="po[{{ $key + 1 }}][rate]" data-len="{{ $key + 1 }}"
                                                value="{{ number_format((float) $poTable->rate, 2, '.', '') }}" readonly />
                                        </td>
                                        <td id="amount{{ $key + 1 }}">
                                            <input type="number" class="form-control amount"
                                                name="po[{{ $key + 1 }}][amount]" data-len="{{ $key + 1 }}"
                                                value="{{ number_format((float) $poTable->amount, 2, '.', '') }}" readonly />
                                        </td>
                                        <td id="gstslab{{ $key + 1 }}">
                                            <input type="number" class="form-control gstslab" min="0"
                                                onchange="changePrice(this);" name="po[{{ $key + 1 }}][gstslab]"
                                                data-len="{{ $key + 1 }}" value="{{ $poTable->gstslab }}"
                                                readonly />
                                        </td>
                                        <td id="gstamount{{ $key + 1 }}">
                                            <input type="number" class="form-control gstamount"
                                                name="po[{{ $key + 1 }}][gstamount]" data-len="{{ $key + 1 }}"
                                                value="{{ number_format((float) $poTable->gstamount, 2, '.', '') }}" readonly />
                                        </td>
                                        <td id="gstamounts{{ $key + 1 }}">
                                            <input type="number" class="form-control gstamounts"
                                                name="po[{{ $key + 1 }}][service_category_id]"
                                                data-len="{{ $key + 1 }}"
                                                value="{{ $poTable->service_category_id }}" hidden readonly />
                                        </td>



                                        <td>
                                            <button type="button" class="close" onclick="deleteRow(this);"
                                                data-bs-dismiss="alert" aria-label="Close"><span
                                                    aria-hidden="true">&times;</span></button>
                                        </td>
                                    </tr>


                                    <tr>
                                        <td id="subRow{{ $key + 1 }}" colspan="12">
                                            <div class="input-wrapper" id="addmoreinputsub1">
                                                @foreach ($subServiceTable as $sub)
                                                    @if ($sub->serviceTable && $sub->serviceTable->id === $poTable->id)
                                                        <div class="add-more-quantity"
                                                            style="margin-top:10px; display:flex; flex-wrap:wrap; gap:10px; align-items:center;">

                                                            <div style="flex:1;">
                                                                <input type="text" class="form-control"
                                                                    name="po[{{ $key + 1 }}][id][]"
                                                                    value="{{ $sub->id }}" placeholder="Name" hidden
                                                                    required />

                                                                <label>Name</label>
                                                                <input type="text" class="form-control"
                                                                    name="po[{{ $key + 1 }}][sub_name][]"
                                                                    value="{{ $sub->sub_name }}" placeholder="Name"
                                                                    required />
                                                            </div>

                                                            <div style="flex:1;">
                                                                <label>Quantity</label>
                                                                @if ($poTable->unit == 'Count')
                                                                    <input type="number" min="1" step="1"
                                                                        class="form-control quantity-ch"
                                                                        onchange="changeDetailsMore(this);"
                                                                        data-len="{{ $key + 1 }}"
                                                                        name="po[{{ $key + 1 }}][sub_quantity][]"
                                                                        value="{{ $sub->sub_quantity }}" readonly />
                                                                @else
                                                                    <input type="number" min="0.1" step="0.1"
                                                                        class="form-control quantity-ch"
                                                                        onchange="changeDetailsMore(this);"
                                                                        data-len="{{ $key + 1 }}"
                                                                        name="po[{{ $key + 1 }}][sub_quantity][]"
                                                                        value="{{ $sub->sub_quantity }}" />
                                                                @endif
                                                            </div>

                                                            <div style="flex:1;">
                                                                <label>Rate</label>
                                                                <input type="number" class="form-control rate-ch"
                                                                    name="po[{{ $key + 1 }}][sub_rate][]"
                                                                    placeholder="Rate" value="{{ number_format((float) $sub->sub_rate, 2, '.', '') }}"
                                                                    required step="0.01"
                                                                    oninput="changeDetailsMore(this)" />
                                                            </div>

                                                            <div style="flex:1;">
                                                                <label>Amount</label>

                                                                <input type="number" step="any" min="1"
                                                                    class="form-control amount-ch"
                                                                    oninput="changeDetailsMore(this);"
                                                                    data-len="{{ $key + 1 }}"
                                                                    name="po[{{ $key + 1 }}][sub_amount][]"
                                                                    value="{{ number_format((float) $sub->sub_amount, 2, '.', '') }}" placeholder="Amount"
                                                                    required />
                                                            </div>

                                                            <div style="flex:1;">
                                                                <label>GST Slab</label>
                                                                <input type="number" min="0"
                                                                    class="form-control gstslab-ch"
                                                                    onchange="changeDetailsMore(this);"
                                                                    data-len="{{ $key + 1 }}"
                                                                    name="po[{{ $key + 1 }}][sub_gstslab][]"
                                                                    placeholder="GST Slab"
                                                                    value="{{ $sub->sub_gstslab }}" readOnly />
                                                            </div>

                                                            <div style="flex:1;">
                                                                <label>GST Amount</label>
                                                                <input type="number" class="form-control gstamount-ch"
                                                                    name="po[{{ $key + 1 }}][sub_gstamount][]"
                                                                    value="{{ $sub->sub_gstamount }}" readonly
                                                                    placeholder="GST Amount" />
                                                            </div>

                                                            <div>
                                                                <label>Action</label><br>
                                                                <button type="button" class="btn btn-danger btn-sm"
                                                                    onclick="removeInput(this)">✖</button>
                                                            </div>

                                                        </div>
                                                    @endif
                                                @endforeach
                                            </div>

                                            <div style="margin-top:10px;">
                                                <button type="button" class="btn btn-sm btn-primary"
                                                    onclick="addMoreInputs(1)">Add More</button>
                                            </div>

                                        </td>
                                    </tr>
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


                <!-- fifth row -->
                <div class="row mt-3">
                    <div class="col-4">
                        <label class="control-label">{{ __('Total GST (₹)') }}</label>
                        <input type="text" class="form-control" name="tgst" id="totalgst"
                            value="{{ $purchaseOrder->tgst }}" readonly />
                    </div>
                    <div class="col-4">
                        <label class="control-label">{{ __('Total Quantity') }}</label>
                        <input type="number" step="0.01" class="form-control" name="tquantity" id="tquantity"
                            value="{{ $purchaseOrder->tquantity }}" readonly />
                    </div>
                    <div class="col-4">
                        <label class="control-label">{{ __('Sub Total Amount (₹)') }}</label>
                        <input type="number" class="form-control" name="subtotalamount" id="subtotalamount"
                            value="{{ $purchaseOrder->subTotal }}" readonly />
                    </div>
                </div>


                <!-- sixth row  -->
                <div class="row mt-3">
                    <div class="col-4">
                        <label class="control-label">{{ __('Total Amount (₹)') }}</label>
                        <input type="number" class="form-control" name="tamount" id="totalamount"
                            value="{{ $purchaseOrder->tamount }}" readonly />
                    </div>
                </div>

                <div class="row col-4">
                    <button id="submitBtn" type="submit" onclick="validateSubmit();"
                        class="btn btn-primary mt-3">Update PO</button>
                </div>
            </div>
        </form>
    </div>
@endsection

@section('footer')
    <!-- Script start for get data from database -->
    {{-- <script type="text/javascript">

    $(document).ready(function () {

    $.ajax({
      'url': "{{ url('/service/product') }}",
      'method': 'GET'
    }).done(function (data) {
      if (data) {
      products = data.product;
      }
    });
    });

    var trcount = $('#productTable tr').length;
    console.log(trcount);
    var productRows = trcount;
    var products = [];

    function changeHSN(ref) {
    var len = $(ref).data('len');
    var id = $(ref).val();

    if ($('#pr' + id).length) {
      alert('product already added');
      $(ref).prop('selectedIndex', 0);
      $(ref).parent().attr("id", 'pr');
    }

    else {
      $(ref).parent().attr("id", 'pr' + id);
      function findProduct(product) {
      return product.id == id;
      }

      var product = products.find(findProduct);
      $("#hsn" + len + " input").val(product.HSN);
      $("#ean" + len + " input").val(product.EAN);
      $("#gstslab" + len + " input").val(product.gstslab);
      $("#categoryId"+len+" input").val(product?.service_category_id || 0);
      supplier_id = $('#supplier_id').val();
      $.ajax({
      'url': "{{ url('/purchaseOrder/spdata') }}",
      'method': 'GET',
      'data': { 'supplier_id': supplier_id, 'product_id': id }
      }).done(function (data) {
      if (data) {
        supplierProduct = data.sp;
        $('#rate' + len + " input").val(data.sp.rate);
        changePrice('#rate' + len + " input");
      }
      });
    }
    }

    function validateSubmit() {
    if ($('#productTable tr').length < 1) {
      alert("No product added. Add atleast 1 product.");
      event.preventDefault();
    }

    else {
      var productTableRow = 0;
      $('#productTable select[class="selectpicker"]').each(function () {
      productTableRow++
      console.log($(this).val());
      if (!$(this).val()) {
        alert("Product Row " + productTableRow + " empty. Select a product or delete the row.");
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

    function handleUnitSelect(ref) {
    const tr = ref.closest("tr");
    const quantity = tr.getElementsByClassName("quantity")[0];
    const unit = tr.getElementsByClassName("unit-hours")[1].value;

    const amount = tr.getElementsByClassName('amount')[0];
    console.log(amount);

    const gstamount = tr.getElementsByClassName('gstamount')[0];
    const gstslab = tr.getElementsByClassName('gstslab')[0].value;







    const rate = tr.getElementsByClassName('rate')[0].value;
    console.log(rate);





    if (unit === "Count") {
      quantity.setAttribute("max", 1);
      quantity.value = 1;
      amount.value = rate;
      gstamount.value = ((amount.value * Number(gstslab)) / 100).toFixed(2);
    } else {
      quantity.removeAttribute("max");
    }
    changePrice(ref)

    }

    function changePrice(ref) {

    // handleUnitSelect(ref)

    var len = $(ref).data('len');
    var quantity = $("#quan" + len + " input").val();
    var rate = $("#rate" + len + " input").val();
    var amount = rate * quantity;
    var gstslab = $("#gstslab" + len + " input").val();
    var gst = (amount * gstslab) / 100;

    console.log(quantity, rate, amount, gst);


    $("#amount" + len + " input").val(amount.toFixed(2));
    $("#gstamount" + len + " input").val(gst);

    var arrq = document.getElementsByClassName('quantity');
    var arrgsta = document.getElementsByClassName('gst');
    var arrgtotamt = document.getElementsByClassName('amount');
    var arrgstamt = document.getElementsByClassName('gstamount');

    var totq = 0;
    // var totgsta = 0;
    var totamt = 0;
    var subamount = 0;
    var gstamount = 0;


    for (var i = 0; i < arrq.length; i++) {
      if (parseFloat(arrq[i].value))
      totq += parseInt(arrq[i].value);
    }
    document.getElementById('tquantity').value = totq;

    for (var i = 0; i < arrgstamt.length; i++) {
      if (parseFloat(arrgstamt[i].value))
      gstamount += parseFloat(arrgstamt[i].value);
    }

    document.getElementById('totalgst').value = gstamount.toFixed(2);



    for (var i = 0; i < arrgtotamt.length; i++) {
      if (parseFloat(arrgtotamt[i].value))
      subamount += parseFloat(arrgtotamt[i].value);
      // totgsta = (subamount*18)/100;
      totamt = subamount + gstamount;
    }
    document.getElementById('subtotalamount').value = subamount.toFixed(2);
    // document.getElementById('totalgst').value = totgsta.toFixed(2);
    document.getElementById('totalamount').value = totamt.toFixed(2);
    }

    $("#addProduct").click(function () {

    var options = '<option value="" selected disabled>-- SELECT PRODUCT --</option>';

    productRows += 1;

    $.each(products, function (index, value) {
      options += '<option value="' + value.id + '">' + value.code + " - " + value.name + '</option>';
    });

    var $block = "";
    $block += '<tr>';
      $block += '<td id="pr">';
            $block +=
                '<select class="selectpicker" data-live-search="true" onchange="changeHSN(this);" data-len="' +
                productRows + '" name="po[' + productRows + '][product]">';
            $block += options + '</select>';

            // Wrapper for name/rate inputs
            $block += '<div class="input-wrapper">';
            $block += '<div style="margin-top:10px; display: flex; gap: 10px; align-items: center;">';
            $block += '<input type="text" class="form-control" name="po[' + productRows +
                '][name][]" placeholder="Name"  required/>';
                $block += '<input type="number" class="form-control" name="po[' + productRows +
    '][rate_num][]"  placeholder="Rate"  required step="any" oninput="updateTotalRate(' + productRows + ')" />';

            $block += '<button type="button" class="btn btn-danger btn-sm" onclick="removeInput(this)">✖</button>';
            $block += '</div>';
            $block += '</div>';

            // Add More Button
            $block +=
                '<button type="button" class="btn btn-sm btn-primary mt-2" onclick="addMoreInputs(this)">Add More</button>';
            $block += '</td>';
    $block += '<td id="quan' + productRows + '"><input type="number" min="1" max="1" class="form-control quantity" onchange="changePrice(this);" data-len="' + productRows + '" name="po[' + productRows + '][quantity]" value="1" /></td><input type="hidden" data-len="' + productRows + '" name="po[' + productRows + '][consumed]" value="" /></td>'
    $block += '<td><select type="text" class="selectpicker unit-hours" data-live-search="true" onchange="handleUnitSelect(this);" data-len="' + productRows + '" name="po[' + productRows + '][unit]">';
    $block += '<option value="Count">Count</option><option value="Hours">Hours</option></select></td>';
    $block += '<td id="rate' + productRows + '"><input type="number" step="any" min="0" class="form-control rate" onchange="changePrice(this);" data-len="' + productRows + '" name="po[' + productRows + '][rate]" value="0.00" required /></td>'
    $block += '<td id="amount' + productRows + '"><input type="number" class="form-control amount" name="po[' + productRows + '][amount]" value="0.00" readonly/></td>'
    $block += '<td id="gstslab' + productRows + '"><input type="number" class="form-control gstslab" min="0" onchange="changePrice(this);" data-len="' + productRows + '" name="po[' + productRows + '][gstslab]" value="0.00" required /></td>'
    $block += '<td id="gstamount' + productRows + '"><input type="number" class="form-control gstamount" name="po[' + productRows + '][gstamount]" value="0.00" readonly/></td>'
    $block += '<td><button type="button" class="close" onclick="deleteRow(this);" data-bs-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button></td>';
      $block += '<td id="categoryId'+productRows+'"><input type="number" step="any" min="1" class="form-control"  data-len="' + productRows + '" name="po['+productRows+'][service_category_id]" value=`` hidden/></td>'
    $block += '</tr>';
    $("#productTable").append($block);
    $('.selectpicker').selectpicker();
    });



function addMoreInputs(button) {
    const td = button.closest('td');
    const wrapper = td.querySelector('.input-wrapper');

    const select = td.querySelector('select');
    const rowMatch = select.name.match(/\[(\d+)\]/);
    const rowIndex = rowMatch ? rowMatch[1] : 0;

    const inputGroup = document.createElement('div');
    inputGroup.style.marginTop = '10px';
    inputGroup.style.display = 'flex';
    inputGroup.style.gap = '10px';
    inputGroup.style.alignItems = 'center';

    const nameInput = document.createElement('input');
    nameInput.type = 'text';
    nameInput.name = `po[${rowIndex}][name][]`;
    nameInput.placeholder = 'Name';
    nameInput.required = true;
    nameInput.className = 'form-control';

    const rateInput = document.createElement('input');
    rateInput.type = 'number';
    rateInput.name = `po[${rowIndex}][rate_num][]`;
    rateInput.placeholder = 'Rate';
    rateInput.required = true;
    rateInput.className = 'form-control';
    rateInput.step = 'any';
    rateInput.oninput = () => updateTotalRate(rowIndex);

    const removeBtn = document.createElement('button');
    removeBtn.type = 'button';
    removeBtn.className = 'btn btn-danger btn-sm';
    removeBtn.innerHTML = '✖';
    removeBtn.onclick = function () {
        inputGroup.remove();
        updateTotalRate(rowIndex);
    };

    inputGroup.appendChild(nameInput);
    inputGroup.appendChild(rateInput);
    inputGroup.appendChild(removeBtn);
    wrapper.appendChild(inputGroup);
}

function removeInput(button) {
    const inputGroup = button.closest('div');
    const rowIndex = inputGroup.closest('td').querySelector('select').name.match(/\[(\d+)\]/)[1];

    inputGroup.remove();

    updateTotalRate(rowIndex);
}

function updateTotalRate(rowIndex) {
    const rateInputs = document.querySelectorAll(`input[name="po[${rowIndex}][rate_num][]"]`);
    let total = 0;

    rateInputs.forEach(input => {
        const val = parseFloat(input.value);
        if (!isNaN(val)) {
            total += val;
        }
    });

    const totalInput = document.querySelector(`input[name="po[${rowIndex}][rate]"]`);
    const totalAmout = document.querySelector(`input[name="po[${rowIndex}][amount]"]`);
    const totalqty = document.querySelector(`input[name="po[${rowIndex}][quantity]"]`);
    // console.log();

    const amount = (totalqty.value * total).toFixed(2);
    if (totalInput) {
        totalInput.value = total.toFixed(2);
        totalAmout.value = amount;

    }
    changePrice(rateInputs[0])

}

  </script> --}}



    <script type="text/javascript">
        $(document).ready(function() {

            $.ajax({
                'url': "{{ url('/service/product') }}"
            }).done(function(data) {
                if (data) {
                    products = data.product;
                }
            });

            // Initialize quantity inputs based on their unit values
            $('.unit-product select').each(function() {
                const unit = $(this).val();
                const tr = $(this).closest('tr');
                const quantityInput = tr.find('.quantity')[0];
                const quantityChInputs = tr.next('tr').find('.quantity-ch');

                if (unit === 'Count') {
                    if (quantityInput) {
                        quantityInput.setAttribute('step', '1');
                        quantityInput.setAttribute('min', '1');
                        quantityInput.step = '1';
                        quantityInput.min = '1';
                        quantityInput.setAttribute('readonly', true);
                    }
                    quantityChInputs.each(function() {
                        this.setAttribute('step', '1');
                        this.setAttribute('min', '1');
                        this.step = '1';
                        this.min = '1';
                        this.setAttribute('readonly', true);
                    });
                } else if (unit === 'Hours') {
                    if (quantityInput) {
                        const currentVal = parseFloat(quantityInput.value);
                        quantityInput.setAttribute('step', '0.1');
                        quantityInput.setAttribute('min', '0.1');
                        quantityInput.step = '0.1';
                        quantityInput.min = '0.1';
                        quantityInput.removeAttribute('readonly');
                        if (!isNaN(currentVal) && currentVal > 0) {
                            quantityInput.value = parseFloat(currentVal.toFixed(1));
                        }
                    }
                    quantityChInputs.each(function() {
                        const currentVal = parseFloat(this.value);
                        this.setAttribute('step', '0.1');
                        this.setAttribute('min', '0.1');
                        this.step = '0.1';
                        this.min = '0.1';
                        this.removeAttribute('readonly');
                        if (!isNaN(currentVal) && currentVal > 0) {
                            this.value = parseFloat(currentVal.toFixed(1));
                        }
                    });
                }
            });
        });

        var productRows = document.querySelectorAll('.input-wrapper').length;
        var products = [];

        function roundMoney(value) {
            return Math.round((parseFloat(value) || 0) * 100) / 100;
        }

        function formatMoney(value) {
            return roundMoney(value).toFixed(2);
        }

        function gstFromAmount(amount, gstSlab) {
            return roundMoney((roundMoney(amount) * (parseFloat(gstSlab) || 0)) / 100);
        }

        function getParentProductRow(ref) {
            const el = ref && ref.jquery ? ref[0] : ref;
            if (!el || !el.closest) {
                return null;
            }
            const tr = el.closest('tr');
            if (!tr) {
                return null;
            }
            if (tr.querySelector('input.quantity')) {
                return tr;
            }
            return tr.previousElementSibling;
        }

        function getRowLen(parentTr) {
            const input = parentTr.querySelector('[data-len]');
            return input ? input.dataset.len : null;
        }

        function getSubRowBlocks(parentTr) {
            const subTr = parentTr.nextElementSibling;
            return subTr ? subTr.querySelectorAll('.add-more-quantity') : [];
        }

        function recalculateGrandTotals() {
            var arrq = document.getElementsByClassName('quantity');
            var arrgtotamt = document.getElementsByClassName('amount');
            var arrgstamt = document.getElementsByClassName('gstamount');

            var totq = 0;
            var totamt = 0;
            var subamount = 0;
            var gstamount = 0;

            for (var i = 0; i < arrq.length; i++) {
                if (parseFloat(arrq[i].value)) {
                    totq += parseFloat(arrq[i].value);
                }
            }
            document.getElementById('tquantity').value = Math.round(totq * 100) / 100;

            for (var i = 0; i < arrgstamt.length; i++) {
                if (parseFloat(arrgstamt[i].value)) {
                    gstamount += roundMoney(arrgstamt[i].value);
                }
            }
            document.getElementById('totalgst').value = formatMoney(gstamount);

            for (var i = 0; i < arrgtotamt.length; i++) {
                if (parseFloat(arrgtotamt[i].value)) {
                    subamount += roundMoney(arrgtotamt[i].value);
                }
            }
            totamt = roundMoney(subamount + gstamount);
            document.getElementById('subtotalamount').value = formatMoney(subamount);
            document.getElementById('totalamount').value = formatMoney(totamt);
        }

        function isServiceProductDuplicate(productId, currentSelect) {
            if (!productId) {
                return false;
            }
            return Array.from(document.querySelectorAll('#productTable .service-product-select'))
                .some(function(sel) {
                    return sel !== currentSelect && sel.value === productId;
                });
        }

        function changeHSN(ref) {
            //console.log('change');

            // Ensure ref is a valid DOM element
            if (!(ref instanceof HTMLElement)) {
                console.error('Invalid reference element.');
                return;
            }

            const len = ref.dataset.len;
            const id = ref.value;

            const supplier_gstpercent = document.getElementById('supplier_gstpercent');
            //   const supplier_gstpercent = document.getElementById('gstpercent');

            if (isServiceProductDuplicate(id, ref)) {
                alert('Product already added');
                ref.selectedIndex = 0;
                if (typeof $(ref).selectpicker === 'function') {
                    $(ref).selectpicker('refresh');
                }
            } else {
                if (Array.isArray(products)) {
                    const product = products.find(product => product.id == id);
                    if (product) {
                        const hsnInput = document.querySelector(`#hsn${len} input`);
                        if (hsnInput) hsnInput.value = product.HSN;

                        const eanInput = document.querySelector(`#ean${len} input`);
                        if (eanInput) eanInput.value = product.EAN;

                        const gstslabInput = document.querySelector(`#gstslab${len} input`);
                        if (gstslabInput) gstslabInput.value = supplier_gstpercent.value;

                        const categoryIdInput = document.querySelector(`#categoryId${len} input`);
                        if (categoryIdInput) categoryIdInput.value = product?.service_category_id || 0;

                        const supplierId = document.getElementById('supplier_id')?.value;
                        if (supplierId) {
                            fetch(`{{ url('/purchaseOrder/spdata') }}?supplier_id=${supplierId}&product_id=${id}`)
                                .then(response => response.json())
                                .then(data => {
                                    if (data) {
                                        const rateInput = document.querySelector(`#rate${len} input`);
                                        if (rateInput) rateInput.value = data.sp.rate;
                                    }
                                })
                                .catch(error => console.error('Error fetching supplier data:', error));
                        }
                    } else {
                        console.error('Product not found.');
                    }
                } else {
                    console.error('Products is not an array.');
                }


            }
            let nextTr = ref.closest('tr').nextElementSibling.querySelectorAll('.gstslab-ch');
            let gstAmount = ref.closest('tr').querySelector('.gstamout-ch');
            //console.log(nextTr);


            if (nextTr) {
                let gstSlab = ref.closest('tr').querySelector('.gstslab').value;
                // let amount =  ref.closest('tr').querySelector('.gstamount').value;

                //console.log(gstSlab);

                for (let i = 0; i < nextTr.length; i++) {
                    nextTr[i].value = gstSlab;


                }
            }
        }


        $('#myForm').on('submit', function() {
            if ($('#productTable tr').length < 1) {
                alert("No product added. Add atleast 1 product.");
                event.preventDefault();
            } else {
                var productTableRow = 0;
                var submitFlag = 0;
                $('#productTable select[class="selectpicker"]').each(function() {
                    productTableRow++;
                    if (!$(this).val()) {
                        alert("Product Row " + productTableRow +
                            " empty. Select a product or delete the row.");
                        event.preventDefault();
                        submitFlag++;
                        return false;
                    }
                });

                if (submitFlag == 0) {
                    $('#submitBtn').prop('disabled', 'true');
                }
            }
        });

        function deleteRow(ref) {
            //console.log(ref.closest('tr').nextElementSibling);

            const nextTr = ref.closest('tr').nextElementSibling;
            if (ref.closest('tr').nextElementSibling.querySelector('.add-more-quantity')) {
                nextTr.remove()
            }
            $(ref).parents("tr").remove();
            changePrice(ref);
        }

        function changeQuantity(ref, refValue) {
            const refRow = getParentProductRow(ref);
            if (!refRow) {
                return;
            }

            const quantity = refRow.querySelector('.quantity');
            if (!quantity) {
                return;
            }

            changeQuantitySub(refRow);

            if (refValue === 'Count') {
                quantity.value = 1;
                quantity.setAttribute('readonly', true);
                quantity.setAttribute('step', '1');
                quantity.setAttribute('min', '1');
                quantity.step = '1';
                quantity.min = '1';
            } else if (refValue === 'Hours') {
                quantity.removeAttribute('readonly');
                quantity.setAttribute('step', '0.1');
                quantity.setAttribute('min', '0.1');
                quantity.step = '0.1';
                quantity.min = '0.1';

                const currentValue = parseFloat(quantity.value);
                if (!quantity.value || isNaN(currentValue) || currentValue <= 0) {
                    quantity.value = 0.1;
                } else {
                    quantity.value = parseFloat(currentValue.toFixed(1));
                }
            }
        }



        function changePrice(ref, skipUnitSync) {
            const tr = getParentProductRow(ref);

            if (tr) {
                const unitSelect = tr.querySelectorAll('select')[1];
                const unitValue = unitSelect ? unitSelect.value : null;

                if (!skipUnitSync && unitValue) {
                    changeQuantity(ref, unitValue);
                }

                const len = getRowLen(tr) || $(ref).data('len');
                if (len && !skipUnitSync) {
                    var quantity = parseFloat($("#quan" + len + " input").val()) || 0;
                    var rate = parseFloat($("#rate" + len + " input").val()) || 0;
                    var amount = roundMoney(rate * quantity);
                    var gstslab = parseFloat($("#gstslab" + len + " input").val()) || 0;
                    var gst = gstFromAmount(amount, gstslab);

                    $("#amount" + len + " input").val(formatMoney(amount));
                    $("#gstamount" + len + " input").val(formatMoney(gst));
                }
            }

            recalculateGrandTotals();
        }



        $("#addProduct").click(function() {
            const gstRate = document.querySelector('#supplier_gstpercent').value;
            console.log(gstRate);

            var options = '<option value="" selected disabled>-- SELECT PRODUCT --</option>';

            productRows += 1;

            $.each(products, function(index, value) {
                options += '<option value="' + value.id + '">' + value.name +
                    '</option>';
            });

            var $block = "";
            $block += '<tr>';
            $block += '<td id="pr">';
            $block +=
                '<select class="selectpicker service-product-select" data-live-search="true" onchange="changeHSN(this);" data-len="' +
                productRows + '" name="po[' + productRows + '][product]">';
            $block += options + '</select>';
            $block += '</td>';

            $block += '<td id="quan' + productRows +
                '"><input type="number" min="1" step="1" class="form-control quantity" onchange="changePrice(this);" data-len="' +
                productRows + '" name="po[' + productRows + '][quantity]" value="1" readonly /></td>';
            $block +=
                '<td><select type="text" class="selectpicker unit-product" data-live-search="true" onchange="changePrice(this);" data-len="' +
                productRows + '" name="po[' + productRows + '][unit]">';
            $block += '<option value="Count">Count</option><option value="Hours">Hours</option>';
            $block += '</select></td>';

            $block += '<td id="rate' + productRows +
                '"><input type="number" step="any" min="1" class="form-control rate" onchange="changePrice(this);" data-len="' +
                productRows + '" name="po[' + productRows + '][rate]" value="0.00" required readonly  /></td>';



            $block += '<td id="amount' + productRows +
                '"><input type="number" class="form-control amount" name="po[' + productRows +
                '][amount]" value="0.00" readonly/></td>';


            $block += '<td id="gstslab' + productRows +
                '"><input type="number" min="0" class="form-control gstslab" onchange="changePrice(this);" data-len="' +
                productRows + '" name="po[' + productRows + '][gstslab]"  id="gstslab" readonly/></td>';


            $block += '<td id="gstamount' + productRows +
                '"><input type="number" class="form-control gstamount" name="po[' + productRows +
                '][gstamount]" value="0.00" readonly/></td>';


            $block +=
                '<td><button type="button" class="close" onclick="deleteRow(this);" data-bs-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button></td>';

            $block += '<td id="categoryId' + productRows +
                '"><input type="number" step="any" min="1" class="form-control"  data-len="' + productRows +
                '" name="po[' + productRows + '][service_category_id]" value=`` hidden/></td>';
            $block += '</tr>';






            $block += '<tr >';
            $block += '<td id="subRow' + productRows + '" colspan="12">';
            $block += '<div class="input-wrapper" id="addmoreinputsub' + productRows + '">';

            // Single row of inputs
            $block +=
                '<div class="add-more-quantity" style="margin-top:10px; display:flex; flex-wrap:wrap; gap:10px; align-items:center;">';

            $block += '<div style="flex:1;">';
            $block += '<label>Name</label>';
            $block += '<input type="text" class="form-control" name="po[' + productRows +
                '][sub_name][]" placeholder="Name" required />';
            $block += '</div>';

            $block += '<div style="flex:1;">';
            $block += '<label>Quantity</label>';
            $block +=
                '<input type="number" min="1" step="1" class="form-control quantity-ch" onchange="changeDetailsMore(this);" data-len="' +
                productRows + '" name="po[' + productRows + '][sub_quantity][]" value="1" readonly />';
            $block += '</div>';

            $block += '<div style="flex:1;">';
            $block += '<label>Rate</label>';
            $block += '<input type="number" class="form-control rate-ch" name="po[' + productRows +
                '][sub_rate][]" placeholder="Rate" required step="0.01" oninput="changeDetailsMore(this)" />';
            $block += '</div>';

            $block += '<div style="flex:1;">';
            $block += '<label>Amount</label>';
            $block +=
                '<input type="number" step="any" min="1" class="form-control amount-ch" oninput="changeDetailsMore(this);" data-len="' +
                productRows + '" name="po[' + productRows +
                '][sub_amount][]" value="0.00" placeholder="Amount" required />';
            $block += '</div>';

            $block += '<div style="flex:1;">';
            $block += '<label>GST Slab</label>';
            $block +=
                '<input type="number" min="0" class="form-control gstslab-ch" onchange="changeDetailsMore(this);" data-len="' +
                productRows + '" name="po[' + productRows + '][sub_gstslab][]" placeholder="GST Slab" readOnly/>';
            $block += '</div>';

            $block += '<div style="flex:1;">';
            $block += '<label>GST Amount</label>';
            $block += '<input type="number" class="form-control gstamount-ch" name="po[' + productRows +
                '][sub_gstamount][]" value="0.00" readonly placeholder="GST Amount" />';
            $block += '</div>';

            // $block += '<div>';
            // $block += '<label>Action</label><br>';
            // $block += '<button type="button" class="btn btn-danger btn-sm" onclick="removeInput(this)">✖</button>';
            // $block += '</div>';

            $block += '</div>';

            $block += '</div>';

            $block += '<div style="margin-top:10px;">';
            $block += '<button type="button" class="btn btn-sm btn-primary" onclick="addMoreInputs(' + productRows +
                ')">Add More</button>';
            $block += '</div>';

            $block += '</td>';
            $block += '</tr>';



            $("#productTable").append($block);

            $('.selectpicker').selectpicker();
            handleGstSlab();
        });


        function calculateAmount(rowIndex) {
            var quantity = $('input[name="po[' + rowIndex + '][quantity]"]').val();
            var rate = $('input[name="po[' + rowIndex + '][sub_rate][]"]').val();
            var amount = 0;
            if (quantity && rate) {
                amount = parseFloat(quantity) * parseFloat(rate);
            }
            $('input[name="po[' + rowIndex + '][rate]"]').val(amount.toFixed(2));
        }




        function addMoreInputs(rowIndex) {

            const gstRate = document.querySelector('#supplier_gstpercent').value;
            console.log(gstRate);

            console.log(rowIndex);

            const wrapper = document.getElementById("addmoreinputsub" + rowIndex);

            const inputGroup = document.createElement('div');
            inputGroup.className = 'add-more-quantity';
            inputGroup.style.marginTop = '10px';
            inputGroup.style.display = 'flex';
            inputGroup.style.gap = '10px';
            inputGroup.style.alignItems = 'center';

            // Name Input
            const nameInput = document.createElement('input');
            nameInput.type = 'text';
            nameInput.name = `po[${rowIndex}][sub_name][]`;
            nameInput.placeholder = 'Name';
            nameInput.required = true;
            nameInput.className = 'form-control';
            nameInput.style.flex = '1';

            // Quantity Input
            const quantityInput = document.createElement('input');
            quantityInput.type = 'number';

            // Get the unit from the parent row
            const parentTr = wrapper.closest('tr').previousElementSibling;
            const unitSelect = parentTr ? parentTr.querySelector('.unit-product select') : null;
            const unit = unitSelect ? unitSelect.value : 'Count';

            if (unit === 'Count') {
                quantityInput.setAttribute('min', '1');
                quantityInput.setAttribute('step', '1');
                quantityInput.min = '1';
                quantityInput.step = '1';
                quantityInput.value = '1';
                quantityInput.setAttribute('readonly', true);
            } else if (unit === 'Hours') {
                quantityInput.setAttribute('min', '0.1');
                quantityInput.setAttribute('step', '0.1');
                quantityInput.min = '0.1';
                quantityInput.step = '0.1';
                quantityInput.value = '0.1';
            } else {
                quantityInput.setAttribute('min', '1');
                quantityInput.setAttribute('step', '1');
                quantityInput.min = '1';
                quantityInput.step = '1';
                quantityInput.value = '1';
            }

            quantityInput.name = `po[${rowIndex}][sub_quantity][]`;
            quantityInput.className = 'form-control quantity-ch';
            quantityInput.setAttribute('data-len', rowIndex);
            quantityInput.style.flex = '1';
            quantityInput.onchange = function() {
                changeDetailsMore(this);
            };

            // Rate Input
            const rateNumInput = document.createElement('input');
            rateNumInput.type = 'number';
            rateNumInput.name = `po[${rowIndex}][sub_rate][]`;
            rateNumInput.placeholder = 'Rate';
            rateNumInput.required = true;
            rateNumInput.className = 'form-control rate-ch';
            rateNumInput.step = 'any';
            rateNumInput.style.flex = '1';
            rateNumInput.oninput = function() {
                changeDetailsMore(this);
            };

            // Amount Input
            const amountInput = document.createElement('input');
            amountInput.type = 'number';
            amountInput.name = `po[${rowIndex}][sub_amount][]`;
            amountInput.min = '1';
            amountInput.step = 'any';
            amountInput.placeholder = 'Amount';
            amountInput.value = '0.00';
            amountInput.className = 'form-control amount-ch';
            amountInput.setAttribute('data-len', rowIndex);
            amountInput.style.flex = '1';
            amountInput.onchange = function() {
                changeDetailsMore(this);
            };

            // GST Slab Input
            const gstSlabInput = document.createElement('input');
            gstSlabInput.type = 'number';
            gstSlabInput.readOnly = true;
            gstSlabInput.min = '0';
            gstSlabInput.name = `po[${rowIndex}][sub_gstslab][]`;
            gstSlabInput.className = 'form-control gstslab-ch';
            gstSlabInput.style.flex = '1';
            gstSlabInput.placeholder = 'GST Slab';
            gstSlabInput.value = gstRate;

            gstSlabInput.onchange = function() {
                changeDetailsMore(this);
            };

            // GST Amount Input
            const gstAmountInput = document.createElement('input');
            gstAmountInput.type = 'number';
            gstAmountInput.name = `po[${rowIndex}][sub_gstamount][]`;
            gstAmountInput.value = '0.00';
            gstAmountInput.className = 'form-control gstamount-ch';
            gstAmountInput.readOnly = true;
            gstAmountInput.style.flex = '1';
            gstAmountInput.placeholder = 'GST Amount';

            // Remove Button
            const removeBtn = document.createElement('button');
            removeBtn.type = 'button';
            removeBtn.className = 'btn btn-danger btn-sm';
            removeBtn.innerHTML = '✖';
            removeBtn.onclick = function(e) {
                // inputGroup.remove();
                handleChildTotal(this, inputGroup);
            };

            // Append all inputs inside inputGroup
            inputGroup.appendChild(nameInput);
            inputGroup.appendChild(quantityInput);
            inputGroup.appendChild(rateNumInput);
            inputGroup.appendChild(amountInput);
            inputGroup.appendChild(gstSlabInput);
            inputGroup.appendChild(gstAmountInput);
            inputGroup.appendChild(removeBtn);

            // Finally, add this group to wrapper
            wrapper.appendChild(inputGroup);
        }

      function removeInput(button) {
    const inputGroup = button.closest('.add-more-quantity');
    inputGroup.remove();


    const td = button.closest('td');
    const match = td.querySelector('input[name^="po["]');
    if (match) {
        const rowIndex = match.name.match(/\[(\d+)\]/)[1];
        updateTotalRate(rowIndex);
    }
}


        // function addMoreInputs(button) {


        //     const td = button.closest('td');
        //     const wrapper = td.querySelector('.input-wrapper');

        //     const select = td.querySelector('select');
        //     const rowMatch = select.name.match(/\[(\d+)\]/);
        //     const rowIndex = rowMatch ? rowMatch[1] : 0;

        //     const inputGroup = document.createElement('div');
        //     inputGroup.style.marginTop = '10px';
        //     inputGroup.style.display = 'flex';
        //     inputGroup.style.gap = '10px';
        //     inputGroup.style.alignItems = 'center';

        //     const nameInput = document.createElement('input');
        //     nameInput.type = 'text';
        //     nameInput.name = `po[${rowIndex}][name][]`;
        //     nameInput.placeholder = 'Name';
        //     nameInput.required = true;
        //     nameInput.className = 'form-control';

        //     const rateInput = document.createElement('input');
        //     rateInput.type = 'number';
        //     rateInput.name = `po[${rowIndex}][rate_num][]`;
        //     rateInput.placeholder = 'Rate';
        //     rateInput.required = true;
        //     rateInput.className = 'form-control';
        //     rateInput.step = 'any';
        //     rateInput.oninput = () => updateTotalRate(rowIndex);

        //     const removeBtn = document.createElement('button');
        //     removeBtn.type = 'button';
        //     removeBtn.className = 'btn btn-danger btn-sm';
        //     removeBtn.innerHTML = '✖';
        //     removeBtn.onclick = function() {
        //         inputGroup.remove();
        //         updateTotalRate(rowIndex);
        //     };

        //     inputGroup.appendChild(nameInput);
        //     inputGroup.appendChild(rateInput);
        //     inputGroup.appendChild(removeBtn);
        //     wrapper.appendChild(inputGroup);
        // }

        // function removeInput(button) {
        //     const inputGroup = button.closest('div');
        //     const rowIndex = inputGroup.closest('td').querySelector('select').name.match(/\[(\d+)\]/)[1];

        //     inputGroup.remove();

        //     updateTotalRate(rowIndex);
        // }

        function updateTotalRate(rowIndex) {
            const rateInputs = document.querySelectorAll(`input[name="po[${rowIndex}][sub_rate][]"]`);
            let total = 0;

            rateInputs.forEach(input => {
                const val = parseFloat(input.value);
                if (!isNaN(val)) {
                    total += val;
                }
            });

            const totalInput = document.querySelector(`input[name="po[${rowIndex}][rate]"]`);
            const totalAmout = document.querySelector(`input[name="po[${rowIndex}][amount]"]`);
            const totalqty = document.querySelector(`input[name="po[${rowIndex}][quantity]"]`);
            // //console.log();

            const amount = formatMoney(roundMoney(totalqty.value * total));
            if (totalInput) {
                totalInput.value = formatMoney(total);
                totalAmout.value = amount;
            }
            changePrice(rateInputs[0], true);

        }



        function handleGstSlab() {
            var gst = parseFloat($('#supplier_gst').val()); // Ensure it's a number

            //console.log(gst);
            let trLength = document.getElementsByTagName('tr').length -
                1; // Subtract 1 to exclude any non-relevant rows (like header rows)

            for (let i = 1; i <= trLength; i++) {
                let gstTr = document.getElementById('gstslab' + i); // Get the specific row for GST slab

                //console.log(gstTr);

                if (gstTr) {
                    let gstInput = gstTr.getElementsByTagName('input')[0]; // Get the input field inside the row
                    if (gst !== 0 && !isNaN(gst)) {
                        gstInput.setAttribute('required', 'required'); // Add the required attribute
                    } else {
                        gstInput.removeAttribute('required'); // Remove the required attribute
                    }

                    // Set the minimum value of the input field based on supplier GST
                    if (gst === 1) {
                        gstInput.setAttribute('min', '1'); // Set min to 1 if GST is 1
                    } else {
                        gstInput.removeAttribute('min'); // Remove min attribute if GST is not 1
                    }
                }
            }
        }

        function handleSelectSupplier(ref) {
            var id = $(ref).val();
            var gst = $(ref).find('option:selected').data('gst');
            var gstpercent = $(ref).find('option:selected').data('gstpercent');
            $('#supplier_gst').val(gst);
            $('#supplier_gstpercent').val(gstpercent);
            handleGstSlab(); // Call the function to adjust the min attribute when supplier is selected
        }


        const changeQuantitySub = (parentTr) => {
            const selects = parentTr.querySelectorAll('select');
            const unit = selects.length > 1 ? selects[1].value : '';
            const quantityInputs = getSubRowBlocks(parentTr);

            if (unit === 'Count') {
                for (let i = 0; i < quantityInputs.length; i++) {
                    const qtyInput = quantityInputs[i].querySelector('.quantity-ch');
                    if (!qtyInput) {
                        continue;
                    }

                    qtyInput.value = 1;
                    qtyInput.setAttribute('readonly', true);
                    qtyInput.setAttribute('step', '1');
                    qtyInput.setAttribute('min', '1');
                    qtyInput.step = '1';
                    qtyInput.min = '1';

                    const rate = parseFloat(quantityInputs[i].querySelector('.rate-ch')?.value) || 0;
                    const amount = roundMoney(rate);
                    quantityInputs[i].querySelector('.amount-ch').value = formatMoney(amount);
                    const gstRate = quantityInputs[i].querySelector('.gstslab-ch')?.value;
                    if (gstRate) {
                        quantityInputs[i].querySelector('.gstamount-ch').value = formatMoney(gstFromAmount(amount, gstRate));
                    }
                }
            } else if (unit === 'Hours') {
                for (let i = 0; i < quantityInputs.length; i++) {
                    const qtyInput = quantityInputs[i].querySelector('.quantity-ch');
                    if (!qtyInput) {
                        continue;
                    }

                    qtyInput.removeAttribute('readonly');
                    qtyInput.setAttribute('step', '0.1');
                    qtyInput.setAttribute('min', '0.1');
                    qtyInput.step = '0.1';
                    qtyInput.min = '0.1';

                    if (!qtyInput.value || parseFloat(qtyInput.value) <= 0) {
                        qtyInput.value = 0.1;
                    }
                }
            }
        };

        const changeDetailsMore = (ref) => {
            console.log(ref);

            const gstrateRow = ref.closest('tr');
            // //console.log(gstrateRow, 'gstrateRow');

            const unitElement = gstrateRow.previousElementSibling.querySelector('.unit-product').querySelector(
            'select');
            const gstrateslab = gstrateRow.previousElementSibling.querySelector('.gstslab').value;

            //console.log(unitElement.value);



            const unit = unitElement ? unitElement.value : null;
            //console.log(unit, 'unit');

            const container = ref.closest('.add-more-quantity');

            //console.log(unit , 'uit');


            if (unit === 'Count') {
                const quantityField = container.querySelector('.quantity-ch');
                if (quantityField) {

                    quantityField.value = 1;
                    quantityField.setAttribute('readonly', true);
                    quantityField.setAttribute('step', '1');
                    quantityField.setAttribute('min', '1');
                    quantityField.step = '1';
                    quantityField.min = '1';

                    container.querySelector('.amount-ch').value = formatMoney(parseFloat(container.querySelector('.rate-ch')?.value) || 0);
                    container.querySelector('.gstamount-ch').value = formatMoney(gstFromAmount(container.querySelector('.amount-ch').value, gstrateslab));
                }

            } else if (unit === 'Hours') {
                const quantityField = container.querySelector('.quantity-ch');
                if (quantityField) {
                    quantityField.removeAttribute('readonly');
                    quantityField.setAttribute('step', '0.1');
                    quantityField.setAttribute('min', '0.1');
                    quantityField.step = '0.1';
                    quantityField.min = '0.1';

                    if (!quantityField.value || parseFloat(quantityField.value) <= 0) {
                        quantityField.value = 0.1;
                    }
                }
            }

            container.querySelector('.gstslab-ch').value = gstrateslab
            const quantityField = container.querySelector('.quantity-ch');
            const rateField = container.querySelector('.rate-ch');
            const amountField = container.querySelector('.amount-ch');
            const gstAmountField = container.querySelector('.gstamount-ch');
            // const gstrateField = container.querySelector('.gst-rate');

            const quantity = quantityField ? parseFloat(quantityField.value) || 0 : 0;
            const rate = rateField ? parseFloat(rateField.value) || 0 : 0;
            const gstrate = gstrateslab ? parseFloat(gstrateslab) || 0 : 0;

            const amount = roundMoney(rate * quantity);

            if (amountField) amountField.value = formatMoney(amount);
            if (gstAmountField) gstAmountField.value = formatMoney(gstFromAmount(amount, gstrate));

            handleChildTotal(ref);
        };


        const handleChildTotal = (ref, button = false) => {
            const tr = ref.closest('tr');
            //console.log(tr);
            //console.log(ref);

            if (button) {
                //console.log(button);
                const td = tr.getElementsByTagName('td')[0].querySelector('.input-wrapper');
                //console.log(td);

                td.removeChild(button);


            }

            //console.log(button);



            const rows = tr.getElementsByClassName('add-more-quantity');

            // const tr = rows[0].closest('tr');


            let quantity = 0;
            let rate = 0;
            let amount = 0;
            let gstamount = 0;
            for (let i = 0; i < rows.length; i++) {
                quantity += parseFloat(rows[i].querySelector('.quantity-ch').value) || 0;
                rate += roundMoney(rows[i].querySelector('.rate-ch').value);
                amount += roundMoney(rows[i].querySelector('.amount-ch').value);
                gstamount += roundMoney(rows[i].querySelector('.gstamount-ch').value);
            }

            const parentTr = tr.previousElementSibling;

            parentTr.querySelector('.quantity').value = Math.round(quantity * 100) / 100;
            parentTr.querySelector('.amount').value = formatMoney(amount);
            parentTr.querySelector('.rate').value = formatMoney(rate);
            parentTr.querySelector('.gstamount').value = formatMoney(gstamount);
            changePrice(parentTr, true);

        }
    </script>
@endsection