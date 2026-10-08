<?php

namespace App\Http\Controllers;

use App\consumable;
use App\invoice;
use App\ManualStockoutPendingCarton;
use App\ManualStockoutPendingConsumable;
use App\packaging;
use App\ProductCarton;
use App\stockLogConsumable;
use App\stockoutTable;
use App\stockoutTableConsumable;
use App\StockLogCarton;
use App\StockOutTableCarton;
use App\Support\ManualStockoutPendingAccess;
use App\Support\ManualStockoutPendingViewData;
use App\WfConsumable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * POST: partial / full pending fulfillment from manual detail pages.
 */
class ManualStockoutPendingController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth', '2fa']);
    }

    public function fulfillConsumable(Request $request)
    {
        $data = $request->validate([
            'pending_id' => 'required|integer|exists:manual_stockout_pending_consumables,id',
            'quantity' => 'nullable|numeric|min:0.0001',
        ]);

        return DB::transaction(function () use ($data, $request) {
            /** @var ManualStockoutPendingConsumable $pending */
            $pending = ManualStockoutPendingConsumable::query()
                ->where('id', (int) $data['pending_id'])
                ->where('status', 0)
                ->lockForUpdate()
                ->firstOrFail();

            $inv = invoice::query()->findOrFail((int) $pending->invoice_id);
            if (! ManualStockoutPendingAccess::canFulfillPendingForInvoice($inv)) {
                return redirect()
                    ->route('consumable.manual_pending_detail', ['invoice' => $inv->id])
                    ->with('error', ManualStockoutPendingAccess::lockReason($inv) ?? 'Fulfillment is not allowed for this invoice.');
            }

            $stillNeed = ManualStockoutPendingViewData::consumableStillNeedFromReason((string) $pending->reason);
            if ($stillNeed === null || $stillNeed <= 0.00001) {
                return redirect()
                    ->route('consumable.manual_pending_detail', ['invoice' => $inv->id])
                    ->with('error', 'Could not read remaining quantity from this pending row.');
            }

            $consumable = consumable::query()->lockForUpdate()->find((int) $pending->consumable_id);
            if (! $consumable) {
                return redirect()
                    ->route('consumable.manual_pending_detail', ['invoice' => $inv->id])
                    ->with('error', 'Consumable master record still missing.');
            }

            $open = (float) ($consumable->quantity ?? 0);
            $maxTake = min($stillNeed, max(0.0, $open));
            if ($maxTake <= 0.00001) {
                return redirect()
                    ->route('consumable.manual_pending_detail', ['invoice' => $inv->id])
                    ->with('error', 'No consumable stock available to issue (available: ' . $open . ', need: ' . $stillNeed . ').');
            }

            $reqQty = $request->filled('quantity') ? (float) $request->input('quantity') : $maxTake;
            $take = min($maxTake, max(0.0, $reqQty));
            if ($take <= 0.00001) {
                return redirect()
                    ->route('consumable.manual_pending_detail', ['invoice' => $inv->id])
                    ->with('error', 'Enter a positive quantity up to ' . $maxTake . '.');
            }

            $productId = (int) $pending->product_id;

            if ($productId > 0) {
                $st = stockoutTable::query()
                    ->where('stock_id', (int) $pending->stock_id)
                    ->where('product_id', $productId)
                    ->first();
                if (! $st) {
                    return redirect()
                        ->route('consumable.manual_pending_detail', ['invoice' => $inv->id])
                        ->with('error', 'Stockout line not found for this product.');
                }

                $remByProduct = $inv->invoiceTable()->get()->keyBy('product_id');
                $remQty = (float) (optional($remByProduct->get($productId))->remqty ?? 0);

                $wf = WfConsumable::query()
                    ->where('product_id', $productId)
                    ->where('consumables_id', (int) $pending->consumable_id)
                    ->first();
                $perUnit = $wf ? (float) ($wf->qty ?? 0) : 0.0;
                $remainingConsumableQty = $perUnit * $remQty;

                stockoutTableConsumable::create([
                    'invoice_id' => (int) $pending->invoice_id,
                    'consumable_id' => $consumable->id,
                    'stock_id' => (int) $pending->stock_id,
                    'orderqty' => $remainingConsumableQty,
                    'receiveqty' => $take,
                    'remainingqty' => 0,
                    'location' => $pending->location ?? $st->location ?? null,
                ]);

                stockLogConsumable::create([
                    'product_id' => $productId,
                    'consumable_id' => $consumable->id,
                    'voucher_no' => $inv->invoiceno,
                    'ref_no' => 'Stock Out - Buyer Order No. - ' . $inv->buyerorderno,
                    'quantity' => $take,
                    'opening_balance' => $open,
                    'remaining_stock' => $open - $take,
                    'type' => 2,
                    'remark' => 'Sell (pending fulfillment)',
                ]);
            } else {
                stockLogConsumable::create([
                    'consumable_id' => $consumable->id,
                    'voucher_no' => $inv->invoiceno,
                    'ref_no' => 'Stock Out - Buyer Order No. - ' . $inv->buyerorderno,
                    'quantity' => $take,
                    'opening_balance' => $open,
                    'remaining_stock' => $open - $take,
                    'type' => 2,
                    'remark' => 'Container Stock out (pending fulfillment)',
                ]);
            }

            $consumable->quantity = $open - $take;
            $consumable->save();

            $left = $stillNeed - $take;
            if ($left > 0.00001) {
                $pending->reason = 'Insufficient consumable quantity — still need: ' . round($left, 4);
                $pending->save();
            } else {
                $pending->status = 1;
                $pending->reason = 'Fulfilled';
                $pending->save();
            }

            return redirect()
                ->route('consumable.manual_pending_detail', ['invoice' => $inv->id])
                ->with('success', 'Issued ' . $take . ' of consumable for invoice ' . ($inv->invoiceno ?? $inv->id) . '.');
        });
    }

    public function fulfillCarton(Request $request)
    {
        $data = $request->validate([
            'pending_id' => 'required|integer|exists:manual_stockout_pending_cartons,id',
            'qty_box1' => 'nullable|numeric|min:0',
            'qty_box2' => 'nullable|numeric|min:0',
        ]);

        return DB::transaction(function () use ($data, $request) {
            /** @var ManualStockoutPendingCarton $pending */
            $pending = ManualStockoutPendingCarton::query()
                ->where('id', (int) $data['pending_id'])
                ->where('status', 0)
                ->lockForUpdate()
                ->firstOrFail();

            $inv = invoice::query()->findOrFail((int) $pending->invoice_id);
            if (! ManualStockoutPendingAccess::canFulfillPendingForInvoice($inv)) {
                return redirect()
                    ->route('carton.manual_invoice_count', ['containerno' => $inv->containerno])
                    ->with('error', ManualStockoutPendingAccess::lockReason($inv) ?? 'Fulfillment is not allowed for this invoice.');
            }

            $productId = (int) $pending->product_id;
            if ($productId <= 0) {
                return redirect()
                    ->route('carton.manual_invoice_count', ['containerno' => $inv->containerno])
                    ->with('error', 'Invalid product on pending carton row.');
            }

            $short1 = $pending->short_qty_box1 !== null ? (float) $pending->short_qty_box1 : 0.0;
            $short2 = $pending->short_qty_box2 !== null ? (float) $pending->short_qty_box2 : 0.0;
            if ($short1 <= 0.00001 && $short2 <= 0.00001) {
                [$short1, $short2] = ManualStockoutPendingViewData::cartonShortFromReason((string) $pending->reason);
            }

            if ($short1 <= 0.00001 && $short2 <= 0.00001) {
                return redirect()
                    ->route('carton.manual_invoice_count', ['containerno' => $inv->containerno])
                    ->with('error', 'No remaining carton short quantity on this row.');
            }

            $packaging = packaging::query()->where('product_id', $productId)->lockForUpdate()->first();
            if (! $packaging) {
                return redirect()
                    ->route('carton.manual_invoice_count', ['containerno' => $inv->containerno])
                    ->with('error', 'Packaging record missing for this product.');
            }

            $avail1 = (float) ($packaging->box_1_qty ?? 0);
            $avail2 = (float) ($packaging->box_2_qty ?? 0);

            $cap1 = $short1 > 0.00001 ? min($short1, max(0.0, $avail1)) : 0.0;
            $cap2 = $short2 > 0.00001 ? min($short2, max(0.0, $avail2)) : 0.0;

            $filled1 = $request->filled('qty_box1');
            $filled2 = $request->filled('qty_box2');

            if (! $filled1 && ! $filled2) {
                $take1 = $cap1;
                $take2 = $cap2;
            } else {
                $take1 = $filled1 ? min($cap1, max(0.0, (float) $request->input('qty_box1'))) : 0.0;
                $take2 = $filled2 ? min($cap2, max(0.0, (float) $request->input('qty_box2'))) : 0.0;
            }

            if ($take1 <= 0.00001 && $take2 <= 0.00001) {
                return redirect()
                    ->route('carton.manual_invoice_count', ['containerno' => $inv->containerno])
                    ->with('error', 'Nothing to issue: enter quantities for box 1 / box 2 (or leave both empty to issue maximum for each short box).');
            }

            $stockId = (int) $pending->stock_id;

            $st = stockoutTable::query()
                ->where('stock_id', $stockId)
                ->where('product_id', $productId)
                ->first();
            if (! $st) {
                return redirect()
                    ->route('carton.manual_invoice_count', ['containerno' => $inv->containerno])
                    ->with('error', 'Stockout line not found for this product.');
            }

            $remByProduct = $inv->invoiceTable()->get()->keyBy('product_id');
            $remQty = (float) (optional($remByProduct->get($productId))->remqty ?? 0);

            $pc = ProductCarton::where('product_id', $productId)->first();
            $q1 = $pc ? (float) ($pc->quantity1 ?? 0) : 0.0;
            $q2 = $pc ? (float) ($pc->quantity2 ?? 0) : 0.0;
            $remainingpackagingQty = $q1 * $remQty;
            $remainingpackagingQty2 = $q2 * $remQty;

            $open1 = $avail1;
            $open2 = $avail2;

            $row = StockOutTableCarton::query()
                ->where('stock_id', $stockId)
                ->where('product_id', $productId)
                ->lockForUpdate()
                ->first();

            if ($row) {
                $row->receiveqty = (float) ($row->receiveqty ?? 0) + $take1;
                $row->receiveqty2 = (float) ($row->receiveqty2 ?? 0) + $take2;
                $row->orderqty = $remainingpackagingQty;
                $row->orderqty2 = $remainingpackagingQty2;
                $row->location = $pending->location ?? $row->location ?? $st->location;
                $row->save();
            } else {
                StockOutTableCarton::create([
                    'product_id' => $productId,
                    'stock_id' => $stockId,
                    'orderqty' => $remainingpackagingQty,
                    'orderqty2' => $remainingpackagingQty2,
                    'receiveqty' => $take1,
                    'receiveqty2' => $take2,
                    'remainingqty' => 0,
                    'remainingqty2' => 0,
                    'location' => $pending->location ?? $st->location ?? null,
                ]);
            }

            $packaging->box_1_qty = $avail1 - $take1;
            $packaging->box_2_qty = $avail2 - $take2;
            $packaging->save();

            StockLogCarton::create([
                'product_id' => $productId,
                'voucher_no' => $inv->invoiceno,
                'ref_no' => 'Stock Out - Buyer Order No. - ' . $inv->buyerorderno,
                'quantity' => $take1,
                'quantity2' => $take2,
                'opening_balance' => $open1,
                'opening_balance2' => $open2,
                'remaining_stock' => $packaging->box_1_qty,
                'remaining_stock2' => $packaging->box_2_qty,
                'type' => 2,
            ]);

            $newShort1 = max(0.0, $short1 - $take1);
            $newShort2 = max(0.0, $short2 - $take2);

            if ($newShort1 > 0.00001 || $newShort2 > 0.00001) {
                $reasonParts = [];
                if ($newShort1 > 0.00001) {
                    $reasonParts[] = 'Box 1 short by ' . round($newShort1, 4);
                }
                if ($newShort2 > 0.00001) {
                    $reasonParts[] = 'Box 2 short by ' . round($newShort2, 4);
                }
                $pending->short_qty_box1 = $newShort1 > 0.00001 ? $newShort1 : null;
                $pending->short_qty_box2 = $newShort2 > 0.00001 ? $newShort2 : null;
                $pending->reason = implode('; ', $reasonParts);
                $pending->save();
            } else {
                $pending->status = 1;
                $pending->reason = 'Fulfilled';
                $pending->short_qty_box1 = null;
                $pending->short_qty_box2 = null;
                $pending->save();
            }

            return redirect()
                ->route('carton.manual_invoice_count', ['containerno' => $inv->containerno])
                ->with('success', 'Issued carton stock (box1: ' . $take1 . ', box2: ' . $take2 . ') for invoice ' . ($inv->invoiceno ?? $inv->id) . '.');
        });
    }
}
