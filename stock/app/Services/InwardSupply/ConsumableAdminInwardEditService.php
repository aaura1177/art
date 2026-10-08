<?php

namespace App\Services\InwardSupply;

use App\consumable;
use App\pbTableConsumable;
use App\pocTable;
use App\purchaseBillConsumable;
use App\purchaseOrderConsumable;
use App\stockLogConsumable;
use App\supplierInvoice;
use App\supplierInvoiceProduct;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ConsumableAdminInwardEditService
{
    public function execute(Request $request): RedirectResponse
    {
        $request->validate([
            'purchaseOrder_id' => 'required|integer',
            'pb' => 'required|array',
            'pb.*.product' => 'required|integer',
            'pb.*.receiveqty' => 'nullable|numeric',
            'pb.*.rate' => 'nullable|numeric',
            'pb.*.amount' => 'nullable|numeric',
            'pb.*.gstslab' => 'nullable|numeric',
            'pb.*.unit' => 'nullable|string',
            'pb.*.location' => 'nullable|string',
        ]);

        $po = purchaseOrderConsumable::find($request['purchaseOrder_id']);
        if (!$po || (int) $po->type !== 1) {
            return back()->with('error', 'Consumable PO not found.')->withInput();
        }

        $latestBill = purchaseBillConsumable::where('purchaseOrder_id', $po->id)
            ->whereNotNull('supplier_invoice_id')
            ->orderByDesc('id')
            ->first();

        if (!$latestBill) {
            return back()->with('error', 'No existing consumable inward found for this PO.')->withInput();
        }

        $supplierInvoice = supplierInvoice::find($latestBill->supplier_invoice_id);
        if (!$supplierInvoice) {
            return back()->with('error', 'Linked supplier invoice not found for latest inward.')->withInput();
        }

        $lines = $request['pb'];
        $hasDelta = false;
        foreach ($lines as $pb) {
            $deltaQty = (float) ($pb['receiveqty'] ?? 0);
            if ($deltaQty <= 0) {
                continue;
            }
            $hasDelta = true;
            $poLine = pocTable::where('poid', $po->id)->where('consumable_id', $pb['product'])->first();
            if (!$poLine) {
                return back()->with('error', 'PO line not found for one of the products.')->withInput();
            }
            if ($deltaQty > (float) $poLine->remqty) {
                return back()->with('error', 'Delta qty exceeds remaining qty for one of the products.')->withInput();
            }
        }
        if (!$hasDelta) {
            return back()->with('error', 'Enter positive qty on at least one product line.')->withInput();
        }

        DB::transaction(function () use ($po, $latestBill, $supplierInvoice, $lines) {
            $deltaTotalQty = 0.0;
            $deltaSubTotal = 0.0;
            $deltaGst = 0.0;

            foreach ($lines as $pb) {
                $deltaQty = (float) ($pb['receiveqty'] ?? 0);
                if ($deltaQty <= 0) {
                    continue;
                }

                $poLine = pocTable::where('poid', $po->id)->where('consumable_id', $pb['product'])->first();
                $orderqty = (float) $poLine->remqty;
                $remainingqty = $orderqty - $deltaQty;

                $rate = (float) ($pb['rate'] ?? 0);
                $amount = (float) ($pb['amount'] ?? 0);
                $gstSlab = (float) ($pb['gstslab'] ?? 0);
                $lineGst = ($amount * $gstSlab) / 100;

                pbTableConsumable::create([
                    'product_id' => $pb['product'],
                    'purchaseOrder_id' => $po->id,
                    'purchaseBill_id' => $latestBill->id,
                    'orderqty' => $orderqty,
                    'receiveqty' => $deltaQty,
                    'remainingqty' => $remainingqty,
                    'rate' => $rate,
                    'unit' => $pb['unit'] ?? '',
                    'amount' => $amount,
                    'location' => $pb['location'] ?? '',
                ]);

                $poLine->remqty = $remainingqty;
                $poLine->save();

                supplierInvoiceProduct::create([
                    'supplier_invoice_id' => $supplierInvoice->id,
                    'purchase_order_id' => $po->id,
                    'product_id' => (int) $pb['product'],
                    'quantity' => $deltaQty,
                    'amount' => $amount,
                    'gst' => $gstSlab,
                    'total' => $amount + $lineGst,
                ]);

                $product = consumable::find($pb['product']);
                if ($product) {
                    $openBalance = (float) $product->quantity;
                    $product->quantity = $openBalance + $deltaQty;
                    $product->save();

                    if ((int) $po->address_option !== 100) {
                        stockLogConsumable::create([
                            'consumable_id' => $pb['product'],
                            'voucher_no' => strtoupper((string) $latestBill->supp_inv_no),
                            'supplier_inv_no' => strtoupper((string) $latestBill->supp_inv_no),
                            'ref_no' => $po->ref_supplier,
                            'quantity' => $deltaQty,
                            'opening_balance' => $openBalance,
                            'remaining_stock' => $product->quantity,
                            'type' => 1,
                            'supplier_name' => optional($po->supplier)->c_name,
                            'entity_id' => $latestBill->id,
                            'batch_balance' => $deltaQty,
                        ]);
                    }
                }

                $deltaTotalQty += $deltaQty;
                $deltaSubTotal += $amount;
                $deltaGst += $lineGst;
            }

            $latestBill->quantity = (float) $latestBill->quantity + $deltaTotalQty;
            $latestBill->subtotal = (float) $latestBill->subtotal + $deltaSubTotal;
            $latestBill->gst = (float) $latestBill->gst + $deltaGst;
            $latestBill->total = (float) $latestBill->subtotal + (float) $latestBill->gst + (float) ($latestBill->freight ?? 0);
            $latestBill->save();

            $supplierInvoice->tquantity = (float) $supplierInvoice->tquantity + $deltaTotalQty;
            $supplierInvoice->subTotal = (float) $supplierInvoice->subTotal + $deltaSubTotal;
            $supplierInvoice->tgst = (float) $supplierInvoice->tgst + $deltaGst;
            $supplierInvoice->tamount = (float) $supplierInvoice->subTotal + (float) $supplierInvoice->tgst + (float) ($latestBill->freight ?? 0);
            $supplierInvoice->save();

            $statusremqty = 0.0;
            foreach (pocTable::where('poid', $po->id)->get() as $row) {
                $statusremqty += (float) $row->remqty;
            }
            $po->status = $statusremqty == 0.0 ? 1 : 0;
            $po->remqty = $statusremqty;
            $po->save();
        });

        return redirect('/purchaseBill/admin/consumable/edit')->with('success', 'Consumable inward updated successfully (delta appended).');
    }
}

