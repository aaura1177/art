@extends('layouts.app')

@section('content')

    <div class="mx-2">
        <div class="row mx-0 my-2">
            <h2>Add Carton PO</h2>
        </div>

        <form id="myForm" method="POST" action="{{ url('/purchaseOrder/createCartonPo') }}">
            @csrf

            <!-- Form Starts -->
            <div class="form-group">
                @if(session('error'))
                <div class="alert alert-danger">
                    {{ session('error') }}
                </div>
            @endif
                @if ($errors->any())
                <div class="alert alert-danger">
                    <ul class="mb-0">
                        @foreach ($errors->all() as $err)
                            <li>{{ $err }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif
                <!-- first row -->
                <div class="row mt-3">
                    <div class="col-4">
                        <label class="control-label">{{ __('PO No.') }}</label>
                        <input type="text" class="form-control toUpperCase" name="pono" required="required"
                            value="CTN/{{ $companyDetails->ctnpo_no + 1 }}" />
                    </div>
                    <div class="col-4">
                        <label class="control-label">{{ __('Supplier') }}</label><a href="{{ url('/supplier/create') }}"
                            style="float: right;" target="_blank"> (+New)</a>
                        <select id="supplier_id" type="text" class="selectpicker" data-live-search="true" onchange="handleSelectSupplier(this)"
                            name="supplier_id" required="required">
                            <option value="" selected disabled>Select Supplier</option>
                            @if (isset($supplier))
                                @foreach ($supplier as $key => $supplier)
                                    <option value="{{ $supplier->id }}" id="{{$supplier->id}}" data-gst="{{$supplier->gst}}">
                                        {{ $supplier->c_name }}
                                    </option>
                                @endforeach
                            @endif
                        </select>
                        <input type="number" value="" id="supplier_gst" hidden>
                    </div>
                    @foreach ($pricingDetails as $pricing)
                        <div style="display:none;" id="supplier-3ply-{{ $pricing->supplier_id }}">{{ $pricing->{"3ply"} }}
                        </div>
                        <div style="display:none;" id="supplier-5ply-{{ $pricing->supplier_id }}">{{ $pricing->{"5ply"} }}
                        </div>
                        <div style="display:none;" id="supplier-7ply-{{ $pricing->supplier_id }}">
                            {{ $pricing->{"7ply"} }}</div>
                    @endforeach
                </div>
                <div class="row mt-3">
                    <div class="col-4">
                        <label class="control-label">{{ __('Date of PO') }}</label>
                        <input type="date" class="form-control" name="podate" required="required"
                            min="{{ isset($po->podate) ? $po->podate : '' }}" value="{{ date('Y-m-d') }}" />
                    </div>
                    <div class="col-4">
                        <label class="control-label">{{ __('Delivery Date') }}</label>
                        <input type="date" class="form-control" name="del_date" required="required"
                            value="{{ date('Y-m-d', strtotime('+3 days')) }}" />
                    </div>
                </div>

                <!-- second row -->
                <div class="row mt-3">
                    <div class="col-4">
                        <label class="control-label">{{ __('Supplier Month') }}</label>
                        <select class="selectpicker" name="month[]" required="required" multiple>
                            <option value="{{ date('m-Y') }}">{{ date('F Y') }}</option>
                            <option value="{{ date('m-Y', strtotime('previous month')) }}">
                                {{ date('F Y', strtotime('previous month')) }}</option>
                        </select>
                    </div>
                    <div class="col-4">
                        <label class="control-label">{{ __('Buyer Order Number') }}</label>
                        <input type="text" class="form-control toUpperCase" name="buyer_orderno" required="required" />
                    </div>
                </div>

                <!-- third row -->
                <div class="row mt-3">
                    <div class="col-4">
                        <label class="control-label">{{ __('Terms of Payment') }}</label>
                        <select class="form-control" name="payterms">
        <option value="30-45 Days" selected>30-45 Days</option>
        <option value="30 Days">30 Days</option>
        <option value="45 Days">45 Days</option>
    </select>
                    </div>
                    <div class="col-8">
                        <label class="control-label">{{ __('Remarks') }}</label>
                        <textarea class="form-control" name="remarks">
    1. Goods must be delivered to our Factory Address.  
    2. Invoice and E waybill should be attached at the time of delivery.  
    3. All rates including freight charges.  
                    </textarea>
                    </div>
                </div>

                <!-- forth Product row -->
                <div class="row mt-5 my-3">
                    <div class="col-6">
                        <h5>Products List</h5>
                    </div>
                </div>

                <!-- Product details -->
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr id="mytable">
                                <th scope="col" style="min-width: 250px;">Product <a href="{{ url('/product/create') }}"
                                        target="_blank"> (+New)</a></th>
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
                        <input type="text" class="form-control" name="tgst" id="totalgst" readonly />
                    </div>
                    <div class="col-4">
                        <label class="control-label">{{ __('Total Quantity') }}</label>
                        <input type="number" class="form-control" name="tquantity" id="tquantity" readonly />
                    </div>
                    <div class="col-4">
                        <label class="control-label">{{ __('Sub Total Amount (₹)') }}</label>
                        <input type="number" class="form-control" name="subtotalamount" id="subtotalamount" readonly />
                    </div>
                </div>


                <!-- sixth row  -->
                <div class="row mt-3">
                    <div class="col-4">
                        <label class="control-label">{{ __('Total Amount (₹)') }}</label>
                        <input type="number" class="form-control" name="tamount" id="totalamount" readonly />
                    </div>
                </div>

                <div class="row col-4">
                    <button id="submitBtn" type="submit" form='myForm' class="btn btn-primary mt-3">Create PO</button>
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
                'url': "{{ url('/purchaseOrder/pdata') }}"
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

        var productRows = 0;
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
                    $("#box2_qty" + len + " input").attr('readonly', 'readonly');
                } else {
                    $("#box2size" + len + " .box2_height").removeAttr('readonly');
                    $("#box2size" + len + " .box2_width").removeAttr('readonly');
                    $("#box2size" + len + " .box2_depth").removeAttr('readonly');
                    $("#box2_ply" + len + " input").removeAttr('readonly');
                    $("#box2_qty" + len + " input").removeAttr('readonly');
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

        $('#myForm').on('submit', function() {
            if ($('#productTable tr').length < 1) {
                alert("No product added. Add atleast 1 product.");
                event.preventDefault();
            } else {
                var productTableRow = 0;
                var submitFlag = 0;
                $('#productTable select').each(function() {
                    productTableRow++;
                    if (!$(this).val()) {
                        alert("Product Row " + productTableRow +
                            " empty. Select a product or delete the row.");
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

        function deleteRow(ref) {
            $(ref).parents("tr").remove();
            changePrice();
        }

        function changePrice(ref) {
            var len = $(ref).data('len');
            var quantity = $("#quan" + len + " input").val();
            var quantity2 = $("#box2_qty" + len + " input").val();

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
            $("#gstamount" + len + " input").val(gst.toFixed(2));

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
            $block += '<td id="lineDrawing' + productRows + '"><select class="form-control" data-len="' +
                productRows + '" name="po[' + productRows +
                '][line_drawing]"><option value = "Yes">Yes</option><option value = "No">No</option></select></td>'
            $block += '<td id="box1size' + productRows +
                '" style="min-width:150px;"><input type="number" step="any" style="width:60px;display:inline-block;padding:5px;" class="form-control box1_height" onchange="changeBox(this);" data-len="' +
                productRows + '" name="po[' + productRows + '][box1_height]" />'
            $block +=
                '<input type="number" step="any" style="width:60px;display:inline-block;padding:5px;" class="form-control box1_width" onchange="changeBox(this);" data-len="' +
                productRows + '" name="po[' + productRows + '][box1_width]"  />'
            $block +=
                '<input type="number" step="any" style="width:60px;display:inline-block;padding:5px;" class="form-control box1_depth" onchange="changeBox(this);" data-len="' +
                productRows + '" name="po[' + productRows + '][box1_depth]"  /></td>'
            $block += '<td id="box1sqinch' + productRows +
                '"><input class="form-control box1_sqinch" onchange="changePrice(this);" data-len="' + productRows +
                '" name="po[' + productRows + '][box1_sqinch]" readonly /></td>'
            $block += '<td id="box1_ply' + productRows +
                '"><input onchange="changeBox(this);" type="number" step="any" class="form-control box1_ply" data-len="' +
                productRows + '" name="po[' + productRows + '][box1_ply]"  /></td>'
            $block += '<td id="box1_rate' + productRows +
                '"><input type="text" class="form-control box1_rate" data-len="' + productRows + '" name="po[' +
                productRows + '][box1_rate]" /></td>'
            $block += '<td id="box1_amount' + productRows +
                '"><input type="text" class="form-control box1_amount" data-len="' + productRows + '" name="po[' +
                productRows + '][box1_amount]" readonly="readonly" readonly /></td>'
            $block += '<td id="box2size' + productRows +
                '" style="min-width:150px;"><input type="number" step="any" style="width:60px;display:inline-block;padding:5px;" class="form-control box2_height" onchange="changeBox(this);" data-len="' +
                productRows + '" name="po[' + productRows + '][box2_height]" />'
            $block +=
                '<input type="number" step="any" style="width:60px;display:inline-block;padding:5px;" class="form-control box2_width" onchange="changeBox(this);" data-len="' +
                productRows + '" name="po[' + productRows + '][box2_width]" />'
            $block +=
                '<input type="number" step="any" style="width:60px;display:inline-block;padding:5px;" class="form-control box2_depth" onchange="changeBox(this);" data-len="' +
                productRows + '" name="po[' + productRows + '][box2_depth]" /></td>'
            $block += '<td id="box2sqinch' + productRows +
                '"><input class="form-control box2_sqinch" onchange="changePrice(this);" data-len="' + productRows +
                '" name="po[' + productRows + '][box2_sqinch]" readonly /></td>'
            $block += '<td id="box2_qty' + productRows +
                '"><input onchange="changePrice(this);" type="number" min="0" class="form-control carton-box2-qty" data-len="' +
                productRows + '" name="po[' + productRows + '][box2_qty]" value="0" /></td>'
            $block += '<td id="box2_ply' + productRows +
                '"><input onchange="changeBox(this);" type="number" step="any" class="form-control box2_ply" data-len="' +
                productRows + '" name="po[' + productRows + '][box2_ply]" /></td>'
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
                productRows + '" name="po[' + productRows + '][gstslab]"   value = "5"/></td>'
            $block += '<td id="gstamount' + productRows +
                '"><input type="number" class="form-control gstamount" name="po[' + productRows +
                '][gstamount]" value="0.00" readonly/></td>'
            $block +=
                '<td><button type="button" class="close" onclick="deleteRow(this);" data-bs-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>';
            $block += '<input type="hidden" id = "no_of_boxes' + productRows +
                '" /><input type="hidden" id = "box1type' + productRows +
                '" /><input type="hidden" id = "box2type' + productRows + '" /></td>';
            $block += '</tr>';
            $("#productTable").append($block);
            $('.selectpicker').selectpicker();
            handleGstSlab()
        });

        function handleGstSlab() {
  var gst = parseFloat($('#supplier_gst').val());  // Ensure it's a number
  
  console.log(gst);
  let trLength = document.getElementsByTagName('tr').length - 1;  // Subtract 1 to exclude any non-relevant rows (like header rows)
  
  for (let i = 1; i <= trLength; i++) {
    let gstTr = document.getElementById('gstslab' + i);  // Get the specific row for GST slab
    
    console.log(gstTr);
    
    if (gstTr) {
      let gstInput = gstTr.getElementsByTagName('input')[0];  // Get the input field inside the row
      if (gst !== 0 && !isNaN(gst)) {
        gstInput.setAttribute('required', 'required');  // Add the required attribute
      } else {
        gstInput.removeAttribute('required');  // Remove the required attribute
      }

      // Set the minimum value of the input field based on supplier GST
      if (gst === 1) {
        gstInput.setAttribute('min', '1');  // Set min to 1 if GST is 1
      } else {
        gstInput.removeAttribute('min');  // Remove min attribute if GST is not 1
      }
    }
  }
}

function handleSelectSupplier(ref) {
  var id = $(ref).val();
  var gst = $(ref).find('option:selected').data('gst');
  $('#supplier_gst').val(gst);
  handleGstSlab();  // Call the function to adjust the min attribute when supplier is selected
}


    </script>

@endsection
