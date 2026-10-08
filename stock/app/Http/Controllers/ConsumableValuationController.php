<?php

namespace App\Http\Controllers;

use App\consumable;
use App\cartonSwapping;
use App\Exports\ConsumableValuationExport;
use App\Exports\PoCartonBillsDueTrackingExport;
use App\Exports\PoConsumableBillsDueTrackingExport;
use App\packaging;
use App\pbTableConsumable;
use App\purchaseOrderConsumable;
use App\setting;
use App\StockLogCarton;
use App\stockLogConsumable;
use App\supplierInvoice;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ConsumableValuationController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth', '2fa']);
    }

    public function valuation()
    {
        $valuationRows = $this->buildValuationRows();

        return view('consumable.valuation', ['valuationRows' => $valuationRows]);
    }

    public function valuationPdfView(Request $request)
    {
        $from = $request->from ? date('Y-m-d', strtotime($request->from)) : date('Y-m-d');
        $valuationRows = $this->buildValuationRows($from, true);
        $companyDetails = setting::first();

        return view('consumable.valuationpdf', [
            'valuationRows' => $valuationRows,
            'companyDetails' => $companyDetails,
            'from' => $from,
        ]);
    }

    public function exportValuationExcel(Request $request)
    {
        $from = $request->from ? date('Y-m-d', strtotime($request->from)) : date('Y-m-d');
        $valuationRows = $this->buildValuationRows($from, true);

        return (new ConsumableValuationExport($valuationRows))->download('consumableValuation.xlsx');
    }

    /**
     * Bills due tracking export for consumable purchase bills (POST from purchase bill consumables list modal).
     * Same date inputs as purchaseBillController::billstracking (fsd, fed).
     */
    public function purchaseBillConsumableBillstracking(Request $request)
    {
        $fsd = $request->input('fsd');
        $fed = $request->input('fed');
        if (!$fsd || !$fed) {
            return redirect()->back()->with('danger', 'Please select start and end date.');
        }

        return (new PoConsumableBillsDueTrackingExport($fsd, $fed))->download('billstracking-consumables.xlsx');
    }

    /**
     * Bills due tracking export for carton purchase bills (POST from purchase bill carton list modal).
     */
    public function purchaseBillCartonBillstracking(Request $request)
    {
        $fsd = $request->input('fsd');
        $fed = $request->input('fed');
        if (!$fsd || !$fed) {
            return redirect()->back()->with('danger', 'Please select start and end date.');
        }

        return (new PoCartonBillsDueTrackingExport($fsd, $fed))->download('billstracking-carton.xlsx');
    }

    public function exportConsumableSupplierInvoiceCsv(Request $request)
    {
        [$startDate, $endDate, $errorRedirect] = $this->parseSupplierInvoiceExportDates($request);
        if ($errorRedirect) {
            return $errorRedirect;
        }

        $consumablePoIds = purchaseOrderConsumable::whereBetween('podate', [$startDate, $endDate])
            ->pluck('id');

        $supplierInvoices = supplierInvoice::with(['purchaseOrderConsumable', 'supplierData'])
            ->whereIn('purchase_order_id', $consumablePoIds)
            ->where('purchase_order_type', '!=', 'Furniture')
            ->orderBy('id', 'ASC')
            ->get();

        return $this->streamSupplierInvoicesCsv($supplierInvoices, 'supplierInvoiceConsumable.csv');
    }

    /**
     * Carton supplier invoices only (same list as /supplierInvoice/carton), filtered by consumable PO date range.
     */
    public function exportCartonSupplierInvoiceCsv(Request $request)
    {
        [$startDate, $endDate, $errorRedirect] = $this->parseSupplierInvoiceExportDates($request);
        if ($errorRedirect) {
            return $errorRedirect;
        }

        $supplierInvoices = supplierInvoice::with(['purchaseOrderConsumable', 'supplierData'])
            ->where('purchase_order_type', 'Carton')
            ->whereHas('purchaseOrderConsumable', function ($q) use ($startDate, $endDate) {
                $q->whereBetween('podate', [$startDate, $endDate]);
            })
            ->orderBy('id', 'ASC')
            ->get();

        return $this->streamSupplierInvoicesCsv($supplierInvoices, 'supplierInvoiceCarton.csv');
    }

    public function cartonSwapping()
    {
        $swaps = cartonSwapping::with([
            'packagingOut.product:id,code,name',
            'packagingIn.product:id,code,name',
        ])->orderByDesc('id')->get();

        return view('consumable.carton_swapping', ['swaps' => $swaps]);
    }

    public function createCartonSwapping()
    {
        $packagingRows = packaging::with('product:id,code,name')
            ->whereNotNull('product_id')
            ->orderByDesc('id')
            ->get()
            ->filter(function ($row) {
                return $row->product !== null;
            })
            ->values();

        return view('consumable.create_carton_swapping', ['packagingRows' => $packagingRows]);
    }

    public function storeCartonSwapping(Request $request)
    {
        $validated = $request->validate([
            'invoice_no' => 'required|string|max:191',
            'po' => 'required|array|min:1',
            'po.*.product' => 'required|integer',
            'po.*.swapped_with' => 'required|integer',
            'po.*.carton_type' => 'required|in:box_1_qty,box_2_qty',
            'po.*.quantity' => 'required|integer|min:1',
        ]);

        DB::beginTransaction();
        try {
            foreach ($validated['po'] as $row) {
                $packagingOut = packaging::with('product:id,code,name')->findOrFail((int) $row['product']);
                $packagingIn = packaging::with('product:id,code,name')->findOrFail((int) $row['swapped_with']);
                if ((int) $packagingOut->id === (int) $packagingIn->id) {
                    throw new \RuntimeException('Product Out and Product In cannot be same.');
                }
                $cartonField = $row['carton_type'];
                $qty = (int) $row['quantity'];

                $outAvailable = (int) ($packagingOut->{$cartonField} ?? 0);
                if ($outAvailable < $qty) {
                    $productName = optional($packagingOut->product)->code ?: 'Selected product';
                    throw new \RuntimeException($productName . ' has insufficient carton stock.');
                }

                cartonSwapping::create([
                    'invoice_no' => strtoupper($validated['invoice_no']),
                    'packaging_out_id' => $packagingOut->id,
                    'packaging_in_id' => $packagingIn->id,
                    'carton_type' => $cartonField,
                    'quantity' => $qty,
                ]);

                $openOutBox1 = (int) ($packagingOut->box_1_qty ?? 0);
                $openOutBox2 = (int) ($packagingOut->box_2_qty ?? 0);
                $openInBox1 = (int) ($packagingIn->box_1_qty ?? 0);
                $openInBox2 = (int) ($packagingIn->box_2_qty ?? 0);

                $packagingOut->{$cartonField} = $outAvailable - $qty;
                $packagingOut->save();

                $inAvailable = (int) ($packagingIn->{$cartonField} ?? 0);
                $packagingIn->{$cartonField} = $inAvailable + $qty;
                $packagingIn->save();

                $isBox1 = $cartonField === 'box_1_qty';

                StockLogCarton::create([
                    'product_id' => (int) $packagingOut->product_id,
                    'voucher_no' => strtoupper($validated['invoice_no']),
                    'ref_no' => 'Carton Swap Out',
                    'quantity' => $isBox1 ? $qty : 0,
                    'quantity2' => $isBox1 ? 0 : $qty,
                    'opening_balance' => $openOutBox1,
                    'opening_balance2' => $openOutBox2,
                    'remaining_stock' => (int) ($packagingOut->box_1_qty ?? 0),
                    'remaining_stock2' => (int) ($packagingOut->box_2_qty ?? 0),
                    'type' => 2,
                ]);

                StockLogCarton::create([
                    'product_id' => (int) $packagingIn->product_id,
                    'voucher_no' => strtoupper($validated['invoice_no']),
                    'ref_no' => 'Carton Swap In',
                    'quantity' => $isBox1 ? $qty : 0,
                    'quantity2' => $isBox1 ? 0 : $qty,
                    'opening_balance' => $openInBox1,
                    'opening_balance2' => $openInBox2,
                    'remaining_stock' => (int) ($packagingIn->box_1_qty ?? 0),
                    'remaining_stock2' => (int) ($packagingIn->box_2_qty ?? 0),
                    'type' => 1,
                ]);
            }

            DB::commit();
            return redirect('/consumable/carton-swapping')->with('success', 'Carton swapping was added successfully.');
        } catch (\Throwable $e) {
            DB::rollBack();
            return redirect()->back()->withInput()->with('danger', $e->getMessage());
        }
    }

    /**
     * @return array{0: string, 1: string, 2: \Illuminate\Http\RedirectResponse|null}
     */
    private function parseSupplierInvoiceExportDates(Request $request): array
    {
        $fsd = $request->input('fsd');
        $fed = $request->input('fed');

        if (!$fsd || !$fed) {
            return ['', '', redirect()->back()->with('danger', 'Please select both start and end date.')];
        }

        if (strtotime($fsd) > strtotime($fed)) {
            return ['', '', redirect()->back()->with('danger', 'Start Date should be less than End Date')];
        }

        $startDate = date('Y-m-d 00:00:00', strtotime($fsd));
        $endDate = date('Y-m-d 23:59:59', strtotime($fed));

        return [$startDate, $endDate, null];
    }

    private function streamSupplierInvoicesCsv($supplierInvoices, string $fileName)
    {
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $fileName . '"',
        ];

        $callback = function () use ($supplierInvoices) {
            $stream = fopen('php://output', 'w');
            fputcsv($stream, [
                'PO',
                'Supplier Invoice No',
                'Supplier Name',
                'Internal Invoice No.',
                'Eway Bill No.',
                'Vehicle No.',
                'Total GST',
                'Sub Total',
                'Total Amount',
            ]);

            foreach ($supplierInvoices as $invoice) {
                fputcsv($stream, [
                    optional($invoice->purchaseOrderConsumable)->pono ?? '',
                    $invoice->supplier_invoice_number ?? '',
                    optional($invoice->supplierData)->c_name ?? '',
                    $invoice->internal_invoice_number ?? '',
                    $invoice->eway_bill_no ?? '',
                    $invoice->vehicle_no ?? '',
                    $invoice->tgst ?? '',
                    $invoice->subTotal ?? '',
                    $invoice->tamount ?? '',
                ]);
            }

            fclose($stream);
        };

        return response()->stream($callback, 200, $headers);
    }

    private function buildValuationRows(?string $from = null, bool $excludeMonthEnd = false)
    {
        $toDateTime = $from ? ($from . ' 23:59:59') : null;

        $consumables = consumable::orderBy('name', 'ASC')->get();
        $valuationRows = collect();

        foreach ($consumables as $consumable) {
            if ($excludeMonthEnd && $this->isMonthEndConsumable($consumable)) {
                continue;
            }

            $stockQty = $this->resolveStockQty($consumable, $toDateTime);
            if ($stockQty <= 0) {
                continue;
            }

            $coveredQty = 0.0;
            $totalAmount = 0.0;
            $ageLines = [];

            $entriesQuery = pbTableConsumable::where('product_id', $consumable->id)
                ->where('receiveqty', '>', 0)
                ->orderBy('id', 'DESC');

            if ($toDateTime) {
                $entriesQuery->where('created_at', '<=', $toDateTime);
            }

            $entries = $entriesQuery->get(['id', 'receiveqty', 'rate', 'created_at']);

            foreach ($entries as $entry) {
                if ($coveredQty >= $stockQty) {
                    break;
                }

                $entryQty = (float) $entry->receiveqty;
                if ($entryQty <= 0) {
                    continue;
                }

                $remainingQty = $stockQty - $coveredQty;
                $usedQty = min($entryQty, $remainingQty);

                $totalAmount += $usedQty * (float) $entry->rate;
                $coveredQty += $usedQty;
                $ageLines[] = $this->formatQuantity($usedQty) . ' - ' . $this->resolveAgeBucket($entry->created_at);
            }

            $valuationRows->push([
                'code' => $consumable->SKU ?: $consumable->EAN,
                'name' => $consumable->name,
                'quantity' => $this->formatQuantity($stockQty),
                'rate' => $stockQty > 0 ? ($totalAmount / $stockQty) : 0,
                'total_value' => $totalAmount,
                'age_lines' => $ageLines,
            ]);
        }

        return $valuationRows;
    }

    private function isMonthEndConsumable($consumable): bool
    {
        return (int) ($consumable->monthEndpo_supplier ?? 0) !== 0;
    }

    private function resolveStockQty($consumable, ?string $toDateTime): float
    {
        if (!$toDateTime) {
            return (float) $consumable->quantity;
        }

        $snapshot = stockLogConsumable::where('consumable_id', $consumable->id)
            ->where('created_at', '<=', $toDateTime)
            ->orderBy('created_at', 'DESC')
            ->orderBy('id', 'DESC')
            ->first(['remaining_stock']);

        if (!$snapshot) {
            return 0.0;
        }

        return max(0, (float) $snapshot->remaining_stock);
    }

    private function resolveAgeBucket($date): string
    {
        if (!$date) {
            return 'N/A';
        }

        $days = Carbon::parse($date)->diffInDays(Carbon::now());

        if ($days <= 30) {
            return '0-30 days';
        }

        if ($days <= 60) {
            return '30-60 days';
        }

        if ($days <= 90) {
            return '61-90 days';
        }

        return 'Above 90 days';
    }

    private function formatQuantity(float $qty): string
    {
        if (floor($qty) == $qty) {
            return (string) (int) $qty;
        }

        return rtrim(rtrim(number_format($qty, 2, '.', ''), '0'), '.');
    }
}
