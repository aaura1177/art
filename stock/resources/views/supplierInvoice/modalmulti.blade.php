@extends('layouts.modal')
<style>
    .table-striped.table td {
        font-size: 12px !important;
        padding: 5px;
    }

    .table-striped.table th {
        font-size: 12px !important;
        padding: 5px;
    }

    .supplier_class {
        width: 450px;
    }
</style>
@include('layouts.partials.modal-print-styles', ['printFontSize' => 8])
<div>
    <h1 class="text-center">Supplier Invoice</h1>
</div>

<div class="container">

    <!-- first row -->
    <div class="row box-space">
        <div class="col-6">

            @if (isset($companyDetails))

                @if (isset($supplierInvoice))
                    <h1>Invoice No. {{ $supplierInvoice->supplier_invoice_number }}</h1>
                    <p>Internal Invoice No.: {{ $supplierInvoice->internal_invoice_number }}<br>
                        Invoice Date: {{ date('d-M-Y', strtotime($supplierInvoice->created_at)) }}</p><br>
                    <address>
                        <h3 class="box-space" style="text-decoration: underline;">SUPPLIER</h3>
                        <p>{{ $supplierInvoice->supplierData->c_name }}</p>
                        <p>{{ $supplierInvoice->supplierData->address1 }}</p>
                        <p>{{ $supplierInvoice->supplierData->address2 }}</p>
                        <p>{{ $supplierInvoice->supplierData->city }} - {{ $supplierInvoice->supplierData->postcode }}
                        </p>
                        <p>State Name: {{ $supplierInvoice->supplierData->state }}</p>
                        <p>GSTIN/UIN: <span style="font-weight: 600; font-size: 16px;"
                                id="gstin">{{ $supplierInvoice->supplierData->gstin }}</span></p>
                        <p>Phone: {{ $supplierInvoice->supplierData->phone1 }}</p>
                        <p>E-mail: {{ $supplierInvoice->supplierData->email }}</p>
                    </address>
                @endif
            @endif
        </div>
        <div class="col-6">
            <div class="pull-right" id="supplier">
                <h3 style="text-decoration: underline;">INVOICE TO</h3><br />
                @php
                    $imageName = $companyDetails->logoUrl;
                @endphp

                @if (array_key_exists($imageName, $fileMap1))
                    <img src="{{ $fileMap1[$imageName] }}" alt="Logo Image" class='supplier_class' style="width:100px">
                @else
                    <p>Image not found</p>
                @endif
                @if (isset($purchaseOrder))
                @endif
                @if (isset($companyDetails))
                    <p style="font-size: 24px;"><strong>{{ $companyDetails->c_name }}</strong></p>
                    <address>
                        <p style="font-weight: 700;">{{ $companyDetails->address1 }}</p>
                        <p style="font-weight: 700;">{{ $companyDetails->address2 }}, {{ $companyDetails->city }} -
                            {{ $companyDetails->postcode }}, India</p>
                        <p>State Name: {{ $companyDetails->state }}</p>
                        <p>Company PAN: {{ $companyDetails->pan }}</p>
                        <p>GSTIN/UIN: {{ $companyDetails->gstin }}</p>
                        <p>E-mail: po@artisanfurniture.net</p>
                    </address>
            </div>

        </div>
    </div>

    <div class="row box-space">
        <div class="col-6">
            <address>
                <table width="100%" cellspacing="0">
                    <tbody>
                        <tr>
                            <td>
                                <h3 style="text-decoration: underline;">DISPATCH TO</h3>
                            </td>
                        </tr>
                        <tr>
                            <td>
                                <p>Global Vision Direct (P) Ltd</p>
                            </td>
                        </tr>
                        <tr>
                            <td>
                                <p>Plot# 1216/2, Mahapura Road,</p>
                            </td>
                        </tr>
                        <tr>
                            <td>
                                <p>Jaipur - Mumbai National Highway,</p>
                            </td>
                        </tr>
                        <tr>
                            <td>
                                <p>Bhankrota, Jaipur, Rajasthan - 08</p>
                            </td>
                        </tr>
                        <tr>
                            <td>
                                <p>GSTIN/UIN: {{ $companyDetails->gstin }}</p>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </address>
        </div>

    </div>

    <!-- Products row-->
    <table class="table table-bordered" width="100%" cellspacing="0">
        <tbody>
            <tr class="row">
                <td class="col-4">
                    <strong>PO No:</strong> {{ $multiPoNumbers ?? $supplierInvoice->mulitple_po }}
                </td>
                <td class="col-4">
                    <strong>PO Date:</strong>
                    {{ $podate->map(fn($date) => \Carbon\Carbon::parse($date)->format('d-M-Y'))->implode(', ') }}

                </td>
                <td class="col-4">
                    <strong>PO Delivery Date:</strong>
                    {{ $del_date->map(fn($date) => \Carbon\Carbon::parse($date)->format('d-M-Y'))->implode(', ') }}
                </td>
            </tr>
        </tbody>
    </table>

    <!-- Product details -->
    <div class="row">
        <div class="col">
            <table class="table table-striped table-bordered" width="100%" cellspacing="0">
                <thead>
                    @php
                        $siMultiIsCarton = isset($purchaseOrder)
                            && (int) ($purchaseOrder->type ?? 0) === 2
                            && $supplierInvoice->purchase_order_type !== 'Furniture';
                        $siMultiConsumablePoCol = $supplierInvoice->purchase_order_type === 'Consumable'
                            && ! empty($supplierInvoice->mulitple_po)
                            && isset($purchaseOrder)
                            && (int) $purchaseOrder->type === 1;
                        $siMultiCartonPoCol = $siMultiIsCarton && ! empty($supplierInvoice->mulitple_po);
                        $siMultiShowPoCol = $siMultiConsumablePoCol || $siMultiCartonPoCol;
                        $siMultiShowHsnCol = $supplierInvoice->purchase_order_type === 'Furniture'
                            || (! $siMultiConsumablePoCol && ! $siMultiIsCarton);
                        $siMultiShowDiscountCol = $supplierInvoice->purchase_order_type === 'Furniture'
                            || (
                                $supplierInvoice->purchase_order_type === 'Consumable'
                                && isset($purchaseOrder)
                                && (int) $purchaseOrder->type === 1
                                && ! $siMultiConsumablePoCol
                            );
                        if ($siMultiIsCarton) {
                            $siMultiTableColspan = $supplierInvoice->supplierData->state_code == '08'
                                ? ($siMultiCartonPoCol ? 12 : 11)
                                : ($siMultiCartonPoCol ? 11 : 10);
                        } elseif ($siMultiConsumablePoCol && ! $siMultiShowHsnCol && ! $siMultiShowDiscountCol) {
                            $siMultiTableColspan = $supplierInvoice->supplierData->state_code == '08' ? 10 : 9;
                        } else {
                            $siMultiTableColspan = $supplierInvoice->supplierData->state_code == '08' ? 15 : 14;
                        }
                    @endphp
                    <tr>
                        <th style="max-width: 40px;">Sl. No.</th>
                        <th>Description of Goods</th>
                        @if ($supplierInvoice->purchase_order_type == 'Furniture')
                            <th>PO.No</th>
                            <th>Image</th>
                            <th>EAN</th>
                        @elseif ($siMultiShowPoCol)
                            <th>PO No.</th>
                        @endif
                        @if ($siMultiShowHsnCol)
                            <th>HSN / SAC</th>
                        @endif

                        @if ($supplierInvoice->purchase_order_type == 'Furniture')
                            <th style="max-width: 40px;">QTY</th>
                            <th>Rate (₹)</th>
                        @else
                            @if ($purchaseOrder->type == 1)
                                <th style="max-width: 40px;">QTY</th>
                                <th>Rate (₹)</th>
                            @else
                                <th style="max-width: 40px;">Box 1 QTY</th>
                                <th style="max-width: 40px;">Box 2 QTY</th>
                                <th>Box 1 Rate (₹)</th>
                                <th>Box 2 Rate (₹)</th>
                            @endif
                        @endif
                        <th>Amount (₹)</th>
                        @if ($siMultiShowDiscountCol)
                            <th>Discount</th>
                        @endif
                        <th>GST (%)</th>
                        @if ($supplierInvoice->supplierData->state_code == '08')
                            <th>CGST (₹)</th>
                            <th>SGST (₹)</th>
                        @else
                            <th>IGST (₹)</th>
                        @endif
                        <th>Total (₹)</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $tgst = 0;
                    $siCartonFooterQty1 = 0;
                    $siCartonFooterQty2 = 0;
                    ?>
                    @if ($supplierInvoice->purchase_order_type == 'Furniture')
                        @if (isset($poTable))
                            @foreach ($poTable as $key => $poTable)
                                @if ($poTable->quantity > 0)
                                   <?php
$afterDiscount = $poTable->amount - $poTable->discount;
$afterDiscountGst = $afterDiscount * (optional($poTable->potable)->gstslab ?? 0) / 100;
$totalamount = $afterDiscount +$afterDiscountGst;
$tgst = $tgst + $afterDiscountGst;

?>

                                    <tr>
                                        <td>{{ ++$key }}</td>
                                        <td>{{ $poTable->product->code }} - {{ $poTable->product->name }}</td>
                                        <td>{{ $poTable->mulitple_po ?? 'N/A' }}</td>
                                        <td><img width="55px"
                                                src="{{ asset('uploads/') }}/image.php?image=/allproducts/{{ $poTable->product->code . '/' . $poTable->product->code . '-1.jpg' }}&height=90px&width=90px"
                                                alt="No Image available" /></td>
                                        <td>{{ $poTable->product->EAN }}</td>
                                        <td>{{ $poTable->product->HSN }}</td>
                                        <td style="min-width: 67px;">{{ $poTable->quantity - $poTable->returned_qty }}
                                            {{ $poTable->unit }}</td>
                                        <td>{{ optional($poTable->potable)->rate !== null ? view_amount(optional($poTable->potable)->rate) : 'N/A' }}</td>
                                        <td>{{ view_amount($poTable->amount) }}</td>
                                        <td>₹{{ view_amount($poTable->discount) }}</td>
                                        <td>{{ optional($poTable->potable)->gstslab }}</td>
                                        @if ($supplierInvoice->supplierData->state_code == '08')
                                            <td>{{ view_amount($afterDiscountGst / 2) }}
                                            </td>
                                            <td>{{ view_amount($afterDiscountGst / 2) }}
                                            </td>>
                                        @else
                                            <td>{{ $afterDiscountGst }}</td>
                                        @endif
                                        <td>{{ view_amount($totalamount) }}</td>
                                    </tr>
                                @endif
                            @endforeach
                        @endif
                    @else
                        @if ($purchaseOrder->type == 1)
                            @if (isset($poTable))
                                @foreach ($poTable as $key => $poTable)
                                    @if ($poTable->quantity > 0)
                                        <?php
                                        $tgst = $tgst + $poTable->gstamount;
                                        ?>
                                        <tr>
                                            <td>{{ ++$key }}</td>
                                            <td>
                                                {{ optional(optional($poTable->potable)->consumable)->name ?? optional($poTable->consumable)->name }}
                                                @if (! empty(optional($poTable->potable)->description))
                                                    <br><small class="text-muted">{{ nl2br(e($poTable->potable->description)) }}</small>
                                                @endif
                                            </td>
                                            @if ($siMultiConsumablePoCol)
                                                <td>{{ $poTable->mulitple_po ?? '' }}</td>
                                            @endif
                                            @if ($siMultiShowHsnCol)
                                                <td></td>
                                            @endif
                                            <td style="min-width: 67px;">
                                                {{ $poTable->quantity - $poTable->returned_qty }} {{ $poTable->unit }}
                                            </td>
                                            <td>{{ view_amount($poTable->potable->rate) }}</td>
                                            <td>{{ view_amount($poTable->amount) }}</td>
                                            @if ($siMultiShowDiscountCol)
                                                <td>₹0</td>
                                            @endif
                                            <td>{{ $poTable->potable->gstslab }}</td>
                                            @if ($supplierInvoice->supplierData->state_code == '08')
                                                <td>{{ view_amount($poTable->gstamount / 2) }}
                                                </td>
                                                <td>{{ view_amount($poTable->gstamount / 2) }}
                                                </td>
                                            @else
                                                <td>{{ view_amount($poTable->gstamount) }}</td>
                                            @endif
                                            <td>{{ view_amount($poTable->total) }}</td>
                                        </tr>
                                    @endif
                                @endforeach
                            @endif
                        @else
                            @if (isset($poTable))
                                @foreach ($poTable as $key => $poTable)
                                    @php
                                        $siCartonNetQty1 = max(0, (int) $poTable->quantity - (int) ($poTable->returned_qty ?? 0));
                                        $siCartonNetQty2 = max(0, (int) ($poTable->quantity2 ?? 0) - (int) ($poTable->return_qty2 ?? 0));
                                    @endphp
                                    @if ($siCartonNetQty1 > 0 || $siCartonNetQty2 > 0)
                                        <?php
                                        $tgst = $tgst + $poTable->gstamount;
                                        $siCartonFooterQty1 += $siCartonNetQty1;
                                        $siCartonFooterQty2 += $siCartonNetQty2;
                                        ?>
                                        <tr>
                                            <td>{{ ++$key }}</td>
                                            <td>{{ $poTable->product->code }} - {{ $poTable->product->name }}</td>
                                            @if ($siMultiCartonPoCol)
                                                <td>{{ $poTable->mulitple_po ?? '' }}</td>
                                            @endif
                                            <td style="min-width: 67px;">
                                                {{ $siCartonNetQty1 }} {{ $poTable->unit }}
                                            </td>
                                            <td style="min-width: 67px;">
                                                {{ $siCartonNetQty2 }} {{ $poTable->unit }}
                                            </td>
                                            <td>{{ view_amount($poTable->potable->box1_rate) }}</td>
                                            <td>{{ view_amount($poTable->potable->box2_rate) }}</td>
                                            <td>{{ view_amount($poTable->amount) }}</td>
                                            <td>{{ $poTable->potable->gstslab }}</td>
                                            @if ($supplierInvoice->supplierData->state_code == '08')
                                                <td>{{ view_amount($poTable->gstamount / 2) }}
                                                </td>
                                                <td>{{ view_amount($poTable->gstamount / 2) }}
                                                </td>
                                            @else
                                                <td>{{ view_amount($poTable->gstamount) }}</td>
                                            @endif
                                            <td>{{ view_amount($poTable->amount + $poTable->gstamount) }}
                                            </td>
                                        </tr>
                                    @endif
                                @endforeach
                            @endif
                        @endif


                    @endif
                    @php
                        $siMultiIntraState = ($supplierInvoice->supplierData->state_code ?? $purchaseOrder->supplier->state_code ?? '') === '08';
                        $siMultiDisplayTgst = isset($footerTgst) && $footerTgst !== null
                            ? (float) $footerTgst
                            : ((int) ($purchaseOrder->type ?? 0) === 2
                                ? (float) ($supplierInvoice->tgst ?? 0)
                                : (float) ($tgst ?? 0));
                        $siMultiTaxable = (float) $supplierInvoice->subTotal + (float) $supplierInvoice->totaldiscount;
                        $siMultiPrintRoundOff = \App\Helpers\PrintAmountHelper::resolvePrintRoundOff(
                            $supplierInvoice->roundoff,
                            $siMultiTaxable,
                            $siMultiDisplayTgst,
                            (float) ($supplierInvoice->tamount ?? 0),
                            $siMultiIntraState
                        );
                        if ($supplierInvoice->purchase_order_type == 'Furniture') {
                            $siMultiRoVariant = 'si_furniture';
                        } elseif ((int) ($purchaseOrder->type ?? 0) === 1) {
                            $siMultiRoVariant = 'si_multi_consumable';
                        } elseif ($siMultiCartonPoCol ?? false) {
                            $siMultiRoVariant = 'si_multi_carton';
                        } else {
                            $siMultiRoVariant = 'si_carton';
                        }
                        $siMultiFooterSubtotal = $siMultiTaxable;
                        $siMultiFooterCgst = $siMultiIntraState ? round($siMultiDisplayTgst / 2, 2) : 0.0;
                        $siMultiFooterSgst = $siMultiIntraState ? round($siMultiDisplayTgst / 2, 2) : 0.0;
                        $siMultiFooterIgst = $siMultiIntraState ? 0.0 : round($siMultiDisplayTgst, 2);
                        $siMultiPreRoundoffTotal = \App\Helpers\PrintAmountHelper::footerTotalSubtotalPlusGst(
                            $siMultiFooterSubtotal,
                            (float) $siMultiDisplayTgst,
                            $siMultiIntraState
                        );
                    @endphp
                    <tr>
                        <td></td>
                        @if ($supplierInvoice->purchase_order_type == 'Furniture')
                            <td></td>
                            <td></td>
                            <td></td>
                            <td></td>
                        @else
                            <td></td>
                            @if ($siMultiShowPoCol ?? false)
                                <td></td>
                            @endif
                        @endif
                        @if ($siMultiShowHsnCol)
                            <td></td>
                        @endif
                        @if ($supplierInvoice->purchase_order_type == 'Furniture')
                            <td><strong>{{ $supplierInvoice->tquantity }}</strong></td>
                            <td></td>
                        @else
                            @if ($purchaseOrder->type == 1)
                                <td><strong>{{ $supplierInvoice->tquantity }}</strong></td>
                                <td></td>
                            @else
                                <td><strong>{{ $siCartonFooterQty1 }}</strong></td>
                                <td><strong>{{ $siCartonFooterQty2 }}</strong></td>
                                <td></td>
                                <td></td>
                            @endif
                        @endif
                        <td><strong>₹{{ view_amount($siMultiFooterSubtotal) }}</strong></td>
                        @if ($siMultiShowDiscountCol)
                            <td>₹{{ view_amount($supplierInvoice->totaldiscount) }}</td>
                        @endif
                        <td></td>
                        @if ($supplierInvoice->supplierData->state_code == '08')
                            <td><strong>₹{{ view_amount($siMultiFooterCgst) }}</strong></td>
                            <td><strong>₹{{ view_amount($siMultiFooterSgst) }}</strong></td>
                        @else
                            <td><strong>₹{{ view_amount($siMultiFooterIgst) }}</strong></td>
                        @endif
                        <td><strong>₹{{ view_amount($siMultiPreRoundoffTotal) }}</strong></td>
                    </tr>
                    @include('partials.print-gst-roundoff-row', [
                        'variant' => $siMultiRoVariant,
                        'printRoundOff' => $siMultiPrintRoundOff,
                        'intraState' => $siMultiIntraState,
                    ])
                    <tr>
                        <td></td>
                        @if ($supplierInvoice->purchase_order_type == 'Furniture')
                            <td></td>
                            <td></td>
                            <td></td>
                            <td></td>
                        @else
                            <td></td>
                            @if ($siMultiShowPoCol ?? false)
                                <td></td>
                            @endif
                        @endif
                        @if ($siMultiShowHsnCol)
                            <td></td>
                        @endif
                        @if ($supplierInvoice->purchase_order_type == 'Furniture')
                            <td></td>
                            <td></td>
                        @elseif ((int) ($purchaseOrder->type ?? 0) === 1)
                            <td></td>
                            <td></td>
                        @else
                            <td></td>
                            <td></td>
                            <td></td>
                            <td></td>
                        @endif
                        <td></td>
                        @if ($siMultiShowDiscountCol)
                            <td></td>
                        @endif
                        <td><strong>TOTAL (₹)</strong></td>
                        @if ($supplierInvoice->supplierData->state_code == '08')
                            <td></td>
                            <td></td>
                        @else
                            <td></td>
                        @endif
                        <td id="tamount">
                            <strong>₹{{ view_amount($supplierInvoice->tamount ? $supplierInvoice->tamount : 0) }}</strong>
                        </td>
                    </tr>
                    <tr>
                        <td colspan="{{ $siMultiTableColspan }}"><strong>Total Amount in words: </strong><span id="amountInWords"></span></td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
    @endif

    <!-- Declaration & Signature row -->
    <div class="row">
        <div class="col">
            <table class="table" width="100%" cellspacing="0">
                <tr>

                    <td style="vertical-align: bottom;" class="pull-right">
                        <h3 class="sig-name">For {{ $supplierInvoice->supplierData->c_name }}</h3>
                        <div style="padding-top: 45px;">___________________________________</div>
                        <div>
                            <p>Authorised Signatory</p>
                        </div>
                    </td>
                </tr>
            </table>
        </div>
    </div>
</div>

<div class="text-center">
    <p>
        SUBJECTED TO JAIPUR, RAJASTHAN JURISDICTION<br>
        This is a Computer Generated Document
    </p>
</div>


<!-- Script start for Amount in Words -->
<script src="{{ asset('ui-vendor/jquery/jquery.min.js') }}"></script>
<script type="text/javascript">
    $(document).ready(function() {

        var n = document.getElementById('tamount').textContent;
        n = n.replace(/[^0-9.]/g, '');
        n = parseFloat(n, 10);

        var nums = n.toString().split('.')

        var whole = convertNumberToWords(nums[0]);

        if (nums.length == 2) {
            var fraction = convertNumberToWords(nums[1]);
            if (whole == 0) {
                var s = document.getElementById('amountInWords');
                s.innerHTML = fraction + 'Paise';
            } else {
                if (fraction) {
                    var s = document.getElementById('amountInWords');
                    s.innerHTML = whole + 'Rupees and ' + fraction + 'Paise';
                } else {
                    var s = document.getElementById('amountInWords');
                    s.innerHTML = whole + 'Rupees';
                }
            }

        } else {
            if (whole == 0) {
                var s = document.getElementById('amountInWords');
                s.innerHTML = whole + 'Zero Rupee';
            } else {
                var s = document.getElementById('amountInWords');
                s.innerHTML = whole + 'Rupees';
            }
        }

        function convertNumberToWords(amount) {

            var words = new Array();
            words[0] = '';
            words[1] = 'One';
            words[2] = 'Two';
            words[3] = 'Three';
            words[4] = 'Four';
            words[5] = 'Five';
            words[6] = 'Six';
            words[7] = 'Seven';
            words[8] = 'Eight';
            words[9] = 'Nine';
            words[10] = 'Ten';
            words[11] = 'Eleven';
            words[12] = 'Twelve';
            words[13] = 'Thirteen';
            words[14] = 'Fourteen';
            words[15] = 'Fifteen';
            words[16] = 'Sixteen';
            words[17] = 'Seventeen';
            words[18] = 'Eighteen';
            words[19] = 'Nineteen';
            words[20] = 'Twenty';
            words[30] = 'Thirty';
            words[40] = 'Forty';
            words[50] = 'Fifty';
            words[60] = 'Sixty';
            words[70] = 'Seventy';
            words[80] = 'Eighty';
            words[90] = 'Ninety';

            var amount = amount.toString();
            var atemp = amount.split(".");
            var number = atemp[0].split(",").join("");
            var n_length = number.length;
            var words_string = "";

            if (n_length <= 9) {
                var n_array = new Array(0, 0, 0, 0, 0, 0, 0, 0, 0);
                var received_n_array = new Array();
                for (var i = 0; i < n_length; i++) {
                    received_n_array[i] = number.substr(i, 1);
                }
                for (var i = 9 - n_length, j = 0; i < 9; i++, j++) {
                    n_array[i] = received_n_array[j];
                }
                for (var i = 0, j = 1; i < 9; i++, j++) {
                    if (i == 0 || i == 2 || i == 4 || i == 7) {
                        if (n_array[i] == 1) {
                            n_array[j] = 10 + parseInt(n_array[j]);
                            n_array[i] = 0;
                        }
                    }
                }

                value = "";
                for (var i = 0; i < 9; i++) {
                    if (i == 0 || i == 2 || i == 4 || i == 7) {
                        value = n_array[i] * 10;
                    } else {
                        value = n_array[i];
                    }
                    if (value != 0) {
                        words_string += words[value] + " ";
                    }
                    if ((i == 1 && value != 0) || (i == 0 && value != 0 && n_array[i + 1] == 0)) {
                        words_string += "Crores ";
                    }
                    if ((i == 3 && value != 0) || (i == 2 && value != 0 && n_array[i + 1] == 0)) {
                        words_string += "Lakhs ";
                    }
                    if ((i == 5 && value != 0) || (i == 4 && value != 0 && n_array[i + 1] == 0)) {
                        words_string += "Thousand ";
                    }
                    if (i == 6 && value != 0 && (n_array[i + 1] != 0 && n_array[i + 2] != 0)) {
                        words_string += "Hundred ";
                    } else if (i == 6 && value != 0) {
                        words_string += "Hundred ";
                    }
                }
                words_string = words_string.split("  ").join(" ");
            }
            return words_string;
        }
    });

    var gstin = document.getElementById("gstin").textContent;
    if (gstin.length != 0) {
        document.getElementById("gstin").innerHTML = gstin;
    } else {
        document.getElementById("gstin").innerHTML = "N.A";

    }
</script>
<!-- Script end -->


<!-- Script start for Print PO -->
@if (isset($print) && $print == 1)
    <script type="text/javascript">
        document.ready = window.print();
    </script>
@endif
<!-- Script end -->