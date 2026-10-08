@extends('layouts.app')

@section('content')

    <div class="mx-2">
        <div class="row mx-0 my-2">
            <h2>Update PO</h2>
        </div>

        <form id="myForm" method="POST" action="{{ url('/purchaseOrder/viewCartonPo/' . $purchaseOrder->id) }}">
            @csrf

            <!-- Form Starts -->
            <div class="form-group">

                <!-- first row -->
                <div class="row mt-3">
                    <div class="col-4">
                        <label class="control-label">{{ __('PO No.') }}</label>
                        <input type="text" class="form-control" name="pono" required="required"
                            value="{{ $purchaseOrder->pono }}" />
                    </div>
                    <div class="col-4">
                        <label class="control-label">{{ __('Supplier') }}</label><a href="{{ url('/supplier/create') }}"
                            style="float: right;" target="_blank"> (+New)</a>
                        <select type="text" class="selectpicker" data-live-search="true" name="supplier_id"
                            required="required" id="supplier_id">
                            <option value="" selected disabled>Select Supplier</option>
                            @if (isset($supplier))
                                @foreach ($supplier as $key => $supplier)
                                    <option value="{{ $supplier->id }}"
                                        {{ $purchaseOrder->supplier_id == $supplier->id ? 'selected' : '' }}>
                                        {{ $supplier->c_name }}
                                    </option>
                                @endforeach
                            @endif
                        </select>
                    </div>
                    @foreach ($pricingDetails as $pricing)
                        <div style="display:none;" id="supplier-3ply-{{ $pricing->supplier_id }}">
                            {{ $pricing->{"3ply"} }}
                        </div>
                        <div style="display:none;" id="supplier-5ply-{{ $pricing->supplier_id }}">
                            {{ $pricing->{"5ply"} }}</div>
                        <div style="display:none;" id="supplier-7ply-{{ $pricing->supplier_id }}">
                            {{ $pricing->{"7ply"} }}</div>
                    @endforeach
                </div>

                <div class="row mt-3">
                    <div class="col-4">
                        <label class="control-label">{{ __('Date of PO') }}</label>
                        <input type="date" class="form-control" name="podate" value="{{ $purchaseOrder->podate }}"
                            required="required" />
                    </div>
                    <div class="col-4">
                        <label class="control-label">{{ __('Delivery Date') }}</label>
                        <input type="date" class="form-control" name="del_date" value="{{ $purchaseOrder->del_date }}"
                            required="required" />
                    </div>
                </div>

                <!-- second row -->
                <div class="row mt-3">
                    <div class="col-4">
                        <label class="control-label">{{ __('Supplier Month') }}</label>
                        <select class="selectpicker" name="month[]" required="required" multiple>
                            <option value="{{ date('m-Y') }}"
                                {{ in_array(date('m-Y'), explode(',', $purchaseOrder->month)) ? 'selected' : '' }}>
                                {{ date('F Y') }}</option>
                            <option value="{{ date('m-Y', strtotime('previous month')) }}"
                                {{ in_array(date('m-Y', strtotime('previous month')), explode(',', $purchaseOrder->month)) ? 'selected' : '' }}>
                                {{ date('F Y', strtotime('previous month')) }}</option>
                        </select>
                    </div>
                    <div class="col-4">
                        <label class="control-label">{{ __('Buyer Order Number') }}</label>
                        <input type="text" class="form-control toUpperCase" name="buyer_orderno"
                            value="{{ $purchaseOrder->buyer_orderno }}" required="required" />
                    </div>
                </div>

                <!-- third row -->
                <div class="row mt-3">
                    <div class="col-4">
                        <label class="control-label">{{ __('Terms of Payment') }}</label>
                        <textarea class="form-control" name="payterms">{{ $purchaseOrder->payterms }}</textarea>
                    </div>
                    <div class="col-8">
                        <label class="control-label">{{ __('Remarks') }}</label>
                        <textarea class="form-control" name="remarks">{{ $purchaseOrder->remarks }}</textarea>
                    </div>
                </div>

                <!-- forth Product row -->
                <div class="row mt-5 my-3">
                    <div class="col-6">
                        <h5>Product List</h5>
                    </div>
                </div>

                <!-- Product details -->
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr id="mytable">
                                <th scope="col" style="min-width: 250px;">Product</th>
                                <th scope="col" style="min-width: 100px;">Qty</th>
                                <th scope="col" style="min-width: 100px;">Line Drawing</th>
                                <th scope="col" style="min-width: 100px;">Box 1 Size</th>
                                <th scope="col" style="min-width: 100px;">Box 1 Sq Inches</th>
                                <th scope="col" style="min-width: 100px;">Box 1 Ply</th>
                                <th scope="col" style="min-width: 100px;">Box 1 Rate</th>
                                <th scope="col" style="min-width: 100px;">Box 1 Amount</th>
                                <th scope="col" style="min-width: 100px;">Box 2 Size</th>
                                <th scope="col" style="min-width: 100px;">Box 2 Sq Inches</th>
                                <th scope="col" style="min-width: 100px;">Box 2 Qty</th>
                                <th scope="col" style="min-width: 100px;">Box 2 Ply</th>
                                <th scope="col" style="min-width: 100px;">Box 2 Rate</th>
                                <th scope="col" style="min-width: 100px;">Box 2 Amount</th>
                                <th scope="col" style="min-width: 150px;">Amount (₹)</th>
                                <th scope="col" style="min-width: 100px;">GST Slab (%)</th>
                                <th scope="col" style="min-width: 130px;">GST (₹)</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody id="productTable">
                            @if (isset($poTable))
                                @foreach ($poTable as $key => $poTable)
                                    <tr>
                                        <td id="pr{{ $poTable->product->id }}">
                                            <select type="text" class="selectpicker" data-live-search="true"
                                                data-len="{{ $key + 1 }}" name="po[{{ $key + 1 }}][product]"
                                                required="required">
                                                <option value="{{ $poTable->product->id }}">
                                                    {{ $poTable->product->name }}
                                                </option>
                                            </select>
                                        </td>

                                        <td id="quan{{ $key + 1 }}">
                                            <input type="number" min="0" style="width:60px;" class="form-control carton-box1-qty"
                                                onchange="changePrice(this);" name="po[{{ $key + 1 }}][quantity]"
                                                data-len="{{ $key + 1 }}" value="{{ $poTable->quantity }}" />
                                        </td>
                                        <td id="lineDrawing{{ $key + 1 }}">
                                            <select class="form-control" data-len="{{ $key + 1 }}"
                                                name="po[{{ $key + 1 }}][line_drawing]">
                                                <option value="Yes"
                                                    {{ $poTable->line_drawing == 'Yes' ? 'selected' : '' }}>
                                                    Yes</option>
                                                <option value="No"
                                                    {{ $poTable->line_drawing == 'No' ? 'selected' : '' }}>No
                                                </option>
                                            </select>
                                        </td>
                                        <td id="box1size{{ $key + 1 }}" style="min-width:155px;">
                                            <input onchange="changeBox(this)"
                                                style="width:60px;display:inline-block;padding:5px;" type="number"
                                                class="form-control box1_height" step=".1" data-live-search="true"
                                                name="po[{{ $key + 1 }}][box1_height]" required="required"
                                                value="{{ $poTable->box1_height }}" data-len="{{ $key + 1 }}" />
                                            <input onchange="changeBox(this)"
                                                style="width:60px;display:inline-block;padding:5px;" type="number"
                                                class="form-control box1_width" step=".1" data-live-search="true"
                                                name="po[{{ $key + 1 }}][box1_width]" required="required"
                                                value="{{ $poTable->box1_width }}" data-len="{{ $key + 1 }}" />
                                            <input onchange="changeBox(this)"
                                                style="width:60px;display:inline-block;padding:5px;" type="number"
                                                class="form-control box1_depth" step=".1" data-live-search="true"
                                                name="po[{{ $key + 1 }}][box1_depth]" required="required"
                                                value="{{ $poTable->box1_depth }}" data-len="{{ $key + 1 }}" />
                                        </td>
                                        <td id="box1sqinch{{ $key + 1 }}">
                                            <input type="text" class="form-control box1_sqinch" data-live-search="true"
                                                name="po[{{ $key + 1 }}][box1_sqinch]" required="required"
                                                value="{{ $poTable->box1_sqinch }}" readonly="readonly" />
                                        </td>
                                        <td id="box1_ply{{ $key + 1 }}">
                                            <input onchange="changeBox(this)" type="text" class="form-control box1_ply"
                                                data-live-search="true" name="po[{{ $key + 1 }}][box1_ply]"
                                                required="required" value="{{ $poTable->box1_ply }}"
                                                data-len="{{ $key + 1 }}" />
                                        </td>

                                        <td id="box1_rate{{ $key + 1 }}">
                                            <input type="text" class="form-control box1_rate" data-live-search="true"
                                                name="po[{{ $key + 1 }}][box1_rate]" required="required"
                                                value="{{ $poTable->box1_rate }}" readonly="readonly" />
                                        </td>

                                        <td id="box1_amount{{ $key + 1 }}">
                                            <input type="text" class="form-control box1_amount" data-live-search="true"
                                                name="po[{{ $key + 1 }}][box1_amount]" required="required"
                                                value="{{ $poTable->box1_amount }}" readonly="readonly" />
                                        </td>

                                        <td id="box2size{{ $key + 1 }}" style="min-width:155px;">
                                            <input onchange="changeBox(this)" data-len="{{ $key + 1 }}"
                                                style="width:60px;display:inline-block;padding:5px;" step=".1" type="number"
                                                class="form-control box2_height" data-live-search="true"
                                                name="po[{{ $key + 1 }}][box2_height]" required="required"
                                                value="{{ $poTable->box2_height }}"
                                                {{ $poTable->box2_ply === 0.0 ? 'readonly' : '' }} />
                                            <input onchange="changeBox(this)" data-len="{{ $key + 1 }}"
                                                style="width:60px;display:inline-block;padding:5px;" step=".1" type="number"
                                                class="form-control box2_width" data-live-search="true"
                                                name="po[{{ $key + 1 }}][box2_width]" required="required"
                                                value="{{ $poTable->box2_width }}"
                                                {{ $poTable->box2_ply === 0.0 ? 'readonly' : '' }} />
                                            <input onchange="changeBox(this)" data-len="{{ $key + 1 }}"
                                                style="width:60px;display:inline-block;padding:5px;"  step=".1" type="number"
                                                class="form-control box2_depth" data-live-search="true"
                                                name="po[{{ $key + 1 }}][box2_depth]" required="required"
                                                value="{{ $poTable->box2_depth }}"
                                                {{ $poTable->box2_ply === 0.0 ? 'readonly' : '' }} />
                                        </td>
                                        <td id="box2sqinch{{ $key + 1 }}">
                                            <input type="text" class="form-control box2_sqinch" data-live-search="true"
                                                name="po[{{ $key + 1 }}][box2_sqinch]" required="required"
                                                value="{{ $poTable->box2_sqinch }}" readonly="readonly" />
                                        </td>
                                        <td id="box2_qty{{ $key + 1 }}">
                                            <input onchange="changeBox(this)" data-len="{{ $key + 1 }}" type="number" min="0"
                                                class="form-control carton-box2-qty" onchange="changePrice(this);" data-live-search="true"
                                                name="po[{{ $key + 1 }}][box2_qty]" required="required"
                                                value="{{ $poTable->box2_qty }}"
                                                {{ $poTable->box2_qty === 0.0 ? 'readonly' : '' }} />
                                        </td>
                                        <td id="box2_ply{{ $key + 1 }}">
                                            <input onchange="changeBox(this)" data-len="{{ $key + 1 }}" type="text"
                                                class="form-control box2_ply" data-live-search="true"
                                                name="po[{{ $key + 1 }}][box2_ply]" required="required"
                                                value="{{ $poTable->box2_ply }}"
                                                {{ $poTable->box2_ply === 0.0 ? 'readonly' : '' }} />
                                        </td>

                                        <td id="box2_rate{{ $key + 1 }}">
                                            <input type="text" class="form-control box2_rate" data-live-search="true"
                                                name="po[{{ $key + 1 }}][box2_rate]" required="required"
                                                value="{{ $poTable->box2_rate }}" readonly="readonly" />
                                        </td>
                                        <td id="box2_amount{{ $key + 1 }}">
                                            <input type="text" class="form-control box2_amount" data-live-search="true"
                                                name="po[{{ $key + 1 }}][box2_amount]" required="required"
                                                value="{{ $poTable->box2_amount }}" readonly="readonly" />
                                        </td>

                                        <td id="amount{{ $key + 1 }}">
                                            <input type="number" class="form-control amount"
                                                name="po[{{ $key + 1 }}][amount]" data-len="{{ $key + 1 }}"
                                                value="{{ $poTable->amount }}" readonly />
                                        </td>
                                        <td id="gstslab{{ $key + 1 }}">
                                            <input type="number" class="form-control gstslab" min="0"
                                                onchange="changePrice(this);" name="po[{{ $key + 1 }}][gstslab]"
                                                data-len="{{ $key + 1 }}" value="{{ $poTable->gstslab }}" />
                                        </td>
                                        <td id="gstamount{{ $key + 1 }}">
                                            <input type="number" class="form-control gstamount"
                                                name="po[{{ $key + 1 }}][gstamount]" data-len="{{ $key + 1 }}"
                                                value="{{ $poTable->gstamount }}" readonly />
                                        </td>
                                        <td>
                                            <button type="button" class="close" onclick="deleteRow(this);"
                                                data-bs-dismiss="alert" aria-label="Close"><span
                                                    aria-hidden="true">&times;</span></button>
                                            <input type="hidden" id="no_of_boxes{{ $key + 1 }}"
                                                value="{{ $poTable->product->packaging->no_of_boxes }}" /><input
                                                type="hidden" id="box1type{{ $key + 1 }}"
                                                value="{{ $poTable->product->packaging->box1_type }}" /><input
                                                type="hidden" id="box2type{{ $key + 1 }}"
                                                value="{{ $poTable->product->packaging->box2_type }}" />
                                        </td>
                                        </td>
                                    </tr>
                                @endforeach
                            @endif
                        </tbody>
                    </table>
                </div>

                <div class="row mt-2">
                    <div class="col">
                        <input type="button" id="addProduct" class="btn btn-primary" value="Add Product" />
                    </div>
                </div>


                <!-- fifth row -->
                <div class="row mt-3">
                    <div class="col-4">
                        <label class="control-label">{{ __('Total GST (₹)') }}</label>
                        <input type="text" class="form-control" name="tgst" id="totalgst"
                            value="{{ $purchaseOrder->tgst }}" readonly />
                    </div>
                    <div class="col-4">
                        <label class="control-label">{{ __('Total Quantity') }}</label>
                        <input type="number" class="form-control" name="tquantity" id="tquantity"
                            value="{{ $purchaseOrder->tquantity }}" readonly />
                    </div>
                    <div class="col-4">
                        <label class="control-label">{{ __('Sub Total Amount (₹)') }}</label>
                        <input type="number" class="form-control" name="subtotalamount" id="subtotalamount"
                            value="{{ $purchaseOrder->subTotal }}" readonly />
                    </div>
                </div>


                <!-- sixth row  -->
                <div class="row mt-3">
                    <div class="col-4">
                        <label class="control-label">{{ __('Total Amount (₹)') }}</label>
                        <input type="number" class="form-control" name="tamount" id="totalamount"
                            value="{{ $purchaseOrder->tamount }}" readonly />
                    </div>
                </div>

                <div class="row col-4">
                    <button id="submitBtn" type="submit" onclick="validateSubmit();" class="btn btn-primary mt-3">Update
                        PO</button>
                </div>
            </div>
        </form>
    </div>
@endsection

@section('footer')

    <!-- Script start for get data from database -->
    <script type="text/javascript">
        $(document).ready(function() {

            $.ajax({
                'url': "{{ url('/purchaseOrder/pdata') }}",
                'method': 'GET'
            }).done(function(data) {
                if (data) {
                    products = data.packaging;
                }
            });
        });

        function changeBox(ref) {
            len = $(ref).data('len');

            var no_of_boxes = $("#no_of_boxes" + len).val();
            var box1_type = $("#box1type" + len).val();
            var box2_type = $("#box2type" + len).val();
            var box1_height = $("#box1size" + len + " .box1_height").val();
            var box1_width = $("#box1size" + len + " .box1_width").val();
            var box1_depth = $("#box1size" + len + " .box1_depth").val();
            box1_ply = $("#box1_ply" + len + " input").val();

            var box2_height = $("#box2size" + len + " .box2_height").val();
            var box2_width = $("#box2size" + len + " .box2_width").val();
            var box2_depth = $("#box2size" + len + " .box2_depth").val();
            box2_ply = $("#box2_ply" + len + " input").val();
            var token = $('input[name="_token"]').val();
            data = {
                '_token': token,
                'box1_type': box1_type,
                'box2_type': box2_type,
                'box1_height': box1_height,
                'box1_width': box1_width,
                'box1_depth': box1_depth,
                'box1_ply': box1_ply,
                'box2_height': box2_height,
                'box2_width': box2_width,
                'box2_depth': box2_depth,
                'box2_ply': box2_ply,
                'no_of_boxes': no_of_boxes
            };
            $.ajax({
                'url': "{{ url('/purchaseOrder/calculate_box') }}",
                'method': 'POST',
                'data': data,
                success: function(r) {
                    $("#box1sqinch" + len + " input").val(r.box1_sqinch);
                    $("#box2sqinch" + len + " input").val(r.box2_sqinch);
                    box_rate(r.box1_sqinch, r.box2_sqinch, box1_ply, box2_ply, len);
                    changePrice(ref);
                }
            });
        }

        var trcount = $('#productTable tr').length;
        console.log(trcount);
        var productRows = trcount;
        var products = [];

        function changeHSN(ref) {
            totrate1 = 0;
            totrate2 = 0;
            var len = $(ref).data('len');
            var id = $(ref).val();

            if ($('#pr' + id).length) {
                alert('product already added');
                $(ref).prop('selectedIndex', 0);
                $(ref).parent().attr("id", 'pr');
            } else {
                $(ref).parent().attr("id", 'pr' + id);

                function findProduct(product) {
                    return product.product_id == id;
                }

                var product = products.find(findProduct);
                $("#box1size" + len + " .box1_height").val(product.box1_height);
                $("#box1size" + len + " .box1_width").val(product.box1_width);
                $("#box1size" + len + " .box1_depth").val(product.box1_depth);
                $("#box1_ply" + len + " input").val(product.box1_ply);
                if (product.no_of_boxes == 1) {
                    $("#box2size" + len + " .box2_height").attr('readonly', 'readonly');
                    $("#box2size" + len + " .box2_width").attr('readonly', 'readonly');
                    $("#box2size" + len + " .box2_depth").attr('readonly', 'readonly');
                    $("#box2_ply" + len + " input").attr('readonly', 'readonly');
                } else {
                    $("#box2size" + len + " .box2_height").removeAttr('readonly');
                    $("#box2size" + len + " .box2_width").removeAttr('readonly');
                    $("#box2size" + len + " .box2_depth").removeAttr('readonly');
                    $("#box2_ply" + len + " input").removeAttr('readonly');
                }
                $("#box2size" + len + " .box2_height").val(product.box2_height);
                $("#box2size" + len + " .box2_width").val(product.box2_width);
                $("#box2size" + len + " .box2_depth").val(product.box2_depth);
                $("#box2_ply" + len + " input").val(product.box2_ply);
                $("#box1sqinch" + len + " input").val(product.box1_sqinch);
                $("#box2sqinch" + len + " input").val(product.box2_sqinch);
                $("#no_of_boxes" + len).val(product.no_of_boxes);
                $("#box1type" + len).val(product.box1_type);
                $("#box2type" + len).val(product.box2_type);
                var sqin1 = $("#box1sqinch" + len + " input").val();
                var sqin2 = $("#box2sqinch" + len + " input").val();
                var totsqin = sqin1 + sqin2;
                box_rate(sqin1, sqin2, product.box1_ply, product.box2_ply, len)
                changePrice(ref);
            }
        }

        function box_rate(sqin1, sqin2, box1_ply, box2_ply, len) {
            totrate1 = 0;
            totrate2 = 0;
            var supplier_id = $('#supplier_id').val();
            if (box1_ply == 3) {
                var rate = parseFloat($('#supplier-3ply-' + supplier_id).html());
                totrate1 = sqin1 * rate;
            }
            if (box1_ply == 5) {
                var rate = parseFloat($('#supplier-5ply-' + supplier_id).html());
                totrate1 = sqin1 * rate;
            }
            if (box1_ply == 7) {
                var rate = parseFloat($('#supplier-7ply-' + supplier_id).html());
                totrate1 = sqin1 * rate;
            }
            if (box2_ply == 3) {
                var rate = parseFloat($('#supplier-3ply-' + supplier_id).html());
                totrate2 = sqin2 * rate;
            }
            if (box2_ply == 5) {
                var rate = parseFloat($('#supplier-5ply-' + supplier_id).html());
                totrate2 = sqin2 * rate;
            }
            if (box2_ply == 7) {
                var rate = parseFloat($('#supplier-7ply-' + supplier_id).html());
                totrate2 = sqin2 * rate;
            }
            $("#box1_rate" + len + " input").val(totrate1);
            $("#box2_rate" + len + " input").val(totrate2);
        }

        function validateSubmit() {
            if ($('#productTable tr').length < 1) {
                alert("No product added. Add atleast 1 product.");
                event.preventDefault();
            } else {
                var productTableRow = 0;
                $('#productTable select').each(function() {
                    productTableRow++
                    console.log($(this).val());
                    if (!$(this).val()) {
                        alert("Product Row " + productTableRow + " empty. Select a product or delete the row.");
                        event.preventDefault();
                        return false;
                    }
                });
            }
        };

        function deleteRow(ref) {
            $(ref).parents("tr").remove();
            changePrice();
        }

        function changePrice(ref) {
            var len = $(ref).data('len');
            var quantity = $("#quan" + len + " input").val();
            var quantity2 = $("#box2_qty"+len+" input").val();
            var totrate1 = $("#box1_rate" + len + " input").val();
            var totrate2 = $("#box2_rate" + len + " input").val();
            var totrate = parseFloat(totrate1) + parseFloat(totrate2);
            var amount = parseFloat(totrate * quantity);
            var gstslab = $("#gstslab" + len + " input").val();
            var gst = (amount * gstslab) / 100;

            var box1_amount = parseFloat(totrate1 * quantity);
            var box2_amount = parseFloat(totrate2 * quantity2);
            $("#box1_amount" + len + " input").val(box1_amount.toFixed(2));
            $("#box2_amount" + len + " input").val(box2_amount.toFixed(2));
amount = parseFloat(box1_amount.toFixed(2)) + parseFloat(box2_amount.toFixed(2));
var gst = (amount * gstslab) / 100;
            $("#amount" + len + " input").val(amount.toFixed(2));
            $("#gstamount" + len + " input").val(gst);

            var arrgtotamt = document.getElementsByClassName('amount');
            var arrgstamt = document.getElementsByClassName('gstamount');

            var totq = 0;
            var totamt = 0;
            var subamount = 0;
            var gstamount = 0;

            document.querySelectorAll('input.carton-box1-qty').forEach(function (el) {
                var n = parseFloat(el.value);
                if (!isNaN(n)) {
                    totq += n;
                }
            });
            document.querySelectorAll('input.carton-box2-qty').forEach(function (el) {
                var n = parseFloat(el.value);
                if (!isNaN(n)) {
                    totq += n;
                }
            });
            document.getElementById('tquantity').value = totq;

            for (var i = 0; i < arrgstamt.length; i++) {
                if (parseFloat(arrgstamt[i].value))
                    gstamount += parseFloat(arrgstamt[i].value);
            }

            document.getElementById('totalgst').value = gstamount.toFixed(2);



            for (var i = 0; i < arrgtotamt.length; i++) {
                if (parseFloat(arrgtotamt[i].value))
                    subamount += parseFloat(arrgtotamt[i].value);
                // totgsta = (subamount*18)/100;
                totamt = subamount + gstamount;
            }
            document.getElementById('subtotalamount').value = subamount.toFixed(2);
            // document.getElementById('totalgst').value = totgsta.toFixed(2);
            document.getElementById('totalamount').value = totamt.toFixed(2);
        }

        $("#addProduct").click(function() {

            var options = '<option value="" selected disabled>-- SELECT PRODUCT --</option>';

            productRows += 1;

            $.each(products, function(index, value) {
                options += '<option value="' + value.product.id + '">' + value.product.code + " - " + value
                    .product.name + '</option>';
            });

            var $block = "";
            $block += '<tr>';
            $block += '<td id="pr">';
            $block +=
                '<select type="text" class="selectpicker" data-live-search="true" onchange="changeHSN(this);" data-len="' +
                productRows + '" name="po[' + productRows + '][product]">';
            $block += options + '</select></td>';
            $block += '<td id="quan' + productRows +
                '"><input type="number" min="0" class="form-control carton-box1-qty" onchange="changePrice(this);" data-len="' +
                productRows + '" name="po[' + productRows + '][quantity]" value="0" /></td>'
            $block += '<td id="lineDrawing' + productRows +
                '"><select class="form-control" onchange="changePrice(this);" data-len="' + productRows +
                '" name="po[' + productRows +
                '][line_drawing]"><option value = "Yes">Yes</option><option value = "No">No</option></select></td>'
            $block += '<td id="box1size' + productRows +
                '" style="min-width:150px;"><input type="number" step=".1" style="width:60px;display:inline-block;padding:5px;" class="form-control box1_height" onchange="changeBox(this);" data-len="' +
                productRows + '" name="po[' + productRows + '][box1_height]" />'
            $block +=
                '<input type="number" step=".1" style="width:60px;display:inline-block;padding:5px;" class="form-control box1_width" onchange="changeBox(this);" data-len="' +
                productRows + '" name="po[' + productRows + '][box1_width]"  />'
            $block +=
                '<input type="number" step=".1" style="width:60px;display:inline-block;padding:5px;" class="form-control box1_depth" onchange="changeBox(this);" data-len="' +
                productRows + '" name="po[' + productRows + '][box1_depth]" /></td>'
            $block += '<td id="box1sqinch' + productRows +
                '"><input class="form-control box1_sqinch" onchange="changePrice(this);" data-len="' + productRows +
                '" name="po[' + productRows + '][box1_sqinch]" readonly /></td>'
            $block += '<td id="box1_ply' + productRows +
                '"><input type="number" class="form-control box1_ply" data-len="' + productRows + '" name="po[' +
                productRows + '][box1_ply]" onchange="changeBox(this);" /></td>'
            $block += '<td id="box1_rate' + productRows +
                '"><input type="text" class="form-control box1_rate" data-len="' + productRows + '" name="po[' +
                productRows + '][box1_rate]" /></td>'
            $block += '<td id="box1_amount' + productRows +
                '"><input type="text" class="form-control box1_amount" data-len="' + productRows + '" name="po[' +
                productRows + '][box1_amount]" readonly="readonly" readonly /></td>'
            $block += '<td id="box2size' + productRows +
                '" style="min-width:150px;"><input type="number" step=".1" style="width:60px;display:inline-block;padding:5px;" class="form-control box2_height" onchange="changeBox(this);" data-len="' +
                productRows + '" name="po[' + productRows + '][box2_height]" />'
            $block +=
                '<input type="number" step=".1" style="width:60px;display:inline-block;padding:5px;" class="form-control box2_width" onchange="changeBox(this);" data-len="' +
                productRows + '" name="po[' + productRows + '][box2_width]" />'
            $block +=
                '<input type="number" step=".1" style="width:60px;display:inline-block;padding:5px;" class="form-control box2_depth" onchange="changeBox(this);" data-len="' +
                productRows + '" name="po[' + productRows + '][box2_depth]" /></td>'
            $block += '<td id="box2sqinch' + productRows +
                '"><input class="form-control box2_sqinch" onchange="changePrice(this);" data-len="' + productRows +
                '" name="po[' + productRows + '][box2_sqinch]" readonly /></td>'
            $block += '<td id="box2_qty' + productRows +
                '"><input onchange="changePrice(this);" type="number" min="0" class="form-control carton-box2-qty" data-len="' +
                productRows + '" name="po[' + productRows + '][box2_qty]" /></td>'
            $block += '<td id="box2_ply' + productRows +
                '"><input type="number" step="any" class="form-control box2_ply" data-len="' + productRows +
                '" name="po[' + productRows + '][box2_ply]" onchange="changeBox(this);" /></td>'
            $block += '<td id="box2_rate' + productRows +
                '"><input type="text" class="form-control box2_rate" data-len="' + productRows + '" name="po[' +
                productRows + '][box2_rate]" /></td>'
            $block += '<td id="box2_amount' + productRows +
                '"><input type="text" class="form-control box2_amount" data-len="' + productRows + '" name="po[' +
                productRows + '][box2_amount]" readonly="readonly" /></td>'
            //$block += '<td id="rate'+productRows+'"><input step="any" class="form-control" data-len="' + productRows + '" name="po['+productRows+'][rate]" required readonly="readonly" /></td>'
            $block += '<td id="amount' + productRows +
                '"><input type="number" class="form-control amount" name="po[' + productRows +
                '][amount]" value="0.00" readonly/></td>'
            $block += '<td id="gstslab' + productRows +
                '"><input type="number" min="0" class="form-control" onchange="changePrice(this);" data-len="' +
                productRows + '" name="po[' + productRows + '][gstslab]" required  value = "5"/></td>'
            $block += '<td id="gstamount' + productRows +
                '"><input type="number" class="form-control gstamount" name="po[' + productRows +
                '][gstamount]" value="0.00" readonly/></td>'
            $block +=
                '<td><button type="button" class="close" onclick="deleteRow(this);" data-bs-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button></td>';
            $block += '<input type="hidden" id = "no_of_boxes' + productRows +
                '" /><input type="hidden" id = "box1type' + productRows +
                '" /><input type="hidden" id = "box2type' + productRows + '" /></td>';
            $block += '</tr>';
            $("#productTable").append($block);
            $('.selectpicker').selectpicker();
        });
    </script>

@endsection