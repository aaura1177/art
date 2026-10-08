@extends('layouts.app')

@section('content')
    <div class="mx-2">
        <div class="row mx-0 my-2">
            <h2>Edit Invoice (Multi Consumable)</h2>
        </div>
        <style>
            /* ===================== Custom Laptop/Desktop Responsive Table ===================== */
            .po-table-wrap {
                width: 100%;
                overflow-x: hidden;
                /* clean on desktop */
            }

            @media (max-width: 1199px) {
                .po-table-wrap {
                    overflow-x: auto;
                }

                /* only scroll on small laptops */
            }

            .po-table {
                width: 100%;
                border-collapse: separate;
                border-spacing: 0 6px;
                table-layout: fixed;
                font-size: 0.93rem;
                color: #222;
            }

            .po-table thead th {
                text-align: left;
                font-weight: 600;
                padding: 10px 12px;
                border-bottom: 1px solid #e6e6e6;
                background: #f8f9fb;
                white-space: nowrap;
            }

            .po-table tbody td {
                padding: 8px 12px;
                vertical-align: middle;
                background: #fff;
                border-bottom: 1px solid #f0f0f0;
            }

            .po-table tbody tr:hover {
                background: #f9fcff;
                box-shadow: 0 0 0 1px #e2efff inset;
            }

            .product-col {
                min-width: 320px;
                max-width: 540px;
                white-space: nowrap;
                overflow: hidden;
                text-overflow: ellipsis;
            }

            @media (max-width: 1366px) {
                .product-col {
                    min-width: 280px;
                    max-width: 460px;
                }
            }

            /* --- Input styling --- */
            .po-table input.form-control,
            .po-table input[type="text"],
            .po-table input[type="number"] {
                height: 34px;
                line-height: 34px;
                padding: 0 8px;
                font-size: 0.9rem;
                border: 1px solid #d9d9d9;
                border-radius: 6px;
                outline: none;
                width: clamp(72px, 6vw, 120px);
                background: #fff;
                color: #333;
                transition: 0.2s ease;
            }

            .po-table input.form-control:focus {
                border-color: #5b9dff;
                box-shadow: 0 0 0 3px rgba(91, 157, 255, 0.25);
            }

            /* Role-based widths */
            .po-table input.receiveqty,
            .po-table input.receiveqty1,
            .po-table input.receiveqty2 {
                width: clamp(64px, 5vw, 100px);
            }

            .po-table input.amount,
            .po-table input.gstamount {
                width: clamp(84px, 6vw, 110px);
            }

            .po-table input.rate {
                width: clamp(70px, 5vw, 95px);
            }

            .po-table input.discount,
            .po-table input.discountamount {
                width: clamp(80px, 6vw, 110px);
            }

            .po-table input[readonly] {
                background: #fafafa;
                color: #666;
            }

            /* Prevent ugly wrapping */
            .po-table th,
            .po-table td {
                white-space: nowrap;
            }

            @media (max-width: 1280px) {
                .po-table td {
                    white-space: normal;
                }

                .po-table input {
                    white-space: nowrap;
                }
            }

            /* === Fix EAN + Remaining QTY column overlap (no markup changes) === */

            /* Ensure numeric columns get some breathing room */
            .po-table td[id^="remqty"] {
                /* Remaining QTY value cell (has id="remqty...") */
                min-width: 120px;
                text-align: center;
            }

            /* Column #3:
           - type==1  → EAN
           - type==2  → Remaining QTY
           Give it a firm width + ellipsis so it never crashes into neighbors. */
            .po-table th:nth-child(3),
            .po-table td:nth-child(3) {
                min-width: 170px;
                /* tweak to 160–200px if needed */
                max-width: 220px;
                overflow: hidden;
                text-overflow: ellipsis;
                white-space: nowrap;
                font-variant-numeric: tabular-nums;
                /* nicer digit alignment */
            }

            /* When type==1, Remaining QTY becomes the 4th column — give it room too */
            .po-table th:nth-child(4),
            .po-table td:nth-child(4) {
                min-width: 130px;
                white-space: nowrap;
                text-align: center;
            }

            /* Small guard so table-layout: fixed doesn't squeeze too hard on tight screens */
            @media (max-width: 1366px) {

                .po-table th:nth-child(3),
                .po-table td:nth-child(3) {
                    min-width: 160px;
                }

                .po-table th:nth-child(4),
                .po-table td:nth-child(4) {
                    min-width: 120px;
                }
            }


            /* --- Show Product & EAN fully, enable desktop scroll if needed --- */

            /* Always allow horizontal scrolling on laptops/desktops */
            .po-table-wrap {
                overflow-x: auto !important;
            }

            /* Let the table expand to fit long content (and still be at least full width) */
            .po-table {
                table-layout: auto !important;
                /* natural widths */
                width: max-content !important;
                /* grow to content (triggers scroll) */
                min-width: 100% !important;
                /* but never shrink below container */
            }

            /* Product column: no truncation */
            .po-table .product-col {
                white-space: nowrap !important;
                overflow: visible !important;
                text-overflow: unset !important;
                min-width: max-content;
                /* size to its content */
            }

            /* EAN column: no truncation */
            .po-table .ean-col {
                white-space: nowrap !important;
                overflow: visible !important;
                text-overflow: unset !important;
                min-width: max-content;
            }

            /* Optional: keep numeric columns tidy while allowing table to grow */
            .po-table td[id^="remqty"] {
                text-align: center;
            }
        </style>
        <form id="createPurchaseBill" method="POST"
            action="{{ url('/supplier-dashboard/update-invoice/multi-consumable/' . $supplierInvoice->id) }}"
            enctype="multipart/form-data">
            @csrf

            <input type="hidden" name="selectedPOIds" value="{{ implode(',', $selectedPOIds) }}" />
            <input type="hidden" name="typeUnit" value="2" />

            {{-- Build a PO summary map for quick header recomputes --}}
            @php
                $headerPOMap = ($purchaseOrders ?? collect())
                    ->mapWithKeys(function ($po) {
                        return [
                            $po->id => [
                                'id' => $po->id,
                                'pono' => $po->pono,
                                'ref_supplier' => $po->ref_supplier,
                                'del_date' => $po->del_date,
                                'tquantity' => (float) $po->tquantity,
                                'subTotal' => (float) $po->subTotal,
                                'supplier_name' => optional($po->supplier)->c_name,
                                'remarks' => (string) ($po->remarks ?? ''),
                            ],
                        ];
                    })
                    ->toArray();
                $multiPoRemarksText = collect($purchaseOrders ?? [])
                    ->filter(fn ($po) => trim((string) ($po->remarks ?? '')) !== '')
                    ->map(function ($po) {
                        $remark = trim((string) $po->remarks);
                        $pono = trim((string) $po->pono);

                        return $pono !== '' ? ($pono . ': ' . $remark) : $remark;
                    })
                    ->implode("\n");
            @endphp
            <script>
                window.headerPOMap =
                    @json($headerPOMap); // { [poId]: {id, pono, supplier_name, del_date, ref_supplier, tquantity, subTotal } }
                window.invoiceType = @json($type); // 1, 2, or 3
            </script>

            @if ($errors->any())
                <div class="alert alert-danger">
                    <ul class="mb-0">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <div class="form-group">
                {{-- Header rows --}}
                <div class="row mt-3">
                    <div class="col-4">
                        <label class="control-label">{{ __('PO NO') }}</label>
                        <input type="text" class="form-control" name="poNumbers" id="po_numbers" readonly
                            value="{{ $poNumbers->implode(', ') }}" />
                    </div>

                    <div class="col-4">
                        <label class="control-label">{{ __('Supplier Name(s)') }}</label>
                        <div id="supplierNamesContainer">
                            @foreach ($supplierNames as $sName)
                                <input type="text" class="form-control mb-1" readonly value="{{ $sName }}" />
                            @endforeach
                        </div>
                    </div>

                    <div class="col-4">
                        <label class="control-label">{{ __('Delivery Date') }}</label>
                        <input type="text" class="form-control" id="delivery_dates" readonly
                            value="{{ $deliveryDates->implode(', ') }}" />
                    </div>
                </div>

                <div class="row mt-3">
                    <div class="col-4">
                        <label class="control-label">{{ __('Supplier Ref.') }}</label>
                        <input type="text" class="form-control" id="supplier_refs" readonly
                            value="{{ $supplierRefs->implode(', ') }}" />
                    </div>

                    @php
                        $toquantity = 0;
                        $subTotal = 0;
                        foreach ($purchaseOrders as $purchaseOrder) {
                            $toquantity += $purchaseOrder->tquantity;
                            $subTotal += $purchaseOrder->subTotal;
                        }
                    @endphp

                    <div class="col-4">
                        <label class="control-label">{{ __('Ordered Quantity') }}</label>
                        <input type="number" class="form-control" name="poQty" id="total_qty" readonly
                            value="{{ $toquantity }}" />
                    </div>

                    <div class="col-4">
                        <label class="control-label">{{ __('Sub Total Amount') }}</label>
                        <input type="number" class="form-control" name="poAmount" id="total_amount" readonly
                            value="{{ $subTotal }}" />
                    </div>
                </div>

                <div class="row mt-3">
                    <div class="col-8">
                        <label class="control-label">{{ __('Remark') }}</label>
                        <textarea class="form-control" name="remark" id="remarks" readonly>{{ $multiPoRemarksText }}</textarea>
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
<p style="color: red; font-weight: 500;">
    E-Way Bill required when invoice total exceeds ₹{{ number_format($ewayThreshold ?? 100000, 0, '.', ',') }} (Jaipur: ₹2,00,000).
</p>
                        @error('eway_bill_no')
                            <small class="text-danger">{{ $message }}</small>
                        @enderror
                    </div>

                    <div class="col-4">
                        <label class="control-label">{{ __('Vehicle No.') }}</label>
                        <input type="text" class="form-control toUpperCase" name="vehicle_no" id="vehicle_no"
                            value="{{ $supplierInvoice->vehicle_no }}" />
                    </div>
                </div>

                <div class="row mt-3">
                    <div class="col-4">
                        <label class="control-label">{{ __('E-Way Bill Upload') }}</label>
                        <input type="file" class="form-control toUpperCase" id="eway_file" name="eway_bill_upload" />
                    </div>

                    <div class="col-4">
                        <label class="control-label">{{ __('Supplier Invoice Date') }}</label>
                        <input type="date" class="form-control" name="invoice_date" id="invoice_date"
                            value="{{ $supplierInvoice->invoice_date ? \Carbon\Carbon::parse($supplierInvoice->invoice_date)->format('Y-m-d') : '' }}" />
                    </div>
                </div>

                {{-- Lines table --}}
                <div class="row mt-3 po-table-wrap">
                    <table class="table table-hover po-table">
                        <thead>
                            <tr id="mytable">
                                <th scope="col">Po NO</th>
                                <th scope="col" style="min-width: 300px;">Product</th>
                                @if ($type == 1)
                                    <th scope="col" class="ean-col">EAN</th>
                                @endif
                                @if ($type == 1 || $type == 2)
                                    <th scope="col">Remaining QTY</th>
                                    <th scope="col">Quantity</th>
                                    @if ($type == 2)
                                        <th scope="col">Unit</th>
                                    @endif
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
                        <tbody id="productTable">
                            <?php $productRows = 0; ?>
                            @foreach ($poTable as $p => $pots)
                                @php
                                    $productRows++;
                                    $lineKey = \App\Support\ConsumableMonthEndInvoiceSupport::invoiceLineDomKey(
                                        (int) $pots->poid,
                                        (int) $pots->id,
                                        $productRows
                                    );
                                    $poAddr = (int) (($addressOptionByPo[$pots->poid] ?? null) ?? $pots->po_address_option ?? optional($pots->purchaseOrderTable)->address_option ?? 0);
                                    $dataType = $pots->data_type ?? 'float';
                                    $unitLabel = optional(optional($pots->consumable)->unitType)->name ?? ($pots->unit ?? '');
                                    $pocRowAttrs = ($poAddr === 100 && isset($pots->id))
                                        ? ' data-poc-quantity="' . e($pots->quantity) . '" data-poc-amount="' . e($pots->amount) . '" data-poc-gstamount="' . e($pots->gstamount) . '"'
                                        : '';
                                @endphp
                                <tr data-poid="{{ $pots->poid }}" data-line-key="{{ $lineKey }}"{!! $pocRowAttrs !!}>
                                    <td scope="col">{{ $pots->purchaseOrderTable->pono }}</td>
                                    <td scope="col" class="product-col">
                                        @if (isset($pots->product->code))
                                            {{ $pots->product->code }} - {{ $pots->product->name }}
                                        @else
                                            {{ $pots->consumable->name }}
                                            @if (!empty($pots->description))
                                                <p class="mb-0 small text-muted">Description: {{ $pots->description }}</p>
                                            @endif
                                        @endif
                                        @if (isset($pots->product_id))
                                            <input name="pb[{{ $productRows }}][product]" type="hidden" value="{{ $pots->product_id }}" />
                                        @else
                                            <input name="pb[{{ $productRows }}][product]" type="hidden" value="{{ $pots->consumable_id }}" />
                                        @endif
                                        <input name="pb[{{ $productRows }}][poid]" type="hidden" value="{{ $pots->poid }}" />
                                        @if ($poAddr === 100 && \Illuminate\Support\Facades\Schema::hasColumn('supplier_invoice_products', 'poc_table_id'))
                                            <input type="hidden" name="pb[{{ $productRows }}][poc_table_id]" value="{{ (int) $pots->id }}" />
                                        @endif
                                    </td>
                                    <td scope="col" id="remqty{{ $lineKey }}">{{ $pots->remqty }}</td>
                                    <td scope="col" id="qty{{ $lineKey }}">
                                        <input class="form-control receiveqty" value="{{ $pots->invoice_qty ?? 0 }}"
                                            min="0" max="{{ $pots->max_receive ?? $pots->remqty }}"
                                            step="{{ $dataType === 'int' ? '1' : '0.01' }}"
                                            name="pb[{{ $productRows }}][receiveqty]" type="number"
                                            data-line-key="{{ $lineKey }}" autocomplete="off"
                                            title="Maximum allowed: {{ $pots->max_receive ?? $pots->remqty }}"
                                            onchange="handleReceiveQtyInput(this, '{{ $dataType }}')"
                                            oninput="handleReceiveQtyInput(this, '{{ $dataType }}')" />
                                    </td>
                                    <td scope="col">
                                        <input class="form-control" value="{{ $unitLabel }}" readonly
                                            name="pb[{{ $productRows }}][unit]" type="text" />
                                    </td>
                                    <td scope="col" id="rate{{ $lineKey }}">
                                        <input class="form-control" value="{{ $pots->rate }}"
                                            name="pb[{{ $productRows }}][rate]" readonly />
                                    </td>
                                    <td scope="col" id="amount{{ $lineKey }}">
                                        <input value="{{ $pots->prefill_amount ?? 0 }}" class="form-control amount"
                                            name="pb[{{ $productRows }}][amount]" readonly />
                                    </td>
                                    <td scope="col" id="gstslab{{ $lineKey }}">
                                        <input class="form-control" value="{{ $pots->gstslab }}"
                                            name="pb[{{ $productRows }}][gstslab]" readonly />
                                    </td>
                                    <td scope="col" id="gstamount{{ $lineKey }}">
                                        @php
                                            $prefillGst = (($pots->prefill_amount ?? 0) * ($pots->gstslab ?? 0)) / 100;
                                        @endphp
                                        <input value="{{ number_format($prefillGst, 2, '.', '') }}" class="form-control gstamount"
                                            name="pb[{{ $productRows }}][gstamount]" readonly />
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
                        <input type="text" name="type" id='invoiceTotal' value="{{ $invoiceTotal ?? 0 }}" hidden />
                    </div>

                    <div class="col-4">
                        <label class="control-label">{{ __('Total TDS') }}</label>
                        <input type="number" class="form-control" name="tdsTotal" id="tdsToal" readonly />
                        <input type="number" class="form-control" id="tdsPercent"
                            value="{{ $userSplier->supplier->tdspercent }}" hidden />
                        <input type="date" class="form-control" id="tdsDate"
                            value="{{ $userSplier->supplier->tdsdate }}" hidden />
                    </div>

                    <div class="col-4">
                        <label class="control-label">{{ __('Round Off') }}</label>
                        <input type="number" class="form-control" step=".1" name="roundoff" id="roundoff"
                            value="{{ $supplierInvoice->roundoff ?? 0 }}" onchange="recalQuantity(this);" />
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

    {{-- Add / Remove PO Modal --}}
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
                            Select to add, deselect to remove. Lines & totals will update accordingly.
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
    @include('partials.supplier-invoice-tds-js')
    @include('partials.month-end-consumable-invoice-math-js')
    <script type="text/javascript">
        const EWAY_BILL_THRESHOLD = {{ (float) ($ewayThreshold ?? 100000) }};
        var invoiceType = 2;

        function parseQtyBound(value) {
            const n = parseFloat(String(value ?? '').replace(/[, ]/g, ''));
            return isNaN(n) ? null : n;
        }

        function enforceReceiveQtyLimit(input) {
            if (!input || input.readOnly || input.disabled) {
                return true;
            }

            const min = parseQtyBound(input.min);
            const max = parseQtyBound(input.getAttribute('max'));
            const raw = String(input.value ?? '').trim();

            if (raw === '' || raw === '-') {
                input.setCustomValidity('');
                return true;
            }

            let val = parseQtyBound(raw);
            if (val === null) {
                input.setCustomValidity('Enter a valid quantity.');
                return false;
            }

            const step = parseQtyBound(input.step);
            if (step === 1) {
                val = Math.floor(val);
            }

            let clamped = val;
            if (min !== null && clamped < min) {
                clamped = min;
            }
            if (max !== null && clamped > max) {
                clamped = max;
            }

            if (clamped !== val) {
                const decimals = step === 1 ? 0 : 2;
                input.value = Number(clamped.toFixed(decimals)).toString();
                val = clamped;
            }

            if (max !== null && val > max + 1e-9) {
                input.setCustomValidity('Receive quantity cannot exceed ' + max + '.');
                return false;
            }
            if (min !== null && val < min - 1e-9) {
                input.setCustomValidity('Receive quantity cannot be less than ' + min + '.');
                return false;
            }

            input.setCustomValidity('');
            return true;
        }

        function handleTypeRestriction(input, dataType) {
            let val = input.value;
            if (dataType === 'int' && val.includes('.')) {
                input.value = Math.floor(val);
            }
        }

        function handleReceiveQtyInput(input, dataType) {
            handleTypeRestriction(input, dataType);
            const valid = enforceReceiveQtyLimit(input);
            if (valid && input.classList.contains('receiveqty')) {
                recalQuantity(input);
            }
            return valid;
        }

        function enforceCartonReceiveQtyLimit(input) {
            return enforceReceiveQtyLimit(input);
        }

        // ========== Bootstrap initial data ==========
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

            if ($('#productTable tr').length > 0) {
                $('.receiveqty').each(function(_, el) {
                    enforceReceiveQtyLimit(el);
                    recalQuantity(el);
                });
                if (typeof handleTds === 'function') {
                    handleTds($('#pbSubTotal').val() || 0);
                }
            }
            SupplierInvoiceTds.bindInvoiceDate();
        });

        var purchaseOrder = [],
            supplier = [],
            pbProduct = [],
            pbPoTable = [];
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

            function findSupplier(supplierName) {
                return supplierName.id == supplierId;
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

        // Consumable multi-PO: recalc within the row only (M-series may repeat consumable per PO).
        function recalQuantity(ref) {
            if (!$('#invoice_date').val()) {
                alert('Please select Invoice Date');
                ref.value = 0;
                enforceReceiveQtyLimit(ref);
                return;
            }
            if (ref && ref.classList && ref.classList.contains('receiveqty')) {
                enforceReceiveQtyLimit(ref);
            }
            const $row = $(ref).closest('tr');
            const quantity = Number($(ref).val()) || 0;
            const rate = parseFloat($row.find('input[name*="[rate]"]').val()) || 0;
            const gstslab = parseFloat($row.find('input[name*="[gstslab]"]').val()) || 0;
            const pocQty = parseFloat($row.attr('data-poc-quantity')) || 0;
            let lineAmount;
            let gstamount;
            if (pocQty > 0) {
                const totals = monthEndConsumableRowTotals($row, quantity);
                lineAmount = totals.amount;
                gstamount = totals.gst;
            } else {
                lineAmount = rate * quantity;
                gstamount = (lineAmount * gstslab) / 100;
            }
            $row.find('input.amount, input[name*="[amount]"]').val(Number(lineAmount).toFixed(2));
            $row.find('input.gstamount, input[name*="[gstamount]"]').val(Number(gstamount).toFixed(2));
            calculateTotal();
            handleTds($('#pbSubTotal').val());
        }

        // --- Quantity recalculation (type 3 carton rows) ---
        function recalQuantityCarton(ref) {
            let token = $(ref).parent().attr('id');
            let quantity1 = parseInt($('#rec1' + token).val(), 10) || 0;
            let quantity2 = parseInt($('#rec2' + token).val(), 10) || 0;

            let rate1 = parseFloat($('#rate1' + token + ' input').val()) || 0;
            let rate2 = parseFloat($('#rate2' + token + ' input').val()) || 0;
            let gstslab = parseFloat($('#gstslab' + token + ' input').val()) || 0;

            let oldrate1 = rate1 * quantity1;
            let oldrate2 = rate2 * quantity2;
            let totoldrate = oldrate1 + oldrate2;

            $("#amount" + token + " input").val(totoldrate.toFixed(2));
            let gstamount = (totoldrate * gstslab) / 100;
            $('#gstamount' + token + ' input').val(gstamount.toFixed(2));

            calculateTotal();
            handleTds($('#pbSubTotal').val());
        }

        function deleteRow(ref) {
            $(ref).parents("tr").remove();
            calculateTotal();
        }

        function calculateTotal() {
            var totQty = 0, subTot = 0, gstamount = 0;
            $('.receiveqty').each(function() {
                const val = parseFloat($(this).val());
                if (!isNaN(val)) totQty += val;
            });
            $('.amount').each(function() {
                subTot += parseFloat($(this).val()) || 0;
            });
            $('.gstamount').each(function() {
                gstamount += parseFloat($(this).val()) || 0;
            });
            var pbTotal = subTot + gstamount + monthEndRoundOffAdd();
            $('#pbSubTotal').val(subTot.toFixed(2));
            $('#pbQty').val(Number(totQty.toFixed(10)).toString());
            $('#pbGST').val(gstamount.toFixed(2));
            $('#pbTotal').val(pbTotal.toFixed(2));
            var potamount = parseFloat($('#pbTotal').val()) || 0;
            if (potamount > EWAY_BILL_THRESHOLD) {
                $('#ewaybill').attr('required', true);
                $('#eway_file').attr('required', true);
            } else {
                $('#ewaybill').removeAttr('required');
                $('#eway_file').removeAttr('required');
            }
            handleTds(subTot);
        }

        $('#createPurchaseBill').on('submit', function(event) {
            var qtyInvalid = false;

            $('#productTable input[name*="[receiveqty]"], #productTable input[name*="[receiveqty_box_1]"], #productTable input[name*="[receiveqty_box_2]"]').each(function() {
                enforceReceiveQtyLimit(this);
                if (!this.checkValidity()) {
                    qtyInvalid = true;
                    this.reportValidity();
                    return false;
                }
            });

            if (qtyInvalid) {
                event.preventDefault();
                return;
            }

            if ($('#productTable tr').length < 1) {
                alert("No product added. Add atleast 1 product.");
                event.preventDefault();
            } else {
                $('#formSubmit').prop('disabled', true);
            }
        });

    </script>

    <script>
        // ===== Add / Remove POs (with full recompute) =====
        (function() {
            // ----- Utils -----
            function getSelectedPOIdsArray() {
                const val = $('input[name="selectedPOIds"]').val() || '';
                return val.split(',').map(s => s.trim()).filter(Boolean);
            }

            function setSelectedPOIdsArray(arr) {
                $('input[name="selectedPOIds"]').val(arr.join(','));
            }

            function uniqToken() {
                return 'r' + Math.random().toString(36).slice(2, 10);
            }

            function num(v) {
                const x = parseFloat(String(v ?? 0).replace(/[, ]/g, ''));
                return isNaN(x) ? 0 : x;
            }

            const poDetailTemplate = @json(route('supplier.po.detail.consumable', ['id' => '__ID__']));

            // ----- Row builders (support type 1/2/3) -----
            function consumableLineDomKey(item, idx) {
                const poid = parseInt(item.poid, 10) || 0;
                const pocId = parseInt(item.poc_table_id, 10) || 0;
                if (pocId > 0) {
                    return 'p' + poid + '_l' + pocId;
                }
                return 'p' + poid + '_r' + idx;
            }

            function renderRowType12(item, token, idx) {
                const lineKey = consumableLineDomKey(item, idx);
                const step = (item.data_type === 'int') ? '1' : '0.01';
                const desc = item.description ? `<p class="mb-0 small text-muted">Description: ${item.description}</p>` : '';
                const pocHidden = (item.address_option === 100 && item.poc_table_id)
                    ? `<input type="hidden" name="pb[${idx}][poc_table_id]" value="${item.poc_table_id}" />` : '';
                const pocAttrs = (item.address_option === 100 && item.poc_table_id)
                    ? ` data-poc-quantity="${item.quantity ?? 0}" data-poc-amount="${item.amount ?? 0}" data-poc-gstamount="${item.gstamount ?? 0}"`
                    : '';
                return `
                  <tr data-poid="${item.poid || ''}" data-line-key="${lineKey}"${pocAttrs}>
                    <td scope="col">${item.pono || ''}</td>
                    <td scope="col" class="product-col">
                      ${(item.product_code ? (item.product_code + ' - ') : '') + (item.product_name || '')}${desc}
                      <input name="pb[${idx}][product]" type="hidden" value="${item.product_id || ''}"/>
                      <input name="pb[${idx}][poid]" type="hidden" value="${item.poid || ''}"/>
                      ${pocHidden}
                    </td>
                    <td scope="col" id="remqty${lineKey}">${item.remqty ?? 0}</td>
                    <td scope="col" id="qty${lineKey}">
                      <input class="form-control receiveqty" value="0" min="0" max="${item.remqty ?? 0}" step="${step}"
                             name="pb[${idx}][receiveqty]" type="number" data-line-key="${lineKey}" autocomplete="off"
                             title="Maximum allowed: ${item.remqty ?? 0}"
                             onchange="handleReceiveQtyInput(this, '${item.data_type || 'float'}')"
                             oninput="handleReceiveQtyInput(this, '${item.data_type || 'float'}')" />
                    </td>
                    <td scope="col">
                      <input class="form-control" value="${item.unit || ''}" name="pb[${idx}][unit]" readonly type="text" />
                    </td>
                    <td scope="col" id="rate${lineKey}">
                      <input class="form-control" value="${item.rate ?? 0}" name="pb[${idx}][rate]" readonly/>
                    </td>
                    <td scope="col" id="amount${lineKey}">
                      <input value="0" class="form-control amount" name="pb[${idx}][amount]" readonly />
                    </td>
                    <td scope="col" id="gstslab${lineKey}">
                      <input class="form-control" value="${item.gstslab ?? 0}" name="pb[${idx}][gstslab]" readonly />
                    </td>
                    <td scope="col" id="gstamount${lineKey}">
                      <input value="0" class="form-control gstamount" name="pb[${idx}][gstamount]" readonly />
                    </td>
                  </tr>
                `;
            }

            function renderRowType3(item, token, idx) {
                return `
                  <tr data-poid="${item.poid || ''}">
                    <td scope="col">${item.pono || ''}</td>
                    <td scope="col">
                      ${(item.product_code ? (item.product_code + ' - ') : '') + (item.product_name || '')}
                      <input name="pb[${idx}][product]" type="hidden" value="${item.product_id || ''}"/>
                      <input name="pb[${idx}][poid]"    type="hidden" value="${item.poid || ''}"/>
                    </td>
                    <td scope="col">${item.remqty_box1 ?? 0}</td>
                    <td scope="col">${item.remqty_box2 ?? 0}</td>
                    <td scope="col" id="${token}">
                      <input id="rec1${token}" class="form-control receiveqty1" value="0" min="0" max="${item.remqty_box1 ?? 0}"
                             name="pb[${idx}][receiveqty_box_1]" type="number"
                             title="Maximum allowed: ${item.remqty_box1 ?? 0}"
                             oninput="enforceCartonReceiveQtyLimit(this);"
                             onchange="enforceCartonReceiveQtyLimit(this); recalQuantityCarton(this);" />
                    </td>
                    <td scope="col" id="${token}">
                      <input id="rec2${token}" class="form-control receiveqty2" value="0" min="0" max="${item.remqty_box2 ?? 0}"
                             name="pb[${idx}][receiveqty_box_2]" type="number"
                             title="Maximum allowed: ${item.remqty_box2 ?? 0}"
                             oninput="enforceCartonReceiveQtyLimit(this);"
                             onchange="enforceCartonReceiveQtyLimit(this); recalQuantityCarton(this);" />
                    </td>
                    <td scope="col" id="rate1${token}">
                      <input class="form-control" value="${item.box1_rate ?? 0}" name="pb[${idx}][box1_rate]" readonly />
                    </td>
                    <td scope="col" id="rate2${token}">
                      <input class="form-control" value="${item.box2_rate ?? 0}" name="pb[${idx}][box2_rate]" readonly />
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
                    <td id="discount_type${token}">
                      <input class="form-control" value="${item.discount_type ?? ''}" name="pb[${idx}][discount_type]" readonly />
                    </td>
                    <td id="discount${token}">
                      <input value="${item.remaining_discount ?? 0}" class="form-control discount" name="pb[${idx}][discount]" readonly />
                    </td>
                    <td id="discountamount${token}">
                      <input value="" class="form-control discountamount" name="pb[${idx}][discountamount]" readonly />
                    </td>
                    <td id="remaing_discount${token}">
                      <input value="" class="form-control remaing_discount" name="pb[${idx}][remaing_discount]" hidden />
                    </td>
                  </tr>
                `;
            }

            function renderRowFromItem(item, token, idx) {
                return renderRowType12(item, token, idx);
            }

            // ----- Header recompute from current selection -----
            function recomputeHeadersFromSelection() {
                const ids = getSelectedPOIdsArray();
                const namesSet = new Set();
                const poSet = new Set();
                const dateSet = new Set();
                const refSet = new Set();
                let qtySum = 0,
                    subTotalSum = 0;

                ids.forEach(id => {
                    const info = window.headerPOMap?.[id];
                    if (!info) return;
                    if (info.pono) poSet.add(info.pono);
                    if (info.supplier_name) namesSet.add(info.supplier_name);
                    if (info.del_date) dateSet.add(info.del_date);
                    if (info.ref_supplier) refSet.add(info.ref_supplier);
                    qtySum += num(info.tquantity);
                    subTotalSum += num(info.subTotal);
                });

                $('#po_numbers').val(Array.from(poSet).join(', '));

                const $names = $('#supplierNamesContainer').empty();
                Array.from(namesSet).forEach(n => {
                    $('<input type="text" class="form-control mb-1" readonly />').val(n).appendTo($names);
                });

                $('#delivery_dates').val(Array.from(dateSet).join(', '));
                $('#supplier_refs').val(Array.from(refSet).join(', '));

                $('#total_qty').val(qtySum);
                $('#total_amount').val(subTotalSum.toFixed(2));

                const remarkLines = [];
                ids.forEach(id => {
                    const info = window.headerPOMap?.[id];
                    if (!info) return;
                    const remark = String(info.remarks ?? '').trim();
                    if (remark === '') return;
                    const pono = String(info.pono ?? '').trim();
                    remarkLines.push(pono !== '' ? (pono + ': ' + remark) : remark);
                });
                $('#remarks').val(remarkLines.join('\n'));
            }

            recomputeHeadersFromSelection();

            // ----- Modal open: load ALL accepted POs and preselect current -----
            $('#addPoModal').on('shown.bs.modal', function() {
                $.get(`{{ route('supplier.eligible.pos.consumable') }}`, function(list) {
                    const $sel = $('#add-po-select');
                    let html = '';
                    (list || []).forEach(po => {
                        html +=
                            `<option value="${po.id}">${po.pono} (${po.ref_supplier})</option>`;
                    });
                    $sel.html(html);
                    if ($sel.selectpicker) $sel.selectpicker('refresh');

                    // preselect
                    const already = getSelectedPOIdsArray();
                    $sel.selectpicker('val', already);
                });
            });

            // ----- Apply selection: add new, remove deselected, recompute everything -----
            $('#add-po-confirm').on('click', function() {
                const newSel = new Set($('#add-po-select').val() || []);
                const oldSel = new Set(getSelectedPOIdsArray());

                const toAdd = Array.from(newSel).filter(id => !oldSel.has(id));
                const toRemove = Array.from(oldSel).filter(id => !newSel.has(id));

                // 1) Remove rows for deselected POs
                toRemove.forEach(function(id) {
                    $('#productTable tr[data-poid="' + id + '"]').remove();
                });

                // 2) If nothing to add, just finalize
                if (toAdd.length === 0) {
                    setSelectedPOIdsArray(Array.from(newSel));
                    recomputeHeadersFromSelection();
                    if (typeof calculateTotal === 'function') calculateTotal();
                    if (typeof handleTds === 'function') handleTds($('#pbSubTotal').val());
                    $('#addPoModal').modal('hide');
                    return;
                }

                // 3) Add new POs
                let idx = $('#productTable tr').length;
                let pending = toAdd.length;

                toAdd.forEach(function(id) {
                    const url = poDetailTemplate.replace('__ID__', id);
                    $.get(url, function(payload) {
                        // store PO meta for headers
                        if (payload && payload.po) {
                            window.headerPOMap = window.headerPOMap || {};
                            window.headerPOMap[String(id)] = payload.po;
                        }

                        (payload.rows || []).forEach(function(r) {
                            const token = uniqToken();
                            const item = {
                                pono: payload.po?.pono,
                                product_id: r.product_id,
                                product_code: r.product_code,
                                product_name: r.product_name,
                                poid: r.poid,
                                poc_table_id: r.poc_table_id,
                                description: r.description,
                                remqty: r.remqty,
                                rate: r.rate,
                                gstslab: r.gstslab,
                                unit: r.unit,
                                data_type: r.data_type || 'float',
                                address_option: r.address_option ?? payload.po?.address_option
                            };
                            idx += 1;
                            $('#productTable').append(renderRowFromItem(item, token,
                                idx));
                        });
                    }).always(function() {
                        pending -= 1;
                        if (pending === 0) {
                            // finalize selection & recompute
                            setSelectedPOIdsArray(Array.from(newSel));
                            recomputeHeadersFromSelection();
                            if (typeof calculateTotal === 'function') calculateTotal();
                            if (typeof handleTds === 'function') handleTds($('#pbSubTotal')
                                .val());
                            $('#addPoModal').modal('hide');
                        }
                    });
                });
            });
        })();
    </script>
@endsection