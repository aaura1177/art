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
  }
}
</style>

<div>
    <h1 class="text-center">Consumables Purchase Order</h1>
    <p class="text-center text-muted mb-0">Draft — for reference only</p>
</div>

<div class="container">
    <div class="row box-space">
        <div class="col-6">
            <h3 style="text-decoration: underline;">INVOICE TO</h3><br />
            @if (isset($companyDetails))
                @php $imageName = $companyDetails->logoUrl; @endphp
                @if (array_key_exists($imageName, $fileMap1))
                    <img width="500px" src="{{ $fileMap1[$imageName] }}" alt="Logo Image" style="width:100px;">
                @else
                    <p>Image not found</p>
                @endif
                <p style="font-size: 24px;"><strong>{{ $companyDetails->c_name }}</strong></p>
                <address>
                    <p style="font-weight: 700;">{{ $companyDetails->address1 }}</p>
                    <p style="font-weight: 700;">{{ $companyDetails->address2 }}, {{ $companyDetails->city }} - {{ $companyDetails->postcode }}, India</p>
                    <p>State Name: {{ $companyDetails->state }}</p>
                    <p>Company PAN: {{ $companyDetails->pan }}</p>
                    <p>GSTIN/UIN: {{ $companyDetails->gstin }}</p>
                    <p>E-mail: po@artisanfurniture.net</p>
                </address>
            @endif
        </div>
        <div class="col-6">
            <div class="pull-right" id="supplier">
                @if (isset($draft))
                    <h1>PO No. {{ $draft->draft_pono }}</h1>
                    <p>PO Date: {{ date('d-M-Y', strtotime($draft->podate)) }}</p><br>
                    <address>
                        <h3 class="box-space" style="text-decoration: underline;">SUPPLIER</h3>
                        <p style="font-size:24px;"><strong>{{ $draft->supplier->c_name }}</strong></p>
                        <p>{{ $draft->supplier->address1 }}</p>
                        <p>{{ $draft->supplier->address2 }}</p>
                        <p>{{ $draft->supplier->city }} - {{ $draft->supplier->postcode }}</p>
                        <p>State Name: {{ $draft->supplier->state }}</p>
                        <p>GSTIN/UIN: <span style="font-weight: 600; font-size: 16px;" id="gstin">{{ $draft->supplier->gstin }}</span></p>
                        <p>Phone: {{ $draft->supplier->phone1 }}</p>
                        <p>E-mail: {{ $draft->supplier->email }}</p>
                    </address>
                @endif
            </div>
        </div>
    </div>

    <div class="row box-space">
        <div class="col-6">
            <address>
                <table width="100%" cellspacing="0">
                    <tbody>
                        <tr>
                            <td><h3 style="text-decoration: underline;">DISPATCH TO ({{ ($draft->address_option == 1) ? 'Office' : 'Factory' }})</h3></td>
                        </tr>
                        <tr><td><p>Global Vision Direct (P) Ltd</p></td></tr>
                        <tr>
                            <td>
                                @if ($draft->address_option == 1)
                                    <p>{{ $companyDetails->address2 }}, {{ $companyDetails->city }} - {{ $companyDetails->postcode }}, India</p>
                                @else
                                    <p>{{ $companyDetails->factory_address }}, {{ $companyDetails->city }} - {{ $companyDetails->postcode }}, India</p>
                                @endif
                            </td>
                        </tr>
                        <tr><td><p>GSTIN/UIN: {{ $companyDetails->gstin }}</p></td></tr>
                    </tbody>
                </table>
            </address>
        </div>
        <div class="col-6">
            <address>
                <table width="100%" cellspacing="0">
                    <tbody>
                        <tr><td class="text-end"><h3 style="text-decoration: underline;">TERMS OF PAYMENT</h3></td></tr>
                        <tr><td class="text-end"><p>{{ $draft->payterms }}</p></td></tr>
                        <tr><th class="text-end box-space"><h3 style="text-decoration: underline;">TERMS OF DELIVERY</h3></th></tr>
                        <tr><td class="terms-delivery-body"><p>{!! str_replace(PHP_EOL, '<br>', $draft->remarks) !!}</p></td></tr>
                    </tbody>
                </table>
            </address>
        </div>
    </div>

    <table class="table table-bordered" width="100%" cellspacing="0">
        <tbody>
            <tr class="row">
                <td class="col-4" style="font-size:20px;"><strong>PO Date: {{ date('d-M-Y', strtotime($draft->podate)) }}</strong></td>
                <td class="col-4" style="font-size:20px;"><strong>Delivery Date: {{ date('d-M-Y', strtotime($draft->del_date)) }}</strong></td>
                <td class="col-4" style="font-size:20px;"><strong>Buyer Ref: {{ $draft->buyer_orderno }}</strong></td>
            </tr>
        </tbody>
    </table>

    <div class="row">
        <div class="col">
            <table class="table table-striped table-bordered" width="100%" cellspacing="0">
                <thead>
                    <tr>
                        <th style="max-width: 50px;">S. No.</th>
                        <th>Description of Goods</th>
                        <th>QTY</th>
                        <th>Rate (₹)</th>
                        <th>Unit</th>
                        <th>Amount (₹)</th>
                        <th>GST (%)</th>
                        @if ($draft->supplier->state_code == '08')
                            <th>CGST (₹)</th>
                            <th>SGST (₹)</th>
                        @else
                            <th>IGST (₹)</th>
                        @endif
                        <th>Total (₹)</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($draft->lines as $key => $line)
                        <tr>
                            <td>{{ $key + 1 }}</td>
                            <td>{{ optional($line->consumable)->name ?? 'N/A' }}<br>@if ($line->description != '') <i>({{ $line->description }})</i> @endif</td>
                            <td style="min-width: 67px;">{{ $line->quantity }}</td>
                            <td>{{ view_amount($line->rate) }}</td>
                            <td style="min-width: 67px;">{{ $line->unit }}</td>
                            <td>{{ view_amount($line->amount) }}</td>
                            <td>{{ $line->gstslab }}</td>
                            @if ($draft->supplier->state_code == '08')
                                <td>{{ view_amount(($line->gstamount) / 2) }}</td>
                                <td>{{ view_amount(($line->gstamount) / 2) }}</td>
                            @else
                                <td>{{ view_amount($line->gstamount) }}</td>
                            @endif
                            <td>{{ view_amount($line->amount + $line->gstamount) }}</td>
                        </tr>
                    @endforeach
                    @php
                        $intraState = ($draft->supplier->state_code ?? '') === '08';
                        $footerSubtotal = (float) ($draft->subTotal ?? 0);
                        $footerCgst = $intraState ? round((float) ($draft->tgst ?? 0) / 2, 2) : 0.0;
                        $footerSgst = $intraState ? round((float) ($draft->tgst ?? 0) / 2, 2) : 0.0;
                        $footerIgst = $intraState ? 0.0 : round((float) ($draft->tgst ?? 0), 2);
                        $preRoundoffTotal = \App\Helpers\PrintAmountHelper::footerTotalSubtotalPlusGst(
                            $footerSubtotal,
                            (float) ($draft->tgst ?? 0),
                            $intraState
                        );
                    @endphp
                    <tr>
                        <td></td><td></td>
                        <td><strong>{{ $draft->tquantity }}</strong></td>
                        <td></td><td></td>
                        <td><strong>₹{{ view_amount($footerSubtotal) }}</strong></td>
                        <td></td>
                        @if ($intraState)
                            <td><strong>₹{{ view_amount($footerCgst) }}</strong></td>
                            <td><strong>₹{{ view_amount($footerSgst) }}</strong></td>
                        @else
                            <td><strong>₹{{ view_amount($footerIgst) }}</strong></td>
                        @endif
                        <td><strong>₹{{ view_amount($preRoundoffTotal) }}</strong></td>
                    </tr>
                    @php
                        $printRoundOff = \App\Helpers\PrintAmountHelper::gstRoundOff(
                            (float) ($draft->subTotal ?? 0),
                            (float) ($draft->tgst ?? 0),
                            (float) ($draft->tamount ?? 0),
                            $intraState
                        );
                    @endphp
                    @include('partials.print-gst-roundoff-row', [
                        'variant' => 'po_consumable',
                        'printRoundOff' => $printRoundOff,
                        'intraState' => $intraState,
                    ])
                    <tr>
                        <td></td><td></td><td></td><td></td><td></td>
                        <td><strong>TOTAL (₹)</strong></td>
                        <td></td>
                        @if ($intraState)
                            <td></td><td></td>
                        @else
                            <td></td>
                        @endif
                        <td id="tamount"><strong>₹</strong><span class="amt"><strong>{{ view_amount($draft->tamount) }}</strong></span></td>
                    </tr>
                    <tr>
                        @if ($intraState)
                            <td colspan="12"><strong>Total Amount in words: </strong><span id="amountInWords"></span></td>
                        @else
                            <td colspan="11"><strong>Total Amount in words: </strong><span id="amountInWords"></span></td>
                        @endif
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    <div class="row">
        <div class="col">
            <table class="table" width="100%" cellspacing="0">
                <tr>
                    <td>
                        <h3>DECLARATION</h3>
                        <p>1. Please get one piece approved before production</p>
                        <p>2. Delivery date MUST be followed</p>
                        <p>3. Any change in quality & delivery term will have financial implications</p>
                        <p>4. Payment term is 30 days from the date of delivery</p>
                        <p>5. Payment will be organised using a/c payee cheques only (No Cash)</p>
                        <p>6. All disputes subject to Jaipur, Rajasthan jurisdiction</p>
                    </td>
                    <td style="vertical-align: bottom;">
                        <h3 class="sig-name">For Global Vision Direct (P) Ltd</h3>
                        @php $signName = $companyDetails->sign_url ?? ''; @endphp
                        @if ($signName && array_key_exists($signName, $fileMap1))
                            <img height="80px" alt="" src="{{ $fileMap1[$signName] }}">
                        @endif
                        <div>___________________________________</div>
                        <div><p>Authorised Signatory</p></div>
                    </td>
                </tr>
            </table>
        </div>
    </div>
</div>

<div class="text-center"><p>This is a Computer Generated Document</p></div>

<script src="{{ asset('ui-vendor/jquery/jquery.min.js') }}"></script>
<script type="text/javascript">
$(document).ready(function () {
    var n = document.getElementById('tamount').textContent.replace(/[^0-9.]/g, '');
    n = parseFloat(n, 10);
    var nums = n.toString().split('.');
    var whole = convertNumberToWords(nums[0]);
    var s = document.getElementById('amountInWords');
    if (nums.length == 2) {
        var fraction = convertNumberToWords(nums[1]);
        s.innerHTML = whole == 0 ? fraction + 'Paise' : (fraction ? whole + 'Rupees and ' + fraction + 'Paise' : whole + 'Rupees');
    } else {
        s.innerHTML = whole == 0 ? whole + 'Zero Rupee' : whole + 'Rupees';
    }
    function convertNumberToWords(amount) {
        var words = ['','One','Two','Three','Four','Five','Six','Seven','Eight','Nine','Ten','Eleven','Twelve','Thirteen','Fourteen','Fifteen','Sixteen','Seventeen','Eighteen','Nineteen','Twenty','Thirty','Forty','Fifty','Sixty','Seventy','Eighty','Ninety'];
        amount = amount.toString();
        var number = amount.split('.').join('').split(',').join('');
        var n_length = number.length, words_string = '';
        if (n_length <= 9) {
            var n_array = [0,0,0,0,0,0,0,0,0], received = number.split('');
            for (var i = 0, j = 9 - n_length; i < n_length; i++, j++) n_array[j] = received[i];
            for (var i = 0, j = 1; i < 9; i++, j++) {
                if ((i == 0 || i == 2 || i == 4 || i == 7) && n_array[i] == 1) {
                    n_array[j] = 10 + parseInt(n_array[j]); n_array[i] = 0;
                }
            }
            var value;
            for (var i = 0; i < 9; i++) {
                value = (i == 0 || i == 2 || i == 4 || i == 7) ? n_array[i] * 10 : n_array[i];
                if (value != 0) words_string += words[value] + ' ';
                if ((i == 1 && value != 0) || (i == 0 && value != 0 && n_array[i + 1] == 0)) words_string += 'Crores ';
                if ((i == 3 && value != 0) || (i == 2 && value != 0 && n_array[i + 1] == 0)) words_string += 'Lakhs ';
                if ((i == 5 && value != 0) || (i == 4 && value != 0 && n_array[i + 1] == 0)) words_string += 'Thousand ';
                if (i == 6 && value != 0) words_string += 'Hundred ';
            }
            words_string = words_string.split('  ').join(' ');
        }
        return words_string;
    }
    var gstin = document.getElementById('gstin');
    if (gstin && gstin.textContent.trim().length === 0) gstin.innerHTML = 'N.A';
});
</script>
@if (isset($print) && $print == 1)
    <script type="text/javascript">document.ready = window.print();</script>
@endif
