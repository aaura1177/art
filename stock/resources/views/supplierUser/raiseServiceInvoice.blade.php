@extends('layouts.app')

@section('content')
    <div class="mx-2">
        <div class="row mx-0 my-2">
            <h2>Raise Service Invoice</h2>
            {{-- {{$userSplier->supplier->tdspercent}} --}}
        </div>

        <!-- <form id='createPurchaseBill' method="POST" action="{{ url('/supplier-dashboard/raise-invoice') }}/{{ $purchaseOrder->id }}">
                  @csrf
                  <input type="hidden" name="purchase_order_id" value="{{ $purchaseOrder->id }}" /> -->
        <form id='createPurchaseBill' method="POST"
            action="{{ url('/supplier-dashboard/raise-service-invoice') }}/{{ $id }}">
            @csrf
            <input type="hidden" name="purchase_order_id" value="{{ $id }}" />
            <!-- Form Starts -->
            <div class="form-group">

                <!-- first row -->
                <div class="row mt-3">
                    <div class="col-4">
                        <label class="control-label">{{ __('PO No.') }}</label> <a
                            href="{{ url('/purchaseOrder/create') }}" style="float: right;" target="_blank"> (+New)</a>
                        <select type="text" class="selectpicker" data-live-search="true" name="purchaseOrder_id"
                            onchange="changeDetails(this);" required="required">
                            <option value="" disabled>Select PO No.</option>
                            @if (isset($purchaseOrder))
                                <option selected value="{{ $purchaseOrder->id }}">
                                    {{ $purchaseOrder->pono }}
                                </option>
                            @endif
                        </select>
                    </div>
                    <div class="col-4">
                        <label class="control-label">{{ __('Supplier Name') }}</label>
                        <input type="text" class="form-control" name="supplierName" id="supplierName" readonly
                            value="{{ $purchaseOrder->supplier->c_name }}" />
                    </div>
                    <div class="col-4">
                        <label class="control-label">{{ __('Delivery Date') }}</label>
                        <input type="date" class="form-control" name="del_date" id="del_date" readonly
                            value="{{ $purchaseOrder->del_date }}" />
                    </div>
                </div>

                <!-- second row -->
                <div class="row mt-3">
                    <div class="col-4">
                        <label class="control-label">{{ __('Supplier Ref.') }}</label>
                        <input type="text" class="form-control" name="Supplier_ref" id="Supplier_ref" readonly
                            value="{{ $purchaseOrder->ref_supplier }}" />
                    </div>
                    <div class="col-4">
                        <label class="control-label">{{ __('Ordered Quantity') }}</label>
                        <input type="number" class="form-control" name="poQty" id="total_qty" readonly
                            value="{{ $purchaseOrder->tquantity }}" />
                    </div>
                    <div class="col-4">
                        <label class="control-label">{{ __('Sub Total Amount') }}</label>
                        <input type="number" class="form-control" name="poAmount" id="total_amount" readonly
                            value="{{ $purchaseOrder->tamount }}" />
                    </div>
                </div>

                <!-- forth row -->
                <div class="row mt-3">
                    <div class="col-8">
                        <label class="control-label">{{ __('Remark') }}</label>
                        <textarea class="form-control" name="remark" id="remarks" readonly>{{ $purchaseOrder->remarks }}</textarea>
                    </div>
                </div>

                <!-- second row -->
                <div class="row mt-3">
                    <div class="col-4">
                        <label class="control-label">{{ __('Supplier Invoice No.') }}</label>
                        <input type="text" class="form-control toUpperCase" id="supinv" name="supp_inv_no" required />
                    </div>

                    <div class="col-4">
                        <label class="control-label">{{ __('E-Way Bill No.') }}</label>
                        <input type="text" class="form-control toUpperCase" name="ewaybill" />
                    </div>

                    <div class="col-4">
                        <label class="control-label">{{ __('Vehicle No.') }}</label>
                        <input type="text" class="form-control toUpperCase" name="vehicle_no" id="vehicle_no" />
                    </div>
                </div>

                <!-- third row -->
                <div class="row mt-3">
                    <div class="col-4">
                        <label class="control-label">{{ __('E-Way Bill Upload') }}</label>
                        <input type="file" class="form-control toUpperCase" id="eway_file" name="eway_bill_upload" />
                    </div>

                    <div class="col-4">
                        <label class="control-label">{{ __('Supplier Invoice Date') }}</label>
                        <input type="date" class="form-control" required name="invoice_date" id="invoice_date"
                            max="<?php echo date('Y-m-d'); ?>" min="<?php echo date('Y-m-d', strtotime('-30 days')); ?>
  " />
                    </div>
                </div>


                <!-- forth row -->
                <!--<div class="row mt-3">
                    <div class="col-8">
                      <div class="input-group">
                      <span class="input-group-addon" style="border: 1px solid #ccc; padding: 0.4rem;"><i class="fa fa-barcode"></i></span>
                      <input id="myInput" type="text" class="form-control" name="enterproductname" onkeyup="searchProduct(this);" placeholder="Enter Product name / SKU / Scan bar code" disabled autocomplete="off"/>
                      </div>
                    </div>
                    </div>-->
                <div class="row mt-3">
                    <table class="table table-hover">
                        <thead>
                            <tr id="mytable">
                                <th scope="col" style="min-width: 300px;">Product</th>

                                @if ($type == 1 || $type == 2)
                                    <th scope="col">Remaining QTY</th>
                                    <th scope="col">QTY</th>
                                    <th scope="col">Unit</th>
                                    <th scope="col">Rate/Item (₹)</th>
                                @endif
                                @if ($type == 3)
                                    <th scope="col">Remaining QTY Box 1</th>
                                    <th scope="col">Remaining QTY Box 2</th>
                                    <th scope="col">QTY Box 1</th>
                                    <th scope="col">QTY Box 2</th>
                                    <th scope="col">Rate/Item (₹) (Box 1)</th>
                                    <th scope="col">Rate/Item (₹) (Box 2)</th>
                                @endif
                                <th scope="col">Amount (₹)</th>
                                <th scope="col">GSTSLAB</th>
                                <th scope="col">GST (₹)</th>
                            </tr>
                        </thead>
                        <tbody id="productTable" style="overflow-x: scroll !important">
                            <?php $productRows = 0; ?>
                            @foreach ($poTable as $p => $pots)
                                <tr id="mytable" style="overflow-x: scroll !important">
                                    <?php $productRows++; ?>
                                    <td scope="col">
                                        @if (isset($pots->product->name))
                                            {{ $pots->product->name }}
                                        @else
                                            {{ $pots->consumable->name }}
                                        @endif

                                        @if (isset($pots->product_id))
                                            <input name="pb[{{ $productRows }}][product]" type="hidden"
                                                value="{{ $pots->product_id }}" />
                                        @else
                                            <input name="pb[{{ $productRows }}][product]" type="hidden"
                                                value="{{ $pots->consumable_id }}" />
                                        @endif
                                       

                                    </td>

                                    @if ($type == 1 || $type == 2)
                                        @if ($pots->unit === 'Hours')
                                            <td scope="col">{{ $pots->remqty }}</td>
                                        @else
                                            <td scope="col">{{ number_format((float) $pots->remaining_percentage, 2, '.', '') }}%</td>
                                        @endif
                                        {{-- change --}}
                                        {{-- input --}}
                                        @if ($pots->unit === 'Hours')
                                            <td style="min-width: 100px" scope="col" id="{{ md5($p) }}"><input
                                                    class="form-control receiveqty" value="0" min="0.01" step="0.01"
                                                    max="{{ $pots->remqty }}" name="pb[{{ $productRows }}][receiveqty]"
                                                    type="number" onchange="recalQuantity(this)" readonly/></td>
                                        @else
                                            <td style="min-width: 100px" scope="col" id="{{ md5($p) }}"><input
                                                    class="form-control receiveqty" value="0" min="0"
                                                    max="{{ number_format((float) $pots->remaining_percentage, 2, '.', '') }}"
                                                    name="pb[{{ $productRows }}][remaining_percentage]" type="number" step="0.01"
                                                    onchange="handleQuantity(this);" readonly/></td>
                                        @endif




                                        {{-- <td id='+ean+'> --}}
                                        {{-- <select style="min-width: 100px" class="form-control unit-hours"
                                                data-len="'+productRows+'" name="pb[{{ $productRows }}][unit]"
                                                onchange="recalQuantity(this);" >
                                                <option value="Count" {{ $pots->unit == 'Count' ? 'selected' : '' }}>
                                                    Count</option>
                                                <option value="Hours" {{ $pots->unit == 'Hours' ? 'selected' : '' }}>
                                                    Hours</option>
                                            </select> --}}

                                        <td scope="col" id=""><input class="form-control  unit-hours"
                                                value="{{ $pots->unit }}" name="pb[{{ $productRows }}][unit]"
                                                readonly /></td>
                                        {{-- </td> --}}

                                        <td scope="col" id="rate{{ md5($p) }}"><input class="form-control rate"
                                                value="{{ $pots->rate }}" name="pb[{{ $productRows }}][rate]"
                                                readonly /></td>
                                    @endif


                                    @if ($pots->unit === 'Count')
                                        <td scope="col" id="amount{{ md5($p) }}"><input value="0"
                                                class="form-control amount" name="pb[{{ $productRows }}][amount]"
                                                type="number" max="{{ $pots->remaining_amount }}"
                                                onchange="handleAmount(this)" readonly /></td>
                                    @else
                                        <td scope="col" id="amount{{ md5($p) }}"><input value="0"
                                                class="form-control amount" name="pb[{{ $productRows }}][amount]"
                                                type="number" readonly /></td>
                                    @endif
                                    <td scope="col" id="gstslab{{ md5($p) }}"><input type="number"
                                            class="form-control gst-slab" value="{{ $pots->gstslab }}"
                                            name="pb[{{ $productRows }}][gstslab]" readonly /></td>
                                    <td scope="col" id="gstamount{{ md5($p) }}"><input value="0"
                                            class="form-control gstamount" name="pb[{{ $productRows }}][gstamount]"
                                            readonly /></td>
                                </tr>


                                <tr >
                                    
                                <td id="pr1" colspan="12">
                                    <div class="input-wrapper" id="addmoreinputsub1">
                                      @foreach ($subServiceTable as $sub)
                                      @if ($sub->serviceTable && $sub->serviceTable->id === $pots->id)                            
                                    <div class="add-more-quantity" style="margin-top:10px; display:flex; flex-wrap:wrap; gap:10px; align-items:center;">
                            
                                    <div style="flex:1;">
                                        <input type="text" class="form-control"
                                        name="po[{{ $productRows}}][id][]"
                                        value="{{ $sub->id }}" placeholder="Name" hidden
                                        required />
                                    <label>Name</label>
                                    <input type="text" class="form-control" name="po[{{$productRows}}][sub_name][]" value="{{$sub->sub_name}}" placeholder="Name" required  readonly/>
                                    </div>
                                        @if ($pots->unit === 'Count')
                                        <div style="flex:1;">
                                            <label>	Remaining percentage</label>
                                            
                                                <input type="number" min="1" class="form-control" data-len="{{$productRows}}" name="po[{{$productRows}}][sub_remaining_percentage][]" value="{{$sub->sub_remaining_percentage}}" readonly/>
                                            </div>
                                        @else
                                        <div style="flex:1;">
                                            <label>	Remaining Qty</label>
                                            
                                                <input type="number" min="0.1" step="any" class="form-control remqty-ch"  data-len="{{$productRows}}" name="po[{{$productRows}}][sub_remqty][]" value="{{$sub->sub_remqty}}" readonly />
                                            </div>
                                        @endif
                                 
                                   
                                        @if ($pots->unit === 'Count')
                                        <div style="flex:1;">
                                            <label>	 percentage</label>
                                            
                                                <input type="number" min="0" step="0.01" class="form-control quantity-ch" data-len="{{$productRows}}" name="po[{{$productRows}}][sub_percentage][]" value="" max="{{ number_format((float) $sub->sub_remaining_percentage, 2, '.', '') }}" data-max-qty="{{ number_format((float) $sub->sub_remaining_percentage, 2, '.', '') }}" oninput="handleQuantityChange(this);" onchange="handleQuantityChange(this);" />
                                            </div>
                                        @else
                                        <div style="flex:1;">
                                            <label>	 Qty</label>
                                            
                                                <input type="number" min="0.01" step="0.01" class="form-control quantity-ch"  data-len="{{$productRows}}" name="po[{{$productRows}}][sub_quantity][]" max="{{$sub->sub_remqty}}" data-remqty="{{$sub->sub_remqty}}" oninput="handleQuantityChange(this);"/>
                                            </div>
                                        @endif


                                  
                            
                                    <div style="flex:1;">
                                    <label>Rate</label>
                                    <input type="number" class="form-control rate-ch" name="po[{{$productRows}}][sub_rate][]" placeholder="Rate" value="{{$sub->sub_rate}}" required step="any" readonly />
                                    </div>
                            
                                    <div style="flex:1;">
                                    <label>Amount</label>
                                    
                                        <input type="number" step="0.01" value="0" class="form-control amount-ch"  onchange="handleAmoutChange(this);" data-len="{{$productRows}}" min="0" name="po[{{$productRows}}][sub_amount][]" max="{{ number_format((float) $sub->sub_remaining_amount, 2, '.', '') }}" placeholder="Amount"  />
                                    </div>
                            
                                    <div style="flex:1;">
                                    <label>GST Slab</label>
                                    <input type="number" min="0" class="form-control gstslab-ch"  data-len="{{$productRows}}" name="po[{{$productRows}}][sub_gstslab][]" placeholder="GST Slab" value="{{$sub->sub_gstslab}}" readOnly/>
                                    </div>
                            
                                    <div style="flex:1;">
                                    <label>GST Amount</label>
                                    <input type="number" class="form-control gstamount-ch" name="po[{{$productRows}}][sub_gstamount][]" value="0" readonly placeholder="GST Amount" />
                                    </div>
                            
                                     
                            
                                    </div>
                            
                                    @endif
                                    @endforeach
                                </div>
                            
                                    <div style="margin-top:10px;">
                                    <!-- <button type="button" class="btn btn-sm btn-primary" onclick="addMoreInputs(this)">Add More</button> -->
                                    </div>
                            
                                    </td>
                                    </tr>
                            
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <!-- forth row -->
                <div class="row mt-3">
                    <div class="col-4">
                        <label class="control-label">{{ __('Total Quantity') }}</label>
                        <input type="number" step="0.01" class="form-control" name="pbQty" id="pbQty" readonly />
                    </div>
                    <div class="col-4">
                        <label class="control-label">{{ __('Sub-Total Amount') }}</label>
                        <input type="number" class="form-control" name="pbSubTotal" id="pbSubTotal" readonly />
                    </div>
                    <div class="col-4">
                        <label class="control-label">{{ __('GST') }}</label>
                        <input type="number" class="form-control" name="pbGST" id="pbGST" readonly />
                    </div>
                </div>

                <div class="row mt-3">

                    <div class="col-4">
                        <label class="control-label">{{ __('Total Amount') }}</label>
                        <input type="number" class="form-control" name="pbTotal" id="pbTotal" readonly />
                        <input type="text" name="type" id='invoiceTotal' value="{{ $invoiceTotal }}" hidden />
                        {{-- <input type="text" id="print"> --}}
                    </div>


                    <div class="col-4">
                        <label class="control-label">{{ __('Total TDS') }}</label>
                        <input type="number" class="form-control" name="tdsTotal" id="tdsToal" readonly />
                        <input type="number" class="form-control" name="" id="tdsPercent"
                            value="{{ $userSplier->supplier->tdspercent }}" hidden />
                        <input type="date" class="form-control" name="" id="tdsDate"
                            value="{{ $userSplier->supplier->tdsdate }}" hidden />

                        {{-- <input type="text" id="print"> --}}
                    </div>
                </div>
                <div class="row col-4">
                    <button type="submit" id="hidden" hidden class="btn btn-primary mt-3" disabled>Create Purchase
                        Bill</button>
                    <button type="submit" id="formSubmit" form="createPurchaseBill" class="btn btn-primary mt-3">Raise
                        Invoice</button>
                </div>

            </div>
        </form>
    </div>
@endsection

@section('footer')
    <!-- hi -->
    <!-- Script start for get data from database -->
    <script type="text/javascript">
        $(document).ready(function() {

            $.ajax({
                'url': "{{ url('/supplier-dashboard/data') }}",
                'method': 'GET'

            }).done(function(data) {
                if (data) {
                    purchaseOrder = data.purchaseOrder;
                    supplier = data.supplier;
                    pbProduct = data.product;
                }
            });
        });

        var purchaseOrder = [];
        var supplier = [];
        var pbProduct = [];
        var pbPoTable = [];
        var productRows = 0;

        function changeDetails(ref) {
            var id = $(ref).val();
            $("#productTable tr").remove();

            $.ajax({
                'url': "{{ url('/supplier-dashboard/data/serviceTable') }}" + '/' + id,
                'method': 'GET'

            }).done(function(data) {
                if (data) {
                    pbPoTable = data.poTable;
                }
            });

            function findPo(purchaseBill) {
                return purchaseBill.id == id;
            };

            function findSupplier(supplierName) {
                return supplierName.id == supplierId;
            };

            var purchaseBill = purchaseOrder.find(findPo);
            var supplierId = purchaseBill.supplier_id;
            var supplierName = supplier.find(findSupplier);
            $("#supplierName").val(supplierName.c_name);
            $("#del_date").val(purchaseBill.del_date);
            $("#Supplier_ref").val(purchaseBill.ref_supplier);
            $("#remarks").val(purchaseBill.remarks);
            $("#total_qty").val(purchaseBill.tquantity);
            $("#total_amount").val(purchaseBill.tamount);
            $("#myInput").removeAttr('disabled');
            $("#supinv").select().focus();

        };


        function checkUnitType(ref) {
            const tr = ref.closest('tr');
            console.log(tr, 'tr');

            if (!tr) return;

            const unitElement = tr.getElementsByClassName('unit-hours')[0];
            console.log(unitElement, 'unitElement');

            const rateElement = tr.getElementsByClassName('rate')[0];
            if (!rateElement) return;
            console.log(rateElement, 'rate Element');
            if (!unitElement) return;

            const unit = unitElement.value;
            console.log(unit, 'unot');

            let rate = rateElement.value;
            rate = Number(rate);
            console.log(rate, 'rate');

            console.log(unit, 'unit');

            const quantityElement = tr.getElementsByClassName('receiveqty')[0];
            if (quantityElement) {
                if (unit === 'Count') {
                    // quantityElement.value = 1;
                    quantityElement.setAttribute('max', 100);
                    console.log((unit * rate) / 100, 'rate');

                    rateElement.value = (unit * Number(rate)) / 100;
                } else {}
            }
            // recalQuantity(ref);
        }

        const roundMoney = (value) => {
            const n = parseFloat(value);
            if (isNaN(n)) {
                return 0;
            }
            return Math.round(n * 100) / 100;
        };

        const formatMoney = (value) => roundMoney(value).toFixed(2);

        const gstFromAmount = (amount, gstRate) => roundMoney((parseFloat(amount) || 0) * (parseFloat(gstRate) || 0) / 100);

        const amountFromRateAndQty = (rate, quantity, unitValue) => {
            const r = parseFloat(rate) || 0;
            const q = parseFloat(quantity) || 0;
            return unitValue === 'Count' ? roundMoney((r * q) / 100) : roundMoney(r * q);
        };

        const clampAmountInput = (input, amount) => {
            let value = roundMoney(amount);
            const max = parseFloat(input.getAttribute('max'));
            if (!isNaN(max) && max >= 0 && value > max) {
                value = max;
            }
            input.value = formatMoney(value);
            return value;
        };

        const syncQtyFromAmount = (qtyInput, amount, rate, unitValue) => {
            const r = parseFloat(rate) || 0;
            if (!qtyInput || r <= 0) {
                return 0;
            }

            let qty = unitValue === 'Count' ? roundMoney((amount / r) * 100) : roundMoney(amount / r);
            qtyInput.value = qty > 0 ? parseFloat(qty.toFixed(2)) : '';
            return qty;
        };

        function handleQuantity(ref) {
            let currentDate = new Date($('#invoice_date').val());
            console.log($('#invoice_date').val(), 'currentDate');

            if (!currentDate || currentDate == 'Invalid Date' || currentDate == '') {
                alert('Please select Invoice Date');
                ref.value = 0;
                return;
            }
            const tr = ref.closest('tr');
            const unitElement = tr.getElementsByClassName('unit-hours')[0];


            const quantityElement = tr.getElementsByClassName('receiveqty')[0];
            const amountElement = tr.getElementsByClassName('amount')[0];
            const gstAmount = tr.getElementsByClassName('gstamount')[0];
            const gstSlab = tr.getElementsByClassName('gst-slab')[0].value;
            const rateElement = tr.getElementsByClassName('rate')[0];

            amountElement.value = formatMoney((Number(rateElement.value) * quantityElement.value) / 100);
            gstAmount.value = formatMoney((amountElement.value * gstSlab) / 100);
            console.log(amountElement.value, ' amountElement.value');

            calculateTotal();

        }

        function handleAmount(ref) {
            let currentDate = new Date($('#invoice_date').val());
            console.log($('#invoice_date').val(), 'currentDate');

            if (!currentDate || currentDate == 'Invalid Date' || currentDate == '') {
                alert('Please select Invoice Date');
                ref.value = 0;
                return;
            }
            const tr = ref.closest('tr');
            const unitElement = tr.getElementsByClassName('unit-hours')[0];


            const quantityElement = tr.getElementsByClassName('receiveqty')[0];
            const amountElement = tr.getElementsByClassName('amount')[0];
            const rateElement = tr.getElementsByClassName('rate')[0];
            const gstAmount = tr.getElementsByClassName('gstamount')[0];
            const gstSlab = tr.getElementsByClassName('gst-slab')[0].value;

            amountElement.value = formatMoney(amountElement.value);
            quantityElement.value = ((Number(amountElement.value) / rateElement.value) * 100).toFixed(2);
            gstAmount.value = formatMoney((amountElement.value * gstSlab) / 100);

            console.log(amountElement.value, ' amountElement.value');

            calculateTotal()

        }



        function recalQuantity(ref) {


            let currentDate = new Date($('#invoice_date').val());
            console.log($('#invoice_date').val(), 'currentDate');

            if (!currentDate || currentDate == 'Invalid Date' || currentDate == '') {
                alert('Please select Invoice Date');
                ref.value = 0;
                return;
            }
            quantity = parseFloat($(ref).val()) || 0;
            var rqean = $(ref).parent().attr('id');
            //if(quantity>0){
            var remqty = $('#remqty' + rqean + ' input').val(); //MYcode
            var rate = $('#rate' + rqean + ' input').val();
            var gstslab = $('#gstslab' + rqean + ' input').val();
            var oldrate = roundMoney(rate * quantity);
            $("#amount" + rqean + " input").val(formatMoney(oldrate));
            var gstamount = gstFromAmount(oldrate, gstslab);
            $('#gstamount' + rqean + ' input').val(formatMoney(gstamount));
            $("#myInput").select().focus();

            calculateTotal();

            //}

            // else{
            //   alert("Quantity can not be less than 1.");
            //   $("#myInput").select().focus();
            // }
        };

        function deleteRow(ref) {
            $(ref).parents("tr").remove();
            $("#myInput").select().focus();
            calculateTotal();
        };

        function calculateTotal() {
            var arrTotQty = [];
            var arrSubTot = [];
            var arrgstamount = [];
            var totQty = 0;
            var subTot = 0;
            var gstamount = 0;
            var unit = []; // Initialize an empty array for unit

            arrTotQty = $('.receiveqty');
            arrSubTot = $('.amount');
            arrgstamount = $('.gstamount');
            unit = $('.unit-hours'); // Corrected how the unit array is populated

            console.log(unit);

            // Calculate total quantity
            $.each(arrTotQty, function(index, value) {
                if (unit[index].value == 'Count') {
                    totQty += 1;
                } else {
                    totQty += parseFloat(arrTotQty[index].value || 0);
                }
            });

            // Calculate subtotal
            $.each(arrSubTot, function(index, value) {
                subTot += parseFloat(arrSubTot[index].value, 10);
            });

            // Calculate GST amount
            $.each(arrgstamount, function(index, value) {
                gstamount += parseFloat(arrgstamount[index].value, 10);
            });

            // Calculate total price before freight
            var pbTotal = subTot + gstamount;
            gstamount = gstamount.toFixed(2);
            subTot = subTot.toFixed(2);
            pbTotal = parseFloat(pbTotal).toFixed(2);

            // Set the values in the input fields
            $('#pbSubTotal').val(subTot);
            $('#pbQty').val(totQty.toFixed(2));
            $('#pbGST').val(gstamount);
            $('#pbTotal').val(pbTotal);

            // Get the total amount from the invoice and calculate the final total
            var potamount = $('#pbTotal').val();
            console.log(potamount);
            console.log(document.getElementById('invoiceTotal').value);

            let totalAmount = Number(document.getElementById('invoiceTotal').value) + Number(potamount);

            // Check if the total amount exceeds 100,000 to enable eway bill fields
            if (totalAmount > 100000) {
                $('#ewaybill').attr('required', true);
                $('#eway_file').attr('required', true);
            } else {
                $('#ewaybill').removeAttr('required');
                $('#eway_file').removeAttr('required');
            }

            // Handle TDS
            handleTds(document.getElementById('pbSubTotal').value);
        }



        function calculateTotalCarton() {
            var arrTotQty1 = [];
            var arrTotQty2 = [];
            var arrSubTot = [];
            var arrgstamount = [];
            var totQty = 0;
            var subTot = 0;
            var gstamount = 0;

            arrTotQty1 = $('.receiveqty1');
            arrTotQty2 = $('.receiveqty2');
            arrSubTot = $('.amount');
            arrgstamount = $('.gstamount');

            $.each(arrTotQty1, function(index, value) {
                totQty += parseInt(arrTotQty1[index].value, 10);
            });

            $.each(arrTotQty2, function(index, value) {
                totQty += parseInt(arrTotQty2[index].value, 10);
            });

            $.each(arrSubTot, function(index, value) {
                subTot += parseFloat(arrSubTot[index].value, 10);
            });

            $.each(arrgstamount, function(index, value) {
                gstamount += parseFloat(arrgstamount[index].value, 10);
            });

            //var  freightRate = parseFloat($('#freight').val(),10);
            var pbTotal = subTot + gstamount;
            gstamount = gstamount.toFixed(2);
            subTot = subTot.toFixed(2);
            pbTotal = parseFloat(pbTotal).toFixed(2);
            $('#pbSubTotal').val(subTot);
            $('#pbQty').val(totQty);
            $('#pbGST').val(gstamount);
            $('#pbTotal').val(pbTotal);

            var potamount = $('#pbTotal').val();
            if (potamount > 100000) {
                $('#ewaybill').attr('required', true);
            } else {
                $('#ewaybill').removeAttr('required', false);
            }
        }

        $('#createPurchaseBill').on('submit', function(e) {
            let qtyExceeded = false;
            let exceededUnit = '';
            $('.add-more-quantity').each(function() {
                const block = $(this);
                const parentUnit = block.closest('tr').prev().find('.unit-hours').val();
                let maxQty = 0;
                if (parentUnit === 'Count') {
                    maxQty = parseFloat(block.find('[name*="sub_remaining_percentage"]').val())
                        || parseFloat(block.find('.quantity-ch').attr('max'))
                        || 0;
                } else {
                    maxQty = parseFloat(block.find('.remqty-ch').val()) || 0;
                }
                const qty = parseFloat(block.find('.quantity-ch').val()) || 0;
                if (qty > maxQty) {
                    qtyExceeded = true;
                    exceededUnit = parentUnit;
                    return false;
                }
            });

            if (qtyExceeded) {
                alert(exceededUnit === 'Count'
                    ? 'Percentage cannot be greater than Remaining percentage.'
                    : 'Qty cannot be greater than Remaining Qty.');
                e.preventDefault();
                return;
            }

            if ($('#productTable tr').length < 1) {
                alert("No product added. Add atleast 1 product.");
                event.preventDefault();
            } else {
                $('#formSubmit').prop('disabled', 'true');
            }
        });

        function searchProduct(ref) {

            if (event.keyCode === 13) {
                var ean = $(ref).val();

                function findPbProduct(poProduct) {
                    return poProduct.EAN == ean;
                };

                function checkProduct(productCheck) {
                    return productCheck.product_id == poProductId;
                };

                var poProduct = pbProduct.find(findPbProduct);

                if (poProduct) {
                    var poProductId = poProduct.id;
                    var productCheck = pbPoTable.find(checkProduct);


                    if (productCheck) {
                        var productCheckRemQty = productCheck.remqty;
                        if (productCheckRemQty > 0) {
                            var productCheckQuantity = productCheck.quantity;
                            var quantity = 1;
                            if ($('#' + ean).length) {
                                quantity = parseInt($('#' + ean + ' input').val(), 10);
                                var gstslab = $('#gstslab' + ean + ' input').val();
                                var newQuantity = quantity + 1;
                                if (newQuantity <= productCheckRemQty) {
                                    $('#' + ean + ' input').val(newQuantity);
                                    $('#amount' + ean + ' input').val(newQuantity * productCheck.rate);
                                    var amount = $('#amount' + ean + ' input').val();
                                    var gstamount = (amount * gstslab) / 100;
                                    $('#gstamount' + ean + ' input').val(gstamount.toFixed(2));
                                    calculateTotal();
                                } else {
                                    alert("Maximum Ordered Quantity Reached.");
                                }
                            } else {

                                productRows += 1;

                                var $block = "";
                                $block += '<tr>';
                                $block += '<td><select type="text" class="selectpicker" data-live-search="true" name="pb[' +
                                    productRows + '][product]" readonly><option value="' + poProduct.id + '">' + poProduct
                                    .name + '</option></td>';
                                $block += '<td><input type="text" class="form-control" name="pb[' + productRows +
                                    '][EAN]" readonly value="' + poProduct.EAN + '" /></td>';
                                $block += '<td id="remqty' + ean +
                                    '"><input type="number" class="form-control remqty" data-len="' + productRows +
                                    '" name="pb[' + productRows + '][remqty]" value="' + productCheck.remqty +
                                    '" readonly/></td>'
                                $block += '<td id=' + ean +
                                    '><input type="number" min="1" class="form-control receiveqty" data-len="' +
                                    productRows + '" name="pb[' + productRows +
                                    '][receiveqty]" value="1" readonly/><a  href="javascript:;" onclick="reduceQuantity(this);"><i class="fa fa-minus"></i></a></td>'
                                //  new added 
                                // $block += '<td id='+ean+'><select class="form-control" data-len="'+productRows+'" name="pb['+productRows+'][unit]"><option value="count">Count</option><option value="hours">Hours</option></select></td>';

                                $block += '<td id="rate' + ean +
                                    '"><input type="number" class="form-control rate" name="pb[' + productRows +
                                    '][rate]" readonly value="' + productCheck.rate + '" /></td>';
                                $block += '<td id=amount' + ean +
                                    '><input type="number" class="form-control amount" name="pb[' + productRows +
                                    '][amount]" readonly value="' + productCheck.rate + '" /></td>';
                                $block += '<td id="gstslab' + ean +
                                    '"><input type="number" class="form-control gstslab" name="pb[' + productRows +
                                    '][gstslab]" readonly value="' + productCheck.gstslab + '" /></td>';
                                $block += '<td id="gstamount' + ean +
                                    '"><input type="number" class="form-control gstamount" name="pb[' + productRows +
                                    '][gstamount]" readonly value="' + ((productCheck.gstslab * productCheck.rate / 100)
                                        .toFixed(2)) + '" /></td>';
                                $block += '<td id="location' + ean +
                                    '"><input type="text" class="form-control location" name="pb[' + productRows +
                                    '][location]" value="" /></td>';
                                $block +=
                                    '<td><button type="button" class="close" onclick="deleteRow(this);" data-bs-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button></td>';
                                $block += '</tr>';
                                $("#productTable").append($block);
                                $('.selectpicker').selectpicker();
                                calculateTotal();

                            }
                        } else {
                            alert('Product Remaining Quantity is Zero');
                        }
                    } else {
                        alert('Product NOT Found in this Inward Supply');

                    }
                } else {
                    alert('Product NOT Found');

                }

                $("#myInput").select().focus();
            };
        };

        function handleTds(pbSubTotal) {
            // console.log(pbTotal,typeof pbTotal);


            let currentDate = new Date($('#invoice_date').val());
            if (currentDate) {
                let tdsDate = new Date($('#tdsDate').val());
                if (currentDate > tdsDate) {
                    let tdsAmount = (Number(pbSubTotal) * document.getElementById('tdsPercent').value) / 100
                    document.getElementById('tdsToal').value = tdsAmount.toFixed(2);
                } else {
                    document.getElementById('tdsToal').value = 0;
                }
            }

        }

        // new script 

        const getDecimalPlaces = (value) => {
            const parts = String(value).split('.');
            return parts[1] ? parts[1].length : 0;
        };

        const clampSubQuantity = (qtyInput) => {
            const block = qtyInput.closest('.add-more-quantity');
            if (!block) return parseFloat(qtyInput.value) || 0;

            const parentTr = block.closest('tr')?.previousElementSibling;
            const unitValue = parentTr?.querySelector('.unit-hours')?.value;

            let maxAllowed = 0;
            if (unitValue === 'Count') {
                const remPctInput = block.querySelector('[name*="sub_remaining_percentage"]');
                maxAllowed = parseFloat(qtyInput.dataset.maxQty)
                    || parseFloat(remPctInput?.value)
                    || parseFloat(qtyInput.getAttribute('max'))
                    || 0;
            } else {
                const remqtyInput = block.querySelector('.remqty-ch');
                maxAllowed = parseFloat(remqtyInput?.value) || parseFloat(qtyInput.dataset.remqty) || 0;
            }

            const decimals = Math.max(getDecimalPlaces(maxAllowed), 2);
            qtyInput.setAttribute('max', maxAllowed);

            let qty = parseFloat(qtyInput.value);
            if (isNaN(qty) || qty < 0) {
                qtyInput.value = '';
                return 0;
            }

            qty = Math.round(qty * Math.pow(10, decimals)) / Math.pow(10, decimals);
            if (qty > maxAllowed) {
                qty = maxAllowed;
            }

            qtyInput.value = qty > 0 ? parseFloat(qty.toFixed(decimals)) : '';
            return qty;
        };

        const calculateChildSum = (ref , unitValue)=>{
            const tr = ref.closest('tr');
            const parentTr = tr.previousElementSibling;
            console.log(parentTr);
            

            // let rate = 0;
            let amount = 0;
            let quantity = 0;
            let gstAmount = 0;

            const amountElems = tr.querySelectorAll('.amount-ch');
            const quantityElems = tr.querySelectorAll('.quantity-ch');
            const gstSlabElems = tr.querySelectorAll('.gstslab-ch');
            const gstAmountElems = tr.querySelectorAll('.gstamount-ch');


            for(let i = 0 ; i < amountElems.length ; i++){
                amount += Number(amountElems[i].value);
                quantity += Number(quantityElems[i].value);
                gstAmount += Number(gstAmountElems[i].value);
            }

            amount = roundMoney(amount);
            gstAmount = roundMoney(gstAmount);

            const rate = parentTr.querySelector('.rate').value;

            if (unitValue === 'Count') {
                parentTr.querySelector('.receiveqty').value = ((amount / rate) * 100).toFixed(2);
            } else {
                // For Hours, preserve decimal values
                parentTr.querySelector('.receiveqty').value = parseFloat(quantity).toFixed(2);
            }

            parentTr.querySelector('.amount').value = formatMoney(amount);
            parentTr.querySelector('.gstamount').value = formatMoney(gstAmount);
           
            calculateTotal();

        }
    
        const handleQuantityChange = (ref)=>{
            
            const tr = ref.closest('.add-more-quantity');
            const unitValue = ref.closest('tr').previousElementSibling.querySelector('.unit-hours').value;
            const quantity = clampSubQuantity(ref);
            
            const rate = tr.querySelector('.rate-ch').value;
            const gstrate = tr.querySelector('.gstslab-ch').value;
            const amountInput = tr.querySelector('.amount-ch');

            let amount = amountFromRateAndQty(rate, quantity, unitValue);
            amount = clampAmountInput(amountInput, amount);
            syncQtyFromAmount(ref, amount, rate, unitValue);

            tr.querySelector('.gstamount-ch').value = formatMoney(gstFromAmount(amount, gstrate));
            calculateChildSum(ref , unitValue);
        }

        const handleAmoutChange = (ref)=>{
            
            const tr = ref.closest('.add-more-quantity');
            const unitValue = ref.closest('tr').previousElementSibling.querySelector('.unit-hours').value;
            const rate = tr.querySelector('.rate-ch').value;
            const gstrate = tr.querySelector('.gstslab-ch').value;
            const qtyInput = tr.querySelector('.quantity-ch');

            let amount = clampAmountInput(ref, parseFloat(ref.value) || 0);
            syncQtyFromAmount(qtyInput, amount, rate, unitValue);
            clampSubQuantity(qtyInput);

            amount = amountFromRateAndQty(rate, qtyInput.value, unitValue);
            amount = clampAmountInput(ref, amount);

            tr.querySelector('.gstamount-ch').value = formatMoney(gstFromAmount(amount, gstrate));
            calculateChildSum(ref , unitValue);
        }

        // const calculateTotal = (ref) =>{
        //     const table = document.querySelector('table');

        //     let amountElement = table.querySelectorAll('.amount'); 
        //     let gstAmountElement = table.querySelectorAll('.gstamount'); 
        //     let qtyElement = table.querySelectorAll('.receiveqty');
        //     let unitElement = table.querySelectorAll('.unit-hours');

        //     let amount = 0;
        //     let quantity  = 0;
        //     let gstAmount = 0;
        //     for(let i = 0 ; i < amountElement.length ; i++){
        //         amount += Number(amountElement[i].value)
        //         gstAmount += Number(gstAmountElement[i].value)

        //         if(unitElement[i].value === 'Count') quantity ++;
        //         else if(unitElement[i].value === 'Hours') quantity +=  Number(qtyElement[i].value);
        //     }

        //     // total table inputs value set;

        //     document.querySelector('#pbQty').value = quantity.toFixed(2);
        //     document.querySelector('#pbSubTotal').value = amount.toFixed(2);
        //     document.querySelector('#pbGST').value = gstAmount.toFixed(2);
        //     document.querySelector('#pbTotal').value = (gstAmount + amount).toFixed(2);
        // }
    </script>
    <!-- Script end -->
@endsection
