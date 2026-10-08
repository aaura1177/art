<?php

namespace App\Http\Controllers;

use App\pocTable;
use App\popTable;
use App\purchaseBillConsumable;
use App\purchaseOrderConsumable;
use App\Services\InwardSupply\ConsumableAdminInwardEditService;
use App\Services\InwardSupply\CartonAdminInwardService;
use App\Services\InwardSupply\ConsumableAdminInwardService;
use App\Services\InwardSupply\InwardEligibility;
use App\Support\CartonProductLabel;
use App\supplierInvoiceProduct;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class AdminPurchaseBillController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth', '2fa']);
    }

    public function createConsumable()
    {
        $purchaseOrders = purchaseOrderConsumable::query()
            ->where('type', 1)
            ->where('status', 0)
            ->where(function ($q) {
                $q->where('address_option', '!=', 100)
                    ->orWhereNull('address_option');
            })
            ->whereHas('poTable', function ($q) {
                $q->where('remqty', '>', 0);
            })
            ->with('supplier')
            ->orderByDesc('id')
            ->get();

        return view('purchaseBill.create_admin_consumable', [
            'purchaseOrders' => $purchaseOrders,
        ]);
    }

    public function editConsumable()
    {
        $purchaseOrders = purchaseOrderConsumable::query()
            ->where('type', 1)
            ->where(function ($q) {
                $q->where('address_option', '!=', 100)
                    ->orWhereNull('address_option');
            })
            ->with('supplier')
            ->orderByDesc('id')
            ->get();

        return view('purchaseBill.edit_admin_consumable', [
            'purchaseOrders' => $purchaseOrders,
        ]);
    }

    public function createCarton()
    {
        $purchaseOrders = purchaseOrderConsumable::query()
            ->where('type', 2)
            ->where('status', 0)
            ->where(function ($q) {
                $q->where('address_option', '!=', 100)
                    ->orWhereNull('address_option');
            })
            ->whereHas('popTable', function ($q) {
                $q->where(function ($q2) {
                    $q2->where('remqty_box1', '>', 0)
                        ->orWhere('remqty_box2', '>', 0);
                });
            })
            ->with('supplier')
            ->orderByDesc('id')
            ->get();

        return view('purchaseBill.create_admin_carton', [
            'purchaseOrders' => $purchaseOrders,
        ]);
    }

    public function consumablePoLines(int $id)
    {
        $po = purchaseOrderConsumable::where('id', $id)->where('type', 1)->first();
        if (!$po) {
            throw new NotFoundHttpException();
        }
        $po->loadMissing('supplier');

        $lines = pocTable::where('poid', $po->id)
            ->where('remqty', '>', 0)
            ->with('consumable.unitType')
            ->orderBy('id')
            ->get()
            ->map(function ($row) {
                $c = $row->consumable;

                return [
                    'consumable_id' => (int) $row->consumable_id,
                    'name' => $c->name ?? '',
                    'sku' => $c->SKU ?? '',
                    'unit' => optional(optional($c)->unitType)->name ?? ($row->unit ?? ''),
                    'data_type' => optional(optional($c)->unitType)->data_type ?? 'int',
                    'remqty' => (float) $row->remqty,
                    'rate' => (float) $row->rate,
                    'gstslab' => (float) ($row->gstslab ?? 0),
                ];
            })
            ->values();

        $eligible = InwardEligibility::firstEligibleInwardDate($po);

        return response()->json([
            'purchase_order' => [
                'id' => $po->id,
                'pono' => $po->pono,
                'supplier_name' => optional($po->supplier)->c_name,
                'supplier_city' => optional($po->supplier)->city,
                'supplier_tds_percent' => (float) (optional($po->supplier)->tdspercent ?? 0),
                'supplier_tds_date' => optional($po->supplier)->tdsdate,
                'ref_supplier' => $po->ref_supplier,
                'podate' => $po->podate,
                'del_date' => $po->del_date,
                'remarks' => $po->remarks,
                'payterms' => $po->payterms,
                'address_option' => $po->address_option,
                'ordered_qty' => $po->tquantity,
                'po_sub_total' => $po->subTotal,
            ],
            'lines' => $lines,
            'eligible_inward_date' => $eligible ? $eligible->format('Y-m-d') : null,
        ]);
    }

    public function consumableEditPoData(int $id)
    {
        $po = purchaseOrderConsumable::where('id', $id)->where('type', 1)->first();
        if (!$po) {
            throw new NotFoundHttpException();
        }
        $po->loadMissing('supplier');

        $latestBill = purchaseBillConsumable::where('purchaseOrder_id', $po->id)
            ->whereNotNull('supplier_invoice_id')
            ->orderByDesc('id')
            ->first();

        $latestInvoiceId = optional($latestBill)->supplier_invoice_id;
        $invoiceAgg = [];
        if ($latestInvoiceId) {
            $invoiceAgg = supplierInvoiceProduct::where('supplier_invoice_id', $latestInvoiceId)
                ->selectRaw('product_id, SUM(quantity) as qty')
                ->groupBy('product_id')
                ->pluck('qty', 'product_id')
                ->toArray();
        }

        $lines = pocTable::where('poid', $po->id)
            ->with('consumable.unitType')
            ->orderBy('id')
            ->get()
            ->map(function ($row) use ($invoiceAgg) {
                $c = $row->consumable;
                $alreadyReceived = (float) ($invoiceAgg[$row->consumable_id] ?? 0);
                $remaining = max(0, (float) $row->remqty);

                return [
                    'consumable_id' => (int) $row->consumable_id,
                    'name' => $c->name ?? '',
                    'sku' => $c->SKU ?? '',
                    'unit' => optional(optional($c)->unitType)->name ?? ($row->unit ?? ''),
                    'data_type' => optional(optional($c)->unitType)->data_type ?? 'int',
                    'ordered_qty' => (float) $row->quantity,
                    'already_received_qty' => $alreadyReceived,
                    'remaining_qty' => $remaining,
                    'rate' => (float) $row->rate,
                    'gstslab' => (float) ($row->gstslab ?? 0),
                ];
            })
            ->values();

        return response()->json([
            'purchase_order' => [
                'id' => $po->id,
                'pono' => $po->pono,
                'supplier_name' => optional($po->supplier)->c_name,
                'ref_supplier' => $po->ref_supplier,
                'del_date' => $po->del_date,
                'remarks' => $po->remarks,
                'ordered_qty' => $po->tquantity,
                'po_sub_total' => $po->subTotal,
            ],
            'latest_bill' => $latestBill ? [
                'id' => $latestBill->id,
                'supp_inv_no' => $latestBill->supp_inv_no,
                'supp_inv_date' => $latestBill->supp_inv_date,
                'ewaybill' => $latestBill->ewaybill,
                'supplier_invoice_id' => $latestBill->supplier_invoice_id,
            ] : null,
            'lines' => $lines,
        ]);
    }

    public function cartonPoLines(int $id)
    {
        $po = purchaseOrderConsumable::where('id', $id)->where('type', 2)->first();
        if (!$po) {
            throw new NotFoundHttpException();
        }
        $po->loadMissing('supplier');

        $lines = popTable::where('poid', $po->id)
            ->where(function ($q) {
                $q->where('remqty_box1', '>', 0)
                    ->orWhere('remqty_box2', '>', 0);
            })
            ->with('product')
            ->orderBy('id')
            ->get()
            ->map(function ($row) {
                $p = $row->product;
                $code = trim((string) ($p->code ?? ''));
                $name = (string) ($p->name ?? ('Product #' . $row->product_id));
                $h = trim((string) ($row->box1_height ?? ''));
                $w = trim((string) ($row->box1_width ?? ''));
                $d = trim((string) ($row->box1_depth ?? ''));
                $box1Size = ($h === '' && $w === '' && $d === '') ? '' : $h . ' X ' . $w . ' X ' . $d;

                return [
                    'product_id' => (int) $row->product_id,
                    'name' => $name,
                    'product_code' => $code,
                    'product_name' => $name,
                    'box1_size' => $box1Size,
                    'product_label' => CartonProductLabel::fromPopTable($row),
                    'remqty_box1' => (int) $row->remqty_box1,
                    'remqty_box2' => (int) $row->remqty_box2,
                    'box1_rate' => (float) ($row->box1_rate ?? 0),
                    'box2_rate' => (float) ($row->box2_rate ?? 0),
                    'gstslab' => (float) ($row->gstslab ?? 0),
                ];
            })
            ->values();

        $eligible = InwardEligibility::firstEligibleInwardDate($po);

        return response()->json([
            'purchase_order' => [
                'id' => $po->id,
                'pono' => $po->pono,
                'supplier_name' => optional($po->supplier)->c_name,
                'supplier_tds_percent' => (float) (optional($po->supplier)->tdspercent ?? 0),
                'supplier_tds_date' => optional($po->supplier)->tdsdate,
                'ref_supplier' => $po->ref_supplier,
                'podate' => $po->podate,
                'del_date' => $po->del_date,
                'remarks' => $po->remarks,
                'payterms' => $po->payterms,
                'address_option' => $po->address_option,
                'ordered_qty' => $po->tquantity,
                'po_sub_total' => $po->subTotal,
            ],
            'lines' => $lines,
            'eligible_inward_date' => $eligible ? $eligible->format('Y-m-d') : null,
        ]);
    }

    public function storeConsumable(Request $request, ConsumableAdminInwardService $service)
    {
        return $service->execute($request);
    }

    public function updateConsumable(Request $request, ConsumableAdminInwardEditService $service)
    {
        return $service->execute($request);
    }

    public function storeCarton(Request $request, CartonAdminInwardService $service)
    {
        return $service->execute($request);
    }
}
