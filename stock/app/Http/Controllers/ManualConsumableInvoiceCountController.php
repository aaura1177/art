<?php

namespace App\Http\Controllers;

use App\consumable;
use App\invoice;
use App\stockLogConsumable;
use App\stockoutTableConsumable;
use App\Support\ManualStockoutPendingPresenter;
use App\WfConsumable;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class ManualConsumableInvoiceCountController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth', '2fa']);
    }

    public function index(Request $request)
    {
        $fromDate = Carbon::today()->subDays(2)->toDateString();
        $toDate = Carbon::today()->toDateString();

        $invoices = invoice::query()
            ->with([
                'buyer',
                'invoiceTable.product',
                'stockout.stockoutTable.product',
            ])
            ->whereDate('date', '>=', $fromDate)
            ->whereDate('date', '<=', $toDate)
            ->whereHas('stockout')
            ->whereDoesntHave('stockout.stockoutTableConsumable')
            ->orderBy('date', 'desc')
            ->orderBy('id', 'desc')
            ->get();

        $productIds = $invoices
            ->flatMap(fn ($inv) => $inv->invoiceTable->pluck('product_id'))
            ->filter()
            ->unique()
            ->values();

        $wfByProduct = WfConsumable::query()
            ->with(['consumable'])
            ->whereIn('product_id', $productIds)
            ->get()
            ->groupBy('product_id');

        $containerConsumablesCatalog = consumable::query()
            ->where('is_container', 1)
            ->orderBy('name')
            ->get()
            ->filter(static fn ($c) => (float) ($c->container_quantity ?? 0) > 0)
            ->values();

        $consumableBreakdowns = [];
        foreach ($invoices as $inv) {
            $stock = $inv->stockout->first();
            if (! $stock) {
                continue;
            }

            $remByProduct = $inv->invoiceTable->keyBy('product_id');
            $lines = [];
            $totals = [];

            foreach ($stock->stockoutTable as $st) {
                $pid = $st->product_id;
                $receiveQty = (float) ($st->receiveqty ?? 0);
                $remQty = (float) (optional($remByProduct->get($pid))->remqty ?? 0);

                $wfList = $wfByProduct->get($pid, collect());
                foreach ($wfList as $wf) {
                    $perUnit = (float) ($wf->qty ?? 0);
                    if ($perUnit <= 0) {
                        continue;
                    }

                    $usedConsumableQty = $perUnit * $receiveQty;
                    $remainingConsumableQty = $perUnit * $remQty;

                    if ($usedConsumableQty == 0 && $remainingConsumableQty == 0) {
                        continue;
                    }

                    $cons = $wf->consumable;
                    $consId = $cons->id ?? null;

                    $lines[] = [
                        'product_code' => optional($st->product)->code ?? $pid,
                        'product_name' => optional($st->product)->name ?? '',
                        'stockout_qty' => $receiveQty,
                        'location' => $st->location ?? '',
                        'consumable_id' => $consId,
                        'consumable_name' => $cons->name ?? '',
                        'unit_per_product' => $perUnit,
                        'used_qty' => $usedConsumableQty,
                        'remaining_qty' => $remainingConsumableQty,
                        'unit_type' => $wf->unit_type_name ?? '',
                    ];

                    if ($consId) {
                        $totals[$consId] = ($totals[$consId] ?? 0) + $usedConsumableQty;
                    }
                }
            }

            $consumableBreakdowns[$inv->id] = [
                'stock_id' => $stock->id,
                'lines' => $lines,
                'totals' => $totals,
            ];
        }

        $pendingQ = (string) $request->query('pending_q', '');

        return view('consumable.manual_invoice_count', [
            'invoices' => $invoices,
            'consumableBreakdowns' => $consumableBreakdowns,
            'containerConsumablesCatalog' => $containerConsumablesCatalog,
            'pending_q' => $pendingQ,
            'consumablePendingOpenSummaries' => ManualStockoutPendingPresenter::consumableOpenInvoiceSummaries($pendingQ !== '' ? $pendingQ : null),
            'consumablePendingCompletedSummaries' => ManualStockoutPendingPresenter::consumableCompletedInvoiceSummaries($pendingQ !== '' ? $pendingQ : null),
        ]);
    }

    public function pendingDetail(int $invoice)
    {
        $data = ManualStockoutPendingPresenter::consumableDetail($invoice);

        return view('consumable.pending_detail', $data);
    }

    public function stockoutConsumables(Request $request)
    {
        $data = $request->validate([
            'invoice_id' => 'required|integer',
        ]);

        $invoiceId = (int) $data['invoice_id'];

        return DB::transaction(function () use ($invoiceId) {
            $inv = invoice::query()
                ->with(['invoiceTable', 'stockout.stockoutTable'])
                ->findOrFail($invoiceId);

            $stock = $inv->stockout->first();
            if (! $stock) {
                return redirect()->back()->with('error', 'Stockout record not found for this invoice.');
            }

            $already = stockoutTableConsumable::query()
                ->where('stock_id', $stock->id)
                ->exists();
            if ($already) {
                return redirect()->back()->with('error', 'Consumable stockout already processed for this invoice.');
            }

            $remByProduct = $inv->invoiceTable->keyBy('product_id');
            $productIds = $stock->stockoutTable->pluck('product_id')->filter()->unique()->values();

            $wfByProduct = WfConsumable::query()
                ->whereIn('product_id', $productIds)
                ->get()
                ->groupBy('product_id');

            // Shortage check (aggregate by consumable_id)
            $requiredTotals = [];
            foreach ($stock->stockoutTable as $st) {
                $pid = $st->product_id;
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
                    $requiredTotals[$consId] = ($requiredTotals[$consId] ?? 0) + ($perUnit * $receiveQty);
                }
            }

            $shortage = [];
            foreach ($requiredTotals as $consId => $requiredQty) {
                $cons = consumable::find($consId);
                $available = (float) ($cons->quantity ?? 0);
                if ($available < (float) $requiredQty) {
                    $shortage[] = ($cons->name ?? $consId) . " (Required: {$requiredQty}, Available: {$available})";
                }
            }
            if (count($shortage) > 0) {
                return redirect()->back()->with('error', 'Consumable shortage: ' . implode(' | ', $shortage));
            }

            // Create entries + deduct stock
            foreach ($stock->stockoutTable as $st) {
                $pid = $st->product_id;
                $receiveQty = (float) ($st->receiveqty ?? 0);
                $remQty = (float) (optional($remByProduct->get($pid))->remqty ?? 0);

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

                    $usedConsumableQty = $perUnit * $receiveQty;
                    $remainingConsumableQty = $perUnit * $remQty;
                    if ($usedConsumableQty == 0 && $remainingConsumableQty == 0) {
                        continue;
                    }

                    $cons = consumable::find($consId);
                    if (! $cons) {
                        continue;
                    }

                    $openBalance = (float) ($cons->quantity ?? 0);

                    stockoutTableConsumable::create([
                        'invoice_id' => (int) $inv->id,
                        'consumable_id' => $cons->id,
                        'stock_id' => $stock->id,
                        'orderqty' => $remainingConsumableQty,
                        'receiveqty' => $usedConsumableQty,
                        'remainingqty' => 0,
                        'location' => $st->location ?? null,
                    ]);

                    $cons->quantity = $openBalance - $usedConsumableQty;
                    $cons->save();

                    stockLogConsumable::create([
                        'product_id' => $pid,
                        'consumable_id' => $cons->id,
                        'voucher_no' => $inv->invoiceno,
                        'ref_no' => 'Stock Out - Buyer Order No. - ' . $inv->buyerorderno,
                        'quantity' => $usedConsumableQty,
                        'opening_balance' => $openBalance,
                        'remaining_stock' => $cons->quantity,
                        'type' => 2,
                        'remark' => 'Sell',
                    ]);
                }
            }

            return redirect()
                ->route('consumable.manual_invoice_count')
                ->with('success', 'Consumable stockout processed successfully.');
        });
    }
}
