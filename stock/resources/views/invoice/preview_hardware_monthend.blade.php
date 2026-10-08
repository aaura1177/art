@extends('layouts.modal')
<style>
    .me-preview .container {
        width: 1180px !important;
        max-width: 100%;
    }

    /* Month-end preview: readable, bold table text (was 10px / 8px on small screens) */
    .me-preview .table th,
    .me-preview .table td {
        font-size: 14px !important;
        line-height: 1.35 !important;
        padding: 10px 12px !important;
        vertical-align: middle !important;
    }

    .me-preview .table thead th {
        font-size: 14px !important;
        font-weight: 700 !important;
        background-color: #f0f0f0 !important;
    }

    .me-preview .table tbody td {
        font-weight: 600 !important;
    }

    .me-preview .table-striped tbody tr:nth-of-type(odd) td {
        background-color: #fafafa !important;
    }

    /* Summary rollup table uses Bootstrap table-sm — undo compact sizing */
    .me-preview .table.table-sm th,
    .me-preview .table.table-sm td {
        font-size: 14px !important;
        padding: 9px 11px !important;
        font-weight: 600 !important;
    }

    .me-preview .table.table-sm thead th {
        font-weight: 700 !important;
    }

    @media screen and (max-width: 1199px) {
        .me-preview .container {
            width: 100% !important;
            max-width: 100% !important;
        }

        .me-preview .table th,
        .me-preview .table td {
            white-space: normal !important;
            word-break: break-word !important;
            font-size: 13px !important;
            padding: 8px 10px !important;
        }
    }
</style>

@php
    $firstInvoice = is_array($previewData ?? null) && count($previewData) > 0 ? collect($previewData)->first() : null;
    $firstPO = $firstInvoice ? collect($firstInvoice['supplierGroups'] ?? [])->first() : null;
    $firstPONo = $firstPO ? $firstPO['po']['pono'] : 'M/1';
    $poNumeric = (int) str_replace('M/', '', (string) $firstPONo);
    if ($poNumeric < 1) {
        $poNumeric = 1;
    }
@endphp

<div class="me-preview">

@if (empty($previewData))
    <div class="container" style="margin-bottom:1rem; padding:2rem 1rem;">
        <h2 class="text-danger">No hardware month-end lines to preview</h2>
        <p>Nothing matched for this request (invoice / supplier / product&ndash;consumable mapping), or a hardware month-end PO already exists for that invoice and supplier.</p>
        <ul class="text-start" style="max-width:36rem;">
            <li>Includes only consumables with <strong>hardware month-end PO product</strong> enabled and <strong>month end PO supplier</strong> set.</li>
            <li>Each invoice line&rsquo;s product must be linked in <strong>wf_consumable</strong> to those consumables.</li>
            <li>No buyer filter — quantities follow invoice &times; wf only.</li>
        </ul>
        @if (!empty($skippedInvoices))
            <p><strong>Invoices with no matching rows:</strong> {{ implode(', ', $skippedInvoices) }}</p>
        @endif
        <p><a href="{{ url('/invoice') }}">&larr; Back to invoices</a></p>
    </div>
@endif

@foreach ($previewData as $invoiceNo => $invoiceData)
    @php
        $invoice = $invoiceData['invoice'];
        $supplierGroups = $invoiceData['supplierGroups'];
        $allItems = [];
        foreach ($supplierGroups as $g) {
            $allItems = array_merge($allItems, $g['items'] ?? []);
        }
        $consumableSummary = [];
        foreach ($allItems as $it) {
            $cid = (int) ($it['consumable_id'] ?? 0);
            $cname = (string) ($it['name'] ?? '');
            $k = $cid > 0 ? (string) $cid : $cname;
            if (!isset($consumableSummary[$k])) {
                $consumableSummary[$k] = [
                    'name' => $cname,
                    'unit' => (string) ($it['unit'] ?? ''),
                    'total_qty' => 0.0,
                    'total_amount' => 0.0,
                    'total_gst' => 0.0,
                    'total_line' => 0.0,
                ];
            }
            $consumableSummary[$k]['total_qty'] += (float) ($it['qty'] ?? 0);
            $consumableSummary[$k]['total_amount'] += (float) ($it['amount'] ?? 0);
            $gLine = (float) ($it['gst_amount'] ?? 0);
            if ($gLine === 0.0 && isset($it['amount'], $it['total'])) {
                $gLine = (float) $it['total'] - (float) $it['amount'];
            }
            $consumableSummary[$k]['total_gst'] += $gLine;
            $consumableSummary[$k]['total_line'] += (float) ($it['total'] ?? 0);
        }
        uasort($consumableSummary, fn($a, $b) => strcmp($a['name'] ?? '', $b['name'] ?? ''));

        $poSumQty = 0.0;
        $poSumAmt = 0.0;
        $poSumGst = 0.0;
        $poSumTot = 0.0;
        foreach ($allItems as $pi) {
            $poSumQty += (float) ($pi['qty'] ?? 0);
            $poSumAmt += (float) ($pi['amount'] ?? 0);
            $gpi = (float) ($pi['gst_amount'] ?? 0);
            if ($gpi === 0.0 && isset($pi['amount'], $pi['total'])) {
                $gpi = (float) $pi['total'] - (float) $pi['amount'];
            }
            $poSumGst += $gpi;
            $poSumTot += (float) ($pi['total'] ?? 0);
        }
    @endphp

    <h1 class="text-center">Hardware Month End PO &mdash; Preview</h1>
    <p class="text-center text-muted mb-3" style="font-size:1.05rem;">Source sales invoice: <strong>{{ $invoiceNo }}</strong></p>

    @foreach ($supplierGroups as $supplierId => $groupData)
        @php
            $po = $groupData['po'] ?? [];
            $supplier = \App\supplier::find($supplierId);
            $items = $groupData['items'] ?? [];
            $addrOpt = (int) ($po['address_option'] ?? 0);
            if ($addrOpt === 1) {
                $dispatchMode = 'Office';
            } elseif ($addrOpt === 100) {
                $dispatchMode = 'Factory (month end)';
            } else {
                $dispatchMode = 'Factory';
            }
            $supSubTot = 0.0;
            foreach ($items as $px) {
                $supSubTot += (float) ($px['total'] ?? 0);
            }
            $previewFooterTotals = \App\Support\ConsumableMonthEndInvoiceSupport::headerTotalsFromPocLines(
                collect($items)->map(fn ($px) => (object) [
                    'amount' => (float) ($px['amount'] ?? 0),
                    'gstslab' => (float) ($px['gst_percent'] ?? $px['gst'] ?? 0),
                    'gstamount' => (float) ($px['gst_amount'] ?? 0),
                    'quantity' => (float) ($px['qty'] ?? 0),
                ])
            );
            $supSubQty = $previewFooterTotals['tquantity'];
            $supSubAmt = $previewFooterTotals['subTotal'];
            $supSubGst = $previewFooterTotals['tgst'];
            $previewTamount = $previewFooterTotals['tamount'];
            $previewRoundOff = 0.0;
            $previewShowRoundOff = false;
        @endphp

    <div class="container" style="margin-bottom:2rem;">

        <!-- Match furniture PO: two-column header -->
        <div class="row box-space">
            <div class="col-6">
                <h3 style="text-decoration: underline;">INVOICE TO</h3><br />
                @if (isset($companyDetails))
                    @php
                        $imageName = $companyDetails->logoUrl;
                    @endphp
                    @if (is_array($fileMap1 ?? null) && array_key_exists($imageName, $fileMap1))
                        <img width="500" src="{{ $fileMap1[$imageName] }}" alt="Logo" style="width:100px;" />
                    @endif
                    <p style="font-size: 24px;"><strong>{{ $companyDetails->c_name }}</strong></p>
                    <address>
                        <p style="font-weight: 700;">{{ $companyDetails->address1 }}</p>
                        <p style="font-weight: 700;">{{ $companyDetails->address2 }}, {{ $companyDetails->city }} -
                            {{ $companyDetails->postcode }}, India</p>
                        <p>State Name: {{ $companyDetails->state }}</p>
                        <p>Company PAN: {{ $companyDetails->pan }}</p>
                        <p>GSTIN/UIN: {{ $companyDetails->gstin }}</p>
                        <p>E-mail: po@artisanfurniture.net</p>
                    </address>
                @endif
            </div>
            <div class="col-6">
                <div class="pull-right" id="supplier">
                    <h1>PO No. {{ $po['pono'] ?? 'N/A' }}</h1>
                    <p>PO Date: @if (!empty($po['podate'])){{ date('d-M-Y', strtotime($po['podate'])) }}@endif</p>
                    <br>
                    <address>
                        <h3 class="box-space" style="text-decoration: underline;">SUPPLIER</h3>
                        @if ($supplier)
                            <p>{{ $supplier->c_name }}</p>
                            <p>{{ $supplier->address1 }}</p>
                            <p>{{ $supplier->address2 }}</p>
                            <p>{{ $supplier->city }} - {{ $supplier->postcode }}</p>
                            <p>State Name: {{ $supplier->state }}</p>
                            <p>GSTIN/UIN: <span style="font-weight: 600; font-size: 16px;">{{ $supplier->gstin }}</span>
                            </p>
                            <p>Phone: {{ $supplier->phone1 }}</p>
                            <p>E-mail: {{ $supplier->email }}</p>
                        @else
                            <p>N/A</p>
                        @endif
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
                                <td>
                                    <h3 style="text-decoration: underline;">DISPATCH TO ({{ $dispatchMode }})</h3>
                                </td>
                            </tr>
                            <tr>
                                <td>
                                    <p>Global Vision Direct (P) Ltd</p>
                                </td>
                            </tr>
                            <tr>
                                <td>
                                    @if (isset($companyDetails))
                                        @if ($addrOpt === 1)
                                            <p>{{ $companyDetails->address2 }}, {{ $companyDetails->city }} -
                                                {{ $companyDetails->postcode }}, India</p>
                                        @else
                                            <p>{{ $companyDetails->factory_address ?? $companyDetails->address2 }},
                                                {{ $companyDetails->city }} - 302026, India</p>
                                        @endif
                                    @endif
                                </td>
                            </tr>
                            <tr>
                                <td>
                                    <p>GSTIN/UIN: @isset($companyDetails){{ $companyDetails->gstin }}@endisset</p>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </address>
            </div>
            <div class="col-6">
                <address>
                    <table width="100%" cellspacing="0">
                        <tbody>
                            <tr>
                                <td class="pull-right">
                                    <h3 style="text-decoration: underline;">TERMS OF PAYMENT</h3>
                                </td>
                            </tr>
                            <tr>
                                <td class="pull-right">
                                    <p style="max-width: 450px; text-align: right;">{{ $po['payterms'] ?? '' }}</p>
                                </td>
                            </tr>
                            <tr>
                                <th class="pull-right box-space">
                                    <h3 style="text-decoration: underline;">TERMS OF DELIVERY</h3>
                                </th>
                            </tr>
                            <tr>
                                <td class="pull-right" style="max-width: 360px; text-align: left;">
                                    {!! str_replace(PHP_EOL, '<br />', (string) ($po['remarks'] ?? '')) !!}
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </address>
            </div>
        </div>

        <table class="table table-bordered" width="100%" cellspacing="0">
            <tbody>
                <tr class="row">
                    <td class="col-3">
                        <strong>PO Date:</strong> @if (!empty($po['podate'])){{ date('d-M-Y', strtotime($po['podate'])) }}@endif
                    </td>
                    <td class="col-3">
                        <strong>Delivery Date:</strong> @if (!empty($po['del_date'])){{ date('d-M-Y', strtotime($po['del_date'])) }}@endif
                    </td>
                    <td class="col-3">
                        <strong>Supplier Ref:</strong> {{ $po['ref_supplier'] ?? '' }}
                    </td>
                    <td class="col-3">
                        <strong>Buyer Ref:</strong> {{ $po['buyer_orderno'] ?? '' }}
                    </td>
                </tr>
            </tbody>
        </table>

        <p class="text-start mb-1" style="font-size:1rem; font-weight:500;">
            <strong>Hardware month-end consumable lines</strong> (per furniture &times; Wf, sorted by box, product, consumable)
        </p>

        <div class="row">
            <div class="col">
                <table class="table table-striped table-bordered" width="100%" cellspacing="0">
                    <thead>
                        <tr>
                            <th style="min-width: 28px;">S. No.</th>
                            <th style="min-width: 90px;">Description of furniture</th>
                            <th style="min-width: 36px;">Fur. Qty</th>
                            <th style="min-width: 56px;">Consumable</th>
                            <th style="min-width: 44px;">Wf / fur.</th>
                            <th style="min-width: 44px;">Total cons. Qty</th>
                            <th style="min-width: 38px;">Rate (₹)</th>
                            <th style="min-width: 28px;">Unit</th>
                            <th style="min-width: 44px;">Amount (₹)</th>
                            <th style="min-width: 32px;">GST %</th>
                            <th style="min-width: 44px;">GST (₹)</th>
                            <th style="min-width: 48px;">Total (₹)</th>
                        </tr>
                    </thead>
                    <tbody>
                        @php $r = 0; @endphp
                        @foreach ($items as $item)
                            @php
                                $r++;
                                $gp = (float) ($item['gst_percent'] ?? $item['gst'] ?? 0);
                                $ga = (float) ($item['gst_amount'] ?? 0);
                                if ($ga === 0.0 && isset($item['amount'], $item['total'])) {
                                    $ga = (float) $item['total'] - (float) $item['amount'];
                                }
                            @endphp
                            <tr>
                                <td>{{ $r }}</td>
                                <td class="text-start" style="max-width: 120px;">
                                    @php
                                        $pc = trim($item['product_code'] ?? '');
                                        $pn = trim($item['product_name'] ?? '');
                                        if ($pc !== '' && $pn !== '') {
                                            $productLine = $pc . ' - ' . $pn;
                                        } else {
                                            $productLine = $pc !== '' ? $pc : $pn;
                                        }
                                    @endphp
                                    @if ($productLine !== '')
                                        {{ $productLine }}<br>
                                    @endif
                                    @if (!empty($item['descriptionBox']))
                                        <i style="font-style: italic; white-space: pre-line;">{{ $item['descriptionBox'] }}</i>
                                    @endif
                                </td>
                                <td>{{ number_format((float) ($item['furniture_qty'] ?? 0), 0, '.', '') }}</td>
                                <td class="text-start">{{ $item['name'] ?? '' }}</td>
                                <td>{{ view_amount((float) ($item['per_furniture_wf'] ?? 0)) }}</td>
                                <td>{{ $item['qty'] ?? 0 }}</td>
                                <td>{{ view_amount((float) ($item['rate'] ?? 0)) }}</td>
                                <td>{{ $item['unit'] ?? '' }}</td>
                                <td>{{ view_amount((float) ($item['amount'] ?? 0)) }}</td>
                                <td>
                                    @if (fmod($gp, 1.0) < 0.0001)
                                        {{ (int) $gp }}
                                    @else
                                        {{ number_format($gp, 2, '.', '') }}
                                    @endif
                                </td>
                                <td>{{ view_amount($ga) }}</td>
                                <td>{{ view_amount((float) ($item['total'] ?? 0)) }}</td>
                            </tr>
                        @endforeach
                        <tr style="font-weight: bold; background: #f5f5f5;">
                            <td class="text-start" colspan="5">Subtotal (this PO: {{ $po['pono'] ?? '' }})</td>
                            <td>{{ $supSubQty }}</td>
                            <td></td>
                            <td></td>
                            <td>{{ view_amount($supSubAmt) }}</td>
                            <td></td>
                            <td>{{ view_amount($supSubGst) }}</td>
                            <td>{{ view_amount($previewTamount) }}</td>
                        </tr>
                        @if ($previewShowRoundOff)
                        <tr style="font-weight: bold; background: #f5f5f5;">
                            <td class="text-start" colspan="5"></td>
                            <td></td>
                            <td></td>
                            <td></td>
                            <td></td>
                            <td><strong>Round Off (₹)</strong></td>
                            <td></td>
                            <td>{{ view_amount($previewRoundOff) }}</td>
                        </tr>
                        <tr style="font-weight: bold; background: #f5f5f5;">
                            <td class="text-start" colspan="5"></td>
                            <td></td>
                            <td></td>
                            <td></td>
                            <td></td>
                            <td><strong>Total (₹)</strong></td>
                            <td></td>
                            <td>{{ view_amount(round($previewTamount + $previewRoundOff, 2)) }}</td>
                        </tr>
                        @else
                        <tr style="font-weight: bold; background: #f5f5f5;">
                            <td class="text-start" colspan="5"></td>
                            <td></td>
                            <td></td>
                            <td></td>
                            <td></td>
                            <td><strong>Total (₹)</strong></td>
                            <td></td>
                            <td>{{ view_amount($previewTamount) }}</td>
                        </tr>
                        @endif
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    @endforeach

    <div class="container" style="margin-top:0.5rem; margin-bottom:1.5rem;">
        <h4 style="text-decoration: underline; font-size:1.15rem; font-weight:700; margin: 0 0 0.5rem 0;">All consumables (this invoice) &mdash; rolled up by item</h4>
        <table class="table table-bordered table-sm" style="max-width:48rem;">
            <thead class="table-light">
                <tr>
                    <th class="text-start">Consumable</th>
                    <th>Unit</th>
                    <th>Total cons. Qty</th>
                    <th>Amount (₹)</th>
                    <th>GST (₹)</th>
                    <th>Total (₹)</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($consumableSummary as $srow)
                    <tr>
                        <td class="text-start">{{ $srow['name'] ?? '' }}</td>
                        <td>{{ $srow['unit'] ?? '' }}</td>
                        <td>{{ $srow['total_qty'] ?? 0 }}</td>
                        <td>{{ view_amount($srow['total_amount'] ?? 0) }}</td>
                        <td>{{ view_amount($srow['total_gst'] ?? 0) }}</td>
                        <td>{{ view_amount($srow['total_line'] ?? 0) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
        <p class="mb-0" style="font-size:1.1rem; font-weight:700; border-top:1px solid #000; padding-top:0.75rem;">
            Grand total (this invoice, ₹): {{ view_amount($poSumTot) }}
        </p>
    </div>

    <div class="container mb-3">
        <div class="row">
            <div class="col">
                <table class="table" width="100%" cellspacing="0">
                    <tr>
                        <td>
                            <h3>DECLARATION</h3>
                            <p>1. Please get one piece approved before production.</p>
                            <p>2. Delivery date MUST be followed.</p>
                            <p>3. Any change in quality &amp; delivery term will have financial implications.</p>
                            <p>4. Payment will be organised using a/c payee cheques only (No Cash).</p>
                            <p>5. All disputes subject to Jaipur, Rajasthan jurisdication.</p>
                        </td>
                        <td style="vertical-align: bottom;">
                            <h3>For Global Vision Direct (P) Ltd</h3>
                            @if (isset($companyDetails) && is_array($fileMap1 ?? null) && $companyDetails->sign_url
                                && array_key_exists($companyDetails->sign_url, $fileMap1))
                                <img height="80" src="{{ $fileMap1[$companyDetails->sign_url] }}" alt="Sign" />
                            @endif
                            <div>___________________________________</div>
                            <div>
                                <p>Authorised Signatory</p>
                            </div>
                        </td>
                    </tr>
                </table>
            </div>
        </div>
    </div>

    <p class="text-center text-muted small" style="margin:1rem 0 2rem;">This is a preview. Accept below to generate hardware month-end purchase orders in the system.</p>

    <hr style="border: 2px dashed #999;" />
@endforeach

    <div class="text-center py-3">
    @if (!empty($previewData))
        <form method="POST" action="{{ route('hardware.monthendpo.create') }}" class="d-inline">
            @csrf
            <input type="hidden" name="invoice_id" value="{{ implode(',', collect($previewData)->pluck('invoice.id')->all()) }}">
            <input type="hidden" name="po_no" value="{{ $poNumeric }}">
            @if (request()->has('supplier_id'))
                <input type="hidden" name="supplier_id" value="{{ request('supplier_id') }}">
            @endif
            <button type="submit" class="btn btn-success btn-lg">Accept &amp; generate hardware POs</button>
        </form>
    @endif
    </div>

    <p class="text-center small text-muted mb-4">This is a computer-generated preview document.</p>
</div>
