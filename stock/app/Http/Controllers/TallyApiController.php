<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\CreditNote;
use App\CreditNoteProduct;
use App\invoice;
use App\buyer;
use App\invoiceTable;
use App\invexport;


use App\RejectRepairPtable;
use App\rejectRepair;
use App\purchaseBill;
use App\purchaseBillConsumable;
use App\PurchaseBillCarton;
use App\purchaseOrder;
use App\purchaseOrderConsumable;
use App\product;
use App\ProductLedger;
use App\supplierProduct;
use App\supplierInvoiceProduct;
use App\poTable;
use App\pocTable;
use App\popTable;
use App\pbTable;
use App\pbTableConsumable;
use App\PbTableCorton;
use App\samplePurchaseBill;
use App\samplePurchaseOrder;
use App\spbTable;
use App\supplier;
use App\consumable;
use Illuminate\Support\Facades\Response;
use Carbon\Carbon;

// https://auratest.shop/api/debit-notes/export?from=2024-06-01&to=2025-02-30

class TallyApiController extends Controller
{
    public function exportCreditNotes(Request $request)
{
    $fromDate = Carbon::parse($request->query('from'))->startOfDay();
    $toDate   = Carbon::parse($request->query('to'))->endOfDay();

    $creditNotes = CreditNote::whereBetween('created_at', [$fromDate, $toDate])->get();
    $exportData  = [];

    foreach ($creditNotes as $note) {
        $products = CreditNoteProduct::where('credit_note_id', $note->id)->get();
        $invoice  = Invoice::find($note->invoice_id);

        $buyer    = Buyer::find(optional($invoice)->buyer_id ?? 0);
        $consinee = Buyer::find(optional($invoice)->consignee_id ?? 0);

        $invoicetype = optional($invoice)->invoicetype ?? null;
        $state       = optional(optional($invoice)->buyer)->state;
        $instate     = ($state === 'Rajasthan');

        // Ledgers per invoice type
        $salesLedger = '';
        $SgstLedger  = 'OUTPUT SGST @';
        $CgstLedger  = 'OUTPUT CGST @';
        $IgstLedger  = 'OUTPUT IGST';
        $voucherType = '';
        $destination = optional($invoice)->destination ?? '';

        if ($invoicetype === 0) {
            $voucherType = 'Export';
            if ((optional($invoice)->exportstatus ?? 0) == 1) {
                $salesLedger = 'Sales of Wooden Furniture Export (LUT)';
                $IgstLedger  = 'IGST on Export';
            } else {
                $salesLedger = 'Sales of Wooden Furniture Export';
                $IgstLedger  = 'IGST on Export';
            }
        } elseif ($invoicetype === 1) {
            $voucherType = 'Local';
            $salesLedger = 'Sales of Wooden Furniture GST ';
        } elseif ($invoicetype === 2) {
            $voucherType = 'Amazon';
            $salesLedger = 'Amazon Sales of Wooden ';
        } elseif ($invoicetype === 3) {
            $voucherType = 'Tata';
            $salesLedger = 'TATA Sales of Wooden ';
        } else {
            $voucherType = 'Credit Note';
        }

        // Helper: numeric 2-decimal (no formatting, no string)
$fmt = fn (float $amt) => round($amt, 2);

        // --- Two-decimal product & tax totals (per-line) ---
        $productTotal = 0.00;
        $taxTotal     = 0.00;
        foreach ($products as $p) {
            $lineAmount   = (float) ($p->amount ?? (($p->quantity ?? 0) * ($p->rate ?? 0)));
            $productTotal += round($lineAmount, 2);
            $taxTotal     += round((float) ($p->tax ?? 0), 2);
        }
        // If you prefer header tax instead of per-line, replace with:
        // $taxTotal = round((float) ($note->total_tax ?? 0), 2);

        // Header voucher total at 2 dp
        $voucherTotal = round((float) ($note->total_amount ?? 0), 2);

        // Roundoff (Tally 2-decimal method): Voucher_Total − (sum(line) + sum(tax))
        $roundOff = round($voucherTotal - ($productTotal + $taxTotal), 2);

        // ---------- Build payload ----------
        $formattedNote = [
            'MasterID'         => 'C' . ($note->id ?? ''),
            'VoucherNumber'    => $note->num ?? '',
            'VoucherDate'      => $note->created_at ? $note->created_at->format('Ymd') : '',
            'Reference'        => $note->num ?? '',
            'ReferenceDate'    => $note->created_at ? $note->created_at->format('Ymd') : '',
            'PartyName'        => $buyer->c_name ?? '',
            'VoucherType'      => 'Credit Note',
            'DeliveryNoteNo'   => '',
            'Voucher_Total'    => $fmt($voucherTotal), // numeric string (2dp)
            'DeliveryNoteDate' => '',
            'DispatchThrough'  => '',
            'Destination'      => $destination,
            'CarrierName'      => '',
            'LRNo'             => optional($invoice)->vechleno ?? '',
            'LRDate'           => '',
            'MotorVehicleNo'   => optional($invoice)->vechleno ?? '',
            'OrderNo'          => optional($invoice)->buyerorderno ?? '',
            'OrderDate' => !empty($invoice->date) ? Carbon::parse($invoice->date)->format('Ymd') : '',
            'TermsOfPayment'   => '',
            'OtherReferences'  => '',
            'TermsOfDelivery'  => '',
            'PlaceOfSupply'    => '',
            'IsInvoice'        => 'Yes',
            'IsDeleted'        => 'No',

            'BuyerName'        => $buyer->c_name ?? '',
            'BuyerAlias'       => $buyer->name ?? '',
            'BuyerGSTIN'       => $buyer->gstno ?? '',
            'GSTRegistrationType' => '',
            'BuyerAddress'     => [
                $buyer->address1 ?? '',
                $buyer->address2 ?? '',
                trim(collect([$buyer->email ?? null, $buyer->phone ?? null])->filter()->implode(', '))
            ],
            'BuyerPinCode'        => $buyer->postcode ?? '',
            'BuyerState'          => $buyer->state ?? '',
            'BuyerCountryName'    => $buyer->country ?? '',
            'BuyerEmail'          => $buyer->email ?? '',
            'BuyerMobile'         => $buyer->phone1 ?? '',

            'ConsigneeName'       => $consinee->c_name ?? '',
            'ConsigneeGSTIN'      => $consinee->gstno ?? '',
            'ConsigneeAddress'    => [
                $consinee->address1 ?? $buyer->address1 ?? '',
                $consinee->address2 ?? $buyer->address2 ?? '',
                trim(implode(', ', array_filter([
                    $consinee->email ?? $buyer->email ?? null,
                    $consinee->phone ?? $buyer->phone ?? null,
                ]))),
            ],
            'ConsigneePinCode'    => $consinee->postcode ?? '',
            'ConsigneeState'      => $consinee->state ?? '',
            'ConsigneeCountryName'=> $consinee->country ?? '',

            'VoucherCostCentre'   => '',
            'Narration'           => '',
            'EWayBillDetails'     => optional($invoice)->ewaybillno ?? '',
            'EInvoiceDetails'     => '',
            'item_total'          => $note->total_quantity ?? 0,

            'InventoryEntries'    => [],
            'ledgerentries'       => [],
            'EWayBillDetails' => []
        ];

        // Inventory entries (INR-only, 2dp numeric strings)
        foreach ($products as $p) {
            $lineBase = round((float) ($p->amount ?? (($p->quantity ?? 0) * ($p->rate ?? 0))), 2);

            $invoiceItem = invoiceTable::where('invoice_id', $note->invoice_id)
                ->where('product_id', $p->product_id)
                ->first();

            $gstSlabForLine = optional($invoiceItem)->gstslab ?? optional(optional($p)->product)->gstslab ?? 0;

            $formattedNote['InventoryEntries'][] = [
                'StockItemName'   => optional(optional($p)->product)->code ?? '',
                'ItemCode'        => '',
                'GroupName'       => optional(optional($p)->product)->subcategory->name ?? '',
                'HSNCode'         => optional(optional($p)->product)->HSN ?? '',
                'Unit'            => 'NOS',
                'GSTRate'         => $gstSlabForLine,
                'CessRate'        => 0,
                'IsDeemedPositive'=> 'Yes',
                'ActualQty'       => optional(optional($p)->product)->quantity ?? 0,
                'BilledQty'       => $p->quantity ?? 0,
                'Rate'            => $p->rate ?? 0,
                'Amount'          => $fmt($lineBase),
                'SalesLedger'     => 'GST sales',
                'BatchAllocations'=> [[
                    'BatchName'   => 'Primary Batch',
                    'GodownName'  => 'Main Location',
                    'ActualQty'   => optional(optional($p)->product)->quantity ?? 0,
                    'BilledQty'   => $p->quantity ?? 0,
                    'Rate'        => $p->rate ?? 0,
                    'Amount'      => $fmt($lineBase),
                ]],
                'AccountingAllocations' => [[
                    'LedgerName'        => ($invoicetype == 0)
                        ? ($salesLedger ?? '')
                        : (($salesLedger ?? '') . $gstSlabForLine . '%'),
                    'Amount'            => $fmt($lineBase),
                    'CategoryAllocation'=> '',
                    'GSTClassification' => ($invoicetype == 0)
                        ? ((optional($invoice)->exportstatus ?? 0) == 0 ? 'Export Taxable' : 'Exports LUT/Bond')
                        : ($instate ? 'Sales Taxable' : 'Interstate Sales Taxable'),
                    'IGST Rate'         => $gstSlabForLine
                ]],
                'StockDescriptions' => [[
                    'Description' => (optional(optional($p)->product)->code ?? '') . '-' . (optional(optional($p)->product)->name ?? '')
                ]]
            ];
        }

        // Party (buyer) ledger
        $formattedNote['ledgerentries'][] = [
            'LedgerName'        => $buyer->c_name ?? '',
            'LedgerAmount'      => $fmt($voucherTotal), // numeric 2dp string
            'IsDeemedPositive'  => 'Yes',                // CN direction (debit to party)
            'IsPartyLedger'     => 'Yes',
            'BillsAllocation'   => '',
            'CategoryAllocation'=> '',
            'LedgerDescription' => '',
            'BillRefType'       => ''
        ];

        // Group GST by slab using invoiceTable mapping (2dp)
        $gstGrouped = [];
        foreach ($products as $item) {
            $invoiceItem = invoiceTable::where('invoice_id', $note->invoice_id)
                ->where('product_id', $item->product_id)
                ->first();

            if ($invoiceItem) {
                $slab = (float) ($invoiceItem->gstslab ?? 0);
                $gstGrouped[$slab] = ($gstGrouped[$slab] ?? 0) + round((float) ($item->tax ?? 0), 2);
            }
        }

        // Push GST ledgers (Credit side in CN)
        if ($instate) {
            foreach ($gstGrouped as $slab => $taxSum) {
                $halfAmt = round($taxSum / 2, 2);
                $halfRt  = $slab / 2;

                $formattedNote['ledgerentries'][] = [
                    'LedgerName'       => "CGST @ {$halfRt}%",
                    'LedgerAmount'     => $fmt($halfAmt),
                    'IsDeemedPositive' => 'No',
                    'IsPartyLedger'    => 'No',
                    'BillsAllocation'  => '',
                    'CategoryAllocation'=> '',
                    'LedgerDescription'=> '',
                    'BillRefType'      => ''
                ];
                $formattedNote['ledgerentries'][] = [
                    'LedgerName'       => "SGST @ {$halfRt}%",
                    'LedgerAmount'     => $fmt($halfAmt),
                    'IsDeemedPositive' => 'No',
                    'IsPartyLedger'    => 'No',
                    'BillsAllocation'  => '',
                    'CategoryAllocation'=> '',
                    'LedgerDescription'=> '',
                    'BillRefType'      => ''
                ];
            }
        } else {
            foreach ($gstGrouped as $slab => $taxSum) {
                $formattedNote['ledgerentries'][] = [
                    'LedgerName'       => "IGST @ {$slab}%",
                    'LedgerAmount'     => $fmt(round($taxSum, 2)),
                    'IsDeemedPositive' => 'No',
                    'IsPartyLedger'    => 'No',
                    'BillsAllocation'  => '',
                    'CategoryAllocation'=> '',
                    'LedgerDescription'=> '',
                    'BillRefType'      => ''
                ];
            }
        }

        // Roundoff ledger (both +/−, skip only if zero at 2dp)
        if (abs($roundOff) >= 0.01) {
            $formattedNote['ledgerentries'][] = [
                'LedgerName'        => 'Roundoff',
                'LedgerAmount'      => $fmt(abs($roundOff)),                 // positive number string
                'IsDeemedPositive'  => 'No',       // Yes=Credit, No=Debit
                'IsPartyLedger'     => 'No',
                'BillsAllocation'   => '',
                'CategoryAllocation'=> '',
                'LedgerDescription' => '',
                'BillRefType'       => ''
            ];
        }

        $formattedNote['EWayBillDetails'][] = [
                    'EWayBillNo'=> $invoice->ewaybillno ?? '',
                    'EWayBillDate'=> $invoice->ewaybilldate ? Carbon::parse($invoice->ewaybilldate)->format('Ymd') : '',
                    'Mode'=>'Road',
                    'Distance'=> $invoice->distance ?? '',
                    'TransporterName'=> $invoice->transportertame ?? '',
                    'TransporterID'=> $invoice->transporterid ?? '',
                    'DocNo'=> $invoice->lr_rr_no ?? '',
                    'DocDate'=> $invoice->docdate ? Carbon::parse($invoice->docdate)->format('Ymd') : '',
                    'VehicleNo'=> $invoice->vehicleno ?? '',
                    'VehicleType'=> 'Regular',
                    'ConsignorAddress1' => "Plot# 1216/2, Mahapura Road,",
                    'ConsignorAddress2'=> "Jaipur - Mumbai National Highway,\nBhankrota, Jaipur, Rajasthan - 08",
                    'ConsignorPinCode'=> '302026',
                    'ConsignorPlace'=> 'Jaipur',
                    'ConsignorActualState'=> 'Rajasthan',
                ];

        $exportData[] = $formattedNote;
    }

    return response()->json(['data' => $exportData]);
}

public function exportDebitNotes(Request $request)
{
    // Get date range from query parameters
    $fromDate = Carbon::parse($request->query('from'))->startOfDay();  // Start of the day
    $toDate   = Carbon::parse($request->query('to'))->endOfDay();

    // Fetch reject repairs that have debit notes within the given date range
    $debitNotes = RejectRepairPtable::whereBetween('created_at', [$fromDate, $toDate])
        ->where('is_debit_note', 1)
        ->get();

    // Initialize an array to store formatted data
    $exportData = [];

    // Helper: return numeric rounded to 2 decimals (no string formatting)
    $fmt = fn (float $amt) => round($amt, 2);

    foreach ($debitNotes as $key => $rejectRepair) {

        $rejRepair     = rejectRepair::find($rejectRepair->reject_repair_id);
        $purchaseBill  = purchaseBill::find($rejectRepair->purchase_bill_id);
        $products      = product::find($rejectRepair->product_id);
        $buyer         = supplier::find(optional($rejRepair)->supplier_id);
        $consinee      = supplier::find($rejectRepair->send_to_supplier);

        if (!$purchaseBill) {
            die('Purchase Bill not found.');
        }

        $pbtable = pbTable::where('product_id', $rejectRepair->product_id)
            ->where('purchasebill_id', $rejectRepair->purchase_bill_id)
            ->first();
        if (!$pbtable) {
            die('Purchase Bill Product not found.');
        }

        $purchaseOrder_id = $purchaseBill->purchaseOrder_id;

        $purchaseOrder = purchaseOrder::where('id',
)->first();

if ($purchaseOrder && $purchaseOrder->id !== 0) {
                    $supplier = supplier::find($purchaseOrder->supplier_id);

            }else {
                    $supplier = supplier::find($purchaseBill->supplier_id);
                }

        $poProduct = poTable::where('product_id', $rejectRepair->product_id)
            ->where('poid', $purchaseOrder_id)
            ->first();
        if (!$poProduct) {
            die('PO Product not found.');
        }

        // ---- Calculations (2-decimal numeric) ----
        $productrate   = (float) ($pbtable->rate ?? 0);
        $quantity      = (float) ($rejectRepair->quantity ?? 0);
        $gstPercentage = (float) ($poProduct->gstslab ?? 0);
        $gstHalf       = $fmt($gstPercentage / 2);

        // Line amount (base)
        $amount     = $fmt($productrate * $quantity);
        // Tax on the line
        $gstamount  = $fmt($amount * ($gstPercentage / 100));
        // Voucher total (header)
        $voucherTotal = $fmt($amount + $gstamount);

        // For roundoff logic (sum over lines; here one line, but scalable)
        $productTotal = $fmt($amount);
        $taxTotal     = $fmt($gstamount);

        // Roundoff = Voucher_Total − (sum(line amounts) + sum(taxes))
        $roundOff = $fmt($voucherTotal - ($productTotal + $taxTotal));

        // ---- Header fields (kept as-is) ----
        $rejRepairSupplierInvNo = strtoupper(optional($rejRepair)->supplier_inv_no ?? '');
        $createdAtYmd = optional($rejectRepair->created_at)?->format('Ymd') ?? '';

        $buyerName     = $buyer->c_name ?? '';
        $buyerAlias    = $buyer->name ?? '';
        $buyerGstin    = $buyer->gstin ?? '';
        $buyerPinCode  = $buyer->postcode ?? '';
        $buyerState    = $buyer->state ?? '';
        $buyerCountry  = $buyer->country ?? '';
        $buyerEmail    = $buyer->email ?? '';
        $buyerMobile   = $buyer->phone1 ?? '';

        $consigneeName    = $consinee->c_name ?? '';
        $consigneeGstin   = $consinee->gstin ?? '';
        $consigneePinCode = $consinee->postcode ?? '';
        $consigneeState   = $consinee->state ?? '';
        $consigneeCountry = $consinee->country ?? '';

        $productName       = $products->name ?? '';
        $groupname         = optional($products->subcategory)->name ?? '';
        $productCode       = $products->code ?? '';
        $productHsn        = $products->HSN ?? '';
        $productLedgerName = optional($products->productLedger)->name ?? '';
        $productQuantity   = $products->quantity ?? 0;
        $gstSlab           = $products->gstslab ?? 0;

        $formattedNote = [
            'MasterID'         => 'D' . $rejectRepair->id,
            'VoucherNumber'    => $rejRepairSupplierInvNo,
            'VoucherDate'      => $createdAtYmd,
            'Reference'        => $rejRepairSupplierInvNo,
            'ReferenceDate'    => $createdAtYmd,
            'PartyName'        => $buyerName,
            'VoucherType'      => 'Debit Note',
            'DeliveryNoteNo'   => '',
            'Voucher_Total'    => $voucherTotal, // numeric 2dp
            'DeliveryNoteDate' => '',
            'DispatchThrough'  => '',
            'Destination'      => '',
            'CarrierName'      => '',
            'LRNo'             => '',
            'LRDate'           => '',
            'MotorVehicleNo'   => '',
            'OrderNo'          => '',
            'OrderDate'        => '',
            'TermsOfPayment'   => '',
            'OtherReferences'  => '',
            'TermsOfDelivery'  => '',
            'PlaceOfSupply'    => '',
            'IsInvoice'        => 'Yes',
            'IsDeleted'        => 'No',

            'BuyerName'        => $buyerName,
            'BuyerAlias'       => $buyerAlias,
            'BuyerGSTIN'       => $buyerGstin,
            'GSTRegistrationType' => '',
            'BuyerAddress'     => [
                $buyer->address1 ?? '',
                $buyer->address2 ?? '',
                trim(collect([$buyer->email ?? null, $buyer->phone1 ?? null])->filter()->implode(', '))
            ],
            'BuyerPinCode'        => $buyerPinCode,
            'BuyerState'          => $buyerState,
            'BuyerCountryName'    => $buyerCountry,
            'BuyerEmail'          => $buyerEmail,
            'BuyerMobile'         => $buyerMobile,

            'ConsigneeName'       => $consigneeName,
            'ConsigneeGSTIN'      => $consigneeGstin,
            'ConsigneeAddress'    => [
                $consinee->address1 ?? $buyer->address1 ?? '',
                $consinee->address2 ?? $buyer->address2 ?? '',
                trim(implode(', ', array_filter([
                    $consinee->email ?? $buyer->email ?? null,
                    $consinee->phone1 ?? $buyer->phone ?? null,
                ]))),
            ],
            'ConsigneePinCode'    => $consigneePinCode,
            'ConsigneeState'      => $consigneeState,
            'ConsigneeCountryName'=> $consigneeCountry,

            'VoucherCostCentre'   => '',
            'Narration'           => '',
            'EWayBillDetails'     => '',
            'EInvoiceDetails'     => '',
            'item_total'          => $quantity ?? 0,

            'InventoryEntries'    => [],
            'ledgerentries'       => [],
            'EWayBillDetails' => []
        ];

        // Populate product details (amounts numeric 2dp)
        $formattedNote['InventoryEntries'][] = [
            'StockItemName'   => $productCode,
            'ItemCode'        => '',
            'GroupName'       => $groupname,
            'HSNCode'         => $productHsn,
            'Unit'            => 'NOS',
            'GSTRate'         => $gstSlab,
            'CessRate'        => 0,
            'IsDeemedPositive'=> 'No',
            'ActualQty'       => $quantity,
            'BilledQty'       => $quantity,
            'Rate'            => $productrate,
            'Amount'          => $amount, // numeric 2dp
            'SalesLedger'     => 'GST Purchase',
            'BatchAllocations'=> [[
                'BatchName'   => 'Primary Batch',
                'GodownName'  => 'Main Location',
                'ActualQty'   => $quantity,
                'BilledQty'   => $quantity,
                'Rate'        => $productrate,
                'Amount'      => $amount, // numeric 2dp
            ]],
            'AccountingAllocations' => [[
                'LedgerName'        => $productLedgerName,
                'Amount'            => $amount, // numeric 2dp
                'CategoryAllocation'=> '',
                'GSTClassification' => 'Purchase Taxable',
                'IGST Rate'         => $gstSlab
            ]],
            'StockDescriptions' => [[
                'Description' => $productCode . '-' . $productName
            ]]
        ];

        // Party ledger (direction kept same as your code)
        $formattedNote['ledgerentries'][] = [
            'LedgerName'        => $buyerName,
            'LedgerAmount'      => $voucherTotal,     // numeric 2dp
            'IsDeemedPositive'  => 'NO',
            'IsPartyLedger'     => 'YES',
            'BillsAllocation'   => '',
            'CategoryAllocation'=> '',
            'LedgerDescription' => '',
            'BillRefType'       => '',
        ];

        // Tax Ledgers (2dp numeric)
        if ($buyerState === 'Rajasthan') {
            $halfTax = $fmt($gstamount / 2);
            $formattedNote['ledgerentries'][] = [
                'LedgerName'        => 'INPUT SGST @ ' . $gstHalf . '%',
                'LedgerAmount'      => $halfTax,
                'IsDeemedPositive'  => 'Yes',
                'IsPartyLedger'     => 'No',
                'BillsAllocation'   => '',
                'CategoryAllocation'=> '',
                'LedgerDescription' => '',
                'BillRefType'       => '',
            ];
            $formattedNote['ledgerentries'][] = [
                'LedgerName'        => 'INPUT CGST @ ' . $gstHalf . '%',
                'LedgerAmount'      => $halfTax,
                'IsDeemedPositive'  => 'Yes',
                'IsPartyLedger'     => 'No',
                'BillsAllocation'   => '',
                'CategoryAllocation'=> '',
                'LedgerDescription' => '',
                'BillRefType'       => '',
            ];
        } else {
            $formattedNote['ledgerentries'][] = [
                'LedgerName'        => 'INPUT IGST @ ' . $gstPercentage . '%',
                'LedgerAmount'      => $gstamount, // numeric 2dp
                'IsDeemedPositive'  => 'Yes',
                'IsPartyLedger'     => 'NO',
                'BillsAllocation'   => '',
                'CategoryAllocation'=> '',
                'LedgerDescription' => '',
                'BillRefType'       => '',
            ];
        }

        // ---- Roundoff Ledger: show for both + / − ; skip only if zero at 2dp ----
        if (abs($roundOff) >= 0.01) {
            $formattedNote['ledgerentries'][] = [
                'LedgerName'        => 'Roundoff',
                'LedgerAmount'      => $fmt(abs($roundOff)),            // numeric positive value
                'IsDeemedPositive'  => 'No',  // Yes = Credit, No = Debit
                'IsPartyLedger'     => 'No',
                'BillsAllocation'   => '',
                'CategoryAllocation'=> '',
                'LedgerDescription' => '',
                'BillRefType'       => '',
            ];
        }


        $formattedNote['EWayBillDetails'][] = [
                    'EWayBillNo'=> $purchaseBill->ewaybill ?? '',
                    'EWayBillDate'=> $purchaseBill->supp_inv_date ? Carbon::parse($purchaseBill->supp_inv_date)->format('Ymd') : '',
                    'Mode'=>'Road',
                    'Distance'=> "",
                    'TransporterName'=> "",
                    'TransporterID'=> "",
                    'DocNo'=> $purchaseBill->supp_inv_no ?? '',
                    'DocDate'=> $purchaseBill->supp_inv_date ? Carbon::parse($purchaseBill->supp_inv_date)->format('Ymd') : '' ,
                    'VehicleNo'=> "",
                    'VehicleType'=> 'Regular',
                    'ConsignorAddress1' => $buyer->address1 ?? '',
                    'ConsignorAddress2'=> $buyer->address2 ?? '',
                    'ConsignorPinCode'=> $buyer->postcode ?? '',
                    'ConsignorPlace'=> $buyer->city ?? '',
                    'ConsignorActualState'=> $buyer->state ?? '',
                ];

        // Push this note
        $exportData[] = $formattedNote;
    }

    // Wrap data in a "data" key and return JSON response
    return response()->json(['data' => $exportData]);
}

    /**
     * Purchase stock accounting ledger name for Tally (embeds the line GST %).
     */
    private function purchaseAccountingLedgerName(string $kind, float $gstSlab): string
    {
        $p = fmod($gstSlab, 1.0) < 0.00001
            ? (string) (int) round($gstSlab, 0)
            : (string) round($gstSlab, 2);

        return 'Purchase - ' . $kind . ' @ ' . $p . '%';
    }

    /**
     * Purchase Round Off: always IsDeemedPositive NO (Tally posts this as Debit only).
     * Only emitted when voucher total exceeds taxable + posted tax + freight so a
     * debit round-off can balance the voucher. Negative gaps are not posted here.
     */
    private function appendPurchaseRoundOffLedger(
        array &$formattedNote,
        float $voucherTotal,
        float $sumLineTaxable,
        float $postedGst,
        float $freight = 0.0
    ): void {
        $roundOff = round($voucherTotal - ($sumLineTaxable + $postedGst + $freight), 2);
        if ($roundOff < 0.01) {
            return;
        }
        $formattedNote['ledgerentries'][] = [
            'LedgerName' => 'Round Off',
            'LedgerAmount' => $roundOff,
            'IsDeemedPositive' => 'NO',
            'IsPartyLedger' => 'NO',
            'BillsAllocation' => '',
            'CategoryAllocation' => '',
            'LedgerDescription' => '',
            'BillRefType' => '',
        ];
    }

       public function exportPurchaseBill(Request $request)
    {

        // Get date range from query parameters
        $fromDate = Carbon::parse($request->query('from'))->startOfDay();  // Start of the day
        $toDate = Carbon::parse($request->query('to'))->endOfDay();

$cutoffDate = Carbon::createFromDate(2025, 10, 29)->endOfDay();
        // Fetch purchase bills within the date range
        $purchaseBills = PurchaseBill::whereBetween('created_at', [$fromDate, $toDate])
        ->where('created_at', '>', $cutoffDate)

        ->where('status', 1)
        ->get();

        // Structure the data for export in the provided format
        $exportData = [];
        foreach ($purchaseBills as $bill) {
            $supplier = null;

            if ($bill->mulitple_po_purchaseBill     == null) {
                $purchaseOrder = purchaseOrder::where('id', $bill->purchaseOrder_id)->first();

                // Check if the purchase order exists and its id is not 0
                if ($purchaseOrder && $purchaseOrder->id !== 0) {
                    $supplier = supplier::find($purchaseOrder->supplier_id);
                }
            }else {
                    $supplier = supplier::find($bill->supplier_id);
                }

            $supplierName = optional($supplier)->c_name ?? '';
            $poNoForNarration = '';
            if (!empty($bill->mulitple_po_purchaseBill)) {
                $poNoForNarration = (string) $bill->mulitple_po_purchaseBill;
            } elseif (!empty($bill->purchaseOrder_id)) {
                $poIdRaw = (string) $bill->purchaseOrder_id;
                if (str_contains($poIdRaw, ',')) {
                    $ids = array_values(array_filter(array_map('trim', explode(',', $poIdRaw))));
                    $poNoForNarration = purchaseOrder::whereIn('id', $ids)->pluck('pono')->filter()->unique()->values()->implode(', ');
                } else {
                    $poNoForNarration = optional(purchaseOrder::where('id', $bill->purchaseOrder_id)->first())->pono ?? '';
                }
            }
            $purchaseBillNarration = 'Goods delivered against GetIn reference no. ' . ($bill->getinserailno ?? '');
            if ($poNoForNarration !== '') {
                $purchaseBillNarration .= ' and PO no. ' . $poNoForNarration;
            }
            $purchaseDetails = pbTable::where('purchaseBill_id', $bill->id)->get();

            $voucherdate = $bill->supp_inv_date ? Carbon::parse($bill->supp_inv_date)->format('Ymd') : '';
$bill_total = $bill->total - ($bill->tdsTotal ?? 0);
            $formattedNote = [
                'MasterID' => 'P' . $bill->id,
                'VoucherNumber' => $bill->supp_inv_no,
                'VoucherDate' => $voucherdate,
                'Reference' => "",
                'ReferenceDate' => "",
                'PartyName' =>  $supplierName,
                'VoucherType' => 'GST Purchase',
                "DeliveryNoteNo" => '',
                'Voucher_Total' => $bill_total,
                "DeliveryNoteDate" => '',
                "DispatchThrough"  => '',
                'Destination' => "",
                "CarrierName" => '',
                "LRNo" => '',
                "LRDate" => '',
                "MotorVehicleNo" => "",
                "OrderNo" => "",
                "OrderDate" => "",
                "TermsOfPayment" => '',
                "OtherReferences" => '',
                "TermsOfDelivery" => '',
                "PlaceOfSupply" => "Rajasthan",
                "IsInvoice" => "YES",
                "IsDeleted" => "NO",
                'BuyerAlias' => $supplierName,
                'BuyerGSTIN' => $supplier->gstin ?? '',
                'GSTRegistrationType' => 'Regular',
                'BuyerAddress' => [
                    $supplier->address1 ?? '',
                    $supplier->address2 ?? '',
                    trim(collect([
                        $supplier->email ?? '',
                        $supplier->phone1 ?? '',
                    ])->filter()->implode(', '))
                ],
                'BuyerState' => $supplier->state ?? '',
                'BuyerCountryName' => $supplier->country ?? '',
                'BuyerEmail' => $supplier->email ?? '',
                'BuyerMobile' => $supplier->phone1 ?? '',
                "ConsigneeName" => 'Global Vision Direct (P) Ltd',
                "ConsigneeGSTIN" => '08AAICG5697G1Z3',
                "ConsigneeAddress" => [
                    'Plot# 1216/2, Mahapura Road,',
                    'Jaipur - Mumbai National Highway, Bhankrota, Jaipur, Rajasthan - 08, Jaipur - 302026, India',
                    trim(collect([
                        'finance@artisanfurniture.net',
                        '+91 98290 19895',
                    ])->filter()->implode(', '))
                ],
                "ConsigneePinCode" => '302026',
                "ConsigneeState" => 'Rajasthan',
                "ConsigneeCountryName" => 'India',
                "VoucherCostCentre" => "",
                "Narration" => $purchaseBillNarration,
                "EWayBillDetails" => "",
                "EInvoiceDetails" => "",
                "item_total" => $bill->quantity ?? 0,


                'InventoryEntries' => [],
                'ledgerentries' => [],
'EWayBillDetails' => []

            ];

            foreach ($purchaseDetails as $product) {
                $productLedgerName = $product->product->product_ledger_id
                    ? ProductLedger::where('id', $product->product->product_ledger_id)->value('name')
                    : 'Purchase - Wooden Furniture Under GST 18%';
                $formattedNote['InventoryEntries'][] = [
                    'StockItemName' => $product->product->code ?? '',
                    'ItemCode' => '',
                    'GroupName' => $product->product->subcategory->name ?? '',
                    'HSNCode' => $product->product->HSN ?? '',
                    'Unit' => "NOS",
                    'GSTRate' => (($g = $product->product->gstslab) === null || $g === '' || $g == 0) ? 18 : $g,
                    'CessRate' => 0,
                    'IsDeemedPositive' => "YES",
                    'ActualQty' => $product->receiveqty ?? 0,
                    'BilledQty' => $product->receiveqty ?? 0,
                    'Rate' => $product->rate ?? 0,
                    'Amount' => $product->amount ?? 0,
                    'SalesLedger' => "GST Purchase",
                    'BatchAllocations' => [
                        [
                            "BatchName" => "Primary Batch",
                            "GodownName" => "Main Location",
                            "ActualQty" => $product->receiveqty ?? 0,
                            "BilledQty" => $product->receiveqty ?? 0,
                            "Rate" => $product->rate ?? 0,
                            "Amount" => $product->amount ?? 0,
                        ]
                    ],
                    'AccountingAllocations' => [
                        [
                            "LedgerName" => $productLedgerName,
                            "Amount" => $product->amount ?? 0,
                            "CategoryAllocation" => "",
                            "GSTClassification" => "Purchase Taxable",
                            "IGST Rate" => (($g = $product->product->gstslab) === null || $g === '' || $g == 0) ? 18 : $g,
                        ]
                    ],
                    'StockDescriptions' => [ // Adding StockDescriptions here
                        [
                            "Description" => ($product->product->code ?? '') . '-' . ($product->product->name ?? ''),
                        ]
                    ]
                ];
            }


            // Add ledger entries (example for tax calculations)
            $formattedNote['ledgerentries'][] = [
                "LedgerName" => $supplierName,
                "LedgerAmount" => $bill_total,
                "IsDeemedPositive" => "NO",
                "IsPartyLedger" => "YES",
                "BillsAllocation" => "",
                "CategoryAllocation" => "",
                "LedgerDescription" => "",
                "BillRefType" => "",
            ];

            $gstSlabAmounts = [];
            $totalWithGST = 0;

            foreach ($purchaseDetails as $product) {
                $gstSlab = $product->product->gstslab ?? null;

    // if slab is empty or zero, default to 18
    if ($gstSlab === null || $gstSlab === '' || $gstSlab == 0) {
        $gstSlab = 18;
    }
                $amount = $product->amount;
                $gstAmount = $gstSlab ? (($amount ?? 0) * ($gstSlab / 100)) : 0;
if ($gstSlab != 0) {
                if (isset($gstSlabAmounts[$gstSlab])) {
                    $gstSlabAmounts[$gstSlab] += $gstAmount;
                } else {
                    $gstSlabAmounts[$gstSlab] = $gstAmount;
                }
                $totalWithGST += ($amount + $gstAmount);
            }  }

            foreach ($gstSlabAmounts as $gstSlab => $totalAmount) {
                //dd($supplier->state);
                if ($supplier && $supplier->state == 'Rajasthan') {
                    $formattedNote['ledgerentries'][] = [
                        'LedgerName' => 'INPUT SGST @ ' . $gstSlab / 2 . '%',
                        'LedgerAmount' => $totalAmount / 2,
                        'IsDeemedPositive' => 'Yes',
                        'IsPartyLedger' => 'NO',
                        'BillsAllocation' => '',
                        'CategoryAllocation' => '',
                        'LedgerDescription' => '',
                        'BillRefType' => '',
                    ];

                    $formattedNote['ledgerentries'][] = [
                        'LedgerName' => 'INPUT CGST @ ' . $gstSlab / 2 . '%',
                        'LedgerAmount' => $totalAmount / 2,
                        'IsDeemedPositive' => 'Yes',
                        'IsPartyLedger' => 'NO',
                        'BillsAllocation' => '',
                        'CategoryAllocation' => '',
                        'LedgerDescription' => '',
                        'BillRefType' => '',
                    ];
                } else {

                    $formattedNote['ledgerentries'][] = [
                        'LedgerName' => 'INPUT IGST @ ' . $gstSlab . '%',
                        'LedgerAmount' => $totalAmount,
                        'IsDeemedPositive' => 'Yes',
                        'IsPartyLedger' => 'NO',
                        'BillsAllocation' => '',
                        'CategoryAllocation' => '',
                        'LedgerDescription' => '',
                        'BillRefType' => '',
                    ];
                }
            }

            if (!empty(optional($supplier)->tdsledger)) {
    $formattedNote['ledgerentries'][] = [
        "LedgerName"        => $supplier->tdsledger,
        "LedgerAmount"      => -$bill->tdsTotal ?? 0,
        "IsDeemedPositive"  => "Yes",
        "IsPartyLedger"     => "No",
        "BillsAllocation"   => "",
        "CategoryAllocation"=> "",
        "LedgerDescription" => "",
        "BillRefType"       => ""
    ];
}

$diff = $bill->total - $totalWithGST;
if (round($diff, 2) != 0) {
            $formattedNote['ledgerentries'][] = [
                "LedgerName" => "Round Off",
                // Purchase voucher: party/TDS are Cr; negate so round-up lands on Dr (unlike sales)
                "LedgerAmount" => -round($diff, 2),
                "IsDeemedPositive" => "NO",
                "IsPartyLedger" => "NO",
                "BillsAllocation" => "",
                "CategoryAllocation" => "",
                "LedgerDescription" => "",
                "BillRefType" => ""
            ];
        }

$formattedNote['EWayBillDetails'][] = [
                    'EWayBillNo'=> $bill->ewaybill ?? '',
                    'EWayBillDate'=> $voucherdate ?? '',
                    'Mode'=>'Road',
                    'Distance'=> "",
                    'TransporterName'=> "",
                    'TransporterID'=> "",
                    'DocNo'=> $bill->supp_inv_no ?? '',
                    'DocDate'=> $voucherdate ,
                    'VehicleNo'=> "",
                    'VehicleType'=> 'Regular',
                    'ConsignorAddress1' => $supplier->address1 ?? '',
                    'ConsignorAddress2'=> $supplier->address2 ?? '',
                    'ConsignorPinCode'=> $supplier->postcode ?? '',
                    'ConsignorPlace'=> $supplier->city ?? '',
                    'ConsignorActualState'=> $supplier->state ?? '',
                ];

            $exportData[] = $formattedNote;
        }

        /*
         * Append Consumables purchase bills export (separate tables).
         * NOTE: Furniture export above remains unchanged by design.
         */
        $consumableBills = purchaseBillConsumable::whereBetween('created_at', [$fromDate, $toDate])
            ->where('created_at', '>', $cutoffDate)
            ->whereNull('swap_id')
            ->where('is_checked', 1)
            ->get();

        foreach ($consumableBills as $bill) {
            $purchaseOrder = purchaseOrderConsumable::where('id', $bill->purchaseOrder_id)->first();
            $supplier = $purchaseOrder ? supplier::find($purchaseOrder->supplier_id) : null;
            $supplierName = optional($supplier)->c_name ?? '';
            $poNoForNarration = optional($purchaseOrder)->pono ?? '';
            $consumableBillNarration = $poNoForNarration !== ''
                ? 'Goods delivered against PO no. ' . $poNoForNarration
                : '';

            $purchaseDetails = pbTableConsumable::where('purchaseBill_id', $bill->id)->get();
            $voucherdate = $bill->supp_inv_date ? Carbon::parse($bill->supp_inv_date)->format('Ymd') : '';
            $bill_total = $bill->total - ($bill->tdsTotal ?? 0);

            $formattedNote = [
                'MasterID' => 'PC' . $bill->id,
                'VoucherNumber' => $bill->supp_inv_no,
                'VoucherDate' => $voucherdate,
                'Reference' => "",
                'ReferenceDate' => "",
                'PartyName' =>  $supplierName,
                'VoucherType' => 'GST Purchase',
                "DeliveryNoteNo" => '',
                'Voucher_Total' => $bill_total,
                "DeliveryNoteDate" => '',
                "DispatchThrough"  => '',
                'Destination' => "",
                "CarrierName" => '',
                "LRNo" => '',
                "LRDate" => '',
                "MotorVehicleNo" => "",
                "OrderNo" => "",
                "OrderDate" => "",
                "TermsOfPayment" => '',
                "OtherReferences" => '',
                "TermsOfDelivery" => '',
                "PlaceOfSupply" => "Rajasthan",
                "IsInvoice" => "YES",
                "IsDeleted" => "NO",
                'BuyerAlias' => $supplierName,
                'BuyerGSTIN' => optional($supplier)->gstin ?? '',
                'GSTRegistrationType' => 'Regular',
                'BuyerAddress' => [
                    optional($supplier)->address1 ?? '',
                    optional($supplier)->address2 ?? '',
                    trim(collect([
                        optional($supplier)->email ?? '',
                        optional($supplier)->phone1 ?? '',
                    ])->filter()->implode(', '))
                ],
                'BuyerState' => optional($supplier)->state ?? '',
                'BuyerCountryName' => optional($supplier)->country ?? '',
                'BuyerEmail' => optional($supplier)->email ?? '',
                'BuyerMobile' => optional($supplier)->phone1 ?? '',
                "ConsigneeName" => 'Global Vision Direct (P) Ltd',
                "ConsigneeGSTIN" => '08AAICG5697G1Z3',
                "ConsigneeAddress" => [
                    'Plot# 1216/2, Mahapura Road,',
                    'Jaipur - Mumbai National Highway, Bhankrota, Jaipur, Rajasthan - 08, Jaipur - 302026, India',
                    trim(collect([
                        'finance@artisanfurniture.net',
                        '+91 98290 19895',
                    ])->filter()->implode(', '))
                ],
                "ConsigneePinCode" => '302026',
                "ConsigneeState" => 'Rajasthan',
                "ConsigneeCountryName" => 'India',
                "VoucherCostCentre" => "",
                "Narration" => $consumableBillNarration,
                "EWayBillDetails" => "",
                "EInvoiceDetails" => "",
                "item_total" => $bill->quantity ?? 0,
                'InventoryEntries' => [],
                'ledgerentries' => [],
                'EWayBillDetails' => []
            ];

            $gstSlabAmounts = [];
            $totalWithGST = 0.0;
            $sumLineTaxable = 0.0;

            foreach ($purchaseDetails as $row) {
                $c = $row->consumable;
                $codeOrName = trim((string) (optional($c)->SKU ?? ''));
                if ($codeOrName === '') {
                    $codeOrName = (string) (optional($c)->name ?? '');
                }
                $gstSlab = 0.0;
                if (!empty($row->purchaseOrder_id) && !empty($row->product_id)) {
                    $poLine = pocTable::where('poid', $row->purchaseOrder_id)
                        ->where('consumable_id', $row->product_id)
                        ->first();
                    if ($poLine) {
                        $gstSlab = (float) ($poLine->gstslab ?? 0);
                    }
                }
                if ($gstSlab <= 0) {
                    $gstSlab = 18;
                }
                $amount = (float) ($row->amount ?? 0);
                $sumLineTaxable += $amount;
                $gstAmount = $gstSlab ? (($amount ?? 0) * ($gstSlab / 100)) : 0;
                if ($gstSlab != 0) {
                    $gstSlabAmounts[$gstSlab] = ($gstSlabAmounts[$gstSlab] ?? 0) + $gstAmount;
                    $totalWithGST += ($amount + $gstAmount);
                }

                $formattedNote['InventoryEntries'][] = [
                    'StockItemName' => $codeOrName,
                    'ItemCode' => '',
                    'GroupName' => '',
                    'HSNCode' => '',
                    'Unit' => strtoupper((string) ($row->unit ?? 'NOS')),
                    'GSTRate' => $gstSlab,
                    'CessRate' => 0,
                    'IsDeemedPositive' => "YES",
                    'ActualQty' => $row->receiveqty ?? 0,
                    'BilledQty' => $row->receiveqty ?? 0,
                    'Rate' => $row->rate ?? 0,
                    'Amount' => $row->amount ?? 0,
                    'SalesLedger' => "GST Purchase",
                    'BatchAllocations' => [[
                        "BatchName" => "Primary Batch",
                        "GodownName" => "Main Location",
                        "ActualQty" => $row->receiveqty ?? 0,
                        "BilledQty" => $row->receiveqty ?? 0,
                        "Rate" => $row->rate ?? 0,
                        "Amount" => $row->amount ?? 0,
                    ]],
                    'AccountingAllocations' => [[
                        'LedgerName' => $this->purchaseAccountingLedgerName('Consumable', $gstSlab),
                        "Amount" => $row->amount ?? 0,
                        "CategoryAllocation" => "",
                        "GSTClassification" => "Purchase Taxable",
                        "IGST Rate" => $gstSlab,
                    ]],
                    'StockDescriptions' => [[
                        "Description" => trim($codeOrName . '-' . (string) (optional($c)->name ?? $codeOrName)),
                    ]]
                ];
            }

            $formattedNote['ledgerentries'][] = [
                "LedgerName" => $supplierName,
                "LedgerAmount" => $bill_total,
                "IsDeemedPositive" => "NO",
                "IsPartyLedger" => "YES",
                "BillsAllocation" => "",
                "CategoryAllocation" => "",
                "LedgerDescription" => "",
                "BillRefType" => "",
            ];

            $freightR = round((float) ($bill->freight ?? 0), 2);
            $dbGst = round((float) ($bill->gst ?? 0), 2);
            $oneSlabOnly = count($gstSlabAmounts) === 1;
            $postedGst = 0.0;

            if ($oneSlabOnly && $dbGst > 0) {
                $gstSlab = (float) array_key_first($gstSlabAmounts);
                if ($supplier && $supplier->state == 'Rajasthan') {
                    $sgstIn = round($dbGst / 2, 2);
                    $cgstIn = $sgstIn;
                    $postedGst = $sgstIn + $cgstIn;
                    $formattedNote['ledgerentries'][] = [
                        'LedgerName' => 'INPUT SGST @ ' . ($gstSlab / 2) . '%',
                        'LedgerAmount' => $sgstIn,
                        'IsDeemedPositive' => 'Yes',
                        'IsPartyLedger' => 'NO',
                        'BillsAllocation' => '',
                        'CategoryAllocation' => '',
                        'LedgerDescription' => '',
                        'BillRefType' => '',
                    ];
                    $formattedNote['ledgerentries'][] = [
                        'LedgerName' => 'INPUT CGST @ ' . ($gstSlab / 2) . '%',
                        'LedgerAmount' => $cgstIn,
                        'IsDeemedPositive' => 'Yes',
                        'IsPartyLedger' => 'NO',
                        'BillsAllocation' => '',
                        'CategoryAllocation' => '',
                        'LedgerDescription' => '',
                        'BillRefType' => '',
                    ];
                } else {
                    $postedGst = $dbGst;
                    $formattedNote['ledgerentries'][] = [
                        'LedgerName' => 'INPUT IGST @ ' . $gstSlab . '%',
                        'LedgerAmount' => $dbGst,
                        'IsDeemedPositive' => 'Yes',
                        'IsPartyLedger' => 'NO',
                        'BillsAllocation' => '',
                        'CategoryAllocation' => '',
                        'LedgerDescription' => '',
                        'BillRefType' => '',
                    ];
                }
            } else {
                foreach ($gstSlabAmounts as $gstSlab => $totalAmount) {
                    $lineGstTotal = round($totalAmount, 2);
                    if ($supplier && $supplier->state == 'Rajasthan') {
                        $sgstIn = round($lineGstTotal / 2, 2);
                        $cgstIn = $sgstIn;
                        $postedGst += ($sgstIn + $cgstIn);
                        $formattedNote['ledgerentries'][] = [
                            'LedgerName' => 'INPUT SGST @ ' . ($gstSlab / 2) . '%',
                            'LedgerAmount' => $sgstIn,
                            'IsDeemedPositive' => 'Yes',
                            'IsPartyLedger' => 'NO',
                            'BillsAllocation' => '',
                            'CategoryAllocation' => '',
                            'LedgerDescription' => '',
                            'BillRefType' => '',
                        ];
                        $formattedNote['ledgerentries'][] = [
                            'LedgerName' => 'INPUT CGST @ ' . ($gstSlab / 2) . '%',
                            'LedgerAmount' => $cgstIn,
                            'IsDeemedPositive' => 'Yes',
                            'IsPartyLedger' => 'NO',
                            'BillsAllocation' => '',
                            'CategoryAllocation' => '',
                            'LedgerDescription' => '',
                            'BillRefType' => '',
                        ];
                    } else {
                        $postedGst += $lineGstTotal;
                        $formattedNote['ledgerentries'][] = [
                            'LedgerName' => 'INPUT IGST @ ' . $gstSlab . '%',
                            'LedgerAmount' => $lineGstTotal,
                            'IsDeemedPositive' => 'Yes',
                            'IsPartyLedger' => 'NO',
                            'BillsAllocation' => '',
                            'CategoryAllocation' => '',
                            'LedgerDescription' => '',
                            'BillRefType' => '',
                        ];
                    }
                }
            }

            if (!empty(optional($supplier)->tdsledger)) {
                $formattedNote['ledgerentries'][] = [
                    "LedgerName"        => $supplier->tdsledger,
                    "LedgerAmount"      => -($bill->tdsTotal ?? 0),
                    "IsDeemedPositive"  => "Yes",
                    "IsPartyLedger"     => "No",
                    "BillsAllocation"   => "",
                    "CategoryAllocation"=> "",
                    "LedgerDescription" => "",
                    "BillRefType"       => ""
                ];
            }

            $this->appendPurchaseRoundOffLedger(
                $formattedNote,
                (float) $bill_total,
                $sumLineTaxable,
                $postedGst,
                $freightR
            );

            $formattedNote['EWayBillDetails'][] = [
                'EWayBillNo'=> $bill->ewaybill ?? '',
                'EWayBillDate'=> $voucherdate ?? '',
                'Mode'=>'Road',
                'Distance'=> "",
                'TransporterName'=> "",
                'TransporterID'=> "",
                'DocNo'=> $bill->supp_inv_no ?? '',
                'DocDate'=> $voucherdate,
                'VehicleNo'=> "",
                'VehicleType'=> 'Regular',
                'ConsignorAddress1' => optional($supplier)->address1 ?? '',
                'ConsignorAddress2'=> optional($supplier)->address2 ?? '',
                'ConsignorPinCode'=> optional($supplier)->postcode ?? '',
                'ConsignorPlace'=> optional($supplier)->city ?? '',
                'ConsignorActualState'=> optional($supplier)->state ?? '',
            ];

            $exportData[] = $formattedNote;
        }

        /*
         * Append Cartons purchase bills export: Main/Leg, rate × qty line amounts. Single slab + header `gst`:
         * CGST/SGST use equal 2-dp halves (round); any paise left vs header gst goes to Round Off; IGST = full `gst`.
         */
        $cartonBills = PurchaseBillCarton::whereBetween('created_at', [$fromDate, $toDate])
            ->where('created_at', '>', $cutoffDate)
            ->whereNull('swap_id')
            ->where('is_checked', 1)
            ->get();

        foreach ($cartonBills as $bill) {
            $supplier = null;
            if (!empty($bill->supplier_id)) {
                $supplier = supplier::find($bill->supplier_id);
            }
            if (!$supplier) {
                $purchaseOrder = purchaseOrderConsumable::where('id', $bill->purchaseOrder_id)->first();
                $supplier = $purchaseOrder ? supplier::find($purchaseOrder->supplier_id) : null;
            }
            $supplierName = optional($supplier)->c_name ?? '';
            $poNoForNarration = optional(purchaseOrderConsumable::where('id', $bill->purchaseOrder_id)->first())->pono ?? '';
            $cartonBillNarration = $poNoForNarration !== ''
                ? 'Goods delivered against PO no. ' . $poNoForNarration
                : '';

            $purchaseDetails = PbTableCorton::where('purchaseBill_id', $bill->id)->get();
            $voucherdate = $bill->supp_inv_date ? Carbon::parse($bill->supp_inv_date)->format('Ymd') : '';
            $bill_total = $bill->total - ($bill->tdsTotal ?? 0);

            $formattedNote = [
                'MasterID' => 'PK' . $bill->id,
                'VoucherNumber' => $bill->supp_inv_no,
                'VoucherDate' => $voucherdate,
                'Reference' => "",
                'ReferenceDate' => "",
                'PartyName' =>  $supplierName,
                'VoucherType' => 'GST Purchase',
                "DeliveryNoteNo" => '',
                'Voucher_Total' => $bill_total,
                "DeliveryNoteDate" => '',
                "DispatchThrough"  => '',
                'Destination' => "",
                "CarrierName" => '',
                "LRNo" => '',
                "LRDate" => '',
                "MotorVehicleNo" => "",
                "OrderNo" => "",
                "OrderDate" => "",
                "TermsOfPayment" => '',
                "OtherReferences" => '',
                "TermsOfDelivery" => '',
                "PlaceOfSupply" => "Rajasthan",
                "IsInvoice" => "YES",
                "IsDeleted" => "NO",
                'BuyerAlias' => $supplierName,
                'BuyerGSTIN' => optional($supplier)->gstin ?? '',
                'GSTRegistrationType' => 'Regular',
                'BuyerAddress' => [
                    optional($supplier)->address1 ?? '',
                    optional($supplier)->address2 ?? '',
                    trim(collect([
                        optional($supplier)->email ?? '',
                        optional($supplier)->phone1 ?? '',
                    ])->filter()->implode(', '))
                ],
                'BuyerState' => optional($supplier)->state ?? '',
                'BuyerCountryName' => optional($supplier)->country ?? '',
                'BuyerEmail' => optional($supplier)->email ?? '',
                'BuyerMobile' => optional($supplier)->phone1 ?? '',
                "ConsigneeName" => 'Global Vision Direct (P) Ltd',
                "ConsigneeGSTIN" => '08AAICG5697G1Z3',
                "ConsigneeAddress" => [
                    'Plot# 1216/2, Mahapura Road,',
                    'Jaipur - Mumbai National Highway, Bhankrota, Jaipur, Rajasthan - 08, Jaipur - 302026, India',
                    trim(collect([
                        'finance@artisanfurniture.net',
                        '+91 98290 19895',
                    ])->filter()->implode(', '))
                ],
                "ConsigneePinCode" => '302026',
                "ConsigneeState" => 'Rajasthan',
                "ConsigneeCountryName" => 'India',
                "VoucherCostCentre" => "",
                "Narration" => $cartonBillNarration,
                "EWayBillDetails" => "",
                "EInvoiceDetails" => "",
                "item_total" => $bill->quantity ?? 0,
                'InventoryEntries' => [],
                'ledgerentries' => [],
                'EWayBillDetails' => []
            ];

            $gstSlabAmounts = [];
            $totalWithGST = 0.0;
            $sumLineTaxable = 0.0;

            $cartonRateKey = static function ($rate): string {
                return number_format(round((float) $rate, 4), 4, '.', '');
            };

            $mainBuckets = [];
            $legBuckets = [];

            foreach ($purchaseDetails as $row) {
                $gstSlab = 0.0;
                if (!empty($row->purchaseOrder_id) && !empty($row->product_id)) {
                    $poLine = popTable::where('poid', $row->purchaseOrder_id)
                        ->where('product_id', $row->product_id)
                        ->first();
                    if ($poLine) {
                        $gstSlab = (float) ($poLine->gstslab ?? 0);
                    }
                }
                if ($gstSlab <= 0) {
                    $gstSlab = 12;
                }

                $p = $row->product;
                $r1 = (float) ($row->rate ?? 0);
                $q1 = (float) ($row->receiveqty ?? 0);
                if (abs($q1) > 0.00001 && abs($r1) > 0.00001) {
                    $k = $cartonRateKey($r1) . '|' . $gstSlab;
                    if (!isset($mainBuckets[$k])) {
                        $mainBuckets[$k] = [
                            'qty' => 0.0,
                            'rate' => $r1,
                            'gstSlab' => $gstSlab,
                            'product' => $p,
                        ];
                    }
                    $mainBuckets[$k]['qty'] += $q1;
                }

                $r2 = (float) ($row->rate2 ?? 0);
                $q2 = (float) ($row->receiveqty2 ?? 0);
                if (abs($q2) > 0.00001 && abs($r2) > 0.00001) {
                    $k2 = $cartonRateKey($r2) . '|' . $gstSlab;
                    if (!isset($legBuckets[$k2])) {
                        $legBuckets[$k2] = [
                            'qty' => 0.0,
                            'rate' => $r2,
                            'gstSlab' => $gstSlab,
                            'product' => $p,
                        ];
                    }
                    $legBuckets[$k2]['qty'] += $q2;
                }
            }

            foreach ([[$mainBuckets, 'Main Carton'], [$legBuckets, 'Leg Carton']] as $bucketPair) {
                [$buckets, $stockItemName] = $bucketPair;
                foreach ($buckets as $bucket) {
                    $qty = (float) ($bucket['qty'] ?? 0);
                    if (round($qty, 4) == 0.0) {
                        continue;
                    }
                    $rate = (float) ($bucket['rate'] ?? 0);
                    $gstSlab = (float) ($bucket['gstSlab'] ?? 0);
                    if ($gstSlab <= 0) {
                        $gstSlab = 12;
                    }
                    $p = $bucket['product'] ?? null;
                    $amount = round($rate * $qty, 2);
                    $sumLineTaxable += $amount;
                    $gstAmount = $gstSlab ? ($amount * ($gstSlab / 100)) : 0;
                    if ($gstSlab != 0) {
                        $gstSlabAmounts[$gstSlab] = ($gstSlabAmounts[$gstSlab] ?? 0) + $gstAmount;
                        $totalWithGST += ($amount + $gstAmount);
                    }

                    $formattedNote['InventoryEntries'][] = [
                        'StockItemName' => $stockItemName,
                        'ItemCode' => '',
                        'GroupName' => optional(optional($p)->subcategory)->name ?? '',
                        'HSNCode' => optional($p)->HSN ?? '',
                        'Unit' => 'NOS',
                        'GSTRate' => $gstSlab,
                        'CessRate' => 0,
                        'IsDeemedPositive' => 'YES',
                        'ActualQty' => $qty,
                        'BilledQty' => $qty,
                        'Rate' => $rate,
                        'Amount' => $amount,
                        'SalesLedger' => 'GST Purchase',
                        'BatchAllocations' => [[
                            'BatchName' => 'Primary Batch',
                            'GodownName' => 'Main Location',
                            'ActualQty' => $qty,
                            'BilledQty' => $qty,
                            'Rate' => $rate,
                            'Amount' => $amount,
                        ]],
                        'AccountingAllocations' => [[
                            'LedgerName' => $this->purchaseAccountingLedgerName('Carton', $gstSlab),
                            'Amount' => $amount,
                            'CategoryAllocation' => '',
                            'GSTClassification' => 'Purchase Taxable',
                            'IGST Rate' => $gstSlab,
                        ]],
                        'StockDescriptions' => [[
                            'Description' => $stockItemName . ' @ ' . (string) $rate,
                        ]],
                    ];
                }
            }

            $formattedNote['ledgerentries'][] = [
                "LedgerName" => $supplierName,
                "LedgerAmount" => $bill_total,
                "IsDeemedPositive" => "NO",
                "IsPartyLedger" => "YES",
                "BillsAllocation" => "",
                "CategoryAllocation" => "",
                "LedgerDescription" => "",
                "BillRefType" => "",
            ];

            $freightR = round((float) ($bill->freight ?? 0), 2);
            $dbGst = round((float) ($bill->gst ?? 0), 2);
            $oneSlabOnly = count($gstSlabAmounts) === 1;
            $postedGst = 0.0;
            if ($oneSlabOnly && $dbGst > 0) {
                $gstSlab = (float) array_key_first($gstSlabAmounts);
                if ($supplier && $supplier->state == 'Rajasthan') {
                    $perHalf = round($dbGst / 2, 2);
                    $sgstIn = $perHalf;
                    $cgstIn = $perHalf;
                    $postedGst = $sgstIn + $cgstIn;
                    $formattedNote['ledgerentries'][] = [
                        'LedgerName' => 'INPUT SGST @ ' . ($gstSlab / 2) . '%',
                        'LedgerAmount' => $sgstIn,
                        'IsDeemedPositive' => 'Yes',
                        'IsPartyLedger' => 'NO',
                        'BillsAllocation' => '',
                        'CategoryAllocation' => '',
                        'LedgerDescription' => '',
                        'BillRefType' => '',
                    ];
                    $formattedNote['ledgerentries'][] = [
                        'LedgerName' => 'INPUT CGST @ ' . ($gstSlab / 2) . '%',
                        'LedgerAmount' => $cgstIn,
                        'IsDeemedPositive' => 'Yes',
                        'IsPartyLedger' => 'NO',
                        'BillsAllocation' => '',
                        'CategoryAllocation' => '',
                        'LedgerDescription' => '',
                        'BillRefType' => '',
                    ];
                    $totalWithGST = round($sumLineTaxable + $sgstIn + $cgstIn + $freightR, 2);
                } else {
                    $postedGst = $dbGst;
                    $formattedNote['ledgerentries'][] = [
                        'LedgerName' => 'INPUT IGST @ ' . $gstSlab . '%',
                        'LedgerAmount' => $dbGst,
                        'IsDeemedPositive' => 'Yes',
                        'IsPartyLedger' => 'NO',
                        'BillsAllocation' => '',
                        'CategoryAllocation' => '',
                        'LedgerDescription' => '',
                        'BillRefType' => '',
                    ];
                    $totalWithGST = round($sumLineTaxable + $dbGst + $freightR, 2);
                }
            } else {
                foreach ($gstSlabAmounts as $gstSlab => $totalAmount) {
                    if ($supplier && $supplier->state == 'Rajasthan') {
                        $perHalf = round($totalAmount / 2, 2);
                        $postedGst += ($perHalf * 2);
                        $sgstIn = $perHalf;
                        $cgstIn = $perHalf;
                        $formattedNote['ledgerentries'][] = [
                            'LedgerName' => 'INPUT SGST @ ' . ($gstSlab / 2) . '%',
                            'LedgerAmount' => $sgstIn,
                            'IsDeemedPositive' => 'Yes',
                            'IsPartyLedger' => 'NO',
                            'BillsAllocation' => '',
                            'CategoryAllocation' => '',
                            'LedgerDescription' => '',
                            'BillRefType' => '',
                        ];
                        $formattedNote['ledgerentries'][] = [
                            'LedgerName' => 'INPUT CGST @ ' . ($gstSlab / 2) . '%',
                            'LedgerAmount' => $cgstIn,
                            'IsDeemedPositive' => 'Yes',
                            'IsPartyLedger' => 'NO',
                            'BillsAllocation' => '',
                            'CategoryAllocation' => '',
                            'LedgerDescription' => '',
                            'BillRefType' => '',
                        ];
                    } else {
                        $formattedNote['ledgerentries'][] = [
                            'LedgerName' => 'INPUT IGST @ ' . $gstSlab . '%',
                            'LedgerAmount' => round($totalAmount, 2),
                            'IsDeemedPositive' => 'Yes',
                            'IsPartyLedger' => 'NO',
                            'BillsAllocation' => '',
                            'CategoryAllocation' => '',
                            'LedgerDescription' => '',
                            'BillRefType' => '',
                        ];
                    }
                }
            }

            if (!empty(optional($supplier)->tdsledger)) {
                $formattedNote['ledgerentries'][] = [
                    "LedgerName"        => $supplier->tdsledger,
                    "LedgerAmount"      => -($bill->tdsTotal ?? 0),
                    "IsDeemedPositive"  => "Yes",
                    "IsPartyLedger"     => "No",
                    "BillsAllocation"   => "",
                    "CategoryAllocation"=> "",
                    "LedgerDescription" => "",
                    "BillRefType"       => ""
                ];
            }

            $this->appendPurchaseRoundOffLedger(
                $formattedNote,
                (float) $bill_total,
                $sumLineTaxable,
                $postedGst,
                $freightR
            );

            $formattedNote['EWayBillDetails'][] = [
                'EWayBillNo'=> $bill->ewaybill ?? '',
                'EWayBillDate'=> $voucherdate ?? '',
                'Mode'=>'Road',
                'Distance'=> "",
                'TransporterName'=> "",
                'TransporterID'=> "",
                'DocNo'=> $bill->supp_inv_no ?? '',
                'DocDate'=> $voucherdate,
                'VehicleNo'=> "",
                'VehicleType'=> 'Regular',
                'ConsignorAddress1' => optional($supplier)->address1 ?? '',
                'ConsignorAddress2'=> optional($supplier)->address2 ?? '',
                'ConsignorPinCode'=> optional($supplier)->postcode ?? '',
                'ConsignorPlace'=> optional($supplier)->city ?? '',
                'ConsignorActualState'=> optional($supplier)->state ?? '',
            ];

            $exportData[] = $formattedNote;
        }

        /*
         * Append Sample purchase bills (sample_purchase_bills / sample_pb_table).
         * Stock and tax fields come from the sample master only (no furniture product / swap / is_checked filters).
         */
        $samplePurchaseBills = samplePurchaseBill::whereBetween('created_at', [$fromDate, $toDate])
            ->where('created_at', '>', $cutoffDate)
            ->get();

        foreach ($samplePurchaseBills as $bill) {
            $samplePo = samplePurchaseOrder::where('id', $bill->purchaseOrder_id)->first();
            $supplier = $samplePo ? supplier::find($samplePo->supplier_id) : null;
            $supplierName = optional($supplier)->c_name ?? '';
            $poNoForNarration = optional($samplePo)->pono ?? '';
            $sampleBillNarration = $poNoForNarration !== ''
                ? 'Goods delivered against PO no. ' . $poNoForNarration
                : '';

            $purchaseDetails = spbTable::where('sample_purchasebill_id', $bill->id)
                ->with('sample.subcategory')
                ->get();
            $voucherdate = $bill->supp_inv_date ? Carbon::parse($bill->supp_inv_date)->format('Ymd') : '';
            $bill_total = (float) ($bill->total ?? 0) - (float) ($bill->tdsTotal ?? 0);

            $formattedNote = [
                'MasterID' => 'PS' . $bill->id,
                'VoucherNumber' => $bill->supp_inv_no,
                'VoucherDate' => $voucherdate,
                'Reference' => '',
                'ReferenceDate' => '',
                'PartyName' => $supplierName,
                'VoucherType' => 'GST Purchase',
                'DeliveryNoteNo' => '',
                'Voucher_Total' => $bill_total,
                'DeliveryNoteDate' => '',
                'DispatchThrough' => '',
                'Destination' => '',
                'CarrierName' => '',
                'LRNo' => '',
                'LRDate' => '',
                'MotorVehicleNo' => '',
                'OrderNo' => '',
                'OrderDate' => '',
                'TermsOfPayment' => '',
                'OtherReferences' => '',
                'TermsOfDelivery' => '',
                'PlaceOfSupply' => 'Rajasthan',
                'IsInvoice' => 'YES',
                'IsDeleted' => 'NO',
                'BuyerAlias' => $supplierName,
                'BuyerGSTIN' => optional($supplier)->gstin ?? '',
                'GSTRegistrationType' => 'Regular',
                'BuyerAddress' => [
                    optional($supplier)->address1 ?? '',
                    optional($supplier)->address2 ?? '',
                    trim(collect([
                        optional($supplier)->email ?? '',
                        optional($supplier)->phone1 ?? '',
                    ])->filter()->implode(', ')),
                ],
                'BuyerState' => optional($supplier)->state ?? '',
                'BuyerCountryName' => optional($supplier)->country ?? '',
                'BuyerEmail' => optional($supplier)->email ?? '',
                'BuyerMobile' => optional($supplier)->phone1 ?? '',
                'ConsigneeName' => 'Global Vision Direct (P) Ltd',
                'ConsigneeGSTIN' => '08AAICG5697G1Z3',
                'ConsigneeAddress' => [
                    'Plot# 1216/2, Mahapura Road,',
                    'Jaipur - Mumbai National Highway, Bhankrota, Jaipur, Rajasthan - 08, Jaipur - 302026, India',
                    trim(collect([
                        'finance@artisanfurniture.net',
                        '+91 98290 19895',
                    ])->filter()->implode(', ')),
                ],
                'ConsigneePinCode' => '302026',
                'ConsigneeState' => 'Rajasthan',
                'ConsigneeCountryName' => 'India',
                'VoucherCostCentre' => '',
                'Narration' => $sampleBillNarration,
                'EWayBillDetails' => '',
                'EInvoiceDetails' => '',
                'item_total' => $bill->quantity ?? 0,
                'InventoryEntries' => [],
                'ledgerentries' => [],
                'EWayBillDetails' => [],
            ];

            $gstSlabAmounts = [];
            $totalWithGST = 0.0;
            $sumLineTaxable = 0.0;

            foreach ($purchaseDetails as $row) {
                $s = $row->sample;
                if (!$s) {
                    continue;
                }
                $stockName = trim((string) ($s->code ?? ''));
                $gstSlab = (float) ($s->gstslab ?? 0);
                if ($gstSlab <= 0) {
                    $gstSlab = 18;
                }
                $amount = (float) ($row->amount ?? 0);
                $sumLineTaxable += $amount;
                $gstAmount = ($gstSlab != 0) ? ($amount * ($gstSlab / 100)) : 0;
                if ($gstSlab != 0) {
                    $gstSlabAmounts[$gstSlab] = ($gstSlabAmounts[$gstSlab] ?? 0) + $gstAmount;
                    $totalWithGST += ($amount + $gstAmount);
                }

                $groupName = (string) (optional($s->subcategory)->name ?? '');

                $desc = $stockName;
                if ($desc !== '' && trim((string) ($s->name ?? '')) !== '') {
                    $desc .= '-' . trim((string) $s->name);
                } elseif ($desc === '') {
                    $desc = trim((string) ($s->name ?? ''));
                }

                $formattedNote['InventoryEntries'][] = [
                    'StockItemName' => $stockName,
                    'ItemCode' => '',
                    'GroupName' => $groupName,
                    'HSNCode' => (string) ($s->hsn ?? ''),
                    'Unit' => 'NOS',
                    'GSTRate' => $gstSlab,
                    'CessRate' => 0,
                    'IsDeemedPositive' => 'YES',
                    'ActualQty' => $row->receiveqty ?? 0,
                    'BilledQty' => $row->receiveqty ?? 0,
                    'Rate' => $row->rate ?? 0,
                    'Amount' => $row->amount ?? 0,
                    'SalesLedger' => 'GST Purchase',
                    'BatchAllocations' => [[
                        'BatchName' => 'Primary Batch',
                        'GodownName' => 'Main Location',
                        'ActualQty' => $row->receiveqty ?? 0,
                        'BilledQty' => $row->receiveqty ?? 0,
                        'Rate' => $row->rate ?? 0,
                        'Amount' => $row->amount ?? 0,
                    ]],
                    'AccountingAllocations' => [[
                        'LedgerName' => $this->purchaseAccountingLedgerName('Sample', $gstSlab),
                        'Amount' => $row->amount ?? 0,
                        'CategoryAllocation' => '',
                        'GSTClassification' => 'Purchase Taxable',
                        'IGST Rate' => $gstSlab,
                    ]],
                    'StockDescriptions' => [[
                        'Description' => $desc,
                    ]],
                ];
            }

            $formattedNote['ledgerentries'][] = [
                'LedgerName' => $supplierName,
                'LedgerAmount' => $bill_total,
                'IsDeemedPositive' => 'NO',
                'IsPartyLedger' => 'YES',
                'BillsAllocation' => '',
                'CategoryAllocation' => '',
                'LedgerDescription' => '',
                'BillRefType' => '',
            ];

            $postedGst = 0.0;
            foreach ($gstSlabAmounts as $gstSlab => $totalAmount) {
                $lineGstTotal = round($totalAmount, 2);
                if ($supplier && $supplier->state == 'Rajasthan') {
                    $perHalf = round($lineGstTotal / 2, 2);
                    $postedGst += ($perHalf * 2);
                    $formattedNote['ledgerentries'][] = [
                        'LedgerName' => 'INPUT SGST @ ' . ($gstSlab / 2) . '%',
                        'LedgerAmount' => $perHalf,
                        'IsDeemedPositive' => 'Yes',
                        'IsPartyLedger' => 'NO',
                        'BillsAllocation' => '',
                        'CategoryAllocation' => '',
                        'LedgerDescription' => '',
                        'BillRefType' => '',
                    ];
                    $formattedNote['ledgerentries'][] = [
                        'LedgerName' => 'INPUT CGST @ ' . ($gstSlab / 2) . '%',
                        'LedgerAmount' => $perHalf,
                        'IsDeemedPositive' => 'Yes',
                        'IsPartyLedger' => 'NO',
                        'BillsAllocation' => '',
                        'CategoryAllocation' => '',
                        'LedgerDescription' => '',
                        'BillRefType' => '',
                    ];
                } else {
                    $postedGst += $lineGstTotal;
                    $formattedNote['ledgerentries'][] = [
                        'LedgerName' => 'INPUT IGST @ ' . $gstSlab . '%',
                        'LedgerAmount' => $lineGstTotal,
                        'IsDeemedPositive' => 'Yes',
                        'IsPartyLedger' => 'NO',
                        'BillsAllocation' => '',
                        'CategoryAllocation' => '',
                        'LedgerDescription' => '',
                        'BillRefType' => '',
                    ];
                }
            }

            $sampleTds = (float) ($bill->tdsTotal ?? 0);
            if (!empty(optional($supplier)->tdsledger) && round($sampleTds, 2) != 0) {
                $formattedNote['ledgerentries'][] = [
                    'LedgerName' => $supplier->tdsledger,
                    'LedgerAmount' => -$sampleTds,
                    'IsDeemedPositive' => 'Yes',
                    'IsPartyLedger' => 'No',
                    'BillsAllocation' => '',
                    'CategoryAllocation' => '',
                    'LedgerDescription' => '',
                    'BillRefType' => '',
                ];
            }

            $this->appendPurchaseRoundOffLedger(
                $formattedNote,
                (float) $bill_total,
                $sumLineTaxable,
                $postedGst,
                0.0
            );

            $formattedNote['EWayBillDetails'][] = [
                'EWayBillNo' => $bill->ewaybill ?? '',
                'EWayBillDate' => $voucherdate ?? '',
                'Mode' => 'Road',
                'Distance' => '',
                'TransporterName' => '',
                'TransporterID' => '',
                'DocNo' => $bill->supp_inv_no ?? '',
                'DocDate' => $voucherdate,
                'VehicleNo' => '',
                'VehicleType' => 'Regular',
                'ConsignorAddress1' => optional($supplier)->address1 ?? '',
                'ConsignorAddress2' => optional($supplier)->address2 ?? '',
                'ConsignorPinCode' => optional($supplier)->postcode ?? '',
                'ConsignorPlace' => optional($supplier)->city ?? '',
                'ConsignorActualState' => optional($supplier)->state ?? '',
            ];

            $exportData[] = $formattedNote;
        }

        // Return the data in JSON format
        return response()->json(['data' => $exportData]);
    }
    public function exportSales(Request $request)
    {


        $fromDate = Carbon::parse($request->query('from'))->startOfDay();  // Start of the day
        $toDate = Carbon::parse($request->query('to'))->endOfDay();
        $invoice = Invoice::with('buyer', 'consignee')
            ->whereBetween('date', [$fromDate, $toDate])
            ->where('tally_status', 1)
            ->get();

        $exportData = [];
        foreach ($invoice as $inv) {
            // return $inv;
            $products = invoiceTable::where('invoice_id', $inv->id)->get();
            $state = $inv->buyer->state;
            $instate = ($state === 'Rajasthan') ? true : false;
            $invoicetype = $inv->invoicetype;
            $exportstatus  = $inv->exportstatus;
            $currency  = $inv->currency;
            $conrate  = $inv->conrate;
            $discount = $inv->discount ?? 0;
            $isExportInvoice = false;
            $isLocalInvoice = false;
            $isAmazonInvoice = false;
            $isTataInvoice = false;
 $isFlipkartInvoice = false;
$isJioMartInvoice = false;
            $isLut = false;

            // Check invoicetype and set the respective flag to true
            $salesLedger = '';
            $SgstLedger = 'OUTPUT SGST @';
            $CgstLedger = 'OUTPUT CGST @';
            $IgstLedger = 'OUTPUT IGST';
            $voucherType = '';
            $destination = '';
            // Check invoicetype and set the respective flag to true and ledger values

            if ($invoicetype == 0) {
                $isExportInvoice = true;
                $voucherType = 'Export';
                $destination = $inv->destination;
                $gsttype = "OIDAR";
                // Check if LUT applies (exportstatus is 0)

                if ($inv->exportstatus == 1) {
                    $isLut = true;
                    $salesLedger = 'Sales of Wooden Furniture Export (LUT)';
                    $IgstLedger = 'IGST on Export';
                } else {
                    $isLut = false;
                    $salesLedger = 'Sales of Wooden Furniture Export';
                    $IgstLedger = 'IGST on Export';
                }
            } elseif ($invoicetype == 1) {
                $isLocalInvoice = true;
                $voucherType = 'Sales GST';
                $voucherType = 'Sales GST';
                $gsttype = $inv->buyer->gstno ? 'Regular' : 'Unregistered/Consumer';
                $destination = ($inv->buyer->address1 ?? '') . ',' . ($inv->buyer->address2 ?? '');
                // Set Sales and GST Ledger for Local Invoice
if($instate){
                $salesLedger = 'Sales of Wooden Furniture GST ';
}else{
$salesLedger = 'Sales of Wooden Furniture IGST ';
}
                //$IgstLedger = 'OUTPUT IGST @18%';
            } elseif ($invoicetype == 2) {
                $isAmazonInvoice = true;
                $voucherType = 'Sales GST';
                $gsttype = $inv->buyer->gstno ? 'Regular' : 'Unregistered/Consumer';
                $destination = ($inv->buyer->address1 ?? '') . ',' . ($inv->buyer->address2 ?? '');

                // Set Sales and GST Ledger for Amazon Invoice
                $salesLedger = 'Amazon Sales of Wooden ';
                //$IgstLedger = 'OUTPUT IGST @18%';
            } elseif ($invoicetype == 3) {
                $isTataInvoice = true;
                $voucherType = 'Sales GST';
                $gsttype = $inv->buyer->gstno ? 'Regular' : 'Unregistered/Consumer';
                $destination = ($inv->buyer->address1 ?? '') . ',' . ($inv->buyer->address2 ?? '');

                // Set Sales and GST Ledger for Tata Invoice
                $salesLedger = 'TATA Sales of Wooden ';
                //$IgstLedger = 'OUTPUT IGST @18%';
            } elseif ($invoicetype == 4) {
    $isFlipkartInvoice = true;
    $voucherType = 'Sales GST';
    $gsttype = $inv->buyer->gstno ? 'Regular' : 'Unregistered/Consumer';
    $destination = ($inv->buyer->address1 ?? '') . ',' . ($inv->buyer->address2 ?? '');
    $salesLedger = 'Flipkart Sales of Wooden';
}
elseif ($invoicetype == 5) {
    $isJioMartInvoice = true;
    $voucherType = 'Sales GST';
    $gsttype = $inv->buyer->gstno ? 'Regular' : 'Unregistered/Consumer';
    $destination = ($inv->buyer->address1 ?? '') . ',' . ($inv->buyer->address2 ?? '');
    $salesLedger = 'JIO Mart Sales of Wooden ';
}

            // Calculate Voucher_Total (handling LUT condition)
            $voucherTotal = 0;
            // return $isLut;
            if ($isLut) {
                // If LUT is true, exclude GST from the total
                $voucherTotal = ($inv->rateamount) / $inv->conrate;


            } else {
                // Otherwise, include both rateamount and totalgst
                $voucherTotal1 = ($inv->rateamount + $inv->totalgst) / $inv->conrate;

                 $voucherTotal = $voucherTotal1;
            }

//$decimalPart = $voucherTotal - floor($voucherTotal);

//if ($decimalPart >= 0.5) {
//    $roundedValue = ceil($voucherTotal);
//    $roundup = true;
//} else {
//    $roundedValue = floor($voucherTotal);
//    $roundup = false;
//}

// Determine the round-off value and update $voucherTotal if invoicetype != 0
//$round = $roundedValue - $voucherTotal;
//$voucherTotalfull = $voucherTotal;
//$voucherTotal = number_format($voucherTotal, 2, '.', '');
// if ($invoicetype != 0) {
//     $voucherTotal = $roundedValue;
// }

            $voucherTotalfull = $voucherTotal;
            $conrateNumParty = (float) ($conrate ?? 1);
            if ($conrateNumParty <= 0) {
                $conrateNumParty = 1.0;
            }
            $exactPartyInr = $voucherTotalfull * $conrateNumParty;
            // Local only: party in Tally is whole rupees; Roundoff absorbs paise vs lines + GST
            $partyInrRounded = null;
            $voucherTotalfullParty = $voucherTotalfull;
            if ($invoicetype != 0) {
                $partyInrRounded = (int) round($exactPartyInr);
                $voucherTotalfullParty = $partyInrRounded / $conrateNumParty;
            }
            $voucherTotal = number_format(
                ($invoicetype != 0 && $currency == '₹')
                    ? $partyInrRounded
                    : $voucherTotalfull,
                2,
                '.',
                ''
            );
            // return $voucherTotal;
            // return $inv->totalgst;
            $totalgst = $inv->totalgst / $conrate;
            $totalgst = round($totalgst, 2);

            $date = $inv->date ?? '';
            $invDate = $date ? Carbon::parse($date)->format('Ymd') : '';
            $billtyDate1 = $inv->billty_date ?? '';
            $billtyDate = $billtyDate1 ? Carbon::parse($billtyDate1)->format('Ymd') : '';
            $ewaybillDate1 = $inv->ewaybilldate ?? '';
            $ewaybillDate = $ewaybillDate1 ? Carbon::parse($ewaybillDate1)->format('Ymd') : '';
            $ReferenceDate1 = $inv->created_at ?? '';
            $ReferenceDate = $ReferenceDate1 ? Carbon::parse($ReferenceDate1)->format('Ymd') : '';
            $cleanOrderNo = trim(explode('/BATCH#', $inv->buyerorderno ?? '')[0]);
            $formattedNote = [
                'MasterID' => 'S' . ($inv->id ?? ''),
                'VoucherNumber' => $inv->invoiceno ?? '',
                'VoucherDate' => $invDate,
                'Reference' => $inv->invoiceno ?? '',
                'ReferenceDate' => $invDate,
                'PartyName' => $inv->buyer->tally_name
    ?? ($inv->buyer->c_name ?? ''),
                'VoucherType' => $voucherType ?? '',
                'VoucherBaseType' => '$VchTypeSales',
                'ShipFromState' => 'Rajasthan',
                'currency' => $currency ?? '',
                'Conversion Rate (₹)' => $conrate ?? '',
                'DeliveryNoteNo' => '',
                'LedgerAmount' => ($currency == "₹")
    ? '₹ ' . $voucherTotal
    : $currency . number_format($voucherTotalfullParty, 2, '.', '') .
      ' @ ₹ ' . $conrate . '/' . $currency .
      ' = ₹ ' . number_format(
          $partyInrRounded !== null ? $partyInrRounded : $exactPartyInr,
          2,
          '.',
          ''
      ),

                'DeliveryNoteDate' => '',
                'DispatchThrough' => '',
                'Destination' => $destination,
                'ContainerNo' => $inv->containerno ?? '',
                'CarrierName' => '',
                'LRNo' => $inv->lr_rr_no ?? '',
                'LRDate' => $billtyDate,
                // 'LrRrNo' => $inv->lr_rr_no,
                // 'BilltyNo' => $inv->billty_no,
                // 'BilltyDate' => $inv->billty_date,
                'EWayBillDate' => $ewaybillDate,
                'EWayBillNo' => $inv->ewaybillno ?? '',
                'MotorVehicleNo' => $inv->vehicleno ?? '',
                'OrderNo' => $inv->invoiceno ?? '',
                'OrderDate' => $invDate,
                'TermsOfPayment' => $inv->payterms ?? '',
                'OtherReferences' => '',
                'TermsOfDelivery' => '',
                'PlaceOfSupply' => $inv->buyer->state ?? '',
                'IsInvoice' => 'Yes',
                'IsDeleted' => 'No',
                'BuyerName' => $inv->buyer->name ?? '',
                'BuyerAlias' => $inv->buyer->code ?? '',
                'BuyerGSTIN' => $inv->buyer->gstno ?? '',
                'GSTRegistrationType' => $gsttype,
                'BuyerAddress' => [
                    $inv->buyer->address1 ?? '',
                    $inv->buyer->address2 ?? '',
                    $inv->buyer->city ?? '',
                    $inv->buyer->postcode ?? '',
                    trim(implode(', ', array_filter([
                        $inv->buyer->email ?? null,
                        $inv->buyer->phone ?? null,
                    ]))),
                ],
                'BuyerState' => $inv->buyer->state ?? '',
                'BuyerCountryName' => $inv->buyer->country,
                'BuyerEmail' => $inv->buyer->email ?? '',
                'BuyerMobile' => $inv->buyer->phone ?? '',
                'ConsigneeName' => $inv->consignee->name ?? $inv->buyer->name,
                'ConsigneeGSTIN' => ($inv->consignee->gstno ?? $inv->buyer->gstno) ?? '',
                'ConsigneeAddress' => [
                    $inv->consignee->address1 ?? $inv->buyer->address1 ?? '',
                    $inv->consignee->address2 ?? $inv->buyer->address2 ?? '',
                    $inv->consignee->city ?? $inv->buyer->city ?? '',
                    $inv->consignee->postcode ?? $inv->buyer->postcode,
                    trim(implode(', ', array_filter([
                        $inv->consignee->email ?? $inv->buyer->email ?? null,
                        $inv->consignee->phone ?? $inv->buyer->phone ?? null,
                    ]))),
                ],
                'ConsigneeState' => $inv->consignee->state ?? $inv->buyer->state,
                'ConsigneeCountryName' => $inv->consignee->country ?? $inv->buyer->country,
                'VoucherCostCentre' => '',
                'Narration' => 'BuyerOrderNo: ' . $cleanOrderNo .
               ', Destination: ' . $destination .
               ', ContainerNo: ' . ($inv->containerno ?? '') .
               ', VehicleNo: ' . ($inv->vehicleno ?? '') .
               ', invoice no: ' . ($inv->invoiceno ?? ''),
                'EWayBillDetails' => '',
                'EInvoiceDetails' => '',
                'item_total' => $inv->totalquantity  ?? 0,

                'InventoryEntries' => [],
                'ledgerentries' => [],
                'EWayBillDetails' => []
            ];

$totalConvertedAmount = 0;
$totalLineSalesInr = 0;
            foreach ($products as $product) {
$convertedAmount = 0;

    if ($currency !== "₹") {
        $convertedAmount = floor((($product->amount ?? 0) * ($conrate ?? 0)) * 100) / 100;
        $totalConvertedAmount += $convertedAmount;
        $totalLineSalesInr += $convertedAmount;
        $convertedAmount1 = number_format($convertedAmount, 2, '.', '');
    } else {
        $totalLineSalesInr += (float) ($product->amount ?? 0);
    }
    $totalConvertedAmount1 = number_format($totalConvertedAmount, 2, '.', '');
                $formattedNote['InventoryEntries'][] = [
                    'StockItemName' => $product->product->code ?? '',
                    'ItemCode' => '',
                    'GroupName' => $product->product->subcategory->name ?? '',
                    'HSNCode' => $product->product->HSN ?? '',
                    'Unit' => 'NOS',
                    'GSTRate' => (($g = $product->gstslab) === null || $g === '' || $g == 0) ? 18 : $g,
                    'CessRate' => 0,
                    'IsDeemedPositive' => 'No',
                    'ActualQty' => $product->quantity ?? 0,
                    'BilledQty' => $product->quantity ?? 0,
                    'Rate' => $product->rate ?? 0,


                    'Amount' => ($currency == "₹")
    ? '₹ ' . number_format(($product->amount ?? 0), 2, '.', '')
    : $currency . number_format(($product->amount ?? 0), 2, '.', '')
      . ' @ ₹ ' . $conrate . '/' . $currency
      . ' = ₹ ' . number_format(floor((($product->amount ?? 0) * ($conrate ?? 0)) * 100) / 100, 2, '.', ''),
'SalesLedger' => $isExportInvoice ? 'Sales Export' : 'Sales GST',
         'BatchAllocations' => [
                        [
                            'BatchName' => 'Primary Batch',
                            'GodownName' => 'Main Location',
                            'ActualQty' => $product->quantity ?? 0,
                            'BilledQty' => $product->quantity ?? 0,
                            'Rate' => $product->rate ?? 0,

                            'Amount' => ($currency == "₹")
    ? '₹ ' . number_format(($product->amount ?? 0), 2, '.', '')
    : $currency . number_format(($product->amount ?? 0), 2, '.', '')
      . ' @ ₹ ' . $conrate . '/' . $currency
      . ' = ₹ ' . number_format(floor((($product->amount ?? 0) * ($conrate ?? 0)) * 100) / 100, 2, '.', ''),

                        ]
                    ],
                    'AccountingAllocations' => [
                        [
                            'LedgerName' => in_array($invoicetype, [0, 4]) ? ($salesLedger ?? '') : (($salesLedger ?? '') . ($product->gstslab ?? '') . '%'),

                            'Amount' => ($currency == "₹")
    ? '₹ ' . number_format(($product->amount ?? 0), 2, '.', '')
    : $currency . number_format(($product->amount ?? 0), 2, '.', '')
      . ' @ ₹ ' . $conrate . '/' . $currency
      . ' = ₹ ' . number_format(floor((($product->amount ?? 0) * ($conrate ?? 0)) * 100) / 100, 2, '.', ''),

                            'CategoryAllocation' => '',
                            'GSTClassification' => ($invoicetype == 0)
                                ? (($inv->exportstatus == 0) ? 'Export Taxable' : 'Exports LUT/Bond')
                                : (($instate) ? 'Sales Taxable' : 'Interstate Sales Taxable'),
                            'IGST Rate' => (($g = $product->gstslab) === null || $g === '' || $g == 0) ? 18 : $g,
                            'LedgerGroup' => '$$GroupSales',
                        ]
                    ],
                    'StockDescriptions' => [
                        [
                            'Description' => ($product->product->code ?? '') . '-' . ($product->product->name ?? ''),
                        ]
                    ]
                ];
            }

            // Add ledger entries
            $formattedNote['ledgerentries'][] = [
                'LedgerName' => $inv->buyer->tally_name
    ?? ($inv->buyer->c_name ?? ''),
                'LedgerAmount' => ($currency == "₹")
    ? '₹ ' . $voucherTotal
    : $currency . number_format($voucherTotalfullParty, 2, '.', '') .
      ' @ ₹ ' . $conrate . '/' . $currency .
      ' = ₹ ' . number_format(
          $partyInrRounded !== null ? $partyInrRounded : $exactPartyInr,
          2,
          '.',
          ''
      ),


                'IsDeemedPositive' => 'Yes',
                'IsPartyLedger' => 'Yes',
                'BillsAllocation' => '',
                'CategoryAllocation' => '',
                'LedgerDescription' => '',
                'BillRefType' => '',
                'LedgerGroup' => '$$GroupSundryDebtors',
            ];


            // Initialize an array to store gstamount sums for each distinct gstslab
            $gstSlabAmounts = [];
$totalGstAmount = 0;
            // Group and sum gstamount by gstslab
            foreach ($products as $product) {
                $gstSlab = $product->gstslab; // GST slab of the product
                $gstAmount = $product->gstamount; // GST amount of the product
$totalGstAmount += $gstAmount;
                // Sum up gstamount for each GST slab
                if (isset($gstSlabAmounts[$gstSlab])) {
                    $gstSlabAmounts[$gstSlab] += $gstAmount;
                } else {
                    $gstSlabAmounts[$gstSlab] = $gstAmount;
                }
            }
            // $instate = true;
            if ($instate) {
                foreach ($gstSlabAmounts as $gstSlab => $totalAmount) {
                    $formattedNote['ledgerentries'][] = [
                        'LedgerName' => $SgstLedger . ($gstSlab / 2) . '%',
                        //'LedgerName' => $SgstLedger . ($gstSlab / 2) . '%',
                        'LedgerAmount' =>
    ($currency == '₹')
        ? '₹ ' . number_format($totalAmount / 2, 2, '.', '')
        : (
            $currency . number_format(($totalAmount / 2) / $conrate, 2, '.', '') .
            ' @ ₹ ' . $conrate . '/' . $currency .
            ' = ₹ ' . number_format($totalAmount / 2, 2, '.', '')
        ),

                        'IsDeemedPositive' => 'No',
                        'IsPartyLedger' => 'No',
                        'BillsAllocation' => '',
                        'CategoryAllocation' => '',
                        'LedgerDescription' => '',
                        'BillRefType' => ''
                    ];

                    $formattedNote['ledgerentries'][] = [
                        'LedgerName' => $CgstLedger . ($gstSlab / 2) . '%',
                        'LedgerAmount' => ($currency == '₹')
    ? '₹ ' . number_format(($totalAmount / 2), 2, '.', '')
    : $currency . number_format((($totalAmount / 2) / $conrate), 2, '.', '')
      . ' @ ₹ ' . $conrate . '/' . $currency
      . ' = ₹ ' . number_format(($totalAmount / 2), 2, '.', ''),

                        'IsDeemedPositive' => 'No',
                        'IsPartyLedger' => 'No',
                        'BillsAllocation' => '',
                        'CategoryAllocation' => '',
                        'LedgerDescription' => '',
                        'BillRefType' => ''
                    ];
                }

            } else {
if ($inv->exportstatus != 1) {
                // Add ledger entries for each GST slab
                foreach ($gstSlabAmounts as $gstSlab => $totalAmount) {
                    $formattedNote['ledgerentries'][] = [
                        'LedgerName' => ($invoicetype != 0 ) ? 'OUTPUT IGST @' . $gstSlab . '%' : $IgstLedger,

                        'LedgerAmount' => ($currency == '₹')
    ? '₹ ' . number_format($totalAmount, 2)
    : $currency . ' ' . number_format(($totalAmount / $conrate), 2) . ' @ ₹ ' . $conrate . '/' . $currency . ' = ₹ ' . number_format($totalAmount, 2),

                        // round($totalAmount / $conrate, 2),
                        'IsDeemedPositive' => 'No',
                        'IsPartyLedger' => 'No',
                        'BillsAllocation' => '',
                        'CategoryAllocation' => '',
                        'LedgerDescription' => '',
                        'BillRefType' => ''
                    ];
}
                }
            }
if($invoicetype != 0){
 $formattedNote['ledgerentries'][] = [
               "LedgerName" => "Discount on Sales",
                "LedgerAmount" => number_format(-$discount, 2, '.', ''),
                "IsDeemedPositive" => "No",
                "IsPartyLedger" => "No",
                "BillsAllocation" => "",
                "CategoryAllocation" => "",
                "LedgerDescription" => "",
                "BillRefType" => ""
            ];


 // Party Dr (whole ₹) = sales Cr + GST Cr − discount Cr + Roundoff Cr
            $round = $partyInrRounded - $totalLineSalesInr - $totalGstAmount + $discount;

            if (abs($round) > 0.009) {

$formattedNote['ledgerentries'][] = [
    'LedgerName' => "Roundoff",
    'LedgerAmount' => number_format(($round), 2, '.', ''),
    'IsDeemedPositive' => 'No',
    'IsPartyLedger' => 'No',
    'BillsAllocation' => '',
    'CategoryAllocation' => '',
    'LedgerDescription' => '',
    'BillRefType' => ''
];
}
}else{
$total = $voucherTotalfull * $conrate;
if ($inv->exportstatus != 1) {
$all = $totalConvertedAmount + $totalGstAmount;
}else{
$all = $totalConvertedAmount;
}
$round = $total - $all;
if (abs($round) > 0.009) {
$formattedNote['ledgerentries'][] = [
    'LedgerName' => "Roundoff",
    'LedgerAmount' => number_format($round, 2, '.', ''),
    'IsDeemedPositive' => 'No',
    'IsPartyLedger' => 'No',
    'BillsAllocation' => '',
    'CategoryAllocation' => '',
    'LedgerDescription' => '',
    'BillRefType' => ''
];
}
}
$formattedNote['EWayBillDetails'][] = [
                    'EWayBillNo'=> $inv->ewaybillno ?? '',
                    'EWayBillDate'=> $ewaybillDate ?? '',
                    'Mode'=>'Road',
                    'Distance'=> $inv->distance ?? '',
                    'TransporterName'=> $inv->transportertame ?? '',
                    'TransporterID'=> $inv->transporterid ?? '',
                    'DocNo'=> $inv->lr_rr_no ?? '',
                    'DocDate'=> $inv->docdate ? Carbon::parse($inv->docdate)->format('Ymd') : '',
                    'VehicleNo'=> $inv->vehicleno ?? '',
                    'VehicleType'=> 'Regular',
                    'ConsignorAddress1' => "Plot# 1216/2, Mahapura Road,",
                    'ConsignorAddress2'=> "Jaipur - Mumbai National Highway,\nBhankrota, Jaipur, Rajasthan - 08",
                    'ConsignorPinCode'=> '302026',
                    'ConsignorPlace'=> 'Jaipur',
                    'ConsignorActualState'=> 'Rajasthan',
                ];

          $exportData[] = $formattedNote;
        }

        return response()->json(['data' => $exportData]);
    }
}