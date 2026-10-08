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

        <form id="createPurchaseBill" method="POST" action="{{ url('/supplierInvoice/approve/multi/' . $supplierInvoice->id) }}">
            @csrf
            <input type="hidden" name="mulitple_po_purchaseBill" value="{{ $supplierInvoice->mulitple_po }}">

            <div class="form-group">
                <!-- first row -->
                <div class="row mt-3">
                    <div class="col-4">
                        <label class="control-label">{{ __('PO No.') }}</label>
                        <input type="text" class="form-control" readonly value="{{ $poNumbers->implode(', ') }}" />
                    </div>
                    <div class="col-4">
                        <label class="control-label">{{ __('Supplier Name') }}</label>
                        <input type="text" value="{{ $supplier->name }}" class="form-control" name="supplierName" id="supplierName" readonly />
                    </div>
                    <div class="col-4">
                        <label class="control-label">{{ __('Delivery Date') }}</label>
                        <input type="text" value="" class="form-control" name="del_date" id="del_date" readonly />
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
                        <textarea class="form-control" name="remark" id="remarks" readonly>{{ $purchaseOrder->remarks }}</textarea>
                    </div>
                     <div class="col-4">
                        <label class="control-label">Get In Serial No.</label>
                        <input type="text" class="form-control" name="getinserailno" id="getinserailno" required/>
                    </div>
                </div>

                <!-- invoice meta -->
                <div class="row mt-3">
                    <div class="col-4">
                        <label class="control-label">{{ __('Supplier Invoice No.') }}</label>
                        <input type="text" value="{{ $supplierInvoice ? $supplierInvoice->supplier_invoice_number : null }}" class="form-control toUpperCase" id="supinv" name="supp_inv_no" required />
                    </div>
                    <div class="col-4">
                        <?php $newDate = date('Y-m-d', strtotime($supplierInvoice->created_at)); ?>
                        <label class="control-label">{{ __('Supplier Invoice Date.') }}</label>
                        <input type="date" value="<?php echo $newDate; ?>" class="form-control" name="supp_inv_date" required />
                    </div>
                    <div class="col-4">
                        <label class="control-label">{{ __('E-Way Bill No.') }}</label>
                        <input type="text" value="{{ $supplierInvoice ? $supplierInvoice->eway_bill_no : null }}" class="form-control toUpperCase" name="ewaybill" id="ewaybill" />
                    </div>
                </div>

                <!-- product table -->
                <div class="row mt-3 po-table-wrap">
                    <table class="table table-hover po-table">
                        <thead>
                            <tr id="mytable">
                                <th scope="col">PO.NO</th>
                                <th scope="col" class="product-col" style="min-width: 300px;">Product</th>
                                @if ($supplierInvoice->purchase_order_type == 'Furniture')
                                    <th scope="col" class="ean-col">EAN</th>
                                @endif
                                <th scope="col">Receive QTY</th>
                                <th scope="col">Rate/Item (₹)</th>
                                <th scope="col">Amount (₹)</th>
                                <th scope="col">GSTSLAB</th>
                                <th scope="col">GST (₹)</th>
                                @if ($supplierInvoice->purchase_order_type == 'Furniture')
                                    {{-- <th scope="col">Discount Type</th> --}}
                                    <th scope="col">Discount(₹)</th>
                                    <th scope="col">Location</th>
                                    <th></th>
                                @else
                                    <th scope="col">Location</th>
                                    <th></th>
                                @endif
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
                                    $rate1 = App\poTable::where('poid', $poTable->purchase_order_id)
                                        ->where('product_id', $poTable->product_id)->first();
                                    $rateValue = $rate1 ? $rate1->rate : ($poTable['potable']->rate ?? 0);

                                    if (isset($poTable['potable']->rate)) {
                                        $productRows += 1;
                                        $ean = $poTable->id;

                                        $block .= '<tr>';

                                        $block .= '<td>' . $poTable->mulitple_po . '</td>';

                                        $block .= '<td><select type="text" class="selectpicker" data-live-search="true" name="pb[' . $productRows . '][product]" readonly>
                                                    <option value="' . $poTable->potable->product->id . '">' . $poTable->potable->product->name . '</option>
                                                   </select></td>';

                                        $block .= '<td>
                                            <input type="hidden" class="form-control" name="pb[' . $productRows . '][poid]" readonly value="' . $poTable->purchase_order_id . '" />
                                            <input type="hidden" class="form-control" name="pb[' . $productRows . '][mulitple_po]" readonly value="' . $poTable->mulitple_po . '" />
                                            <input type="hidden" class="form-control" name="pb[' . $productRows . '][EAN]" readonly value="' . $poTable->potable->product->EAN . '" />' . $poTable->potable->product->EAN . '
                                        </td>';

                                        $appqty = $poTable->quantity;
                                        $block .= '<td id="' . $ean . '">
                                            <input type="number" min="1" class="form-control receiveqty" data-len="' . $productRows . '" name="pb[' . $productRows . '][receiveqty]" max="' . $appqty . '" value="' . $appqty . '" readonly />
                                            <a href="javascript:;" onclick="reduceQuantity(this);"><i class="fa fa-minus"></i></a>
                                            <a href="javascript:;" onclick="increaseQuantity(this);"><i class="fa fa-plus"></i></a>
                                        </td>';

                                        $block .= '<td id="rate' . $ean . '">
                                            <input type="number" class="form-control rate" name="pb[' . $productRows . '][rate]" readonly value="' . $rateValue . '" />
                                        </td>';

                                        $block .= '<td id="amount' . $ean . '">
                                            <input type="number" class="form-control amount" name="pb[' . $productRows . '][amount]" readonly value="' . ($poTable->amount ? $poTable->amount : 0) . '" />
                                        </td>';

                                        $gstslab = isset($poTable['potable']->gstslab) ? $poTable['potable']->gstslab : '';
                                        $block .= '<td id="gstslab' . $ean . '">
                                            <input type="number" class="form-control gstslab" name="pb[' . $productRows . '][gstslab]" readonly value="' . $gstslab . '" />
                                        </td>';

                                        // GST amount (hidden input + visible span we update live)
                                        $block .= '<td id="gstamount' . $ean . '">
                                            <input type="hidden" class="form-control gstamount" name="pb[' . $productRows . '][gstamount]" readonly value="' . $poTable['gstamount'] . '" />
                                            <span class="gstamount-display">' . $poTable['gstamount'] . '</span>
                                        </td>';

                                       

                                        $block .= '<td id="discount' . $ean . '">
                                            <input type="text" class="form-control discount" name="pb[' . $productRows . '][discount]" readonly value="' . $poTable->discount . '" />
                                        </td>';

                                        $block .= '<td id="rem_discount' . $ean . '">
                                            <input type="hidden" class="form-control rem_discount" name="pb[' . $productRows . '][rem_discount]" readonly value="' . $poTable->discount . '" />
                                        </td>';

                                          $block .= '<td id="location' . $ean . '">
                                            <input type="text" class="form-control location" name="pb[' . $productRows . '][location]" value="" />
                                        </td>';
                                         $block .= '<td id="discount_type' . $ean . '">
                                            <input type="text" class="form-control discount_type" name="pb[' . $productRows . '][discount_type]" hidden value="' . $poTable->discount_type . '" />
                                        </td>';
                                        
                                        $block .= '<td id="after_discount' . $ean . '">
                                            <input type="text" class="form-control after_discount" name="pb[' . $productRows . '][after_discount]" hidden value="' . $poTable->amount . '" />
                                        </td>';

                                      

                                        $block .= '<td><button type="button" class="close" onclick="deleteRow(this);" data-bs-dismiss="alert" aria-label="Close">
                                            <span aria-hidden="true">&times;</span></button></td>';

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

                                        $block .= '<td><select type="text" class="selectpicker" data-live-search="true" name="pb[' . $productRows . '][product]" readonly>
                                                    <option value="' . $poTable->potable->consumable->id . '">' . $poTable->potable->consumable->name . '</option>
                                                   </select></td>';

                                        $appqty = $poTable->quantity;
                                        $block .= '<td id="' . $ean . '">
                                            <input type="number" min="1" class="form-control receiveqty" data-len="' . $productRows . '" name="pb[' . $productRows . '][receiveqty]" max="' . $appqty . '" value="' . $appqty . '" readonly />
                                            <a href="javascript:;" onclick="reduceQuantity(this);"><i class="fa fa-minus"></i></a>
                                            <a href="javascript:;" onclick="increaseQuantity(this);"><i class="fa fa-plus"></i></a>
                                        </td>';

                                        $rate = $poTable['potable']->rate ?? 0;
                                        $block .= '<td id="rate' . $ean . '">
                                            <input type="number" class="form-control rate" name="pb[' . $productRows . '][rate]" readonly value="' . $rate . '" />
                                        </td>';

                                        $block .= '<td id="amount' . $ean . '">
                                            <input type="number" class="form-control amount" name="pb[' . $productRows . '][amount]" readonly value="' . ($poTable->amount ? $poTable->amount : 0) . '" />
                                        </td>';

                                        $gstslab = $poTable['potable']->gstslab ?? '';
                                        $block .= '<td id="gstslab' . $ean . '">
                                            <input type="number" class="form-control gstslab" name="pb[' . $productRows . '][gstslab]" readonly value="' . $gstslab . '" />
                                        </td>';

                                        $block .= '<td id="gstamount' . $ean . '">
                                            <input type="hidden" class="form-control gstamount" name="pb[' . $productRows . '][gstamount]" readonly value="' . $poTable['gstamount'] . '" />
                                            <span class="gstamount-display">' . $poTable['gstamount'] . '</span>
                                        </td>';

                                        $block .= '<td id="location' . $ean . '">
                                            <input type="text" class="form-control location" name="pb[' . $productRows . '][location]" value="" />
                                        </td>';

                                        $block .= '<td><button type="button" class="close" onclick="deleteRow(this);" data-bs-dismiss="alert" aria-label="Close">
                                            <span aria-hidden="true">&times;</span></button></td>';

                                        $block .= '</tr>';
                                    }
                                    ?>
                                @endforeach
                            @endif
                        @endif

                        <tbody id="productTable">
                            {!! $block !!}
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
                        <input type="number" class="form-control" name="pbSubTotal" id="pbSubTotal" value="0" readonly />
                    </div>
                    <div class="col-4">
                        <label class="control-label">{{ __('GST') }}</label>
                        <input type="number" class="form-control" name="pbGST" id="pbGST" value="0" readonly />
                    </div>
                </div>

                <div class="row mt-3">
                    <div class="col-4">
                        <label class="control-label">{{ __('Freight') }}</label>
                        <input type="number" class="form-control" onchange="calculateTotal();" name="freight" id="freight" value="0" step="any" min="0" required />
                    </div>
                    <div class="col-4">
                        <label class="control-label">{{ __('Total Amount') }}</label>
                        <input type="number" class="form-control" name="pbTotal" id="pbTotal" readonly />
                    </div>
                    <div class="col-4">
                        <label class="control-label">{{ __('Total TDS') }}</label>
                        <input type="number" class="form-control" value="" name="tdsTotal" id="tdsTotal" readonly />
                        <input type="number" class="form-control" value="{{ $supplier->tdspercent }}" name="tdspercent" id="tdsPercent" hidden />
                    </div>
                    <div class="col-4">
                        <label class="control-label">{{ __('Total Discount') }}</label>
                        <input type="number" class="form-control" value="{{ $supplier->totaldiscount }}" name="totaldiscount" id="totaldiscount" readonly />
                    </div>

                     <div class="col-4">
                            <label class="control-label">{{ __('Round Off') }}</label>
                            <input type="number" class="form-control" 
                                name="roundoff" id="roundoff" value="{{$supplierInvoice->roundoff  }}" readonly />
                        </div>
                </div>

                <div class="row col-4">
                    <button type="submit" id="formSubmit" form="createPurchaseBill" class="btn btn-primary mt-3">Approve</button>
                </div>

            </div>
        </form>
    </div>
@endsection

@section('footer')
<script type="text/javascript">
    // --- Small util to extract the row key (EAN/id suffix) from any cell we’re in
    function extractRowKeyFromAny(el) {
        const $td = $(el).closest('td[id]');
        if (!$td.length) return null;
        const raw = $td.attr('id') || '';
        return raw.replace(/^rate|^gstslab|^amount|^discount_type|^discount|^rem_discount|^after_discount|^location|^gstamount/, '');
    }

    // Recompute a single row: GST on PRE-discount (rate * qty)
    function recomputeRowByEAN(ean) {
        const $qtyInput = $('#' + ean + ' .receiveqty');
        if (!$qtyInput.length) return;

        const qty = parseInt($qtyInput.val(), 10) || 0;
        const max = parseInt($qtyInput.attr('max'), 10) || qty;

        const rate = parseFloat($('#rate' + ean + ' input').val()) || 0;
        const discountTotal = parseFloat($('#discount' + ean + ' input').val()) || 0;
        const gstslab = parseFloat($('#gstslab' + ean + ' input').val()) || 0;

        const baseAmount = rate * qty; // pre-discount base
        const perUnitDiscount = max ? (discountTotal / max) : 0;
        const allocatedDiscount = perUnitDiscount * qty;
        const afterDiscount = baseAmount - allocatedDiscount;
        const gstAmount = (afterDiscount * gstslab) / 100;

        $("#amount" + ean + " input").val(baseAmount.toFixed(2));
        $("#after_discount" + ean + " input").val(afterDiscount.toFixed(2));

        // Update hidden input + visible span for GST
        $('#gstamount' + ean + ' input.gstamount').val(gstAmount.toFixed(2));
        $('#gstamount' + ean + ' .gstamount-display').text(gstAmount.toFixed(2));

        $('#rem_discount' + ean + ' input').val(allocatedDiscount.toFixed(2));
    }

    function recomputeAllRows() {
        $('#productTable tr').each(function () {
            const $qtyTd = $(this).find('td').filter(function () {
                return $(this).find('.receiveqty').length;
            }).first();
            const ean = $qtyTd.attr('id');
            if (ean) recomputeRowByEAN(ean);
        });
    }

  function calculateTotal() {
    let totQty = 0, subTot = 0, gstamount = 0, discount = 0;

    $('.receiveqty').each(function () { totQty += parseInt($(this).val(), 10) || 0; });
    $('.rem_discount').each(function () { discount += parseFloat($(this).val()) || 0; });
    $('.after_discount').each(function () { subTot += parseFloat($(this).val()) || 0; });
    $('.gstamount').each(function () { gstamount += parseFloat($(this).val()) || 0; });

    const freightRate = parseFloat($('#freight').val()) || 0;
    let pbTotal = subTot + gstamount + freightRate;

    let roundOff = parseFloat($('#roundoff').val()) || 0;
    pbTotal += roundOff; 

    $('#pbSubTotal').val(subTot.toFixed(2));
    $('#pbQty').val(totQty);
    $('#pbGST').val(gstamount.toFixed(2));
    $('#totaldiscount').val(discount.toFixed(2));
    $('#pbTotal').val(pbTotal.toFixed(2));

    // ✅ eWay Bill check
    if (pbTotal > 100000) {
        $('#ewaybill').attr('required', true);
    } else {
        $('#ewaybill').removeAttr('required');
    }
}

    function handleTds() {
        const subTotal = document.querySelector('input[name="pbSubTotal"]').value;
        const tdsPercent = document.querySelector('input[name="tdspercent"]').value;
        const tdsTotal = (Number(subTotal) * Number(tdsPercent)) / 100;
        document.querySelector('input[name="tdsTotal"]').value = tdsTotal.toFixed(2);
    }

    // --- Qty +/- handlers (unchanged except they now delegate to the same recompute/totals)
    function reduceQuantity(ref) {
        let quantity = parseInt($(ref).siblings('input').val(), 10);
        let rqean = $(ref).parent().attr('id');
        let max = parseInt($(ref).siblings('input').attr('max'), 10);

        if (quantity > 1) {
            $(ref).siblings('input').val(quantity - 1);
            recomputeRowByEAN(rqean);
            calculateTotal();
        } else {
            alert("Quantity cannot be less than 1.");
        }
        handleTds();
    }

    function increaseQuantity(ref) {
        let quantity = parseInt($(ref).siblings('input').val(), 10);
        let max = parseInt($(ref).siblings('input').attr('max'), 10);
        let rqean = $(ref).parent().attr('id');

        if (quantity < max) {
            $(ref).siblings('input').val(quantity + 1);
            recomputeRowByEAN(rqean);
            calculateTotal();
        }
        handleTds();
    }

    // --- Recalc hooks: any change to qty/rate/gst slab/discount will refresh row + totals
    $(document).on('input change', '.receiveqty, .rate, .gstslab, .discount', function () {
        const key = extractRowKeyFromAny(this);
        if (key) {
            recomputeRowByEAN(key);
            calculateTotal();
            handleTds();
        }
    });

    // --- Delete row
    function deleteRow(ref) {
        $(ref).parents("tr").remove();
        calculateTotal();
        handleTds();
    }

    // --- Init: populate header fields and compute everything
    function changeDetails() {
        $("#supplierName").val('{{ $supplierNames[0] ?? '' }}');
        $("#del_date").val('{{ implode(", ", $deliveryDates->toArray()) }}');
        $("#Supplier_ref").val('{{ implode(", ", $supplierRefs->toArray()) }}');
        $("#total_qty").val('{{ $supplierInvoice->tquantity }}');
        $("#total_amount").val('{{ $supplierInvoice->subTotal }}');
        $("#supinv").select().focus();
    }

    $(function () {
        changeDetails();
        // Let the DOM settle (plugins, etc.), then recompute rows → totals → TDS
        setTimeout(function () {
            recomputeAllRows();
            calculateTotal();
            handleTds();
        }, 0);
    });

    window.onload = function () {
        // Final safeguard
        recomputeAllRows();
        calculateTotal();
        handleTds();
    };

    // Submit guard
    $('#createPurchaseBill').on('submit', function (e) {
        if ($('#productTable tr').length < 1) {
            alert("No product added. Add at least 1 product.");
            e.preventDefault();
        } else {
            $('#formSubmit').prop('disabled', true);
        }
    });
</script>
@endsection
