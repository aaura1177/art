<?php

namespace App\Http\Controllers;

use App\invoice;
use App\packaging;
use App\ProductCarton;
use App\Services\Carton\CartonStockoutService;
use App\Services\Carton\CartonSwapService;
use App\Support\ManualCartonInvoiceAccess;
use App\Support\ManualCartonProductGap;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ManualCartonInvoiceCountController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth', '2fa']);
    }

    protected function invoiceNeedsManualCarton(invoice $inv): bool
    {
        $stock = $inv->stockout->first();
        if (! $stock) {
            return false;
        }

        $aggregates = ManualCartonProductGap::aggregateFurnitureAndDeductions($stock);
        if ($aggregates === []) {
            return false;
        }

        $cartons = ProductCarton::query()
            ->whereIn('product_id', array_keys($aggregates))
            ->get()
            ->keyBy('product_id');

        foreach ($aggregates as $pid => $base) {
            $pc = $cartons->get($pid);
            $per1 = (float) (optional($pc)->quantity1 ?? 0);
            $per2 = (float) (optional($pc)->quantity2 ?? 0);
            $gaps = ManualCartonProductGap::computeGaps($base, $per1, $per2);

            if (ManualCartonProductGap::needsManualCarton(
                $gaps['gap_box1'],
                $gaps['gap_box2'],
                $gaps['needs_qty_config']
            )) {
                return true;
            }
        }

        return false;
    }

    /**
     * Lightweight query: invoices in window with stockout, minimal eager load.
     */
    protected function baseInvoiceQuery(string $fromDate, string $toDate)
    {
        return invoice::query()
            ->select(['id', 'invoiceno', 'date', 'containerno', 'buyerorderno', 'totalquantity', 'totalamount', 'buyer_id'])
            ->with([
                'buyer:id,name,c_name',
                'stockout:id,invoice_id',
                'stockout.stockoutTable:id,stock_id,product_id,receiveqty,location',
                'stockout.stockoutTableCarton:id,stock_id,product_id,receiveqty,receiveqty2',
            ])
            ->whereNotNull('containerno')
            ->where('containerno', '!=', '')
            ->whereDate('date', '>=', $fromDate)
            ->whereDate('date', '<=', $toDate)
            ->whereHas('stockout');
    }

    protected function buildLineBreakdown(invoice $inv): array
    {
        $stock = $inv->stockout->first();
        if (! $stock) {
            return ['stock_id' => null, 'lines' => [], 'lock_reason' => null];
        }

        $aggregates = ManualCartonProductGap::aggregateFurnitureAndDeductions($stock);
        if ($aggregates === []) {
            return ['stock_id' => $stock->id, 'lines' => [], 'lock_reason' => null];
        }

        $productIds = array_keys($aggregates);

        $products = \App\product::query()
            ->whereIn('id', $productIds)
            ->get(['id', 'code', 'name'])
            ->keyBy('id');

        $cartons = ProductCarton::query()
            ->whereIn('product_id', $productIds)
            ->get()
            ->keyBy('product_id');

        $packaging = packaging::query()
            ->whereIn('product_id', $productIds)
            ->get(['product_id', 'box_1_qty', 'box_2_qty'])
            ->keyBy('product_id');

        $lines = [];
        foreach ($aggregates as $pid => $base) {
            $prod = $products->get($pid);
            $pc = $cartons->get($pid);
            $pack = $packaging->get($pid);
            $per1 = (float) (optional($pc)->quantity1 ?? 0);
            $per2 = (float) (optional($pc)->quantity2 ?? 0);
            $gaps = ManualCartonProductGap::computeGaps($base, $per1, $per2);

            if (! ManualCartonProductGap::needsManualCarton(
                $gaps['gap_box1'],
                $gaps['gap_box2'],
                $gaps['needs_qty_config']
            )) {
                continue;
            }

            $stock1 = (float) (optional($pack)->box_1_qty ?? 0);
            $stock2 = (float) (optional($pack)->box_2_qty ?? 0);
            $need1 = $gaps['gap_box1'];
            $need2 = $gaps['gap_box2'];

            $lines[] = [
                'product_id' => (int) $pid,
                'product_code' => optional($prod)->code ?? (string) $pid,
                'product_name' => optional($prod)->name ?? '',
                'stockout_qty' => (float) $base['total_furniture_out'],
                'deducted_box1' => (float) $base['deducted_box1'],
                'deducted_box2' => (float) $base['deducted_box2'],
                'is_incremental' => (
                    (float) $base['deducted_box1'] > 0.00001
                    || (float) $base['deducted_box2'] > 0.00001
                ),
                'location' => $base['location'] ?? '',
                'qty1_per_product' => $per1,
                'qty2_per_product' => $per2,
                'need_box1' => $need1,
                'need_box2' => $need2,
                'stock_box1' => $stock1,
                'stock_box2' => $stock2,
                'short_box1' => max(0.0, $need1 - $stock1),
                'short_box2' => max(0.0, $need2 - $stock2),
                'needs_qty' => $gaps['needs_qty_config'],
            ];
        }

        return [
            'stock_id' => $stock->id,
            'lines' => $lines,
            'lock_reason' => ManualCartonInvoiceAccess::isEligibleForUi($inv)
                ? null
                : \App\Support\ManualStockoutPendingAccess::lockReason($inv),
        ];
    }

    public function index(Request $request)
    {
        [$fromDate, $toDate] = ManualCartonInvoiceAccess::dateWindow();

        $candidates = $this->baseInvoiceQuery($fromDate, $toDate)
            ->orderBy('date')
            ->orderBy('id')
            ->get()
            ->filter(fn ($inv) => $this->invoiceNeedsManualCarton($inv));

        $containers = $candidates
            ->pluck('containerno')
            ->unique()
            ->sortDesc()
            ->values();

        $selectedContainer = trim((string) $request->query('containerno', ''));
        $activeInvoice = null;
        $breakdown = ['stock_id' => null, 'lines' => [], 'lock_reason' => null];

        if ($selectedContainer !== '') {
            $activeInvoice = ManualCartonInvoiceAccess::activeInvoiceForContainer($selectedContainer, $candidates);
            if ($activeInvoice && $this->invoiceNeedsManualCarton($activeInvoice)) {
                $breakdown = $this->buildLineBreakdown($activeInvoice);
            }
        }

        return view('carton.manual_invoice_count', [
            'containers' => $containers,
            'selectedContainer' => $selectedContainer,
            'activeInvoice' => $activeInvoice,
            'breakdown' => $breakdown,
            'fromDate' => $fromDate,
            'toDate' => $toDate,
        ]);
    }

    public function searchAlternateProducts(Request $request)
    {
        $q = trim((string) $request->query('q', ''));
        $exclude = (int) $request->query('exclude', 0);

        $rows = packaging::query()
            ->select([
                'packaging.product_id',
                'packaging.box_1_qty',
                'packaging.box_2_qty',
                'product_table.code as product_code',
                'product_table.name as product_name',
            ])
            ->join('product_table', 'product_table.id', '=', 'packaging.product_id')
            ->when($exclude > 0, fn ($query) => $query->where('packaging.product_id', '!=', $exclude))
            ->where(function ($query) {
                $query->where('packaging.box_1_qty', '>', 0)
                    ->orWhere('packaging.box_2_qty', '>', 0);
            })
            ->when($q !== '', function ($query) use ($q) {
                $query->where(function ($inner) use ($q) {
                    $inner->where('product_table.code', 'like', '%' . $q . '%')
                        ->orWhere('product_table.name', 'like', '%' . $q . '%');
                });
            })
            ->orderBy('product_table.code')
            ->limit(20)
            ->get();

        return response()->json($rows);
    }

    public function stockoutCartons(Request $request, CartonSwapService $swapService, CartonStockoutService $stockoutService)
    {
        $data = $request->validate([
            'invoice_id' => 'required|integer',
            'lines' => 'required|array|min:1',
            'lines.*.product_id' => 'required|integer',
            'lines.*.quantity1' => 'nullable|numeric|min:0',
            'lines.*.quantity2' => 'nullable|numeric|min:0',
            'lines.*.swaps' => 'nullable|array',
            'lines.*.swaps.*.from_product_id' => 'required_with:lines.*.swaps|integer',
            'lines.*.swaps.*.box1' => 'nullable|numeric|min:0',
            'lines.*.swaps.*.box2' => 'nullable|numeric|min:0',
            'lines.*.swaps.*.priority' => 'required_with:lines.*.swaps|integer|min:1',
        ]);

        $invoiceId = (int) $data['invoice_id'];

        try {
            return DB::transaction(function () use ($data, $invoiceId, $swapService, $stockoutService) {
                $inv = invoice::query()
                    ->with(['stockout.stockoutTable', 'stockout.stockoutTableCarton'])
                    ->findOrFail($invoiceId);

                if (! ManualCartonInvoiceAccess::isEligibleForUi($inv)) {
                    return redirect()->back()->with('error', \App\Support\ManualStockoutPendingAccess::lockReason($inv) ?? 'Invoice is not eligible for manual carton stockout.');
                }

                $stock = $inv->stockout->first();
                if (! $stock) {
                    return redirect()->back()->with('error', 'Stockout record not found for this invoice.');
                }

                $aggregates = ManualCartonProductGap::aggregateFurnitureAndDeductions($stock);
                $remByProduct = $inv->invoiceTable()->get(['product_id', 'remqty'])->keyBy('product_id');
                $createdAny = false;

                foreach ($data['lines'] as $line) {
                    $pid = (int) $line['product_id'];
                    $base = $aggregates[$pid] ?? null;
                    if (! $base) {
                        continue;
                    }

                    $q1 = isset($line['quantity1']) ? (float) $line['quantity1'] : 0;
                    $q2 = isset($line['quantity2']) ? (float) $line['quantity2'] : 0;

                    $incremental = ManualCartonProductGap::incrementalNeed($base, $q1, $q2);
                    $need1 = $incremental['need_box1'];
                    $need2 = $incremental['need_box2'];
                    $append = $incremental['append'];

                    if ($need1 <= 0 && $need2 <= 0) {
                        continue;
                    }

                    $pack = packaging::where('product_id', $pid)->first();
                    if (! $pack) {
                        throw new \RuntimeException('Packaging record not found for product ID ' . $pid . '.');
                    }

                    ProductCarton::updateOrCreate(
                        ['product_id' => $pid],
                        [
                            'packaging_id' => (int) $pack->id,
                            'quantity1' => $q1,
                            'quantity2' => $q2,
                        ]
                    );

                    $swaps = collect($line['swaps'] ?? [])
                        ->sortBy(fn ($s) => (int) ($s['priority'] ?? 999))
                        ->values()
                        ->all();

                    $swapAudit = [];
                    foreach ($swaps as $swap) {
                        $fromId = (int) $swap['from_product_id'];
                        $box1 = (float) ($swap['box1'] ?? 0);
                        $box2 = (float) ($swap['box2'] ?? 0);
                        if ($box1 <= 0 && $box2 <= 0) {
                            continue;
                        }

                        $result = $swapService->transferBetweenProducts(
                            $fromId,
                            $pid,
                            $box1,
                            $box2,
                            (string) $inv->invoiceno,
                            'Manual Carton Swap Out',
                            'Manual Carton Swap In'
                        );

                        $swapAudit[] = [
                            'source_product_id' => $fromId,
                            'swap_priority' => (int) ($swap['priority'] ?? 1),
                            'box1' => $result['box1'],
                            'box2' => $result['box2'],
                            'carton_swapping_ids' => $result['carton_swapping_ids'],
                        ];
                    }

                    $stockoutService->deductForLine(
                        $inv,
                        $stock,
                        $pid,
                        $need1,
                        $need2,
                        (float) (optional($remByProduct->get($pid))->remqty ?? 0),
                        $base['location'] ?? null,
                        $swapAudit,
                        $append
                    );

                    $createdAny = true;
                }

                if (! $createdAny) {
                    return redirect()->back()->with(
                        'error',
                        'No carton stock was applied. Set qty1/qty2, add swaps if needed, then try again.'
                    );
                }

                return redirect()
                    ->route('carton.manual_invoice_count', ['containerno' => $inv->containerno])
                    ->with('success', 'Carton stockout processed successfully.');
            });
        } catch (\Throwable $e) {
            return redirect()->back()->with('error', $e->getMessage() ?: 'Carton stockout failed.');
        }
    }
}
