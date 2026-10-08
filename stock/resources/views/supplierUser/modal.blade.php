@extends('layouts.modal')
<style>
  .table td {
    font-size: 13px !important;
  }

  .table th {
    font-size: 15px !important;
  }

  .container {
    width: 1150px !important;
  }
</style>
@include('layouts.partials.modal-print-styles', ['printFontSize' => 8, 'containerWidth' => 1150])
<div>
  <h1 class="text-center">Purchase Order</h1>
  @if($purchaseOrder->supplier_status === 0)
  <a style="position: absolute;right:470px;top:10px;color:#fff;" href="{{ url('/supplier-dashboard/accept-purchase-order') }}/{{$purchaseOrder->id}}" class="btn btn-primary">Accept PO</a>
  @endif
</div>

<div class="container">

  <!-- first row -->
  <div class="row box-space">
    <div class="col-6">
      <h3 style="text-decoration: underline;">INVOICE TO</h3><br />
      @if(isset($companyDetails))
      @php
      $imageName = $companyDetails->logoUrl;
      @endphp

      @if(array_key_exists($imageName, $fileMap1))
      <img width="500px" src="{{ $fileMap1[$imageName] }}" alt="Logo Image" style="width:100px;">
      @else
      <p>Image not found</p>
      @endif
      <p style="font-size: 24px;"><strong>{{ $companyDetails->c_name }}</strong></p>
      <address>
        <p style="font-weight: 700;">{{$companyDetails->address1}}</p>
        <p style="font-weight: 700;">{{$companyDetails->address2}}, {{$companyDetails->city}} - {{$companyDetails->postcode}}, India</p>
        <p>State Name: {{$companyDetails->state}}</p>
        <p>Company PAN: {{$companyDetails->pan}}</p>
        <p>GSTIN/UIN: {{$companyDetails->gstin}}</p>
        <p>E-mail: po@artisanfurniture.net</p>
      </address>
      @endif
    </div>
    <div class="col-6">
      <div class="pull-right" id="supplier">
        @if(isset($purchaseOrder))
        <h1>PO No. {{$purchaseOrder->pono}}</h1>
        <p>PO Revise Date: {{date('d-M-Y',strtotime($purchaseOrder->updated_at))}}</p><br>
        <address>
          <h3 class="box-space" style="text-decoration: underline;">SUPPLIER</h3>
          <p>{{$purchaseOrder->supplier->c_name}}</p>
          <p>{{$purchaseOrder->supplier->address1}}</p>
          <p>{{$purchaseOrder->supplier->address2}}</p>
          <p>{{$purchaseOrder->supplier->city}} - {{$purchaseOrder->supplier->postcode}}</p>
          <p>State Name: {{$purchaseOrder->supplier->state}}</p>
          <p>GSTIN/UIN: <span style="font-weight: 600; font-size: 16px;" id="gstin">{{$purchaseOrder->supplier->gstin}}</span></p>
          <p>Phone: {{$purchaseOrder->supplier->phone1}}</p>
          <p>E-mail: {{$purchaseOrder->supplier->email}}</p>
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
                <p>GSTIN/UIN: {{$companyDetails->gstin}}</p>
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
                <p>{{$purchaseOrder->payterms}}</p>
              </td>
            </tr>
            <tr>
              <th class="text-end box-space">
                <h3 style="text-decoration: underline;">TERMS OF DELIVERY</h3>
              </th>
            </tr>
            <tr>
              <td class="terms-delivery-body">
                <p>{!! str_replace(PHP_EOL,'<br>',$purchaseOrder->remarks) !!}</p>
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
          <strong>PO Date:</strong> {{date('d-M-Y',strtotime($purchaseOrder->podate))}}
        </td>
        <td class="col-3">
          <strong>Delivery Date:</strong> {{date('d-M-Y',strtotime($purchaseOrder->del_date))}}
        </td>
        <td class="col-3">
          <strong>Supplier Ref:</strong> {{$purchaseOrder->ref_supplier}}
        </td>
        <td class="col-3">
          <strong>Buyer Ref:</strong> {{$purchaseOrder->buyer_orderno}}
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
            <th>S. No.</th>
            <th>Description of Goods</th>
            @if(!$is_consumable)
            <th>Image</th>
            <th>EAN</th>
            <th>HSN / SAC</th>
            @endif
            @if($is_consumable)
            <th>HSN </th>
            @endif

            <th>QTY</th>
            <th>Rate (₹)</th>
            <th>Amount (₹)</th>
            @if(!$purchaseOrder->type)
            <th>Discount</th>
            @endif
            <th>GST (%)</th>
            @if($purchaseOrder->supplier->state_code == "08")
            <th>CGST (₹)</th>
            <th>SGST (₹)</th>
            @else
            <th>IGST (₹)</th>
            @endif
            <th>Total (₹)</th>
            <th>Priority</th>
          </tr>
        </thead>

        <tbody>
          @if(!$is_consumable)
          @if(isset($poTable)) @foreach($poTable as $key => $poTable)
          @php
          if ($poTable->discount_type == 'Per'){
          $amountproduct = ($poTable->amount * $poTable->discount) /100;
          $amountpro = $poTable->amount - $amountproduct ;
          }else{
          $amountproduct = $poTable->discount;
          $amountpro = $poTable->amount-$poTable->discount;
          }
          @endphp
          <tr>
            <td>{{++$key}}</td>
            <td>@if(isset($poTable->product->code)) {{$poTable->product->code}} - @endif{{$poTable->product->name}}</td>
            @if(isset($poTable->product->code))
            <td><img width="55px" src="{{ asset('uploads/') }}/image.php?image=/allproducts/{{ $poTable->product->code . '/' . $poTable->product->code .  '-1.jpg' }}&height=90px&width=90px" alt="No Image available" /></td>
            @endif
            <td>{{$poTable->product->EAN}}</td>
            <td>{{$poTable->product->HSN}}</td>
            <td>{{$poTable->quantity}} {{$poTable->unit}}</td>
            <td>{{ view_amount($poTable->rate) }}</td>
            <td>{{ view_amount($poTable->amount) }}</td>
            @if(!$purchaseOrder->type)
            @if($poTable->discount_type == 'Per')

            <td>₹{{ view_amount($amountproduct) }}</td>
            @else

            <td>₹{{ view_amount($amountproduct) }}</td>
            @endif
            @endif

            <td>{{$poTable->gstslab}}</td>
            @if($purchaseOrder->supplier->state_code == "08")
            <td>{{ view_amount(($poTable->gstamount) / 2) }}</td>
            <td>{{ view_amount(($poTable->gstamount) / 2) }}</td>
            @else
            <td>{{ view_amount($poTable->gstamount) }}</td>
            @endif
            <td>{{ view_amount($amountpro + $poTable->gstamount) }}</td>
            <td>{{$poTable->priority}}</td>
          </tr>
          @endforeach @endif
          @else
          @php
            $pocRowsForRollup = isset($poTable) ? collect($poTable) : collect();
          @endphp
          @if(isset($poTable)) @foreach($poTable as $key => $poTable)
          <tr>
            <td>{{++$key}}</td>
            <td>@if(isset($poTable->consumable->code)) {{$poTable->consumable->code}} - @endif{{$poTable->consumable->name}}</td>
            <td>{{$poTable->consumable->EAN ?? ''}}</td>
            <td>{{$poTable->quantity}} {{$poTable->consumable->unitType->name}}</td>
            <td>{{ view_amount($poTable->rate) }}</td>
            <td>{{ view_amount($poTable->amount) }}</td>
            <td>{{$poTable->gstslab}}</td>
            @if($purchaseOrder->supplier->state_code == "08")
            <td>{{ view_amount(($poTable->gstamount) / 2) }}</td>
            <td>{{ view_amount(($poTable->gstamount) / 2) }}</td>
            @else
            <td>{{ view_amount($poTable->gstamount) }}</td>
            @endif
            <td>{{ view_amount($poTable->amount + $poTable->gstamount) }}</td>
            <td>{{$poTable->priority}}</td>
          </tr>
          @endforeach @endif
          @endif

          <tr>
            <td></td>
            @if(!$is_consumable)
            <td></td>
            <td></td>
            @endif
            <td></td>
            <td></td>
            <td><strong>{{$purchaseOrder->tquantity}}</strong></td>
            <td></td>
            @php
              $supPoFooterIntra = ($purchaseOrder->supplier->state_code ?? '') === '08';
              $supPoFooterSubtotal = (float) $purchaseOrder->subTotal + (float) $purchaseOrder->totaldiscount;
              $supPoFooterCgst = $supPoFooterIntra ? round((float) $purchaseOrder->tgst / 2, 2) : 0.0;
              $supPoFooterSgst = $supPoFooterIntra ? round((float) $purchaseOrder->tgst / 2, 2) : 0.0;
              $supPoFooterIgst = $supPoFooterIntra ? 0.0 : round((float) $purchaseOrder->tgst, 2);
              $supPoPreRoundoffTotal = \App\Helpers\PrintAmountHelper::footerTotalSubtotalPlusGst(
                $supPoFooterSubtotal,
                (float) $purchaseOrder->tgst,
                $supPoFooterIntra
              );
            @endphp
            <td><strong>₹{{ view_amount($supPoFooterSubtotal) }}</strong></td>
            @if(!$purchaseOrder->type)
            <td>₹{{ view_amount($purchaseOrder->totaldiscount) }}</td>
            @endif
            <td></td>
            @if($supPoFooterIntra)
            <td><strong>₹{{ view_amount($supPoFooterCgst) }}</strong></td>
            <td><strong>₹{{ view_amount($supPoFooterSgst) }}</strong></td>
            @else
            <td><strong>₹{{ view_amount($supPoFooterIgst) }}</strong></td>
            @endif
            <td><strong>₹{{ view_amount($supPoPreRoundoffTotal) }}</strong></td>
            <td></td>
          </tr>
          @php
            $supPoIntraState = ($purchaseOrder->supplier->state_code ?? '') === '08';
            $supPoTaxable = (float) $purchaseOrder->subTotal + (float) $purchaseOrder->totaldiscount;
            $supPoPrintRoundOff = \App\Helpers\PrintAmountHelper::gstRoundOff(
              $supPoTaxable,
              (float) $purchaseOrder->tgst,
              (float) $purchaseOrder->tamount,
              $supPoIntraState
            );
            $supPoRoVariant = $is_consumable ? 'supplier_po_consumable' : 'supplier_po_furniture';
          @endphp
          @include('partials.print-gst-roundoff-row', [
            'variant' => $supPoRoVariant,
            'printRoundOff' => $supPoPrintRoundOff,
            'intraState' => $supPoIntraState,
            'showHsnCol' => $is_consumable,
            'hideDiscountCol' => (bool) $purchaseOrder->type,
          ])
          <tr>
            <td></td>
            @if(!$is_consumable)
            <td></td>
            <td></td>
            @endif
            <td></td>
            <td></td>
            <td></td>
            <td></td>
            <td></td>
            @if(!$purchaseOrder->type)
            <td></td>
            @endif
            <td><strong>TOTAL (₹)</strong></td>
            @if($purchaseOrder->supplier->state_code == "08")
            <td></td>
            <td></td>
            @else
            <td></td>
            @endif
            <td id="tamount"><strong>₹{{ view_amount($purchaseOrder->tamount) }}</strong></td>
            <td></td>
          </tr>
          <tr>
            @if($purchaseOrder->supplier->state_code == "08")
            <td colspan="15"><strong>Total Amount in words: </strong><span id="amountInWords"></span></td>
            @else
            <td colspan="14"><strong>Total Amount in words: </strong><span id="amountInWords"></span></td>
            @endif
          </tr>
        </tbody>
      </table>
    </div>
  </div>

  @if($is_consumable && (int) ($purchaseOrder->address_option ?? 0) === 100 && isset($pocRowsForRollup) && $pocRowsForRollup->isNotEmpty())
  @php
    $consumableSummaryMe = [];
    $poSumTotMe = 0.0;
    foreach ($pocRowsForRollup as $it) {
        $cid = (int) ($it->consumable_id ?? 0);
        $cname = (string) (optional($it->consumable)->name ?? 'N/A');
        $k = $cid > 0 ? (string) $cid : $cname;
        if (! isset($consumableSummaryMe[$k])) {
            $consumableSummaryMe[$k] = [
                'name' => $cname,
                'unit' => (string) (optional(optional($it->consumable)->unitType)->name ?? ($it->unit ?? '')),
                'total_qty' => 0.0,
                'total_amount' => 0.0,
                'total_gst' => 0.0,
                'total_line' => 0.0,
            ];
        }
        $qty = (float) ($it->quantity ?? 0);
        $amount = (float) ($it->amount ?? 0);
        $gLine = (float) ($it->gstamount ?? 0);
        $lineTotal = $amount + $gLine;
        $consumableSummaryMe[$k]['total_qty'] += $qty;
        $consumableSummaryMe[$k]['total_amount'] += $amount;
        $consumableSummaryMe[$k]['total_gst'] += $gLine;
        $consumableSummaryMe[$k]['total_line'] += $lineTotal;
        $poSumTotMe += $lineTotal;
    }
    uasort($consumableSummaryMe, fn ($a, $b) => strcmp($a['name'] ?? '', $b['name'] ?? ''));
  @endphp
  <div class="row">
    <div class="col" style="margin-top:0.5rem; margin-bottom:1rem;">
      <h4 style="text-decoration: underline; font-size:1.15rem; font-weight:700; margin: 0 0 0.5rem 0;">All consumables (this PO) &mdash; rolled up by item</h4>
      <table class="table table-bordered table-sm" style="max-width:48rem;">
        <thead class="table-light">
          <tr>
            <th class="text-start">Consumable</th>
            <th>Unit</th>
            <th>Total cons. Qty</th>
            <th>Amount (₹)</th>
            <th>GST (₹)</th>
            <th>Total (₹)</th>
          </tr>
        </thead>
        <tbody>
          @foreach ($consumableSummaryMe as $srow)
            <tr>
              <td class="text-start">{{ $srow['name'] ?? '' }}</td>
              <td>{{ $srow['unit'] ?? '' }}</td>
              <td>{{ view_amount($srow['total_qty'] ?? 0) }}</td>
              <td>{{ view_amount($srow['total_amount'] ?? 0) }}</td>
              <td>{{ view_amount($srow['total_gst'] ?? 0) }}</td>
              <td>{{ view_amount($srow['total_line'] ?? 0) }}</td>
            </tr>
          @endforeach
        </tbody>
      </table>
      <p class="mb-0" style="font-size:1.1rem; font-weight:700; border-top:1px solid #000; padding-top:0.75rem;">
        Grand total (this PO, ₹): {{ view_amount($poSumTotMe) }}
      </p>
    </div>
  </div>
  @endif

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
@if(isset($print) && $print==1)
<script type="text/javascript">
  document.ready = window.print();
</script>
@endif
<!-- Script end -->