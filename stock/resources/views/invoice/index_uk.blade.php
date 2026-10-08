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
      <div class="col">
        <a class="btn btn-primary float-end" style="color: #fff;" data-bs-toggle="modal" data-bs-target="#addUkInvoice" href="#">Add Invoice</a>
      </div>
      @endhasrole

	  @hasrole('office')
	  <div class="col">
        {{-- <a id="corner_bill_link" class="btn btn-success float-end" style="color: #fff; margin-left: 5px; "  onclick="exportSales()" href="#">Export Sales</a> --}}
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
				        <th data-orderable="false" style="padding:0px;"><input type="checkbox" id="checkedAll" onclick="selectAllInvoice()" style="width:50px;height:50px"></th>
                <th>Buyer</th>
                <th>Date</th>
                <th>Total QTY</th>
                <th>Buyer Order No.</th>
                <th>Total Amount</th>
                <th>VAT</th>
                <th>Total Amount After VAT</th>
                <th>Status</th>
                <th>Invoice Type</th>
                <th style="min-width:120px;">Options</th>
                
              </tr>
            </thead>
            <tbody>
              @if(isset($invoice_uk)) @foreach($invoice_uk as $key => $inv_uk)
              <tr>
                <td>{{$inv_uk->invoiceno}}</td>
				        <td style="padding:0px;"><input class="checkSingle" type="checkbox" id="invoice_{{$inv_uk->id}}" onclick="selectInvoice($(this))" style="width:50px;height:50px"></td>
                <td>{{$inv_uk->ship_to}}</td>
                <td>{{date('d-M-Y',strtotime($inv_uk->date))}}</td>
                <td>{{$inv_uk->totalquantity}}</td>
                <td>{{$inv_uk->buyerorderno}}</td>
                <td>{{$inv_uk->currency}}{{$inv_uk->totalamount}}</td>
                <td>{{$inv_uk->vat}}%</td>
                <td>{{$inv_uk->currency}}{{$inv_uk->totalamount + ($inv_uk->totalamount * $inv_uk->vat/100)}}</td>
                <td>@if($inv_uk->status == 0){{'Pending'}}@endif
                    @if($inv_uk->status == 1){{'Complete'}}@endif
                </td>
                <td>@if($inv_uk->invoicetype == 0){{'Export'}}@endif
                    @if($inv_uk->invoicetype == 1){{'Local'}}@endif
                </td>
                <td>
                    

                    <span class="tool-tip1" data-bs-toggle="tooltip" data-bs-placement="top" title="View">
                       <a class="btn btn-primary" style="color: #fff;" href="{{ URL::to('/invoice/modal_uk/'.$inv_uk->id)}}" target="_blank"><i class="fa fa-eye"></i></a>
                    </span>
                    <span class="tool-tip1" data-bs-toggle="tooltip" data-bs-placement="top" title="Edit">
                      <a class="btn btn-warning" style="color: #fff;"  href="{{ URL::to('/invoice/view-uk/'.$inv_uk->id)}}" ><i class="fa fa-edit"></i></a>
                   </span>
                    <span class="tool-tip1" data-bs-toggle="tooltip" data-bs-placement="top" title="Delete">
                      <a class="btn btn-danger" style="color: #fff;" onclick="return confirm('Are you sure you want to delete invoice {{$inv_uk->invoiceno}}?');" href="{{ URL::to('/invoice/delete-uk/'.$inv_uk->id)}}" ><i class="fa fa-trash"></i></a>
                   </span>
                    
                  </td>
              </tr>
              @endforeach @endif
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <!-- MODAL FOR DATE -->
    <div class="modal fade" id="addUkInvoice" role="dialog">
      <div class="modal-dialog">
        <div class="modal-content">
          <div class="modal-header">
            <h4 class="modal-title">Select Invoice</h4>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <form method="POST" action="{{ url('/invoice/create_uk') }}">
          @csrf
            <div class="modal-body">
                <div class="row">
                  
                  <div class="col-12">
                      <select name="invoice_id" data-live-search="true" class="selectpicker" required>
                        <option value="">Select Buyer Order No.</option>
                        @if(isset($invoice)) @foreach($invoice as $key => $inv)
                          <option value="{{$inv->id}}">{{$inv->buyerorderno}}</option>
                        @endforeach @endif
                      </select>
                  </div>
                </div>
            </div>
            <div class="modal-footer">
              <button type="submit" class="btn btn-success">Proceed</button>
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
						<select class="selectpicker" data-live-search="true" onchange="changeDetails(this)" name="hardware_supplier" required>
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
						<select class="selectpicker" data-live-search="true" onchange="changeDetails(this)" name="smallhardware_supplier" required>
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
						<select class="selectpicker" data-live-search="true" onchange="changeDetails(this)" name="supplier" required>
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
						<select class="selectpicker" data-live-search="true" onchange="changeDetails(this)" name="supplier" required>
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
    <form id="exportsalesBillForm" method="POST" action="{{ url('/invoice/exportsalesBillForm') }}" style="visibility:hidden;">
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
						<option value="<?php echo date('m-Y',strtotime("Previous month")); ?>"><?php echo date('F-Y',strtotime("Previous month")); ?></option>
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
                    <input type="date" class="form-control" name="ack_date" id="ack_date"  />
                  </div>
                </div>

                <div class="row">
                  <div class="col-12">
                    <label>Bill of Lading/LR-RR No.</label>
                    <input type="text" class="form-control" name="lr_rr_no" id="lr_rr_no"  />
                  </div>
                </div>

                <div class="row">
                  <div class="col-12">
                    <label>E-Invoice QR</label>
                    <input type="file" class="form-control" name="einvoice_qr" id="einvoice_qr"  />
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

@endsection

@section('footer')


<!-- Script start Invoice delete -->
<script type="text/javascript">


  function einvUpdate(inv_id, irn, ack_no, ack_date, einvoice_qr, lr_rr_no){
    $('#einv_invoice_id').val(inv_id);
    $('#irn').val(irn);
    $('#ack_no').val(ack_no);
    $('#ack_date').val(ack_date);
    $('#lr_rr_no').val(lr_rr_no);
    if(einvoice_qr != '' && einvoice_qr != null){
      imgurl = "{{url('uploads/einvoice')}}/"+einvoice_qr;
      $('#einv_img').html('<img src="'+imgurl+'" />');
    }
  }

  function validate(){
    var fsd = $('#fsd').val();
    var fed = $('#fed').val();
    if(fsd > fed){
        alert('Start Date should be less than End Date');
        return false;
    }
    return true;
  }

  function validateExport(){
    var fesd = $('#fesd').val();
    var feed = $('#feed').val();
    if(fesd > feed){
        alert('Start Date should be less than End Date');
        return false;
    }
    return true;
  }

  function submitHardwareBill(){
	if($('#invoice_ids').val() == ""){
      alert("Please select atleast 1 Invoice");
	  return false;
    }else{
      return true;
    }
  }
  function submitSmallHardwareBill(){
	if($('#invoice_ids').val() == ""){
      alert("Please select atleast 1 Invoice");
	  return false;
    }else{
      return true;
    }
  }
  function submitDustCoverBill(){
	if($('#invoice_ids').val() == ""){
      alert("Please select atleast 1 Invoice");
	  return false;
    }else{
      return true;
    }
  }
  function submitPouchBill(){
	if($('#invoice_ids').val() == ""){
      alert("Please select atleast 1 Invoice");
	  return false;
    }else{
      return true;
    }
  }

  $(function () {
    $('[data-bs-toggle="tooltip"]').tooltip()

	 $("#checkedAll").change(function() {
        if (this.checked) {
            $(".checkSingle").each(function() {
                this.checked=true;
                selectInvoice($(this));
            });
        } else {
            $(".checkSingle").each(function() {
                this.checked=false;
                selectInvoice($(this));
            });
        }
    });

    $(".checkSingle").click(function () {
        if ($(this).is(":checked")) {
            var isAllChecked = 0;

            $(".checkSingle").each(function() {
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

  function selectInvoice(chObj){
		if(chObj.is(":checked")){
			invoice_id = chObj.attr("id").split("_");
			invoice_ids = $('#invoice_ids').val();
			invoice_ids = invoice_ids+invoice_id[1]+",";
			$('#invoice_ids').val(invoice_ids);
			$("#iifhb").val(invoice_ids);
			$("#iifshb").val(invoice_ids);
			$("#iifdcb").val(invoice_ids);
			$("#iifpb").val(invoice_ids);
			$("#up_invoice_ids").val(invoice_ids);
			$("#corner_invoice_ids").val(invoice_ids);
			$("#export_invoice_ids").val(invoice_ids);
		}else{
			invoice_id = chObj.attr("id").split("_");
			invoice_ids = $('#invoice_ids').val().replace(invoice_id[1]+",","");
			$('#invoice_ids').val(invoice_ids);
			$("#iifhb").val(invoice_ids);
      $("#iifshb").val(invoice_ids);
      $("#iifdcb").val(invoice_ids);
      $("#iifpb").val(invoice_ids);
			$("#up_invoice_ids").val(invoice_ids);
			$("#corner_invoice_ids").val(invoice_ids);
			$("#export_invoice_ids").val(invoice_ids);
		}
	}

  function contractorBill(){
    //$('#invoice_ids').val("");
     if($('#invoice_ids').val() == ""){
      alert("Please select atleast 1 Invoice");
     }else{
      $("#contractorBillForm").submit();
     }
  }

  function upholestryBill(){
    //$('#invoice_ids').val("");
     if($('#up_invoice_ids').val() == ""){
      alert("Please select atleast 1 Invoice");
     }else{
      $("#upholestryBillForm").submit();
     }
  }

  function cornerBill(){
    //$('#invoice_ids').val("");
     if($('#corner_invoice_ids').val() == ""){
      alert("Please select atleast 1 Invoice");
     }else{
		$("#cornerBillForm").attr('action',"{{ url('/invoice/cornerbill') }}");
		$("#cornerBillForm").submit();
     }
  }

  function exportSales(){
    //$('#invoice_ids').val("");
     if($('#export_invoice_ids').val() == ""){
      alert("Please select atleast 1 Invoice");
     }else{
		$("#exportsalesBillForm").attr('action',"{{ url('/invoice/exportsalesBillForm') }}");
		$("#exportsalesBillForm").submit();
     }
  }

  function cornerBillEdit(){
    //$('#invoice_ids').val("");
     if($('#corner_invoice_ids').val() == ""){
      alert("Please select atleast 1 Invoice");
     }else{
		$("#cornerBillForm").attr('action',"{{ url('/invoice/cornerbillEdit') }}");
		$("#cornerBillForm").submit();
     }
  }

  function deleteModal(id){
    $('#deleteInvoice').modal('show');
    $('#deleteInvoiceForm').attr('action', "{{ url('/invoice/delete') }}" + '/' +id);
  }

  function updateinvoice(id){
     location.href = "{{ url('/invoice/view') }}" + '/' +id;
  }

  function shipBill(inv_id,shipping_bill_no,shipping_bill_date,ewaybillno,ewaybilldate,port_code,shipping_exchange_rate,bl_no,bl_date){
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

  function invExp(inv_id,shipping_bill_no,shipping_bill_date,booking_value,shipdawn,inrat_booking_value,tax_type){

    $("#sb_inv_id_val"+inv_id).val(inv_id);
    $("#shipping_bill_no"+inv_id).val(shipping_bill_no);
    $("#shipping_bill_date"+inv_id).val(shipping_bill_date);
    $("#booking_value"+inv_id).val(booking_value);
    $("#shipdawn"+inv_id).val(shipdawn);
    $("#inrat_booking_value"+inv_id).val(inrat_booking_value);
    $("#tax_type"+inv_id).val(tax_type);

    var trcount = $('#invoiceTable'+inv_id+' tr').length;

   var invoiceRows = trcount;
   var products = [];


    $("#addMore"+inv_id).click(function() {

        invoiceRows += 1;
        console.log(invoiceRows);
        $.each(products, function(index, value) {
            options += '<option value="' + value.id + '">' + value.code + " - " + value.name + '</option>';
        });

        var $block = "";
            $block += '<tr>';
            $block += '<td id="realisation_date'+invoiceRows+'"><input type="date" step="any" min="0" class="form-control"  data-len="' + invoiceRows + '" name="ie['+invoiceRows+'][realisation_date]" value=""  /></td>'
            $block += '<td id="realisation_fc'+invoiceRows+'"><input type="text" step="any" min="0" class="form-control"  data-len="' + invoiceRows + '" name="ie['+invoiceRows+'][realisation_fc]" value=""  /></td>'
            $block += '<td id="rate'+invoiceRows+'"><input type="number" step="any" min="0" class="form-control"  data-len="' + invoiceRows + '" name="ie['+invoiceRows+'][rate]" value=""  /></td>'
            $block += '<td id="bank_reference'+invoiceRows+'"><input type="text" step="any" min="0" class="form-control"  data-len="' + invoiceRows + '" name="ie['+invoiceRows+'][bank_reference]" value=""  /></td>'
            $block += '<td id="fbc'+invoiceRows+'"><input type="text" step="any" min="0" class="form-control"  data-len="' + invoiceRows + '" name="ie['+invoiceRows+'][fbc]" value=""  /></td>'
            $block += '<td><a href="javascript:void(0);" class="close" onclick="deleteRow(this);" >&times;</a></td>';
            $block += '</tr>';
        $("#invoiceTable"+inv_id).append($block);

    });
  }
  function deleteRow(ref) {
    $(ref).parent().parent().remove();

  }

  function invEmail(id, invoice){
    $('#invoiceEmail').modal('show');
    $('#iifemail').val(id);
    $('#inv-no').html(invoice);
  }

  $(function(){
	  var table = $("#dataTable").DataTable();
		$('#dataTable_filter').append( '<br><label>Search invoice type: <input id="pending-product-search" class="form-control" type="text" placeholder="Search invoice type" data-index="" /></label>');
		$( table.table().container() ).on( 'keyup', '#pending-product-search', function () {
			table
				.column(9)
				.search( this.value )
				.draw();
		} );
		
		ids = "";
		table.rows().data().toArray().forEach(function(item,i){
			ids += item[1] + ",";
		});
		
  });

</script>
<!-- Script End -->

<script>
  ClassicEditor
      .create( document.querySelector( '#editor' ) )
      .catch( error => {
          console.error( error );
      } );
</script>
@endsection
