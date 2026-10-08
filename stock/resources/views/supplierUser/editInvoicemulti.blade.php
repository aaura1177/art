@extends('layouts.app')

@section('content')
    <div class="mx-2">
        <div class="row mx-0 my-2">
            <h2>Edit Invoice Multiple</h2>
        </div>

        <div class="ml-auto mb-2">
            <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#addPoModal">
                + Add Another PO
            </button>
        </div>

        @php
            // Seed the list of currently included PO IDs from the rendered table rows
            $existingPOIds = collect($poTable ?? [])
                ->pluck('poid')
                ->unique()
                ->values()
                ->all();
        @endphp

        <form id="createPurchaseBill" method="POST"
            action="{{ url('/supplier-dashboard/update-invoice/multi') }}/{{ $supplierInvoice->id }}">
            @csrf

            @if ($errors->any())
                <div class="alert alert-danger">
                    <ul class="mb-0">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <input type="hidden" id="selectedPOIds" name="poids" value="{{ implode(',', $existingPOIds) }}">
            <input type="hidden" name="purchase_order_id" value="{{ $purchaseOrder->id }}" />
            <input type="hidden" name="mulitple_po" value="{{ $supplierInvoice->mulitple_po }}" />

            <div class="form-group">

                {{-- Header rows --}}
                <div class="row mt-3">
                    <div class="col-4">
                        <label class="control-label">{{ __('PO No.') }}</label>
                        <input type="text" class="form-control" name="poNumbers" readonly
                            value="{{ $poNumbers->implode(', ') }}" />
                    </div>
                    <div class="col-4">
                        <label class="control-label">{{ __('Supplier Name') }}</label>
                        <input type="text" class="form-control" name="supplierName" id="supplierName" readonly
                            value="{{ $supplierNames->implode(', ') }}" />
                    </div>
                    <div class="col-4">
                        <label class="control-label">{{ __('Delivery Date') }}</label>
                        <input type="text" class="form-control" name="del_date" id="del_date" readonly
                            value="{{ $deliveryDates->implode(', ') }}" />
                    </div>
                </div>

                <div class="row mt-3">
                    <div class="col-4">
                        <label class="control-label">{{ __('Supplier Ref.') }}</label>
                        <input type="text" class="form-control" name="Supplier_ref" id="Supplier_ref" readonly
                            value="{{ $supplierRefs->implode(', ') }}" />
                    </div>

                    <div class="col-4">
                        <label class="control-label">{{ __('Ordered Quantity') }}</label>
                        <input type="number" class="form-control" name="poQty" id="total_qty" readonly
                            value="{{ $supplierInvoice->tquantity }}" />
                    </div>

                    <div class="col-4">
                        <label class="control-label">{{ __('Sub Total Amount') }}</label>
                        <input type="number" class="form-control" name="poAmount" id="total_amount" readonly
                            value="{{ $supplierInvoice->subTotal }}" />
                    </div>
                </div>

                <div class="row mt-3">
                    <div class="col-8">
                        <label class="control-label">{{ __('Remark') }}</label>
                        <textarea class="form-control" name="remark" id="remarks" readonly>{{ $purchaseOrder->remarks }}</textarea>
                    </div>
                </div>

                {{-- Invoice header inputs --}}
                <div class="row mt-3">
                    <div class="col-4">
                        <label class="control-label">{{ __('Supplier Invoice No.') }}</label>
                        <input type="text" class="form-control toUpperCase" id="supinv" name="supp_inv_no"
                            value="{{ $supplierInvoice->supplier_invoice_number }}" required />
                        @error('supp_inv_no')
                            <small class="text-danger">{{ $message }}</small>
                        @enderror
                    </div>

                    <div class="col-4">
                        <label class="control-label">{{ __('E-Way Bill No.') }}</label>
                        <input type="text" class="form-control toUpperCase" name="ewaybill" id="ewaybill"
                            value="{{ $supplierInvoice->eway_bill_no }}" />
                        @error('eway_bill_no')
                            <small class="text-danger">{{ $message }}</small>
                        @enderror
                    </div>

                    <div class="col-4">
                        <label class="control-label">{{ __('Vehicle No.') }}</label>
                        <input type="text" class="form-control toUpperCase" name="vehicle_no" id="vehicle_no"
                            value="{{ $supplierInvoice->vehicle_no }}" />
                    </div>

                    <div class="col-4">
                        <label class="control-label">{{ __('Supplier Invoice Date') }}</label>
                        <input type="date" class="form-control" name="invoice_date" id="invoice_date"
                            max="<?php echo date('Y-m-d'); ?>" min="<?php echo date('Y-m-d', strtotime('yesterday')); ?>"
                            value="{{ $supplierInvoice->invoice_date }}" />
                    </div>
                </div>

                {{-- widen the Rate/Item column --}}
                <style>
                    #productTable th.rate-col,
                    #productTable td.rate-col {
                        min-width: 100px;
                    }
                </style>

                <style>
                    /* ========== Desktop/Laptop responsive (full Product + EAN, scroll if needed) ========== */

                    /* Always allow horizontal scroll when content is wider than container */
                    .po-table-wrap {
                        overflow-x: auto;
                    }

                    /* Let the table expand to fit long content, but never shrink below container */
                    .po-table {
                        table-layout: auto;
                        /* natural column widths (no squeezing) */
                        width: max-content;
                        /* grow to content width → triggers horizontal scroll */
                        min-width: 100%;
                        /* still fill container when content is short */
                    }

                    /* Show Product/EAN fully (no truncation/ellipsis) */
                    .po-table .product-col,
                    .po-table .ean-col {
                        white-space: nowrap;
                        overflow: visible;
                        text-overflow: unset;
                        min-width: max-content;
                        /* size to content */
                        font-variant-numeric: tabular-nums;
                        /* optional: nicer alignment if numbers appear */
                    }

                    /* Keep numeric/compact columns neat while table can still scroll */
                    #productTable th,
                    #productTable td {
                        white-space: nowrap;
                        /* avoid wrapping numbers/labels across lines */
                    }

                    /* Optional: give a little guaranteed space to key numeric columns */
                    #productTable th.rate-col,
                    #productTable td.rate-col {
                        min-width: 110px;
                    }

                    #productTable td[id^="remqty"] {
                        min-width: 100px;
                        text-align: center;
                    }

                    /* If you want slightly tighter behavior on smaller laptops (≤1366px), you can relax min-widths a bit: */
                    @media (max-width: 1366px) {

                        #productTable th.rate-col,
                        #productTable td.rate-col {
                            min-width: 100px;
                        }

                        #productTable td[id^="remqty"] {
                            min-width: 90px;
                        }
                    }
                </style>


                {{-- Lines table --}}
                <div class="row mt-3 po-table-wrap">
                    <table class="table table-hover po-table">
                        <thead>
                            <tr id="mytable">
                                <th scope="col">PO.NO</th>
                                <th scope="col" class="product-col">Product</th>
                                <th scope="col" class="ean-col">EAN</th>
                                <th scope="col">Rem QTY</th>
                                <th scope="col">Quantity</th>
                                <th scope="col" class="rate-col">Rate/Item (₹)</th>
                                <th scope="col">Amount (₹)</th>
                                <th scope="col">GSTSLAB</th>
                                <th scope="col" class="rate-col">GST Amount</th>
                                <th scope="col">Discount Per Item(₹)</th>
                                <th scope="col">Discount(₹)</th>
                                <th scope="col">Po Discount </th>
                            </tr>
                        </thead>
                        <tbody id="productTable">
                            <?php $productRows = 0;
                                use App\supplierInvoiceProduct;
                                   

                            ?>
                            @foreach ($poTable as $pots)
                                @php
                                    $productRows++;
                                    // Seed values for previously invoiced lines
                                    $valremqty = 0;
                                    $val = 0;
                                    $valquantity = 0;
                                    $valdiscount = 0;

                                
                                    $supplierProduct = supplierInvoiceProduct::where('purchase_order_id', $pots->poid)->where('product_id',$pots->product_id)->where('supplier_invoice_id',$supplierInvoice->id)->first();
                                 
                                                $val = $supplierProduct->quantity;
                                                $valdiscount = $supplierProduct->discount;
                                                $valremqty = $pots->remqty;
                                                $valquantity = $pots->quantity;
                                   
                                @endphp
                                 
                                <tr id="mytable" data-poid="{{ $pots->poid }}">
                                   
                                    <td scope="col">{{ $pots->purchaseOrderTable->pono }}</td>

                                    <td scope="col" class="product-col">
                                        @if (isset($pots->product->code))
                                            {{ $pots->product->code }} - {{ $pots->product->name }}
                                        @else
                                            {{ $pots->consumable->name }}
                                        @endif

                                        @if (isset($pots->product_id))
                                            <input name="pb[{{ $productRows }}][product]" type="hidden"
                                                value="{{ $pots->product_id }}" />
                                            <input name="pb[{{ $productRows }}][poid]" type="hidden"
                                                value="{{ $pots->poid }}" />
                                        @else
                                            <input name="pb[{{ $productRows }}][product]" type="hidden"
                                                value="{{ $pots->consumable_id }}" />
                                        @endif
                                    </td>

                                    {{-- EAN --}}
                                    <td scope="col" class="ean-col">{{ $pots->id }}</td>

                                    {{-- Rem QTY --}}
                                    <td scope="col" id="remqty{{ $pots->id }}">{{ $valremqty }}</td>

                                    {{-- QTY input (token = EAN) --}}
                                    <td scope="col" id="{{ $pots->id }}">
                                        <input class="form-control receiveqty" value="{{ $val }}"
                                            min="0" max="{{ $valquantity }}"
                                            name="pb[{{ $productRows }}][receiveqty]" type="number"
                                            onchange="recalQuantity(this);" />
                                    </td>

                                    {{-- Rate --}}
                                    <td scope="col" id="rate{{ $pots->id }}" class="rate-col">
                                        <input class="form-control" value="{{ $pots->rate }}"
                                            name="pb[{{ $productRows }}][rate]" readonly />
                                    </td>

                                    {{-- Amount (gross) --}}
                                    <td scope="col" id="amount{{ $pots->id }}">
                                        <input value="0" class="form-control amount"
                                            name="pb[{{ $productRows }}][amount]" readonly />
                                    </td>

                                    {{-- GST slab --}}
                                    <td scope="col" id="gstslab{{ $pots->id }}">
                                        <input class="form-control" value="{{ $pots->gstslab }}"
                                            name="pb[{{ $productRows }}][gstslab]" readonly />
                                    </td>

                                    {{-- GST amount (computed) --}}
                                    <td scope="col" class="rate-col" id="gstamount{{ $pots->id }}">
                                        <input value="0" class="form-control gstamount"
                                            name="pb[{{ $productRows }}][gstamount]" readonly />
                                    </td>

                                    {{-- Discount type --}}
                                    <td scope="col" id="discountperitem{{ $pots->id }}">
                                        <input class="form-control"
                                            value="{{ number_format(($pots->remaining_discount + $valdiscount) / $valquantity, 2) }}"
                                            name="pb[{{ $productRows }}][discountperitem]" readonly />
                                    </td>

                                    <td scope="col" id="remaing_discount{{ $pots->id }}">
                                        <input value="{{ $pots->remaing_discount }}"
                                            class="form-control remaing_discount"
                                            name="pb[{{ $productRows }}][remaing_discount]" readonly />
                                    </td>

                                    {{-- Discount pool --}}
                                    <td scope="col" id="discount{{ $pots->id }}">
                                        <input value="{{ $pots->remaining_discount + $valdiscount }}"
                                            class="form-control discount" name="pb[{{ $productRows }}][discount]"
                                            readonly />
                                    </td>



                                    {{-- Net line (after discount) --}}
                                    <td scope="col" id="discountamount{{ $pots->id }}">
                                        <input class="form-control discountamount" value="{{ $pots->discountamount }}"
                                            name="pb[{{ $productRows }}][discountamount]" hidden />
                                    </td>

                                    {{-- Total quantity considered for discount allocation --}}
                                    <td scope="col" id="totalquantity{{ $pots->id }}">
                                        <input value="{{ $val + $pots->remqty }}" class="form-control totalquantity"
                                            hidden />
                                    </td>
                                    <td scope="col" id="discount_type{{ $pots->id }}">
                                        <input class="form-control" value="{{ $pots->discount_type }}"
                                            name="pb[{{ $productRows }}][discount_type]" hidden />
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                {{-- Footer totals --}}
                <div class="row mt-3">
                    <div class="col-4">
                        <label class="control-label">{{ __('Total Quantity') }}</label>
                        <input type="number" class="form-control" name="pbQty" id="pbQty"
                            value="{{ $supplierInvoice->tquantity }}" readonly />
                    </div>
                    <div class="col-4">
                        <label class="control-label">{{ __('Sub-Total Amount') }}</label>
                        <input type="number" class="form-control" name="pbSubTotal" id="pbSubTotal"
                            value="{{ $supplierInvoice->subTotal }}" readonly />
                    </div>
                    <div class="col-4">
                        <label class="control-label">{{ __('GST') }}</label>
                        <input type="number" class="form-control" name="pbGST" id="pbGST"
                            value="{{ $supplierInvoice->tgst }}" readonly />
                    </div>
                </div>

                <div class="row mt-3">
                    <div class="col-4">
                        <label class="control-label">{{ __('Total Amount') }}</label>
                        <input type="number" class="form-control" name="pbTotal" id="pbTotal"
                            value="{{ $supplierInvoice->tamount }}" readonly />
                    </div>

                    <div class="col-4">
                        <label class="control-label">{{ __('Total TDS') }}</label>
                        <input type="number" class="form-control" value="" name="tdsTotal" id="tdsTotal"
                            readonly />
                        <input type="number" class="form-control" value="{{ $userSplier->supplier->tdspercent }}"
                            name="tdspercent" id="tdsPercent" hidden />
                    </div>

                    <div class="col-4">
                        <label class="control-label">{{ __('Total Discount') }}</label>
                        <input type="number" class="form-control" name="totaldiscount" id="totaldiscount"
                            value="{{ $supplierInvoice->totaldiscount }}" readonly />
                    </div>
                    <div class="col-4">
                        <label class="control-label">{{ __('Round Off') }}</label>
                        <input type="number" class="form-control" step=".1" name="roundoff" id="roundoff"
                            value="{{ $supplierInvoice->roundoff }}" onchange="calculateTotal();" />
                    </div>
                </div>

                <div class="row col-4">
                    <button type="submit" id="hidden" hidden class="btn btn-primary mt-3" disabled>Create Purchase
                        Bill</button>
                    <button type="submit" id="formSubmit" form="createPurchaseBill" class="btn btn-primary mt-3">Update
                        Invoice</button>
                </div>

            </div>
        </form>
    </div>

    {{-- Add Another PO Modal --}}
    <div class="modal fade" id="addPoModal" tabindex="-1" role="dialog" aria-labelledby="addPoModalLabel"
        aria-hidden="true">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="addPoModalLabel">Add / Remove Accepted Purchase Orders</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="form-group">
                        <label>Select PO(s)</label>
                        <select id="add-po-select" class="selectpicker" data-live-search="true" data-actions-box="true"
                            multiple data-width="100%">
                            <!-- populated via AJAX -->
                        </select>
                        <small class="form-text text-muted">
                            You can select or deselect POs. Deselecting will remove their lines and update totals.
                        </small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button id="add-po-confirm" type="button" class="btn btn-primary">Apply Selection</button>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('footer')
    <!-- Scripts -->
    <script type="text/javascript">
        // Bootstrap existing data and pre-calc lines so Amount/GST are visible on load
        $(document).ready(function() {
            $.ajax({
                    url: "{{ url('/supplier-dashboard/data') }}",
                    method: 'GET'
                })
                .done(function(data) {
                    if (data) {
                        purchaseOrder = data.purchaseOrder || [];
                        supplier = data.supplier || [];
                        pbProduct = data.product || [];
                    }
                });

            // Pre-calc existing rows
            $('#productTable .receiveqty').each(function() {
                const v = parseFloat(this.value);
                if (!isNaN(v) && v >= 0) recalQuantity(this);
            });

            handleTds();
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
                    url: "{{ url('/supplier-dashboard/data/pbPoTable') }}/" + id,
                    method: 'GET'
                })
                .done(function(data) {
                    if (data) pbPoTable = data.poTable;
                });

            function findPo(purchaseBill) {
                return purchaseBill.id == id;
            }

            function findSupplier(row) {
                return row.id == supplierId;
            }

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
        }

        // --- Core recalculation (robust remqty detection) ---
        function recalQuantity(ref) {
            const $qtyInput = $(ref);
            const qty = parseFloat($qtyInput.val()) || 0;
            const token = $qtyInput.parent().attr('id'); // token per-row (EAN or random id)

            // remqty by explicit id or previous cell
            let remqty = NaN;
            const $remCellById = $('#remqty' + token);
            if ($remCellById.length) {
                const maybeInput = $remCellById.find('input').val();
                remqty = parseFloat(maybeInput != null ? maybeInput : $remCellById.text());
            }
            if (isNaN(remqty)) {
                const txt = $qtyInput.closest('td').prev('td').text();
                remqty = parseFloat((txt || '').toString().replace(/[, ]/g, ''));
            }
            if (isNaN(remqty)) remqty = 0;

            const rate = parseFloat($('#rate' + token + ' input').val()) || 0;
            const gstslab = parseFloat($('#gstslab' + token + ' input').val()) || 0;

            const discount = parseFloat($('#discount' + token + ' input').val()) || 0;
            const totalQty = parseFloat($('#totalquantity' + token + ' input').val()) || remqty || 1;

            const gross = rate * qty;

            // apportion discount across total considered qty
            const perUnitDisc = totalQty > 0 ? (discount / totalQty) : 0;
            const discountValue = perUnitDisc * qty;
            const net = gross - discountValue;

            $('#amount' + token + ' input').val(gross.toFixed(2));
            $('#discountamount' + token + ' input').val(net.toFixed(2));
            $('#remaing_discount' + token + ' input').val(discountValue.toFixed(2));

            const gstAmount = (net * gstslab) / 100;
            $('#gstamount' + token + ' input').val(gstAmount.toFixed(2));

            calculateTotal();
            handleTds();
        }

        function deleteRow(ref) {
            $(ref).parents("tr").remove();
            calculateTotal();
        }

      function calculateTotal() {
    let totQty = 0, subTotNet = 0, gstTotal = 0, totalDiscount = 0;

    $('.receiveqty').each(function() {
        totQty += parseFloat(this.value) || 0;
    });
    $('.discountamount').each(function() {
        subTotNet += parseFloat(this.value) || 0; // NET
    });
    $('.gstamount').each(function() {
        gstTotal += parseFloat(this.value) || 0;
    });
    $('.remaing_discount').each(function() {
        totalDiscount += parseFloat(this.value) || 0;
    });

    let pbTotal = subTotNet + gstTotal; // use let
    let roundOff = parseFloat($('#roundoff').val()) || 0;

    pbTotal += roundOff; // Adds or subtracts automatically if roundOff is + or -

    $('#pbSubTotal').val(subTotNet.toFixed(2));
    $('#pbQty').val(totQty);
    $('#pbGST').val(gstTotal.toFixed(2));
    $('#pbTotal').val(pbTotal.toFixed(2));
    $('#totaldiscount').val(totalDiscount.toFixed(2));

    if (pbTotal > 100000)
        $('#ewaybill').attr('required', true);
    else
        $('#ewaybill').removeAttr('required');
}


        function searchProduct(ref) {
            if (event.keyCode !== 13) return;
        }

        function handleTds() {
            const subTotal = Number(document.querySelector('input[name="pbSubTotal"]').value || 0);
            const tdsPercent = Number(document.querySelector('#tdsPercent').value || 0);
            const tdsTotal = (subTotal * tdsPercent) / 100;
            document.querySelector('input[name="tdsTotal"]').value = tdsTotal.toFixed(2);
        }

        $('#createPurchaseBill').on('submit', function(e) {
            var qtyExceeded = false;

            $('#productTable input[name*="[receiveqty]"], #productTable input[name*="[receiveqty_box_1]"], #productTable input[name*="[receiveqty_box_2]"]').each(function() {
                var max = parseFloat($(this).attr('max'));
                var val = parseFloat($(this).val()) || 0;
                if (!isNaN(max) && val > max) {
                    qtyExceeded = true;
                    return false;
                }
            });

            if (qtyExceeded) {
                alert("Receive quantity cannot be greater than remaining quantity.");
                e.preventDefault();
                return;
            }

            if ($('#productTable tr').length < 1) {
                alert("No product added. Add at least 1 product.");
                e.preventDefault();
            } else {
                $('#formSubmit').prop('disabled', true);
            }
        });
    </script>

    <script>
        // ===== Add / Remove POs (Edit view) =====
        (function() {
            function getSelectedPOIdsArray() {
                const val = $('#selectedPOIds').val() || '';
                return val.split(',').map(s => s.trim()).filter(Boolean);
            }

            function setSelectedPOIdsArray(arr) {
                $('#selectedPOIds').val(arr.join(','));
            }

            function uniqToken() {
                return 'r' + Math.random().toString(36).slice(2, 10);
            }

            function num(v) {
                const x = parseFloat(String(v ?? 0).replace(/[, ]/g, ''));
                return isNaN(x) ? 0 : x;
            }

            // requires routes:
            //   route('supplier.eligible.pos')          -> list accepted POs (used for select/deselect)
            //   route('supplier.po.detail', ['id'=>..]) -> returns { po: {...}, rows: [...] }
            const poDetailTemplate = @json(route('supplier.po.detail', ['id' => '__ID__']));

            function renderRowFromItem(item, token, idx) {
                return `
          <tr data-poid="${item.poid || ''}">
            <td scope="col">${item.pono || ''}</td>
            <td scope="col" class="product-col">
              ${(item.product_code ? (item.product_code + ' - ') : '') + (item.product_name || '')}
              <input name="pb[${idx}][product]" type="hidden" value="${item.product_id || ''}"/>
              <input name="pb[${idx}][poid]"    type="hidden" value="${item.poid || ''}"/>
            </td>
            <td scope="col" class="ean-col">${item.EAN || ''}</td>
            <td scope="col" id="remqty${token}">${item.remqty ?? 0}</td>
            <td scope="col" id="${token}">
              <input class="form-control receiveqty" value="0" min="0" max="${item.remqty ?? 0}"
                     name="pb[${idx}][receiveqty]" type="number" onchange="recalQuantity(this);" />
            </td>
            <td scope="col" id="rate${token}" class="rate-col">
              <input class="form-control" value="${item.rate ?? 0}" name="pb[${idx}][rate]" readonly />
            </td>
            <td scope="col" id="amount${token}">
              <input value="0" class="form-control amount" name="pb[${idx}][amount]" readonly />
            </td>
            <td scope="col" id="gstslab${token}">
              <input class="form-control" value="${item.gstslab ?? 0}" name="pb[${idx}][gstslab]" readonly />
            </td>
            <td scope="col" id="gstamount${token}">
              <input value="0" class="form-control gstamount" name="pb[${idx}][gstamount]" readonly />
            </td>
            <td id="discountperitem${token}">
              <input class="form-control"  value="${((item.remaining_discount ?? 0) / (item.remqty ?? 1)).toFixed(2)}" name="pb[${idx}][discountperitem]" readonly />
            </td>
             <td id="remaing_discount${token}">
              <input value="" class="form-control remaing_discount" name="pb[${idx}][remaing_discount]" readonly />
            </td>
            <td id="discount${token}">
              <input value="${item.remaining_discount ?? 0}" class="form-control discount" name="pb[${idx}][discount]" readonly />
            </td>
           
            <td id="discountamount${token}">
              <input value="" class="form-control discountamount" name="pb[${idx}][discountamount]" hidden />
            </td>
            <td id="totalquantity${token}">
              <input value="${(item.remqty ?? 0)}" class="form-control totalquantity" hidden />
            </td>
               <td id="discount_type${token}">
              <input class="form-control" value="${item.discount_type ?? ''}" name="pb[${idx}][discount_type]" hidden />
            </td>
          </tr>
        `;
            }

            // Store PO metadata for recompute (id -> po meta)
            const selectedPOs =
        new Map(); // { id: {id, pono, supplier_name, del_date, ref_supplier, tquantity, subTotal} }

            // Recompute all header fields from selectedPOs
            function recomputeHeadersFromSelection() {
                const pos = new Set();
                const names = new Set();
                const dates = new Set();
                const refs = new Set();

                let totalQty = 0;
                let totalAmt = 0;

                for (const [, po] of selectedPOs) {
                    if (po?.pono) pos.add(po.pono);
                    if (po?.supplier_name) names.add(po.supplier_name);
                    if (po?.del_date) dates.add(po.del_date);
                    if (po?.ref_supplier) refs.add(po.ref_supplier);
                    totalQty += num(po?.tquantity ?? po?.quantity ?? po?.po_quantity);
                    totalAmt += num(po?.subTotal ?? po?.sub_total ?? po?.subtotal ?? po?.amount);
                }

                $('input[name="poNumbers"]').val(Array.from(pos).join(', '));
                $('#supplierName').val(Array.from(names).join(', '));
                $('#del_date').val(Array.from(dates).join(', '));
                $('#Supplier_ref').val(Array.from(refs).join(', '));

                // PO list string
                const multi = Array.from(pos).join(', ');
                $('input[name="mulitple_po"]').val(multi);

                // Header totals
                $('#total_qty').val(totalQty);
                $('#total_amount').val(totalAmt.toFixed(2));
            }

            // Load modal options; preselect currently chosen; preload their meta
            $('#addPoModal').on('shown.bs.modal', function() {
                $.get(`{{ route('supplier.eligible.pos') }}`, function(list) {
                    const $sel = $('#add-po-select');
                    let html = '';
                    (list || []).forEach(po => {
                        html +=
                            `<option value="${po.id}">${po.pono} (${po.ref_supplier})</option>`;
                    });
                    $sel.html(html);
                    if ($sel.selectpicker) $sel.selectpicker('refresh');

                    // Preselect existing selection
                    const already = getSelectedPOIdsArray();
                    $sel.selectpicker('val', already);

                    // Ensure metadata for already selected POs exists
                    already.forEach(function(id) {
                        if (!selectedPOs.has(String(id))) {
                            const url = poDetailTemplate.replace('__ID__', id);
                            $.get(url, function(payload) {
                                if (payload && payload.po) {
                                    selectedPOs.set(String(id), payload.po);
                                    recomputeHeadersFromSelection();
                                }
                            });
                        }
                    });
                });
            });

            // Apply selection: add new, remove deselected, recompute everything
            $('#add-po-confirm').on('click', function() {
                const newSel = new Set($('#add-po-select').val() || []);
                const oldSel = new Set(getSelectedPOIdsArray());

                // Compute diffs
                const toAdd = Array.from(newSel).filter(id => !oldSel.has(id));
                const toRemove = Array.from(oldSel).filter(id => !newSel.has(id));

                let idx = $('#productTable tr').length;

                // Handle removals first
                toRemove.forEach(function(id) {
                    $('#productTable tr[data-poid="' + id + '"]').remove();
                    selectedPOs.delete(String(id));
                    oldSel.delete(id);
                });

                // If nothing to add, just finalize
                if (toAdd.length === 0) {
                    setSelectedPOIdsArray(Array.from(newSel));
                    recomputeHeadersFromSelection();
                    if (typeof calculateTotal === 'function') calculateTotal();
                    if (typeof handleTds === 'function') handleTds();
                    $('#addPoModal').modal('hide');
                    return;
                }

                // Additions
                let pending = toAdd.length;
                toAdd.forEach(function(id) {
                    const url = poDetailTemplate.replace('__ID__', id);
                    $.get(url, function(payload) {
                        if (payload && payload.po) selectedPOs.set(String(id), payload.po);

                        (payload.rows || []).forEach(function(r) {
                            const token = uniqToken();
                            const item = {
                                pono: payload.po?.pono,
                                product_id: r.product_id,
                                product_code: r.product_code,
                                product_name: r.product_name,
                                poid: r.poid,
                                EAN: r.EAN,
                                remqty: r.remqty,
                                rate: r.rate,
                                gstslab: r.gstslab,
                                remaining_discount: r.remaining_discount,
                                discount_type: r.discount_type
                            };
                            idx += 1;
                            $('#productTable').append(renderRowFromItem(item, token,
                                idx));
                        });

                        oldSel.add(String(id));
                    }).always(function() {
                        pending -= 1;
                        if (pending === 0) {
                            // finalize selection & recompute
                            const finalIds = Array.from(newSel);
                            setSelectedPOIdsArray(finalIds);
                            recomputeHeadersFromSelection();
                            if (typeof calculateTotal === 'function') calculateTotal();
                            if (typeof handleTds === 'function') handleTds();
                            $('#addPoModal').modal('hide');
                        }
                    });
                });
            });
        })();
    </script>
@endsection
