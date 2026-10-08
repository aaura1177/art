<?php

namespace App\Http\Controllers;

use App\consumable;
use App\hardwares;
use App\pricingTable;
use App\supplier;
use App\UnitType;
use App\WfConsumable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class HardwareMonthEndController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth', '2fa']);
    }

    public function index()
    {
        $hardwares = hardwares::with(['hardwareSuppliers', 'consumable'])->orderBy('id', 'desc')->get();

        $matchedConsumableNames = [];
        $consumableRows = consumable::select('id', 'name')->get();
        foreach ($consumableRows as $row) {
            $key = mb_strtolower(trim((string) $row->name));
            if ($key === '' || isset($matchedConsumableNames[$key])) {
                continue;
            }
            $matchedConsumableNames[$key] = $row;
        }

        foreach ($hardwares as $hardware) {
            $matchKey = mb_strtolower(trim((string) $hardware->name));
            $hardware->name_matched_consumable = $matchedConsumableNames[$matchKey] ?? null;
        }

        return view('hardwares/monthend_onboarding_index', ['hardwares' => $hardwares]);
    }

    public function createConsumableForm(int $hardwareId)
    {
        $hardware = hardwares::with('consumable')->findOrFail($hardwareId);
        if (! Schema::hasColumn('hardwares', 'consumable_id')) {
            return redirect()->back()->with('danger', 'hardwares.consumable_id column is missing. Please run SQL first.');
        }
        if (! empty($hardware->consumable_id)) {
            return redirect()->route('hardwares.monthend.onboarding.index')
                ->with('success', 'This hardware is already linked to a consumable.');
        }

        $suppliers = supplier::orderBy('c_name')->get();
        $unitType = UnitType::all();

        return view('hardwares/monthend_consumable_form', [
            'hardware' => $hardware,
            'suppliers' => $suppliers,
            'unitType' => $unitType,
        ]);
    }

    public function storeConsumable(Request $request, int $hardwareId)
    {
        $hardware = hardwares::findOrFail($hardwareId);

        $request->validate([
            'name' => 'required|string|max:255',
            'EAN' => 'required|string|max:255',
            'SKU' => 'nullable|string|max:255|unique:consumables,SKU',
            'unit_type_id' => 'required|integer',
            'rate' => 'required|numeric',
            'gst' => 'required|in:0,5,12,18,28',
            'payment_terms' => 'required|string|max:255',
            'supplier' => 'required|array|min:1',
            'supplier.*' => 'required|integer',
            'monthEndpo_supplier' => 'required|integer',
            'description' => 'nullable|string|max:65535',
            'is_moq' => 'nullable|in:0,1',
            'moq_qty' => 'nullable|numeric|min:0.00000001|required_if:is_moq,1',
            'is_container' => 'nullable|in:0,1',
            'container_quantity' => 'nullable|numeric|min:0',
        ]);

        if (! Schema::hasColumn('hardwares', 'consumable_id')) {
            return redirect()->back()->with('danger', 'hardwares.consumable_id column is missing. Please run SQL first.');
        }
        if ($hardware->consumable_id) {
            return redirect()->route('hardwares.monthend.onboarding.index')->with('danger', 'Hardware already linked.');
        }

        $containerQuantity = null;
        if ((string) $request->input('is_container', '0') === '1' && $request->filled('container_quantity')) {
            $containerQuantity = (float) $request->input('container_quantity');
        }

        DB::transaction(function () use ($request, $hardware) {
            $data = [
                'name' => $request->input('name'),
                'unit' => (string) $request->input('unit', ''),
                'rate' => (float) $request->input('rate'),
                'EAN' => strtoupper((string) $request->input('EAN')),
                'supplier' => json_encode($request->input('supplier', [])),
                'payment_terms' => $request->input('payment_terms'),
                'unit_type_id' => (int) $request->input('unit_type_id'),
                'gst' => (float) $request->input('gst'),
                'quantity' => 0,
                'monthEndpo_supplier' => (int) $request->input('monthEndpo_supplier'),
                'is_container' => (int) $request->input('is_container', 0),
                'container_quantity' => $containerQuantity = (
                    (string) $request->input('is_container', '0') === '1' && $request->filled('container_quantity')
                        ? (float) $request->input('container_quantity')
                        : null
                ),
            ];

            if (Schema::hasColumn('consumables', 'description')) {
                $data['description'] = $request->filled('description') ? (string) $request->input('description') : null;
            }
            if (Schema::hasColumn('consumables', 'is_moq')) {
                $data['is_moq'] = (string) $request->input('is_moq', '0') === '1' ? 1 : 0;
            }
            if (Schema::hasColumn('consumables', 'moq_qty')) {
                $data['moq_qty'] = (string) $request->input('is_moq', '0') === '1'
                    ? (float) $request->input('moq_qty')
                    : null;
            }
            if (Schema::hasColumn('consumables', 'SKU')) {
                $sku = strtoupper(trim((string) $request->input('SKU', '')));
                $data['SKU'] = $sku !== '' ? $sku : null;
            }
            if (Schema::hasColumn('consumables', 'monthEndpo_buyer')) {
                // Buyer is intentionally not part of hardware month-end flow.
                $data['monthEndpo_buyer'] = null;
            }
            if (Schema::hasColumn('consumables', 'hardware_monthend_po_product')) {
                $data['hardware_monthend_po_product'] = $request->boolean('hardware_monthend_po_product') ? 1 : 0;
            }

            $createdConsumable = consumable::create($data);

            $hardware->consumable_id = $createdConsumable->id;
            $hardware->save();

            $this->syncConsumableToFurnitureByPricing((int) $hardware->id, (int) $createdConsumable->id);
            $this->replaceHardwareWithConsumableInPricing((int) $hardware->id, (int) $createdConsumable->id);
        });

        return redirect()->route('hardwares.monthend.onboarding.index')
            ->with('success', 'Consumable created and linked with hardware successfully.');
    }

    protected function syncConsumableToFurnitureByPricing(int $hardwareId, int $consumableId): void
    {
        $qtyByProduct = [];
        foreach (range(1, 5) as $n) {
            $hardwareCol = 'hardware' . $n;
            $qtyCol = 'hardware' . $n . '_quantity';
            $rows = pricingTable::query()
                ->where($hardwareCol, $hardwareId)
                ->whereNotNull('product_id')
                ->get(['product_id', $qtyCol]);

            foreach ($rows as $row) {
                $productId = (int) ($row->product_id ?? 0);
                $qty = (float) ($row->{$qtyCol} ?? 0);
                if ($productId <= 0 || $qty <= 0) {
                    continue;
                }
                if (! isset($qtyByProduct[$productId])) {
                    $qtyByProduct[$productId] = $qty;
                } else {
                    $qtyByProduct[$productId] = max((float) $qtyByProduct[$productId], $qty);
                }
            }
        }

        if ($qtyByProduct === []) {
            return;
        }

        $consumable = consumable::find($consumableId);
        foreach ($qtyByProduct as $productId => $qty) {
            $wfData = [
                'qty' => $qty,
                'rate' => (float) ($consumable->rate ?? 0),
                'unit_type_id' => $consumable->unit_type_id ?? null,
                'unit_type_name' => optional(optional($consumable)->unitType)->name ?? null,
            ];
            if (Schema::hasColumn('wf_consumable', 'consumable_id')) {
                $wfData['consumable_id'] = $consumableId;
            }

            WfConsumable::updateOrCreate(
                [
                    'product_id' => (int) $productId,
                    'consumables_id' => $consumableId,
                ],
                $wfData
            );
        }
    }

    /**
     * Swap pricingTable.hardware1..5 from hardware id to linked consumable id (after wf sync).
     */
    protected function replaceHardwareWithConsumableInPricing(int $hardwareId, int $consumableId): void
    {
        foreach (range(1, 5) as $n) {
            $col = 'hardware' . $n;
            pricingTable::query()->where($col, $hardwareId)->update([$col => $consumableId]);
        }
    }
}

