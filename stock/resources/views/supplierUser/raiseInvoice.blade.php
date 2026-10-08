@extends('layouts.app')

@section('content')
<style>
            /* ===================== Custom Laptop/Desktop Responsive Table ===================== */
            .po-table-wrap {
                width: 100%;
                overflow-x: hidden;
                /* clean on desktop */
            }

            @media (max-width: 1199px) {
                .po-table-wrap {
                    overflow-x: auto;
                }

                /* only scroll on small laptops */
            }

            .po-table {
                width: 100%;
                border-collapse: separate;
                border-spacing: 0 6px;
                table-layout: fixed;
                font-size: 0.93rem;
                color: #222;
            }

            .po-table thead th {
                text-align: left;
                font-weight: 600;
                padding: 10px 12px;
                border-bottom: 1px solid #e6e6e6;
                background: #f8f9fb;
                white-space: nowrap;
            }

            .po-table tbody td {
                padding: 8px 12px;
                vertical-align: middle;
                background: #fff;
                border-bottom: 1px solid #f0f0f0;
            }

            .po-table tbody tr:hover {
                background: #f9fcff;
                box-shadow: 0 0 0 1px #e2efff inset;
            }

            .product-col {
                min-width: 320px;
                max-width: 540px;
                white-space: nowrap;
                overflow: hidden;
                text-overflow: ellipsis;
            }

            @media (max-width: 1366px) {
                .product-col {
                    min-width: 280px;
                    max-width: 460px;
                }
            }

            /* --- Input styling --- */
            .po-table input.form-control,
            .po-table input[type="text"],
            .po-table input[type="number"] {
                height: 34px;
                line-height: 34px;
                padding: 0 8px;
                font-size: 0.9rem;
                border: 1px solid #d9d9d9;
                border-radius: 6px;
                outline: none;
                width: clamp(72px, 6vw, 120px);
                background: #fff;
                color: #333;
                transition: 0.2s ease;
            }

            .po-table input.form-control:focus {
                border-color: #5b9dff;
                box-shadow: 0 0 0 3px rgba(91, 157, 255, 0.25);
            }

            /* Role-based widths */
            .po-table input.receiveqty,
            .po-table input.receiveqty1,
            .po-table input.receiveqty2 {
                width: clamp(64px, 5vw, 100px);
            }

            .po-table input.amount,
            .po-table input.gstamount {
                width: clamp(84px, 6vw, 110px);
            }

            .po-table input.rate {
                width: clamp(70px, 5vw, 95px);
            }

            .po-table input.discount,
            .po-table input.discountamount {
                width: clamp(80px, 6vw, 110px);
            }

            .po-table input[readonly] {
                background: #fafafa;
                color: #666;
            }

            /* Prevent ugly wrapping */
            .po-table th,
            .po-table td {
                white-space: nowrap;
            }

            @media (max-width: 1280px) {
                .po-table td {
                    white-space: normal;
                }

                .po-table input {
                    white-space: nowrap;
                }
            }

            /* === Fix EAN + Remaining QTY column overlap (no markup changes) === */

            /* Ensure numeric columns get some breathing room */
            .po-table td[id^="remqty"] {
                /* Remaining QTY value cell (has id="remqty...") */
                min-width: 120px;
                text-align: center;
            }

            /* Column #3:
       - type==1  → EAN
       - type==2  → Remaining QTY
       Give it a firm width + ellipsis so it never crashes into neighbors. */
            .po-table th:nth-child(3),
            .po-table td:nth-child(3) {
                min-width: 170px;
                /* tweak to 160–200px if needed */
                max-width: 220px;
                overflow: hidden;
                text-overflow: ellipsis;
                white-space: nowrap;
                font-variant-numeric: tabular-nums;
                /* nicer digit alignment */
            }

            /* When type==1, Remaining QTY becomes the 4th column — give it room too */
            .po-table th:nth-child(4),
            .po-table td:nth-child(4) {
                min-width: 130px;
                white-space: nowrap;
                text-align: center;
            }

            /* Small guard so table-layout: fixed doesn't squeeze too hard on tight screens */
            @media (max-width: 1366px) {

                .po-table th:nth-child(3),
                .po-table td:nth-child(3) {
                    min-width: 160px;
                }

                .po-table th:nth-child(4),
                .po-table td:nth-child(4) {
                    min-width: 120px;
                }
            }


            /* --- Show Product & EAN fully, enable desktop scroll if needed --- */

            /* Always allow horizontal scrolling on laptops/desktops */
            .po-table-wrap {
                overflow-x: auto !important;
            }

            /* Let the table expand to fit long content (and still be at least full width) */
            .po-table {
                table-layout: auto !important;
                /* natural widths */
                width: max-content !important;
                /* grow to content (triggers scroll) */
                min-width: 100% !important;
                /* but never shrink below container */
            }

            /* Product column: no truncation */
            .po-table .product-col {
                white-space: nowrap !important;
                overflow: visible !important;
                text-overflow: unset !important;
                min-width: max-content;
                /* size to its content */
            }

            /* EAN column: no truncation */
            .po-table .ean-col {
                white-space: nowrap !important;
                overflow: visible !important;
                text-overflow: unset !important;
                min-width: max-content;
            }

            /* Optional: keep numeric columns tidy while allowing table to grow */
            .po-table td[id^="remqty"] {
                text-align: center;
            }
        </style>
    <div class="mx-2">
        <div class="row mx-0 my-2">
            <h2>Raise Invoice</h2>
            {{-- {{$userSplier->supplier->tdspercent}} --}}
        </div>
        @if ($errors->any())
            <div class="alert alert-danger">
                <ul class="mb-0">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif
        <!-- <form id='createPurchaseBill' method="POST" action="{{ url('/supplier-dashboard/raise-invoice') }}/{{ $purchaseOrder->id }}">
                              @csrf
                              <input type="hidden" name="purchase_order_id" value="{{ $purchaseOrder->id }}" /> -->
        <form id='createPurchaseBill' method="POST"
            action="{{ url('/supplier-dashboard/raise-invoice') }}/{{ $id }}" multiform
            enctype="multipart/form-data">
            @csrf
            <input type="hidden" name="purchase_order_id" value="{{ $id }}" />
            <input type="hidden" name="typeUnit" value="{{ $type }}" />
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
                        <input type="text" class="form-control toUpperCase" id="supinv" name="supp_inv_no" required />
                        @error('supp_inv_no')
                            <small class="text-danger">{{ $message }}</small>
                        @enderror
                    </div>

                    <div class="col-4">
                        <label class="control-label">{{ __('E-Way Bill No.') }}</label>
                        <input type="text" class="form-control toUpperCase" name="ewaybill" id="ewaybill" />
                        <p style="color: red; font-weight: 500;">
    E-Way Bill is optional (if the Total amount is below ₹1,00,000 for a single day)
</p>
                        @error('eway_bill_no')
                            <small class="text-danger">{{ $message }}</small>
                        @enderror
                    </div>

                    <div class="col-4">
                        <label class="control-label">{{ __('Vehicle No.') }}</label>
                        <input type="text" class="form-control toUpperCase" name="vehicle_no" id="vehicle_no" />
                    </div>
                </div>

                <!-- third row -->
                <div class="row mt-3">
                    <div class="col-4">
                        <label class="control-label">{{ __('E-Way Bill Upload') }}</label>
                        <input type="file" class="form-control toUpperCase" id="eway_file" name="eway_bill_upload" />
                    </div>

                    <div class="col-4">
                        <label class="control-label">{{ __('Supplier Invoice Date') }}</label>
                        <input type="date" class="form-control" required name="invoice_date" id="invoice_date"
                            max="<?php echo date('Y-m-d'); ?>" min="<?php echo date('Y-m-d', strtotime('-30 days')); ?>" />
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
                <div class="row mt-3 po-table-wrap">
                    <table class="table table-hover po-table">
                        <thead>
                            <tr id="mytable">
                                <th scope="col" class="product-col" style="min-width: 300px;">Product</th>
                                @if ($type == 1)
                                    <th scope="col" class="ean-col">EAN</th>
                                @endif
                                @if ($type == 1 || $type == 2)
                                    <th scope="col">Remaining QTY</th>
                                    <th scope="col">Quantity </th>

                                    @if ($type == 2)
                                        <th scope="col">Unit</th>
                                    @endif
                                    <th scope="col">Rate/Item (₹)</th>
                                @endif

                                @if ($type == 3)
                                    <th scope="col">Remaining QTY Box 1</th>
                                    <th scope="col">Remaining QTY Box 2</th>
                                    <th scope="col">QTY Box 1</th>
                                    <th scope="col">QTY Box 2</th>
                                    <th scope="col">Rate/Item (₹) (Box 1)</th>
                                    <th scope="col">Rate/Item (₹) (Box 2)</th>
                                @endif
                                <th scope="col">Amount (₹)</th>
                                <th scope="col">GSTSLAB</th>
                                <th scope="col">GST (₹)</th>
                                @if ($type == 1)
                                    <th scope="col">Discount Per Item</th>
                                    <th scope="col">Discount</th>
                                    <th scope="col"> Po Discount</th>
                                @endif
                            </tr>
                        </thead>
                        <tbody id="productTable">
                            <?php $productRows = 0; ?>
                            @foreach ($poTable as $p => $pots)
                                <tr id="mytable">
                                    <?php $productRows++; ?>
                                    <td scope="col" class="product-col">
                                        @if (isset($pots->product->code))
                                            {{ $pots->product->code }} - {{ $pots->product->name }}
                                        @else
                                            {{ $pots->consumable->name }}
                                        @endif

                                        @if (isset($pots->product_id))
                                            <input name="pb[{{ $productRows }}][product]" type="hidden"
                                                value="{{ $pots->product_id }}" />
                                        @else
                                            <input name="pb[{{ $productRows }}][product]" type="hidden"
                                                value="{{ $pots->consumable_id }}" />
                                        @endif
                                    </td>
                                    @if ($type == 1)
                                        <td scope="col" class="ean-col">{{ $pots->EAN }}</td>
                                    @endif
                                    @if ($type == 1 || $type == 2)
                                        <td scope="col" id="remqty{{ md5($p) }}">{{ $pots->remqty }}</td>
                                        @if ($type == 2)
                                            <td scope="col" id="{{ md5($p) }}">
                                                <input class="form-control receiveqty" value="0"
                                                    step="{{ $pots->data_type == 'float' ? '0.1' : '1' }}" min="0"
                                                    max="{{ $pots->remqty }}" name="pb[{{ $productRows }}][receiveqty]"
                                                    type="number" onchange="recalQuantity(this);"
                                                    oninput="handleTypeRestriction(this, '{{ $pots->data_type }}')" />

                                            </td>
                                            <td scope="col" id="{{ md5($p) }}">
                                                <input id="rec1{{ md5($p) }}" class="form-control receiveqty1"
                                                    value="{{ $pots->unit }}" min="0" readonly
                                                    name="pb[{{ $productRows }}][unit]" type="text"
                                                    onchange="recalQuantityCarton(this);" />
                                            </td>
                                        @else
                                            <td scope="col" id="{{ md5($p) }}"><input
                                                    class="form-control receiveqty" value="0" min="0"
                                                    max="{{ $pots->remqty }}" name="pb[{{ $productRows }}][receiveqty]"
                                                    type="number" onchange="recalQuantity(this);" /></td>
                                        @endif

                                        <td scope="col" id="rate{{ md5($p) }}"><input class="form-control"
                                                value="{{ $pots->rate }}" name="pb[{{ $productRows }}][rate]"
                                                readonly /></td>
                                    @endif

                                    @if ($type == 3)
                                        <td scope="col">{{ $pots->remqty_box1 }}</td>
                                        <td scope="col">{{ $pots->remqty_box2 }}</td>
                                        <td scope="col" id="{{ md5($p) }}"><input id="rec1{{ md5($p) }}"
                                                class="form-control receiveqty1" value="0" min="0"
                                                max="{{ $pots->remqty_box1 }}"
                                                name="pb[{{ $productRows }}][receiveqty_box_1]" type="number"
                                                onchange="recalQuantityCarton(this);" /></td>
                                        <td scope="col" id="{{ md5($p) }}"><input id="rec2{{ md5($p) }}"
                                                class="form-control receiveqty2" value="0" min="0"
                                                max="{{ $pots->remqty_box2 }}"
                                                name="pb[{{ $productRows }}][receiveqty_box_2]" type="number"
                                                onchange="recalQuantityCarton(this);" /></td>
                                        <td scope="col" id="rate1{{ md5($p) }}"><input class="form-control"
                                                value="{{ $pots->box1_rate }}" name="pb[{{ $productRows }}][box1_rate]"
                                                readonly /></td>
                                        <td scope="col" id="rate2{{ md5($p) }}"><input class="form-control"
                                                value="{{ $pots->box2_rate }}" name="pb[{{ $productRows }}][box2_rate]"
                                                readonly /></td>
                                    @endif
                                    <td scope="col" id="amount{{ md5($p) }}"><input value="0"
                                            class="form-control amount" name="pb[{{ $productRows }}][amount]"
                                            readonly /></td>
                                    <td scope="col" id="gstslab{{ md5($p) }}"><input class="form-control"
                                            value="{{ $pots->gstslab }}" name="pb[{{ $productRows }}][gstslab]"
                                            readonly /></td>
                                    <td scope="col" id="gstamount{{ md5($p) }}"><input value="0"
                                            class="form-control gstamount" name="pb[{{ $productRows }}][gstamount]"
                                            readonly /></td>
                                    @if ($type == 1)
                                        <td scope="col" id="discountperitem{{ md5($p) }}"><input
                                                class="form-control"value="{{ $pots->remqty > 0 ? number_format($pots->remaining_discount / $pots->remqty, 2) : '0.00' }}"


                                                name="pb[{{ $productRows }}][discountperitem]" readonly /></td>

                                       
                                        <td scope="col" id="discountamount{{ md5($p) }}"><input value=""
                                                class="form-control discountamount"
                                                name="pb[{{ $productRows }}][discountamount]" readonly /></td>

                                                 <td scope="col" id="discount{{ md5($p) }}"><input
                                                value="{{ $pots->remaining_discount }}" class="form-control discount"
                                                name="pb[{{ $productRows }}][discount]" readonly /></td>

                                        <td scope="col" id="remaing_discount{{ md5($p) }}"><input
                                                value="" class="form-control remaing_discount"
                                                name="pb[{{ $productRows }}][remaing_discount]" hidden /></td>
                                                 <td scope="col" id="discount_type{{ md5($p) }}"><input
                                                class="form-control" value="{{ $pots->discount_type }}"
                                                name="pb[{{ $productRows }}][discount_type]" hidden /></td>
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
                        <label class="control-label">{{ __('Total Amount') }}</label>
                        <input type="number" class="form-control" name="pbTotal" id="pbTotal" readonly />
                        <input type="text" name="type" id='invoiceTotal' value="{{ $invoiceTotal }}" hidden />
                        {{-- <input type="text" id="print"> --}}
                    </div>


                    <div class="col-4">
                        <label class="control-label">{{ __('Total TDS') }}</label>
                        <input type="number" class="form-control" name="tdsTotal" id="tdsToal" readonly />
                        <input type="number" class="form-control" name="" id="tdsPercent"
                            value="{{ $userSplier->supplier->tdspercent }}" hidden />
                        <input type="date" class="form-control" name="" id="tdsDate"
                            value="{{ $userSplier->supplier->tdsdate }}" hidden />

                        {{-- <input type="text" id="print"> --}}
                    </div>
                    @if ($type == 1)
                        <div class="col-4">
                            <label class="control-label">{{ __('Total Discount') }}</label>
                            <input type="number" class="form-control" name="totaldiscount" id="totaldiscount"
                                readonly />
                        </div>
                        <div class="col-4">
                            <label class="control-label">{{ __('Round Off') }}</label>
                            <input type="number" class="form-control" step=".1" name="roundoff" id="roundoff" onchange="recalQuantity(this);" />
                        </div>
                    @endif
                </div>
                <div class="row col-4">
                    <button type="submit" id="hidden" hidden class="btn btn-primary mt-3" disabled>Create Purchase
                        Bill</button>
                    <button type="submit" id="formSubmit" form="createPurchaseBill" class="btn btn-primary mt-3">Raise
                        Invoice</button>
                </div>

            </div>
        </form>
    </div>
@endsection

@section('footer')
    @include('partials.supplier-invoice-tds-js')
    <!-- Script start for get data from database -->
    <script type="text/javascript">
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

            if ($('#productTable tr').length > 0) {
                if (typeof calculateTotal === 'function') {
                    calculateTotal();
                } else if (typeof handleTds === 'function') {
                    handleTds($('#pbSubTotal').val() || 0);
                }
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

            if (dataType === 'int') {
                if (val.includes('.')) {
                    input.value = Math.floor(val);
                }
            }
        }
        var invoiceType = {{ $type }};

        function recalQuantity(ref) {

            let currentDate = new Date($('#invoice_date').val());
            // console.log($('#invoice_date').val(),'currentDate');





            if (!currentDate || currentDate == 'Invalid Date' || currentDate == '') {
                alert('Please select Invoice Date');
                ref.value = 0;
                return;
            }
            quantity = Number($(ref).val(), 10);
            var rqean = $(ref).parent().attr('id');
            //if(quantity>0){
            let remqty = parseFloat($('#remqty' + rqean).text()) || 0;
            var rate = $('#rate' + rqean + ' input').val();
            var gstslab = $('#gstslab' + rqean + ' input').val();

            var oldrate = rate * quantity;
            if (invoiceType == 1) {
                let discountValue = 0;
                let remainingDiscount = 0;
                let discount = parseFloat($("#discount" + rqean + " input").val()) || 0;
                let discountType = $("#discount_type" + rqean + " input").val();

                let perUnitDiscount = discount / remqty;
                perUnitDiscount     = Math.round(perUnitDiscount * 100) / 100;
                discountValue = Math.round(perUnitDiscount * quantity * 100) / 100;
                remainingDiscount = discountValue;

                let finalAmount = oldrate - discountValue;

                $("#amount" + rqean + " input").val(oldrate.toFixed(2));
                $("#discountamount" + rqean + " input").val(discountValue.toFixed(2));
                $("#remaing_discount" + rqean + " input").val(remainingDiscount.toFixed(2));

                // GST on discounted amount
                let gstamount = (finalAmount * gstslab) / 100;
                $('#gstamount' + rqean + ' input').val(gstamount.toFixed(2));


            } else {
                $("#amount" + rqean + " input").val(oldrate);
                var gstamount = (oldrate * gstslab) / 100;
                $('#gstamount' + rqean + ' input').val(gstamount.toFixed(2));
            }

            $("#myInput").select().focus();

            calculateTotal();

            //}

            // else{
            //   alert("Quantity can not be less than 1.");
            //   $("#myInput").select().focus();
            // }
        };

        function deleteRow(ref) {
            $(ref).parents("tr").remove();
            $("#myInput").select().focus();
            calculateTotal();
        };

        function calculateTotal() {
            if (invoiceType == 1) {
                var totQty = 0;
                var subTot = 0;
                var gstamount = 0;
                var totalDiscount = 0;

                $('.receiveqty').each(function() {
                    totQty += parseInt($(this).val(), 10) || 0;
                });

                $('.amount').each(function(i, el) {
                    let rowAmount = parseFloat($(el).val()) || 0;
                    let rowDiscount = parseFloat($('.remaing_discount').eq(i).val()) || 0;
                    subTot += (rowAmount - rowDiscount);
                });

                $('.gstamount').each(function(i, el) {
                    gstamount += parseFloat($(el).val()) || 0;
                });

                $('.remaing_discount').each(function(i, el) {
                    totalDiscount += parseFloat($(el).val()) || 0;
                });

                var pbTotal = subTot + gstamount;
                var roundOff = parseFloat($('#roundoff').val()) || 0;

                pbTotal += roundOff;
                $('#pbSubTotal').val(subTot.toFixed(2));
                $('#pbQty').val(totQty);
                $('#pbGST').val(gstamount.toFixed(2));
                $('#pbTotal').val(pbTotal.toFixed(2));
                $('#totaldiscount').val(totalDiscount.toFixed(2));
            } else {

                var arrTotQty = [];
                var arrSubTot = [];
                var arrgstamount = [];
                var totQty = 0;
                var subTot = 0;
                var gstamount = 0;

                arrTotQty = $('.receiveqty');
                arrSubTot = $('.amount');
                arrgstamount = $('.gstamount');

                $.each(arrTotQty, function(index, value) {
                    // totQty += parseInt(arrTotQty[index].value,10);
                    const val = parseFloat(arrTotQty[index].value);
                    if (!isNaN(val)) {
                        totQty += val;

                    }
                });
                totQty = Number(totQty.toFixed(10)).toString();


                $.each(arrSubTot, function(index, value) {
                    subTot += parseFloat(arrSubTot[index].value, 10);
                });

                $.each(arrgstamount, function(index, value) {
                    gstamount += parseFloat(arrgstamount[index].value, 10);
                });

                //var  freightRate = parseFloat($('#freight').val(),10);
                var pbTotal = subTot + gstamount;
                gstamount = gstamount.toFixed(2);
                subTot = subTot.toFixed(2);
                pbTotal = parseFloat(pbTotal).toFixed(2);
                $('#pbSubTotal').val(subTot);
                $('#pbQty').val(totQty);
                $('#pbGST').val(gstamount);
                $('#pbTotal').val(pbTotal);


                var potamount = $('#pbTotal').val();
                console.log(potamount);
                console.log(document.getElementById('invoiceTotal').value);


                let totalAmomunt = Number(document.getElementById('invoiceTotal').value) + Number(potamount);
                // document.getElementById('print').value = totalAmomunt;
                if (totalAmomunt > 100000) {
                    $('#ewaybill').attr('required', true);
                    $('#eway_file').attr('required', true);
                } else {
                    $('#ewaybill').removeAttr('required', false);
                }
            }

            handleTds($('#pbSubTotal').val() || 0);
        }

        function recalQuantityCarton(ref) {
            quantity = parseInt($(ref).val(), 10);
            var rqean = $(ref).parent().attr('id');
            quantity1 = parseInt($('#rec1' + rqean).val(), 10);
            quantity2 = parseInt($('#rec2' + rqean).val(), 10);
            //if(quantity>0){
            var remqty = $('#remqty' + rqean + ' input').val(); //MYcode
            var rate1 = $('#rate1' + rqean + ' input').val();
            var rate2 = $('#rate2' + rqean + ' input').val();
            var gstslab = $('#gstslab' + rqean + ' input').val();
            var oldrate1 = rate1 * quantity1;
            var oldrate2 = rate2 * quantity2;
            var totoldrate = oldrate1 + oldrate2;
            $("#amount" + rqean + " input").val(totoldrate);
            var gstamount = (totoldrate * gstslab) / 100;
            $('#gstamount' + rqean + ' input').val(gstamount.toFixed(2));
            $("#myInput").select().focus();

            calculateTotalCarton();
            //}

            // else{
            //   alert("Quantity can not be less than 1.");
            //   $("#myInput").select().focus();
            // }
        };

        function calculateTotalCarton() {
            var arrTotQty1 = [];
            var arrTotQty2 = [];
            var arrSubTot = [];
            var arrgstamount = [];
            var totQty = 0;
            var subTot = 0;
            var gstamount = 0;

            arrTotQty1 = $('.receiveqty1');
            arrTotQty2 = $('.receiveqty2');
            arrSubTot = $('.amount');
            arrgstamount = $('.gstamount');

            $.each(arrTotQty1, function(index, value) {
                totQty += parseInt(arrTotQty1[index].value, 10);
            });

            $.each(arrTotQty2, function(index, value) {
                totQty += parseInt(arrTotQty2[index].value, 10);
            });

            $.each(arrSubTot, function(index, value) {
                subTot += parseFloat(arrSubTot[index].value, 10);
            });

            $.each(arrgstamount, function(index, value) {
                gstamount += parseFloat(arrgstamount[index].value, 10);
            });

            //var  freightRate = parseFloat($('#freight').val(),10);
            var pbTotal = subTot + gstamount;
            gstamount = gstamount.toFixed(2);
            subTot = subTot.toFixed(2);
            pbTotal = parseFloat(pbTotal).toFixed(2);
            $('#pbSubTotal').val(subTot);
            $('#pbQty').val(totQty);
            $('#pbGST').val(gstamount);
            $('#pbTotal').val(pbTotal);

            var potamount = $('#pbTotal').val();
            if (potamount > 100000) {
                $('#ewaybill').attr('required', true);
            } else {
                $('#ewaybill').removeAttr('required', false);
            }
            handleTds($('#pbSubTotal').val() || 0);
        }

        $('#createPurchaseBill').on('submit', function(e) {
            calculateTotal();
            var pbTotal = parseFloat($('#pbTotal').val()) || 0;
            var needsEway = pbTotal > 100000;
            var hasEway = $.trim($('#ewaybill').val() || '') !== '';
            var hasEwayFile = ($('#eway_file').get(0) && $('#eway_file').get(0).files && $('#eway_file').get(0).files.length > 0);
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

            if (needsEway && (!hasEway || !hasEwayFile)) {
                alert("E-Way Bill No. and upload are required when total exceeds 100000.");
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
                                $block += '<td class="product-col"><select type="text" class="selectpicker" data-live-search="true" name="pb[' +
                                    productRows + '][product]" readonly><option value="' + poProduct.id + '">' + poProduct
                                    .name + '</option></td>';
                                $block += '<td class="ean-col"><input type="text" class="form-control" name="pb[' + productRows +
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
