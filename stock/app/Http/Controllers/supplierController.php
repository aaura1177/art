<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\supplier;
use App\supplierProduct;
use \auth;
use App\states;
use App\packagingPrice;
use App\Imports\SuppliersImports;
use App\Imports\SupplierPricingImport;
use App\Exports\SupplierPricingWideExport;
use App\Exports\SupplierPricingImportTemplateExport;
use Maatwebsite\Excel\Facades\Excel;
use Maatwebsite\Excel\Excel as ExcelFormat;
use PDF;
use App\user;


use App\Mail\SendSupplierCredentials;
use Illuminate\Support\Facades\Mail;

use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Pagination\LengthAwarePaginator;
use App\Support\SupplierProductPriceLogWriter;
use App\SupplierProductPriceLog;

class supplierController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth','2fa']);
    }

    public function create()
    {
        $states = states::orderBy('statename', 'ASC')->get();
        return view('supplier/create', ['states'=>$states]);
    }
	
	public function importCSV(Request $request)
    {
        $file = $request->file('importCSV');
        Excel::import(new SuppliersImports, $file);
        return redirect('/supplier')->with('success', 'Product Supplier Excel was updated successfully.');
    }

    public function store(Request $request)
    {       
        //dd($request);
        $request->validate([
            'c_name' => 'required|unique:suppliers,c_name',
            'short_name' => 'required|unique:suppliers,short_name',
        ]);

        $s = supplier::create(array_merge([
            'c_name'=>$request['c_name'],  
            'name'=>$request['name'],
            'pan'=>strtoupper($request['pan']),
            'address1'=>$request['address1'],
            'address2'=>$request['address2'],
            'city'=>$request['city'],
            'state'=>$request['state'],
            'country'=>$request['country'],
            'postcode'=>$request['postcode'],
            'gst'=>$request['gst'],
            'gstin'=>strtoupper($request['gstin']),
            'state_code'=>$request['statecode'],
            'email'=>$request['email'],
            'phone1'=>$request['phone1'],
            'phone2'=>$request['phone2'],
            'tds'=>$request['tds'],
            'tdspercent'=>$request['tdspercent'],
            'tdsledger'=>$request['tdsledger'],
            'tdsdate'=>$request['tdsdate'],
            'gstpercent'=>$request['gstpercent'],
            'type'=>$request['type'],
            'do_packaging'=>$request['do_packaging'],
            'short_name'=>$request['short_name'],
            'monthly_invoice_limit'=> $request['monthly_invoice_limit'],
        ], $this->poLimitAttributesFromRequest($request)));

        $user = user::create([
            'firstname'=>$request['c_name'],
            'lastname'=>$request['name'],
            'email'=>$request['email'],  
            'password'=>Hash::make('password'),
            'role' => 'Supplier', 
            'supplier_id' => $s->id
        ]);
        
$user->assignRole('supplier');
Mail::to($request->input('email'))->send(new SendSupplierCredentials($request->input('email'), 'password',$request['c_name']." ".$request['name']));
        if(isset($request['po']) && !empty($request['po'])){
            foreach ($request['po'] as $po) {
                $sp = supplierProduct::create([
                    'product_id' => $po['product'],
                    'rate' => $po['rate'],
                    'uk_45_rate' => $po['uk_45_rate'] ?? null,
                    'supplier_id' => $s->id
                ]);
                SupplierProductPriceLogWriter::log($sp, 'create', 'supplier_create', null);
            }
        }

      return redirect('/supplier')->with('success', 'Supplier was added successfully.');;
    }

    public function index()
    {
        $supplier=supplier::get();
        return view('supplier/index',['supplier'=>$supplier]);
    }

    public function view($id)
    {

        $supplier = supplier::find($id);
        $states = states::orderBy('statename', 'ASC')->get();
        $poProduct = supplierProduct::where('supplier_id',$id)->get();
        $packaging_pricing = packagingPrice::where('supplier_id',$id)->first();
        if ($supplier) {
            return view('supplier/view', ['supplier'=>$supplier, 'states'=>$states,'poProduct'=>$poProduct,'packaging_pricing'=>$packaging_pricing]);
        } else {
            return redirect('/supplier')->with('danger', 'Supplier was not found.');
        }
    }

    public function update(Request $request, $id)
    {   
        $request->validate([
            'short_name' => [
                'required',
                'max:4',
                Rule::unique('suppliers')->ignore($id),
            ],
        ]);

        $supplier = supplier::find($id);
        $poProduct = supplierProduct::where('supplier_id',$id)->get();
       
        if ($supplier) {
            $supplier->c_name=$request['c_name'];  
            $supplier->name=$request['name'];
            $supplier->pan=strtoupper($request['pan']);
            $supplier->address1=$request['address1'];
            $supplier->address2=$request['address2'];
            $supplier->city=$request['city'];
            $supplier->state=$request['state'];
            $supplier->country=$request['country'];
            $supplier->postcode=$request['postcode'];
            $supplier->gst=$request['gst'];
            $supplier->gstin=strtoupper($request['gstin']);
            $supplier->state_code=$request['statecode'];
            $supplier->email=$request['email'];
            $supplier->phone1=$request['phone1'];
            $supplier->phone2=$request['phone2'];
            $supplier->tds=$request['tds'];
            $supplier->tdspercent=$request['tdspercent'];
            $supplier->tdsledger=$request['tdsledger'];
            $supplier->tdsdate=$request['tdsdate'];
            $supplier->gstpercent=$request['gstpercent'];
            $supplier->type=$request['type'];
            $supplier->do_packaging=$request['do_packaging'];
            $supplier->short_name=$request['short_name'];
            $supplier->monthly_invoice_limit = $request['monthly_invoice_limit'];
            foreach ($this->poLimitAttributesFromRequest($request) as $key => $value) {
                $supplier->{$key} = $value;
            }

            if(isset($poProduct) && !empty($poProduct)){
                foreach ($poProduct as $existingRow) {
                    $incoming = null;
                    if (isset($request['po']) && is_array($request['po'])) {
                        foreach ($request['po'] as $po) {
                            if ((int) ($po['product'] ?? 0) === (int) $existingRow->product_id) {
                                $incoming = $po;
                                break;
                            }
                        }
                    }
                    if ($incoming === null) {
                        SupplierProductPriceLogWriter::logRemove(
                            (int) $existingRow->product_id,
                            (int) $existingRow->supplier_id,
                            (int) $existingRow->id,
                            SupplierProductPriceLogWriter::buildState($existingRow),
                            'supplier_update'
                        );
                    }
                    $existingRow->delete();
                }
             }

            if(isset($request['po']) && !empty($request['po'])){
                foreach ($request['po'] as $po) {
                    $beforeState = null;
                    $eventType = 'create';
                    foreach ($poProduct as $old) {
                        if ((int) $old->product_id === (int) $po['product']) {
                            $beforeState = SupplierProductPriceLogWriter::buildState($old);
                            $eventType = 'update';
                            break;
                        }
                    }
                    $sp = supplierProduct::create([
                        'product_id' => $po['product'],
                        'rate' => $po['rate'],
                        'uk_45_rate' => $po['uk_45_rate'] ?? null,
                        'supplier_id' => $supplier->id
                    ]);
                    SupplierProductPriceLogWriter::log($sp, $eventType, 'supplier_update', $beforeState);
                }
            }
            
            if(isset($request['packaging_pricing']) && !empty($request['packaging_pricing'])){
                $packaging_pricing = packagingPrice::where('supplier_id',$id)->first();
                $packaging_pricing->{'3ply'} = $request['packaging_pricing']['3ply'];
                $packaging_pricing->{'5ply'} = $request['packaging_pricing']['5ply'];
                $packaging_pricing->{'7ply'} = $request['packaging_pricing']['7ply'];
                $packaging_pricing->save();
            }

            if ($supplier->save()) {
                return redirect('supplier')->with('success', 'Supplier was updated successfully.');
            } else {
                return redirect('supplier')->with('danger', 'Error occurred while saving product.');
            }
        } else {
            return redirect('/supplier')->with('danger', 'supplier was not found.');
        }
    }

    public function data()
    {
        $states=states::all();
        return response()->json(['states'=>$states]);
    }

    public function delete(Request $request, $id)
    {
        $supplier = supplier::where('id', $id)->first();
        $supplierRelationCount = $supplier->rejectRepair->count() + $supplier->purchaseOrder->count();
        if($supplierRelationCount > 0){
            return redirect('/supplier')->with('danger', 'Supplier cannot be deleted. Supplier exist in other relation.');
        }
        else {
            if ($supplier->delete()) {
                return redirect('/supplier')->with('success', 'Supplier deleted successfully.');
            } else {
                return redirect('/supplier')->with('danger', 'Supplier was not found.');
            }
        }
    }

    public function pricingSupp(Request $request)
    {
        $allowedPerPage = [10, 25, 50, 100, 200];
        $perPage = (int) $request->query('per_page', 25);
        if (! in_array($perPage, $allowedPerPage, true)) {
            $perPage = 25;
        }
        $q = trim((string) $request->query('q', ''));

        $page = max(1, (int) $request->query('page', 1));

        $baseQuery = supplierProduct::query()
            ->whereHas('product')
            ->whereHas('supplier')
            ->join('product_table', 'product_table.id', '=', 'supplier_products.product_id')
            ->join('suppliers', 'suppliers.id', '=', 'supplier_products.supplier_id');

        if ($q !== '') {
            $baseQuery->where(function ($query) use ($q) {
                $query->where('product_table.code', 'like', '%'.$q.'%')
                    ->orWhere('suppliers.c_name', 'like', '%'.$q.'%')
                    ->orWhereRaw('CAST(supplier_products.rate AS CHAR) like ?', ['%'.$q.'%'])
                    ->orWhereRaw('CAST(supplier_products.uk_45_rate AS CHAR) like ?', ['%'.$q.'%']);
            });
        }

        $totalProducts = (int) (clone $baseQuery)
            ->selectRaw('COUNT(DISTINCT supplier_products.product_id) AS aggregate')
            ->value('aggregate');

        $lastPage = max(1, (int) ceil($totalProducts / $perPage));
        if ($totalProducts > 0 && $page > $lastPage) {
            return redirect()->to($request->fullUrlWithQuery(['page' => $lastPage]));
        }

        $productIds = (clone $baseQuery)
            ->orderBy('product_table.code')
            ->groupBy('supplier_products.product_id', 'product_table.code')
            ->select('supplier_products.product_id')
            ->forPage($page, $perPage)
            ->pluck('product_id');

        $groupedFull = supplierProduct::with(['product', 'supplier'])
            ->whereIn('product_id', $productIds)
            ->get()
            ->groupBy('product_id');

        $groupsInOrder = collect($productIds)->map(function ($id) use ($groupedFull) {
            return $groupedFull->get($id);
        })->filter();

        $items = $groupsInOrder->values()->all();

        $paginator = new LengthAwarePaginator(
            $items,
            $totalProducts,
            $perPage,
            $page,
            ['path' => $request->url()]
        );
        $paginator->withQueryString();

        $latestLogs = SupplierProductPriceLogWriter::latestByProductSupplier($productIds);

        return view('supplier/pricing', [
            'paginator' => $paginator,
            'perPage' => $perPage,
            'q' => $q,
            'latestLogs' => $latestLogs,
        ]);
    }

    public function pricingHistory(Request $request)
    {
        $q = trim((string) $request->query('q', ''));
        $perPage = (int) $request->query('per_page', 50);
        if (! in_array($perPage, [25, 50, 100, 200], true)) {
            $perPage = 50;
        }

        if (! SupplierProductPriceLogWriter::tableExists()) {
            return view('supplier.pricing_history.index', [
                'logs' => new LengthAwarePaginator([], 0, $perPage),
                'q' => $q,
                'perPage' => $perPage,
                'tableMissing' => true,
            ]);
        }

        $query = SupplierProductPriceLog::with(['product', 'supplier'])->orderByDesc('id');

        if ($q !== '') {
            $query->where(function ($inner) use ($q) {
                $inner->whereHas('product', function ($p) use ($q) {
                    $p->where('code', 'like', '%'.$q.'%');
                })->orWhereHas('supplier', function ($s) use ($q) {
                    $s->where('c_name', 'like', '%'.$q.'%');
                })->orWhere('changed_by_label', 'like', '%'.$q.'%')
                    ->orWhere('change_summary', 'like', '%'.$q.'%')
                    ->orWhere('event_type', 'like', '%'.$q.'%')
                    ->orWhere('source', 'like', '%'.$q.'%');
            });
        }

        $logs = $query->paginate($perPage)->withQueryString();

        return view('supplier.pricing_history.index', [
            'logs' => $logs,
            'q' => $q,
            'perPage' => $perPage,
            'tableMissing' => false,
        ]);
    }

    public function pricingHistoryDetail(Request $request, $productId, $supplierId)
    {
        $productId = (int) $productId;
        $supplierId = (int) $supplierId;

        $product = \App\product::find($productId);
        $supplier = \App\supplier::find($supplierId);
        $current = supplierProduct::where('product_id', $productId)
            ->where('supplier_id', $supplierId)
            ->first();

        $logs = collect();
        $tableMissing = ! SupplierProductPriceLogWriter::tableExists();
        if (! $tableMissing) {
            $logs = SupplierProductPriceLog::with(['product', 'supplier'])
                ->where('product_id', $productId)
                ->where('supplier_id', $supplierId)
                ->orderByDesc('id')
                ->get();
        }

        return view('supplier.pricing_history.detail', [
            'product' => $product,
            'supplier' => $supplier,
            'current' => $current,
            'logs' => $logs,
            'tableMissing' => $tableMissing,
        ]);
    }

    public function pricingExportExcel()
    {
        $filename = 'supplier_pricing_'.date('Y-m-d_His').'.xlsx';

        return Excel::download(new SupplierPricingWideExport, $filename);
    }

    public function pricingExportCsv()
    {
        $filename = 'supplier_pricing_'.date('Y-m-d_His').'.csv';

        return Excel::download(new SupplierPricingWideExport, $filename, ExcelFormat::CSV);
    }

    public function pricingExportPdf()
    {
        $grouped = supplierProduct::with(['product', 'supplier'])
            ->whereHas('product')
            ->whereHas('supplier')
            ->get()
            ->groupBy('product_id')
            ->sortBy(function ($group) {
                $product = $group->first()->product;

                return $product ? $product->code : '';
            });

        $downloadedAt = now()->format('Y-m-d H:i:s');
        $pdf = PDF::loadView('supplier.pricing_pdf', compact('grouped', 'downloadedAt'));

        return $pdf->download('supplier_pricing_'.date('Y-m-d_His').'.pdf');
    }

    public function pricingImportTemplate()
    {
        return Excel::download(
            new SupplierPricingImportTemplateExport,
            'supplier_pricing_import_template.xlsx'
        );
    }

    public function pricingImport(Request $request)
    {
        $request->validate([
            'pricing_import_file' => 'required|file|mimes:xlsx,xls,csv|max:5120',
        ], [
            'pricing_import_file.required' => 'Please choose a file to upload.',
            'pricing_import_file.mimes' => 'The file must be xlsx, xls, or csv.',
        ]);

        $import = new SupplierPricingImport;
        Excel::import($import, $request->file('pricing_import_file'));

        return redirect('/supplier/pricing')->with('success', $import->summary());
    }

    /**
     * Normalize PO limit columns from request (type + is_merged). Clears unused columns.
     *
     * @return array<string, mixed>
     */
    private function poLimitAttributesFromRequest(Request $request): array
    {
        $empty = [
            'is_merged' => 0,
            'po_monthly_limit_furniture' => null,
            'po_limit_start_date_furniture' => null,
            'po_monthly_limit_consumable' => null,
            'po_limit_start_date_consumable' => null,
            'po_monthly_limit_merged' => null,
            'po_limit_start_date_merged' => null,
        ];

        $type = (string) $request->input('type', '');
        if (in_array($type, ['Service', 'Packaging'], true)) {
            return $empty;
        }

        $isMerged = $request->boolean('is_merged');
        if ($isMerged) {
            return [
                'is_merged' => 1,
                'po_monthly_limit_furniture' => null,
                'po_limit_start_date_furniture' => null,
                'po_monthly_limit_consumable' => null,
                'po_limit_start_date_consumable' => null,
                'po_monthly_limit_merged' => $request->input('po_monthly_limit_merged') ?: null,
                'po_limit_start_date_merged' => $request->input('po_limit_start_date_merged') ?: null,
            ];
        }

        $attrs = $empty;
        if (in_array($type, ['Furniture', 'Both'], true)) {
            $attrs['po_monthly_limit_furniture'] = $request->input('po_monthly_limit_furniture') ?: null;
            $attrs['po_limit_start_date_furniture'] = $request->input('po_limit_start_date_furniture') ?: null;
        }
        if (in_array($type, ['Consumable', 'Both'], true)) {
            $attrs['po_monthly_limit_consumable'] = $request->input('po_monthly_limit_consumable') ?: null;
            $attrs['po_limit_start_date_consumable'] = $request->input('po_limit_start_date_consumable') ?: null;
        }

        return $attrs;
    }
}
