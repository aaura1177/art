@extends('layouts.modal')
<style>
.table-striped.table td{
	font-size: 12px !important;
  padding:5px;
}
.table-striped.table th{
	font-size: 12px !important;
  padding:5px;
}
.supplier_class{
	width:450px;
}
</style>
@include('layouts.partials.modal-print-styles', ['printFontSize' => 10])
  <div>
    <h1 class="text-center">Supplier Invoice</h1>
  </div>

  <div class="container">

    <!-- first row -->
    <div class="row box-space">
        <div class="col-6">
          
          @if(isset($companyDetails))
            
            @if(isset($purchaseOrder))
            <h1>Invoice No. {{$supplierInvoice->supplier_invoice_number}}</h1>
                <p>Internal Invoice No.: {{$supplierInvoice->internal_invoice_number}}<br>
                Invoice Date: {{date('d-M-Y',strtotime($supplierInvoice->created_at))}}</p><br>
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
             @endif
            @endif
        </div>
        <div class="col-6">
            <div class="pull-right" id="supplier">
              <h3 style="text-decoration: underline;">INVOICE TO</h3><br/>
              @php
            $imageName = $companyDetails->logoUrl;
        @endphp
        
        @if(array_key_exists($imageName, $fileMap1))
        <img src="{{ $fileMap1[$imageName] }}" alt="Logo Image" class='supplier_class' style="width:100px">
        @else
            <p>Image not found</p>
        @endif              @if(isset($purchaseOrder))
                
                 @endif
              @if(isset($companyDetails))

                <address>
                  <h2 style="font-weight: 500;">{{ $companyDetails->c_name }}</h2>
                  <p style="font-weight: 700;">{{$companyDetails->address1}}</p>
                  <p style="font-weight: 700;">{{$companyDetails->address2}}, {{$companyDetails->city}} - {{$companyDetails->postcode}}, India</p>
                  <p>State Name: {{$companyDetails->state}}</p>
                  <p>Company PAN: {{$companyDetails->pan}}</p>
                  <p>GSTIN/UIN: {{$companyDetails->gstin}}</p>
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
                  <!-- <td><h3 style="text-decoration: underline;">DISPATCH TO</h3></td> -->
                </tr>
                <tr>
                  <td><p>Global Vision Direct (P) Ltd</p></td>
                </tr>
                <tr>
                  <td><p>{{$companyDetails->address1}}</p></td>
                </tr>
                <tr>
                  <td><p>{{$companyDetails->address2}},{{$companyDetails->city}} </p></td>
                </tr>
                <tr>
                  <td><p>{{$companyDetails->postcode}}</p></td>
                </tr>
                <tr>
                  <td><p>GSTIN/UIN: {{$companyDetails->gstin}}</p></td>
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
            <strong>PO No:</strong> {{$purchaseOrder->pono}}
          </td>
          <td class="col-4">
            <strong>PO Date:</strong> {{date('d-M-Y',strtotime($purchaseOrder->podate))}}
          </td>
          <td class="col-4">
            <strong>Project / Service completion date:</strong> {{date('d-M-Y',strtotime($purchaseOrder->del_date))}}
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
                  <th style="max-width: 40px;">Sl. No.</th>
                  <th>Description of Goods</th>
                 
                 
                  
                  @if($supplierInvoice->purchase_order_type == 'Furniture')
                    <th style="max-width: 40px;">QTY</th>
                    <th>Rate (₹)</th>
                  @else
                    @if($purchaseOrder->type == 1)
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
                  <th>GST (%)</th>
                @if($purchaseOrder->supplier->state_code == "08")
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
                  ?>
                  @if($supplierInvoice->purchase_order_type == 'Furniture')
                    @if(isset($poTable)) @foreach($poTable as $key => $poTable) @if($poTable->quantity > 0)
                      <?php
                      $tgst = $tgst+$poTable->gstamount;
                      ?>
                    <tr style="background-color: #dfdfdf ;font-weight: bold;">
                      <td>{{++$key}}</td>
                      <td>{{$poTable->ServiceProduct->name}}</td>
                     @if($poTable->unit == 'Hours')
                      <td style="min-width: 67px;">{{$poTable->quantity }} {{$poTable->unit}}</td>
                      @else
                      <td style="min-width: 67px;">{{$poTable->percentage}} %</td>
                      @endif
                      <td>{{ str_replace(',', '', number_format((float) optional($poTable->potable)->rate, 2)) }}</td>
                      <td>{{ str_replace(',', '', number_format((float) $poTable->amount, 2)) }}</td>
                      <td>{{optional($poTable->potable)->gstslab}}</td>
                    @if($purchaseOrder->supplier->state_code == "08")
                      <td>{{str_replace(",", "", number_format(($poTable->gstamount)/2,2))}}</td>
                      <td>{{str_replace(",", "", number_format(($poTable->gstamount)/2,2))}}</td>
                    @else
                      <td>{{ str_replace(',', '', number_format((float) $poTable->gstamount, 2)) }}</td>
                    @endif
                      <td>{{str_replace(",", "", number_format(($poTable->amount+$poTable->gstamount),2))}}</td>
                    </tr>
                    @if ($poTable->sub_service_product && count($poTable->sub_service_product))
                    @foreach ($poTable->sub_service_product as $sub)
                    <tr>
                      <td></td>
                      <td>->{{$sub->sub_name}}</td>
                     @if($poTable->unit == 'Hours')
                      <td style="min-width: 67px;">{{$sub->sub_quantity }}</td>
                      @else
                      <td style="min-width: 67px;">{{$sub->sub_percentage}} %</td>
                      @endif
                      <td>{{ str_replace(',', '', number_format((float) $sub->sub_rate, 2)) }}</td>
                      <td>{{ str_replace(',', '', number_format((float) $sub->sub_amount, 2)) }}</td>
                      <td>{{optional($poTable->potable)->gstslab}}</td>
                    @if($purchaseOrder->supplier->state_code == "08")
                      <td>{{str_replace(",", "", number_format(($sub->sub_gstamount)/2,2))}}</td>
                      <td>{{str_replace(",", "", number_format(($sub->sub_gstamount)/2,2))}}</td>
                    @else
                      <td>{{$sub->gstamount}}</td>
                    @endif
                      <td>{{str_replace(",", "", number_format(($sub->sub_gstamount),2))}}</td>
                    </tr>
@endforeach
@endif

                   @endif @endforeach @endif
                  @else
                    @if($purchaseOrder->type == 1)
                      @if(isset($poTable)) @foreach($poTable as $key => $poTable) @if($poTable->quantity > 0)
                        <?php
                        $tgst = $tgst+$poTable->gstamount;
                        ?>
                        <tr>
                          <td>{{++$key}}</td>
                          <td>{{$poTable->potable->consumable->name}}</td>
                          <td></td>
                          <td style="min-width: 67px;">{{$poTable->quantity - $poTable->returned_qty}} {{$poTable->unit}}</td>
                          <td>{{$poTable->potable->rate}}</td>
                          <td>{{$poTable->amount}}</td>
                          <td>{{$poTable->potable->gstslab}}</td>
                        @if($purchaseOrder->supplier->state_code == "08")
                          <td>{{str_replace(",", "", number_format(($poTable->gstamount)/2,2))}}</td>
                          <td>{{str_replace(",", "", number_format(($poTable->gstamount)/2,2))}}</td>
                        @else
                          <td>{{$poTable->gstamount}}</td>
                        @endif
                          <td>{{str_replace(",", "", number_format(($poTable->amount+$poTable->gstamount),2))}}</td>
                        </tr>
                      @endif 
                      @endforeach @endif
                @endif

                   
                  @endif
                @php
                  $siServiceIntraState = ($purchaseOrder->supplier->state_code ?? '') === '08';
                  $siServiceDisplayTgst = (float) ($supplierInvoice->tgst ?? $tgst);
                  $siServiceFooterSubtotal = (float) $supplierInvoice->subTotal;
                  $siServiceFooterCgst = $siServiceIntraState ? round($siServiceDisplayTgst / 2, 2) : 0.0;
                  $siServiceFooterSgst = $siServiceIntraState ? round($siServiceDisplayTgst / 2, 2) : 0.0;
                  $siServiceFooterIgst = $siServiceIntraState ? 0.0 : round($siServiceDisplayTgst, 2);
                  $siServicePreRoundoffTotal = \App\Helpers\PrintAmountHelper::footerTotalSubtotalPlusGst(
                    $siServiceFooterSubtotal,
                    $siServiceDisplayTgst,
                    $siServiceIntraState
                  );
                  $siServicePrintRoundOff = \App\Helpers\PrintAmountHelper::gstRoundOff(
                    $siServiceFooterSubtotal,
                    $siServiceDisplayTgst,
                    (float) ($supplierInvoice->tamount ?? 0),
                    $siServiceIntraState
                  );
                @endphp
                <tr>
                  <td></td>
                  @if($supplierInvoice->purchase_order_type == 'Furniture')
                    <td>Total</td>
                    <td><strong>{{$supplierInvoice->tquantity}}</strong></td>
                  @endif
                  <td></td>
                  <td><strong>₹{{ view_amount($siServiceFooterSubtotal) }}</strong></td>
                  <td></td>
                  @if($siServiceIntraState)
                    <td><strong>₹{{ view_amount($siServiceFooterCgst) }}</strong></td>
                    <td><strong>₹{{ view_amount($siServiceFooterSgst) }}</strong></td>
                  @else
                    <td><strong>₹{{ view_amount($siServiceFooterIgst) }}</strong></td>
                  @endif
                  <td><strong>₹{{ view_amount($siServicePreRoundoffTotal) }}</strong></td>
                </tr>
                @include('partials.print-gst-roundoff-row', [
                  'variant' => 'si_consumable',
                  'printRoundOff' => $siServicePrintRoundOff,
                  'intraState' => $siServiceIntraState,
                ])
                <tr>
                  <td></td>
                  @if($supplierInvoice->purchase_order_type == 'Furniture')
                    <td></td>
                    <td></td>
                  @endif
                  <td></td>
                  <td><strong>TOTAL (₹)</strong></td>
                  <td></td>
                  @if($siServiceIntraState)
                    <td></td>
                    <td></td>
                  @else
                    <td></td>
                  @endif
                  <td id="tamount"><strong>₹{{ view_amount($supplierInvoice->tamount ?? 0) }}</strong></td>
                </tr>
                <tr>
                @if($purchaseOrder->supplier->state_code == "08")
                  <td colspan="11"><strong>Total Amount in words: </strong><span id="amountInWords"></span></td>
                @else
                  <td colspan="11"><strong>Total Amount in words: </strong><span id="amountInWords"></span></td>
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

              <td style="vertical-align: bottom;" class="pull-right">
                        <h3 class="sig-name">For {{$purchaseOrder->supplier->c_name}}</h3>
                  <div style="padding-top: 45px;">___________________________________</div>
                  <div><p>Authorised Signatory</p></div>
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
  $(document).ready(function(){

    var n = document.getElementById('tamount').textContent;
    n = n.replace(/[^0-9.]/g,'');
    n = parseFloat(n,10);

    var nums = n.toString().split('.')

    var whole = convertNumberToWords(nums[0]);

      if (nums.length == 2)
      {
          var fraction = convertNumberToWords(nums[1]);
          if(whole == 0)
          {
              var s=document.getElementById('amountInWords');
              s.innerHTML = fraction + 'Paise';
          }
          else
          {
              if(fraction)
              {
                  var s=document.getElementById('amountInWords');
                  s.innerHTML = whole + 'Rupees and ' + fraction + 'Paise';
              }
              else
              {
                  var s=document.getElementById('amountInWords');
                  s.innerHTML = whole + 'Rupees';
              }
          }

      }



      else
      {
        if(whole==0)
         {
            var s=document.getElementById('amountInWords');
            s.innerHTML = whole + 'Zero Rupee';
         }
        else
         {
            var s=document.getElementById('amountInWords');
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
    if(gstin.length != 0){
      document.getElementById("gstin").innerHTML = gstin;
    }
    else{
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
