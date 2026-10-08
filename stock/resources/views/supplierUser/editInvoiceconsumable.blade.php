@extends('layouts.app')

@section('content')
    <div class="mx-2">
        <div class="row mx-0 my-2">
            <h2>Raise Invoice</h2>

        </div>

        <form id='createPurchaseBill' method="POST"
            action="{{ url('/supplier-dashboard/update-invoice') }}/{{ $supplierInvoice->id }}">
            @csrf
            <input type="hidden" name="purchase_order_id" value="{{ $purchaseOrder->id }}" />
            <input type="hidden" name="typeUnit" value="{{ $typeUnit }}" />
            <!-- Form Starts -->
            <div class="form-group">

                <!-- first row -->
                <div class="row mt-3">
                    <div class="col-4">
                        <label class="control-label">{{ __('PO No.') }}</label> <a
                            href="{{ url('/purchaseOrder/create') }}" style="float: right;" target="_blank"> (+New)</a>
                        <select type="text" class="selectpicker" data-live-search="true" name="purchaseOrder_id"
                            onchange="changeDetails(this);" required="required">
                            <option value="" disabled>Select PO No.</option>
                            @if (isset($purchaseOrder))
                                <option selected value="{{ $purchaseOrder->id }}">
                                    {{ $purchaseOrder->pono }}
                                </option>
                            @endif
                        </select>
                    </div>
                    <div class="col-4">
                        <label class="control-label">{{ __('Supplier Name') }}</label>
                        <input type="text" class="form-control" name="supplierName" id="supplierName" readonly
                            value = "{{ $purchaseOrder->supplier->c_name }}" />
                    </div>
                    <div class="col-4">
                        <label class="control-label">{{ __('Delivery Date') }}</label>
                        <input type="date" class="form-control" name="del_date" id="del_date" readonly
                            value = "{{ $purchaseOrder->del_date }}" />
                    </div>
                </div>

                <!-- second row -->
                <div class="row mt-3">
                    <div class="col-4">
                        <label class="control-label">{{ __('Supplier Ref.') }}</label>
                        <input type="text" class="form-control" name="Supplier_ref" id="Supplier_ref" readonly
                            value = "{{ $purchaseOrder->ref_supplier }}" />
                    </div>
                    <div class="col-4">
                        <label class="control-label">{{ __('Ordered Quantity') }}</label>
                        <input type="number" class="form-control" name="poQty" id="total_qty" readonly
                            value = "{{ $purchaseOrder->tquantity }}" />
                    </div>
                    <div class="col-4">
                        <label class="control-label">{{ __('Sub Total Amount') }}</label>
                        <input type="number" class="form-control" name="poAmount" id="total_amount" readonly
                            value = "{{ $purchaseOrder->tamount }}" />
                    </div>
                </div>

                <!-- forth row -->
                <div class="row mt-3">
                    <div class="col-8">
                        <label class="control-label">{{ __('Remark') }}</label>
                        <textarea class="form-control" name="remark" id="remarks" readonly>{{ $purchaseOrder->remarks }}</textarea>
                    </div>
                </div>

                <!-- second row -->
                <div class="row mt-3">
                    <div class="col-4">
                        <label class="control-label">{{ __('Supplier Invoice No.') }}</label>
                        <input type="text" class="form-control toUpperCase" id="supinv" name="supp_inv_no"
                            value="{{ $supplierInvoice->supplier_invoice_number }}" required />
                    </div>

                    <div class="col-4">
                        <label class="control-label">{{ __('E-Way Bill No.') }}</label>
                        <input type="text" class="form-control toUpperCase" name="ewaybill" id="ewaybill"
                            value="{{ $supplierInvoice->eway_bill_no }}" />
                    </div>

                    <div class="col-4">
                        <label class="control-label">{{ __('Vehicle No.') }}</label>
                        <input type="text" class="form-control toUpperCase" name="vehicle_no" id="vehicle_no"
                            value="{{ $supplierInvoice->vehicle_no }}" />
                    </div>
                    <div class="col-4">
                        <label class="control-label">{{ __('Supplier Invoice Date') }}</label>
                        <input type="date" class="form-control" name="invoice_date" id="invoice_date"
                            value="{{ $supplierInvoice->invoice_date }}" />
                    </div>
                </div>


                <!-- forth row -->
                <!--<div class="row mt-3">
                                                <div class="col-8">
                                                  <div class="input-group">
                                                    <span class="input-group-addon" style="border: 1px solid #ccc; padding: 0.4rem;"><i class="fa fa-barcode"></i></span>
                                                    <input id="myInput" type="text" class="form-control" name="enterproductname" onkeyup="searchProduct(this);" placeholder="Enter Product name / SKU / Scan bar code" disabled autocomplete="off"/>
                                                  </div>
                                                </div>
                                              </div>-->
                <div class="row mt-3">
                    <table class="table table-hover">
                        <thead>
                            <tr id="mytable">
                                <th scope="col" style="min-width: 300px;">Product</th>
                                @if ($typeUnit == 1)
                                    <th scope="col">EAN</th>
                                @endif
                                <th scope="col">Remaining QTY</th>
                                <th scope="col">Qty</th>
                                @if ($typeUnit == 2)
                                    <th scope="col">Unit</th>
                                @endif
                                <th scope="col">Rate/Item (₹)</th>
                                <th scope="col">Amount (₹)</th>
                                <th scope="col">GSTSLAB</th>
                                <th scope="col">GST (₹)</th>
                                @if ($typeUnit == 1)
                                    <th scope="col">Discount Type</th>
                                    <th scope="col">Discount(₹)</th>
                                @endif
                            </tr>
                        </thead>
                        <tbody id="productTable">
                            <?php $productRows = 0; ?>
                            @foreach ($poTable as $key => $pots)
                                @php
                                    $isMonthEndLine = (int) ($purchaseOrder->address_option ?? 0) === 100 && isset($pots->id);
                                    $pocRowAttrs = $isMonthEndLine
                                        ? ' data-poc-quantity="' . e($pots->quantity) . '" data-poc-amount="' . e($pots->amount) . '" data-poc-gstamount="' . e($pots->gstamount) . '"'
                                        : '';
                                @endphp
                                <tr{!! $pocRowAttrs !!}>
                                    <?php $productRows++; ?>
                                    @php $sip = null; @endphp


                                    <?php
                                    if ($pots->EAN == null) {
                                        $pots->EAN = $key;
                                    }
                                    ?>


                                    <td scope="col">
                                        @if (isset($pots->product->code))
                                            {{ $pots->product->code }} - {{ $pots->product->name }}
                                        @else
                                            {{ $pots->consumable->name }}
                                                                                         @if($pots->description)
                                             <p>Description :{{$pots->description}}</p>
                                             @endif
                                        @endif

                                        @if (isset($pots->product_id))
                                            <input name="pb[{{ $productRows }}][product]" type="hidden"
                                                value="{{ $pots->product_id }}" />
                                        @else
                                            <input name="pb[{{ $productRows }}][product]" type="hidden"
                                                value="{{ $pots->consumable_id }}" />
                                        @endif
                                        @if ((int) ($purchaseOrder->address_option ?? 0) === 100 && \Illuminate\Support\Facades\Schema::hasColumn('supplier_invoice_products', 'poc_table_id') && isset($pots->id))
                                            <input type="hidden" name="pb[{{ $productRows }}][poc_table_id]"
                                                value="{{ (int) $pots->id }}" />
                                        @endif
                                    </td>
                                    @if ($typeUnit == 1)
                                        <td scope="col">{{ $pots->EAN }}</td>
                                    @endif
                                    <td scope="col" class=""><input class="remqty" type="number"
                                            value="{{ $pots->remqty }}" readonly> </td>



                                    @if ($typeUnit == 1)
                                        @php
                                            $sip = App\supplierInvoiceProduct::where(
                                                'supplier_invoice_id',
                                                $supplierInvoice->id,
                                            )
                                                ->where('product_id', $pots->product_id)
                                                ->first();
                                        @endphp
                                    @else
                                        @php
                                            if ((int) ($purchaseOrder->address_option ?? 0) === 100 && \Illuminate\Support\Facades\Schema::hasColumn('supplier_invoice_products', 'poc_table_id') && isset($pots->id)) {
                                                $sip = App\supplierInvoiceProduct::where(
                                                    'supplier_invoice_id',
                                                    $supplierInvoice->id,
                                                )
                                                    ->where('poc_table_id', (int) $pots->id)
                                                    ->first();
                                            }
                                            if (empty($sip)) {
                                                $sip = App\supplierInvoiceProduct::where(
                                                    'supplier_invoice_id',
                                                    $supplierInvoice->id,
                                                )
                                                    ->where('product_id', $pots->consumable_id)
                                                    ->first();
                                            }
                                        @endphp
                                    @endif


                                    <td scope="col" id="{{ $pots->EAN }}">
                                        <input class="form-control receiveqty"
                                            step="{{ $pots->data_type == 'float' ? '0.01' : '1' }}"
                                            value="{{ $sip->quantity }}" min="0" max="{{ $pots->quantity }}"
                                            name="pb[{{ $productRows }}][receiveqty]" type="number"
                                            onchange="recalQuantity(this);"
                                            oninput="handleTypeRestriction(this, '{{ $pots->data_type }}')" />
                                    </td>


                                    @if ($typeUnit == 2)
                                        <td scope="col" id=""><input type="text" class="form-control"
                                                value="{{ $pots->consumable->unitType->name }}" name="pb[{{ $productRows }}][unit]"
                                                readonly />
                                        </td>
                                    @endif
                                    <td scope="col" id="rate{{ $pots->EAN }}"><input class="form-control rate"
                                            value="{{ $pots->rate }}" name="pb[{{ $productRows }}][rate]" readonly />
                                    </td>
                                    <td scope="col" id="amount{{ $pots->EAN }}"><input value="0"
                                            class="form-control amount" name="pb[{{ $productRows }}][amount]"
                                            readonly /></td>
                                    <td scope="col" id="gstslab{{ $pots->EAN }}"><input class="form-control"
                                            value="{{ $pots->gstslab }}" name="pb[{{ $productRows }}][gstslab]"
                                            readonly /></td>
                                    <td scope="col" id="gstamount{{ $pots->EAN }}"><input value="0"
                                            class="form-control gstamount" name="pb[{{ $productRows }}][gstamount]"
                                            readonly /></td>

                                    @if ($typeUnit == 1)
                                        <td scope="col" id="discount_type{{ $pots->EAN }}"><input
                                                class="form-control" value="{{ $pots->discount_type }}"
                                                name="pb[{{ $productRows }}][discount_type]" readonly /></td>
                                        <td scope="col" id="discount{{ $pots->EAN }}"><input
                                                value="{{ $sip->discount + $pots->remaining_discount }}"
                                                class="form-control discount" name="pb[{{ $productRows }}][discount]"
                                                readonly /></td>
                                        <td scope="col" id="discountamount{{ $pots->EAN }}"><input
                                                class="form-control discountamount" value="{{ $pots->discountamount }}"
                                                name="pb[{{ $productRows }}][discountamount]"  /></td>dd
                                        <td scope="col" id="remaing_discount{{ $pots->EAN }}"><input
                                                value="{{ $pots->remaing_discount }}"
                                                class="form-control remaing_discount"
                                                name="pb[{{ $productRows }}][remaing_discount]" hidden /></td>
                                        <td scope="col" id="totalquantity{{ $pots->EAN }}"><input
                                                value="{{ $sip->quantity + $pots->remqty }}"
                                                class="form-control totalquantity" hidden /></td>
                                    @endif
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <!-- forth row -->
                <div class="row mt-3">
                    <div class="col-4">
                        <label class="control-label">{{ __('Total Quantity') }}</label>
                        <input type="number" class="form-control" name="pbQty" id="pbQty"
                            value="{{ $supplierInvoice->tquantity }}dfgr" readonly />
                    </div>
                    <div class="col-4">
                        <label class="control-label">{{ __('Sub-Total Amount') }}</label>
                        <input type="number" class="form-control" name="pbSubTotal" id="pbSubTotal"
                            value="{{ $supplierInvoice->subTotal }}" readonly />
                    </div>
                    <div class="col-4">
                        <label class="control-label">{{ __('GST') }}</label>
                        <input type="number" class="form-control" name="pbGST" id="pbGST"
                            value="{{ $supplierInvoice->tgst }}" readonly />
                    </div>
                </div>

                <div class="row mt-3">

                    <div class="col-4">
                        <label class="control-label">{{ __('Total Amount') }}</label>
                        <input type="number" class="form-control" name="pbTotal" id="pbTotal"
                            value="{{ $supplierInvoice->tamount }}" readonly />
                    </div>

                    <div class="col-4">
                        <label class="control-label">{{ __('Total TDS') }}</label>
                        <input type="number" class="form-control" value="" name="tdsTotal" id="tdsTotal"
                            readonly />
                        <input type="number" class="form-control" value="{{ $userSplier->supplier->tdspercent }}"
                            name="tdspercent" id="tdsPercent" hidden />
                        <input type="date" class="form-control" id="tdsDate"
                            value="{{ $userSplier->supplier->tdsdate ?? '' }}" hidden />
                    </div>
                    @if ($typeUnit == 1)
                        <div class="col-4">
                            <label class="control-label">{{ __('Total Discount') }}</label>
                            <input type="number" class="form-control" name="totaldiscount" id="totaldiscount"
                                value="{{ $supplierInvoice->totaldiscount }}" readonly />
                        </div>
                    @endif
                </div>
                <div class="row col-4">
                    <button type="submit" id="hidden" hidden class="btn btn-primary mt-3" disabled>Create Purchase
                        Bill</button>
                    <button type="submit" id="formSubmit" form="createPurchaseBill" class="btn btn-primary mt-3">Update
                        Invoice</button>
                </div>

            </div>
        </form>
    </div>
@endsection

@section('footer')
    @include('partials.supplier-invoice-tds-js')
    @include('partials.month-end-consumable-invoice-math-js')
    <!-- Script start for get data from database -->
    <script type="text/javascript">
        const EWAY_BILL_THRESHOLD = {{ (float) ($ewayThreshold ?? 100000) }};
        const MONTH_END_INVOICE = {{ ((int) ($purchaseOrder->address_option ?? 0) === 100 && (int) $typeUnit === 2) ? 'true' : 'false' }};
        $(document).ready(function() {

            $.ajax({
                'url': "{{ url('/supplier-dashboard/data') }}",
                'method': 'GET'

            }).done(function(data) {

                if (data) {
                    purchaseOrder = data.purchaseOrder;
                    supplier = data.supplier;
                    pbProduct = data.product;
                }



            });

            if ($('#productTable tr').length > 0 && typeof calculateTotal === 'function') {
                calculateTotal();
            } else {
                SupplierInvoiceTds.apply();
            }
            SupplierInvoiceTds.bindInvoiceDate();
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
                'url': "{{ url('/supplier-dashboard/data/pbPoTable') }}" + '/' + id,
                'method': 'GET'

            }).done(function(data) {
                if (data) {
                    pbPoTable = data.poTable;
                }
            });

            function findPo(purchaseBill) {
                return purchaseBill.id == id;
            };

            function findSupplier(supplierName) {
                return supplierName.id == supplierId;
            };

            var purchaseBill = purchaseOrder.find(findPo);
            var supplierId = purchaseBill.supplier_id;
            var supplierName = supplier.find(findSupplier);
            $("#supplierName").val(supplierName.c_name);
            $("#del_date").val(purchaseBill.del_date);
            $("#Supplier_ref").val(purchaseBill.ref_supplier);
            $("#remarks").val(purchaseBill.remarks);
            $("#total_qty").val(purchaseBill.tquantity);
            $("#total_amount").val(purchaseBill.tamount);
            $("#myInput").removeAttr('disabled');
            $("#supinv").select().focus();


        };





        function handleTypeRestriction(input, dataType) {
            let val = input.value;

            if (dataType == 'int') {
                if (val.includes('.')) {
                    input.value = Math.floor(val);
                }
            }
        }

        $(() => $('.receiveqty').each((_, el) => recalQuantity(el)));

      var invoiceType = {{ $typeUnit }};

function recalQuantity(ref) {
    let quantity = parseFloat($(ref).val()) || 0;
    let rqean = $(ref).parent().attr('id');
    let tr = ref.closest('tr');

    let remqty = parseFloat($('#remqty' + rqean + ' input').val()) || 0;
    let rate = parseFloat(tr.querySelector('.rate').value) || 0;

    let gstslabElem = tr.querySelector(`#gstslab${rqean} input`);
    if (!gstslabElem) {
        console.error('gstslab element not found');
        return;
    }
    let gstslab = parseFloat(gstslabElem.value) || 0;

    let oldrate = rate * quantity;
    let finalAmount = oldrate;
    let discountValue = 0;

    if (invoiceType == 1) {
        let discount = parseFloat($("#discount" + rqean + " input").val()) || 0;
        let totalquantity = parseFloat($("#totalquantity" + rqean + " input").val()) || 0;

        let perUnitDiscount = totalquantity > 0 ? discount / totalquantity : 0;
        discountValue = perUnitDiscount * quantity;
        finalAmount = oldrate - discountValue;
    }

    // Save row values
    $("#amount" + rqean + " input").val(finalAmount.toFixed(2));
    $("#discountamount" + rqean + " input").val(discountValue.toFixed(2));
    $("#remaing_discount" + rqean + " input").val(discountValue.toFixed(2));

    // GST
    let gstamount;
    const pocQty = parseFloat(tr.getAttribute('data-poc-quantity')) || 0;
    if (invoiceType !== 1 && pocQty > 0) {
        const totals = monthEndConsumableRowTotals($(tr), quantity);
        $("#amount" + rqean + " input").val(totals.amount.toFixed(2));
        gstamount = totals.gst;
    } else {
        gstamount = (finalAmount * gstslab) / 100;
    }
    $('#gstamount' + rqean + ' input').val(gstamount.toFixed(2));

    $("#myInput").select().focus();

    calculateTotal();
    handleTds();
}

        function deleteRow(ref) {
            $(ref).parents("tr").remove();
            $("#myInput").select().focus();
            calculateTotal();
        };

      function calculateTotal() {
    let totQty = 0, subTot = 0, gstamount = 0, totalDiscount = 0;

    if (invoiceType == 1) {
        // With discount
        $('.receiveqty').each(function() {
            totQty += parseFloat($(this).val()) || 0;
        });

        $('.amount').each(function() {
            subTot += parseFloat($(this).val()) || 0;
        });

        $('.gstamount').each(function() {
            gstamount += parseFloat($(this).val()) || 0;
        });

        $('.remaing_discount').each(function() {
            totalDiscount += parseFloat($(this).val()) || 0;
        });

        let pbTotal = subTot + gstamount;

        $('#pbSubTotal').val(subTot.toFixed(2));
        $('#pbQty').val(totQty);
        $('#pbGST').val(gstamount.toFixed(2));
        $('#pbTotal').val(pbTotal.toFixed(2));
        $('#totaldiscount').val(totalDiscount.toFixed(2));

        if (pbTotal > EWAY_BILL_THRESHOLD) {
            $('#ewaybill').attr('required', true);
            $('#eway_file').attr('required', true);
        } else {
            $('#ewaybill').removeAttr('required');
            $('#eway_file').removeAttr('required');
        }

    } else {
        // Without discount
        $('.receiveqty').each(function() {
            totQty += parseFloat($(this).val()) || 0;
        });

        $('.amount').each(function() {
            subTot += parseFloat($(this).val()) || 0;
        });

        $('.gstamount').each(function() {
            gstamount += parseFloat($(this).val()) || 0;
        });

        let pbTotal = subTot + gstamount;

        $('#pbSubTotal').val(subTot.toFixed(2));
        $('#pbQty').val(totQty);
        $('#pbGST').val(gstamount.toFixed(2));
        $('#pbTotal').val(pbTotal.toFixed(2));

        if (pbTotal > EWAY_BILL_THRESHOLD) {
            $('#ewaybill').attr('required', true);
            $('#eway_file').attr('required', true);
        } else {
            $('#ewaybill').removeAttr('required');
            $('#eway_file').removeAttr('required');
        }
    }

    handleTds($('#pbSubTotal').val() || 0);
}

        $('#createPurchaseBill').on('submit', function(e) {
            calculateTotal();
            var pbTotal = parseFloat($('#pbTotal').val()) || 0;
            var needsEway = pbTotal > EWAY_BILL_THRESHOLD;
            var hasEway = $.trim($('#ewaybill').val() || '') !== '';
            var hasNewUpload = ($('#eway_file').get(0) && $('#eway_file').get(0).files && $('#eway_file').get(0).files.length > 0);
            var hasExistingUpload = $.trim('{{ $supplierInvoice->eway_bill_pdf ?? '' }}') !== '';
            var qtyExceeded = false;

            $('#productTable input[name*="[receiveqty]"], #productTable input[name*="[receiveqty_box_1]"], #productTable input[name*="[receiveqty_box_2]"]').each(function() {
                var max = parseFloat($(this).attr('max'));
                var val = parseFloat($(this).val()) || 0;
                if (!isNaN(max) && val > max) {
                    qtyExceeded = true;
                    return false;
                }
            });

            if (qtyExceeded) {
                alert("Receive quantity cannot be greater than remaining quantity.");
                e.preventDefault();
                return;
            }

            if (needsEway && (!hasEway || (!hasNewUpload && !hasExistingUpload))) {
                alert("E-Way Bill No. and upload are required when total exceeds the configured threshold.");
                e.preventDefault();
                return;
            }
            if ($('#productTable tr').length < 1) {
                alert("No product added. Add atleast 1 product.");
                e.preventDefault();
            } else {
                $('#formSubmit').prop('disabled', 'true');
            }
        });

        function searchProduct(ref) {

            if (event.keyCode === 13) {
                var ean = $(ref).val();

                function findPbProduct(poProduct) {
                    return poProduct.EAN == ean;
                };

                function checkProduct(productCheck) {
                    return productCheck.product_id == poProductId;
                };

                var poProduct = pbProduct.find(findPbProduct);

                if (poProduct) {
                    var poProductId = poProduct.id;
                    var productCheck = pbPoTable.find(checkProduct);


                    if (productCheck) {
                        var productCheckRemQty = productCheck.remqty;
                        if (productCheckRemQty > 0) {
                            var productCheckQuantity = productCheck.quantity;
                            var quantity = 1;
                            if ($('#' + ean).length) {
                                quantity = parseInt($('#' + ean + ' input').val(), 10);
                                var gstslab = $('#gstslab' + ean + ' input').val();
                                var newQuantity = quantity + 1;
                                if (newQuantity <= productCheckRemQty) {
                                    $('#' + ean + ' input').val(newQuantity);
                                    $('#amount' + ean + ' input').val(newQuantity * productCheck.rate);
                                    var amount = $('#amount' + ean + ' input').val();
                                    var gstamount = (amount * gstslab) / 100;
                                    $('#gstamount' + ean + ' input').val(gstamount.toFixed(2));
                                    calculateTotal();
                                } else {
                                    alert("Maximum Ordered Quantity Reached.");
                                }
                            } else {

                                productRows += 1;

                                var $block = "";
                                $block += '<tr>';
                                $block += '<td><select type="text" class="selectpicker" data-live-search="true" name="pb[' +
                                    productRows + '][product]" readonly><option value="' + poProduct.id + '">' + poProduct
                                    .name + '</option></td>';
                                $block += '<td><input type="text" class="form-control" name="pb[' + productRows +
                                    '][EAN]" readonly value="' + poProduct.EAN + '" /></td>';
                                $block += '<td id="remqty' + ean +
                                    '"><input type="number" class="form-control remqty" data-len="' + productRows +
                                    '" name="pb[' + productRows + '][remqty]" value="' + productCheck.remqty +
                                    '" readonly/></td>'
                                $block += '<td id=' + ean +
                                    '><input type="number" min="1" class="form-control receiveqty" data-len="' +
                                    productRows + '" name="pb[' + productRows +
                                    '][receiveqty]" value="1" readonly/><a  href="javascript:;" onclick="reduceQuantity(this);"><i class="fa fa-minus"></i></a></td>'
                                $block += '<td id="rate' + ean +
                                    '"><input type="number" class="form-control rate" name="pb[' + productRows +
                                    '][rate]" readonly value="' + productCheck.rate + '" /></td>';
                                $block += '<td id=amount' + ean +
                                    '><input type="number" class="form-control amount" name="pb[' + productRows +
                                    '][amount]" readonly value="' + productCheck.rate + '" /></td>';
                                $block += '<td id="gstslab' + ean +
                                    '"><input type="number" class="form-control gstslab" name="pb[' + productRows +
                                    '][gstslab]" readonly value="' + productCheck.gstslab + '" /></td>';
                                $block += '<td id="gstamount' + ean +
                                    '"><input type="number" class="form-control gstamount" name="pb[' + productRows +
                                    '][gstamount]" readonly value="' + ((productCheck.gstslab * productCheck.rate / 100)
                                        .toFixed(2)) + '" /></td>';
                                $block += '<td id="location' + ean +
                                    '"><input type="text" class="form-control location" name="pb[' + productRows +
                                    '][location]" value="" /></td>';
                                $block +=
                                    '<td><button type="button" class="close" onclick="deleteRow(this);" data-bs-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button></td>';
                                $block += '</tr>';
                                $("#productTable").append($block);
                                $('.selectpicker').selectpicker();
                                calculateTotal();

                            }
                        } else {
                            alert('Product Remaining Quantity is Zero');
                        }
                    } else {
                        alert('Product NOT Found in this Inward Supply');

                    }
                } else {
                    alert('Product NOT Found');

                }

                $("#myInput").select().focus();
            };
        };
    </script>
    <!-- Script end -->
@endsection
