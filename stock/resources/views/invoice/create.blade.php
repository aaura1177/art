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
    z-index: 2000;
    /* raise if your sidebar uses a higher z-index */
  }

  /* If your table is inside a .table-responsive wrapper, avoid clipping */
  #mytable.table-responsive {
    overflow: visible;
    /* or overflow: initial; */
  }

  #mytable .dropdown-menu.show {
    max-width: 800px !important;
    min-width: 800px !important;
    left: 0px !important;
  }

  /* (Optional) make the control full width inside cells */
  #mytable .bootstrap-select,
  #mytable .bootstrap-select>#mytable .dropdown-toggle {
    width: 100% !important;
  }
.custom-alert {
    background-color: #ffe6e6; 
    color: #a80000;
    border: 1px solid #ff0000;
    border-radius: 8px;
    font-size: 14px;
    padding: 15px;
    position: relative;
}

.btn-close {
    position: absolute;
    top: 8px;
    right: 8px;
}


</style>

<div class="loader_container">
  <div class="mx-2">
    <div class="row mx-0 my-2">
      <h2>Add Invoice</h2>
    </div>

@if(session('custom_error'))
<div class=" custom-alert alert-danger alert-dismissible fade show mt-3" role="alert">
    <strong>⚠ Error:</strong> {{ session('custom_error') }}
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
</div>
@endif






    <form id='myForm' method="POST" action="{{ url('/invoice/create') }}">
      @csrf

      <!-- Form Starts -->
      <div class="form-group">

        <!-- first row -->
        <div class="row mt-3">
          <div class="col-4">
            <label class="control-label">{{ __('Invoice No.') }}</label>
            <input type="text" id="invoiceno" class="form-control toUpperCase" name="invoiceno" value="{{ old('invoiceno', 'GVD/') }}" required="required" />
          </div>
          <div class="col-4">
            <label class="control-label">{{ __('Invoice Date') }}</label>
            <input type="date" class="form-control" name="date" value="{{ old('date') }}" required="required" />
          </div>
          <div class="col-4">
            <label class="control-label">{{ __('Invoice Type') }}</label>
            <select type="text" class="selectpicker" data-live-search="true" onchange="invtype(this);" name="invoicetype" required="required">
              <option value="0" {{ old('invoicetype') == 0 ? 'selected' : '' }}>Export Invoice</option>
              <option value="1" {{ old('invoicetype') == 1 ? 'selected' : '' }}>Local Invoice</option>
              <option value="2" {{ old('invoicetype') == 2 ? 'selected' : '' }}>Amazon Invoice</option>
              <option value="3" {{ old('invoicetype') == 3 ? 'selected' : '' }}>Tata Invoice</option>
              <option value="4" {{ old('invoicetype') == 4 ? 'selected' : '' }}>Flipkart Invoice</option>
              <option value="5" {{ old('invoicetype') == 5 ? 'selected' : '' }}>JIO Mart Invoice</option>
            </select>
          </div>
        </div>

        <!-- second row -->
        <div class="row mt-3">
          <div class="col-4">
            <label class="control-label">{{ __('Consignee') }}</label><a href="{{ url('/buyer/create')}}" style="float: right;" target="_blank"> (+New)</a>
            <select type="text" class="selectpicker" data-live-search="true" name="consignee" required="required">
              <option value="" selected disabled>Select Consignee</option>
              @if(isset($consignee)) @foreach($consignee as $key => $consignee)
              <option value="{{$consignee->id}}" {{ old('consignee') == $consignee->id ? 'selected' : '' }}>
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
              <option value="{{$buyer->id}}"  {{ old('buyer_id') == $buyer->id ? 'selected' : '' }}>
                {{$buyer->c_name . ' (' . $buyer->code.')'}}
              </option>
              @endforeach @endif
            </select>

          </div>
          <div class="col-4">
            <label class="control-label">{{ __('Status') }}</label>
            <select type="text" class="selectpicker" data-live-search="true" name="exportstatus" required="required" id="exportstatus">
              <option value="0" {{ old('exportstatus') == 0 ? 'selected' : '' }}>Export on payment of IGST</option>
              <option value="1" {{ old('exportstatus') == 1 ? 'selected' : '' }}>Export under LUT</option>
            </select>
          </div>
        </div>

        <!-- third row -->
        <div class="row mt-3">
          <div class="col-4">
            <label class="control-label">{{ __('Buyer Order No.') }}</label><a href="#" data-bs-toggle="modal" data-bs-target="#selBuyerOrderNo" style="float: right;"> (+Select)</a>
            <input type="text" id="buyerorderno" class="form-control toUpperCase" value="{{ old('buyerorderno') }}" name="buyerorderno" readonly required="required" />
          </div>
          <div class="col-4">
            <label class="control-label">{{ __('Container No.') }}</label>
            <input type="text" class="form-control toUpperCase" name="containerno"  value="{{ old('containerno') }}" required="required" />
          </div>
          <div class="col-4">
            <label class="control-label">{{ __('Vehicle No.') }}</label>
            <input type="text" class="form-control" name="vehicleno"  value="{{ old('vehicleno') }}"required="required" />
          </div>
        </div>

        <div class="row mt-3">

          <div class="col-4">
            <label class="control-label">{{ __('Distance.') }}</label>
            <input type="number" step="any" class="form-control" value="{{ old('distance') }}" name="distance" />
          </div>

          <div class="col-4">
            <label class="control-label">{{ __('Transporter Name.') }}</label>
            <input type="text" class="form-control " value="{{ old('transportertame') }}" name="transportertame" />
          </div>
          <div class="col-4">
            <label class="control-label">{{ __('Transporter ID.') }}</label>
            <input type="text" class="form-control" value="{{ old('transporterid') }}" name="transporterid" />
          </div>
        </div>

        <div class="row mt-3">

          <div class="col-4">
            <label class="control-label">{{ __('Doc Date.') }}</label>
            <input type="date" class="form-control" value="{{ old('docdate') }}" name="docdate" />
          </div>

        </div>


        <!-- fourth row -->
        <div class="row mt-3">
          <div class="col-4">
            <label class="control-label">{{ __('E-Way Bill No.') }}</label>
            <input type="text" class="form-control toUpperCase" value="{{ old('ewaybillno') }}" name="ewaybillno" />
          </div>
          <div class="col-4">
            <label class="control-label">{{ __('Kind of Pkgs') }}</label>
            <input type="text" class="form-control" name="pkgs" value="{{ old('pkgs') }}" required="required" />
          </div>
        </div>

        <!-- fifth row -->
        <div class="row mt-3">
          <div class="col-4">
            <label class="control-label">{{ __('Currency') }}</label>
            <select type="text" class="selectpicker" data-live-search="true" id="changecurrency" name="currency" required="required">
             
             <option value=""  disabled>Select Currency</option>
            <option value="$" {{ old('currency') == '$' ? 'selected' : '' }}>$</option>
    <option value="€" {{ old('currency') == '€' ? 'selected' : '' }}>€</option>
    <option value="£" {{ old('currency') == '£' ? 'selected' : '' }}>£</option>
    <option value="₹" {{ old('currency') == '₹' ? 'selected' : '' }}>₹</option>
    <option value="C$" {{ old('currency') == 'C$' ? 'selected' : '' }}>C$</option>
    <option value="A$" {{ old('currency') == 'A$' ? 'selected' : '' }}>A$</option>
            </select>
          </div>
          <div class="col-4">
            <label class="control-label">{{ __('Conversion Rate (₹)') }}</label>
            <input type="number" class="form-control" step="any" onchange="recalculate()" value="{{ old('cunrate', $invoice->cunrate ?? '') }}"  name="cunrate" id="conrate" required />
          </div>
        </div>

        <!-- sixth row -->
        <div class="row mt-3">
          <div class="col-8">
            <label class="control-label">{{ __('Declaration') }}</label>
            <textarea class="form-control" name="declaration">{{ old('declaration', $declarationExport ?? '') }}</textarea>
          </div>
        </div>

        @php
          $showExportOnlySections = (string) old('invoicetype', '0') === '0';
        @endphp
        <div id="invoice-export-terms-block" class="invoice-export-only-fields" style="{{ $showExportOnlySections ? '' : 'display:none;' }}">
        <!-- Separator -->
        <div class="row mt-4">
          <div class="col-12 mt-4">
            <h4>Terms of Delivery
              <hr>
            </h4>
          </div>
        </div>

        <!-- fourth row -->
        <div class="row mt-0">
          <div class="col-4">
            <label class="control-label">{{ __('Price') }}</label>
            <input type="text" class="form-control" name="fob" data-req-when-export="1" value="{{ old('fob', $invoice->fob ?? 'FOB MUNDRA, INDIA') }}"  />
          </div>
          <div class="col-4">
            <label class="control-label">{{ __('Payment Term') }}</label>
            <input type="text" class="form-control" name="payterms" data-req-when-export="1" value="{{ old('payterms', $invoice->payterms ?? '90 Days') }}" />
          </div>
          <div class="col-4">
            <label class="control-label">{{ __('Shipment') }}</label>
            <input type="text" class="form-control" name="shipmentby" data-req-when-export="1" value="{{ old('shipmentby', $invoice->shipmentby ?? 'By Sea') }}" />
          </div>
        </div>
        </div>

        <!-- fifth row -->
        <div class="row mt-3">
          <div class="col-8">
            <label class="control-label">{{ __('Description of Goods') }}</label>
            <textarea class="form-control" name="desgoods" required="required">{{ old('desgoods', $invoice->desgoods ?? '') }}</textarea>
          </div>
        </div>

        <div id="invoice-export-shipping-block" class="invoice-export-only-fields" style="{{ $showExportOnlySections ? '' : 'display:none;' }}">
        <!-- Separator -->
        <div class="row mt-4">
          <div class="col-12 mt-4">
            <h4>Shipping Details
              <hr>
            </h4>
          </div>
        </div>

        <!-- sixth row -->
        <div class="row mt-0">
          <div class="col-4">
            <label class="control-label">{{ __('Pre-Carriage by') }}</label>
            <input type="text" class="form-control" name="carriage" value="{{ old('carriage', $invoice->carriage ?? 'ROAD') }}" data-req-when-export="1" />
          </div>
          <div class="col-4">
            <label class="control-label">{{ __('Place of receipt by PreCarrier') }}</label>
            <input type="text" class="form-control" name="receipt" value="{{ old('receipt', $invoice->receipt ?? 'CONCOR / MUNDRA') }}" data-req-when-export="1" />
          </div>
          <div class="col-4">
            <label class="control-label">{{ __('Shipment Under') }}</label>
            <input type="text" class="form-control" name="shipment" value="{{ old('shipment', $invoice->shipment ?? 'Duty Drawback') }}" />
          </div>
        </div>

        <!-- seventh row -->
        <div class="row mt-3">
          <div class="col-4">
            <label class="control-label">{{ __('Port of Loading') }}</label>
            <input type="text" class="form-control" name="postloading" value="{{ old('postloading', $invoice->postloading ?? 'MUNDRA / INDIA') }}" data-req-when-export="1" />
          </div>

          <div class="col-4">
            <label class="control-label">{{ __('Port of Discharge') }}</label><a href="{{ route('sustainability.port.create')}}" style="float: right;"> (+New)</a>
            <select name="discharge" class="form-control" data-req-when-export="1">
              @foreach($dischargePorts as $port)
              <option value="{{ $port->name }}" {{ old('discharge', $invoice->discharge ?? '') == $port->name ? 'selected' : '' }} >{{ $port->name }}</option>
              @endforeach
            </select>
          </div>

          <div class="col-4">
            <label class="control-label">{{ __('Final Destination') }}</label>
            <select name="destination" class="form-control" data-req-when-export="1">
              <option value="England, UK" {{ old('destination', $invoice->destination ?? '') == 'England, UK' ? 'selected' : '' }} >England, UK</option>
              <option value="NJ/NY - Fairview" {{ old('destination', $invoice->destination ?? '') == 'NJ/NY - Fairview' ? 'selected' : '' }} >NJ/NY - Fairview</option>
              <option value="NJ/NY - Cranbury" {{ old('destination', $invoice->destination ?? '') == 'NJ/NY - Cranbury' ? 'selected' : '' }} >NJ/NY - Cranbury</option>
              <option value="California - Perris" {{ old('destination', $invoice->destination ?? '') == 'California - Perris' ? 'selected' : '' }} >California - Perris</option>
              <option value="Hamburg - Lich" {{ old('destination', $invoice->destination ?? '') == 'Hamburg - Lich' ? 'selected' : '' }} >Hamburg - Lich</option>
              <option value="Hamburg - Magdeburg" {{ old('destination', $invoice->destination ?? '') == 'Hamburg - Magdeburg' ? 'selected' : '' }} >Hamburg - Magdeburg</option>
            </select>
            <!--<input type="text" class="form-control" name="destination" value="England, UK" required="required" /> -->
          </div>
        </div>

        <div class="row mt-3">
          <div class="col-6">
            <label class="control-label">{{ __('Additional Information') }}</label>
            <input type="text" class="form-control" name="additional_info" value="{{ old('additional_info', $invoice->additional_info ?? '') }}" />
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
                <th scope="col" style="min-width: 120px;">GST Slab(%)</th>
                <th scope="col" style="min-width: 150px;">IGST(₹)</th>
              </tr>
            </thead>
         <tbody id="productInvoice">
    @if(old('inv'))
        @foreach(old('inv') as $key => $inv)
        <tr>
            <td id="pr">
                <select class="selectpicker" data-live-search="true"
                        name="inv[{{ $key }}][product]" required>
                    @foreach($allProducts as $p)
                        <option value="{{ $p->id }}"
                            {{ isset($inv['product']) && $inv['product']==$p->id ? 'selected' : '' }}>
                            {{ $p->code }} - {{ $p->name }}
                        </option>
                    @endforeach
                </select>
                <span id="plus{{ $key }}" onclick="showDescription({{ $key }})" style="cursor: pointer;">(+)</span>
                <span id="minus{{ $key }}" onclick="showDescription({{ $key }})" style="cursor:pointer; display:none;">(-)</span>
                <textarea class="form-control" id="descriptionBox{{ $key }}" style="display:none;" rows="2"
                    name="inv[{{ $key }}][descriptionBox]">{{ $inv['descriptionBox'] ?? '' }}</textarea>
            </td>

            <td id="quan{{ $key }}">
                <input type="number" min="1" class="form-control quantity" data-len="{{ $key }}"
                       name="inv[{{ $key }}][quantity]" value="{{ $inv['quantity'] ?? '' }}" onchange="changeWeight(this);" />
            </td>

            <td id="rate{{ $key }}">
                <input type="number" step="any" min="0" class="form-control" data-len="{{ $key }}"
                       name="inv[{{ $key }}][rate]" value="{{ $inv['rate'] ?? '' }}" required onchange="changeWeight(this);" />
            </td>

            <td id="amount{{ $key }}">
                <input type="number" class="form-control amount" name="inv[{{ $key }}][amount]" 
                       value="{{ $inv['amount'] ?? '0.00' }}" readonly/>
            </td>

            <td id="wt{{ $key }}">
                <input type="number" step="any" min="0" class="form-control weight" data-len="{{ $key }}"
                       name="inv[{{ $key }}][weight]" value="{{ $inv['weight'] ?? '' }}" required onchange="changeWeight(this);" />
            </td>

            <td id="subtotalnetwt{{ $key }}">
                <input type="number" step="any" min="0" class="form-control subtotalnetwt" data-len="{{ $key }}"
                       name="inv[{{ $key }}][subtotalnetwt]" value="{{ $inv['subtotalnetwt'] ?? '' }}" readonly onchange="changeWeight(this);" />
            </td>

            <td id="grosswt{{ $key }}">
                <input type="number" step="any" min="0" class="form-control grosswt" data-len="{{ $key }}"
                       name="inv[{{ $key }}][grosswt]" value="{{ $inv['grosswt'] ?? '' }}" required onchange="changeWeight(this);" />
            </td>

            <td id="subTotalGrossWT{{ $key }}">
                <input type="number" step="any" min="0" class="form-control subTotalGrossWT" data-len="{{ $key }}"
                       name="inv[{{ $key }}][subTotalGrossWT]" value="{{ $inv['subTotalGrossWT'] ?? '' }}" readonly onchange="changeWeight(this);" />
            </td>

            <td id="box{{ $key }}">
                <input type="number" step="any" min="0" class="form-control box" data-len="{{ $key }}"
                       name="inv[{{ $key }}][box]" value="{{ $inv['box'] ?? '' }}" required onchange="changeWeight(this);" />
            </td>

            <td id="endBox{{ $key }}">
                <input type="number" step="any" min="0" class="form-control endBox" data-len="{{ $key }}"
                       name="inv[{{ $key }}][endBox]" value="{{ $inv['endBox'] ?? '' }}" required onchange="changeWeight(this);" />
            </td>

            <td id="subTotalBox{{ $key }}">
                <input type="number" step="any" min="0" class="form-control subTotalBox" data-len="{{ $key }}"
                       name="inv[{{ $key }}][subTotalBox]" value="{{ $inv['subTotalBox'] ?? '' }}" readonly onchange="changeWeight(this);" />
            </td>

            <td id="qtybox{{ $key }}">
                <input type="text" class="form-control qtybox" name="inv[{{ $key }}][qtybox]" 
                       value="{{ $inv['qtybox'] ?? '' }}" readonly />
            </td>

            <td id="gstslab{{ $key }}">
                <input type="number" min="0" class="form-control" data-len="{{ $key }}"
                       name="inv[{{ $key }}][gstslab]" value="{{ $inv['gstslab'] ?? '18' }}" required onchange="changeWeight(this);" />
            </td>

            <td id="gstamount{{ $key }}">
                <input type="number" class="form-control gstamount" name="inv[{{ $key }}][gstamount]" 
                       value="{{ $inv['gstamount'] ?? '0.00' }}" readonly/>
            </td>

            <td>
                <button type="button" class="close" onclick="deleterowInvoice(this);" data-bs-dismiss="alert" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </td>
        </tr>
        @endforeach
    @endif
</tbody>

          </table>
        </div>

        <div class="row mt-2">
          <div class="col">
            <input type="button" id="addProductInvoice" class="btn btn-primary" value="Add Product" />
          </div>
        </div>

        <!-- eighth row -->
        <div class="row mt-3">
          <div class="col-4">
            <label class="control-label">{{ _('Additional Shipping Charges')}}&nbsp;(<span class="changeCur">$</span>)</label>
            <input type="number" class="form-control" name="shipping_charges" id="shipping_charges" step="any" value="{{ old('shipping_charges', 0) }}" min='0' onchange="changeWeight(this);" required />
          </div>
          <div class="col-4">
            <label class="control-label">{{ _('Additional Packing Charges')}}&nbsp;(<span class="changeCur">$</span>)</label>
            <input type="number" class="form-control" name="packing_charges" id="packing_charges" step="any" value="{{ old('packing_charges', 0) }}" min='0' onchange="changeWeight(this);" required />
          </div>
          <div class="col-4">
            <label class="control-label">{{ _('Discount')}}&nbsp;(<span class="changeCur">$</span>)</label>
            <input type="number" class="form-control" name="discount" id="discount" step="any" value="{{ old('discount', 0) }}" min='0' onchange="changeWeight(this);" required />
          </div>
        </div>

        <!-- eighth row -->
        <div class="row mt-3">
          <div class="col-4">
            <label class="control-label">{{ __('Total Amount') }}&nbsp;(<span class="changeCur">$</span>)</label>
            <input type="number" class="form-control" name="totalamount" value="{{ old('totalamount', 0) }}" id="totalamount" readonly />
          </div>
          <div class="col-4">
            <label class="control-label">{{ __('Total IGST (₹)') }}</label>
            <input type="text" class="form-control" step="any" name="totalgst" value="{{ old('totalgst', 0) }}" id="totalgst" readonly />
          </div>
          <div class="col-4">
            <label class="control-label">{{ __('Total Amount (₹)') }}</label>
            <input type="number" class="form-control" step="any" name="rateamount" value="{{ old('rateamount', 0) }}" id="rateamount" readonly />
          </div>
        </div>

        <!-- ninth row -->
        <div class="row mt-3">
          <div class="col-4">
            <label class="control-label">{{ __('Total Quantity') }}</label>
            <input type="number" class="form-control" name="tquantity" id="tquantity" value="{{ old('tquantity', 0) }}" readonly />
          </div>
          <div class="col-4">
            <label class="control-label">{{ __('Total Net Weight (kg)') }}</label>
            <input type="number" class="form-control" step="any" name="totalwt" value="{{ old('totalwt', 0) }}" id="totalwt" readonly />
          </div>
          <div class="col-4">
            <label class="control-label">{{ __('Total Gross Wt (kg)') }}</label>
            <input type="number" class="form-control" step="any" name="grosswt" value="{{ old('grosswt', 0) }}" id="grosswt" readonly />
          </div>
        </div>

        <!-- tenth row -->
        <div class="row mt-3">
          <div class="col-4">
            <label class="control-label">{{ __('Total Box') }}</label>
            <input type="number" class="form-control" id="totalbox" value="{{ old('totalbox', 0) }}"  name="totalbox" readonly />
          </div>
        </div>

        <div class="row col-4">
          <button id='submitBtn' type="submit" form='myForm' class="btn btn-primary mt-3">Create Invoice</button>
        </div>
      </div>
    </form>
  </div>

  <!-- MODAL FOR Hardware Bill -->
  <div class="modal fade" id="selBuyerOrderNo" role="dialog">
    <div class="modal-dialog">
      <div class="modal-content">
        <div class="modal-header">
          <h4 class="modal-title">Select BuyerOrder No.</h4>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>

        @csrf
        <div class="modal-body">
          <div class="row">
            <div class="col-6">
              <label>Select BuyerOrder No.</label>
              <select class="selectpicker" data-live-search="true" id="buyer_order_sel" required>
                <option value="" disabled>Select BuyerOrder No.</option>
                @if(isset($packing)) @foreach($packing as $key => $packing)
                <option value="{{$packing->id}}" rel="{{$packing->buyer_order_no}}">{{$packing->buyer_order_no}}</option>
                @endforeach @endif
              </select>
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <a onclick="selBuyerOrderNo()" class="btn btn-success" style="color: #fff;">Select</a>
        </div>
      </div>
    </div>
  </div>
</div>
@endsection

@section('footer')

<script>
  function selBuyerOrderNo() {
    var packing = $('#buyer_order_sel').val();
    var buyer_order = $('#buyer_order_sel').find('option:selected').html();
    var buyerId = $('#buyer_id').val();
    $('input[name=buyerorderno]').val(buyer_order);
    $('#selBuyerOrderNo').modal('hide');

    $.ajax({
      url: "{{ url('/invoice/packing_detail') }}/" + packing,
      method: 'GET',
      data: { buyer_id: buyerId },
      success: function(data) {
        prods = data;
        productRows = 0;
        var $block = "";
        $.each(prods, function(index, value) {
          productRows++;
          $block += '<tr>';
          $block += '<td id="pr">'
          $block += '<select type="text" class="selectpicker" data-live-search="true" onchange="changeHSN(this);" data-len="' + productRows + '" name="inv[' + productRows + '][product]" required="required">';
          $block += '<option value="' + value.product.id + '">' + value.product.code + " - " + value.product.name + '</option></select><span id="plus' + productRows + '" onclick="showDescription(' + productRows + ')" style="cursor: pointer;">(+)</span><span id="minus' + productRows + '" onclick="showDescription(' + productRows + ')" style="cursor:pointer; display:none;">(-)</span><textarea class="form-control" id="descriptionBox' + productRows + '" style="display:none;" rows="2" name="inv[' + productRows + '][descriptionBox]"/></textarea></td>';
          $block += '<td id="quan' + productRows + '"><input type="number" min="1" class="form-control quantity" onchange="changeWeight(this);" data-len="' + productRows + '" name="inv[' + productRows + '][quantity]" value="' + value.quantity + '" /></td>'
          $block += '<td id="rate' + productRows + '"><input type="number" step="any" min="0" class="form-control" onchange="changeWeight(this);" data-len="' + productRows + '" name="inv[' + productRows + '][rate]" value="' + value.product.fob_pricing + '" required /></td>'
          $block += '<td id="amount' + productRows + '"><input type="number" class="form-control amount" name="inv[' + productRows + '][amount]" value="0.00" readonly/></td>'
          $block += '<td id="wt' + productRows + '"><input type="number" step="any" min="0" class="form-control weight" onchange="changeWeight(this);" data-len="' + productRows + '" name="inv[' + productRows + '][weight]" required value="' + value.weight + '" /></td>'
          $block += '<td id="subtotalnetwt' + productRows + '"><input type="number" step="any" min="0" class="form-control subtotalnetwt" onchange="changeWeight(this);" data-len="' + productRows + '" name="inv[' + productRows + '][subtotalnetwt]" value="' + value.subtotalnetwt + '" readonly/></td>'
          $block += '<td id="grosswt' + productRows + '"><input type="number" step="any" min="0" class="form-control grosswt" onchange="changeWeight(this);" data-len="' + productRows + '" name="inv[' + productRows + '][grosswt]" required value="' + value.grosswt + '" /></td>'
          $block += '<td id="subTotalGrossWT' + productRows + '"><input type="number" step="any" min="0" class="form-control subTotalGrossWT" onchange="changeWeight(this);" data-len="' + productRows + '" name="inv[' + productRows + '][subTotalGrossWT]" readonly value="' + value.subtotalgrosswt + '" /></td>'
          $block += '<td id="box' + productRows + '"><input type="number" step="any" class="form-control box" onchange="changeWeight(this);" data-len="' + productRows + '" name="inv[' + productRows + '][box]" min="0" required value="' + value.box + '" /></td>'
          $block += '<td id="endBox' + productRows + '"><input type="number" step="any" class="form-control endBox" onchange="changeWeight(this);" data-len="' + productRows + '" name="inv[' + productRows + '][endBox]" min="0" required value="' + value.endBox + '" /></td>'
          $block += '<td id="subTotalBox' + productRows + '"><input type="number" step="any" class="form-control subTotalBox" onchange="changeWeight(this);" data-len="' + productRows + '" name="inv[' + productRows + '][subTotalBox]" min="0" readonly value="' + value.subTotalBox + '" /></td>'
          $block += '<td id="qtybox' + productRows + '"><input type="text" step="any" class="form-control qtybox" name="inv[' + productRows + '][qtybox]" min="0" readonly value="' + value.qtybox + '" /></td>'
          $block += '<td id="gstslab' + productRows + '"><input type="number" min="0" class="form-control" onchange="changeWeight(this);" data-len="' + productRows + '" name="inv[' + productRows + '][gstslab]" required value="18" /></td>'
          $block += '<td id="gstamount' + productRows + '"><input type="number" class="form-control gstamount" name="inv[' + productRows + '][gstamount]" value="0.00" readonly/></td>'
          $block += '<td><button type="button" class="close" onclick="deleterowInvoice(this);" data-bs-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button></td>';
          $block += '</tr>';
        });
        $("#productInvoice").html($block);
        $('.selectpicker').selectpicker({
          container: '#mytable'
        });
        productRows = 0;
        $.each(prods, function(index, value) {
          productRows++;
          changeWeight($('#quan' + productRows + ' input'));
          
        });
      }
    });
  }

  function invtype(reff) {
    var type = $(reff).val();
    var $cur = $('select[name=currency]');
    var $con = $('#conrate');
    var $statusCol = $('#exportstatus').closest('.col-4');
    var $decl = $('textarea[name=declaration]');

    // Declaration templates from Settings (snapshotted onto the invoice on save)
    var exportDeclaration = @json($declarationExport ?? '');
    var localDeclaration = @json($declarationLocal ?? '');

    if (type == '0') {
      // ✅ Export Invoice
      $statusCol.show();
      $con.val('').prop("readonly", false);

      $cur.off('.lock').prop("disabled", false).val("").selectpicker('refresh');

      // set Export declaration
      $decl.val(exportDeclaration);

    } else {
      // ✅ Local, Amazon, Tata, etc.
      $statusCol.hide();
      $con.val('1').prop("readonly", true);

      $cur.val("₹").prop("disabled", false).selectpicker('refresh')
        .on('mousedown.lock keydown.lock change.lock', function(e) {
          e.preventDefault();
          $(this).val("₹").selectpicker('refresh');
        });

      // set Local declaration
      $decl.val(localDeclaration);
    }

    // Export-only: Terms of Delivery + Shipping Details (+ additional info); keep desgoods visible for all types
    var isExport = (String(type) === '0');
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
    $('#descriptionBox' + ref).toggle();
    $('#plus' + ref).toggle();
    $('#minus' + ref).toggle();
  }


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

  var productRows = 0;
  var products = [];

  $("#conrate, #totalamount").on('change', function() {
    var totalamount = $("#totalamount").val();
    var conrate = $("#conrate").val();
    var output = parseFloat(totalamount) * parseFloat(conrate);
    var totalamount = parseFloat(output).toFixed(2);
    var totalgst = parseFloat($('#totalgst').val());
    if (!isNaN(output)) {
      $("#rateamount").val(totalamount);
    }
  });

  $('#exportstatus').on('change', function() {
    recalculate();
  })

  function validateSubmit() {
    if ($('#productInvoice tr').length < 1) {
      alert("No product added. Add atleast 1 product.");
      event.preventDefault();
    } else {
      var productTableRow = 0;
      $('#productInvoice select').each(function() {
        productTableRow++
        if (!$(this).val()) {
          alert("Product Row " + productTableRow + " empty. Select a product or delete the row.");
          event.preventDefault();
          return false;
        }
      });
    }
  };

  $('#myForm').on('submit', function() {
    if ($('#productInvoice tr').length < 1) {
      alert("No product added. Add atleast 1 product.");
      event.preventDefault();
    } else {
      var productTableRow = 0;
      var submitFlag = 0;
      $('#productInvoice select').each(function() {
        productTableRow++;
        if (!$(this).val()) {
          alert("Product Row " + productTableRow + " empty. Select a product or delete the row.");
          event.preventDefault();
          submitFlag++;
          return false;
        }
      });

      if (submitFlag == 0) {
        $('#submitBtn').prop('disabled', 'true');
      }
    }
  });

  function changeHSN(ref) {
    var len = $(ref).data('len');
    var id = $(ref).val();

    function findProduct(product) {
      return product.id == id;
    }

    if ($('#pr' + id).length) {
      alert('product already added');
      $(ref).prop('selectedIndex', 0);
      $(ref).parent().attr("id", 'pr');
    } else {
      $(ref).parent().attr("id", 'pr' + id);

      function findProduct(product) {
        return product.id == id;
      }

      var product = products.find(findProduct);
      $("#gstslab" + len + " input").val(product.gstslab);
      //$("#quan"+len+" input").attr("max", product.quantity);
      $("#finishingPrice" + len + " input").val(product.finishing_price);
      getFobPrice(id, len);
    }
  }
  //Add Table
  function changeWeight(ref) {
    var len = $(ref).data('len');
    var conrate = $('#conrate').val();
    var quantity = $("#quan" + len + " input").val();
    var rate = $("#rate" + len + " input").val();
    var gstslab = $("#gstslab" + len + " input").val();
    var grosswt = $("#grosswt" + len + " input").val();
    var box = $("#box" + len + " input").val();
    var endBox = $("#endBox" + len + " input").val();
    var subTotalBox = (endBox - box) + 1;
    var pcbox = quantity / subTotalBox;
    var netwt = $("#wt" + len + " input").val();
    var subtotalnetwt = netwt * quantity;
    subtotalnetwt = subtotalnetwt.toFixed(2);
    var subTotalGrossWT = grosswt * quantity;
    subTotalGrossWT = subTotalGrossWT.toFixed(2);
    var amount = rate * quantity;
    var amount = amount.toFixed(2);
    var gst = (amount * gstslab * conrate) / 100;
    gst = parseFloat(gst).toFixed(2);
    if ($('#exportstatus').val() == 1) {
      gst = 0;
    }

    $("#amount" + len + " input").val(amount);
    $("#gstamount" + len + " input").val(gst);
    $("#subtotalnetwt" + len + " input").val(subtotalnetwt);
    $("#subTotalBox" + len + " input").val(subTotalBox);
    $("#qtybox" + len + " input").val(pcbox);
    $("#subTotalGrossWT" + len + " input").val(subTotalGrossWT);

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


    for (var i = 0; i < arrq.length; i++) {
      if (parseFloat(arrq[i].value))
        totq += parseInt(arrq[i].value);
    }
    document.getElementById('tquantity').value = totq;

    for (var i = 0; i < arrgsta.length; i++) {
      if (parseFloat(arrgsta[i].value))
        totgsta += parseFloat(arrgsta[i].value);
    }
    document.getElementById('totalgst').value = totgsta;

    for (var i = 0; i < arrgrosswt.length; i++) {
      if (parseFloat(arrgrosswt[i].value))
        totgrosswt += parseFloat(arrgrosswt[i].value);
    }
    document.getElementById('grosswt').value = totgrosswt.toFixed(2);

    for (var i = 0; i < arrgstamt.length; i++) {
      if (parseFloat(arrgstamt[i].value))
        gstamount += parseFloat(arrgstamt[i].value);
    }

    document.getElementById('totalgst').value = gstamount.toFixed(2);

    for (var i = 0; i < arrgtotamt.length; i++) {
      if (parseFloat(arrgtotamt[i].value))
        totalamount += parseFloat(arrgtotamt[i].value);
      totamt = totalamount + totgsta;
    }

    var shipping_charges = parseFloat($('#shipping_charges').val());
    var packing_charges = parseFloat($('#packing_charges').val());
    var discount = parseFloat($('#discount').val());
    var totalamount = (shipping_charges + packing_charges + totalamount) - discount;
    document.getElementById('totalamount').value = totalamount.toFixed(2);

    for (var i = 0; i < arrwt.length; i++) {
      if (parseFloat(arrwt[i].value))
        totwt += parseFloat(arrwt[i].value);
    }
    document.getElementById('totalwt').value = totwt.toFixed(2);

    for (var i = 0; i < arrbox.length; i++) {
      if (parseFloat(arrbox[i].value))
        totalbox += parseInt(arrbox[i].value);
    }
    document.getElementById('totalbox').value = totalbox;

    var totalamount = $("#totalamount").val();
    var conrate = $("#conrate").val();
    var output = parseFloat(totalamount) * parseFloat(conrate);
    var totalamount = parseFloat(output).toFixed(2);
    if (!isNaN(output)) {
      var totalgst = parseFloat($('#totalgst').val());
      totalamount = parseFloat(totalamount);
      $("#rateamount").val(totalamount);
    }
  }

 function recalculate() {
    var arrtr = $('#productInvoice tr').length;
    var conrate = parseFloat($("#conrate").val()) || 0;
    var totalgst = 0;
    var totalamount = 0;

    if (arrtr > 0) {
        for (var i = 1; i <= arrtr; i++) {
            var amount = parseFloat($("#amount" + i + " input").val()) || 0;
            var gstslab = parseFloat($("#gstslab" + i + " input").val()) || 0;
            var gst = (amount * gstslab * conrate) / 100;

            if ($('#exportstatus').val() == 1) {
                gst = 0;
            }

            $("#gstamount" + i + " input").val(gst.toFixed(2));

            totalgst += gst;
            totalamount += amount;
        }
    }

    var shipping_charges = parseFloat($('#shipping_charges').val()) || 0;
    var packing_charges = parseFloat($('#packing_charges').val()) || 0;
    var discount = parseFloat($('#discount').val()) || 0;

    totalamount = totalamount + shipping_charges + packing_charges - discount;

    $("#totalamount").val(totalamount.toFixed(2));
    $("#totalgst").val(totalgst.toFixed(2));
    $("#rateamount").val((totalamount * conrate).toFixed(2));
}



  function deleterowInvoice(ref) {
    $(ref).parents("tr").remove();
    changeWeight();
  }

  $("#addProductInvoice").click(function() {
    var options = '<option selected disabled>-- SELECT PRODUCT --</option>';

    productRows += 1;

    $.each(products, function(index, value) {
      options += '<option value="' + value.id + '">' + value.code + " - " + value.name + '</option>';
    });


    var $block = "";
    $block += '<tr>';
    $block += '<td id="pr">'
    $block += '<div class=""><select type="text" class="selectpicker" data-live-search="true" onchange="changeHSN(this);" data-len="' + productRows + '" name="inv[' + productRows + '][product]" required="required">';
    $block += options + '</select></div><span id="plus' + productRows + '" onclick="showDescription(' + productRows + ')" style="cursor: pointer;">(+)</span><span id="minus' + productRows + '" onclick="showDescription(' + productRows + ')" style="cursor:pointer; display:none;">(-)</span><textarea class="form-control" id="descriptionBox' + productRows + '" style="display:none;" rows="2" name="inv[' + productRows + '][descriptionBox]"/></textarea></td>';
    $block += '<td id="quan' + productRows + '"><input type="number" min="1" class="form-control quantity" onchange="changeWeight(this);" data-len="' + productRows + '" name="inv[' + productRows + '][quantity]" value="1" /></td>'
    $block += '<td id="rate' + productRows + '"><input type="number" step="any" min="0" class="form-control" onchange="changeWeight(this);" data-len="' + productRows + '" name="inv[' + productRows + '][rate]" value="0.00" required /></td>'
    $block += '<td id="amount' + productRows + '"><input type="number" class="form-control amount" name="inv[' + productRows + '][amount]" value="0.00" readonly/></td>'
    $block += '<td id="wt' + productRows + '"><input type="number" step="any" min="0" class="form-control weight" onchange="changeWeight(this);" data-len="' + productRows + '" name="inv[' + productRows + '][weight]" value="0" required /></td>'
    $block += '<td id="subtotalnetwt' + productRows + '"><input type="number" step="any" min="0" class="form-control subtotalnetwt" onchange="changeWeight(this);" data-len="' + productRows + '" name="inv[' + productRows + '][subtotalnetwt]" value="0" readonly/></td>'
    $block += '<td id="grosswt' + productRows + '"><input type="number" step="any" min="0" class="form-control grosswt" onchange="changeWeight(this);" data-len="' + productRows + '" name="inv[' + productRows + '][grosswt]" value="0" required /></td>'
    $block += '<td id="subTotalGrossWT' + productRows + '"><input type="number" step="any" min="0" class="form-control subTotalGrossWT" onchange="changeWeight(this);" data-len="' + productRows + '" name="inv[' + productRows + '][subTotalGrossWT]" value="0" readonly/></td>'
    $block += '<td id="box' + productRows + '"><input type="number" step="any" class="form-control box" onchange="changeWeight(this);" data-len="' + productRows + '" name="inv[' + productRows + '][box]" value="0" min="0" required /></td>'
    $block += '<td id="endBox' + productRows + '"><input type="number" step="any" class="form-control endBox" onchange="changeWeight(this);" data-len="' + productRows + '" name="inv[' + productRows + '][endBox]" value="0" min="0" required /></td>'
    $block += '<td id="subTotalBox' + productRows + '"><input type="number" step="any" class="form-control subTotalBox" onchange="changeWeight(this);" data-len="' + productRows + '" name="inv[' + productRows + '][subTotalBox]" value="0" min="0" readonly/></td>'
    $block += '<td id="qtybox' + productRows + '"><input type="text" step="any" class="form-control qtybox" name="inv[' + productRows + '][qtybox]" value="1 Pc/Box" min="0" readonly/></td>'
    $block += '<td id="gstslab' + productRows + '"><input type="number" min="0" class="form-control" onchange="changeWeight(this);" data-len="' + productRows + '" name="inv[' + productRows + '][gstslab]" required /></td>'
    $block += '<td id="gstamount' + productRows + '"><input type="number" class="form-control gstamount" name="inv[' + productRows + '][gstamount]" value="0.00" readonly/></td>'
    $block += '<td><button type="button" class="close" onclick="deleterowInvoice(this);" data-bs-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button></td>';
    $block += '</tr>';
    $("#productInvoice").append($block);
    $('.selectpicker').selectpicker({
      container: '#mytable'
    });
  });

  $("#changecurrency").change(function() {
    $(".changeCur").html($(this).val());
  });

  function getFobPrice(id, len) {
    var buyerId = $('#buyer_id').val();
    if (!buyerId) {
      return;
    }
    $.ajax({
      url: "{{ url('/pricing/get-fob-value') }}",
      method: 'GET',
      data: {
        buyerId: buyerId,
        productId: id,
      },
      success: function(data) {
        if (data !== '' && data !== null && typeof data !== 'undefined') {
          $("#rate" + len + " input").val(data);
          changeWeight("#rate" + len + " input");
        }
      }
    });
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
<script>
  (function() {
    const invoiceEl = document.getElementById('invoiceno');
    const buyerEl = document.getElementById('buyerorderno');

    function updateBuyerReadonly() {
      const invoiceVal = (invoiceEl.value || '').toUpperCase();
      // if invoice does NOT contain 'CARTON' -> make readonly
      if (invoiceVal.indexOf('CARTON') === -1) {
        buyerEl.setAttribute('readonly', 'readonly');
      } else {
        buyerEl.removeAttribute('readonly');
      }
    }

    // initial check on load
    document.addEventListener('DOMContentLoaded', updateBuyerReadonly);
    // also update live when invoice input changes
    invoiceEl.addEventListener('input', updateBuyerReadonly);
  })();
</script>

<script>
    document.querySelectorAll('.btn-close').forEach(btn => {
        btn.addEventListener('click', function() {
            this.closest('.custom-alert').style.display = 'none';
        });
    });
</script>

<!-- Script End -->

@endsection