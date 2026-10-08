@extends('layouts.app')

@section('content')
  <style type="text/css">
    .ck-editor__editable_inline {
    min-height: 300px;
    }
  </style>
  <div class="row mx-3 my-2">
    <h2>Invoice</h2>

    @hasrole('admin')
    <div class="col-12 d-flex flex-wrap justify-content-end align-items-center" style="gap: 5px;">
    <a class="btn btn-warning" style="color: #fff;" data-bs-toggle="modal" data-bs-target="#mysalesRegister" href="{{ url('/invoice/saleRegister')}}">Sales Register Export</a>
    <a class="btn btn-success" style="color: #fff;" data-bs-toggle="modal" data-bs-target="#mySmallHadwareBill" href="{{ url('/invoice/dustcoverbill')}}">Small Hardware Bill</a>
    <a class="btn btn-success" style="color: #fff;" data-bs-toggle="modal" data-bs-target="#myHadwareBill" href="{{ url('/invoice/hardwarebill')}}">Hardware Bill</a>
    <a class="btn btn-success" style="color: #fff;" data-bs-toggle="modal" data-bs-target="#mydateModal" href="{{ url('/invoice/exportcsv')}}">Download CSV</a>
    <a id="contractor_bill_link" class="btn btn-success" data-bs-toggle="modal" onclick="contractorBill()" href="#">Contractor Bill</a>
    <a id="upholestry_bill_link" class="btn btn-success" data-bs-toggle="modal" onclick="upholestryBill()" href="#">Upholstery Bill</a>
    <a id="upholestry_bill_link" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#myUpholsteryBill" href="#">Upholstery Bill Download</a>
    <a id="corner_bill_link" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#myCornerBill" href="#">Corner and L Bill Download</a>
    <a id="corner_bill_link" class="btn btn-success" onclick="exportSales()" href="#">Export Sales</a>
    <a class="btn btn-success" data-bs-toggle="modal" data-bs-target="#exportSaleModal" href="{{ url('/invoice/exportSalesSheet')}}">Export Sales Sheet</a>
    <a class="btn btn-info" style="color: #fff;" href="javascript:void(0);" onclick="openMonthEndPoModal(getSelectedInvoiceIds())">Month End PO</a>
    <!-- <a class="btn btn-secondary" style="color: #fff;" href="javascript:void(0);" onclick="openHardwareMonthEndPoModal(getSelectedInvoiceIds())">Hardware Month End PO</a> -->

    <a class="btn btn-primary" style="color: #fff;" href="{{ url('/invoice/create')}}">Add Invoice</a>
    <div class="col-md-4">
     <form method="GET" action="{{ route('invoice.index') }}">
    <select class="form-select" name="invoice_type" onchange="this.form.submit()" 
        style="padding: .5rem; border: 1px solid #b3b1b1; color: #444444; border-radius: .2rem;">
        
        <option value="0" {{ request('invoice_type') === '0' ? 'selected' : '' }}>Export</option>
        <option value="1" {{ request('invoice_type') === '1' ? 'selected' : '' }}>Local</option>
        <option value="2" {{ request('invoice_type') === '2' ? 'selected' : '' }}>E-Commerce</option>
        <option value="all" {{ request('invoice_type') === 'all' || request('invoice_type') === null ? 'selected' : '' }}>All</option>
    </select>
</form>





    </div>
    </div>
    @endhasrole

    @hasrole('office')
    <div class="col-12 d-flex justify-content-end">
    <a id="corner_bill_link" class="btn btn-success"
      onclick="exportSales()" href="#">Export Sales</a>
	</div>
    @endhasrole
  </div>

  <div class="card mb-3">
    <div class="card-body">
    <div class="table-responsive">
      <table class="table table-bordered" id="dataTable" width="100%" cellspacing="0">
      <thead>
        <tr>
        <th>Inv. No.</th>
        <th data-orderable="false" style="padding:0px;"><input type="checkbox" id="checkedAll"
          onclick="selectAllInvoice()" style="width:50px;height:50px"></th>
        <th>Buyer</th>
        <th>Date</th>
        <th>Total QTY</th>
        <th>Buyer Order No.</th>
        <th>Total Amount</th>
        <th>Amount (₹)</th>
        <th>Status</th>
        <th>Tally status</th>
        <th>Invoice Type</th>
        <th style="min-width:120px;">Options</th>
        <th></th>
        </tr>
      </thead>
      <tbody>
        @if(isset($invoice)) @foreach($invoice as $key => $inv)
      <tr>
      <td>{{$inv->invoiceno}}</td>
      <td style="padding:0px;"><input class="checkSingle" type="checkbox" id="invoice_{{$inv->id}}"
      onclick="selectInvoice($(this))" style="width:50px;height:50px"></td>
      <td>{{@$inv->buyer->c_name}}</td>
      <td>{{date('d-M-Y', strtotime($inv->date))}}</td>
      <td>{{$inv->totalquantity}}</td>
      <td>{{$inv->buyerorderno}}</td>
      <td>{{$inv->currency}}{{$inv->totalamount}}</td>
      <td>{{$inv->rateamount}}</td>
      <td>
          @if($inv->is_canceled == 1)
            <span class="badge bg-danger">Cancelled</span>
          @elseif($inv->status == 0)
            {{'Pending'}}
          @elseif($inv->status == 1)
            {{'Complete'}}
          @endif
      </td>
      
      <td>
      @if ($inv->tally_status == 0)
      {{ 'Pending' }}
      @endif
      @if ($inv->tally_status == 1)
      {{ 'Complete' }}
      @endif
      </td>
      <td>
      @if ($inv->invoicetype == 0)
      {{ 'Export' }}
      @endif
      @if ($inv->invoicetype == 1)
      {{ 'Local' }}
      @endif
      @if ($inv->invoicetype == 2)
      {{ 'Amazon' }}
      @endif
      @if ($inv->invoicetype == 3)
      {{ 'Tata' }}
      @endif
      @if ($inv->invoicetype == 4)
                                            {{ 'Flipkart' }}
                                        @endif
                                           @if ($inv->invoicetype == 5)
                                            {{ 'JIO Mart' }}
                                        @endif
      </td>
      <td>
      <span class="tool-tip1" data-bs-toggle="tooltip" data-bs-placement="top" title="View">
      <a class="btn btn-primary" style="color: #fff;" href="{{ URL::to('/invoice/modal/' . $inv->id)}}"
        target="_blank"><i class="fa fa-eye"></i></a>
      </span>

      @if($inv->is_canceled == 0)
      @if($inv->status != 1)
      @hasrole('admin')
        <span class="tool-tip1" data-bs-toggle="tooltip" data-bs-placement="top" title="Edit">
        <a href="{{ URL::to('/invoice/view/' . $inv->id)}}" class="btn btn-info">
        <i class="fa fa-edit"></i>
        </a>
      </span>
      @endhasrole
      @endif
            <span class="tool-tip1" data-bs-toggle="tooltip" data-bs-placement="top"
            title="{{($inv->invoicetype == '1') ? 'Can\'t Generate PackingSheet.' : 'Generate PackingSheet'}}">
            <a class="btn btn-warning" style="color: #fff;"
              href="{{ URL::to('/invoice/modalpackingsheet/' . $inv->id)}}" {{$inv->invoicetype == 1 ? 'disabled' : ''}}
              target="_blank"><i class="fa fa-file-invoice"></i></a>
            </span>
            <span class="tool-tip1" data-bs-toggle="tooltip" data-bs-placement="top" title="Print">
            <a class="btn btn-success invoice-print-open" style="color: #fff;" href="#"
              data-invoice-id="{{$inv->id}}"><i
              class="fa fa-print"></i></a>
            </span>
            <span class="tool-tip1" data-bs-toggle="tooltip" data-bs-placement="top" title="Download CSV">
            <a class="btn btn-success" style="color: #fff;"
              href="{{ URL::to('/invoice/exportEachCSV/' . $inv->id)}}"><i class="fa fa-file-download"></i></a>
            </span>
            <span class="tool-tip1" data-bs-toggle="tooltip" data-bs-placement="top" title="Update Shipping Bill">
            <a class="btn {{($inv->shipping_bill_no != "" && $inv->shipping_bill_date != "" && $inv->ewaybillno != "" && $inv->ewaybilldate != "" && $inv->port_code != "" && $inv->shipping_exchange_rate != "") ? 'btn-success' : 'btn-danger'}}"
              style="color: #fff;" href="#" data-bs-toggle="modal" data-bs-target="#shippingBill"
              onclick="shipBill({{$inv->id}}, '{{$inv->shipping_bill_no}}', '{{$inv->shipping_bill_date}}', '{{$inv->ewaybillno}}', '{{$inv->ewaybilldate}}', '{{$inv->port_code}}', '{{$inv->shipping_exchange_rate}}', '{{$inv->bl_no}}', '{{$inv->bl_date}}')"><i
              class="fa fa-shipping-fast"></i></a>
            </span>

            <span class="tool-tip1" data-bs-toggle="tooltip" data-bs-placement="top" title="Invoice Export Fields">
            <a class="btn {{($inv->shipping_bill_no != "" && $inv->shipping_bill_date != "" && $inv->ewaybillno != "" && $inv->ewaybilldate != "" && $inv->port_code != "" && $inv->shipping_exchange_rate != "") ? 'btn-success' : 'btn-warning'}}"
              style="color: #fff;" href="#" data-bs-toggle="modal" data-bs-target="#invoiceExport_{{$inv->id}}"
              onclick="invExp({{$inv->id}}, '{{$inv->shipping_bill_no}}', '{{$inv->shipping_bill_date}}', '{{$inv->booking_value}}', '{{$inv->shipdawn}}', '{{$inv->inrat_booking_value}}', '{{$inv->tax_type}}')"><i
              class="fa fa-file-invoice"></i></a>


            </span>

            <span class="tool-tip1" data-bs-toggle="tooltip" data-bs-placement="top" title="E-Invoice Fields">
            <a class="btn {{($inv->irn != "" && $inv->ack_no != "" && $inv->ack_date != "" && $inv->einvoice_qr != "" && $inv->lr_rr_no != "") ? 'btn-success' : 'btn-warning'}}"
              style="color: #fff;" href="#" data-bs-toggle="modal" data-bs-target="#einvoicing"
              onclick="einvUpdate({{$inv->id}}, '{{$inv->irn}}', '{{$inv->ack_no}}', '{{$inv->ack_date}}', '{{$inv->einvoice_qr}}', '{{$inv->lr_rr_no}}')"><i
              class="fa fa-file-invoice"></i></a>
            </span>
            <span class="tool-tip1" data-bs-toggle="tooltip" data-bs-placement="top" title="Invoice Email">
            <a class="btn btn-success" style="color: #fff;" href="#" data-bs-toggle="modal" data-bs-target="#invoiceEmail"
              onclick="invEmail({{$inv->id}},'{{$inv->invoiceno}}')"><i class="fa fa-envelope"></i></a>


            </span>

            @php
            $invoiceRelationCount = $inv->stockout->count();
            @endphp
            @if($invoiceRelationCount == 0)
              @if($inv->status == 0 && $inv->tally_status == 0)
                @if(auth()->user()->hasRole('admin') || auth()->user()->can('delete-invoice'))
                  <span class="tool-tip1" data-bs-toggle="tooltip" data-bs-placement="top" title="Delete">
                    <button class="btn btn-danger" data-bs-toggle="modal" onclick="deleteModal('{{$inv->id}}')">
                      <i class="fa fa-trash"></i>
                    </button>
                  </span>
                @endif
              @endif
            @endif

            @if((auth()->user()->hasRole('admin') || auth()->user()->can('cancel-invoice')) && $inv->canBeCanceled())
              <span class="tool-tip1" data-bs-toggle="tooltip" data-bs-placement="top" title="Cancel">
                  <button type="button" class="btn btn-warning"
                      data-invoice-id="{{$inv->id}}"
                      data-next-invoiceno="{{ $inv->suggestedCloneInvoiceNumber() }}"
                      onclick="cancelModal(this)">
                      <i class="fa fa-times"></i>
                  </button>
              </span>
            @endif

            
            <span class="tool-tip1" data-bs-toggle="tooltip" data-bs-placement="top" title="Sales Register">
            <a class="btn btn-info" href="{{ url('/invoice/sale-register?id=' . $inv->id)}}"><i
              class="fa fa-file"></i></a>
            </span>
            
            <span class="tool-tip1" data-bs-toggle="tooltip" data-bs-placement="top" title="Update Tally Status">
            <a class="btn btn-success" style="color: #fff;" href="javascript:void(0);"
              onclick="tallyStatus('{{$inv->id}}')">Tally Status</a>
            </span>
            
           
        @endif
      </td>

      <td>


      <!-- MODAL FOR Invoice Report -->
      <div class="modal fade" id="invoiceExport_{{$inv->id}}" role="dialog">
      <div class="modal-dialog">
        <div class="modal-content" style="width:770px">
        <div class="modal-header">
        <h4 class="modal-title">Invoice Export Fields</h4>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <form method="POST" action="{{ url('/invoice/updateInvoiceExport') }}">
        @csrf
        <div class="modal-body">
        <div class="row">
        <input id="sb_inv_id_val{{$inv->id}}" type="hidden" class="form-control" name="id" />
        <div class="col-6">
        <label>BOOKING VALUE</label>
        <input id="booking_value{{$inv->id}}" step="any" type="number" class="form-control"
        name="booking_value" />
        </div>
        <div class="col-6">
        <label>BOOKING RATE</label>
        <input id="fbc{{$inv->booking_rate}}" type="text" class="form-control" name="booking_rate"
        value="{{$inv->booking_rate}}" />
        </div>
        </div>
        <div class="row">
        <div class="col-6">
        <label>SHIPPING RATE</label>
        <input step="any" type="number" class="form-control" name="shipping_rate"
        value="{{$inv->conrate}}" disabled />
        </div>

        </div>
        <div class="row">
        <div class="col-6">
        <label>SHIPPED ON</label>
        <input id="shipdawn{{$inv->id}}" type="date" class="form-control" name="shipdawn"
        value="{{$inv->shipdawn}}" />
        </div>
        <div class="col-6">
        <label>TAX TYPE</label>
        <input id="tax_type{{$inv->id}}" type="text" class="form-control" name="tax_type"
        value="IGST" />
        </div>
        </div>

        <!-- Product details -->
        <div class="table-responsive" style="margin-top:20px;">
        <table class="table table-hover">
        <thead>
        <tr id="mytable{{$inv->id}}">
        <th scope="col" style="min-width: 100px;">Realisation Date</th>
        <th scope="col" style="min-width: 100px;">Realisation FC</th>
        <th scope="col" style="min-width: 100px;">Rate</th>
        <th scope="col" style="min-width: 100px;">Bank Reference</th>
        <th scope="col" style="min-width: 70px;">FBC</th>
        <th></th>
        </tr>
        </thead>

        <tbody id="invoiceTable{{$inv->id}}">

        @if(isset($inv->invexport)) @foreach($inv->invexport as $key => $invExp)

        <tr>
        <td>
        <input type="date" class="form-control realisation_date"
        name="ie[{{$key + 1}}][realisation_date]" value="{{$invExp->realisation_date}}" />
        </td>

        <td id="realisation_fc{{$key + 1}}">
        <input type="text" class="form-control realisation_fc"
        name="ie[{{$key + 1}}][realisation_fc]" data-len="{{$key + 1}}"
        value="{{($invExp->realisation_fc == null) ? $inv->totalamount : $invExp->realisation_fc}}" />
        </td>
        <td>
        <input type="number" class="form-control rate" name="ie[{{$key + 1}}][rate]"
        value="{{$invExp->rate}}" />
        </td>
        <td>
        <input type="text" class="form-control bank_reference"
        name="ie[{{$key + 1}}][bank_reference]" value="{{$invExp->bank_reference}}" />
        </td>
        <td>
        <input type="text" class="form-control fbc" name="ie[{{$key + 1}}][fbc]"
        value="{{$invExp->fbc}}" />
        </td>
        <td>
        <a href="javascript:void(0);" class="close" onclick="deleteRow(this);">&times;</a>
        </td>

        </tr>
      @endforeach @endif
        </tbody>

        </table>
        </div>
        <div class="row mt-2">
        <div class="col">
        <input type="button" id="addMore{{$inv->id}}" class="btn btn-primary" value="Add More" />
        </div>
        </div>

        </div>

        <div class="modal-footer">
        <button type="submit" class="btn btn-success">Save</button>
        </div>
        </form>
        </div>
      </div>
      </div>

      </td>
      </tr>
      @endforeach @endif
      </tbody>
      </table>
    </div>
    </div>
  </div>

  <!-- MODAL FOR TALLY STATUS -->
  <div class="modal fade" id="statusTally" role="dialog">
    <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
      <h4 class="modal-title">Update Confirmation</h4>
      <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <form id="statusTallyFORM" method="POST" action="">
      @csrf
      <div class="modal-body">
        <div class="row">
        <div class="col-6">
          <select class="form-control" name="tallystatus" required>
          <option value="">Select Status</option>
          <option value="0">Pending</option>
          <option value="1">Completed</option>
          </select>
        </div>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-default" data-bs-dismiss="modal">Cancel</button>
        <button type="submit" id="" class="btn btn-success">Update</button>
      </div>
      </form>
    </div>
    </div>
  </div>

  <!-- MODAL FOR DATE -->
  <div class="modal fade" id="mydateModal" role="dialog">
    <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
      <h4 class="modal-title">Enter Date</h4>
      <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <form method="POST" action="{{ url('/invoice/exportcsv') }}">
      @csrf
      <div class="modal-body">
        <div class="row">
        <div class="col-6">
          <input class="form-control" type="date" name="fsd" id="fsd" required value="" />
        </div>
        <div class="col-6">
          <input class="form-control" type="date" name="fed" id="fed" required value="" />
        </div>
        </div>
      </div>
      <div class="modal-footer">
        <button type="submit" onclick="return validate()" class="btn btn-success">Download</button>
      </div>
      </form>
    </div>
    </div>
  </div>
  <!-- MODAL FOR DATE -->
  <div class="modal fade" id="mysalesRegister" role="dialog">
    <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
      <h4 class="modal-title">Enter Date</h4>
      <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <form method="POST" action="{{ url('/invoice/saleRegister') }}">
      @csrf
      <div class="modal-body">
        <div class="row">
        <div class="col-6">
          <input class="form-control" type="date" name="fersd" id="fersd" required value="" />
        </div>
        <div class="col-6">
          <input class="form-control" type="date" name="ferd" id="ferd" required value="" />
        </div>
        </div>
      </div>
      <div class="modal-footer">
        <button type="submit" onclick="return validate()" class="btn btn-success">Download</button>
      </div>
      </form>
    </div>
    </div>
  </div>

  <!-- MODAL FOR DATE -->
  <div class="modal fade" id="exportSaleModal" role="dialog">
    <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
      <h4 class="modal-title">Enter Date</h4>
      <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <form method="POST" action="{{ url('/invoice/exportSalesSheet') }}">
      @csrf
      <div class="modal-body">
        <div class="row">
        <div class="col-6">
          <input class="form-control" type="date" name="fesd" id="fesd" required value="" />
        </div>
        <div class="col-6">
          <input class="form-control" type="date" name="feed" id="feed" required value="" />
        </div>
        </div>
      </div>
      <div class="modal-footer">
        <button type="submit" onclick="return validateExport()" class="btn btn-success">Download</button>
      </div>
      </form>
    </div>
    </div>
  </div>

  <!-- MODAL FOR Hardware Bill -->
  <div class="modal fade" id="myHadwareBill" role="dialog">
    <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
      <h4 class="modal-title">Hardware Bill Generation</h4>
      <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <form method="POST" action="{{ url('/invoice/hardwarebill') }}">
      @csrf
      <div class="modal-body">
        <div class="row">
        <div class="col-6">
          <label>Po No.</label>
          <input value="O/{{$companyDetails->opo_no + 1}}" name="po_no" type="text" class="form-control" />
        </div>
        <div class="col-6">
          <label>Date</label>
          <input name="date" type="date" class="form-control" />
        </div>
        </div>
        <div class="row">
        <input id="iifhb" name="invoice_id" type="hidden">
        <div class="col-6">
          <label>Select Hardware Suppliers</label>
          <select class="selectpicker" data-live-search="true" onchange="changeDetails(this)"
          name="hardware_supplier" required>
          <option value="" disabled>Select Hardware Suppliers</option>
          @if(isset($hardwareSuppliers)) @foreach($hardwareSuppliers as $key => $hardwareSupplier)
        <option value="{{$hardwareSupplier->id}}">
        {{$hardwareSupplier->name}}
        </option>
      @endforeach @endif
          </select>
        </div>
        </div>
      </div>
      <div class="modal-footer">
        <button type="submit" onclick="return submitHardwareBill()" class="btn btn-success">Download</button>
      </div>
      </form>
    </div>
    </div>
  </div>
  <!-- MODAL FOR Small Hardware Bill -->
  <div class="modal fade" id="mySmallHadwareBill" role="dialog">
    <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
      <h4 class="modal-title">Small Hardware Bill Generation</h4>
      <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <form method="POST" action="{{ url('/invoice/smallhardwarebill') }}">
      @csrf
      <div class="modal-body">
        <div class="row">
        <div class="col-6">
          <label>Po No.</label>
          <input value="O/{{$companyDetails->opo_no + 1}}" name="po_no" type="text" class="form-control" />
        </div>
        <div class="col-6">
          <label>Date</label>
          <input name="date" type="date" class="form-control" />
        </div>
        </div>
        <div class="row">
        <input id="iifshb" name="invoice_id" type="hidden">
        <div class="col-6">
          <label>Select Hardware Suppliers</label>
          <select class="selectpicker" data-live-search="true" onchange="changeDetails(this)"
          name="smallhardware_supplier" required>
          <option value="" disabled>Select Small Hardware Suppliers</option>
          @if(isset($suppliers)) @foreach($suppliers as $key => $supplier)
        <option value="{{$supplier->id}}">
        {{$supplier->c_name}}
        </option>
      @endforeach @endif
          </select>
        </div>
        <div class="col-6">
          <label class="control-label">{{ __('Buyer') }}</label>
          <select class="form-control selectpicker" name="uk18[]" required multiple>

          <option value="1">UK-18</option>
          <option value="2">Non UK-18</option>
          <option value="3">Common</option>

          </select>
        </div>
        </div>
      </div>
      <div class="modal-footer">
        <button type="submit" onclick="return submitSmallHardwareBill()" class="btn btn-success">Download</button>
      </div>
      </form>
    </div>
    </div>
  </div>


<div class="modal fade" id="monthEndpo" role="dialog">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h4 class="modal-title">Month End PO</h4>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <p class="px-3 small text-muted mb-0" style="margin-top:-0.5rem;">Same idea as the Small Hardware bill (supplier + buyers), but lines use <strong>consumable</strong> + <em>wf_consumable</em> and month-end fields — not the old small hardware (SM) product table.</p>
<form method="get" id="monthEndPoForm" action="{{ url('/invoice/monthEndPO') }}">
        @csrf
        <div class="modal-body">
          <div class="row">
            @php
              $settingsMonthEndCounter = isset($companyDetails->monthend_po_no) ? (int) $companyDetails->monthend_po_no : 0;
              $lastNumber = (int) str_replace('M/', '', $lastPO->pono ?? 'M/0');
              $nextPoNo = $settingsMonthEndCounter > 0 ? $settingsMonthEndCounter : ($lastNumber + 1);
              if ($nextPoNo < 1) {
                $nextPoNo = 1;
              }
            @endphp

            <div class="col-6">
              <label>Po No.</label>
              <input id="monthEndPoNoField" value="M/{{ $nextPoNo }}" name="po_no" type="text" class="form-control" />
            </div>

            <div class="col-6">
              <label>Supplier</label>
              <select id="supplierSelect" name="supplier_id" class="form-control" required>
                <option value="">-- Select Supplier --</option>
              </select>
            </div>
          </div>
          <div class="row mt-2">
            <div class="col-12">
              <label class="control-label">{{ __('Buyer') }}</label>
              <select class="form-control selectpicker" name="uk18[]" id="monthEndModalUk18" multiple required data-actions-box="true">
                <option value="1" selected>UK-18</option>
                <option value="2" selected>Non UK-18</option>
                <option value="3" selected>Common</option>
              </select>
            </div>
          </div>

          <div class="row">
            <input id="monthid" name="invoice_id" type="hidden">
          </div>
        </div>
        <div class="modal-footer">
          <button type="submit" onclick="return submitSmallHardwareBill()" class="btn btn-success">
            Create MonthEnd PO
          </button>
        </div>
      </form>
    </div>
  </div>
</div>

<div class="modal fade" id="hardwareMonthEndpo" role="dialog">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h4 class="modal-title">Hardware Month End PO</h4>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <p class="px-3 small text-muted mb-0" style="margin-top:-0.5rem;">Consumables flagged <strong>hardware month-end PO</strong> only — same PO/challan/stock tables as month-end, <strong>no buyer filter</strong>.</p>
<form method="get" id="hardwareMonthEndPoForm" action="{{ url('/invoice/hardwareMonthEndPO') }}">
        @csrf
        <div class="modal-body">
          <div class="row">
            @php
              $settingsMonthEndCounterHw = isset($companyDetails->monthend_po_no) ? (int) $companyDetails->monthend_po_no : 0;
              $lastNumberHw = (int) str_replace('M/', '', $lastPO->pono ?? 'M/0');
              $nextPoNoHw = $settingsMonthEndCounterHw > 0 ? $settingsMonthEndCounterHw : ($lastNumberHw + 1);
              if ($nextPoNoHw < 1) {
                $nextPoNoHw = 1;
              }
            @endphp

            <div class="col-6">
              <label>Po No.</label>
              <input id="hardwareMonthEndPoNoField" value="M/{{ $nextPoNoHw }}" name="po_no" type="text" class="form-control" />
            </div>

            <div class="col-6">
              <label>Supplier</label>
              <select id="hardwareSupplierSelect" name="supplier_id" class="form-control" required>
                <option value="">-- Select Supplier + Invoice --</option>
              </select>
            </div>
          </div>

          <div class="row">
            <input id="hardwareMonthendInvoiceId" name="invoice_id" type="hidden">
          </div>
        </div>
        <div class="modal-footer">
          <button type="submit" onclick="return submitSmallHardwareBill()" class="btn btn-success">
            Preview hardware Month-End PO
          </button>
        </div>
      </form>
    </div>
  </div>
</div>

    
  <!-- MODAL FOR myDustCoverBill Bill -->
  <div class="modal fade" id="myDustCoverBill" role="dialog">
    <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
      <h4 class="modal-title">Dust Cover Bill</h4>
      <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <form method="POST" action="{{ url('/invoice/dustcoverbill') }}">
      @csrf
      <div class="modal-body">
        <div class="row">
        <div class="col-6">
          <label>Po No.</label>
          <input value="O/{{$companyDetails->opo_no + 1}}" name="po_no" type="text" class="form-control" />
        </div>
        <div class="col-6">
          <label>Date</label>
          <input name="date" type="date" class="form-control" />
        </div>
        </div>
        <div class="row">
        <input id="iifdcb" name="invoice_id" type="hidden">
        <div class="col-6">
          <label>Select Supplier</label>
          <select class="selectpicker" data-live-search="true" onchange="changeDetails(this)" name="supplier"
          required>
          <option value="" disabled>Select Suppliers</option>
          @if(isset($smhsuppliers)) @foreach($smhsuppliers as $key => $smhsupplier)
        <option value="{{$smhsupplier->id}}">
        {{$smhsupplier->c_name}}
        </option>
      @endforeach @endif
          </select>
        </div>
        </div>
      </div>
      <div class="modal-footer">
        <button type="submit" onclick="return submitDustCoverBill()" class="btn btn-success">Download</button>
      </div>
      </form>
    </div>
    </div>
  </div>

  <!-- MODAL FOR myPouchBill Bill -->
  <div class="modal fade" id="myPouchBill" role="dialog">
    <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
      <h4 class="modal-title">Pouch Bill</h4>
      <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <form method="POST" action="{{ url('/invoice/pouchbill') }}">
      @csrf
      <div class="modal-body">
        <div class="row">
        <div class="col-6">
          <label>Po No.</label>
          <input value="O/{{$companyDetails->opo_no + 1}}" name="po_no" type="text" class="form-control" />
        </div>
        <div class="col-6">
          <label>Date</label>
          <input name="date" type="date" class="form-control" />
        </div>
        </div>
        <div class="row">
        <input id="iifpb" name="invoice_id" type="hidden">
        <div class="col-6">
          <label>Select Supplier</label>
          <select class="selectpicker" data-live-search="true" onchange="changeDetails(this)" name="supplier"
          required>
          <option value="" disabled>Select Suppliers</option>
          @if(isset($smhsuppliers)) @foreach($smhsuppliers as $key => $smhsupplier)
        <option value="{{$smhsupplier->id}}">
        {{$smhsupplier->c_name}}
        </option>
      @endforeach @endif
          </select>
        </div>
        </div>
      </div>
      <div class="modal-footer">
        <button type="submit" onclick="return submitPouchBill()" class="btn btn-success">Download</button>
      </div>
      </form>
    </div>
    </div>
  </div>
  <!-- MODAL FOR Invoice Email -->
  <div class="modal fade" id="invoiceEmail" role="dialog">
    <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
      <h4 class="modal-title">Email for <span id="inv-no"></span></h4>
      <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <form method="POST" action="{{ url('/invoice/email') }}">
      @csrf
      <div class="modal-body">
        <div class="row">
        <div class="col-12">
          <label>Emails(<i>Comma seperated in case of multiple emails</i>)</label>
          <input name="emails" type="text" class="form-control" required />
        </div>
        </div>
        <div class="row">
        <div class="col-12">
          <label>Subject</label>
          <input name="subject" type="text" class="form-control" required />
        </div>
        </div>
        <div class="row">
        <input id="iifemail" name="invoice_id" type="hidden">
        <div class="col-12">
          <label>Email</label>
          <textarea id="editor" name="body"></textarea>
        </div>
        </div>
      </div>
      <div class="modal-footer">
        <button type="submit" class="btn btn-success">Send</button>
      </div>
      </form>
    </div>
    </div>
  </div>

  <!-- Form FOR Contractor Bill -->
  <form id="contractorBillForm" method="POST" action="{{ url('/invoice/contractorbill') }}" style="visibility:hidden;">
    @csrf
    <input type="hidden" id="invoice_ids" name="invoice_ids" />
    <button type="submit" onclick="return false" class="btn btn-success">Download</button>
  </form>

  <!-- Form FOR Contractor Bill -->
  <form id="upholestryBillForm" method="POST" action="{{ url('/invoice/upholsterybill') }}" style="visibility:hidden;">
    @csrf
    <input type="hidden" id="up_invoice_ids" name="invoice_ids" />
    <button type="submit" onclick="return false" class="btn btn-success">Open</button>
  </form>


  <!-- Form FOR Export Sales -->
  <form id="exportsalesBillForm" method="POST" action="{{ url('/invoice/exportsalesBillForm') }}"
    style="visibility:hidden;">
    @csrf
    <input type="hidden" id="export_invoice_ids" name="invoice_ids" />
    <button type="submit" onclick="return false" class="btn btn-success">Open</button>
  </form>


  <!-- MODAL FOR DELETE -->
  <div class="modal fade" id="deleteInvoice" role="dialog">
    <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
      <h4 class="modal-title">Delete Confirmation</h4>
      <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <form id="deleteInvoiceForm" method="POST" action="">
      @csrf
      <div class="modal-body">
        <p>Are You sure you want to Delete this?</p>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-default" data-bs-dismiss="modal">No</button>
        <button type="submit" id="deleteInvoiceForm" class="btn btn-danger">Yes</button>
      </div>
      </form>
    </div>
    </div>
  </div>

  <!-- MODAL FOR Upholstery Bill -->
  <div class="modal fade" id="myUpholsteryBill" role="dialog">
    <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
      <h4 class="modal-title">Upholstery Bill Generation</h4>
      <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <form method="POST" action="{{ url('/invoice/downloadUpholstreyBill') }}">
      @csrf
      <div class="modal-body">
        <div class="row">

        <div class="col-6">
          <label>Select Month</label>
          <select class="selectpicker" name="month" required>
          <option value="" disabled>Select Month</option>
          <option value="<?php echo date('m-Y', strtotime("Previous month")); ?>">
            <?php echo date('F-Y', strtotime("Previous month")); ?></option>
          <option value="<?php echo date('m-Y'); ?>"><?php echo date('F-Y'); ?></option>
          </select>
        </div>
        <div class="col-6">
          <label>Select Contractors</label>
          <select class="selectpicker" data-live-search="true" name="contractor" required>
          <option value="" disabled>Select Contractors</option>
          @if(isset($upcontractor)) @foreach($upcontractor as $key => $cont)
        <option value="{{$cont->id}}">
        {{$cont->name}}
        </option>
      @endforeach @endif
          </select>
        </div>
        </div>

      </div>
      <div class="modal-footer">
        <button type="submit" class="btn btn-success">Download</button>
      </div>
      </form>
    </div>
    </div>
  </div>

  <!-- Print invoice: optional packing-list batch on product lines -->
  <div class="modal fade" id="invoicePrintOptionsModal" role="dialog" tabindex="-1">
    <div class="modal-dialog">
      <div class="modal-content">
        <div class="modal-header">
          <h4 class="modal-title">Print invoice</h4>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <div class="form-check">
            <input type="checkbox" class="form-check-input" id="invoicePrintIncludeBatch" />
            <label class="form-check-label" for="invoicePrintIncludeBatch">Show batch numbers on print </label>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-default" data-bs-dismiss="modal">Cancel</button>
          <button type="button" class="btn btn-success" id="invoicePrintConfirmBtn">Print</button>
        </div>
      </div>
    </div>
  </div>

  <!-- MODAL FOR Shipping Bill -->
  <div class="modal fade" id="shippingBill" role="dialog">
    <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
      <h4 class="modal-title">Shipping Bill</h4>
      <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <form method="POST" action="{{ url('/invoice/updateShippingBill') }}">
      @csrf
      <div class="modal-body">
        <div class="row">
        <input id="sb_inv_id" type="hidden" class="form-control" name="id" />
        <div class="col-6">
          <label>SHIPPING BILL NO.</label>
          <input id="shipping_bill_no" type="text" class="form-control" name="shipping_bill_no" />
        </div>
        <div class="col-6">
          <label>SHIPPING BILL DATE</label>
          <input id="shipping_bill_date" type="date" class="form-control" name="shipping_bill_date" />
        </div>

        </div>
        <div class="row">
        <div class="col-6">
          <label>E WAY BILL NO</label>
          <input id="ewaybillno" type="text" class="form-control" name="ewaybillno" />
        </div>
        <div class="col-6">
          <label>E WAY BILL DATE</label>
          <input id="ewaybilldate" type="date" class="form-control" name="ewaybilldate" />
        </div>
        </div>

        <div class="row">
        <div class="col-6">
          <label>PORT CODE</label>
          <input id="port_code" type="text" class="form-control" name="port_code" />
        </div>
        <div class="col-6">
          <label>SHIPPING EXCHANGE RATE</label>
          <input id="shipping_exchange_rate" type="text" class="form-control" name="shipping_exchange_rate" />
        </div>
        </div>
        <div class="row">
        <div class="col-6">
          <label>BL Number</label>
          <input id="bl_no" type="text" class="form-control" name="bl_no" />
        </div>
        <div class="col-6">
          <label>BL Date</label>
          <input id="bl_date" type="date" class="form-control" name="bl_date" />
        </div>
        </div>
        <div class="row">
        <div class="col-6">
          <label>Billty Number</label>
          <input id="billty_no" type="text" class="form-control" name="billty_no" />
        </div>
        <div class="col-6">
          <label>Billty Date</label>
          <input id="billty_date" type="date" class="form-control" name="billty_date" />
        </div>
        </div>
        <div class="row">
        <div class="col-6">
          <label>Agent</label>
          <input id="agent" type="text" class="form-control" name="agent" />
        </div>

        </div>
        <div class="row">
        <div class="col-6">
          <label>Port of Loading</label>
          <input id="port_of_loading" type="text" class="form-control" name="port_of_loading" />
        </div>
        <div class="col-6">
          <label>Port of Discharge</label>
          <input id="port_of_discharge" type="text" class="form-control" name="port_of_discharge" />
        </div>
        </div>
        <div class="row">
        <div class="col-6">
          <label>Ship To</label>
          <input id="ship_to" type="text" class="form-control" name="ship_to" />
        </div>
        <div class="col-6">
          <label>Bill To</label>
          <input id="bill_to" type="text" class="form-control" name="bill_to" />
        </div>
        </div>
      </div>
      <div class="modal-footer">
        <button type="submit" class="btn btn-success">Save</button>
      </div>
      </form>
    </div>
    </div>
  </div>


  <!-- MODAL FOR myCornerBill -->
  <div class="modal fade" id="myCornerBill" role="dialog">
    <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
      <h4 class="modal-title">Corner and L Bill Generation</h4>
      <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <form id="cornerBillForm" method="POST" action="{{ url('/invoice/cornerbill') }}">
      @csrf
      <input type="hidden" id="corner_invoice_ids" name="invoice_ids" />
      <div class="modal-body">
        <div class="row">
        Click Download to download the bill.<br>
        Click Edit to edit the bill.
        </div>

      </div>
      <div class="modal-footer">
        <a href="#" onclick="cornerBill()" class="btn btn-success">Download</a>
        <a href="#" onclick="cornerBillEdit()" class="btn btn-success">Edit</a>
      </div>
      </form>
    </div>
    </div>
  </div>

  <!-- MODAL FOR E-Invoice -->
  <div class="modal fade" id="einvoicing" role="dialog">
    <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
      <h4 class="modal-title">E-Invoice Fields</h4>
      <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <form id="einvoice" method="POST" action="{{ url('/invoice/einvoice') }}" enctype="multipart/form-data">
      @csrf
      <input type="hidden" id="einv_invoice_id" name="invoice_id" />
      <div class="modal-body">
        <div class="row">
        <div class="col-12">
          <label>IRN</label>
          <input type="text" class="form-control" name="irn" id="irn" />
        </div>
        </div>

        <div class="row">
        <div class="col-12">
          <label>Acknowledgement Number</label>
          <input type="text" class="form-control" name="ack_no" id="ack_no" />
        </div>
        </div>

        <div class="row">
        <div class="col-12">
          <label>Acknowledgement Date</label>
          <input type="date" class="form-control" name="ack_date" id="ack_date" />
        </div>
        </div>

        <div class="row">
        <div class="col-12">
          <label>Bill of Lading/LR-RR No.</label>
          <input type="text" class="form-control" name="lr_rr_no" id="lr_rr_no" />
        </div>
        </div>

        <div class="row">
        <div class="col-12">
          <label>E-Invoice QR</label>
          <input type="file" class="form-control" name="einvoice_qr" id="einvoice_qr" />
        </div>
        <div id="einv_img"></div>
        </div>

      </div>
      <div class="modal-footer">
        <button type="submit" href="#" class="btn btn-success">Update</button>
      </div>
      </form>
    </div>
    </div>
  </div>

   <!-- MODAL FOR DELETE -->
    <div class="modal" id="cancelInvoice" tabindex="-1" role="dialog">
      <div class="modal-dialog" role="document">
          <div class="modal-content">
              <form id="cancelInvoiceFORM" method="POST">
                  @csrf
                  @method('POST')
                  <div class="modal-header">
                      <h5 class="modal-title">Cancel Invoice</h5>
                      <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                  </div>
                  <div class="modal-body">
                      <p>Are you sure you want to cancel this Invoice?</p>
                      <div class="mb-3">
                          <label class="form-label d-block">Clone this invoice with a new number?</label>
                          <div class="form-check form-check-inline">
                              <input class="form-check-input" type="radio" name="clone" id="cancelCloneNo" value="0" checked>
                              <label class="form-check-label" for="cancelCloneNo">No, cancel only</label>
                          </div>
                          <div class="form-check form-check-inline">
                              <input class="form-check-input" type="radio" name="clone" id="cancelCloneYes" value="1">
                              <label class="form-check-label" for="cancelCloneYes">Yes, clone it</label>
                          </div>
                      </div>
                      <div class="mb-3" id="cloneInvoiceNoWrap" style="display:none;">
                          <label class="form-label" for="clone_invoiceno">New Invoice No.</label>
                          <input type="text" class="form-control toUpperCase" name="clone_invoiceno" id="clone_invoiceno" placeholder="Enter next invoice number">
                      </div>
                  </div>
                  <div class="modal-footer">
                      <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                      <button type="submit" class="btn btn-danger">Confirm</button>
                  </div>
              </form>
          </div>
      </div>
  </div>
  

@endsection

@section('footer')


  <!-- Script start Invoice delete -->
  <script type="text/javascript">
    $(document).on('click', '.invoice-print-open', function (e) {
      e.preventDefault();
      var id = $(this).data('invoice-id');
      $('#invoicePrintOptionsModal').data('invoice-id', id);
      $('#invoicePrintIncludeBatch').prop('checked', false);
      $('#invoicePrintOptionsModal').modal('show');
    });
    $('#invoicePrintConfirmBtn').on('click', function () {
      var id = $('#invoicePrintOptionsModal').data('invoice-id');
      if (!id) {
        return;
      }
      var url = "{{ url('/invoice/modal') }}/" + id + "?print=1";
      if ($('#invoicePrintIncludeBatch').is(':checked')) {
        url += "&show_batch=1";
      }
      window.open(url, "_blank");
      $('#invoicePrintOptionsModal').modal('hide');
    });

    function tallyStatus(id) {
    $('#statusTally').modal('show');
    $('#statusTallyFORM').attr('action', "{{ url('/invoice/updateTallyStatus') }}" + '/' + id);
    }
    function einvUpdate(inv_id, irn, ack_no, ack_date, einvoice_qr, lr_rr_no) {
    $('#einv_invoice_id').val(inv_id);
    $('#irn').val(irn);
    $('#ack_no').val(ack_no);
    $('#ack_date').val(ack_date);
    $('#lr_rr_no').val(lr_rr_no);
    if (einvoice_qr != '' && einvoice_qr != null) {
      imgurl = "{{url('uploads/einvoice')}}/" + einvoice_qr;
      $('#einv_img').html('<img src="' + imgurl + '" />');
    }
    }

    function cancelModal(btn) {
      var id = $(btn).attr('data-invoice-id');
      var nextNo = $(btn).attr('data-next-invoiceno') || '';
      $('#cancelInvoiceFORM').attr('action', "{{ url('/invoice/cancel') }}" + '/' + id);
      $('#cancelCloneNo').prop('checked', true);
      $('#cloneInvoiceNoWrap').hide();
      $('#clone_invoiceno').val(nextNo).prop('required', false);
      $('#cancelInvoice').modal('show');
    }

    $(document).on('change', 'input[name="clone"]', function () {
      var cloneYes = $('input[name="clone"]:checked').val() === '1';
      $('#cloneInvoiceNoWrap').toggle(cloneYes);
      $('#clone_invoiceno').prop('required', cloneYes);
    });

    $('#cancelInvoiceFORM').on('submit', function () {
      if ($('input[name="clone"]:checked').val() === '1' && $.trim($('#clone_invoiceno').val()) === '') {
        alert('Please enter the new invoice number.');
        return false;
      }
      return true;
    });
    
    function validate() {
    var fsd = $('#fsd').val();
    var fed = $('#fed').val();
    if (fsd > fed) {
      alert('Start Date should be less than End Date');
      return false;
    }
    return true;
    }

    function validateExport() {
    var fesd = $('#fesd').val();
    var feed = $('#feed').val();
    if (fesd > feed) {
      alert('Start Date should be less than End Date');
      return false;
    }
    return true;
    }

    function submitHardwareBill() {
    if ($('#invoice_ids').val() == "") {
      alert("Please select atleast 1 Invoice");
      return false;
    } else {
      return true;
    }
    }
    function submitSmallHardwareBill() {
    if ($('#invoice_ids').val() == "") {
      alert("Please select atleast 1 Invoice");
      return false;
    } else {
      return true;
    }
    }
    function submitDustCoverBill() {
    if ($('#invoice_ids').val() == "") {
      alert("Please select atleast 1 Invoice");
      return false;
    } else {
      return true;
    }
    }
    function submitPouchBill() {
    if ($('#invoice_ids').val() == "") {
      alert("Please select atleast 1 Invoice");
      return false;
    } else {
      return true;
    }
    }

    $(function () {
    $('[data-bs-toggle="tooltip"]').tooltip()

    $("#checkedAll").change(function () {
      if (this.checked) {
      $(".checkSingle").each(function () {
        this.checked = true;
        selectInvoice($(this));
      });
      } else {
      $(".checkSingle").each(function () {
        this.checked = false;
        selectInvoice($(this));
      });
      }
    });

    $(".checkSingle").click(function () {
      if ($(this).is(":checked")) {
      var isAllChecked = 0;

      $(".checkSingle").each(function () {
        if (!this.checked)
        isAllChecked = 1;
      });

      if (isAllChecked == 0) {
        $("#checkedAll").prop("checked", true);
      }
      }
      else {
      $("#checkedAll").prop("checked", false);
      }
    });
    });

    function selectInvoice(chObj) {
    if (chObj.is(":checked")) {
      invoice_id = chObj.attr("id").split("_");
      invoice_ids = $('#invoice_ids').val();
      invoice_ids = invoice_ids + invoice_id[1] + ",";
      $('#invoice_ids').val(invoice_ids);
      $("#iifhb").val(invoice_ids);
      	$("#monthid").val(invoice_ids);
      $("#hardwareMonthendInvoiceId").val(invoice_ids);
      $("#iifshb").val(invoice_ids);
      $("#iifdcb").val(invoice_ids);
      $("#iifpb").val(invoice_ids);
      $("#up_invoice_ids").val(invoice_ids);
      $("#corner_invoice_ids").val(invoice_ids);
      $("#export_invoice_ids").val(invoice_ids);
    } else {
      invoice_id = chObj.attr("id").split("_");
      invoice_ids = $('#invoice_ids').val().replace(invoice_id[1] + ",", "");
      $('#invoice_ids').val(invoice_ids);
      $("#iifhb").val(invoice_ids);
      $("#iifshb").val(invoice_ids);
       $("#monthid").val(invoice_ids);
      $("#hardwareMonthendInvoiceId").val(invoice_ids);
      $("#iifdcb").val(invoice_ids);
      $("#iifpb").val(invoice_ids);
      $("#up_invoice_ids").val(invoice_ids);
      $("#corner_invoice_ids").val(invoice_ids);
      $("#export_invoice_ids").val(invoice_ids);
    }
    }

    function contractorBill() {
    //$('#invoice_ids').val("");
    if ($('#invoice_ids').val() == "") {
      alert("Please select atleast 1 Invoice");
    } else {
      $("#contractorBillForm").submit();
    }
    }

    function upholestryBill() {
    //$('#invoice_ids').val("");
    if ($('#up_invoice_ids').val() == "") {
      alert("Please select atleast 1 Invoice");
    } else {
      $("#upholestryBillForm").submit();
    }
    }

    function cornerBill() {
    //$('#invoice_ids').val("");
    if ($('#corner_invoice_ids').val() == "") {
      alert("Please select atleast 1 Invoice");
    } else {
      $("#cornerBillForm").attr('action', "{{ url('/invoice/cornerbill') }}");
      $("#cornerBillForm").submit();
    }
    }

    function exportSales() {
    //$('#invoice_ids').val("");
    if ($('#export_invoice_ids').val() == "") {
      alert("Please select atleast 1 Invoice");
    } else {
      $("#exportsalesBillForm").attr('action', "{{ url('/invoice/exportsalesBillForm') }}");
      $("#exportsalesBillForm").submit();
    }
    }

    function cornerBillEdit() {
    //$('#invoice_ids').val("");
    if ($('#corner_invoice_ids').val() == "") {
      alert("Please select atleast 1 Invoice");
    } else {
      $("#cornerBillForm").attr('action', "{{ url('/invoice/cornerbillEdit') }}");
      $("#cornerBillForm").submit();
    }
    }

    function deleteModal(id) {
    $('#deleteInvoice').modal('show');
    $('#deleteInvoiceForm').attr('action', "{{ url('/invoice/delete') }}" + '/' + id);
    }

    function updateinvoice(id) {
    location.href = "{{ url('/invoice/view') }}" + '/' + id;
    }

    function shipBill(inv_id, shipping_bill_no, shipping_bill_date, ewaybillno, ewaybilldate, port_code, shipping_exchange_rate, bl_no, bl_date) {
    $("#sb_inv_id").val(inv_id);
    $("#shipping_bill_no").val(shipping_bill_no);
    $("#shipping_bill_date").val(shipping_bill_date);
    $("#ewaybillno").val(ewaybillno);
    $("#ewaybilldate").val(ewaybilldate);
    $("#port_code").val(port_code);
    $("#shipping_exchange_rate").val(shipping_exchange_rate);
    $("#bl_no").val(bl_no);
    $("#bl_date").val(bl_date);
    }

    function invExp(inv_id, shipping_bill_no, shipping_bill_date, booking_value, shipdawn, inrat_booking_value, tax_type) {

    $("#sb_inv_id_val" + inv_id).val(inv_id);
    $("#shipping_bill_no" + inv_id).val(shipping_bill_no);
    $("#shipping_bill_date" + inv_id).val(shipping_bill_date);
    $("#booking_value" + inv_id).val(booking_value);
    $("#shipdawn" + inv_id).val(shipdawn);
    $("#inrat_booking_value" + inv_id).val(inrat_booking_value);
    $("#tax_type" + inv_id).val(tax_type);

    var trcount = $('#invoiceTable' + inv_id + ' tr').length;

    var invoiceRows = trcount;
    var products = [];


    $("#addMore" + inv_id).click(function () {

      invoiceRows += 1;
      console.log(invoiceRows);
      $.each(products, function (index, value) {
      options += '<option value="' + value.id + '">' + value.code + " - " + value.name + '</option>';
      });

      var $block = "";
      $block += '<tr>';
      $block += '<td id="realisation_date' + invoiceRows + '"><input type="date" step="any" min="0" class="form-control"  data-len="' + invoiceRows + '" name="ie[' + invoiceRows + '][realisation_date]" value=""  /></td>'
      $block += '<td id="realisation_fc' + invoiceRows + '"><input type="text" step="any" min="0" class="form-control"  data-len="' + invoiceRows + '" name="ie[' + invoiceRows + '][realisation_fc]" value=""  /></td>'
      $block += '<td id="rate' + invoiceRows + '"><input type="number" step="any" min="0" class="form-control"  data-len="' + invoiceRows + '" name="ie[' + invoiceRows + '][rate]" value=""  /></td>'
      $block += '<td id="bank_reference' + invoiceRows + '"><input type="text" step="any" min="0" class="form-control"  data-len="' + invoiceRows + '" name="ie[' + invoiceRows + '][bank_reference]" value=""  /></td>'
      $block += '<td id="fbc' + invoiceRows + '"><input type="text" step="any" min="0" class="form-control"  data-len="' + invoiceRows + '" name="ie[' + invoiceRows + '][fbc]" value=""  /></td>'
      $block += '<td><a href="javascript:void(0);" class="close" onclick="deleteRow(this);" >&times;</a></td>';
      $block += '</tr>';
      $("#invoiceTable" + inv_id).append($block);

    });
    }
    function deleteRow(ref) {
    $(ref).parent().parent().remove();

    }

    function invEmail(id, invoice) {
    $('#invoiceEmail').modal('show');
    $('#iifemail').val(id);
    $('#inv-no').html(invoice);
    }

    $(function () {
    var table = $("#dataTable").DataTable();
    $('#dataTable_filter').append('<br><label>Search invoice type: <input id="pending-product-search" class="form-control" type="text" placeholder="Search invoice type" data-index="" /></label>');
    $(table.table().container()).on('keyup', '#pending-product-search', function () {
      table
      .column(9)
      .search(this.value)
      .draw();
    });

    ids = "";
    table.rows().data().toArray().forEach(function (item, i) {
      ids += item[1] + ",";
    });

    });

  </script>
  <!-- Script End -->

  <script>
    ClassicEditor
    .create(document.querySelector('#editor'))
    .catch(error => {
      console.error(error);
    });
</script>

<script>
function getSelectedInvoiceIds() {
  // If you already have your own, keep it. This is a safe fallback:
  const ids = [];
  $(".checkSingle:checked").each(function () {
    const id = $(this).attr("id")?.split("_")[1];
    if (id) ids.push(id);
  });
  return ids;
}

function openMonthEndPoModal(invoice_ids) {
  if (Array.isArray(invoice_ids)) invoice_ids = invoice_ids.join(',');
  if (!invoice_ids) {
    alert("Please select at least 1 Invoice");
    return;
  }
  window._monthEndLastInvoiceIds = invoice_ids;
  window._monthEndOpenModalOnSupplierLoad = true;
  $("#monthid").val("");
  loadMonthEndSupplierOptions();
}

function loadMonthEndSupplierOptions() {
  const invoice_ids = window._monthEndLastInvoiceIds;
  if (!invoice_ids) return;

  const formData = new FormData();
  formData.append('invoice_id', invoice_ids);
  formData.append('po_no', ($("#monthEndPoNoField").val() || ""));
  formData.append('_token', "{{ csrf_token() }}");

  $.ajax({
    url: "{{ url('/invoice/monthendsupplier') }}",
    type: "POST",
    data: formData,
    processData: false,
    contentType: false,
    success: function(res) {
      const items = res.items || [];
      const $sel = $("#supplierSelect");
      $sel.empty();
      $sel.append('<option value="">-- Select Supplier + Invoice --</option>');

      if (items.length === 0) {
        alert("No suppliers found for selected invoices.");
        return;
      }

      items.forEach(row => {
        const text = `${row.supplier_name} — Inv ${row.invoice_no} (#${row.invoice_id})`;
        $sel.append(
          $(`<option></option>`).val(row.supplier_id || '').attr('data-invoice', row.invoice_id).text(text)
        );
      });

      $sel.off('change.monthend').on('change.monthend', function() {
        const invId = $(this).find(':selected').data('invoice');
        $("#monthid").val(invId || "");
      });

      if (items.length === 1) {
        $sel.prop('selectedIndex', 1).trigger('change');
      } else {
        $sel.val('');
        $("#monthid").val("");
      }

      if (window._monthEndOpenModalOnSupplierLoad) {
        window._monthEndOpenModalOnSupplierLoad = false;
        $("#monthEndpo").modal("show");
      }
    },
    error: function(err) {
      console.error(err);
      alert("Failed to load suppliers. Please try again.");
    }
  });
}

function openHardwareMonthEndPoModal(invoice_ids) {
  if (Array.isArray(invoice_ids)) invoice_ids = invoice_ids.join(',');
  if (!invoice_ids) {
    alert("Please select at least 1 Invoice");
    return;
  }
  window._hwMonthEndLastInvoiceIds = invoice_ids;
  window._hwMonthEndOpenModalOnSupplierLoad = true;
  $("#hardwareMonthendInvoiceId").val("");
  loadHardwareMonthEndSupplierOptions();
}

function loadHardwareMonthEndSupplierOptions() {
  const invoice_ids = window._hwMonthEndLastInvoiceIds;
  if (!invoice_ids) return;

  const formData = new FormData();
  formData.append('invoice_id', invoice_ids);
  formData.append('po_no', ($("#hardwareMonthEndPoNoField").val() || ""));
  formData.append('_token', "{{ csrf_token() }}");

  $.ajax({
    url: "{{ url('/invoice/hardwareMonthendsupplier') }}",
    type: "POST",
    data: formData,
    processData: false,
    contentType: false,
    success: function(res) {
      const items = res.items || [];
      const $sel = $("#hardwareSupplierSelect");
      $sel.empty();
      $sel.append('<option value="">-- Select Supplier + Invoice --</option>');

      if (items.length === 0) {
        alert("No eligible hardware month-end suppliers for selected invoices (flag consumables, wf mapping, or PO already exists).");
        return;
      }

      items.forEach(row => {
        const text = `${row.supplier_name} — Inv ${row.invoice_no} (#${row.invoice_id})`;
        $sel.append(
          $(`<option></option>`).val(row.supplier_id || '').attr('data-invoice', row.invoice_id).text(text)
        );
      });

      $sel.off('change.hwmonthend').on('change.hwmonthend', function() {
        const invId = $(this).find(':selected').data('invoice');
        $("#hardwareMonthendInvoiceId").val(invId || "");
      });

      if (items.length === 1) {
        $sel.prop('selectedIndex', 1).trigger('change');
      } else {
        $sel.val('');
        $("#hardwareMonthendInvoiceId").val("");
      }

      if (window._hwMonthEndOpenModalOnSupplierLoad) {
        window._hwMonthEndOpenModalOnSupplierLoad = false;
        $("#hardwareMonthEndpo").modal("show");
      }
    },
    error: function(err) {
      console.error(err);
      alert("Failed to load suppliers. Please try again.");
    }
  });
}

$(function() {
  // GET forms + bootstrap-select: ensure native <select> has correct selected options
  // so uk18[]=… is present; otherwise the server may see no uk18 and default to all buyers.
  $(document).on('submit', '#monthEndPoForm', function () {
    const $s = $('#monthEndModalUk18');
    if (!$s.length) {
      return true;
    }
    let val = $s.selectpicker('val');
    if (val === null) {
      val = [];
    }
    if (typeof val === 'string') {
      val = [val];
    }
    $s.find('option').prop('selected', false);
    val.forEach(function (v) {
      if (v === null || v === undefined) {
        return;
      }
      $s.find('option[value="' + String(v) + '"]').prop('selected', true);
    });
    return true;
  });
});
</script>


  
@endsection