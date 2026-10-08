<?php

namespace App\Support;

use App\consumable as ConsumableModel;
use App\invoice;
use App\ManualStockoutPendingCarton;
use App\ManualStockoutPendingConsumable;
use App\packaging;
use App\ProductCarton;
use App\stockoutTable;
use App\WfConsumable;
use Illuminate\Support\Collection;

class ManualStockoutPendingPresenter
{
    public static function consumableOpenInvoiceSummaries(?string $search): Collection
    {
        $rows = ManualStockoutPendingConsumable::query()
            ->where('status', 0)
            ->with(['invoice.buyer'])
            ->orderByDesc('id')
            ->get();

        return self::groupConsumableSummaries($rows, $search, false);
    }

    public static function consumableCompletedInvoiceSummaries(?string $search): Collection
    {
        $openIds = ManualStockoutPendingConsumable::query()
            ->where('status', 0)
            ->pluck('invoice_id')
            ->unique()
            ->all();

        $rows = ManualStockoutPendingConsumable::query()
            ->where('status', 1)
            ->when(count($openIds) > 0, fn ($q) => $q->whereNotIn('invoice_id', $openIds))
            ->with(['invoice.buyer'])
            ->orderByDesc('updated_at')
            ->orderByDesc('id')
            ->get();

        return self::groupConsumableSummaries($rows, $search, true);
    }

    private static function groupConsumableSummaries(Collection $rows, ?string $search, bool $completed): Collection
    {
        $byInvoice = $rows->groupBy('invoice_id');
        $out = collect();

        foreach ($byInvoice as $invoiceId => $group) {
            /** @var invoice|null $inv */
            $inv = $group->first()->invoice;
            if (! $inv) {
                continue;
            }

            if ($search !== null && $search !== '') {
                $q = strtolower(trim($search));
                $buyer = $inv->buyer;
                $buyerHay = $buyer
                    ? strtolower(trim((string) ($buyer->name ?? $buyer->c_name ?? '')))
                    : '';
                $hay = strtolower(
                    (string) ($inv->invoiceno ?? '')
                    . ' ' . (string) ($inv->containerno ?? '')
                    . ' ' . (string) ($inv->buyerorderno ?? '')
                    . ' ' . $buyerHay
                );
                if ($q !== '' && ! str_contains($hay, $q)) {
                    continue;
                }
            }

            $stillSum = 0.0;
            $stillCount = 0;
            foreach ($group as $p) {
                $sn = ManualStockoutPendingViewData::consumableStillNeedFromReason((string) $p->reason);
                if ($sn !== null) {
                    $stillSum += $sn;
                    $stillCount++;
                }
            }

            $canFulfill = ! $completed && ManualStockoutPendingAccess::canFulfillPendingForInvoice($inv);

            $out->push([
                'invoice' => $inv,
                'container' => (string) ($inv->containerno ?? ''),
                'pending_line_count' => $group->count(),
                'total_still_need' => $stillCount > 0 ? round($stillSum, 4) : null,
                'completed' => $completed,
                'can_open_detail' => ! $completed && $canFulfill,
                'lock_reason' => ! $completed ? ManualStockoutPendingAccess::lockReason($inv) : null,
                'max_pending_id' => $group->max('id'),
            ]);
        }

        return $out->sortByDesc('max_pending_id')->values();
    }

    public static function cartonOpenInvoiceSummaries(?string $search): Collection
    {
        $rows = ManualStockoutPendingCarton::query()
            ->where('status', 0)
            ->with(['invoice.buyer'])
            ->orderByDesc('id')
            ->get();

        return self::groupCartonSummaries($rows, $search, false);
    }

    public static function cartonCompletedInvoiceSummaries(?string $search): Collection
    {
        $openIds = ManualStockoutPendingCarton::query()
            ->where('status', 0)
            ->pluck('invoice_id')
            ->unique()
            ->all();

        $rows = ManualStockoutPendingCarton::query()
            ->where('status', 1)
            ->when(count($openIds) > 0, fn ($q) => $q->whereNotIn('invoice_id', $openIds))
            ->with(['invoice.buyer'])
            ->orderByDesc('updated_at')
            ->orderByDesc('id')
            ->get();

        return self::groupCartonSummaries($rows, $search, true);
    }

    private static function groupCartonSummaries(Collection $rows, ?string $search, bool $completed): Collection
    {
        $byInvoice = $rows->groupBy('invoice_id');
        $out = collect();

        foreach ($byInvoice as $invoiceId => $group) {
            $inv = $group->first()->invoice;
            if (! $inv) {
                continue;
            }

            if ($search !== null && $search !== '') {
                $q = strtolower(trim($search));
                $hay = strtolower(
                    (string) ($inv->invoiceno ?? '')
                    . ' ' . (string) ($inv->containerno ?? '')
                    . ' ' . (string) ($inv->buyerorderno ?? '')
                );
                if ($q !== '' && ! str_contains($hay, $q)) {
                    continue;
                }
            }

            $short1 = 0.0;
            $short2 = 0.0;
            if (! $completed) {
                foreach ($group as $p) {
                    $s1 = $p->short_qty_box1 !== null ? (float) $p->short_qty_box1 : 0.0;
                    $s2 = $p->short_qty_box2 !== null ? (float) $p->short_qty_box2 : 0.0;
                    if ($s1 <= 0 && $s2 <= 0) {
                        [$r1, $r2] = ManualStockoutPendingViewData::cartonShortFromReason((string) $p->reason);
                        $s1 = $r1;
                        $s2 = $r2;
                    }
                    $short1 += $s1;
                    $short2 += $s2;
                }
            }

            $canFulfill = ! $completed && ManualStockoutPendingAccess::canFulfillPendingForInvoice($inv);

            $out->push([
                'invoice' => $inv,
                'container' => (string) ($inv->containerno ?? ''),
                'pending_line_count' => $group->count(),
                'total_short_box1' => $completed ? null : round($short1, 4),
                'total_short_box2' => $completed ? null : round($short2, 4),
                'completed' => $completed,
                'can_open_detail' => ! $completed && $canFulfill,
                'lock_reason' => ! $completed ? ManualStockoutPendingAccess::lockReason($inv) : null,
                'max_pending_id' => $group->max('id'),
            ]);
        }

        return $out->sortByDesc('max_pending_id')->values();
    }

    /**
     * @return array{invoice: invoice, lines: Collection, totals_by_consumable: array<int, float>, can_fulfill: bool, lock_reason: ?string}
     */
    public static function consumableDetail(int $invoiceId): array
    {
        $inv = invoice::query()
            ->with(['buyer', 'invoiceTable', 'stockout.stockoutTable.product'])
            ->findOrFail($invoiceId);

        $stock = $inv->stockout->first();
        $lines = collect();
        $totalsByConsumable = [];

        if ($stock) {
            $remByProduct = $inv->invoiceTable->keyBy('product_id');
            $productIds = $stock->stockoutTable->pluck('product_id')->filter()->unique();
            $wfByProduct = WfConsumable::query()
                ->with(['consumable'])
                ->whereIn('product_id', $productIds)
                ->get()
                ->groupBy('product_id');

            foreach ($stock->stockoutTable as $st) {
                $pid = (int) $st->product_id;
                $receiveQty = (float) ($st->receiveqty ?? 0);
                $wfList = $wfByProduct->get($pid, collect());
                foreach ($wfList as $wf) {
                    $perUnit = (float) ($wf->qty ?? 0);
                    if ($perUnit <= 0) {
                        continue;
                    }
                    $consId = (int) ($wf->consumables_id ?? 0);
                    if ($consId <= 0) {
                        continue;
                    }
                    $need = $perUnit * $receiveQty;
                    if ($need <= 0) {
                        continue;
                    }
                    $totalsByConsumable[$consId] = ($totalsByConsumable[$consId] ?? 0) + $need;
                }
            }
        }

        $pendingRows = ManualStockoutPendingConsumable::query()
            ->where('invoice_id', $invoiceId)
            ->where('status', 0)
            ->with(['consumable', 'product'])
            ->orderByDesc('id')
            ->get();

        foreach ($pendingRows as $p) {
            $still = ManualStockoutPendingViewData::consumableStillNeedFromReason((string) $p->reason);
            $cons = $p->consumable;
            $avail = $cons ? (float) ($cons->quantity ?? 0) : null;

            $st = null;
            $perUnit = null;
            $furnitureQty = null;
            $totalNeed = null;
            $deducted = null;

            if ((int) $p->product_id > 0 && $stock) {
                $st = stockoutTable::query()
                    ->where('stock_id', (int) $p->stock_id)
                    ->where('product_id', (int) $p->product_id)
                    ->first();
                $furnitureQty = $st ? (float) ($st->receiveqty ?? 0) : null;
                $wf = WfConsumable::query()
                    ->where('product_id', (int) $p->product_id)
                    ->where('consumables_id', (int) $p->consumable_id)
                    ->first();
                $perUnit = $wf ? (float) ($wf->qty ?? 0) : null;
                if ($perUnit !== null && $furnitureQty !== null) {
                    $totalNeed = $perUnit * $furnitureQty;
                }
                if ($totalNeed !== null && $still !== null) {
                    $deducted = max(0.0, $totalNeed - $still);
                }
            } else {
                if (preg_match('/required:\s*([\d.]+)/', (string) $p->reason, $m)) {
                    $totalNeed = (float) $m[1];
                }
                if ($totalNeed !== null && $still !== null) {
                    $deducted = max(0.0, $totalNeed - $still);
                }
            }

            $lines->push([
                'pending' => $p,
                'still_need' => $still,
                'available' => $avail,
                'product_code' => $p->product_id > 0 ? (optional($p->product)->code ?? $p->product_id) : '—',
                'product_name' => $p->product_id > 0 ? (optional($p->product)->name ?? '') : 'Container consumable',
                'furniture_qty' => $furnitureQty,
                'per_unit' => $perUnit,
                'total_need' => $totalNeed,
                'deducted_so_far' => $deducted,
                'location' => $p->location ?? (optional($st)->location ?? ''),
            ]);
        }

        $canFulfill = ManualStockoutPendingAccess::canFulfillPendingForInvoice($inv);

        $totalsRows = [];
        foreach ($totalsByConsumable as $cid => $qty) {
            $c = ConsumableModel::query()->find((int) $cid);
            $totalsRows[] = [
                'id' => (int) $cid,
                'name' => $c->name ?? ('#' . $cid),
                'qty' => $qty,
            ];
        }

        return [
            'invoice' => $inv,
            'lines' => $lines,
            'totals_by_consumable' => $totalsRows,
            'can_fulfill' => $canFulfill,
            'lock_reason' => ManualStockoutPendingAccess::lockReason($inv),
        ];
    }

    /**
     * @return array{invoice: invoice, lines: Collection, can_fulfill: bool, lock_reason: ?string}
     */
    public static function cartonDetail(int $invoiceId): array
    {
        $inv = invoice::query()
            ->with(['buyer', 'invoiceTable', 'stockout.stockoutTable.product'])
            ->findOrFail($invoiceId);

        $pendingRows = ManualStockoutPendingCarton::query()
            ->where('invoice_id', $invoiceId)
            ->where('status', 0)
            ->with(['product'])
            ->orderByDesc('id')
            ->get();

        $lines = collect();
        foreach ($pendingRows as $p) {
            $s1 = $p->short_qty_box1 !== null ? (float) $p->short_qty_box1 : 0.0;
            $s2 = $p->short_qty_box2 !== null ? (float) $p->short_qty_box2 : 0.0;
            if ($s1 <= 0.00001 && $s2 <= 0.00001) {
                [$s1, $s2] = ManualStockoutPendingViewData::cartonShortFromReason((string) $p->reason);
            }
            $pack = $p->product_id ? packaging::where('product_id', $p->product_id)->first() : null;
            $avail1 = $pack ? (float) ($pack->box_1_qty ?? 0) : null;
            $avail2 = $pack ? (float) ($pack->box_2_qty ?? 0) : null;

            $st = stockoutTable::query()
                ->where('stock_id', (int) $p->stock_id)
                ->where('product_id', (int) $p->product_id)
                ->first();
            $furnitureQty = $st ? (float) ($st->receiveqty ?? 0) : null;
            $pc = $p->product_id ? ProductCarton::where('product_id', $p->product_id)->first() : null;
            $q1 = $pc ? (float) ($pc->quantity1 ?? 0) : 0.0;
            $q2 = $pc ? (float) ($pc->quantity2 ?? 0) : 0.0;
            $need1 = $furnitureQty !== null ? $q1 * $furnitureQty : null;
            $need2 = $furnitureQty !== null ? $q2 * $furnitureQty : null;
            $ded1 = ($need1 !== null) ? max(0.0, $need1 - $s1) : null;
            $ded2 = ($need2 !== null) ? max(0.0, $need2 - $s2) : null;

            $lines->push([
                'pending' => $p,
                'short_box1' => $s1,
                'short_box2' => $s2,
                'avail_box1' => $avail1,
                'avail_box2' => $avail2,
                'product_code' => optional($p->product)->code ?? $p->product_id,
                'product_name' => optional($p->product)->name ?? '',
                'furniture_qty' => $furnitureQty,
                'qty1_per_unit' => $q1,
                'qty2_per_unit' => $q2,
                'total_need_box1' => $need1,
                'total_need_box2' => $need2,
                'deducted_box1' => $ded1,
                'deducted_box2' => $ded2,
                'location' => $p->location ?? (optional($st)->location ?? ''),
            ]);
        }

        $canFulfill = ManualStockoutPendingAccess::canFulfillPendingForInvoice($inv);

        return [
            'invoice' => $inv,
            'lines' => $lines,
            'can_fulfill' => $canFulfill,
            'lock_reason' => ManualStockoutPendingAccess::lockReason($inv),
        ];
    }
}
