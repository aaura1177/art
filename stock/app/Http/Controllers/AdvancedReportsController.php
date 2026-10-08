<?php

namespace App\Http\Controllers;

use App\Exports\AdvancedReportExport;
use App\product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;

class AdvancedReportsController extends Controller
{
    /**
     * Numeric columns that should export as 0 (not blank) when missing/null/empty.
     */
    private const NUMERIC_EXPORT_COLUMNS = [
        'qty',
        'opening_balance',
        'closing_balance',
        'out_qty',
        'reference_quantity',
    ];

    public function index(Request $request)
    {
        $reportType = (string) $request->query('report_type', 'product_movement');
        $columnsMap = $this->columnsMap();
        if (! isset($columnsMap[$reportType])) {
            $reportType = 'product_movement';
        }

        $selectedColumns = $this->resolveSelectedColumns($reportType, $columnsMap, $request->query('columns'));

        $rows = collect();
        $previewRequested = $request->has('preview');
        if ($previewRequested) {
            $rows = $this->buildRows($reportType, $request, $selectedColumns);
        }

        [$invoiceOptions, $batchOptions] = $this->filterOptions($request, $reportType);
        $productOptions = $this->productOptions($request);

        return view('reports.advanced_index', [
            'reportType' => $reportType,
            'columnsMap' => $columnsMap,
            'selectedColumns' => $selectedColumns,
            'rows' => $rows,
            'previewRequested' => $previewRequested,
            'products' => $productOptions,
            'invoiceOptions' => $invoiceOptions,
            'batchOptions' => $batchOptions,
        ]);
    }

    public function export(Request $request)
    {
        $reportType = (string) $request->query('report_type', 'product_movement');
        $columnsMap = $this->columnsMap();
        if (! isset($columnsMap[$reportType])) {
            $reportType = 'product_movement';
        }

        $selectedColumns = $this->resolveSelectedColumns($reportType, $columnsMap, $request->query('columns'));

        $rows = $this->buildRows($reportType, $request, $selectedColumns);
        $headings = [];
        foreach ($selectedColumns as $key) {
            $headings[] = $columnsMap[$reportType]['columns'][$key] ?? $key;
        }
        $exportRows = $rows->map(function ($row) use ($selectedColumns) {
            $out = [];
            foreach ($selectedColumns as $key) {
                $hasKey = is_array($row) && array_key_exists($key, $row);
                $isNumericCol = in_array($key, self::NUMERIC_EXPORT_COLUMNS, true);
                if (! $hasKey || $row[$key] === null) {
                    $out[] = $isNumericCol ? 0 : '';
                    continue;
                }
                if ($isNumericCol && $row[$key] === '') {
                    $out[] = 0;
                    continue;
                }
                $out[] = $row[$key];
            }
            return $out;
        })->values()->all();

        $fileName = 'advanced_report_' . $reportType . '_' . date('Ymd_His') . '.xlsx';
        return Excel::download(new AdvancedReportExport($headings, $exportRows), $fileName);
    }

    public function invoiceOptions(Request $request)
    {
        [$invoiceOptions] = $this->filterOptions($request, 'movement_by_sales_invoice');

        return response()->json([
            'invoice_options' => $invoiceOptions->values(),
        ]);
    }

    private function buildRows(string $reportType, Request $request, array $selectedColumns, ?int $limit = null)
    {
        switch ($reportType) {
            case 'batch_detail':
                return $this->batchDetailRows($request, $selectedColumns, $limit);
            case 'movement_by_sales_invoice':
                return $this->movementBySalesInvoiceRows($request, $selectedColumns, $limit);
            case 'product_movement':
            default:
                return $this->productMovementRows($request, $selectedColumns, $limit);
        }
    }

    private function productMovementRows(Request $request, array $selectedColumns, ?int $limit = null)
    {
        $q = DB::table('stock_log as sl')
            ->leftJoin('product_table as p', 'p.id', '=', 'sl.product_id')
            ->where('sl.type', '<', 3)
            ->select([
                'sl.created_at',
                'p.code as product_code',
                'p.name as product_name',
                'sl.voucher_no',
                'sl.ref_no',
                'sl.quantity',
                'sl.opening_balance',
                'sl.remaining_stock',
                'sl.batch_no',
                'sl.reference_number',
                'sl.supplier_name',
                'sl.buyer_name',
                'sl.type',
            ])
            ->orderByDesc('sl.created_at')
            ->orderByDesc('sl.id');

        if ($request->filled('from')) {
            $q->whereDate('sl.created_at', '>=', $request->query('from'));
        }
        if ($request->filled('to')) {
            $q->whereDate('sl.created_at', '<=', $request->query('to'));
        }
        if ($request->filled('product_id')) {
            $q->where('sl.product_id', (int) $request->query('product_id'));
        }
        if ($limit) {
            $q->limit($limit);
        }

        return $q->get()->map(function ($r) use ($selectedColumns) {
            $isIn = (int) ($r->type ?? 0) === 1;
            $qtyRaw = (float) ($r->quantity ?? 0);
            $qty = $isIn ? abs($qtyRaw) : -abs($qtyRaw);

            $row = [
                'date' => substr((string) $r->created_at, 0, 10),
                'product_code' => $r->product_code ?? '',
                'product_name' => $r->product_name ?? '',
                'voucher_no' => $r->voucher_no ?? '',
                'movement_type' => $isIn ? 'IN' : 'OUT',
                'opening_balance' => (float) ($r->opening_balance ?? 0),
                'qty' => $qty,
                'closing_balance' => (float) ($r->remaining_stock ?? 0),
                'batch_no' => $r->batch_no ?? 'Not available',
                'unique_ref' => $r->reference_number ?: 'Not available',
                'supplier_name' => $r->supplier_name ?: 'Not available',
                'buyer_name' => $r->buyer_name ?: 'Not available',
                'reference' => $r->ref_no ?? '',
            ];
            return array_intersect_key($row, array_flip($selectedColumns));
        });
    }

    private function batchDetailRows(Request $request, array $selectedColumns, ?int $limit = null)
    {
        $q = DB::table('stock_log as sl')
            ->leftJoin('product_table as p', 'p.id', '=', 'sl.product_id')
            ->where('sl.type', '<', 3)
            ->select([
                'sl.id as stock_log_id',
                'sl.product_id as sl_product_id',
                'sl.created_at',
                'sl.batch_no',
                'p.code as product_code',
                'p.name as product_name',
                'sl.voucher_no',
                'sl.ref_no',
                'sl.quantity',
                'sl.batch_balance',
                'sl.opening_balance',
                'sl.remaining_stock',
                'sl.reference_number',
                'sl.supplier_name',
                'sl.buyer_name',
                'sl.type',
            ])
            ->orderByDesc('sl.created_at')
            ->orderByDesc('sl.id');

        if ($request->filled('from')) {
            $q->whereDate('sl.created_at', '>=', $request->query('from'));
        }
        if ($request->filled('to')) {
            $q->whereDate('sl.created_at', '<=', $request->query('to'));
        }
        if ($request->filled('batch_no')) {
            $q->where('sl.batch_no', 'like', '%' . $request->query('batch_no') . '%');
        }
        if ($request->filled('product_id')) {
            $q->where('sl.product_id', (int) $request->query('product_id'));
        }
        if ($request->filled('invoice_no')) {
            $q->where('sl.voucher_no', (string) $request->query('invoice_no'));
        }
        if ($limit) {
            $q->limit($limit);
        }

        $rawRows = $q->get();
        if ($rawRows->isEmpty()) {
            return collect();
        }

        $byProduct = $rawRows->groupBy(function ($r) {
            return (int) ($r->sl_product_id ?? 0);
        });

        $sortedProductIds = $byProduct->keys()->sort(function ($pidA, $pidB) use ($byProduct) {
            $fa = $byProduct->get($pidA)->first();
            $fb = $byProduct->get($pidB)->first();
            $cmp = strcmp((string) ($fa->product_code ?? ''), (string) ($fb->product_code ?? ''));
            if ($cmp !== 0) {
                return -$cmp;
            }

            return (int) $pidB <=> (int) $pidA;
        })->values();

        $result = collect();
        $singleBatchMode = $request->filled('batch_no');
        foreach ($sortedProductIds as $pid) {
            $rowsSorted = $byProduct->get($pid)->sort(function ($a, $b) {
                $tc = strcmp((string) ($a->created_at ?? ''), (string) ($b->created_at ?? ''));
                if ($tc !== 0) {
                    return -$tc;
                }

                return ((int) ($b->stock_log_id ?? 0)) <=> ((int) ($a->stock_log_id ?? 0));
            })->values();

            foreach ($rowsSorted as $r) {
                if ($singleBatchMode) {
                    $result->push($this->mapBatchDetailRow($r, $selectedColumns, true));
                } else {
                    $result->push($this->mapBatchDetailRow($r, $selectedColumns));
                }
            }
        }

        return $result;
    }

    private function mapBatchDetailRow(object $r, array $selectedColumns, bool $singleBatchMode = false): array
    {
        $isIn = (int) ($r->type ?? 0) === 1;
        $qtyRaw = (float) ($r->quantity ?? 0);
        $qty = $isIn ? abs($qtyRaw) : -abs($qtyRaw);
        $opening = (float) ($r->opening_balance ?? 0);
        $closing = (float) ($r->remaining_stock ?? 0);

        // When a single batch is selected, show balances using batch-level stock.
        if ($singleBatchMode && isset($r->batch_balance) && $r->batch_balance !== null) {
            $closing = (float) $r->batch_balance;
            $opening = $closing - $qty;
        }

        $row = [
            'date' => substr((string) $r->created_at, 0, 10),
            'batch_no' => $r->batch_no ?: 'Not available',
            'product_code' => $r->product_code ?? '',
            'product_name' => $r->product_name ?? '',
            'voucher_no' => $r->voucher_no ?? '',
            'movement_type' => $isIn ? 'IN' : 'OUT',
            'opening_balance' => $opening,
            'qty' => $qty,
            'closing_balance' => $closing,
            'unique_ref' => $r->reference_number ?: 'Not available',
            'supplier_name' => $r->supplier_name ?: 'Not available',
            'buyer_name' => $r->buyer_name ?: 'Not available',
            'reference' => $r->ref_no ?? '',
        ];

        return array_intersect_key($row, array_flip($selectedColumns));
    }

    private function movementBySalesInvoiceRows(Request $request, array $selectedColumns, ?int $limit = null)
    {
        $invoiceNo = (string) $request->query('invoice_no', '');
        $q = DB::table('stock_log as sl')
            ->leftJoin('product_table as p', 'p.id', '=', 'sl.product_id')
            ->where('sl.type', 2)
            ->select([
                'sl.id as stock_log_id',
                'sl.product_id as sl_product_id',
                'sl.created_at',
                'sl.supplier_inv_no',
                'sl.voucher_no',
                'sl.ref_no',
                'sl.reference_number',
                'sl.batch_no',
                'sl.quantity',
                'sl.type',
                'sl.opening_balance',
                'sl.remaining_stock',
                'sl.supplier_name',
                'sl.buyer_name',
                'p.code as product_code',
                'p.name as product_name',
            ])
            ->orderByDesc('sl.created_at')
            ->orderByDesc('sl.id');

        if ($request->filled('from')) {
            $q->whereDate('sl.created_at', '>=', $request->query('from'));
        }
        if ($request->filled('to')) {
            $q->whereDate('sl.created_at', '<=', $request->query('to'));
        }
        if ($request->filled('product_id')) {
            $q->where('sl.product_id', (int) $request->query('product_id'));
        }
        if ($invoiceNo !== '') {
            $q->where('sl.voucher_no', $invoiceNo);
        }
        if ($limit) {
            $q->limit($limit);
        }

        $outRows = $q->get();
        $productIds = $outRows->pluck('sl_product_id')->filter()->map(function ($id) {
            return (int) $id;
        })->unique()->values()->all();

        if ($productIds === []) {
            return collect();
        }

        $inQ = DB::table('stock_log as sl')
            ->leftJoin('product_table as p', 'p.id', '=', 'sl.product_id')
            ->where('sl.type', 1)
            ->whereIn('sl.product_id', $productIds)
            ->select([
                'sl.id as stock_log_id',
                'sl.product_id as sl_product_id',
                'sl.created_at',
                'sl.supplier_inv_no',
                'sl.voucher_no',
                'sl.ref_no',
                'sl.reference_number',
                'sl.batch_no',
                'sl.quantity',
                'sl.opening_balance',
                'sl.remaining_stock',
                'sl.supplier_name',
                'sl.buyer_name',
                'p.code as product_code',
                'p.name as product_name',
            ]);

        if ($request->filled('product_id')) {
            $inQ->where('sl.product_id', (int) $request->query('product_id'));
        }

        $inFrom = $this->inwardStockLogMinimumDate();
        if ($request->filled('from')) {
            $inFrom = max($inFrom, (string) $request->query('from'));
        }
        $inQ->whereDate('sl.created_at', '>=', $inFrom);

        $candidateIn = $inQ->orderBy('sl.created_at')
            ->orderBy('sl.id')
            ->get()
            ->groupBy(function ($row) {
                return (int) ($row->sl_product_id ?? 0);
            });

        $outByProduct = $outRows->groupBy(function ($row) {
            return (int) ($row->sl_product_id ?? 0);
        });

        $sortedProductIds = $outByProduct->keys()->sort(function ($pidA, $pidB) use ($outByProduct) {
            $fa = $outByProduct->get($pidA)->first();
            $fb = $outByProduct->get($pidB)->first();
            $cmp = strcmp((string) ($fa->product_code ?? ''), (string) ($fb->product_code ?? ''));
            if ($cmp !== 0) {
                return $cmp;
            }

            return (int) $pidA <=> (int) $pidB;
        })->values();

        $result = collect();
        foreach ($sortedProductIds as $pid) {
            $outsThisProduct = $outByProduct->get($pid);
            $result = $result->concat(
                $this->movementBySalesInvoiceRowsStackedLayout($outsThisProduct, $candidateIn, $selectedColumns)
            );
        }

        return $result;
    }

    /**
     * Inward (IN) stock_log rows: minimum created_at (1 April 2026). Stricter when "From" is later.
     */
    private function inwardStockLogMinimumDate(): string
    {
        return '2026-04-01';
    }

    /**
     * For one product's OUT rows: OUT first (subset query order), then matched IN at bottom (created_at desc).
     * IN deduped by stock_log_id within this product; trace uses earliest matching OUT (created_at, then id).
     *
     * @param  \Illuminate\Support\Collection<int, \stdClass>  $outRows
     * @param  \Illuminate\Support\Collection  $candidateIn  grouped by product id
     */
    private function movementBySalesInvoiceRowsStackedLayout($outRows, $candidateIn, array $selectedColumns)
    {
        $result = collect();

        foreach ($outRows as $r) {
            $tokens = $this->supplierRefTokensFromOutStockLog($r->reference_number ?? '', $r->supplier_inv_no ?? null);
            $result->push($this->mapMovementBySalesInvoiceOutRow($r, $selectedColumns, $tokens));
        }

        $inById = [];
        $outRowsAsc = $outRows->sortBy(function ($row) {
            return [(string) ($row->created_at ?? ''), (int) ($row->stock_log_id ?? 0)];
        })->values();

        foreach ($outRowsAsc as $r) {
            $tokens = $this->supplierRefTokensFromOutStockLog($r->reference_number ?? '', $r->supplier_inv_no ?? null);
            $pid = (int) ($r->sl_product_id ?? 0);
            $pool = $candidateIn->get($pid, collect());
            $outAt = strtotime((string) ($r->created_at ?? '')) ?: null;
            $matched = $pool->filter(function ($inRow) use ($tokens, $outAt) {
                if (! $this->stockLogInMatchesOutRefTokens($inRow, $tokens)) {
                    return false;
                }
                if ($outAt !== null) {
                    $inAt = strtotime((string) ($inRow->created_at ?? '')) ?: null;
                    if ($inAt !== null && $inAt > $outAt) {
                        return false;
                    }
                }

                return true;
            })->unique('stock_log_id')->values();

            foreach ($matched as $inRow) {
                $iid = (int) ($inRow->stock_log_id ?? 0);
                if (! isset($inById[$iid])) {
                    $inById[$iid] = [
                        'inRow' => $inRow,
                        'voucher' => (string) ($r->voucher_no ?? ''),
                        'tokens' => $tokens,
                    ];
                }
            }
        }

        $inOrdered = collect($inById)->sort(function (array $a, array $b) {
            $ira = $a['inRow'];
            $irb = $b['inRow'];
            $tc = strcmp((string) ($ira->created_at ?? ''), (string) ($irb->created_at ?? ''));
            if ($tc !== 0) {
                return -$tc;
            }

            return ((int) ($irb->stock_log_id ?? 0)) <=> ((int) ($ira->stock_log_id ?? 0));
        })->values();

        foreach ($inOrdered as $entry) {
            $result->push($this->mapMovementBySalesInvoiceInRow(
                $entry['inRow'],
                $selectedColumns,
                $entry['voucher'],
                $entry['tokens']
            ));
        }

        return $result;
    }

    /**
     * Tokens embedded in OUT.reference_number as NAME(qty/total), plus optional fallback from supplier_inv_no.
     *
     * @return list<string>
     */
    private function supplierRefTokensFromOutStockLog($referenceNumber, $supplierInvNoFallback): array
    {
        $rn = (string) ($referenceNumber ?? '');
        $tokens = [];
        if ($rn !== '') {
            preg_match_all('/([A-Za-z0-9\/\-\._]+)\s*\(\d+\/\d+\)/', $rn, $m);
            if (! empty($m[1])) {
                $tokens = array_values(array_unique($m[1]));
            }
        }
        if ($tokens === [] && $supplierInvNoFallback !== null && (string) $supplierInvNoFallback !== '') {
            $tokens = [(string) $supplierInvNoFallback];
        }

        return $tokens;
    }

    private function stockLogInMatchesOutRefTokens(object $inRow, array $tokens): bool
    {
        foreach ($tokens as $t) {
            $t = trim((string) $t);
            if ($t === '' || strcasecmp($t, 'no supplierinvoice') === 0) {
                continue;
            }
            if ($this->stockLogInMatchesSingleRefToken($inRow, $t)) {
                return true;
            }
        }

        return false;
    }

    private function stockLogInMatchesSingleRefToken(object $inRow, string $t): bool
    {
        $sin = (string) ($inRow->supplier_inv_no ?? '');
        $vch = (string) ($inRow->voucher_no ?? '');
        if ($sin !== '' && strcasecmp($sin, $t) === 0) {
            return true;
        }
        if ($vch !== '' && strcasecmp($vch, $t) === 0) {
            return true;
        }
        $refNum = (string) ($inRow->reference_number ?? '');
        if ($refNum !== '' && stripos($refNum, $t) !== false) {
            return true;
        }

        return false;
    }

    /**
     * @param  \stdClass  $r  OUT stock_log row joined with product
     * @param  list<string>  $tokens
     */
    private function mapMovementBySalesInvoiceOutRow(object $r, array $selectedColumns, array $tokens): array
    {
        $qtyRaw = (float) ($r->quantity ?? 0);
        $row = [
            'date' => substr((string) $r->created_at, 0, 10),
            'product_code' => $r->product_code ?? '',
            'product_name' => $r->product_name ?? '',
            'voucher_no' => $r->voucher_no ?? '',
            'movement_type' => 'OUT',
            'opening_balance' => (float) ($r->opening_balance ?? 0),
            'qty' => -abs($qtyRaw),
            'closing_balance' => (float) ($r->remaining_stock ?? 0),
            'batch_no' => $r->batch_no ?? 'Not available',
            'unique_ref' => $r->reference_number ?: 'Not available',
            'supplier_name' => $r->supplier_name ?: 'Not available',
            'buyer_name' => $r->buyer_name ?: 'Not available',
            'reference' => $r->ref_no ?? '',
            'trace_relation' => $tokens !== [] ? ('Source IN supplier invoice: ' . implode(', ', $tokens)) : 'Source IN not available',
        ];

        return array_intersect_key($row, array_flip($selectedColumns));
    }

    /**
     * @param  \stdClass  $inRow  IN stock_log row joined with product
     * @param  list<string>  $tokens
     */
    private function mapMovementBySalesInvoiceInRow(object $inRow, array $selectedColumns, string $linkedSalesVoucherNo, array $tokens): array
    {
        $qtyRaw = (float) ($inRow->quantity ?? 0);
        $matchedTokens = [];
        foreach ($tokens as $t) {
            $t = trim((string) $t);
            if ($t === '' || strcasecmp($t, 'no supplierinvoice') === 0) {
                continue;
            }
            if ($this->stockLogInMatchesSingleRefToken($inRow, $t)) {
                $matchedTokens[] = $t;
            }
        }
        $matchedTokens = array_values(array_unique($matchedTokens));

        $trace = 'IN for OUT on sales invoice ' . ($linkedSalesVoucherNo !== '' ? $linkedSalesVoucherNo : '(unknown)');
        if ($matchedTokens !== []) {
            $trace .= ' (matched ref: ' . implode(', ', $matchedTokens) . ')';
        }

        $row = [
            'date' => substr((string) $inRow->created_at, 0, 10),
            'product_code' => $inRow->product_code ?? '',
            'product_name' => $inRow->product_name ?? '',
            'voucher_no' => $inRow->voucher_no ?? '',
            'movement_type' => 'IN',
            'opening_balance' => (float) ($inRow->opening_balance ?? 0),
            'qty' => abs($qtyRaw),
            'closing_balance' => (float) ($inRow->remaining_stock ?? 0),
            'batch_no' => $inRow->batch_no ?? 'Not available',
            'unique_ref' => $inRow->reference_number ?: 'Not available',
            'supplier_name' => $inRow->supplier_name ?: 'Not available',
            'buyer_name' => $inRow->buyer_name ?: 'Not available',
            'reference' => $inRow->ref_no ?? '',
            'trace_relation' => $trace,
        ];

        return array_intersect_key($row, array_flip($selectedColumns));
    }

    private function filterOptions(Request $request, string $reportType): array
    {
        $invoiceQuery = DB::table('invoice as i')
            ->select('i.invoiceno')
            ->whereNotNull('i.invoiceno')
            ->where('i.invoiceno', '!=', '');

        if ($request->filled('from')) {
            $from = (string) $request->query('from');
            $invoiceQuery->whereNotNull('i.date');
            $invoiceQuery->whereDate('i.date', '>=', $from);
        }
        if ($request->filled('to')) {
            $to = (string) $request->query('to');
            $invoiceQuery->whereNotNull('i.date');
            $invoiceQuery->whereDate('i.date', '<=', $to);
        }

        $invoiceOptions = $invoiceQuery
            ->distinct()
            ->orderBy('i.invoiceno')
            ->pluck('i.invoiceno');

        // If no date filter is selected, show all known batches.
        // If date filter is selected, show batches that had movement in that period.
        if ($request->filled('from') || $request->filled('to')) {
            $batchQuery = DB::table('stock_log as sl')
                ->leftJoin('batch as b', 'b.batch_no', '=', 'sl.batch_no')
                ->leftJoin('suppliers as s', 's.id', '=', 'b.supplier_id')
                ->select([
                    'sl.batch_no',
                    's.c_name as supplier_name',
                ])
                ->whereNotNull('sl.batch_no')
                ->where('sl.batch_no', '!=', '');

            if ($request->filled('from')) {
                $batchQuery->whereDate('sl.created_at', '>=', $request->query('from'));
            }
            if ($request->filled('to')) {
                $batchQuery->whereDate('sl.created_at', '<=', $request->query('to'));
            }
        } else {
            $batchQuery = DB::table('batch as b')
                ->leftJoin('suppliers as s', 's.id', '=', 'b.supplier_id')
                ->select([
                    'b.batch_no',
                    's.c_name as supplier_name',
                ])
                ->whereNotNull('b.batch_no')
                ->where('b.batch_no', '!=', '');
        }

        $batchOptions = $batchQuery
            ->distinct()
            ->orderBy('batch_no')
            ->get()
            ->map(function ($r) {
                $bn = (string) ($r->batch_no ?? '');
                $sname = trim((string) ($r->supplier_name ?? ''));
                return [
                    'value' => $bn,
                    'label' => $sname !== '' ? ($bn . ' (' . $sname . ')') : $bn,
                ];
            });

        return [$invoiceOptions, $batchOptions];
    }

    private function productOptions(Request $request)
    {
        // If no date selected, return all products.
        if (! $request->filled('from') && ! $request->filled('to')) {
            return product::orderBy('code')->get(['id', 'code', 'name']);
        }

        // Stock movement window for advanced reports.
        $q = DB::table('stock_log as sl')
            ->join('product_table as p', 'p.id', '=', 'sl.product_id')
            ->where('sl.type', '<', 3)
            ->select('p.id', 'p.code', 'p.name')
            ->distinct();
        if ($request->filled('from')) {
            $q->whereDate('sl.created_at', '>=', $request->query('from'));
        }
        if ($request->filled('to')) {
            $q->whereDate('sl.created_at', '<=', $request->query('to'));
        }
        return $q->orderBy('p.code')->get();
    }

    /**
     * Keep only valid column keys and order them like the report definition (so Opening Balance stays before Quantity in preview and Excel).
     *
     * @param  array<string, array<string, mixed>>  $columnsMap
     * @param  mixed  $requested
     * @return list<string>
     */
    private function resolveSelectedColumns(string $reportType, array $columnsMap, $requested): array
    {
        $meta = $columnsMap[$reportType];
        $allowedOrder = array_keys($meta['columns']);
        $allowedSet = array_flip($allowedOrder);

        if (! is_array($requested) || $requested === []) {
            $requested = $meta['default_columns'];
        }
        $requested = array_values(array_unique($requested));

        $picked = [];
        foreach ($requested as $key) {
            if (isset($allowedSet[(string) $key])) {
                $picked[] = (string) $key;
            }
        }

        if ($picked === []) {
            $picked = $meta['default_columns'];
        }

        $pickedSet = array_flip($picked);
        $ordered = [];
        foreach ($allowedOrder as $key) {
            if (isset($pickedSet[$key])) {
                $ordered[] = $key;
            }
        }

        return $ordered;
    }

    private function columnsMap(): array
    {
        return [
            'product_movement' => [
                'label' => 'Product Movement',
                'columns' => [
                    'date' => 'Date',
                    'product_code' => 'Product Code',
                    'product_name' => 'Product Name',
                    'voucher_no' => 'Invoice No',
                    'movement_type' => 'Movement Type',
                    'opening_balance' => 'Opening Balance',
                    'qty' => 'Quantity',
                    'closing_balance' => 'Closing Balance',
                    'batch_no' => 'Batch No',
                    'unique_ref' => 'Unique Ref',
                    'supplier_name' => 'Supplier Name',
                    'buyer_name' => 'Buyer Name',
                    'reference' => 'Reference',
                ],
                'default_columns' => ['date', 'product_code', 'product_name', 'voucher_no', 'movement_type', 'opening_balance', 'qty', 'closing_balance', 'batch_no', 'unique_ref', 'supplier_name', 'buyer_name'],
            ],
            'batch_detail' => [
                'label' => 'Batch Details',
                'columns' => [
                    'date' => 'Date',
                    'batch_no' => 'Batch No',
                    'product_code' => 'Product Code',
                    'product_name' => 'Product Name',
                    'voucher_no' => 'Invoice No',
                    'movement_type' => 'Movement Type',
                    'opening_balance' => 'Opening Balance',
                    'qty' => 'Quantity',
                    'closing_balance' => 'Closing Balance',
                    'unique_ref' => 'Unique Ref',
                    'supplier_name' => 'Supplier Name',
                    'buyer_name' => 'Buyer Name',
                    'reference' => 'Reference',
                ],
                'default_columns' => ['date', 'batch_no', 'product_code', 'product_name', 'voucher_no', 'movement_type', 'opening_balance', 'qty', 'closing_balance', 'unique_ref', 'supplier_name', 'buyer_name'],
            ],
            'movement_by_sales_invoice' => [
                'label' => 'Movement by Sales Invoice',
                'columns' => [
                    'date' => 'Date',
                    'product_code' => 'Product Code',
                    'product_name' => 'Product Name',
                    'voucher_no' => 'Invoice No',
                    'movement_type' => 'Movement Type',
                    'opening_balance' => 'Opening Balance',
                    'qty' => 'Quantity',
                    'closing_balance' => 'Closing Balance',
                    'batch_no' => 'Batch No',
                    'unique_ref' => 'Unique Ref',
                    'supplier_name' => 'Supplier Name',
                    'buyer_name' => 'Buyer Name',
                    'reference' => 'Reference',
                    'trace_relation' => 'Trace Relation',
                ],
                'default_columns' => ['date', 'product_code', 'product_name', 'voucher_no', 'movement_type', 'opening_balance', 'qty', 'closing_balance', 'batch_no', 'unique_ref', 'supplier_name', 'buyer_name', 'trace_relation'],
            ],
        ];
    }
}

