<?php

namespace App\Services\InwardSupply;

use App\packaging;
use App\PurchaseBillCarton;
use App\purchaseOrderConsumable;
use App\PbTableCorton;
use App\popTable;
use App\StockLogCarton;
use App\supplier;
use App\supplierInvoice;
use App\supplierInvoiceProduct;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;

class CartonAdminInwardService
{
    public function execute(Request $request): RedirectResponse
    {
        $request->validate([
            'purchaseOrder_id' => 'required|integer',
            'ewaybill' => 'nullable|string',
            'supp_inv_no' => 'required|string',
            'supp_inv_date' => 'nullable|date',
            'invoice_date' => 'nullable|date',
            'pbQty' => 'nullable|numeric',
            'pbSubTotal' => 'nullable|numeric',
            'pbGST' => 'nullable|numeric',
            'freight' => 'nullable|numeric',
            'roundoff' => 'nullable|numeric|min:-3|max:3',
            'pbTotal' => 'nullable|numeric',
            'tdsTotal' => 'nullable|numeric',
            'pb' => 'required|array',
            'pb.*.product' => 'required',
            'pb.*.receiveqty_box_1' => 'nullable|numeric',
            'pb.*.receiveqty_box_2' => 'nullable|numeric',
        ]);

        $po = purchaseOrderConsumable::find($request['purchaseOrder_id']);
        if (!$po) {
            return back()->with('error', 'Purchase order not found.')->withInput();
        }
        if ((int) $po->type !== 2) {
            return back()->with('error', 'This inward flow is only for carton purchase orders.')->withInput();
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

        $invDate = $request['supp_inv_date'] ?? $request['invoice_date'];
        if (empty($invDate)) {
            return back()->with('error', 'Invoice date is required.')->withInput();
        }

        $lines = $request['pb'];
        $hasReceipt = false;
        foreach ($lines as $pb) {
            $r1 = (int) ($pb['receiveqty_box_1'] ?? 0);
            $r2 = (int) ($pb['receiveqty_box_2'] ?? 0);
            if ($r1 <= 0 && $r2 <= 0) {
                continue;
            }
            $hasReceipt = true;
            $pop = popTable::where('poid', $po->id)->where('product_id', $pb['product'])->first();
            if (!$pop) {
                return back()->with('error', 'Purchase order line not found for one of the products.')->withInput();
            }
            if ($r1 > (int) $pop->remqty_box1 || $r2 > (int) $pop->remqty_box2) {
                return back()->with('error', 'Receive quantity cannot exceed remaining box quantities on the PO.')->withInput();
            }
        }

        if (!$hasReceipt) {
            return back()->with('error', 'Enter a receive quantity on at least one line.')->withInput();
        }

        DB::transaction(function () use ($request, $po, $supplier, $suppInvNo, $eway, $lines, $invDate) {
            $referenceNumber = AdminInwardSupport::newSupplierInvoiceReferenceNumber();
            $billSubTotal = 0.0;
            $billGst = 0.0;
            $billQty = 0.0;

            $roundoff = (float) ($request['roundoff'] ?? 0);
            if ($roundoff > 3) {
                $roundoff = 3.0;
            } elseif ($roundoff < -3) {
                $roundoff = -3.0;
            }

            $supplierInvoice = supplierInvoice::create([
                'purchase_order_id' => $po->id,
                'supplier_id' => $supplier->id,
                'supplier_invoice_number' => $suppInvNo,
                'eway_bill_no' => $eway,
                'tquantity' => (int) ($request['pbQty'] ?? 0),
                'tgst' => (float) ($request['pbGST'] ?? 0),
                'subTotal' => (float) ($request['pbSubTotal'] ?? 0),
                'tamount' => (float) ($request['pbTotal'] ?? 0),
                'totaldiscount' => (float) ($request['totaldiscount'] ?? 0),
                'roundoff' => $roundoff,
                'invoice_date' => $invDate,
                'purchase_order_type' => 'Carton',
                'user_id' => auth()->id(),
                'status' => 0,
                'reference_number' => $referenceNumber,
                'batch_no' => null,
            ]);
            $supplierInvoice->is_approved = 1;
            $supplierInvoice->save();

            $tdsRounded = AdminInwardSupport::roundTds($request['tdsTotal'] ?? null);

            $bill = PurchaseBillCarton::create([
                'purchaseOrder_id' => $po->id,
                'ewaybill' => $eway,
                'supp_inv_no' => $suppInvNo,
                'supp_inv_date' => $invDate,
                'quantity' => 0,
                'subtotal' => 0,
                'gst' => 0,
                'freight' => $request['freight'] ?? 0,
                'roundoff' => $roundoff,
                'total' => 0,
                'tdsTotal' => $tdsRounded ?? $request['tdsTotal'],
                'invoice_status' => 1,
                'supplier_invoice_id' => $supplierInvoice->id,
                'supplier_id' => $supplier->id,
            ]);

            foreach ($lines as $pb) {
                $r1 = (int) ($pb['receiveqty_box_1'] ?? 0);
                $r2 = (int) ($pb['receiveqty_box_2'] ?? 0);
                if ($r1 <= 0 && $r2 <= 0) {
                    continue;
                }

                $poProduct = popTable::where('poid', $po->id)->where('product_id', $pb['product'])->first();
                if (! $poProduct) {
                    throw new \RuntimeException('Purchase order line not found for one of the products.');
                }

                $orderqty = (int) $poProduct->remqty_box1;
                $orderqty2 = (int) $poProduct->remqty_box2;
                $remainingqty = $orderqty - $r1;
                $remainingqty2 = $orderqty2 - $r2;

                $rate1 = (float) ($poProduct->box1_rate ?? 0);
                $rate2 = (float) ($poProduct->box2_rate ?? 0);
                $amount = round($rate1 * $r1 + $rate2 * $r2, 2);
                $gstSlab = (float) ($poProduct->gstslab ?? $pb['gstslab'] ?? 0);
                $lineGst = round($amount * $gstSlab / 100, 2);
                $lineTotal = round($amount + $lineGst, 2);
                $billSubTotal += $amount;
                $billGst += $lineGst;
                $billQty += $r1 + $r2;

                PbTableCorton::create([
                    'product_id' => $pb['product'],
                    'purchaseOrder_id' => $bill->purchaseOrder_id,
                    'purchaseBill_id' => $bill->id,
                    'orderqty' => $orderqty,
                    'orderqty2' => $orderqty2,
                    'receiveqty' => $r1,
                    'receiveqty2' => $r2,
                    'remainingqty' => $remainingqty,
                    'remainingqty2' => $remainingqty2,
                    'rate' => $rate1,
                    'rate2' => $rate2,
                    'amount' => $amount,
                ]);

                $poProduct->remqty_box1 = $remainingqty;
                $poProduct->remqty_box2 = $remainingqty2;
                $poProduct->save();

                supplierInvoiceProduct::create([
                    'supplier_invoice_id' => $supplierInvoice->id,
                    'purchase_order_id' => $po->id,
                    'product_id' => (int) $pb['product'],
                    'quantity' => $r1,
                    'quantity2' => $r2,
                    'amount' => $amount,
                    'discount_type' => $pb['discount_type'] ?? null,
                    'discount' => (float) ($pb['discount'] ?? 0),
                    'gst' => $gstSlab,
                    'total' => $lineTotal,
                ]);

                $product = packaging::where('product_id', $pb['product'])->first();
                if ($product) {
                    $open1 = (int) $product->box_1_qty;
                    $open2 = (int) $product->box_2_qty;
                    $product->box_1_qty = $open1 + $r1;
                    $product->box_2_qty = $open2 + $r2;
                    $product->save();

                    if ((int) $po->address_option !== 100) {
                        StockLogCarton::create([
                            'product_id' => $pb['product'],
                            'voucher_no' => $suppInvNo,
                            'supplier_inv_no' => $suppInvNo,
                            'ref_no' => $po->ref_supplier,
                            'quantity' => $r1,
                            'quantity2' => $r2,
                            'opening_balance' => $open1,
                            'opening_balance2' => $open2,
                            'remaining_stock' => $product->box_1_qty,
                            'remaining_stock2' => $product->box_2_qty,
                            'type' => 1,
                            'supplier_name' => $supplier->c_name,
                            'entity_id' => $bill->id,
                        ]);
                    }
                }
            }

            $billSubTotal = round($billSubTotal, 2);
            $billGst = round($billGst, 2);
            $freight = (float) ($request['freight'] ?? 0);
            $bill->quantity = $billQty;
            $bill->subtotal = $billSubTotal;
            $bill->gst = $billGst;
            $bill->roundoff = $roundoff;
            $bill->total = round($billSubTotal + $billGst + $freight + $roundoff, 2);
            $bill->save();

            $supplierInvoice->tquantity = $billQty;
            $supplierInvoice->subTotal = $billSubTotal;
            $supplierInvoice->tgst = $billGst;
            $supplierInvoice->roundoff = $roundoff;
            $supplierInvoice->tamount = $bill->total;
            $supplierInvoice->save();

            $statusremqty = 0;
            $statusremqty2 = 0;
            foreach (popTable::where('poid', $po->id)->get() as $row) {
                $statusremqty += (int) $row->remqty_box1;
                $statusremqty2 += (int) $row->remqty_box2;
            }
            $po->status = ($statusremqty === 0 && $statusremqty2 === 0) ? 1 : 0;
            $po->remqty = $statusremqty;
            $po->save();
        });

        return redirect('/purchaseBill/condition/carton')->with('success', 'Carton inward saved successfully.');
    }
}
