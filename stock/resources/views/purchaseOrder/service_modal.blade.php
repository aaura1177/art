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

    .container {
        width: 1180px !important;
    }

@media screen and (max-width: 1199px), print {
  .container {
    width: 100% !important;
    max-width: 100% !important;
  }
  .table {
    table-layout: auto !important;
    width: 100% !important;
  }
  .table th, .table td {
    white-space: normal !important;
    word-break: normal !important;
    overflow-wrap: normal !important;
    font-size: 10px !important;
    padding: 2px 3px !important;
  }
}

@media print {
  .container {
    width: 100% !important;
    max-width: 100% !important;
    box-sizing: border-box !important;
    margin-bottom: 0.5rem !important;
  }

  /* Compact header/terms/declaration only — product table rules unchanged */
  h1.text-center {
    margin: 0 0 0.25rem !important;
    font-size: 1.25rem !important;
  }
  .row.box-space {
    margin-top: 0.35rem !important;
  }
  .box-space {
    margin-bottom: 0 !important;
  }
  address h2,
  address h3 {
    margin-bottom: 0.15rem !important;
  }
  address p {
    margin-bottom: 0.1rem !important;
    line-height: 1.2 !important;
  }
  address br {
    display: none;
  }
  .container > .row:last-of-type p {
    margin-bottom: 0.15rem !important;
    line-height: 1.25 !important;
    font-size: 14px !important;
  }
  .container > .row:last-of-type h3 {
    margin-bottom: 0.25rem !important;
  }
  .container > .row:last-of-type img {
    height: 55px !important;
  }
  .container > .row:last-of-type {
    page-break-inside: avoid;
  }
  .text-center > p {
    margin: 0.25rem 0 0 !important;
    font-size: 12px !important;
  }
}
</style>
<div>
    <h1 class="text-center">Purchase Order</h1>
</div>

<div class="container">

    <!-- first row -->
    <div class="row box-space">
        <div class="col-6">
            <h3 style="text-decoration: underline;">INVOICE TO</h3><br />
            @if (isset($companyDetails))
                @php
                    $imageName = $companyDetails->logoUrl;
                @endphp

                @if (array_key_exists($imageName, $fileMap1))
                    <img width="500px" src="{{ $fileMap1[$imageName] }}" alt="Logo Image" style="width:100px;">
                @else
                    <p>Image not found</p>
                @endif
                <address>
                    <h2 style="font-weight: 500;">{{ $companyDetails->c_name }}</h2>
                    <p style="font-weight: 700;">{{ $companyDetails->address1 }}</p>
                    <p style="font-weight: 700;">{{ $companyDetails->address2 }}, {{ $companyDetails->city }} -
                        {{ $companyDetails->postcode }}, India</p>
                    <p>State Name: {{ $companyDetails->state }}</p>
                    <p>Company PAN: {{ $companyDetails->pan }}</p>
                    <p>GSTIN/UIN: {{ $companyDetails->gstin }}</p>
                    <p>E-mail: po@artisanfurniture.net</p>
                </address>
            @endif
        </div>
        <div class="col-6">
            <div class="pull-right" id="supplier">
                @if (isset($purchaseOrder))
                    <h1>PO No. {{ $purchaseOrder->pono }}</h1>
                    @if ($purchaseOrder->po_revise_date)
                        <p>PO Revise Date: {{ date('d-M-Y', strtotime($purchaseOrder->po_revise_date)) }}</p><br>
                    @else
                        <p>PO Date: {{ date('d-M-Y', strtotime($purchaseOrder->podate)) }}</p><br>
                    @endif
                    <address>
                        <h3 class="box-space" style="text-decoration: underline;">SUPPLIER</h3>
                        <p>{{ $purchaseOrder->supplier->c_name }}</p>
                        <p>{{ $purchaseOrder->supplier->address1 }}</p>
                        <p>{{ $purchaseOrder->supplier->address2 }}</p>
                        <p>{{ $purchaseOrder->supplier->city }} - {{ $purchaseOrder->supplier->postcode }}</p>
                        <p>State Name: {{ $purchaseOrder->supplier->state }}</p>
                        <p>GSTIN/UIN: <span style="font-weight: 600; font-size: 16px;"
                                id="gstin">{{ $purchaseOrder->supplier->gstin }}</span></p>
                        <p>Phone: {{ $purchaseOrder->supplier->phone1 }}</p>
                        <p>E-mail: {{ $purchaseOrder->supplier->email }}</p>
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
                                {{-- <h3 style="text-decoration: underline;">DISPATCH TO
                                    ({{ $purchaseOrder->address_option == 1 ? 'Office' : 'Factory' }})</h3> --}}
                            </td>
                        </tr>
                        <tr>
                            <td>
                                <p>Global Vision Direct (P) Ltd</p>
                            </td>
                        </tr>
                        <tr>
                            <td>
                                @if ($purchaseOrder->address_option == 1)
                                    <p>{{ $companyDetails->address2 }}, {{ $companyDetails->city }} -
                                        {{ $companyDetails->postcode }}, India</p>
                                @else
                                    <p>{{ $companyDetails->factory_address }}, {{ $companyDetails->city }} - {{ $companyDetails->postcode }},
                                        India</p>
                                @endif
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
        <div class="col-6">
            <address>
                <table width="100%" cellspacing="0">
                    <tbody>
                        <tr>
                            <td class="text-end">
                                <h3 style="text-decoration: underline;">TERMS OF PAYMENT</h3>
                            </td>
                        </tr>
                        <tr>
                            <td class="text-end">
                                <p>{{ $purchaseOrder->payterms }}</p>
                            </td>
                        </tr>
                        <tr>
                            <th class="text-end box-space">
                                <h3 style="text-decoration: underline;">TERMS OF DELIVERY</h3>
                            </th>
                        </tr>
                        <tr>
                            <td class="terms-delivery-body service-po-terms">
                                <p>{!! str_replace(PHP_EOL, '<br>', $purchaseOrder->remarks) !!}</p>
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
                <td class="col-3">
                    <strong>PO Date:</strong> {{ date('d-M-Y', strtotime($purchaseOrder->podate)) }}
                </td>
                <td class="col-3">
                    <strong>Project / Service completion date:</strong> {{ date('d-M-Y', strtotime($purchaseOrder->del_date)) }}
                </td>
                <td class="col-3">
                    <strong>Supplier Ref:</strong> {{ $purchaseOrder->ref_supplier }}
                </td>
                <td class="col-3">
                    <strong>Buyer Ref:</strong> {{ $purchaseOrder->buyer_orderno }}
                </td>
            </tr>
        </tbody>
    </table>

    <!-- Product details -->
    <div class="row">
        <div class="col">
            
            <table class="table table-striped table-bordered" width="100%" cellspacing="0">
                <thead>
                    <tr>
                        <th style="max-width: 40px;">S. No.</th>
                        <th>Description of Goods</th>
                        <th>Category</th>
                        {{-- <th>EAN</th>
                  <th>HSN / SAC</th> --}}
                        <th style="max-width: 40px;">QTY</th>
                        <th>Rate (₹)</th>
                        <th>Amount (₹)</th>
                        <th>GST (%)</th>
                        @if ($purchaseOrder->supplier->state_code == '08')
                            <th>CGST (₹)</th>
                            <th>SGST (₹)</th>
                        @else
                            <th>IGST (₹)</th>
                        @endif
                        <th>Total (₹)</th>

                        <th>KD to be provided by the company</th>
                    </tr>
                </thead>

                <tbody>


                    {{-- @if (isset($poTable))
                        @foreach ($poTable as $key => $poTable)
                            <tr>
                                <td>{{ ++$key }}</td>
                                <td> {{ $poTable->product->name }}<br>
                                    @if ($poTable->description != '')
                                        <i>({{ $poTable->description }})</i>
                                    @endif
                                </td>
                                <td>{{ $poTable->product->serviceCategory->name }}</td>

                                <td style="min-width: 67px;">{{ $poTable->quantity }} {{ $poTable->unit }}</td>
                                <td>{{ $poTable->rate }}</td>
                                <td>{{ $poTable->amount }}</td>
                                <td>{{ $poTable->gstslab }}</td>
                                @if ($purchaseOrder->supplier->state_code == '08')
                                    <td>{{ str_replace(',', '', number_format($poTable->gstamount / 2, 2)) }}</td>
                                    <td>{{ str_replace(',', '', number_format($poTable->gstamount / 2, 2)) }}</td>
                                @else
                                    <td>{{ $poTable->gstamount }}</td>
                                @endif
                                <td>{{ str_replace(',', '', number_format($poTable->amount + $poTable->gstamount, 2)) }}
                                </td>


                                <td>
                                    @if ($poTable->legs == 1)
                                        Yes
                                    @endif
                                    @if ($poTable->legs == 2)
                                        No
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    @endif --}}

                    @if (isset($poTable))
                    @foreach ($poTable as $key => $item)
                        <tr style="background-color: #dfdfdf;font-weight: bold;">
                            <td>{{ ++$key }}</td>
                            <td>
                                {{ $item->product->name }}<br>
                                @if ($item->description != '')
                                    <i>({{ $item->description }})</i>
                                @endif
                            </td>
                            <td>{{ $item->product->serviceCategory->name }}</td>
                            <td style="min-width: 67px;">{{ $item->quantity }} {{ $item->unit }}</td>
                            <td>{{ str_replace(',', '', number_format((float) $item->rate, 2)) }}</td>
                            <td>{{ str_replace(',', '', number_format((float) $item->amount, 2)) }}</td>
                            <td>{{ $item->gstslab }}</td>
                
                            @if ($purchaseOrder->supplier->state_code == '08')
                                <td>{{ number_format($item->gstamount / 2, 2) }}</td>
                                <td>{{ number_format($item->gstamount / 2, 2) }}</td>
                            @else
                                <td>{{ str_replace(',', '', number_format((float) $item->gstamount, 2)) }}</td>
                            @endif
                
                            <td>{{ number_format($item->amount + $item->gstamount, 2) }}</td>
                
                            <td>
                                @if ($item->legs == 1)
                                    Yes
                                @elseif ($item->legs == 2)
                                    No
                                @endif
                            </td>
                        </tr>
                
                        @if ($item->sub_serviceproduct && count($item->sub_serviceproduct))
                            @foreach ($item->sub_serviceproduct as $sub)
                                <tr style="background-color: #ffffff">
                                    <td></td>
                                    <td colspan="2">→ {{ $sub->sub_name }}</td>
                                    <td>{{ $sub->sub_quantity }}</td>
                                    <td>{{ str_replace(',', '', number_format((float) $sub->sub_rate, 2)) }}</td>
                                    <td>{{ str_replace(',', '', number_format((float) $sub->sub_amount, 2)) }}</td>
                                    <td>{{ $sub->sub_gstslab }}</td>
                
                                    @if ($purchaseOrder->supplier->state_code == '08')
                                        <td>{{ number_format($sub->sub_gstamount / 2, 2) }}</td>
                                        <td>{{ number_format($sub->sub_gstamount / 2, 2) }}</td>
                                    @else
                                        <td>{{ str_replace(',', '', number_format((float) $sub->sub_gstamount, 2)) }}</td>
                                    @endif
                
                                    <td>{{ number_format($sub->sub_amount + $sub->sub_gstamount, 2) }}</td>
                                    <td></td>
                                </tr>
                            @endforeach
                        @endif
                    @endforeach
                @endif
                



                    @php
                        $poServiceIntraState = ($purchaseOrder->supplier->state_code ?? '') === '08';
                        $poServiceFooterSubtotal = (float) $purchaseOrder->subTotal;
                        $poServiceFooterCgst = $poServiceIntraState ? round((float) $purchaseOrder->tgst / 2, 2) : 0.0;
                        $poServiceFooterSgst = $poServiceIntraState ? round((float) $purchaseOrder->tgst / 2, 2) : 0.0;
                        $poServiceFooterIgst = $poServiceIntraState ? 0.0 : round((float) $purchaseOrder->tgst, 2);
                        $poServicePreRoundoffTotal = \App\Helpers\PrintAmountHelper::footerTotalSubtotalPlusGst(
                            $poServiceFooterSubtotal,
                            (float) $purchaseOrder->tgst,
                            $poServiceIntraState
                        );
                        $poServicePrintRoundOff = \App\Helpers\PrintAmountHelper::gstRoundOff(
                            $poServiceFooterSubtotal,
                            (float) $purchaseOrder->tgst,
                            (float) $purchaseOrder->tamount,
                            $poServiceIntraState
                        );
                    @endphp
                    <tr style="background-color: #ceb1b1">
                        <td></td>
                        <td colspan="2">Total</td>
                        <td><strong>{{ number_format($poTable->sum(fn ($item) => (float) $item->quantity), 2) }}</strong></td>
                        <td></td>
                        <td><strong>₹{{ view_amount($poServiceFooterSubtotal) }}</strong></td>
                        <td></td>
                        @if ($poServiceIntraState)
                            <td><strong>₹{{ view_amount($poServiceFooterCgst) }}</strong></td>
                            <td><strong>₹{{ view_amount($poServiceFooterSgst) }}</strong></td>
                        @else
                            <td><strong>₹{{ view_amount($poServiceFooterIgst) }}</strong></td>
                        @endif
                        <td><strong>₹{{ view_amount($poServicePreRoundoffTotal) }}</strong></td>
                        <td></td>
                    </tr>
                    @include('partials.print-gst-roundoff-row', [
                        'variant' => 'po_service',
                        'printRoundOff' => $poServicePrintRoundOff,
                        'intraState' => $poServiceIntraState,
                    ])
                    <tr>
                        <td></td>
                        <td colspan="2"></td>
                        <td></td>
                        <td></td>
                        <td><strong>TOTAL (₹)</strong></td>
                        <td></td>
                        @if ($poServiceIntraState)
                            <td></td>
                            <td></td>
                        @else
                            <td></td>
                        @endif
                        <td id="tamount"><strong>₹</strong><span
                                class="amt"><strong>{{ view_amount($purchaseOrder->tamount) }}</strong></span></td>
                        <td></td>
                    </tr>
                    <tr>
                        @if ($purchaseOrder->supplier->state_code == '08')
                            <td colspan="15"><strong>Total Amount in words: </strong><span id="amountInWords"></span>
                            </td>
                        @else
                            <td colspan="15"><strong>Total Amount in words: </strong><span id="amountInWords"></span>
                            </td>
                        @endif
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
                    <td>
                        <h3>DECLARATION</h3>
                        <p>1. Please get one piece approved before production.</p>
                        <p>2. Delivery date MUST be followed.</p>
                        <p>3. Any change in quality & delivery term will have financial implications.</p>
                        <p>4. Payment will be organised using a/c payee cheques only (No Cash).</p>
                        <p>5. All disputes subject to Jaipur, Rajasthan jurisdication.</p>
                    </td>
                    <td style="vertical-align: bottom;">
                        <h3 class="sig-name">For Global Vision Direct (P) Ltd</h3>
   @php
            $imageName = $companyDetails->sign_url;
        @endphp
        
        @if(array_key_exists($imageName, $fileMap1))
            <img height="80px" alt="" src="{{ $fileMap1[$imageName] }}" alt="Sign Image">
        @else
            <p>Image not found</p>
        @endif                        <div>___________________________________</div>
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
        b = 1;
        $('#tamount').click(function() {
            var a = document.getElementById('tamount').textContent;
            a = a.replace(/[^0-9.]/g, '');
            a = parseFloat(a, 10);
            $(this).toggleClass('roundOff');
            if ($(this).hasClass('roundOff')) {
                if (b == 1) {
                    a = Math.round(a);
                    $(this).find('.amt').html('<strong>' + a + '</strong>');
                }
                if (b == 2) {
                    a = Math.ceil(a);
                    $(this).find('.amt').html('<strong>' + a + '</strong>');
                }
                if (b == 3) {
                    a = Math.floor(a);
                    $(this).find('.amt').html('<strong>' + a + '</strong>');
                    b = 0;
                }
                b++;
            } else {
                $(this).find('.amt').html('<strong>{{ view_amount($purchaseOrder->tamount) }}</strong>');
            }
        });
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
