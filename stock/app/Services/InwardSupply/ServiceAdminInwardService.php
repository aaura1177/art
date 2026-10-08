<?php

namespace App\Services\InwardSupply;

use App\Service;
use App\ServicePbTable;
use App\serviceProductTable;
use App\servicePurchaseBill;
use App\serviceTable;
use App\Services\ServiceInvoiceQuantityService;
use App\SubServicePbTable;
use App\SubServiceProductTable;
use App\SubServiceTable;
use App\supplier;
use App\supplierServiceInvoice;
use App\User;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class ServiceAdminInwardService
{
    public static function existingServiceInvoiceConflict(string $suppInvNo, string $ewayBill): bool
    {
        $suppInvNo = strtoupper(trim($suppInvNo));
        $ewayBill = strtoupper(trim($ewayBill));

        $query = supplierServiceInvoice::query()
            ->where(function ($q) use ($suppInvNo, $ewayBill) {
                $q->where('supplier_invoice_number', $suppInvNo);
                if ($ewayBill !== '') {
                    $q->orWhere('eway_bill_no', $ewayBill);
                }
            })
            ->where(function ($q) {
                $q->where('status', '!=', 2)->orWhereNull('status');
            });

        return $query->exists();
    }

    public function execute(Request $request): RedirectResponse
    {
        $request->validate([
            'purchaseOrder_id' => 'required|integer',
            'supp_inv_no' => 'required|string',
            'supp_inv_date' => 'required|date',
            'ewaybill' => 'nullable|string',
            'pbQty' => 'nullable|numeric',
            'pbSubTotal' => 'nullable|numeric',
            'pbGST' => 'nullable|numeric',
            'freight' => 'nullable|numeric',
            'pbTotal' => 'nullable|numeric',
            'tdsTotal' => 'nullable|numeric',
            'pb' => 'required|array',
            'pb.*.product' => 'required',
        ]);

        $po = Service::find($request['purchaseOrder_id']);
        if (!$po) {
            return back()->with('error', 'Purchase order not found.')->withInput();
        }
        if ((int) $po->status === 1) {
            return back()->with('error', 'This purchase order is complete.')->withInput();
        }

        $suppInvNo = strtoupper(trim($request['supp_inv_no']));
        $eway = strtoupper(trim((string) ($request['ewaybill'] ?? '')));

        if (self::existingServiceInvoiceConflict($suppInvNo, $eway)) {
            return back()->with('error', 'Supplier Invoice No. or E-Way Bill No. already exists.')->withInput();
        }

        ServiceInvoiceQuantityService::syncAllForPo((int) $po->id);
        $po->refresh();

        $lines = $request['pb'];
        $hasReceipt = false;

        foreach ($lines as $key => $pb) {
            $unit = $pb['unit'] ?? '';
            $poProduct = serviceTable::where('poid', $po->id)->where('product_id', $pb['product'])->first();
            if (!$poProduct) {
                return back()->with('error', 'Purchase order line not found for one of the products.')->withInput();
            }

            $poSubs = $request['po'][$key]['sub_name'] ?? null;
            if (is_array($poSubs) && count($poSubs) > 0) {
                foreach ($poSubs as $index => $name) {
                    $subPo = !empty($request['po'][$key]['id'][$index])
                        ? SubServiceTable::where('id', $request['po'][$key]['id'][$index])->first()
                        : SubServiceTable::where('serviceTable_id', $poProduct->id)->where('sub_name', $name)->first();

                    if (!$subPo) {
                        continue;
                    }

                    if ($unit === 'Hours') {
                        $subQty = round((float) ($request['po'][$key]['sub_quantity'][$index] ?? 0), 2);
                        if ($subQty <= 0) {
                            continue;
                        }
                        if ($subQty > (float) $subPo->sub_remqty + 0.0001) {
                            return back()->withInput()->with(
                                'danger',
                                "Qty for {$name} cannot exceed remaining quantity ({$subPo->sub_remqty})."
                            );
                        }
                        $hasReceipt = true;
                    } else {
                        $subPct = round((float) ($request['po'][$key]['sub_percentage'][$index] ?? 0), 2);
                        if ($subPct <= 0) {
                            continue;
                        }
                        if ($subPct > round((float) $subPo->sub_remaining_percentage, 2) + 0.0001) {
                            return back()->withInput()->with(
                                'danger',
                                "Percentage for {$name} cannot exceed remaining percentage ({$subPo->sub_remaining_percentage}%)."
                            );
                        }
                        $hasReceipt = true;
                    }
                }
                continue;
            }

            if ($unit === 'Hours') {
                $qty = round((float) ($pb['receiveqty'] ?? 0), 2);
                if ($qty <= 0) {
                    continue;
                }
                if ($qty > (float) $poProduct->remqty + 0.0001) {
                    return back()->withInput()->with('danger', 'Receive quantity cannot exceed remaining PO quantity.');
                }
                $hasReceipt = true;
            } elseif ($unit === 'Count') {
                $pct = round((float) ($pb['remaining_percentage'] ?? 0), 2);
                if ($pct <= 0) {
                    continue;
                }
                if ($pct > round((float) $poProduct->remaining_percentage, 2) + 0.0001) {
                    return back()->withInput()->with('danger', 'Percentage cannot exceed remaining percentage.');
                }
                $hasReceipt = true;
            }
        }

        if (!$hasReceipt) {
            return back()->with('error', 'Enter a receive quantity or percentage on at least one line.')->withInput();
        }

        $supplier = supplier::find($po->supplier_id);
        if (!$supplier) {
            return back()->with('error', 'Supplier not found for this purchase order.')->withInput();
        }

        $billSubtotal = round((float) ($request['pbSubTotal'] ?? 0), 2);
        $billGst = round((float) ($request['pbGST'] ?? 0), 2);
        $billTotal = round($billSubtotal + $billGst, 2);
        $billQty = round((float) ($request['pbQty'] ?? 0), 2);
        $tdsTotal = AdminInwardSupport::roundTds($request['tdsTotal'] ?? null);

        DB::transaction(function () use ($request, $po, $supplier, $suppInvNo, $eway, $lines, $billSubtotal, $billGst, $billTotal, $billQty, $tdsTotal) {
            $carbonDate = Carbon::parse($request['supp_inv_date']);
            $batchMonth = $carbonDate->format('m') . $carbonDate->format('y');

            $supplierUser = User::where('supplier_id', $supplier->id)->first();
            $userId = $supplierUser ? $supplierUser->id : auth()->id();

            $supplierInvoice = supplierServiceInvoice::create([
                'purchase_order_id' => $po->id,
                'supplier_invoice_number' => $suppInvNo,
                'eway_bill_no' => $eway,
                'vehicle_no' => '',
                'eway_bill_pdf' => '',
                'user_id' => $userId,
                'tamount' => $billTotal,
                'purchase_order_type' => 'Furniture',
                'invoice_date' => $request['supp_inv_date'],
                'tquantity' => $billQty,
                'subTotal' => $billSubtotal,
                'tgst' => $billGst,
                'tdsTotal' => $tdsTotal,
            ]);
            $supplierInvoice->is_approved = 1;
            $supplierInvoice->batch_no = $supplier->short_name . $batchMonth . rand(1000, 9999);
            $supplierInvoice->internal_invoice_number = 'GV-' . date('Y') . '-' . $supplierInvoice->id;
            $supplierInvoice->save();

            $bill = servicePurchaseBill::create([
                'purchaseOrder_id' => $po->id,
                'ewaybill' => $eway,
                'supp_inv_no' => $suppInvNo,
                'supp_inv_date' => $request['supp_inv_date'],
                'quantity' => $billQty,
                'subtotal' => $billSubtotal,
                'gst' => $billGst,
                'freight' => round((float) ($request['freight'] ?? 0), 2),
                'total' => $billTotal,
                'tdsTotal' => $tdsTotal ?? round((float) ($request['tdsTotal'] ?? 0), 2),
                'invoice_status' => 1,
                'supplier_invoice_id' => $supplierInvoice->id,
            ]);

            foreach ($lines as $key => $pb) {
                $unit = $pb['unit'] ?? '';
                $amount = round((float) ($pb['amount'] ?? 0), 2);
                $gstSlab = round((float) ($pb['gstslab'] ?? 0), 2);
                $gstAmount = round((float) ($pb['gstamount'] ?? 0), 2);

                if ($unit === 'Hours' && $amount <= 0 && empty($request['po'][$key]['sub_name'])) {
                    continue;
                }
                if ($unit === 'Count' && $amount <= 0) {
                    continue;
                }

                $poProduct = serviceTable::where('poid', $po->id)->where('product_id', $pb['product'])->first();
                if (!$poProduct) {
                    continue;
                }

                $invoiceLine = serviceProductTable::create([
                    'product_id' => $pb['product'],
                    'supplier_invoice_id' => $supplierInvoice->id,
                    'purchase_order_id' => $po->id,
                    'unit' => $unit,
                    'gst' => $gstSlab,
                    'amount' => $amount,
                    'total' => round($amount + $gstAmount, 2),
                ]);

                if ($unit === 'Hours') {
                    $invoiceLine->quantity = round((float) ($pb['receiveqty'] ?? 0), 2);
                } else {
                    $invoiceLine->quantity = 1;
                    $invoiceLine->percentage = round((float) ($pb['remaining_percentage'] ?? 0), 2);
                }
                $invoiceLine->save();

                $receiveQty = $unit === 'Hours' ? round((float) ($pb['receiveqty'] ?? 0), 2) : 0;

                $pbLine = ServicePbTable::create([
                    'product_id' => $pb['product'],
                    'purchaseOrder_id' => $po->id,
                    'purchaseBill_id' => $bill->id,
                    'orderqty' => (float) $poProduct->remqty + $receiveQty,
                    'receiveqty' => $unit === 'Hours' ? $receiveQty : null,
                    'receivepercentage' => $unit === 'Count' ? round((float) ($pb['remaining_percentage'] ?? 0), 2) : null,
                    'remainingqty' => max(0, (float) $poProduct->remqty - $receiveQty),
                    'rate' => round((float) ($pb['rate'] ?? 0), 2),
                    'unit' => $unit,
                    'amount' => $amount,
                    'location' => $pb['location'] ?? '',
                ]);

                if (isset($request['po'][$key]['sub_name'], $request['po'][$key]['sub_rate'])) {
                    foreach ($request['po'][$key]['sub_name'] as $index => $name) {
                        if (!isset($request['po'][$key]['sub_rate'][$index])) {
                            continue;
                        }

                        $subAmount = round((float) ($request['po'][$key]['sub_amount'][$index] ?? 0), 2);
                        if ($subAmount <= 0) {
                            continue;
                        }

                        SubServiceProductTable::create([
                            'supplier_invoice_id' => $supplierInvoice->id,
                            'supplier_invoice_product_id' => $invoiceLine->id,
                            'product_id' => $invoiceLine->product_id,
                            'sub_name' => $name,
                            'sub_rate' => round((float) ($request['po'][$key]['sub_rate'][$index] ?? 0), 2),
                            'sub_amount' => $subAmount,
                            'sub_gstamount' => round((float) ($request['po'][$key]['sub_gstamount'][$index] ?? 0), 2),
                            'sub_percentage' => round((float) ($request['po'][$key]['sub_percentage'][$index] ?? 0), 2),
                            'sub_quantity' => round((float) ($request['po'][$key]['sub_quantity'][$index] ?? 0), 2),
                            'sub_remqty' => 0,
                            'sub_remaining_percentage' => 0,
                            'sub_remaining_amount' => 0,
                        ]);

                        SubServicePbTable::create([
                            'purchase_bill_id' => $bill->id,
                            'pb_id' => $pbLine->id,
                            'product_id' => $pbLine->product_id,
                            'sub_name' => $name,
                            'sub_receiveqty' => $request['po'][$key]['sub_quantity'][$index] ?? 0,
                            'sub_receivepercentage' => $request['po'][$key]['sub_percentage'][$index] ?? 0,
                            'sub_rate' => round((float) ($request['po'][$key]['sub_rate'][$index] ?? 0), 2),
                            'sub_amount' => $subAmount,
                            'sub_gstslab' => round((float) ($request['po'][$key]['sub_gstslab'][$index] ?? $gstSlab), 2),
                            'sub_gstamount' => round((float) ($request['po'][$key]['sub_gstamount'][$index] ?? 0), 2),
                        ]);
                    }
                }
            }

            ServiceInvoiceQuantityService::syncAllForPo((int) $po->id);

            SubServiceProductTable::where('supplier_invoice_id', $supplierInvoice->id)->get()->each(function ($line) use ($po) {
                $sub = SubServiceTable::where('po_id', $po->id)
                    ->where('product_id', $line->product_id)
                    ->where('sub_name', $line->sub_name)
                    ->first();
                if ($sub) {
                    ServiceInvoiceQuantityService::snapshotSubRemainingOnInvoiceLine($line, $sub);
                }
            });
        });

        return redirect('/purchaseBill/service/condition')->with('success', 'Service inward supply saved successfully.');
    }
}
