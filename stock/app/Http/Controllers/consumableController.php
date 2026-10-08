<?php

namespace App\Http\Controllers;

use PDF;
use App\consumable;
use App\supplier;
use App\UnitType;
use App\stockLogConsumable;
use Illuminate\Http\Request;
use Illuminate\Database\Eloquent\Relations;
use \auth;
use Maatwebsite\Excel\Facades\Excel;
use App\Imports\ConsumableImport;
use App\Imports\ConsumableUnitTypeUpdateImport;
use App\Exports\ConsumablesExport;
use Maatwebsite\Excel\Excel as ExcelFormat;
use App\stockoutTableConsumable;
use App\WfConsumable;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class consumableController extends Controller
{
    /**
     * When is_container = 1, normalize container_quantity to match unit type (int vs decimal).
     */
    protected function normalizeContainerQuantityForRequest(Request $request): ?float
    {
        if ((string) $request->input('is_container', '') !== '1') {
            return null;
        }
        $raw = $request->input('container_quantity');
        if ($raw === null || $raw === '') {
            return null;
        }
        $unitTypeId = $request->input('unit_type_id');
        $unitType = $unitTypeId ? UnitType::find($unitTypeId) : null;
        $dataType = $unitType->data_type ?? 'float';
        $num = (float) $raw;
        if ($dataType === 'int') {
            return (float) (int) round($num);
        }

        return round($num, 2);
    }

    /**
     * @return array<string, string|array<int, string>>
     */
    protected function consumableDescriptionMoqRules(): array
    {
        return [
            'description' => 'nullable|string|max:65535',
            'is_moq' => 'nullable|in:0,1',
            'moq_qty' => 'nullable|numeric|min:0.00000001|required_if:is_moq,1',
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     */
    /**
     * When Month end PO supplier is set, require a single Buyer (like small hardware master).
     * 1 = UK-18, 2 = Non UK-18, 3 = Common.
     */
    protected function validatedMonthEndpoBuyerForRequest(Request $request): ?int
    {
        if (! Schema::hasColumn('consumables', 'monthEndpo_buyer')) {
            return null;
        }
        $sid = $request->input('monthEndpo_supplier');
        if ($sid === null || $sid === '' || (int) $sid === 0) {
            return null;
        }
        $request->validate([
            'monthEndpo_buyer' => 'required|in:1,2,3',
        ]);

        return (int) $request->input('monthEndpo_buyer');
    }

    protected function applyDescriptionMoqToData(Request $request, array &$data): void
    {
        $isMoq = (string) $request->input('is_moq', '0') === '1';

        if (Schema::hasColumn('consumables', 'description')) {
            $raw = $request->input('description');
            $data['description'] = ($raw !== null && $raw !== '') ? (string) $raw : null;
        }
        if (Schema::hasColumn('consumables', 'is_moq')) {
            $data['is_moq'] = $isMoq ? 1 : 0;
        }
        if (Schema::hasColumn('consumables', 'moq_qty')) {
            $data['moq_qty'] = $isMoq ? $request->input('moq_qty') : null;
        }
    }

    public function __construct()
    {
        $this->middleware(['auth','2fa']);
    }
	
	 public function index()
    {
        //$consumables=consumable::get();
        $consumables = consumable::where('is_deleted', '==', '0')->with('unitType')->get();
        return view('consumable/index', ['consumables' => $consumables]);
    }
	
  public function create()
    {
        $supplier = supplier::get();
        $unitType = UnitType::all();
        return view('consumable/create', ['supplier' => $supplier, 'suppliers' => $supplier, 'unitType' => $unitType]);
    }

    

    public function store(Request $request)
    {
        if (Schema::hasColumn('consumables', 'SKU')) {
            if ($request->has('SKU')) {
                $request->merge([
                    'SKU' => strtoupper(trim((string) $request->input('SKU'))),
                ]);
            }

            $request->validate([
                'SKU' => 'nullable|string|max:255|unique:consumables,SKU',
            ]);
        }

        if (Schema::hasColumn('consumables', 'is_moq') || Schema::hasColumn('consumables', 'description') || Schema::hasColumn('consumables', 'moq_qty')) {
            $request->validate($this->consumableDescriptionMoqRules());
        }

        $monthEndpoBuyer = $this->validatedMonthEndpoBuyerForRequest($request);

        $EAN = preg_replace("/[~!@#$%^&*()_+=`{}\[\]\|\"\:;'<>,.\/? ]+/", "", $request['name']);
        $skuValue = $request->has('SKU') ? strtoupper(trim((string) $request['SKU'])) : null;
        if ($request->hasfile('imgURL')) {
            $file = $request->file('imgURL');
            $extension = $file->getClientOriginalExtension(); // getting image extension
            $filename = $EAN . '.' . $extension;

            $directory = public_path('../../uploads/product');
            $imageUrl = $directory . '/' . $filename;
            Image::make($file)->resize(200, 200, function ($constraint) {
                $constraint->aspectRatio();
            })->save($imageUrl);
        } else {

            $filename = 'default.jpg';
        }
        $data = [
            'name' => $request['name'],
            'unit' => $request['unit'],
            'rate' => $request['rate'],
          'EAN' => strtoupper($request['EAN']),

            'supplier' => json_encode($request->input('supplier', [])),
            'payment_terms' => $request['payment_terms'],
            'unit_type_id' => $request['unit_type_id'],
            'gst' => $request['gst'],
            'quantity' => 0,
            'monthEndpo_supplier' => $request['monthEndpo_supplier'],
             'is_container' => $request->is_container ?? null,
            'container_quantity' => $this->normalizeContainerQuantityForRequest($request),
        ];

        if (Schema::hasColumn('consumables', 'monthEndpo_buyer')) {
            $data['monthEndpo_buyer'] = $monthEndpoBuyer;
        }

        if (Schema::hasColumn('consumables', 'hardware_monthend_po_product')) {
            $data['hardware_monthend_po_product'] = 0;
        }

        $this->applyDescriptionMoqToData($request, $data);

        // Optional SKU support (only if DB column exists).
        if (Schema::hasColumn('consumables', 'SKU') && $skuValue !== null) {
            $data['SKU'] = $skuValue !== '' ? $skuValue : null;
        }

        $q = consumable::create($data);

        return redirect('/consumables')->with('success', 'Consumable was added successfully.');
    }

public function importConsumable(Request $request)
{
    $request->validate([
        'importCSV' => 'required|mimes:xlsx,csv|max:5120',
    ]);

    try {
        $file = $request->file('importCSV');

        $import = new ConsumableImport;
        Excel::import($import, $file);

        $summary = $import->getSummary();
        $reportRows = $import->getReportRows();
        $warnings = $import->getWarnings();
        $reportFile = $this->storeConsumableImportReport($reportRows);

        $summaryMessage = "Import completed. Fully updated: {$summary['fully_updated']}, Partially updated: {$summary['partially_updated']}, Not updated: {$summary['not_updated']}.";
        $redirect = redirect('/consumables')
            ->with('success', $summaryMessage)
            ->with('import_report_download_url', url('/consumables/import-report/download/' . rawurlencode($reportFile)));

        if (!empty($warnings)) {
            return $redirect
                ->with('error', $warnings);
        }

        return $redirect;

    } catch (\Exception $e) {

        \Log::error('Consumable Import Error:', ['message' => $e->getMessage()]);

        return redirect('/consumables')
            ->with('error', 'Import failed. Check logs for details.');
    }
}

protected function storeConsumableImportReport(array $reportRows): string
{
    $directory = storage_path('app/import-reports');
    if (!is_dir($directory)) {
        mkdir($directory, 0755, true);
    }

    $fileName = 'import-result-report-' . now()->format('Ymd-His') . '-' . Str::random(6) . '.csv';
    $filePath = $directory . DIRECTORY_SEPARATOR . $fileName;

    $handle = fopen($filePath, 'w');
    fputcsv($handle, [
        'row_number',
        'id',
        'name',
        'status',
        'updated_fields',
        'failed_fields',
        'errors',
        'warnings',
        'raw_row_json',
    ]);

    foreach ($reportRows as $row) {
        fputcsv($handle, [
            $row['row_number'] ?? '',
            $row['id'] ?? '',
            $row['name'] ?? '',
            $row['status'] ?? '',
            implode(' | ', $row['updated_fields'] ?? []),
            implode(' | ', $row['failed_fields'] ?? []),
            implode(' | ', $row['errors'] ?? []),
            implode(' | ', $row['warnings'] ?? []),
            json_encode($row['raw_row'] ?? [], JSON_UNESCAPED_UNICODE),
        ]);
    }

    fclose($handle);

    return $fileName;
}

public function downloadImportReport($fileName)
{
    $safeFileName = basename((string) $fileName);
    $fullPath = storage_path('app/import-reports/' . $safeFileName);

    if (!file_exists($fullPath)) {
        return redirect('/consumables')->with('danger', 'Import report file not found.');
    }

    return response()->download($fullPath, 'import-result-report.csv');
}

    /**
     * Update only Unit Type for consumables by name from CSV/Excel.
     * Matches rows by Name and updates only unit_type_id (from Unit Type ID column).
     */
    public function importUpdateUnitType(Request $request)
    {
        $request->validate([
            'importCSV' => 'required|mimes:xlsx,csv|max:5120',
        ]);

        try {
            $file = $request->file('importCSV');
            $import = new ConsumableUnitTypeUpdateImport;
            Excel::import($import, $file);

            $updated = $import->getUpdatedCount();
            $warnings = $import->getWarnings();

            $message = "Unit Type update completed. Updated: {$updated} consumable(s).";
            if (!empty($warnings)) {
                return redirect('/consumables')
                    ->with('success', $message)
                    ->with('error', $warnings);
            }

            return redirect('/consumables')->with('success', $message);
        } catch (\Exception $e) {
            \Log::error('Consumable Unit Type Update Import Error:', ['message' => $e->getMessage()]);
            return redirect('/consumables')
                ->with('error', 'Import failed: ' . $e->getMessage());
        }
    }

/**
 * Update only SKU for consumables by name from CSV/Excel.
 * Matches rows by Name and updates only consumables.SKU column.
 */
public function importUpdateSKU(Request $request)
{
    $request->validate([
        'importCSV' => 'required|mimes:xlsx,csv|max:5120',
    ]);

        try {
            $file = $request->file('importCSV');
            $import = new ConsumableImport(true); // SKU-only mode
            Excel::import($import, $file);

        $updated = $import->getUpdatedCount();
        $warnings = $import->getWarnings();

        $message = "SKU update completed. Updated: {$updated} consumable(s).";
        if (!empty($warnings)) {
            return redirect('/consumables')
                ->with('success', $message)
                ->with('error', $warnings);
        }

        return redirect('/consumables')->with('success', $message);
    } catch (\Exception $e) {
        \Log::error('Consumable SKU Update Import Error:', ['message' => $e->getMessage()]);
        return redirect('/consumables')
            ->with('error', 'Import failed: ' . $e->getMessage());
    }
}


   public function view($id)
    {
        $consumable = consumable::find($id);
         $unitType = UnitType::all();
        $supplier = supplier::get();
        return view('/consumable/view', ['consumable' => $consumable, 'supplier' => $supplier,'unitType' => $unitType]);
    }

     public function update(Request $request, $id)
    {
        $consumable = consumable::find($id);
        if ($consumable) {
            if (Schema::hasColumn('consumables', 'SKU')) {
                if ($request->has('SKU')) {
                    $request->merge([
                        'SKU' => strtoupper(trim((string) $request->input('SKU'))),
                    ]);
                }

                $request->validate([
                    'SKU' => 'nullable|string|max:255|unique:consumables,SKU,' . $consumable->id,
                ]);
            }

            if (Schema::hasColumn('consumables', 'is_moq') || Schema::hasColumn('consumables', 'description') || Schema::hasColumn('consumables', 'moq_qty')) {
                $request->validate($this->consumableDescriptionMoqRules());
            }

            $monthEndpoBuyer = $this->validatedMonthEndpoBuyerForRequest($request);

            $oldQty = $consumable->quantity;
            $supplierId = $consumable->supplier;
            $consumable->name = $request['name'];
            $consumable->rate = $request['rate'];
           $consumable->supplier = json_encode($request->input('supplier', []));

            $consumable->unit_type_id = $request['unit_type_id'];
            $consumable->payment_terms = $request['payment_terms'];
            $consumable->quantity = $request['quantity'];
         $consumable->EAN = strtoupper($request['EAN']);
            if (Schema::hasColumn('consumables', 'SKU') && $request->has('SKU')) {
                $sku = strtoupper(trim((string) $request['SKU']));
                $consumable->SKU = $sku !== '' ? $sku : null;
            }
            $consumable->gst = $request['gst'];
            $consumable->monthEndpo_supplier =  $request['monthEndpo_supplier'];
            if (Schema::hasColumn('consumables', 'monthEndpo_buyer')) {
                $consumable->monthEndpo_buyer = $monthEndpoBuyer;
            }
            if (Schema::hasColumn('consumables', 'hardware_monthend_po_product')) {
                $consumable->hardware_monthend_po_product = 0;
            }
              $consumable->is_container = $request->is_container ?? 0;
            $consumable->container_quantity = $this->normalizeContainerQuantityForRequest($request);

            $moqData = [];
            $this->applyDescriptionMoqToData($request, $moqData);
            foreach ($moqData as $key => $val) {
                $consumable->{$key} = $val;
            }

            if ($consumable->save()) {
                // Only write stock logs when quantity actually changes. The form may require
                // a value (e.g. 0); 0 -> 0 must not create a Manual Stock In/Out line.
                $oldQ = (float) ($oldQty ?? 0);
                $newQ = (float) $request->input('quantity', 0);
                $delta = $newQ - $oldQ;
                $negligible = 0.0001;

                if (abs($delta) < $negligible) {
                    // no movement — skip stock_log + stockout_table rows
                } elseif ($delta > 0) {
                    $addedQty = $newQ - $oldQ;
                    $TotalQuantity = $newQ;
                    // $supplier = supplier::find($supplierId);

                    stockLogConsumable::create([
                        'consumable_id' => $consumable->id,
                        'quantity' => $addedQty,
                        'opening_balance' => $oldQ,
                        'remaining_stock' => $TotalQuantity,
                        'type' => 1,
                        'supplier_name' => null,
                        'batch_balance' => $addedQty,
                        'remark' => 'Manual Stock In',
                    ]);
                } else {
                    $outQty = $oldQ - $newQ;
                    stockoutTableConsumable::create([
                        'consumable_id' => $consumable->id,
                        'orderqty' => $newQ,
                        'receiveqty' => $newQ,
                        'remainingqty' => 0,
                    ]);
                    stockLogConsumable::create([
                        'consumable_id' => $consumable->id,
                        'quantity' => $outQty,
                        'opening_balance' => $oldQ,
                        'remaining_stock' => $newQ,
                        'type' => 2,
                        'remark' => 'Manual Stock Out',

                    ]);
                }

                return redirect('consumables')->with('success', 'Consumable was updated successfully.');
            } else {
                return redirect('consumables')->with('danger', 'Error occurred while saving consumable.');
            }
        } else {
            return redirect('/consumables')->with('danger', 'Consumable was not found.');
        }
    }

	
	public function delete(Request $request, $id)
    {

        consumable::where('id', $id)->update(['is_deleted' => 1]);
        return redirect('/consumables')->with('success', 'Consumable deleted successfully.');
        /*dd($consumable);
        $consumable = consumable::where('id', $id)->first();
        if ($consumable) {
            $myData->where('id', $request->id)->update(['active' => $request->active]);
			if ($consumable->delete()) {
				return redirect('/consumables')->with('success', 'Consumable deleted successfully.');
			} else {
				return redirect('/consumables')->with('danger', 'Consumable was not found.');
			}
        }*/
    }




        public function unitType()
    {
        $unitType  = UnitType::all();
        return view('consumable/unitType', compact('unitType'));
    }
    public function unitTypeCreate()
    {
        return view('consumable/unitTypeCrate');
    }

    public function storeunitType(Request $request)
    {
        $request->validate($this->unitTypeNameRules());

        UnitType::create([
            'name' => $request->input('name'),
            'data_type' => $request->input('data_type'),
        ]);

        return redirect('/unitType')->with('success', 'Unit Type added successfully.');
    }
    public function unitTypeview($id)
    {
        $unitType = UnitType::find($id);
        return view('consumable/unitTypeEdit', compact('unitType'));
    }
    public function updateunitType(Request $request, $id)
    {
        $request->validate($this->unitTypeNameRules((int) $id));

        // Find and update
        $unitType = UnitType::findOrFail($id);
        $unitType->update([
            'name' => $request->input('name'),
            'data_type' => $request->input('data_type'),
        ]);

        // Redirect back or to list with success
        return redirect('/unitType')->with('success', 'Unit Type updated successfully.');
    }

    public function deleteunitType($id)
    {
        $unitType = UnitType::findOrFail($id);
        $unitType->delete();

        return redirect('/unitType')->with('success', 'Unit Type deleted successfully.');
    }

    private function unitTypeNameRules(?int $ignoreId = null): array
    {
        return [
            'name' => [
                'required',
                'string',
                'max:255',
                function ($attribute, $value, $fail) use ($ignoreId) {
                    $query = UnitType::whereRaw('BINARY `name` = ?', [$value]);
                    if ($ignoreId !== null) {
                        $query->where('id', '!=', $ignoreId);
                    }
                    if ($query->exists()) {
                        $fail('The name has already been taken.');
                    }
                },
            ],
        ];
    }

        public function consumables_exportcsv()
    {

        $fileName = 'consumables_export.csv';
        return Excel::download(new ConsumablesExport, $fileName, ExcelFormat::CSV);
    }
	
}