<?php

namespace App\Services\InwardSupply;

use App\consumable;
use App\purchaseBillConsumable;
use App\purchaseOrderConsumable;
use App\pbTableConsumable;
use App\pocTable;
use App\stockLogConsumable;
use App\supplier;
use App\supplierInvoice;
use App\supplierInvoiceProduct;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;

class ConsumableAdminInwardService
{
    public function execute(Request $request): RedirectResponse
    {
        $request->validate([
            'purchaseOrder_id' => 'required|integer',
            'ewaybill' => 'nullable|string',
            'supp_inv_no' => 'required|string',
            'supp_inv_date' => 'required|date',
            'pbQty' => 'nullable|numeric',
            'pbSubTotal' => 'nullable|numeric',
            'pbGST' => 'nullable|numeric',
            'freight' => 'nullable|numeric',
            'pbTotal' => 'nullable|numeric',
            'tdsTotal' => 'nullable|numeric',
            'pb' => 'required|array',
            'pb.*.product' => 'required',
            'pb.*.receiveqty' => 'nullable|numeric',
        ]);

        $po = purchaseOrderConsumable::find($request['purchaseOrder_id']);
        if (!$po) {
            return back()->with('error', 'Purchase order not found.')->withInput();
        }
        if ((int) $po->type !== 1) {
            return back()->with('error', 'This inward flow is only for consumable purchase orders.')->withInput();
        }

        $eligible = InwardEligibility::firstEligibleInwardDate($po);
        if ($eligible && Carbon::today()->lt($eligible)) {
            return back()->with(
                'danger',
                'Inward supply can be generated only from 7 days before delivery date (eligible on ' . $eligible->format('d-m-Y') . ').'
            )->withInput();
        }

        $supplier = supplier::find($po->supplier_id);
        if (!$supplier) {
            return back()->with('error', 'Supplier not found for this purchase order.')->withInput();
        }

        $suppInvNo = strtoupper(trim($request['supp_inv_no']));
        $eway = strtoupper(trim((string) ($request['ewaybill'] ?? '')));

        if (AdminInwardSupport::existingActiveSupplierInvoiceConflict((int) $supplier->id, $suppInvNo, $eway)) {
            return back()->with('error', 'Supplier Invoice No. or E-Way Bill No. already exists!')->withInput();
        }

        $lines = $request['pb'];
        $hasReceipt = false;
        foreach ($lines as $pb) {
            $rq = (float) ($pb['receiveqty'] ?? 0);
            if ($rq <= 0) {
                continue;
            }
            $hasReceipt = true;
            $poc = pocTable::where('poid', $po->id)->where('consumable_id', $pb['product'])->first();
            if (!$poc) {
                return back()->with('error', 'Purchase order line not found for one of the products.')->withInput();
            }
            if ($rq > (float) $poc->remqty) {
                return back()->with('error', 'Receive quantity cannot exceed remaining quantity on the PO.')->withInput();
            }
        }

        if (!$hasReceipt) {
            return back()->with('error', 'Enter a receive quantity on at least one line.')->withInput();
        }

        DB::transaction(function () use ($request, $po, $supplier, $suppInvNo, $eway, $lines) {
            $referenceNumber = AdminInwardSupport::newSupplierInvoiceReferenceNumber();

            $supplierInvoice = supplierInvoice::create([
                'purchase_order_id' => $po->id,
                'supplier_id' => $supplier->id,
                'supplier_invoice_number' => $suppInvNo,
                'eway_bill_no' => $eway,
                'tquantity' => (float) ($request['pbQty'] ?? 0),
                'tgst' => (float) ($request['pbGST'] ?? 0),
                'subTotal' => (float) ($request['pbSubTotal'] ?? 0),
                'tamount' => (float) ($request['pbTotal'] ?? 0),
                'totaldiscount' => (float) ($request['totaldiscount'] ?? 0),
                'invoice_date' => $request['supp_inv_date'],
                'purchase_order_type' => 'Consumable',
                'user_id' => auth()->id(),
                'status' => 0,
                'reference_number' => $referenceNumber,
                'batch_no' => null,
            ]);
            $supplierInvoice->is_approved = 1;
            $supplierInvoice->save();

            $tdsRounded = AdminInwardSupport::roundTds($request['tdsTotal'] ?? null);

            $bill = purchaseBillConsumable::create([
                'purchaseOrder_id' => $po->id,
                'supplier_id' => $po->supplier_id,
                'ewaybill' => $eway,
                'supp_inv_no' => $suppInvNo,
                'supp_inv_date' => $request['supp_inv_date'],
                'quantity' => $request['pbQty'],
                'subtotal' => $request['pbSubTotal'],
                'gst' => $request['pbGST'],
                'freight' => $request['freight'],
                'total' => $request['pbTotal'],
                'tdsTotal' => $tdsRounded ?? $request['tdsTotal'],
                'invoice_status' => 1,
                'supplier_invoice_id' => $supplierInvoice->id,
            ]);

            foreach ($lines as $pb) {
                $receiveQty = (float) ($pb['receiveqty'] ?? 0);
                if ($receiveQty <= 0) {
                    continue;
                }

                $poProduct = pocTable::where('poid', $po->id)->where('consumable_id', $pb['product'])->first();

                $orderqty = (float) $poProduct->remqty;
                $remainingqty = $orderqty - $receiveQty;

                pbTableConsumable::create([
                    'product_id' => $pb['product'],
                    'purchaseOrder_id' => $bill->purchaseOrder_id,
                    'purchaseBill_id' => $bill->id,
                    'orderqty' => $orderqty,
                    'receiveqty' => $receiveQty,
                    'remainingqty' => $remainingqty,
                    'rate' => $pb['rate'] ?? 0,
                    'unit' => $pb['unit'] ?? '',
                    'amount' => $pb['amount'] ?? 0,
                    'location' => $pb['location'] ?? '',
                ]);

                $poProduct->remqty = $remainingqty;
                $poProduct->save();

                $gstSlab = (float) ($pb['gstslab'] ?? 0);
                $amount = (float) ($pb['amount'] ?? 0);
                $lineTotal = $amount + (($amount * $gstSlab) / 100);

                supplierInvoiceProduct::create([
                    'supplier_invoice_id' => $supplierInvoice->id,
                    'purchase_order_id' => $po->id,
                    'product_id' => (int) $pb['product'],
                    'quantity' => $receiveQty,
                    'amount' => $amount,
                    'discount_type' => $pb['discount_type'] ?? null,
                    'discount' => (float) ($pb['discount'] ?? 0),
                    'gst' => $gstSlab,
                    'total' => $lineTotal,
                ]);

                $product = consumable::where('id', $pb['product'])->first();
                if ($product) {
                    $openBalance = (float) $product->quantity;
                    $product->quantity = $openBalance + $receiveQty;
                    $product->save();

                    if ((int) $po->address_option !== 100) {
                        stockLogConsumable::create([
                            'consumable_id' => $pb['product'],
                            'voucher_no' => $suppInvNo,
                            'supplier_inv_no' => $suppInvNo,
                            'ref_no' => $po->ref_supplier,
                            'quantity' => $receiveQty,
                            'opening_balance' => $openBalance,
                            'remaining_stock' => $product->quantity,
                            'type' => 1,
                            'supplier_name' => $supplier->c_name,
                            'entity_id' => $bill->id,
                            'batch_balance' => $receiveQty,
                        ]);
                    }
                }
            }

            $statusremqty = 0;
            foreach (pocTable::where('poid', $po->id)->get() as $row) {
                $statusremqty += (float) $row->remqty;
            }
            $po->status = $statusremqty == 0.0 ? 1 : 0;
            $po->remqty = $statusremqty;
            $po->save();
        });

        return redirect('/purchaseBill/condition/consumables')->with('success', 'Consumable inward saved successfully.');
    }
}
