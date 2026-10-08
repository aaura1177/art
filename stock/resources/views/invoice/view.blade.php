@extends('layouts.app')

@section('content')
<style>
  .message-loading-overlay2>.ace-icon2 {
      position: absolute;
      top: 50% ! important;
      left: 0;
      right: 0;
      text-align: center;
  }

  .bigger-320 {
      font-size: 326% !important;
  }

  .message-loading-overlay2 {
      position: absolute;
      z-index: 14;
      top: 0;
      bottom: 0;
      right: 0;
      left: 0;
      background-color: rgba(255, 255, 255, .5);
      text-align: center;
  }

  .fa-spinner {
      color: #fd7e14;
  }



  /* Keep the dropdown on top of sidebars/navs */
  #mytable.bootstrap-select .dropdown-menu {
    z-index: 2000; /* raise if your sidebar uses a higher z-index */
  }

  /* If your table is inside a .table-responsive wrapper, avoid clipping */
  #mytable.table-responsive {
    overflow: visible; /* or overflow: initial; */
  }
#mytable .dropdown-menu.show {
    max-width: 800px !important;
    min-width: 800px !important;
    left: 0px !important;
}
  /* (Optional) make the control full width inside cells */
  #mytable .bootstrap-select,
  #mytable .bootstrap-select > #mytable .dropdown-toggle {
    width: 100% !important;
  }
  </style>

  <div class="mx-2 loader_container">
    <div class="row mx-0 my-2">
        <h2>Update invoice</h2>
    </div>

    <form method="POST" action="{{ url('/invoice/view/'.$invoice->id)}}">
      @csrf

      <!-- Form Starts -->
      <div class="form-group">

          <!-- first row -->
          <div class="row mt-3">
            <div class="col-4">
              <label class="control-label">{{ __('Invoice No.') }}</label>
              <input type="text" class="form-control" name="invoiceno" required="required" value="{{$invoice->invoiceno}}" />
            </div>
            <div class="col-4">
              <label class="control-label">{{ __('invoice Date') }}</label>
              <input type="date" class="form-control" name="date" value="{{$invoice->date}}" required="required" />
            </div>
            <div class="col-4">
              <label class="control-label">{{ __('Invoice Type') }}</label>
                        @php
                          $invTypeSelected = old('invoicetype', $invoice->invoicetype);
                          $showExportOnlySections = (string) $invTypeSelected === '0';
                        @endphp
                        <select type="text" class="selectpicker" data-live-search="true" onchange="invtype(this);"
                            name="invoicetype" required="required">
                            <option value="0" {{ (string) $invTypeSelected === '0' ? 'selected' : '' }}>Export Invoice</option>
                            <option value="1" {{ (string) $invTypeSelected === '1' ? 'selected' : '' }}>Local Invoice</option>
                            <option value="2" {{ (string) $invTypeSelected === '2' ? 'selected' : '' }}>Amazon Invoice</option>
                            <option value="3" {{ (string) $invTypeSelected === '3' ? 'selected' : '' }}>Tata Invoice</option>
                            <option value="4" {{ (string) $invTypeSelected === '4' ? 'selected' : '' }}>Flipkart Invoice
                            </option>
                            <option value="5" {{ (string) $invTypeSelected === '5' ? 'selected' : '' }}>JIO Mart Invoice
                            </option>
                        </select>
            </div>
          </div>

          <!-- first row -->
          <div class="row mt-3">
            <div class="col-4">
              <label class="control-label">{{ __('Consignee') }}</label><a href="{{ url('/buyer/create')}}" style="float: right;" target="_blank"> (+New)</a>
              <select type="text" class="selectpicker" data-live-search="true" name="consignee" required="required">
                <option value="" selected disabled>Select Consignee</option>
                  @if(isset($consignee)) @foreach($consignee as $key => $consignee)
                    <option value="{{$consignee->id}}" {{($invoice->consignee_id == $consignee->id)?'selected':''}}>
                    {{$consignee->c_name}}
                    </option>
                  @endforeach @endif
              </select>
            </div>
            <div class="col-4">
              <label class="control-label">{{ __('Buyer') }}</label><a href="{{ url('/buyer/create')}}" style="float: right;" target="_blank"> (+New)</a>
              <select type="text" class="selectpicker" data-live-search="true" name="buyer_id" id="buyer_id" required="required">
                <option value="" selected disabled>Select Buyer</option>
                  @if(isset($buyer)) @foreach($buyer as $key => $buyer)
                    <option value="{{$buyer->id}}" {{($invoice->buyer_id == $buyer->id)?'selected':''}}>
                      {{$buyer->c_name . ' (' . $buyer->code.')'}}
                    </option>
                  @endforeach @endif
              </select>
            </div>
            <div class="col-4">
              <label class="control-label">{{ __('Status') }}</label>
              <select type="text" class="selectpicker" data-live-search="true" name="exportstatus" required="required">
                  <option value="0" {{($invoice->exportstatus == 0)?'selected':''}}>Export on payment of IGST</option>
                  <option value="1" {{($invoice->exportstatus == 1)?'selected':''}}>Export under LUT</option>
              </select>
            </div>
          </div>

          <!-- second row -->
          <div class="row mt-3">
            <div class="col-4">
              <label class="control-label">{{ __('BuyerOrder No.') }}</label>
              <input type="text" class="form-control toUpperCase" name="buyerorderno" value="{{$invoice->buyerorderno}}" required="required" />
            </div>
            <div class="col-4">
              <label class="control-label">{{ __('Container No.') }}</label>
              <input type="text" class="form-control toUpperCase" name="containerno" value="{{$invoice->containerno}}" required="required" />
            </div>
            <div class="col-4">
              <label class="control-label">{{ __('Vehicle No.') }}</label>
              <input type="text" class="form-control" name="vehicleno" value="{{$invoice->vehicleno}}" required="required" />
            </div>
          </div>

          <div class="row mt-3">

                    <div class="col-4">
                        <label class="control-label">{{ __('Distance.') }}</label>
                        <input type="number" step="any" class="form-control" value="{{ $invoice->distance }}"
                            name="distance"  />
                    </div>

                    <div class="col-4">
                        <label class="control-label">{{ __('Transporter Name.') }}</label>
                        <input type="text" class="form-control " name="transportertame"
                            value="{{ $invoice->transportertame }}"  />
                    </div>
                    <div class="col-4">
                        <label class="control-label">{{ __('Transporter ID.') }}</label>
                        <input type="text" class="form-control" name="transporterid"
                            value="{{ $invoice->transporterid }}"  />
                    </div>
                </div>

                <div class="row mt-3">

                    <div class="col-4">
                        <label class="control-label">{{ __('Doc Date.') }}</label>
                        <input type="date" class="form-control" name="docdate" value="{{ $invoice->docdate }}"
                             />
                    </div>

                </div>


          <!-- third row -->
          <div class="row mt-3">
            <div class="col-4">
              <label class="control-label">{{ __('E-Way Bill No.') }}</label>
              <input type="text" class="form-control toUpperCase" name="ewaybillno" value="{{$invoice->ewaybillno}}" />
            </div>
            <div class="col-4">
              <label class="control-label">{{ __('Kind of Pkgs') }}</label>
              <input type="text" class="form-control" name="pkgs" value="{{$invoice->pkgs}}" required="required" />
            </div>
          </div>

          <!-- third row -->
          <div class="row mt-3">
            <div class="col-4">
                <label class="control-label">{{ __('Currency') }}</label>
                <select type="text" class="selectpicker" data-live-search="true" id="changecurrency" name="currency" required="required">
                  <option value="" disabled>Select Currency</option>
                  <option value="$" {{($invoice->currency == "$")?'selected':''}}>$</option>
                  <option value="€" {{($invoice->currency == "€")?'selected':''}}>€</option>
                  <option value="£" {{($invoice->currency == "£")?'selected':''}}>£</option>
                  <option value="₹" {{($invoice->currency == "₹")?'selected':''}}>₹</option>
                  <option value="C$" {{($invoice->currency == "C$")?'selected':''}}>C$</option>
                  <option value="A$" {{($invoice->currency == "A$")?'selected':''}}>A$</option>
                </select>
            </div>
            <div class="col-4">
                <label class="control-label">{{ __('Conversion Rate (₹)') }}</label>
                <input type="number" class="form-control" step="any" onchange="recalculate()" name="cunrate" id="conrate" value="{{$invoice->conrate}}" required="required"/>
            </div>
          </div>

          <!-- third row -->
          <div class="row mt-3">
            <div class="col-8">
               <label class="control-label">{{ __('Declaration') }}</label>
               <textarea class="form-control" name="declaration">{{$invoice->declaration}}</textarea>
            </div>
          </div>

          <div id="invoice-export-terms-block" class="invoice-export-only-fields" style="{{ $showExportOnlySections ? '' : 'display:none;' }}">
          <!-- Separator -->
          <div class="row mt-4">
            <div class="col-12 mt-4"><h4>Terms of Delivery<hr></h4></div>
          </div>

          <!-- fourth row -->
          <div class="row mt-0">
            <div class="col-4">
                <label class="control-label">{{ __('Price') }}</label>
                <input type="text" class="form-control" name="fob" data-req-when-export="1" value="{{$invoice->fob}}" />
            </div>
            <div class="col-4">
                <label class="control-label">{{ __('Payment Term') }}</label>
                <input type="text" class="form-control" name="payterms" data-req-when-export="1" value="{{$invoice->payterms}}" />
            </div>
            <div class="col-4">
                <label class="control-label">{{ __('Shipment') }}</label>
                <input type="text" class="form-control" name="shipmentby" data-req-when-export="1" value="{{$invoice->shipmentby}}" />
            </div>
          </div>
          </div>

          <!-- fifth row -->
          <div class="row mt-3">
            <div class="col-8">
                <label class="control-label">{{ __('Description of Goods') }}</label>
                <textarea class="form-control" name="desgoods" required="required">{{$invoice->desgoods}}</textarea>
            </div>
          </div>

          <div id="invoice-export-shipping-block" class="invoice-export-only-fields" style="{{ $showExportOnlySections ? '' : 'display:none;' }}">
          <!-- Separator -->
          <div class="row mt-4">
            <div class="col-12 mt-4"><h4>Shipping Details<hr></h4></div>
          </div>

          <!-- sixth row -->
          <div class="row mt-0">
            <div class="col-4">
                <label class="control-label">{{ __('Pre-Carriage by') }}</label>
                <input type="text" class="form-control" name="carriage" value="{{$invoice->carriage}}" data-req-when-export="1" />
            </div>
            <div class="col-4">
                <label class="control-label">{{ __('Place of receipt by PreCarrier') }}</label>
                <input type="text" class="form-control" name="receipt" value="{{$invoice->receipt}}" data-req-when-export="1" />
            </div>
            <div class="col-4">
                <label class="control-label">{{ __('Shipment Under') }}</label>
                <input type="text" class="form-control" name="shipment" value="{{$invoice->shipment}}" readonly />
            </div>
          </div>

           <!-- seventh row -->
          <div class="row mt-3">
            <div class="col-4">
                <label class="control-label">{{ __('Port of Loading') }}</label>
                <input type="text" class="form-control" name="postloading" value="{{$invoice->postloading}}" data-req-when-export="1" />
            </div>


             <div class="col-4">
              <label class="control-label">{{ __('Port of Discharge') }}</label><a href="{{ route('sustainability.port.create')}}"  style="float: right;" > (+New)</a>
               <select name="discharge" class="form-control" data-req-when-export="1">
                    @foreach($dischargePorts as $port)
                        <option value="{{ $port->name }}" {{ $invoice->discharge == $port->name ? 'selected' : '' }}>{{ $port->name }}</option>
                    @endforeach
                </select>
            </div>

            <div class="col-4">
                <label class="control-label">{{ __('Final Destination') }}</label>
                <input type="text" class="form-control" name="destination" value="{{$invoice->destination}}" data-req-when-export="1" />
            </div>
          </div>

                        <div class="row mt-3">
                                <div class="col-6">
                                        <label class="control-label">{{ __('Additional Information') }}</label>
                                        <input type="text" class="form-control" name="additional_info" value="{{$invoice->additional_info}}" />
                                </div>
                        </div>
          </div>

          <!-- Product Details-->
          <div class="row mt-5 my-3">
              <div class="col-6">
                <h5>Products Details</h5>
              </div>
          </div>
          <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                  <tr id="mytable">
                    <th scope="col" style="min-width: 300px;">Product <a href="{{ url('/product/create')}}" target="_blank"> (+New)</a></th>
                    <th scope="col" style="min-width: 100px;">QTY</th>
                    <th scope="col" style="min-width: 130px;">Rate</th>
                    <th scope="col" style="min-width: 150px;">Amount</th>
                    <th scope="col" style="min-width: 120px;">Net Wt (Kg)</th>
                    <th scope="col" style="min-width: 150px;">Sub Total Net Wt (Kg)</th>
                    <th scope="col" style="min-width: 120px;">Gross Wt (Kg)</th>
                    <th scope="col" style="min-width: 150px;">Sub Total Gross Wt (Kg)</th>
                    <th scope="col" style="min-width: 100px;">Start Box</th>
                    <th scope="col" style="min-width: 100px;">End Box</th>
                    <th scope="col" style="min-width: 100px;">Sub Total Box</th>
                    <th scope="col" style="min-width: 100px;">PC/Box</th>
                    <th scope="col" style="min-width: 120px;">GST Slab (%)</th>
                    <th scope="col" style="min-width: 150px;">IGST(₹)</th>
                  </tr>
                </thead>
                <tbody id="productinvoice">
                  @if(isset($invoiceTable)) @foreach($invoiceTable as $key => $invoiceTable)
                    <tr>
                      <td id="pr{{$invoiceTable->product->id}}">
                        <select type="text" class="selectpicker" data-live-search="true" data-len="{{$key+1}}" name="inv[{{$key+1}}][product]" required="required">
                          <option value="{{$invoiceTable->product_id}}">
                            {{$invoiceTable->product->code}} - {{$invoiceTable->product->name}}
                          </option>'
                        </select>
                        <textarea class="form-control" name="inv[{{$key+1}}][descriptionBox]" placeholder="Enter your description here...">{{$invoiceTable->descriptionBox}}</textarea>
                      </td>
                      <td id="quan{{$key+1}}">
                        <input type="number" min="1" style="width:60px;" class="form-control quantity" onchange="changeWeight(this);" data-len="{{$key+1}}" name="inv[{{$key+1}}][quantity]" value="{{$invoiceTable->quantity}}" />
                        <input type="hidden" data-len="{{$key+1}}" name="inv[{{$key+1}}][consumed]" value="{{$invoiceTable->quantity-$invoiceTable->remqty}}" />
                      </td>
                      <td id="rate{{$key+1}}">
                        <input type="number" min="0" step="any" class="form-control rate" onchange="changeWeight(this);" name="inv[{{$key+1}}][rate]" data-len="{{$key+1}}" value="{{$invoiceTable->rate}}" />
                      </td>
                      <td id="amount{{$key+1}}">
                        <input type="number" class="form-control amount" name="inv[{{$key+1}}][amount]" value="{{$invoiceTable->amount}}" readonly />
                      </td>
                      <td id="wt{{$key+1}}">
                        <input type="number" min="0" step="any" class="form-control weight" onchange="changeWeight(this);" data-len="{{$key+1}}" name="inv[{{$key+1}}][weight]" value="{{$invoiceTable->weight}}" />
                      </td>
                      <td id="subtotalnetwt{{$key+1}}">
                        <input type="number" min="0" step="any" class="form-control subtotalnetwt" onchange="changeWeight(this);" name="inv[{{$key+1}}][subtotalnetwt]" value="{{$invoiceTable->subtotalnetwt}}" readonly/>
                      </td>
                      <td id="grosswt{{$key+1}}">
                        <input type="number" min="0" step="any" class="form-control grosswt" onchange="changeWeight(this);" data-len="{{$key+1}}" name="inv[{{$key+1}}][grosswt]" value="{{$invoiceTable->grosswt}}" />
                      </td>
                      <td id="subTotalGrossWT{{$key+1}}">
                        <input type="number" min="0" step="any" class="form-control subTotalGrossWT" onchange="changeWeight(this);" name="inv[{{$key+1}}][subTotalGrossWT]" value="{{$invoiceTable->subtotalgrosswt}}" readonly/>
                      </td>
                      <td id="box{{$key+1}}">
                        <input type="number" min="0" class="form-control box" onchange="changeWeight(this);" data-len="{{$key+1}}" name="inv[{{$key+1}}][box]" value="{{$invoiceTable->box}}" />
                      </td>
                      <td id="endBox{{$key+1}}">
                        <input type="number" min="0" class="form-control endBox" onchange="changeWeight(this);" data-len="{{$key+1}}" name="inv[{{$key+1}}][endBox]" value="{{$invoiceTable->endbox}}" />
                      </td>
                      <td id="subTotalBox{{$key+1}}">
                        <input type="number" min="0" class="form-control subTotalBox" onchange="changeWeight(this);" name="inv[{{$key+1}}][subTotalBox]" value="{{$invoiceTable->subtotalbox}}" readonly/>
                      </td>
                       <td id="qtybox{{$key+1}}">
                        <input type="text" min="0" class="form-control qtybox" name="inv[{{$key+1}}][qtybox]" value="{{$invoiceTable->qtybox}}" />
                      </td>
                      <td id="gstslab{{$key+1}}">
                        <input type="number" min="0" class="form-control gstslab" data-len="{{$key+1}}" onchange="changeWeight(this);" name="inv[{{$key+1}}][gstslab]" value="{{$invoiceTable->gstslab}}" />
                      </td>
                      <td id="gstamount{{$key+1}}">
                        <input type="number" class="form-control gstamount" name="inv[{{$key+1}}][gstamount]" value="{{$invoiceTable->gstamount}}" step="any"/>
                      </td>
                      <td>
                        <button type="button" class="close" onclick="deleterowinvoice(this);" data-bs-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                      </td>
                    </tr>
                  @endforeach @endif
                </tbody>
            </table>
          </div>

          <div class="row mt-2">
              <div class="col">
                <input type="button" id="addProductinvoice" class="btn btn-primary" value="Add Product" />
              </div>
          </div>

          <!-- eighth row -->
          <div class="row mt-3">
                <div class="col-4">
                  <label class="control-label">{{ __('Additional Shipping Charges')}}&nbsp;(<span class="changeCur">{{$invoice->currency}}</span>)</label>
                  <input type="number" class="form-control" name="shipping_charges" id="shipping_charges" step="any" value='{{$invoice->shipping_charges}}' min='0' onchange="changeWeight(this);" required/>
                </div>
                <div class="col-4">
                  <label class="control-label">{{ __('Additional Packing Charges')}}&nbsp;(<span class="changeCur">{{$invoice->currency}}</span>)</label>
                  <input type="number" class="form-control" name="packing_charges" id="packing_charges" step="any" value='{{$invoice->packing_charges}}' min='0' onchange="changeWeight(this);" required/>
                </div>
                <div class="col-4">
                  <label class="control-label">{{ __('Discount')}}&nbsp;(<span class="changeCur">{{$invoice->currency}}</span>)</label>
                  <input type="number" class="form-control" name="discount" id="discount" step="any" value='{{$invoice->discount}}' min='0' onchange="changeWeight(this);" required/>
                </div>
          </div>

          <!-- eighth row -->
          <div class="row mt-3">
                <div class="col-4">
                    <label class="control-label">{{ __('Total Amount') }}&nbsp;(<span class="changeCur">{{$invoice->currency}}</span>)</label>
                    <input type="number" class="form-control" name="totalamount" id="totalamount" value="{{$invoice->totalamount}}" readonly />
                </div>
                <div class="col-4">
                    <label class="control-label">{{ __('Total IGST (₹)') }}</label>
                    <input type="text" class="form-control" step="any" name="totalgst" id="totalgst" value="{{$invoice->totalgst}}" />
                </div>
                <div class="col-4">
                    <label class="control-label">{{ __('Total Amount (₹)') }}</label>
                    <input type="number" class="form-control" step="any" name="rateamount" id="rateamount" value="{{$invoice->rateamount}}" readonly />
                </div>

          </div>

          <!-- ninth row -->
          <div class="row mt-3">
                <div class="col-4">
                    <label class="control-label">{{ __('Total Quantity') }}</label>
                    <input type="number" class="form-control" name="tquantity" id="tquantity" value="{{$invoice->totalquantity}}" readonly />
                </div>
                <div class="col-4">
                    <label class="control-label">{{ __('Net Weight (kg)') }}</label>
                    <input type="number" class="form-control" step="any" name="totalwt" id="totalwt" value="{{$invoice->totalwt}}" readonly />
                </div>
                <div class="col-4">
                    <label class="control-label">{{ __('Gross Wt(kg)') }}</label>
                    <input type="number" class="form-control" step="any" name="grosswt" id="grosswt" value="{{$invoice->totalgrosswt}}" readonly />
                </div>
          </div>

           <!-- tenth row -->
          <div class="row mt-3">
                <div class="col-4">
                  <label class="control-label">{{ __('Total Box') }}</label>
                  <input type="number" class="form-control" name="totalbox" id="totalbox" value="{{$invoice->totalbox}}" readonly />
                </div>
          </div>

          <div class="row col-4">
                <button type="submit" onclick="validateSubmit();" class="btn btn-primary mt-3">Update invoice</button>
          </div>

      </div>
    </form>
  </div>

@endsection

@section('footer')
<!-- Script start for Statecode & GSTIN button -->
<script>

    var previousInvoiceType = null;

    function invtype(reff) {
  var type = String($(reff).val()); // normalize
  var $cur = $('select[name=currency]');
  var $con = $('#conrate');
  var $statusCol = $('#exportstatus').closest('.col-4');
  var $decl = $('textarea[name=declaration]');

  // Declaration templates from Settings
  var exportDeclaration = @json($declarationExport ?? '');
  var localDeclaration  = @json($declarationLocal ?? '');
  var typeChanged = previousInvoiceType !== null && previousInvoiceType !== type;

  // Always remove any previous lock handlers before we add (or not) new ones
  $cur.off('.lock');

  if (type === '0') {
    // ✅ Export Invoice
    $statusCol.show();

    // Make exchange rate editable; do NOT clear existing value on edit
    $con.prop('readonly', false);

    // Allow currency to be freely chosen; do NOT clear existing selection
    $cur.prop('disabled', false).selectpicker('refresh');

    // Keep saved declaration on load; replace from Settings only when type changes
    if (typeChanged) {
      $decl.val(exportDeclaration);
    } else if (!$decl.val()) {
      $decl.val(exportDeclaration);
    }

  } else {
    // ✅ Local / Marketplaces
    $statusCol.hide();

    // Rate is 1 and read-only
    $con.val('1').prop('readonly', true);

    // Force INR and prevent changing it
    $cur.val('₹')
        .prop('disabled', false)
        .selectpicker('refresh')
        .on('mousedown.lock keydown.lock change.lock', function(e){
          e.preventDefault();
          $(this).val('₹').selectpicker('refresh');
        });

    if (typeChanged) {
      $decl.val(localDeclaration);
    } else if (!$decl.val()) {
      $decl.val(localDeclaration);
    }
  }

  previousInvoiceType = type;

  // Export-only: Terms of Delivery + Shipping Details (+ additional info)
  var isExport = (type === '0');
  var $exportFieldBlocks = $('#invoice-export-terms-block, #invoice-export-shipping-block');
  if (isExport) {
    $exportFieldBlocks.show();
    $exportFieldBlocks.find('[data-req-when-export="1"]').prop('required', true);
  } else {
    $exportFieldBlocks.find('[data-req-when-export="1"]').prop('required', false);
    $exportFieldBlocks.hide();
  }
}


   $(document).ready(function() {
    invtype($('select[name=invoicetype]')[0]);
});

function showDescription(ref) {
  $('#descriptionBox'+ref).toggle();
  $('#plus'+ref).toggle();
  $('#minus'+ref).toggle();
}

$(document).ready(function() {
  $("#conrate").on('change',function() {
    $("#totalamount").each(function (index) {
      var totalamount = $("#totalamount").eq(index).val();
      var conrate = $("#conrate").eq(index).val();
      var output = parseFloat(totalamount) * parseFloat(conrate);
      var totalamount = parseFloat(output).toFixed(2);
      if (!isNaN(output)) {
        $("#rateamount").eq(index).val(totalamount);
      }
    });
  });


});

var trcount = $('#productinvoice tr').length;
var productRows = trcount;
var products = [];

$(document).ready(function() {
  $(".loader_container").append(
        '<div class="message-loading-overlay2"><i class="fa-spin ace-icon2 fa fa-spinner orange2 bigger-320"></i></div>'
      );
        $.ajax({
      'url': "{{ url('/purchaseOrder/data') }}",
      'method': 'GET'
        }).done(function(data) {
      if (data) {
                products = data.product;
          }
    $(".loader_container").find(".message-loading-overlay2").remove();

    });
});

function validateSubmit(){
  if($('#productinvoice tr').length<1) {
    alert( "No product added. Add atleast 1 product." );
    event.preventDefault();
  }

  else{
    var productTableRow = 0;
    $('#productinvoice select').each(function(){
      productTableRow++
      if(!$(this).val()){
        alert( "Product Row "+productTableRow+ " empty. Select a product or delete the row." );
        event.preventDefault();
        return false;
      }
    });
  }
};

function changeHSN(ref) {
  var len = $(ref).data('len');
  var id = $(ref).val();

  function findProduct(product) {
    return product.id == id;
  }

   if($('#pr'+id).length){
      alert('product already added');
      $(ref).prop('selectedIndex',0);
      $(ref).parent().attr("id",'pr');
    }

    else{
      $(ref).parent().attr("id",'pr'+id);
      function findProduct(product) {
        return product.id == id;
      }

      var product = products.find(findProduct);
      $("#gstslab"+len+" input").val(product.gstslab);
      $("#quan"+len+" input").attr("max", product.quantity);
       getFobPrice(id,len);
    }
}

//Add Table
  function changeWeight(ref) {
    var len = $(ref).data('len');
    var conrate = $('#conrate').val();
    var quantity = $("#quan"+len+" input").val();
    var rate = $("#rate"+len+" input").val();
    var gstSlab = $("#gstslab"+len+" input").val();
    var grosswt = $("#grosswt"+len+" input").val();
    var box = $("#box"+len+" input").val();
    var endBox = $("#endBox"+len+" input").val();
    var subTotalBox = (endBox-box)+1;
    var pcbox = quantity/subTotalBox;
    var netwt = $("#wt"+len+" input").val();
    var subtotalnetwt = netwt*quantity;
    subtotalnetwt = subtotalnetwt.toFixed(2);
    var subTotalGrossWT = grosswt*quantity;
    subTotalGrossWT = subTotalGrossWT.toFixed(2);
    var amount = rate*quantity;
    var amount = amount.toFixed(2);
    var gst = (amount*gstSlab*conrate)/100;
    gst = parseFloat(gst).toFixed(2);

    $("#amount"+len+" input").val(amount);
    $("#gstamount"+len+" input").val(gst);
    $("#subtotalnetwt"+len+" input").val(subtotalnetwt);
    $("#subTotalBox"+len+" input").val(subTotalBox);
    $("#qtybox"+len+" input").val(pcbox);
    $("#subTotalGrossWT"+len+" input").val(subTotalGrossWT);

    var arrq = document.getElementsByClassName('quantity');
    var arrgsta = document.getElementsByClassName('igst');
    var arrgtotamt = document.getElementsByClassName('amount');
    var arrwt = document.getElementsByClassName('subtotalnetwt');
    var arrgrosswt = document.getElementsByClassName('subTotalGrossWT');
    var arrgstamt = document.getElementsByClassName('gstamount');
    var arrbox = document.getElementsByClassName('subTotalBox');

    var totq = 0;
    var totgsta = 0;
    var totamt = 0;
    var totalamount = 0;
    var totwt = 0;
    var totgrosswt = 0;
    var gstamount = 0;
    var totalbox = 0;


      for(var i=0;i<arrq.length;i++){
          if(parseFloat(arrq[i].value))
              totq += parseInt(arrq[i].value);
      }
          document.getElementById('tquantity').value = totq;

      for(var i=0;i<arrgsta.length;i++){
          if(parseFloat(arrgsta[i].value))
              totgsta += parseFloat(arrgsta[i].value);
      }
          document.getElementById('totalgst').value = totgsta;

      for(var i=0;i<arrgrosswt.length;i++){
          if(parseFloat(arrgrosswt[i].value))
              totgrosswt += parseFloat(arrgrosswt[i].value);
      }
          document.getElementById('grosswt').value = totgrosswt.toFixed(2);

      for(var i=0;i<arrgstamt.length;i++){
          if(parseFloat(arrgstamt[i].value))
              gstamount += parseFloat(arrgstamt[i].value);
      }

        document.getElementById('totalgst').value = gstamount.toFixed(2);

      for(var i=0;i<arrgtotamt.length;i++){
          if(parseFloat(arrgtotamt[i].value))
              totalamount += parseFloat(arrgtotamt[i].value);
              totamt = totalamount + totgsta;
      }
          var shipping_charges = parseFloat($('#shipping_charges').val());
          var packing_charges = parseFloat($('#packing_charges').val());
          var discount = parseFloat($('#discount').val());
          var totalamount = (shipping_charges + packing_charges + totalamount) - discount;
          document.getElementById('totalamount').value = totalamount.toFixed(2);

      for(var i=0;i<arrwt.length;i++){
          if(parseFloat(arrwt[i].value))
              totwt += parseFloat(arrwt[i].value);
      }
          document.getElementById('totalwt').value = totwt.toFixed(2);

       for(var i=0;i<arrbox.length;i++){
          if(parseInt(arrbox[i].value))
              totalbox += parseInt(arrbox[i].value);
      }
          document.getElementById('totalbox').value = totalbox;

      var totalamount = $("#totalamount").val();
      var conrate = $("#conrate").val();
      var output = parseFloat(totalamount) * parseFloat(conrate);
      var totalamount = parseFloat(output).toFixed(2);
      if (!isNaN(output)) {
        $("#rateamount").val(totalamount);
      }
  }

  function recalculate(){
    var arrtr = $('#productinvoice tr').length;
    var conrate = parseFloat($("#conrate").val()).toFixed(2);
    var totalgst = 0;
    console.log(arrtr);
    if(arrtr>0){
      for( var i=1;i<=arrtr;i++){
         var amount = $("#amount"+i+" input").val();
         amount = parseFloat(amount).toFixed(2);
         var gstslab = $("#gstslab"+i+" input").val();
         gstslab = parseFloat(gstslab).toFixed(2);
         var gst = parseFloat(amount*gstslab*conrate/100).toFixed(2);
         totalgst = gst;
         console.log(gst);
         console.log(totalgst);
         $("#gstamount"+i+" input").val(gst);
      }

      var totalAmount = parseFloat($('#totalamount').val()).toFixed(2);
      var totalAmountInr = parseFloat(totalAmount).toFixed(2)*parseFloat(conrate).toFixed(2);
      $('#rateamount').val(totalAmountInr);
      $('#totalgst').val(totalgst);
    }
  }

function deleterowinvoice(ref) {
  $(ref).parents("tr").remove();
  changeWeight();
}

$("#addProductinvoice").click(function() {
  var options = '<option selected disabled>-- SELECT PRODUCT --</option>';

    productRows += 1;

    $.each(products, function(index, value) {
        options += '<option value="' + value.id + '">' + value.code + " - " + value.name + '</option>';
    });

  var $block = "";
      $block += '<tr>';
      $block += '<td id="pr">'
      $block += '<select type="text" class="selectpicker" data-live-search="true" onchange="changeHSN(this);" data-len="' + productRows + '" name="inv['+productRows+'][product]" required="required">';
      $block += options + '</select><span id="plus'+productRows+'" onclick="showDescription('+productRows+')" style="cursor: pointer;">(+)</span><span id="minus'+productRows+'" onclick="showDescription('+productRows+')" style="cursor:pointer; display:none;">(-)</span><textarea class="form-control" id="descriptionBox'+productRows+'" style="display:none;" rows="2" name="inv['+productRows+'][descriptionBox]"/></textarea></td>';
      $block += '<td id="quan'+productRows+'"><input type="number" min="1" style="width:60px;" class="form-control quantity" onchange="changeWeight(this);" data-len="' + productRows + '" name="inv['+productRows+'][quantity]" value="1" /></td>'
      $block += '<td id="rate'+productRows+'"><input type="number" step="any" min="0" class="form-control" onchange="changeWeight(this);" data-len="' + productRows + '" name="inv['+productRows+'][rate]" value="0.00" required /></td>'
      $block += '<td id="amount'+productRows+'"><input type="number" class="form-control amount" name="inv['+productRows+'][amount]" value="0.00" readonly/></td>'
      $block += '<td id="wt'+productRows+'"><input type="number" step="any" min="0" class="form-control weight" onchange="changeWeight(this);" data-len="' + productRows + '" name="inv['+productRows+'][weight]" value="0" required /></td>'
      $block += '<td id="subtotalnetwt'+productRows+'"><input type="number" step="any" min="0" class="form-control subtotalnetwt" onchange="changeWeight(this);" data-len="' + productRows + '" name="inv['+productRows+'][subtotalnetwt]" value="0" readonly/></td>'
      $block += '<td id="grosswt'+productRows+'"><input type="number" min="0" step="any" class="form-control grosswt" onchange="changeWeight(this);" data-len="' + productRows + '" name="inv['+productRows+'][grosswt]" value="0" required /></td>'
      $block += '<td id="subTotalGrossWT'+productRows+'"><input type="number" step="any" min="0" class="form-control subTotalGrossWT" onchange="changeWeight(this);" data-len="' + productRows + '" name="inv['+productRows+'][subTotalGrossWT]" value="0" readonly/></td>'
      $block += '<td id="box'+productRows+'"><input type="number" step="any" class="form-control box" onchange="changeWeight(this);" data-len="' + productRows + '" name="inv['+productRows+'][box]" value="0" min="0" required /></td>'
      $block += '<td id="endBox'+productRows+'"><input type="number" step="any" class="form-control endBox" onchange="changeWeight(this);" data-len="' + productRows + '" name="inv['+productRows+'][endBox]" value="0" min="0" required/></td>'
      $block += '<td id="subTotalBox'+productRows+'"><input type="number" step="any" class="form-control subTotalBox" onchange="changeWeight(this);" data-len="' + productRows + '" name="inv['+productRows+'][subTotalBox]" value="0" min="0" readonly/></td>'
      $block += '<td id="qtybox'+productRows+'"><input type="text" step="any" class="form-control qtybox" name="inv['+productRows+'][qtybox]" value="1 Pc/Box" min="0" /></td>'
      $block += '<td id="gstslab'+productRows+'"><input type="number" min="0" class="form-control" onchange="changeWeight(this);" data-len="' + productRows + '" name="inv['+productRows+'][gstslab]" required /></td>'
      $block += '<td id="gstamount'+productRows+'"><input type="number" class="form-control gstamount" name="inv['+productRows+'][gstamount]" value="0.00" readonly/></td>'
      $block += '<td><button type="button" class="close" onclick="deleterowinvoice(this);" data-bs-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button><input type="hidden" data-len="' + productRows + '" name="inv['+productRows+'][consumed]" value="" /></td>';
      $block += '</tr>';
      $("#productinvoice").append($block);
      $('.selectpicker').selectpicker({ container: '#mytable' });
});

$("#changecurrency").change(function () {
  $(".changeCur").html($(this).val());
});

function getFobPrice(id,len){
  var buyerId = $('#buyer_id').val();
  if (!buyerId) {
    return;
  }
  $.ajax({
                url: "{{ url('/pricing/get-fob-value') }}",
                method: 'GET',
    data :{
      buyerId : buyerId,
      productId : id,
    },
                success: function(data){
      if (data !== '' && data !== null && typeof data !== 'undefined') {
        $("#rate"+len+" input").val(data);
        changeWeight("#rate"+len+" input");
      }
    }});
}

function refreshAllFobRates() {
  $('#productInvoice tr').each(function() {
    var $select = $(this).find('select[name*="[product]"]');
    var productId = $select.val();
    var len = $select.data('len');
    if (productId && len) {
      getFobPrice(productId, len);
    }
  });
}

$('#buyer_id').on('changed.bs.select', refreshAllFobRates);

</script>
<!-- Script End -->

@endsection