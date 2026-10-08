@extends('layouts.app')

@section('content')

    <div class="mx-2">
        <div class="row mx-0 my-2">
            <h2>Add Services</h2>
        </div>

        <form id="myForm" method="POST" action="{{ url('/purchaseOrder/create/service') }}">
            @csrf

            <!-- Form Starts -->
            <div class="form-group">
                @if (session('error'))
                    <div class="alert alert-danger">
                        {{ session('error') }}
                    </div>
                @endif

                <!-- first row -->
                <div class="row mt-3">
                    <div class="col-4">
                        <label class="control-label">{{ __('PO No.') }}</label>
                        @php
                        $currentPONumber = isset($po->pono) ? intval(preg_replace('/[^0-9]/', '', $po->pono)) : 0;
                        $nextPONumber = 'S/' . ($currentPONumber + 1);
                    @endphp
                   
                    <input type="text" class="form-control toUpperCase" name="pono" required="required" value="{{ $nextPONumber }}" />
                    </div>
                    <div class="col-4">
                        <label class="control-label">{{ __('Supplier') }}</label><a href="{{ url('/supplier/create') }}"
                            style="float: right;" target="_blank"> (+New)</a>
                        <select type="text" class="selectpicker" data-live-search="true"
                            onchange="handleSelectSupplier(this)" name="supplier_id" required="required" id='supplier_id'>
                            <option value="" selected disabled>Select Supplier</option>
                            @if (isset($supplier))
                                @foreach ($supplier as $key => $supplier)
                                    <option value="{{ $supplier->id }}" id="{{ $supplier->id }}"
                                        data-gst="{{ $supplier->gst }}"
                                        data-gstpercent="{{ $supplier->gstpercent }}"
                                        >
                                        {{ $supplier->c_name }}
                                    </option>
                                @endforeach
                            @endif
                        </select>
                        <input type="number" value="" id="supplier_gst" hidden>
                        <input type="text" id="supplier_gstpercent"hidden readonly> 
                    </div>
                </div>
                <div class="row mt-3">
                    <div class="col-4">
                        <label class="control-label">{{ __('Date of PO') }}</label>
                        <input type="date" class="form-control" name="podate" required="required"
                            value = "{{ date('Y-m-d') }}" />
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
                    <div class="col-4">
                        <label class="control-label">{{ __('Address to') }}</label>
                        <select name="address_option" class="form-control">
                            <option value="1">Office</option>
                        </select>
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

                </textarea>
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
                              <th scope="col" style="min-width: 250px;">Product <a
                                        href="{{ url('/service/create/product') }}" target="_blank"> (+New)</a></th>
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
        $(document).ready(function() {

            $.ajax({
                'url': "{{ url('/service/product') }}"
            }).done(function(data) {
                if (data) {
                    products = data.product;
                }
            });
        });

        var productRows = 0;
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
//   console.log(id);

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
        if (gstslabInput )  gstslabInput.value = supplier_gstpercent.value;

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
  let gstAmount =  ref.closest('tr').querySelector('.gstamout-ch');
  //console.log(nextTr);
  

  if(nextTr){
    let gstSlab = ref.closest('tr').querySelector('.gstslab').value;
    // let amount =  ref.closest('tr').querySelector('.gstamount').value;
   
    //console.log(gstSlab);
    
    for(let i = 0 ; i < nextTr.length ; i++){
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
            if(ref.closest('tr').nextElementSibling.querySelector('.add-more-quantity')){
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
            } else if (refValue === 'Hours') {
                quantity.removeAttribute('readonly');
                quantity.setAttribute('step', '0.01');
                quantity.setAttribute('min', '0.01');
                if (!quantity.value || parseFloat(quantity.value) <= 0) {
                    quantity.value = '0.01';
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
            var options = '<option value="" selected disabled>-- SELECT PRODUCT --</option>';

            productRows += 1;

            $.each(products, function(index, value) {
                options += '<option value="' + value.id + '">'  + value.name +
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
                '"><input type="number" min="1" class="form-control quantity" onchange="changePrice(this);" data-len="' +
                productRows + '" name="po[' + productRows + '][quantity]" value="1" /></td>';
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
            $block += '<div class="add-more-quantity" style="margin-top:10px; display:flex; flex-wrap:wrap; gap:10px; align-items:center;">';

            $block += '<div style="flex:1;">';
            $block += '<label>Name</label>';
            $block += '<input type="text" class="form-control" name="po[' + productRows +
                '][sub_name][]" placeholder="Name" required />';
            $block += '</div>';

            $block += '<div style="flex:1;">';
            $block += '<label>Quantity</label>';
            $block +=
                '<input type="number" min="1" class="form-control quantity-ch" onchange="changeDetailsMore(this);" data-len="' +
                productRows + '" name="po[' + productRows + '][sub_quantity][]" value="1" />';
            $block += '</div>';

            $block += '<div style="flex:1;">';
            $block += '<label>Rate</label>';
            $block += '<input type="number" class="form-control rate-ch" name="po[' + productRows +
                '][sub_rate][]" placeholder="Rate" required step="0.01" oninput="changeDetailsMore(this)" />';
            $block += '</div>';

            $block += '<div style="flex:1;">';
            $block += '<label>Amount</label>';
            $block +=
                '<input type="number" step="0.01" min="0.01" class="form-control amount-ch" oninput="changeDetailsMore(this);" data-len="' +
                productRows + '" name="po[' + productRows +
                '][sub_amount][]" value="0.00" placeholder="Amount" required />';
            $block += '</div>';

            $block += '<div style="flex:1;">';
            $block += '<label>GST Slab</label>';
            $block += '<input type="number" min="0" class="form-control gstslab-ch" onchange="changeDetailsMore(this);" data-len="' +
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
            const wrapper = document.getElementById("addmoreinputsub" + rowIndex);

            const inputGroup = document.createElement('div');
            inputGroup.className= 'add-more-quantity';
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
            quantityInput.min = '0.01';
            quantityInput.value = '1';
            quantityInput.step = '0.01';
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
                handleChildTotal(this , inputGroup);
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
            const inputGroup = button.closest('div');
            const rowIndex = inputGroup.closest('td').querySelector('select').name.match(/\[(\d+)\]/)[1];

            inputGroup.remove();

            updateTotalRate(rowIndex);
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

            console.log(totalInput , totalAmout , totalqty);
            
            // //console.log();

            const amount = roundMoney(totalqty.value * total);
            if (totalInput) {
                totalInput.value = formatMoney(total);
                totalAmout.value = formatMoney(amount);
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
                    qtyInput.setAttribute('step', '0.01');
                    qtyInput.setAttribute('min', '0.01');
                    if (!qtyInput.value || parseFloat(qtyInput.value) <= 0) {
                        qtyInput.value = '0.01';
                    }
                }
            }
        };

const changeDetailsMore = (ref) => {
    //console.log(ref);

    const gstrateRow = ref.closest('tr');
    // //console.log(gstrateRow, 'gstrateRow');

    const unitElement = gstrateRow.previousElementSibling.querySelector('.unit-product').querySelector('select');
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
            const rate = parseFloat(container.querySelector('.rate-ch')?.value) || 0;
            const amount = roundMoney(rate);
            container.querySelector('.amount-ch').value = formatMoney(amount);
            container.querySelector('.gstamount-ch').value = formatMoney(gstFromAmount(amount, gstrateslab));
        }
    }

    container.querySelector('.gstslab-ch').value = gstrateslab;
    const quantityField = container.querySelector('.quantity-ch');
    const rateField = container.querySelector('.rate-ch');
    const amountField = container.querySelector('.amount-ch');
    const gstAmountField = container.querySelector('.gstamount-ch');

    const quantity = quantityField ? parseFloat(quantityField.value) || 0 : 0;
    const rate = rateField ? parseFloat(rateField.value) || 0 : 0;
    const gstrate = gstrateslab ? parseFloat(gstrateslab) || 0 : 0;

    const amount = roundMoney(rate * quantity);

    if (amountField) amountField.value = formatMoney(amount);
    if (gstAmountField) gstAmountField.value = formatMoney(gstFromAmount(amount, gstrate));

    handleChildTotal(ref);
};


const handleChildTotal = (ref , button = false)=>{
    const tr = ref.closest('tr');
    //console.log(tr);
    //console.log(ref);

    if(button){
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
