@extends('layouts.app')

@section('content')

    <div class="mx-2">
        <div class="row mx-0 my-2">
            <h2>Supplier Invoice Approval</h2>

        </div>

        <form id='createPurchaseBill' method="POST"
            action="{{ url('/supplier-service-Invoice/approve/' . $supplierInvoice->id) }}">
            @csrf

            <!-- Form Starts -->
            <div class="form-group">

                <!-- first row -->
                <div class="row mt-3">
                    <div class="col-4">
                        <label class="control-label">{{ __('PO No.') }}</label>
                        <input type="text" class="form-control" name="po_no" id="po_no" value="{{ $purchaseOrder->pono }}"
                            readonly />

                    </div>
                    <div class="col-4">
                        <label class="control-label">{{ __('Supplier Name') }}</label>
                        <input type="text" value="{{ $supplier->name }}" class="form-control" name="supplierName"
                            id="supplierName" readonly />
                    </div>
                    <div class="col-4">
                        <label class="control-label">{{ __('Delivery Date') }}</label>
                        <input type="date" value="{{ $purchaseOrder->del_date }}" class="form-control" name="del_date"
                            id="del_date" readonly />
                    </div>
                </div>

                <!-- second row -->
                <div class="row mt-3">
                    <div class="col-4">
                        <label class="control-label">{{ __('Supplier Ref.') }}</label>
                        <input type="text" value="" class="form-control" name="Supplier_ref" id="Supplier_ref" readonly />
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

                <!-- forth row -->
                <div class="row mt-3">
                    <div class="col-8">
                        <label class="control-label">{{ __('Remark') }}</label>
                        <textarea class="form-control" name="remark" id="remarks"
                            readonly>{{ $purchaseOrder->remarks }}</textarea>
                    </div>
                </div>

                <!-- second row -->
                <div class="row mt-3">
                    <div class="col-4">
                        <label class="control-label">{{ __('Supplier Invoice No.') }}</label>
                        <input type="text" value="{{ $supplierInvoice ? $supplierInvoice->supplier_invoice_number : null }}"
                            class="form-control toUpperCase" id="supinv" name="supp_inv_no" required />
                    </div>
                    <div class="col-4">
                        <?php $newDate = date('Y-m-d', strtotime($supplierInvoice->created_at)); ?>
                        <label class="control-label">{{ __('Supplier Invoice Date.') }}</label>
                        <input type="date" value="<?php echo $newDate; ?>" class="form-control" name="supp_inv_date"
                            required />
                    </div>
                    <div class="col-4">
                        <label class="control-label">{{ __('E-Way Bill No.') }}</label>
                        <input type="text" value="{{ $supplierInvoice ? $supplierInvoice->eway_bill_no : null }}"
                            class="form-control toUpperCase" name="ewaybill" id="ewaybill" />
                    </div>
                </div>

                <!-- forth row -->

                <div class="row mt-3">
                    <table class="table table-hover">
                        <thead>
                            <tr id="mytable">
                                <th scope="col" style="min-width: 300px;">Product</th>

                                <th scope="col">Receive QTY</th>
                                <th scope="col">Unit</th>
                                <th scope="col">Rate/Item (₹)</th>
                                <th scope="col">Amount (₹)</th>
                                <th scope="col">GSTSLAB</th>
                                <th scope="col">GST (₹)</th>
                                <th scope="col">Location</th>
                                <th></th>
                            </tr>
                        </thead>

                        <?php
    $block = '';
    $productRows = 0;
                            ?>
                        @if ($supplierInvoice->purchase_order_type == 'Furniture')
                            @if (isset($supplierInvoiceProduct))
                                @foreach ($supplierInvoiceProduct as $key => $poTable)
                                            <?php
                                    if (isset($poTable['potable']->rate)) {
                                        $productRows += 1;
                                        $ean = $poTable->potable->product->EAN;
                                        $block .= '<tr>';
                                        $block .=
                                            '<td><select type="text" class="selectpicker" data-live-search="true"
                                                                                                                                                    name="pb[' .
                                            $productRows .
                                            '][product]" readonly>
                                                                                                                                                    <option value="' .
                                            $poTable->potable->product->id .
                                            '">' .
                                            $poTable->potable->product->name .
                                            '
                                                                                                                                                    </option>

                                                                                                                                            </td>';

                                        $appqty = $poTable->quantity;
                                        $apppercentage = $poTable->percentage;
                                        if ($poTable->unit == 'Hours') {
                                            $block .=
                                                '<td id=' .
                                                $ean .
                                                '><input type="number" min="1"
                                                                                                                                                    class="form-control receiveqty" data-len="' .
                                                $productRows .
                                                '"
                                                                                                                                                    name="pb[' .
                                                $productRows .
                                                '][receiveqty]" max="' .
                                                $appqty .
                                                '" value="' .
                                                $appqty .
                                                '"
                                                                                                                                                    readonly />
                                                                                                                                                  
                                                                                                                                                        </td>';
                                        } else {
                                            $block .=
                                                '<td id=' .
                                                $ean .
                                                '><input type="number" min="1"
                                                                                                                                                    class="form-control receiveqty" data-len="' .
                                                $productRows .
                                                '"
                                                                                                                                                    name="pb[' .
                                                $productRows .
                                                '][percentage]" max="' .
                                                $apppercentage .
                                                '" value="' .
                                                $apppercentage .
                                                '"
                                                                                                                                                    readonly /></td>';
                                        }

                                        $block .=
                                            '<td id="rate' .
                                            $ean .
                                            '"><input type="text" class="form-control unit-hours"
                                                                                                                                                    name="pb[' .
                                            $productRows .
                                            '][unit]" readonly
                                                                                                                                                    value="' .
                                            $poTable->unit .
                                            '" /></td>';
                                        if (isset($poTable['potable']->rate)) {
                                            $rate = number_format((float) $poTable['potable']->rate, 2, '.', '');
                                        } else {
                                            $rate = '';
                                        }
                                        if (isset($poTable['potable']->gstslab)) {
                                            $gstslab = $poTable['potable']->gstslab;
                                        } else {
                                            $gstslab = '';
                                        }
                                        $block .=
                                            '<td id="rate' .
                                            $ean .
                                            '"><input type="number" class="form-control rate"  name="pb[' .
                                            $productRows .
                                            '][rate]" readonly value="' .
                                            $rate .
                                            '" /></td>';
                                        $block .=
                                            '<td id=amount' .
                                            $ean .
                                            '><input type="number" class="form-control amount" name="pb[' .
                                            $productRows .
                                            '][amount]" readonly value="' .
                                            ($poTable->amount ? number_format((float) $poTable->amount, 2, '.', '') : '0.00') .
                                            '" />
                                                                                                                                            </td>';
                                        $block .=
                                            '<td id="gstslab' .
                                            $ean .
                                            '"><input type="number"
                                                                                                                                                    class="form-control gstslab" name="pb[' .
                                            $productRows .
                                            '][gstslab]" readonly
                                                                                                                                                    value="' .
                                            $gstslab .
                                            '" /></td>';
                                        $block .=
                                            '<td id="gstamount' .
                                            $ean .
                                            '"><input type=""
                                                                                                                                                    class="form-control gstamount" name="pb[' .
                                            $productRows .
                                            '][gstamount]"
                                                                                                                                                    readonly value="' .
                                            number_format((float) ($poTable['gstamount'] ?? 0), 2, '.', '') .
                                            '" />' .
                                            
                                            '</td>';
                                        $block .=
                                            '<td id="location' .
                                            $ean .
                                            '"><input type="text"
                                                                                                                                                    class="form-control location" name="pb[' .
                                            $productRows .
                                            '][location]"
                                                                                                                                                    value="" /></td>';
                                        $block .= '<td><button type="button" class="close" onclick="deleteRow(this);"
                                                                                                                                                    data-bs-dismiss="alert" aria-label="Close"><span
                                                                                                                                                        aria-hidden="true">&times;</span></button></td>';
                                        $block .= '</tr>';
                                        $block .= '<tr><td colspan="12">';

                                        foreach ($subServicePoduct as $index => $item) {
                                            if ($item->serviceProductTableIN && $item->serviceProductTableIN->id == $poTable->id)
 {
                                                $block .= '<div class="row mb-2 subquantity-wrapper" style="display:flex; flex-wrap:wrap; gap:10px;">';

                                                // Hidden ID field (optional)
                                                $block .= '<input type="hidden" name="pb[' . $productRows . '][sub_id][]" value="' . $item->id . '" />';

                                                // Sub Name
                                                $block .= '<div style="flex:1;">';
                                                $block .= '<label>Name</label>';
                                                $block .= '<input type="text" class="form-control" name="pb[' . $productRows . '][sub_name][]" value="' . htmlspecialchars($item->sub_name) . '" required />';
                                                $block .= '</div>';

                                                // Quantity or Percentage
                                                if ($poTable->unit == 'Hours') {
                                                    $block .= '<div style="flex:1;">';
                                                    $block .= '<label>Qty</label>';
                                                    $block .= '<input type="number" step="0.01" min="0" class="form-control subquantity-ch" oninput="handleSubQuantity(this)" name="pb[' . $productRows . '][sub_quantity][]" value="' . number_format((float) $item->sub_quantity, 2, '.', '') . '" max="' . number_format((float) $item->sub_quantity, 2, '.', '') . '" data-original-qty="' . number_format((float) $item->sub_quantity, 2, '.', '') . '"/>';
                                                    $block .= '</div>';
                                                } else {
                                                    $block .= '<div style="flex:1;">';
                                                    $block .= '<label>Percentage</label>';
                                                    $block .= '<input type="number" step="0.01" min="0" class="form-control subquantity-ch" oninput="handleSubQuantity(this)" onchange="handleSubQuantity(this)" name="pb[' . $productRows . '][sub_percentage][]" value="' . number_format((float) $item->sub_percentage, 2, '.', '') . '" max="' . number_format((float) $item->sub_percentage, 2, '.', '') . '" data-original-qty="' . number_format((float) $item->sub_percentage, 2, '.', '') . '"/>';
                                                    $block .= '</div>';
                                                }

                                                // Rate
                                                $block .= '<div style="flex:1;">';
                                                $block .= '<label>Rate</label>';
                                                $block .= '<input type="number" step="0.01" class="form-control rate-ch" name="pb[' . $productRows . '][sub_rate][]" value="' . number_format((float) $item->sub_rate, 2, '.', '') . '" readonly />';
                                                $block .= '</div>';

                                                // Amount
                                                $block .= '<div style="flex:1;">';
                                                $block .= '<label>Amount</label>';
                                                $block .= '<input type="number" step="0.01" class="form-control amount-ch" name="pb[' . $productRows . '][sub_amount][]" value="' . number_format((float) $item->sub_amount, 2, '.', '') . '" readonly />';
                                                $block .= '</div>';

                                                // GST Slab
                                                $block .= '<div style="flex:1;">';
                                                $block .= '<label>GST Slab</label>';
                                                $block .= '<input type="number" step="any" class="form-control gstslab-ch" name="pb[' . $productRows . '][sub_gstslab][]" value="' . $gstslab . '" readonly />';
                                                $block .= '</div>';

                                                // GST Amount
                                                $block .= '<div style="flex:1;">';
                                                $block .= '<label>GST Amount</label>';
                                                $block .= '<input type="number" step="0.01" class="form-control gstamount-ch" name="pb[' . $productRows . '][sub_gstamount][]" value="' . number_format((float) $item->sub_gstamount, 2, '.', '') . '" readonly />';
                                                $block .= '</div>';

                                                $block .= '</div>'; // End of sub row
                                            }
                                        }

                                        $block .= '</td></tr>';

                                    }
                                                            ?>
                                @endforeach
                            @endif

                        @endif

                        <tbody id="productTable">
                            <?php print_r($block); ?>
                        </tbody>
                    </table>
                </div>

                <!-- forth row -->
                <div class="row mt-3">
                    <div class="col-4">
                        <label class="control-label">{{ __('Total Quantity') }}</label>
                        <input type="number" class="form-control" name="pbQty" id="pbQty" readonly />
                    </div>
                    <div class="col-4">
                        <label class="control-label">{{ __('Sub-Total Amount') }}</label>
                        <input type="number" class="form-control" name="pbSubTotal" id="pbSubTotal" value="0" readonly />
                    </div>
                    <div class="col-4">
                        <label class="control-label">{{ __('GST') }}</label>
                        <input type="number" class="form-control" name="pbGST" id="pbGST" readonly />
                    </div>
                </div>

                <div class="row mt-3">
                    <div class="col-4">
                        <label class="control-label">{{ __('Freight') }}</label>
                        <input type="number" class="form-control" onchange="calculateTotal();" name="freight" id="freight"
                            value='0' step="any" min='0' required />
                    </div>
                    <div class="col-4">
                        <label class="control-label">{{ __('Total Amount') }}</label>
                        <input type="number" class="form-control" name="pbTotal" id="pbTotal" readonly />
                    </div>
                    <div class="col-4">
                        <label class="control-label">{{ __('Total TDS') }}</label>
                        <input type="number" class="form-control" value="0" name="tdsTotal" id="tdsTotal" readonly />
                        <input type="number" class="form-control" value="{{ $supplier->tdspercent }}" name="tdspercent"
                            id="tdsTotal" hidden />
                    </div>
                </div>
                <div class="row col-4">
                    <button type="submit" id="formSubmit" form="createPurchaseBill"
                        class="btn btn-primary mt-3">Approve</button>
                </div>

            </div>
        </form>
    </div>
@endsection

@section('footer')
    <!-- Script start for get data from database -->
     <script>
        const roundMoney = (value) => {
            const n = parseFloat(value);
            if (isNaN(n)) {
                return 0;
            }
            return Math.round(n * 100) / 100;
        };

        const formatMoney = (value) => roundMoney(value).toFixed(2);

        const gstFromAmount = (amount, gstRate) => roundMoney((parseFloat(amount) || 0) * (parseFloat(gstRate) || 0) / 100);

        const amountFromRateAndQty = (rate, quantity, unitValue) => {
            const r = parseFloat(rate) || 0;
            const q = parseFloat(quantity) || 0;
            return unitValue === 'Count' ? roundMoney((r * q) / 100) : roundMoney(r * q);
        };

        const getDecimalPlaces = (value) => {
            const parts = String(value).split('.');
            return parts[1] ? parts[1].length : 0;
        };

        const clampApproveQuantity = (input) => {
            const maxQty = parseFloat(input.dataset.originalQty) || parseFloat(input.getAttribute('max')) || 0;
            const decimals = Math.max(getDecimalPlaces(maxQty), 2);
            let qty = parseFloat(input.value);

            if (isNaN(qty) || qty < 0) {
                input.value = 0;
                return 0;
            }

            qty = Math.round(qty * Math.pow(10, decimals)) / Math.pow(10, decimals);
            if (qty > maxQty) {
                qty = maxQty;
            }

            input.value = parseFloat(qty.toFixed(decimals));
            return qty;
        };

        const handleSubQuantity = (ref)=>{
            const tr = ref.closest('tr');
            const parentTr = tr.previousElementSibling;
            const parentWrapper = ref.closest('.subquantity-wrapper');
            const unit = parentTr.querySelector('.unit-hours').value;
            const quantity = clampApproveQuantity(ref);
            
            const rate = Number(parentWrapper.querySelector('.rate-ch').value);
            const gstslab = Number(parentWrapper.querySelector('.gstslab-ch').value);

            const newAmount = amountFromRateAndQty(rate, quantity, unit);

            parentWrapper.querySelector('.amount-ch').value = formatMoney(newAmount);
            parentWrapper.querySelector('.gstamount-ch').value = formatMoney(gstFromAmount(newAmount, gstslab));
            handleSubQuantityTotal(ref ,tr);
        }

        const handleSubQuantityTotal = (ref , tr)=>{
            const wrapper = tr.querySelectorAll('.subquantity-wrapper');

            let amount = 0;
            let gstamount = 0;
            let quantity = 0;

            for(let i = 0 ; i<wrapper.length ; i++){
                amount += roundMoney(wrapper[i].querySelector('.amount-ch').value);
                gstamount += roundMoney(wrapper[i].querySelector('.gstamount-ch').value);
                quantity += Number(wrapper[i].querySelector('.subquantity-ch').value);
            }

            amount = roundMoney(amount);
            gstamount = roundMoney(gstamount);

            const parentTr = ref.closest('tr').previousElementSibling;
            const unit = parentTr.querySelector('.unit-hours').value;
            const rate = Number(parentTr.querySelector('.rate').value);

            parentTr.querySelector('.amount').value = formatMoney(amount);
            parentTr.querySelector('.gstamount').value = formatMoney(gstamount);

            parentTr.querySelector('.receiveqty').value = unit === 'Count'
                ? (rate > 0 ? formatMoney((amount / rate) * 100) : '0.00')
                : formatMoney(quantity);
            calculateTotal();
        }

        const initApproveInvoiceRows = () => {
            document.querySelectorAll('.subquantity-wrapper').forEach(function(wrapper) {
                const amountInput = wrapper.querySelector('.amount-ch');
                const gstInput = wrapper.querySelector('.gstamount-ch');
                if (amountInput) {
                    amountInput.value = formatMoney(amountInput.value);
                }
                if (gstInput) {
                    gstInput.value = formatMoney(gstInput.value);
                }
            });

            document.querySelectorAll('#productTable tr').forEach(function(tr) {
                const subs = tr.querySelectorAll('.subquantity-wrapper');
                if (subs.length === 0) {
                    return;
                }
                const firstSub = subs[0].querySelector('.subquantity-ch');
                if (firstSub) {
                    handleSubQuantityTotal(firstSub, tr);
                }
            });

            document.querySelectorAll('#productTable > tr .amount').forEach(function(el) {
                el.value = formatMoney(el.value);
            });
            document.querySelectorAll('#productTable > tr .gstamount').forEach(function(el) {
                el.value = formatMoney(el.value);
            });

            calculateTotal();
        };

        $('#createPurchaseBill').on('submit', function(e) {
            let qtyExceeded = false;
            $('.subquantity-ch').each(function() {
                const maxQty = parseFloat($(this).data('original-qty')) || parseFloat($(this).attr('max')) || 0;
                const qty = parseFloat($(this).val()) || 0;
                if (qty > maxQty) {
                    qtyExceeded = true;
                    return false;
                }
            });

            if (qtyExceeded) {
                alert('Approved Qty cannot exceed invoiced Qty.');
                e.preventDefault();
            }
        });
     </script>
    <script type="text/javascript">

        $(document).ready(function () {


        });

        var purchaseOrder = [];
        var productCheck = [];
        var pbProduct = [];
        var pbPoTable = [];
        var productRows = 0;
        changeDetails();
        initApproveInvoiceRows();

        function changeDetails() {
            var id = '{{ $purchaseOrder->id }}';


            function findPo(purchaseBill) {
                return purchaseBill.id == id;
            };

            function findSupplier(supplierName) {
                return supplierName.id == supplierId;
            };

            // var purchaseBill = purchaseOrder.find(findPo);
            // var supplierId = purchaseBill.supplier_id;
            // var supplierName = supplier.find(findSupplier);
            $("#supplierName").val('{{ $supplier->c_name }}');
            $("#del_date").val('{{ $purchaseOrder->del_date }}');
            $("#Supplier_ref").val('{{ $purchaseOrder->ref_supplier }}');
            $("#total_qty").val('{{ $purchaseOrder->tquantity }}');
            $("#total_amount").val('{{ $purchaseOrder->tamount }}');
            $("#myInput").removeAttr('disabled');
            $("#supinv").select().focus();

        };

        function reduceQuantity(ref) {
            const unitHours = ref.closest('tr').querySelector('.unit-hours').value;
            quantity = parseFloat($(ref).siblings('input').val());
            var rqean = $(ref).parent().attr('id');


            // /new feild  
            const tr = ref.closest('tr');
            const amountElement = tr.querySelector('.amount');
            const amount = amountElement.value


            //end


            if (quantity > 1) {
                newQuantity = quantity - 0.1;
                $(ref).siblings('input').val(newQuantity);
                if (unitHours === 'Count') {
                    newQuantity = (1 / 100) * newQuantity;
                }
                // var rate = $('#rate' + rqean + ' input').val();
                const rateElement = tr.querySelector('.rate');


                const rate = rateElement.value;
                var gstslab = tr.getElementsByClassName('gstslab')[0].value;
                var oldrate = rate * newQuantity;


                // $("#remainingqty"+rqean+" input").val(remainingqty);
                tr.getElementsByClassName('amount')[0].value = formatMoney(oldrate);
                var gstamount = gstFromAmount(oldrate, gstslab);

                tr.getElementsByClassName('gstamount')[0].value = formatMoney(gstamount);
                $("#myInput").select().focus();
                calculateTotal();
            } else {
                alert("Quantity can not be less than 1.");
                $("#myInput").select().focus();
            }
            handleTds()
        }


        function increaseQuantity(ref) {
            const unitHours = ref.closest('tr').querySelector('.unit-hours').value;
            quantity = parseFloat($(ref).siblings('input').val());
            max = parseFloat($(ref).siblings('input').attr('max'));
            var rqean = $(ref).parent().attr('id');

            const tr = ref.closest('tr');
            const amountElement = tr.querySelector('.amount');
            const amount = amountElement.value



            if (quantity >= 1 && max > quantity) {
                newQuantity = quantity + 0.1;
                $(ref).siblings('input').val(newQuantity);

                if (unitHours === 'Count') {
                    newQuantity = (1 / 100) * newQuantity;
                }

                const rateElement = tr.querySelector('.rate');
                const rate = rateElement.value;
                // var gstslab = $('#gstslab' + rqean + ' input').val();
                var gstslab = tr.getElementsByClassName('gstslab')[0].value;
                var oldrate = rate * newQuantity;


                // $("#remainingqty"+rqean+" input").val(remainingqty);
                // $("#amount" + rqean + " input").val(oldrate);

                tr.getElementsByClassName('amount')[0].value = formatMoney(oldrate);
                var gstamount = gstFromAmount(oldrate, gstslab);


                tr.getElementsByClassName('gstamount')[0].value = formatMoney(gstamount);
                // $('#gstamount' + rqean + ' input').val(formatMoney(gstamount));
                $("#myInput").select().focus();
                calculateTotal();
            } else {


                //alert("Quantity can not be less than 1.");
                $("#myInput").select().focus();
            }
            handleTds()
        }

        function deleteRow(ref) {
            $(ref).parents("tr").remove();
            $("#myInput").select().focus();
            calculateTotal();
        };

        function calculateTotal() {
            var arrTotQty = [];
            var arrSubTot = [];
            var arrgstamount = [];
            var totQty = 0;
            var subTot = 0;
            var gstamount = 0;
            var unit = []; // Initialize an empty array for unit

            arrTotQty = $('.receiveqty');
            arrSubTot = $('.amount');
            arrgstamount = $('.gstamount');
            unit = $('.unit-hours'); // Corrected how the unit array is populated

            console.log(unit);

            // Calculate total quantity
            $.each(arrTotQty, function (index, value) {
                if (unit[index].value == 'Count') {
                    totQty += 1;
                } else {
                    totQty += parseFloat(arrTotQty[index].value);
                }
            });

            // Calculate subtotal
            $.each(arrSubTot, function (index, value) {
                subTot += roundMoney(arrSubTot[index].value);
            });

            // Calculate GST amount
            $.each(arrgstamount, function (index, value) {
                gstamount += roundMoney(arrgstamount[index].value);
            });

            // Calculate total price before freight
            var pbTotal = roundMoney(subTot + gstamount);
            gstamount = formatMoney(gstamount);
            subTot = formatMoney(subTot);
            pbTotal = formatMoney(pbTotal);

            // Set the values in the input fields
            $('#pbSubTotal').val(subTot);
            $('#pbQty').val(totQty.toFixed(2));
            $('#pbGST').val(gstamount);
            $('#pbTotal').val(pbTotal);

            // Get the total amount from the invoice and calculate the final total
            var potamount = $('#pbTotal').val();
            const invoiceTotalEl = document.getElementById('invoiceTotal');
            let totalAmount = Number(potamount);
            if (invoiceTotalEl) {
                totalAmount = Number(invoiceTotalEl.value) + Number(potamount);
            }

            // Check if the total amount exceeds 100,000 to enable eway bill fields
            if (totalAmount > 100000) {
                $('#ewaybill').attr('required', true);
                $('#eway_file').attr('required', true);
            } else {
                $('#ewaybill').removeAttr('required');
                $('#eway_file').removeAttr('required');
            }

            // Handle TDS
            handleTds(document.getElementById('pbSubTotal').value);
        }

        $('#createPurchaseBill').on('submit', function () {
            if ($('#productTable tr').length < 1) {
                alert("No product added. Add atleast 1 product.");
                event.preventDefault();
            } else {
                $('#formSubmit').prop('disabled', 'true');
            }
        });

        function handleTds() {
            const subTotal = document.querySelector('input[name="pbSubTotal"]').value;
            const tdsPercent = document.querySelector('input[name="tdspercent"]').value;
            const tdsTotal = (Number(subTotal) * Number(tdsPercent)) / 100;
            console.log(tdsTotal);

            document.querySelector('input[name="tdsTotal"]').value = formatMoney(tdsTotal);


        }

        window.onload = function () {
            handleTds(document.querySelector('input[name="pbSubTotal"]'));
        }

        
    </script>
    <!-- Script end -->
@endsection