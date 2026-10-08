@extends('layouts.app')

@section('content')

    <div class="mx-2">
        <div class="row mx-0 my-2">
            <h2>{{ !empty($draftPrefill) ? 'Create Consumable PO from Draft ' . $draftPrefill->draft_pono : 'Add Consumable PO' }}</h2>
        </div>

        <form id="myForm" method="POST" action="{{ url('/purchaseOrder/createConsumablePo') }}">
            @csrf
            @if (!empty($fromDraftId))
                <input type="hidden" name="from_draft_id" value="{{ $fromDraftId }}" />
            @endif

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
                        <input type="text" class="form-control toUpperCase" name="pono" required="required"
                            value="C/{{ $companyDetails->cpo_no + 1 }}" />
                    </div>
                    <div class="col-4">
                        <label class="control-label">{{ __('Supplier') }}</label><a href="{{ url('/supplier/create') }}"
                            style="float: right;" target="_blank"> (+New)</a>
                        <select type="text" class="selectpicker" data-live-search="true"
                            onchange="handleSelectSupplier(this)" name="supplier_id" id="supplier_id" required="required">
                            <option value="" selected disabled>Select Supplier</option>
                            @if (isset($supplier))
                                @foreach ($supplier as $key => $supplier)
                                    <option value="{{ $supplier->id }}" id="{{ $supplier->id }}"
                                        data-gst="{{ $supplier->gst }}"
                                        {{ !empty($draftPrefill) && (int) $draftPrefill->supplier_id === (int) $supplier->id ? 'selected' : '' }}>
                                        {{ $supplier->c_name }}
                                    </option>
                                @endforeach
                            @endif
                        </select>
                        <input type="number" value="{{ !empty($draftPrefill) ? optional($draftPrefill->supplier)->gst : '' }}" id="supplier_gst" hidden>
                    </div>
                </div>
                <div class="row mt-3">
                    <div class="col-4">
                        <label class="control-label">{{ __('Date of PO') }}</label>
                        <input type="date" class="form-control" name="podate" required="required"
                            min="{{ isset($po->podate) ? $po->podate : '' }}" value="{{ !empty($draftPrefill) ? $draftPrefill->podate : date('Y-m-d') }}" />
                    </div>
                    <div class="col-4">
                        <label class="control-label">{{ __('Delivery Date') }}</label>
                        <input type="date" class="form-control" name="del_date" required="required"
                            value="{{ !empty($draftPrefill) ? $draftPrefill->del_date : date('Y-m-d', strtotime('+3 days')) }}" />
                    </div>
                </div>

                <!-- second row -->
                <div class="row mt-3">
                    <div class="col-4">
                        <label class="control-label">{{ __('Supplier Month') }}</label>
                        @php
                            $selectedMonths = !empty($draftPrefill) && $draftPrefill->month
                                ? explode(',', $draftPrefill->month)
                                : [date('m-Y')];
                        @endphp
                        <select class="selectpicker" name="month[]" required="required" multiple>
                            <option value="{{ date('m-Y') }}" {{ in_array(date('m-Y'), $selectedMonths, true) ? 'selected' : '' }}>{{ date('F Y') }}</option>
                            <option value="{{ date('m-Y', strtotime('previous month')) }}" {{ in_array(date('m-Y', strtotime('previous month')), $selectedMonths, true) ? 'selected' : '' }}>
                                {{ date('F Y', strtotime('previous month')) }}</option>
                        </select>
                    </div>
                    <div class="col-4">
                        <label class="control-label">{{ __('Buyer Order Number') }}</label>
                        <input type="text" class="form-control toUpperCase" name="buyer_orderno" required="required"
                            value="{{ !empty($draftPrefill) ? $draftPrefill->buyer_orderno : '' }}" />
                    </div>
                    <div class="col-4">
                        <label class="control-label">{{ __('Address to') }}</label>
                        <select name="address_option" class="form-control">
                            <option value="2" {{ empty($draftPrefill) || (string) $draftPrefill->address_option === '2' ? 'selected' : '' }}>Factory</option>
                            <option value="1" {{ !empty($draftPrefill) && (string) $draftPrefill->address_option === '1' ? 'selected' : '' }}>Office</option>
                        </select>
                    </div>
                </div>

                <!-- third row -->
                <div class="row mt-3">
                    <div class="col-4">
                        <label class="control-label">{{ __('Terms of Payment') }}</label>
                        <select class="form-control" name="payterms">
                            <option value="30-45 Days" {{ empty($draftPrefill) || $draftPrefill->payterms === '30-45 Days' ? 'selected' : '' }}>30-45 Days</option>
                            <option value="30 Days" {{ !empty($draftPrefill) && $draftPrefill->payterms === '30 Days' ? 'selected' : '' }}>30 Days</option>
                            <option value="45 Days" {{ !empty($draftPrefill) && $draftPrefill->payterms === '45 Days' ? 'selected' : '' }}>45 Days</option>
                        </select>
                    </div>
                    <div class="col-8">
                        <label class="control-label">{{ __('Remarks') }}</label>
                        <textarea class="form-control" name="remarks">{{ !empty($draftPrefill) ? $draftPrefill->remarks : "1. Goods must be delivered to our Factory Address.\n2. Invoice and E waybill should be attached at the time of delivery.\n3. All rates including freight charges." }}</textarea>
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
                                <th scope="col" style="min-width: 250px;">Product <a href="{{ url('/product/create') }}"
                                        target="_blank"> (+New)</a></th>
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
                    <button id="submitBtn" type="submit" form="myForm" class="btn btn-primary mt-3">Create PO</button>
                </div>
            </div>
        </form>
    </div>
@endsection

@section('footer')
@include('purchaseOrder.partials.po_supplier_limit_check', [
    'poKind' => 'consumable',
    'excludeFurniturePoId' => null,
    'excludeConsumablePoId' => null,
])
    <!-- Script start for get data from database -->
    <script type="text/javascript">
        $(document).ready(function() {

            $.ajax({
                'url': "{{ url('/purchaseOrder/cdata') }}"
            }).done(function(data) {
                if (data) {
                    products = data.product;
                }
                if (draftPrefillLines.length) {
                    draftPrefillLines.forEach(function (line) { appendProductRow(line); });
                }
                if ($('#supplier_id').val()) {
                    selectedSupplierId = $('#supplier_id').val();
                    handleGstSlab();
                }
            });
        });

        var productRows = 0;
        var products = [];
        var draftPrefillLines = @json($draftPrefillLines ?? []);
        const MOQ_EPSILON = 1e-6;

        function getRowErrorElement(row) {
            let err = row.querySelector('.qty-rule-error');
            if (!err) {
                err = document.createElement('span');
                err.className = 'qty-rule-error';
                err.style.color = 'red';
                err.style.display = 'none';
                const qtyCell = row.querySelector('td[id^="quan"]');
                if (qtyCell) {
                    qtyCell.appendChild(document.createElement('br'));
                    qtyCell.appendChild(err);
                }
            }
            return err;
        }

        function setRowError(row, message) {
            const err = getRowErrorElement(row);
            if (!err) return;
            err.style.display = message ? 'block' : 'none';
            err.textContent = message || '';
        }

        function isMultipleOfMoq(quantity, moqQty) {
            if (!(moqQty > 0)) return false;
            const ratio = quantity / moqQty;
            return Math.abs(ratio - Math.round(ratio)) <= MOQ_EPSILON;
        }

        function qtyStepForDataType(dataType) {
            if (dataType === 'float') return '0.01';
            if (dataType === 'int') return '1';
            return null;
        }

        function applyMoqRulesToRow(row, product) {
            const quantityInput = row.querySelector('.quantity');
            if (!quantityInput) return;

            const rawIsMoq = product?.is_moq;
            const isMoq = rawIsMoq === 1 || rawIsMoq === '1' || rawIsMoq === true || rawIsMoq === 'true';
            const moqQty = parseFloat(product?.moq_qty ?? 0);
            const dataType = quantityInput.getAttribute('data-type');

            quantityInput.removeAttribute('data-moq-enabled');
            quantityInput.removeAttribute('data-moq-qty');
            setRowError(row, '');

            if (!isMoq) {
                quantityInput.min = '1';
                const step = qtyStepForDataType(dataType);
                if (step !== null) {
                    quantityInput.step = step;
                }
                return;
            }

            if (!(moqQty > 0)) {
                setRowError(row, 'MOQ is enabled but MOQ Qty is missing on consumable master.');
                return;
            }

            quantityInput.setAttribute('data-moq-enabled', '1');
            quantityInput.setAttribute('data-moq-qty', String(moqQty));
            quantityInput.min = String(moqQty);
            quantityInput.step = (dataType === 'int' && Number.isInteger(moqQty)) ? String(Math.max(1, Math.trunc(moqQty))) : String(moqQty);

            const qty = parseFloat(quantityInput.value || '0');
            if (!(qty >= moqQty) || !isMultipleOfMoq(qty, moqQty)) {
                quantityInput.value = String(moqQty);
            }

            setRowError(row, `MOQ ${moqQty}: allowed ${moqQty}, ${moqQty * 2}, ${moqQty * 3}...`);
        }

        function validateMoqRowsBeforeSubmit() {
            const rows = document.querySelectorAll('#productTable tr');
            for (let i = 0; i < rows.length; i++) {
                const row = rows[i];
                const qtyInput = row.querySelector('.quantity');
                if (!qtyInput) continue;

                const isMoq = qtyInput.getAttribute('data-moq-enabled') === '1';
                if (!isMoq) continue;

                const moqQty = parseFloat(qtyInput.getAttribute('data-moq-qty') || '0');
                const qty = parseFloat(qtyInput.value || '0');
                if (!(moqQty > 0)) {
                    setRowError(row, 'MOQ is enabled but MOQ Qty is missing on consumable master.');
                    return `Row ${i + 1}: MOQ is enabled but MOQ Qty is missing on consumable master.`;
                }
                if (qty + MOQ_EPSILON < moqQty) {
                    setRowError(row, `Qty must be at least ${moqQty}.`);
                    return `Row ${i + 1}: Qty must be at least ${moqQty}.`;
                }
                if (!isMultipleOfMoq(qty, moqQty)) {
                    setRowError(row, `Qty must be a multiple of ${moqQty}.`);
                    return `Row ${i + 1}: Qty must be a multiple of ${moqQty}.`;
                }
            }
            return null;
        }

        // const changeUnitName = async (hsn)=>{
        //   console.log(hsn);

        //   const url = '/purchaseOrder/cunitType'
        //     if(hsn){
        //       let response = await fetch(url)
        //     response = await response.json()
        //     console.log(response);



        //    let unittype =  response.unittype.find((item) => item.id == hsn)
        //    console.log(unittype);
        //    const unit = unittype.name

        //    return unit
        //     }else {
        //       return null
        //     }



        // }


        const changeUnitName = async (hsn) => {
            const url = '/purchaseOrder/cunitType';
            if (hsn) {
                let response = await fetch(url);
                response = await response.json();

                let unittype = response.unittype.find((item) => item.id == hsn);
                if (unittype) {
                    return {
                        name: unittype.name,
                        data_type: unittype.data_type
                    };
                }
            }
            return null;
        };


        // async function changeHSN(ref) {
        //   const selected = ref.options[ref.selectedIndex];
        //   const hsn = selected.dataset.id;  
        //   const unit = await changeUnitName(hsn) 
        //   if(unit){
        //     console.log(unit);
        //     alert(unit);

        //     ref.closest('tr').querySelector('.unit').value = unit
        //     console.log(ref.closest('tr').querySelector('.unit'));

        //   } 
        //   else{
        //           ref.closest('tr').querySelector('.unit').value = ''

        //   }



        //   var len = $(ref).data('len');
        //   var id = $(ref).val();

        //   if($('#pr'+id).length){
        //     alert('product already added');
        //     $(ref).prop('selectedIndex',0);
        //     $(ref).parent().attr("id",'pr');
        //   }

        //   else{
        //     $(ref).parent().attr("id",'pr'+id);
        //     function findProduct(product) {
        //       return product.id == id;
        //     }

        //     var product = products.find(findProduct);
        //     // $("#unit"+len+" input").val(product.unit_type_id);
        //     $("#rate"+len+" input").val(product.rate);
        //   changePrice($("#rate"+len+" input"));
        //   }
        // }

        async function changeHSN(ref) {
             let gstslab = 0;
            const selected = ref.options[ref.selectedIndex];
            const hsn = selected.dataset.id;
            const unitInfo = await changeUnitName(hsn);

            if (unitInfo) {
                const row = ref.closest('tr');

                row.querySelector('.unit').value = unitInfo.name;

                const quantityInput = row.querySelector('.quantity');

                quantityInput.setAttribute('data-type', unitInfo.data_type);

                const val = quantityInput.value;

                if (unitInfo.data_type === 'int') {
                    // quantityInput.value = parseInt(val);
                    quantityInput.value = Math.floor(quantityInput.value);

                    quantityInput.step = '1';
                } else if (unitInfo.data_type === 'float') {
                    quantityInput.step = '0.01';
                } else {
                    quantityInput.step = '1';
                }

            } else {
                ref.closest('tr').querySelector('.unit').value = '';
            }


            const productId = ref.value;
            const len = ref.dataset.len;

            const product = products.find(p => p.id == productId);
            const row = ref.closest('tr');
            const descriptionInput = row ? row.querySelector('input[name$="[description]"]') : null;
            if (descriptionInput) {
                descriptionInput.value = product && product.description ? product.description : '';
            }

            let rate = 0.00;

            if (product) {
                let supplierList = product.supplier || [];

                if (typeof supplierList === 'string') {
                    try {
                        const parsed = JSON.parse(supplierList);
                        supplierList = Array.isArray(parsed) ? parsed : [parsed];
                    } catch (e) {
                        supplierList = [supplierList];
                    }
                } else if (typeof supplierList === 'number') {
                    supplierList = [supplierList];
                } else if (!Array.isArray(supplierList)) {
                    supplierList = [supplierList];
                }

                const matched = supplierList.map(String).includes(String(selectedSupplierId));
                // console.log("Product:", product);
                // console.log("Selected Supplier:", selectedSupplierId);
                // console.log("Matched:", matched);
                // console.log("Rate from product:", product.rate);

                gstslab = product.gst;
                if (matched) {
                    rate = product.rate;
                    
                }
            }

              console.log(gstslab);
            setTimeout(() => {
                // alert(rate);
                $(`#rate${len} input`).val(rate);
                  $(`#gstslab${len} input`).val(gstslab); 

                changePrice($(`#rate${len} input`));
                applyMoqRulesToRow(ref.closest('tr'), product);
            }, 50);

        }


        $('#myForm').on('submit', function(event) {
            if (window._poLimitAllowSubmit) {
                window._poLimitAllowSubmit = false;
                return;
            }
            if ($('#productTable tr').length < 1) {
                alert("No product added. Add atleast 1 product.");
                event.preventDefault();
                return;
            }
            const moqErr = validateMoqRowsBeforeSubmit();
            if (moqErr) {
                alert(moqErr);
                event.preventDefault();
                return;
            }
            var productTableRow = 0;
            var submitFlag = 0;
            $('#productTable select').each(function() {
                productTableRow++;
                if (!$(this).val()) {
                    alert("Product Row " + productTableRow +
                        " empty. Select a product or delete the row.");
                    event.preventDefault();
                    submitFlag++;
                    return false;
                }
            });
            if (submitFlag !== 0) {
                return;
            }
            event.preventDefault();
            window.validatePoSupplierLimitOnSubmit().then(function (ok) {
                if (ok) {
                    $('#submitBtn').prop('disabled', true);
                    window._poLimitAllowSubmit = true;
                    $('#myForm').submit();
                }
            });
        });

        function deleteRow(ref) {
            $(ref).parents("tr").remove();
            changePrice();
        }

        function changePrice(ref) {
            var len = $(ref).data('len');
            var quantity = $("#quan" + len + " input").val();
            var rate = $("#rate" + len + " input").val();
            var amount = rate * quantity;
            var gstslab = $("#gstslab" + len + " input").val();
            var gst = (amount * gstslab) / 100;


            $("#amount" + len + " input").val(amount.toFixed(2));
            $("#gstamount" + len + " input").val(gst.toFixed(2));

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
                    // totq += parseInt(arrq[i].value);
                    totq += parseFloat(arrq[i].value);
            }
            let displayQuantity = parseFloat(totq.toFixed(2));
            document.getElementById('tquantity').value = displayQuantity;


            // document.getElementById('tquantity').value = totq;

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
            if (window.schedulePoLimitCheck) {
                window.schedulePoLimitCheck();
            }
        }

        $("#addProduct").click(function() {
            appendProductRow(null);
        });

        function appendProductRow(prefill) {
            var options = '<option value="" selected disabled>-- SELECT CONSUMABLE --</option>';
            productRows += 1;
            var cid = prefill ? prefill.consumable_id : '';

            $.each(products, function(index, value) {
                var sel = prefill && String(value.id) === String(cid) ? ' selected' : '';
                options += '<option value="' + value.id + '" data-id="' + value.unit_type_id + '"' + sel + '>' + value.name + '</option>';
            });

            var $block = "";
            $block += '<tr>';
            $block += '<td id="' + (cid ? 'pr' + cid : 'pr') + '">';
            $block +=
                '<select type="text" class="selectpicker" data-live-search="true" onchange="changeHSN(this);" data-len="' +
                productRows + '" name="po[' + productRows + '][consumable]">';
            $block += options + '</select>';
            $block += '<input type="text" class="form-control" style="margin-top:10px;" name="po[' + productRows +
                '][description]" placeholder="Description(Optional)" value="' + (prefill && prefill.description ? String(prefill.description).replace(/"/g, '&quot;') : '') + '" />'
            $block += '</td>'

            $block += '<td id="quan' + productRows +
                '"><input type="number" step="0.01" min="1" class="form-control quantity" onchange="changePrice(this);" data-len="' +
                productRows + '" name="po[' + productRows +
                '][quantity]" value="' + (prefill ? prefill.quantity : 1) + '" oninput="restrictDecimal(this)"  /> <br> <span class="qty-rule-error" style="color:red; display:none;"></span> </td>'


            $block += '<td id="unit' + productRows +
                '"><input type="text" class="form-control unit" readonly required  data-live-search="true"  data-len="' + productRows +
                '" name="po[' + productRows + '][unit]" value="' + (prefill && prefill.unit ? prefill.unit : '') + '" >';
            $block += '</td>';


            $block += '<td id="rate' + productRows +
                '"><input type="number" step="any" min="0" class="form-control" onchange="changePrice(this);" data-len="' +
                productRows + '" name="po[' + productRows + '][rate]" value="' + (prefill ? prefill.rate : '0.00') + '" required /></td>'


            $block += '<td id="amount' + productRows +
                '"><input type="number" class="form-control amount" name="po[' + productRows +
                '][amount]" value="' + (prefill ? prefill.amount : '0.00') + '" readonly/></td>'
            $block += '<td id="gstslab' + productRows +
                '"><input type="number" min="0" class="form-control" onchange="changePrice(this);" data-len="' +
                productRows + '" name="po[' + productRows + '][gstslab]" value="' + (prefill ? prefill.gstslab : '') + '" /></td>'

            $block += '<td id="gstamount' + productRows +
                '"><input type="number" class="form-control gstamount" name="po[' + productRows +
                '][gstamount]" value="' + (prefill ? prefill.gstamount : '0.00') + '" readonly/></td>'
            $block +=
                '<td><button type="button" class="close" onclick="deleteRow(this);" data-bs-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button></td>';
            $block += '</tr>';
            $("#productTable").append($block);
            $('.selectpicker').selectpicker();
            handleGstSlab();
            if (prefill && cid) {
                var prod = (products || []).find(function (p) { return String(p.id) === String(cid); });
                var row = $('#productTable tr:last')[0];
                if (row && prod) applyMoqRulesToRow(row, prod);
                changePrice($('#rate' + productRows + ' input'));
            }
        }
  
        function restrictDecimal(input) {
            const err = input.nextElementSibling.nextElementSibling;
            const isInt = input.getAttribute('data-type') === 'int';
            if (isInt && input.value.includes('.')) {
                err.style.display = 'block';
                err.textContent = 'Only integer values allowed!';
                input.value = input.value.split('.')[0];
            } else {
                err.style.display = 'none';
                err.textContent = '';   
            }
        }

        function handleGstSlab() {
            var gst = parseFloat($('#supplier_gst').val()); // Ensure it's a number

            console.log(gst);
            let trLength = document.getElementsByTagName('tr').length -
                1; // Subtract 1 to exclude any non-relevant rows (like header rows)

            for (let i = 1; i <= trLength; i++) {
                let gstTr = document.getElementById('gstslab' + i); // Get the specific row for GST slab

                console.log(gstTr);

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
        let selectedSupplierId = null;


        function handleSelectSupplier(ref) {
            var id = $(ref).val();
            selectedSupplierId = id;
            var gst = $(ref).find('option:selected').data('gst');
            $('#supplier_gst').val(gst);
            handleGstSlab(); // Call the function to adjust the min attribute when supplier is selected
        }
    </script>
@endsection
