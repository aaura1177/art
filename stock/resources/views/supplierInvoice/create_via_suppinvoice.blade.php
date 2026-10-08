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
            <h2>Supplier Invoice Approval</h2>

        </div>

        <form id='createPurchaseBill' method="POST" action="{{ url('/supplierInvoice/approve/' . $supplierInvoice->id) }}">
            @csrf

            <!-- Form Starts -->
            <div class="form-group">

                <!-- first row -->
                <div class="row mt-3">
                    <div class="col-4">
                        <label class="control-label">{{ __('PO No.') }}</label>
                        <input type="text" class="form-control" name="po_no" id="po_no"
                            value="{{ $purchaseOrder->pono }}" readonly />

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
                        <input type="text" value="" class="form-control" name="Supplier_ref" id="Supplier_ref"
                            readonly />
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
                        <textarea class="form-control" name="remark" id="remarks" readonly>{{ $purchaseOrder->remarks }}</textarea>
                    </div>
                    <div class="col-4">
                        <label class="control-label">Get In Serial No.</label>
                        <input type="text" class="form-control" name="getinserailno" id="getinserailno" required/>
                    </div>

                </div>

                <!-- second row -->
                <div class="row mt-3">
                    <div class="col-4">
                        <label class="control-label">{{ __('Supplier Invoice No.') }}</label>
                        <input type="text"
                            value="{{ $supplierInvoice ? $supplierInvoice->supplier_invoice_number : null }}"
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

                <div class="row mt-3 po-table-wrap">
                    <table class="table table-hover po-table">
                        <thead>

                            <tr id="mytable">
                                <th scope="col" class="product-col" style="min-width: 300px;">Product</th>
                                @if ($supplierInvoice->purchase_order_type == 'Furniture')
                                    <th scope="col" class="ean-col">EAN</th>
                                @endif
                                <th scope="col">Receive QTY</th>
                                @if ($typeUnit == 2)
                                    <th scope="col">Unit</th>
                                @endif
                                <th scope="col">Rate/Item (₹)</th>
                                <th scope="col">Amount (₹)</th>
                                <th scope="col">GSTSLAB</th>
                                <th scope="col">GST (₹)</th>
                                @if ($typeUnit == 1)
                                    <th scope="col">Discount Type</th>
                                    <th scope="col">Discount (₹)</th>
                                @endif
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
                                        $block .=
                                            '<td><input type="hidden" class="form-control"
                                                                                                                                                                name="pb[' .
                                            $productRows .
                                            '][EAN]" readonly
                                                                                                                                                                value="' .
                                            $poTable->potable->product->EAN .
                                            '" />' .
                                            $poTable->potable->product->EAN .
                                            '</td>';
                                        $appqty = $poTable->quantity;
                                    
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
                                                                                                                                                                readonly /><a href="javascript:;" onclick="reduceQuantityStep(this);"><i
                                                                                                                                                                    class="fa fa-minus"></i> </a> <a href="javascript:;" onclick="increaseQuantityStep(this);"><i
                                                                                                                                                                    class="fa fa-plus"></i></a></td>';
                                        if (isset($poTable['potable']->rate)) {
                                            $rate = $poTable['potable']->rate;
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
                                            '"><input type="number" class="form-control rate"
                                                                                                                                                                name="pb[' .
                                            $productRows .
                                            '][rate]" readonly
                                                                                                                                                                value="' .
                                            $rate .
                                            '" /></td>';
                                        $block .=
                                            '<td id=amount' .
                                            $ean .
                                            '><input type="number" class="form-control amount" name="pb[' .
                                            $productRows .
                                            '][amount]" readonly value="' .
                                            ($poTable->amount ? $poTable->amount : 0) .
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
                                            '"><input type="hidden"
                                                                                                                                                                class="form-control gstamount" name="pb[' .
                                            $productRows .
                                            '][gstamount]"
                                                                                                                                                                readonly value="' .
                                            $poTable['gstamount'] .
                                            '" />' .
                                            $poTable['gstamount'] .
                                            '</td>';
                                    
                                        $block .=
                                            '<td id="discount_type' .
                                            $ean .
                                            '"><input type="text"
                                                                                                                                                                                                                                                                                                                class="form-control discount_type" name="pb[' .
                                            $productRows .
                                            '][discount_type]" readonly
                                                                                                                                                                                                                                                                                                                value="' .
                                            $poTable->discount_type .
                                            '" />
                                                                                                                                                                                                                                                             </td>';
                                        $block .=
                                            '<td id="discount' .
                                            $ean .
                                            '"><input type="text"
                                                                                                                                                                                                                                                                                                                class="form-control discount" name="pb[' .
                                            $productRows .
                                            '][discount]"
                                                                                                                                                                                                                                                                                                                readonly value="' .
                                            $poTable->discount .
                                            '" />
                                                                                                            
                                                                                                                                                        
                                                                                                                                                                                                                                                             </td>';
                                        $block .=
                                            '<td id="rem_discount' .
                                            $ean .
                                            '"><input type="hidden"
                                                                                                                                                                                                                                                                                                                class="form-control rem_discount" name="pb[' .
                                            $productRows .
                                            '][rem_discount]"
                                                                                                                                                                                                                                                                                                                readonly value="' .
                                            $poTable->discount .
                                            '" />
                                                                                                            
                                                                                                                                                        
                                                                                                                                                                                                                                                             </td>';
                                    
                                        $block .=
                                            '<td id="after_discount' .
                                            $ean .
                                            '"><input type="hidden"
                                                                                                                                                                                                                                                                                                                class="form-control after_discount" name="pb[' .
                                            $productRows .
                                            '][after_discount]"
                                                                                                                                                                                                                                                                                                                readonly value="' .
                                            $poTable->amount .
                                            '" />
                                                                                                            
                                                                                                                                                        
                                                                                                                                                                                                                                                             </td>';
                                    
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
                                    }
                                    ?>
                                @endforeach
                            @endif
                        @else
                            @if (isset($supplierInvoiceProduct))
                                @foreach ($supplierInvoiceProduct as $key => $poTable)
                                    <?php
                                    if (isset($poTable['potable']->rate)) {
                                        $productRows += 1;
                                        $ean = $poTable->potable->consumable->id;
                                        $block .= '<tr>';
                                        $block .=
                                            '<td><select type="text" class="selectpicker" data-live-search="true"
                                                                                                                                                                name="pb[' .
                                            $productRows .
                                            '][product]" readonly>
                                                                                                                                                                <option value="' .
                                            $poTable->potable->consumable->id .
                                            '">' .
                                            $poTable->potable->consumable->name .
                                            '
                                                                                                                                                                </option>
                                                                                                                                                        </td>';
                                    
                                        $sip = App\UnitType::where('name', $poTable->unit)->first();
                                    
                                        if ($sip && $sip->data_type == 'int') {
                                            $appqty = $poTable->quantity;
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
                                                                                                                        readonly /><a href="javascript:;" onclick="reduceQuantityStep(this);"><i
                                                                                                                            class="fa fa-minus"></i></a> <a href="javascript:;" onclick="increaseQuantityStep(this);"><i
                                                                                                                            class="fa fa-plus"></i></a></td>';
                                        } else {
                                            $appqty = $poTable->quantity;
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
                                                                                                                        readonly /><a href="javascript:;" onclick="reduceQuantity(this);"><i
                                                                                                                            class="fa fa-minus"></i></a> <a href="javascript:;" onclick="increaseQuantity(this);"><i
                                                                                                                            class="fa fa-plus"></i></a></td>';
                                        }
                                    
                                        if (isset($poTable['potable']->rate)) {
                                            $rate = $poTable['potable']->rate;
                                        } else {
                                            $rate = '';
                                        }
                                        if (isset($poTable['potable']->gstslab)) {
                                            $gstslab = $poTable['potable']->gstslab;
                                        } else {
                                            $gstslab = '';
                                        }
                                    
                                        $block .=
                                            '<td id="unit' .
                                            $ean .
                                            '"><input type="text" class="form-control unit" 
                                                                                                                            name="pb[' .
                                            $productRows .
                                            '][unit]" readonly 
                                                                                                                            value="' .
                                            $poTable->unit .
                                            '" /></td>';
                                    
                                        $block .=
                                            '<td id="rate' .
                                            $ean .
                                            '"><input type="number" class="form-control rate"
                                                                                                                                                                name="pb[' .
                                            $productRows .
                                            '][rate]" readonly
                                                                                                                                                                value="' .
                                            $rate .
                                            '" /></td>';
                                        $block .=
                                            '<td id=amount' .
                                            $ean .
                                            '><input type="number" class="form-control amount" name="pb[' .
                                            $productRows .
                                            '][amount]" readonly value="' .
                                            ($poTable->amount ? $poTable->amount : 0) .
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
                                            '"><input type="hidden"
                                                                                                                                                                class="form-control gstamount" name="pb[' .
                                            $productRows .
                                            '][gstamount]"
                                                                                                                                                                readonly value="' .
                                            $poTable['gstamount'] .
                                            '" />' .
                                            $poTable['gstamount'] .
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
                        <input type="number" class="form-control" onchange="calculateTotal();" name="freight"
                            id="freight" value='0' step="any" min='0' required />
                    </div>
                    <div class="col-4">
                        <label class="control-label">{{ __('Total Amount') }}</label>
                        <input type="number" class="form-control" name="pbTotal" id="pbTotal" readonly />
                    </div>
                    <div class="col-4">
                        <label class="control-label">{{ __('Total TDS') }}</label>
                        <input type="number" class="form-control" value="" name="tdsTotal" id="tdsTotal"
                            readonly />
                        <input type="number" class="form-control" value="{{ $supplier->tdspercent }}"
                            name="tdspercent" id="tdsTotal" hidden />
                    </div>

                    @if ($typeUnit == 1)
                        <div class="col-4">
                            <label class="control-label">{{ __('Total Discount') }}</label>
                            <input type="number" class="form-control" name="totaldiscount" id="totaldiscount"
                                readonly />
                        </div>
                        <div class="col-4">
                            <label class="control-label">{{ __('Round Off') }}</label>
                            <input type="number" class="form-control" name="roundoff" id="roundoff"
                                value="{{ $supplierInvoice->roundoff }}" readonly />
                        </div>
                    @endif
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
    <script type="text/javascript">
        $(document).ready(function() {


        });

        var purchaseOrder = [];
        var productCheck = [];
        var pbProduct = [];
        var pbPoTable = [];
        var productRows = 0;
        changeDetails();
        calculateTotal();

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

        var invoiceType = {{ $typeUnit }};

        function reduceQuantity(ref) {
            if (invoiceType == 1) {
                let quantity = parseInt($(ref).siblings('input').val(), 10);
                let rqean = $(ref).parent().attr('id');
                let max = parseInt($(ref).siblings('input').attr('max'), 10);

                if (quantity > 1) {
                    let newQuantity = quantity - 1;
                    $(ref).siblings('input').val(newQuantity);

                    let rate = parseFloat($('#rate' + rqean + ' input').val()) || 0;
                    let discount = parseFloat($('#discount' + rqean + ' input').val()) || 0;
                    let discountType = $('#discount_type' + rqean + ' input').val();
                    let remqty = max; // total qty allowed from backend
                    let gstslab = parseFloat($('#gstslab' + rqean + ' input').val()) || 0;

                    let oldrate = rate * newQuantity;
                    let discountValue = 0;

                    console.log(rate);


                    let perUnitDiscount = discount / remqty;
                    discountValue = perUnitDiscount * newQuantity;


                    let afterDiscount = oldrate - discountValue;
                    let gstamount = (oldrate * gstslab) / 100;

                    $("#amount" + rqean + " input").val(oldrate.toFixed(2));
                    $("#after_discount" + rqean + " input").val(afterDiscount.toFixed(2));
                    $('#gstamount' + rqean + ' input').val(gstamount.toFixed(2));
                    $('#rem_discount' + rqean + ' input').val(discountValue.toFixed(2));

                    calculateTotal();
                } else {
                    alert("Quantity cannot be less than 1.");
                }
            } else {
                quantity = Number($(ref).siblings('input').val(), 10);
                // console.log(quantity , 'this is quantity ');

                var rqean = $(ref).parent().attr('id');
                if (quantity > 1) {
                    newQuantity = quantity - 0.1;
                    console.log(newQuantity);
                    // console.log(quantity);


                    $(ref).siblings('input').val(newQuantity.toFixed(2));
                    var rate = $('#rate' + rqean + ' input').val();
                    var gstslab = $('#gstslab' + rqean + ' input').val();
                    var oldrate = rate * newQuantity;

                    // $("#remainingqty"+rqean+" input").val(remainingqty);
                    $("#amount" + rqean + " input").val(oldrate.toFixed(2));
                    var gstamount = (oldrate * gstslab) / 100;
                    $('#gstamount' + rqean + ' input').val(gstamount.toFixed(2));
                    $("#myInput").select().focus();
                    calculateTotal();
                } else {
                    alert("Quantity can not be less than 1.");
                    $("#myInput").select().focus();
                }
            }

            handleTds()
        }


        function increaseQuantity(ref) {
            if (invoiceType == 1) {
                let quantity = parseInt($(ref).siblings('input').val(), 10);
                let max = parseInt($(ref).siblings('input').attr('max'), 10);
                let rqean = $(ref).parent().attr('id');

                if (quantity < max) {
                    let newQuantity = quantity + 1;
                    $(ref).siblings('input').val(newQuantity);

                    let rate = parseFloat($('#rate' + rqean + ' input').val()) || 0;
                    let discount = parseFloat($('#discount' + rqean + ' input').val()) || 0;
                    let discountType = $('#discount_type' + rqean + ' input').val();
                    let gstslab = parseFloat($('#gstslab' + rqean + ' input').val()) || 0;

                    let oldrate = rate * newQuantity;
                    let discountValue = 0;

                    console.log(rate);

                    let perUnitDiscount = discount / max;
                    discountValue = perUnitDiscount * newQuantity;


                    let afterDiscount = oldrate - discountValue;
                    let gstamount = (oldrate * gstslab) / 100;

                    $("#amount" + rqean + " input").val(oldrate.toFixed(2));
                    $("#after_discount" + rqean + " input").val(afterDiscount.toFixed(2));
                    $('#gstamount' + rqean + ' input').val(gstamount.toFixed(2));
                    $('#rem_discount' + rqean + ' input').val(discountValue.toFixed(2));

                    calculateTotal();
                }
            } else {
                quantity = Number($(ref).siblings('input').val(), 10);
                max = Number($(ref).siblings('input').attr('max'), 10);
                var rqean = $(ref).parent().attr('id');
                if (quantity > 1 && max > quantity) {
                    newQuantity = quantity + 0.1;
                    $(ref).siblings('input').val(newQuantity.toFixed(2));
                    var rate = $('#rate' + rqean + ' input').val();
                    var gstslab = $('#gstslab' + rqean + ' input').val();
                    var oldrate = rate * newQuantity;

                    $("#amount" + rqean + " input").val(oldrate.toFixed(2));
                    var gstamount = (oldrate * gstslab) / 100;
                    $('#gstamount' + rqean + ' input').val(gstamount.toFixed(2));
                    $("#myInput").select().focus();
                    calculateTotal();
                } else {
                    $("#myInput").select().focus();
                }
            }

            handleTds()
        }




        function reduceQuantityStep(ref) {

            quantity = Number($(ref).siblings('input').val(), 10);
            // console.log(quantity , 'this is quantity ');

            var rqean = $(ref).parent().attr('id');
            if (quantity > 1) {
                newQuantity = quantity - 1;
                console.log(newQuantity);
                // console.log(quantity);


                $(ref).siblings('input').val(newQuantity.toFixed(2));
                var rate = $('#rate' + rqean + ' input').val();
                var gstslab = $('#gstslab' + rqean + ' input').val();
                var oldrate = rate * newQuantity;

                // $("#remainingqty"+rqean+" input").val(remainingqty);
                $("#amount" + rqean + " input").val(oldrate.toFixed(2));
                var gstamount = (oldrate * gstslab) / 100;
                $('#gstamount' + rqean + ' input').val(gstamount.toFixed(2));
                $("#myInput").select().focus();
                calculateTotal();
            } else {
                alert("Quantity can not be less than 1.");
                $("#myInput").select().focus();
            }
            handleTds()
        }


        function increaseQuantityStep(ref) {

            quantity = Number($(ref).siblings('input').val(), 10);
            max = Number($(ref).siblings('input').attr('max'), 10);
            var rqean = $(ref).parent().attr('id');
            if (quantity > 1 && max > quantity) {
                newQuantity = quantity + 1;
                $(ref).siblings('input').val(newQuantity.toFixed(2));
                var rate = $('#rate' + rqean + ' input').val();
                var gstslab = $('#gstslab' + rqean + ' input').val();
                var oldrate = rate * newQuantity;

                // $("#remainingqty"+rqean+" input").val(remainingqty);
                $("#amount" + rqean + " input").val(oldrate.toFixed(2));
                var gstamount = (oldrate * gstslab) / 100;
                $('#gstamount' + rqean + ' input').val(gstamount.toFixed(2));
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
            let invoiceType = {{ $typeUnit }}; // 1 = Furniture, 2 = Consumable

            let totQty = 0;
            let subTot = 0;
            let gstAmount = 0;
            let totalDiscount = 0;

            $('.receiveqty').each(function(index) {
                let $row = $(this).closest('tr');
                let qty = parseFloat($(this).val()) || 0;
                totQty += qty;

                let rate = parseFloat($row.find('.rate').val()) || 0;
                let gstslab = parseFloat($row.find('.gstslab').val()) || 0;
                let discountVal = parseFloat($row.find('.discount').val()) || 0;
                let discountType = $row.find('.discount_type').val() || 'Amount';

                let grossAmount = rate * qty;
                let discountAmount = 0;

                let maxQty = parseFloat($(this).attr('max'));
                if (isNaN(maxQty) || maxQty <= 0) {
                    maxQty = qty > 0 ? qty : 1;
                }
                discountAmount = maxQty > 0 ? (discountVal / maxQty) * qty : 0;
                if (!isFinite(discountAmount)) {
                    discountAmount = 0;
                }

                let afterDiscount = grossAmount - discountAmount;

                // ✅ GST should be on grossAmount (without discount)
                let gstAmt = (afterDiscount * gstslab) / 100;

                // Update row hidden inputs
                $row.find('.amount').val(grossAmount.toFixed(2));
                $row.find('.after_discount').val(afterDiscount.toFixed(2));
                $row.find('.gstamount').val(gstAmt.toFixed(2));
                $row.find('.rem_discount').val(discountAmount.toFixed(2));

                // Update totals
                subTot += afterDiscount; // subtotal after discount
                gstAmount += gstAmt; // gst on gross (no discount)
                totalDiscount += discountAmount;
            });

            let freight = parseFloat($('#freight').val()) || 0;
            let totalAmount = subTot + gstAmount + freight;

            let roundOff = parseFloat($('#roundoff').val()) || 0;

            totalAmount += roundOff;

            // Update totals in UI
            $('#pbQty').val(totQty);
            $('#pbSubTotal').val(subTot.toFixed(2));
            $('#pbGST').val(gstAmount.toFixed(2));
            $('#totaldiscount').val(totalDiscount.toFixed(2));
            $('#pbTotal').val(totalAmount.toFixed(2));

            // TDS calculation
            handleTds();

            // Ewaybill requirement
            if (totalAmount > 100000) {
                $('#ewaybill').attr('required', true);
            } else {
                $('#ewaybill').removeAttr('required');
            }
        }


        // Call on page load
        $(document).ready(function() {
            // ensure default values exist
            $('.receiveqty, .rate, .discount, .gstslab').each(function() {
                if ($(this).val() === '') $(this).val(0);
            });

            calculateTotal();
        });


        $('#createPurchaseBill').on('submit', function() {
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
            // console.log(tdsTotal);

            document.querySelector('input[name="tdsTotal"]').value = tdsTotal.toFixed(2);


        }

        window.onload = function() {
            handleTds(document.querySelector('input[name="pbSubTotal"]'));
        }
    </script>
    <!-- Script end -->
@endsection
