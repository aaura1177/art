<?php

namespace App\Http\Controllers;

use App\consumable;
use App\Notification;
use App\packaging;
use App\pbTableConsumable;
use App\PbTableCorton;
use App\pocTable;
use App\popTable;
use App\purchaseBillConsumable;
use App\PurchaseBillCarton;
use App\purchaseOrderConsumable;
use App\soTable;
use App\stockLogConsumable;
use App\StockLogCarton;
use App\supplier;
use App\supplierInvoice;
use App\supplierInvoiceProduct;
use App\Support\ConsumableMonthEndInvoiceSupport;
use App\Support\SupplierMultiPoSupport;
use App\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AdminSupplierMultiPoController extends Controller
{
    public function indexConsumable(Request $request)
    {
        $search = $request->input('search');
        $supplierInvoices = supplierInvoice::with(['supplier', 'user.supplier', 'consumablePurchaseBill'])
            ->whereNotNull('mulitple_po')
            ->where('purchase_order_type', 'Consumable')
            ->when($search, function ($query, $search) {
                $query->where(function ($q) use ($search) {
                    $q->where('supplier_invoice_number', 'like', "%{$search}%")
                        ->orWhere('internal_invoice_number', 'like', "%{$search}%")
                        ->orWhere('mulitple_po', 'like', "%{$search}%");
                });
            })
            ->orderByDesc('created_at')
            ->paginate(50);

        return view('supplierInvoice.indexmultiConsumable', ['supplierInvoices' => $supplierInvoices]);
    }

    public function indexCarton(Request $request)
    {
        $search = $request->input('search');
        $supplierInvoices = supplierInvoice::with(['supplier', 'user.supplier', 'cartonPurchaseBill'])
            ->whereNotNull('mulitple_po')
            ->where('purchase_order_type', 'Carton')
            ->when($search, function ($query, $search) {
                $query->where(function ($q) use ($search) {
                    $q->where('supplier_invoice_number', 'like', "%{$search}%")
                        ->orWhere('internal_invoice_number', 'like', "%{$search}%")
                        ->orWhere('mulitple_po', 'like', "%{$search}%");
                });
            })
            ->orderByDesc('created_at')
            ->paginate(50);

        return view('supplierInvoice.indexmultiCarton', ['supplierInvoices' => $supplierInvoices]);
    }

    public function approveConsumable($id)
    {
        $supplierInvoice = supplierInvoice::findOrFail($id);
        if ($supplierInvoice->purchase_order_type !== 'Consumable' || empty($supplierInvoice->mulitple_po)) {
            return back()->with('error', 'Not a multi-PO consumable invoice.');
        }

        $poIds = SupplierMultiPoSupport::normalizeConsumablePoIds($supplierInvoice->purchase_order_id);
        $purchaseOrders = purchaseOrderConsumable::whereIn('id', $poIds)->get()->keyBy('id');
        $firstPO = $purchaseOrders->first();
        if (! $firstPO) {
            return back()->with('error', 'Purchase orders for this invoice could not be loaded.');
        }
        $supplier = supplier::find($firstPO->supplier_id);

        $supplierInvoiceProduct = supplierInvoiceProduct::where('supplier_invoice_id', $id)
            ->with(['consumable.unitType'])
            ->get();
        foreach ($supplierInvoiceProduct as $key => $sup) {
            $po = $purchaseOrders->get($sup->purchase_order_id) ?? purchaseOrderConsumable::find($sup->purchase_order_id);
            $supplierInvoiceProduct[$key]['potable'] = $po
                ? ConsumableMonthEndInvoiceSupport::resolvePotableForSupplierInvoiceLine($po, $sup)
                : null;
            $supplierInvoiceProduct[$key]['gstamount'] = $po
                ? ConsumableMonthEndInvoiceSupport::supplierInvoiceLineGstAmount(
                    $po,
                    $sup,
                    $supplierInvoiceProduct[$key]['potable']
                )
                : 0;
        }

        return view('supplierInvoice.create_via_suppinvoiceconsumable', [
            'supplierInvoice' => $supplierInvoice,
            'supplierInvoiceProduct' => $supplierInvoiceProduct,
            'purchaseOrder' => $firstPO,
            'allPurchaseOrders' => $purchaseOrders,
            'supplier' => $supplier,
            'typeUnit' => 2,
            'isMultiPo' => true,
            'multiPoNumbers' => SupplierMultiPoSupport::displayMultiPoNumbers($supplierInvoice),
            'approveUrl' => url('/supplierInvoice/approve/multi-consumable/' . $supplierInvoice->id),
        ]);
    }

    public function storeApproveConsumable(Request $request, $id)
    {
        $supplierInvoice = supplierInvoice::findOrFail($id);
        $poIds = SupplierMultiPoSupport::normalizeConsumablePoIds($supplierInvoice->purchase_order_id);

        $billAttrs = [
            'supplier_id' => $supplierInvoice->supplier_id,
            'ewaybill' => strtoupper((string) $request->ewaybill),
            'supp_inv_no' => strtoupper((string) $request->supp_inv_no),
            'supp_inv_date' => $request->supp_inv_date,
            'quantity' => $request->pbQty,
            'subtotal' => $request->pbSubTotal,
            'gst' => $request->pbGST,
            'freight' => $request->freight ?? 0,
            'total' => $request->pbTotal,
            'tdsTotal' => $request->tdsTotal ?? 0,
            'invoice_status' => 1,
            'supplier_invoice_id' => $supplierInvoice->id,
        ];
        SupplierMultiPoSupport::applyMultiPoPurchaseBillAttrs(
            $billAttrs,
            'purchase_bill_consumables',
            $poIds,
            $supplierInvoice->mulitple_po
        );

        $bill = purchaseBillConsumable::create($billAttrs);

        foreach ($request->pb ?? [] as $pb) {
            if ((float) ($pb['receiveqty'] ?? 0) <= 0) {
                continue;
            }

            $poid = (int) ($pb['poid'] ?? 0);
            $purchaseOrder = purchaseOrderConsumable::find($poid);
            if (! $purchaseOrder) {
                continue;
            }

            $pocLineId = ConsumableMonthEndInvoiceSupport::pocTableIdFromRequest($purchaseOrder, $pb);
            $poProduct = ConsumableMonthEndInvoiceSupport::resolvePocLine(
                $purchaseOrder,
                (int) $pb['product'],
                $pocLineId
            );
            $soProduct = ConsumableMonthEndInvoiceSupport::resolveSoLine(
                (int) $supplierInvoice->id,
                $poid,
                (int) $pb['product'],
                $pocLineId,
                $purchaseOrder
            );
            if (! $poProduct || ! $soProduct) {
                return redirect('/supplierInvoice/multi-consumable')->with(
                    'danger',
                    'Could not match approve line to purchase order / supplier invoice.'
                );
            }

            $lineAmount = (float) ($pb['amount'] ?? 0);
            if ($lineAmount <= 0) {
                $lineAmount = (float) ($pb['receiveqty'] ?? 0) * (float) ($pb['rate'] ?? 0);
            }

            $p = pbTableConsumable::create([
                'product_id' => $pb['product'],
                'purchaseOrder_id' => $poid,
                'purchaseBill_id' => $bill->id,
                'orderqty' => $poProduct->remqty + $soProduct->quantity,
                'receiveqty' => $pb['receiveqty'],
                'remainingqty' => $poProduct->remqty + $soProduct->quantity - $pb['receiveqty'],
                'rate' => $pb['rate'],
                'unit' => $pb['unit'] ?? null,
                'amount' => $lineAmount,
                'location' => $pb['location'] ?? null,
            ]);

            $poProduct->remqty = $p->remainingqty;
            $poProduct->save();

            $statusremqty = (float) pocTable::where('poid', $poid)->sum('remqty');
            $purchaseOrder->status = $statusremqty == 0 ? 1 : 0;
            $purchaseOrder->remqty = $statusremqty;
            $purchaseOrder->save();

            if ((int) $purchaseOrder->address_option !== 100) {
                $product = consumable::find($pb['product']);
                if ($product) {
                    $open_balance = $product->quantity;
                    $product->quantity += (float) $pb['receiveqty'];
                    $product->save();

                    $supplier = supplier::find($purchaseOrder->supplier_id);
                    stockLogConsumable::create([
                        'consumable_id' => $pb['product'],
                        'voucher_no' => strtoupper((string) $request->supp_inv_no),
                        'supplier_inv_no' => strtoupper((string) $request->supp_inv_no),
                        'ref_no' => $purchaseOrder->ref_supplier,
                        'quantity' => $pb['receiveqty'],
                        'opening_balance' => $open_balance,
                        'remaining_stock' => $product->quantity,
                        'type' => 1,
                        'supplier_name' => optional($supplier)->c_name,
                        'entity_id' => $bill->id,
                        'batch_balance' => $pb['receiveqty'],
                    ]);
                }
            }
        }

        $supplierInvoice->is_approved = 1;
        $supplierInvoice->save();

        return redirect('/supplierInvoice/multi-consumable')->with('success', 'Approved successfully.');
    }

    public function approveCarton($id)
    {
        $supplierInvoice = supplierInvoice::findOrFail($id);
        if ($supplierInvoice->purchase_order_type !== 'Carton' || empty($supplierInvoice->mulitple_po)) {
            return back()->with('error', 'Not a multi-PO carton invoice.');
        }
        if ((int) $supplierInvoice->is_approved === 1) {
            return back()->with('error', 'This carton invoice is already approved.');
        }

        $poIds = SupplierMultiPoSupport::normalizeConsumablePoIds($supplierInvoice->purchase_order_id);
        $purchaseOrders = purchaseOrderConsumable::whereIn('id', $poIds)
            ->with('supplier')
            ->get()
            ->keyBy('id');
        $firstPO = $purchaseOrders->first();
        if (! $firstPO) {
            return back()->with('error', 'Purchase orders for this invoice could not be loaded.');
        }
        $supplier = supplier::find($firstPO->supplier_id);

        $supplierInvoiceProduct = supplierInvoiceProduct::where('supplier_invoice_id', $id)
            ->with('product')
            ->get();
        foreach ($supplierInvoiceProduct as $key => $sup) {
            $supplierInvoiceProduct[$key]['potable'] = popTable::where('poid', $sup->purchase_order_id)
                ->where('product_id', $sup->product_id)
                ->first();
            $gstSlab = (float) ($supplierInvoiceProduct[$key]['potable']->gstslab ?? $sup->gst ?? 0);
            $supplierInvoiceProduct[$key]['gstamount'] = round((float) $sup->amount * $gstSlab / 100, 2);
        }

        $multiPoRemarks = $purchaseOrders
            ->map(function ($po) {
                $remark = trim((string) ($po->remarks ?? ''));
                if ($remark === '') {
                    return null;
                }

                return trim((string) $po->pono) !== ''
                    ? $po->pono . ': ' . $remark
                    : $remark;
            })
            ->filter()
            ->implode("\n");

        return view('supplierInvoice.create_via_suppinvoicecarton', [
            'supplierInvoicecar' => $supplierInvoice,
            'supplierInvoiceProduct' => $supplierInvoiceProduct,
            'purchaseOrder' => $firstPO,
            'allPurchaseOrders' => $purchaseOrders,
            'type' => 3,
            'id' => $firstPO->id,
            'userSplier' => $supplier,
            'isMultiPo' => true,
            'multiPoNumbers' => SupplierMultiPoSupport::displayMultiPoNumbers($supplierInvoice),
            'multiPoRemarks' => $multiPoRemarks,
            'approveUrl' => url('/supplierInvoice/approve/multi-carton/' . $supplierInvoice->id),
        ]);
    }

    public function storeApproveCarton(Request $request, $id)
    {
        $request->validate([
            'supp_inv_no' => 'required|string',
            'invoice_date' => 'required|date',
            'ewaybill' => 'nullable|string',
            'tdsTotal' => 'nullable|numeric',
            'pb' => 'required|array|min:1',
            'pb.*.product' => 'required',
            'pb.*.poid' => 'required|integer',
            'pb.*.receiveqty_box_1' => 'nullable|numeric|min:0',
            'pb.*.receiveqty_box_2' => 'nullable|numeric|min:0',
        ]);

        $supplierInvoice = supplierInvoice::findOrFail($id);
        if ($supplierInvoice->purchase_order_type !== 'Carton' || empty($supplierInvoice->mulitple_po)) {
            return back()->with('error', 'Not a multi-PO carton invoice.');
        }
        if ((int) $supplierInvoice->is_approved === 1) {
            return back()->with('error', 'This carton invoice is already approved.');
        }

        $poIds = SupplierMultiPoSupport::normalizeConsumablePoIds($supplierInvoice->purchase_order_id);

        $supplier = supplier::find($supplierInvoice->supplier_id);
        if (! $supplier) {
            return back()->with('error', 'Supplier not found for this invoice.');
        }

        $hasReceipt = false;
        $touchedPoIds = [];

        try {
            DB::transaction(function () use ($request, $supplierInvoice, $poIds, $supplier, &$hasReceipt, &$touchedPoIds) {
                $billSubTotal = 0.0;
                $billGst = 0.0;
                $billQty = 0.0;

                $billAttrs = [
                    'supplier_id' => $supplierInvoice->supplier_id,
                    'ewaybill' => strtoupper((string) ($request->ewaybill ?? '')),
                    'supp_inv_no' => strtoupper((string) $request->supp_inv_no),
                    'supp_inv_date' => $request->invoice_date,
                    'quantity' => 0,
                    'subtotal' => 0,
                    'gst' => 0,
                    'total' => 0,
                    'tdsTotal' => $request->tdsTotal ?? 0,
                    'invoice_status' => 1,
                    'supplier_invoice_id' => $supplierInvoice->id,
                ];
                SupplierMultiPoSupport::applyMultiPoPurchaseBillAttrs(
                    $billAttrs,
                    'purchase_bill_carton',
                    $poIds,
                    $supplierInvoice->mulitple_po
                );

                $bill = PurchaseBillCarton::create($billAttrs);

                foreach ($request->pb as $pb) {
                    $r1 = (float) ($pb['receiveqty_box_1'] ?? 0);
                    $r2 = (float) ($pb['receiveqty_box_2'] ?? 0);
                    if ($r1 <= 0 && $r2 <= 0) {
                        continue;
                    }
                    $hasReceipt = true;

                    $poid = (int) ($pb['poid'] ?? 0);
                    $productId = (int) ($pb['product'] ?? 0);
                    $purchaseOrder = purchaseOrderConsumable::find($poid);
                    if (! $purchaseOrder) {
                        throw new \RuntimeException('Purchase order not found for one of the carton lines.');
                    }

                    $poProduct = popTable::where('poid', $poid)->where('product_id', $productId)->first();
                    $soProduct = soTable::where('supplier_invoice_id', $supplierInvoice->id)
                        ->where('purchase_order_id', $poid)
                        ->where('product_id', $productId)
                        ->first();

                    if (! $poProduct || ! $soProduct) {
                        throw new \RuntimeException('Could not match carton line to PO / supplier invoice.');
                    }

                    $orderqty = (float) $poProduct->remqty_box1 + (float) $soProduct->quantity;
                    $orderqty2 = (float) $poProduct->remqty_box2 + (float) $soProduct->quantity2;
                    if ($r1 > $orderqty || $r2 > $orderqty2) {
                        throw new \RuntimeException('Receive quantity cannot exceed available carton quantity.');
                    }

                    $remainingqty = $orderqty - $r1;
                    $remainingqty2 = $orderqty2 - $r2;

                    $rate1 = (float) ($poProduct->box1_rate ?? 0);
                    $rate2 = (float) ($poProduct->box2_rate ?? 0);
                    $lineAmount = round(($rate1 * $r1) + ($rate2 * $r2), 2);
                    $gstSlab = (float) ($soProduct->gst ?? $poProduct->gstslab ?? 0);
                    $lineGst = round(($lineAmount * $gstSlab) / 100, 2);
                    $billSubTotal += $lineAmount;
                    $billGst += $lineGst;
                    $billQty += $r1 + $r2;

                    PbTableCorton::create([
                        'product_id' => $productId,
                        'purchaseOrder_id' => $poid,
                        'purchaseBill_id' => $bill->id,
                        'orderqty' => $orderqty,
                        'orderqty2' => $orderqty2,
                        'receiveqty' => $r1,
                        'receiveqty2' => $r2,
                        'remainingqty' => $remainingqty,
                        'remainingqty2' => $remainingqty2,
                        'rate' => $rate1,
                        'rate2' => $rate2,
                        'amount' => $lineAmount,
                    ]);

                    $poProduct->remqty_box1 = $remainingqty;
                    $poProduct->remqty_box2 = $remainingqty2;
                    $poProduct->save();

                    $touchedPoIds[$poid] = true;

                    $product = packaging::where('product_id', $productId)->first();
                    if ($product) {
                        $open_balance = (float) $product->box_1_qty;
                        $open_balance2 = (float) $product->box_2_qty;
                        $product->box_1_qty = $open_balance + $r1;
                        $product->box_2_qty = $open_balance2 + $r2;
                        $product->save();

                        if ((int) $purchaseOrder->address_option !== 100) {
                            StockLogCarton::create([
                                'product_id' => $productId,
                                'voucher_no' => strtoupper((string) $request->supp_inv_no),
                                'supplier_inv_no' => strtoupper((string) $request->supp_inv_no),
                                'ref_no' => $purchaseOrder->ref_supplier,
                                'quantity' => $r1,
                                'quantity2' => $r2,
                                'opening_balance' => $open_balance,
                                'opening_balance2' => $open_balance2,
                                'remaining_stock' => $product->box_1_qty,
                                'remaining_stock2' => $product->box_2_qty,
                                'type' => 1,
                                'supplier_name' => $supplier->c_name,
                                'entity_id' => $bill->id,
                            ]);
                        }
                    }
                }

                if (! $hasReceipt) {
                    throw new \RuntimeException('Enter a receive quantity on at least one carton line.');
                }

                $billSubTotal = round($billSubTotal, 2);
                $billGst = round($billGst, 2);
                $bill->quantity = $billQty;
                $bill->subtotal = $billSubTotal;
                $bill->gst = $billGst;
                $bill->total = round($billSubTotal + $billGst, 2);
                $bill->save();

                foreach (array_keys($touchedPoIds) as $poid) {
                    $purchaseOrder = purchaseOrderConsumable::find($poid);
                    if (! $purchaseOrder) {
                        continue;
                    }

                    $statusremqty = (float) popTable::where('poid', $poid)->sum('remqty_box1');
                    $statusremqty2 = (float) popTable::where('poid', $poid)->sum('remqty_box2');
                    $purchaseOrder->status = ($statusremqty == 0.0 && $statusremqty2 == 0.0) ? 1 : 0;
                    $purchaseOrder->remqty = $statusremqty;
                    $purchaseOrder->save();
                }

                $supplierInvoice->is_approved = 1;
                $supplierInvoice->save();
            });
        } catch (\Throwable $e) {
            return back()->with('error', $e->getMessage())->withInput();
        }

        return redirect('/supplierInvoice/multi-carton')->with('success', 'Approved successfully.');
    }
}
