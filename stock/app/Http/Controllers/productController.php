<?php

namespace App\Http\Controllers;

use App\product;
use App\invoice;
use App\contractorBill;
use App\category;
use App\WfConsumable;
use App\packaging;
use App\UnitType;
use App\ProductCarton;
use App\consumable;
use App\ProductLedger;
use App\Imports\CartonProductsImport;
use App\hardwares;
use App\stockLog;
use App\supplier;
use App\Batch;
use App\BatchProduct;
use Illuminate\Support\Facades\DB;
use App\setting;
use App\FinshingExtraPrice;
use App\productLocations;
use App\Imports\BatchImport;
use App\subCategory;
use App\productGrouping;
use App\Exports\AllProductsExport;
use App\Exports\ProductCodeWiseExport;
use App\Exports\exportValuationExcel;
use App\Exports\exportInventory;
use App\Exports\BatchProductWiseExport;

use Illuminate\Http\Request;
use Illuminate\Database\Eloquent\Relations;
use \auth;
use PDF;
use Image;
use Maatwebsite\Excel\Facades\Excel;
use App\Imports\ProductImport;
use App\Imports\FinishingImport;
use App\Imports\FamilyImport;
use App\Imports\LegImport;
use App\Exports\BatchExport;
use App\Exports\FinishingPriceRecalcExport;
use App\Imports\ErpProductAddImport;
use App\Imports\ErpProductLessImport;
use App\Imports\ErpProductLessImportManager;
use App\Imports\ErpProductAdd2ImportManager;
use App\Imports\ErpProductAddImportManager;
use App\Imports\ErpProductImportManager;
use App\Imports\ErpProductImport;

use App\Imports\ProductWeightLbsImport;

use App\Imports\ProductConsumableImport;
use App\Imports\ErpUsProductImportManager;
use App\Imports\ErpUsProductImport;
use App\Imports\ErpUsProductAdd2ImportManager;
use App\Imports\ErpUsProductAddImportManager;
use App\Imports\ErpUsProductLessImportManager;
use App\Imports\ErpUsProductAddImport;
use App\Imports\ErpUsProductLessImport;

use App\Imports\ErpEuProductImportManager;
use App\Imports\ErpEuProductImport;
use App\Imports\ErpEuProductAdd2ImportManager;
use App\Imports\ErpEuProductAddImportManager;
use App\Imports\ErpEuProductLessImportManager;
use App\Imports\ErpEuProductAddImport;
use App\Imports\ErpEuProductLessImport;

use App\Exports\inReport;
use App\Exports\outReport;
use App\Exports\ErpProductsExport;
use App\Exports\ErpDateWiseExport;
use App\Exports\ErpDateWiseExportManager;
use App\Exports\ErpDiffProductsExport;

use App\Exports\ErpUsProductsExport;
use App\Exports\ErpUsDateWiseExport;
use App\Exports\ErpUsDateWiseExportManager;
use App\Exports\ErpUsDiffProductsExport;

use App\Exports\ErpEuProductsExport;
use App\Exports\ErpEuDateWiseExport;
use App\Exports\ErpEuDateWiseExportManager;
use App\Exports\ErpEuDiffProductsExport;
use App\Exports\ProductSmallHardwareExport;
use App\Exports\ProductConsumableTemplateExport;
use App\Exports\CartonProductsTemplateExport;
use App\Exports\ProductWeightLbsTemplateExport;

use App\Exports\productAnnualReport;
use App\Exports\inventoryDateWiseReport;
use App\legs;
use App\finishRate;
use App\pbTable;
use App\smallhardwares;
use App\smallHardwareProducts;
use App\ErpProduct;
use App\ErpSheet;
use App\ErpHistory;
use App\ErpHistoryManager;
use App\ErpSheetManager;

use App\ErpUsProduct;
use App\ErpUsSheet;
use App\ErpUsHistory;
use App\ErpUsHistoryManager;
use App\ErpUsSheetManager;
use Illuminate\Support\Facades\Storage;

use App\ErpEuProduct;
use App\ErpEuSheet;
use App\ErpEuHistory;
use App\ErpEuHistoryManager;
use App\ErpEuSheetManager;
use Exception;

use App\Exports\HardwareProductExport;

class productController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth']);
    }


    public function hardwareProduct()
{
    return Excel::download(new HardwareProductExport, 'hardware_products.xlsx');
}

    public function exportcsv()
    {
        return (new AllProductsExport())->download('products.xlsx');
    }

    public function create()
    {
        $category = category::get();
        $subCategory = subCategory::get();
        $ProductLedger = ProductLedger::get();
        $hardwares = hardwares::get();
        $finishRates = finishRate::get();
        $FinshingExtraPrice = FinshingExtraPrice::all();
        return view('product/create', ['category' => $category, 'subCategory' => $subCategory, 'hardwares' => $hardwares, 'finishRates' => $finishRates, 'ProductLedger' => $ProductLedger,'FinshingExtraPrice'=>$FinshingExtraPrice]);
    }

    public function store(Request $request)
    {
        $EAN = preg_replace("/[~!@#$%^&*()_+=`{}\[\]\|\"\:;'<>,.\/? ]+/", "", $request['ean']);

        $request->validate([
            'code' => 'unique:product_table',
            'ean' => 'unique:product_table',
        ]);

        if (!isset($errors)) {

            // if ($request->hasfile('imgURL')) {
            //     $file = $request->file('imgURL');
            //     $extension = $file->getClientOriginalExtension(); // getting image extension
            //     $filename = $EAN . '.' . $extension;

            //     $directory = public_path('../../uploads/product');
            //     $imageUrl = $directory . '/' . $filename;
            //     Image::make($file)->resize(200, 200, function ($constraint) {
            //         $constraint->aspectRatio();
            //     })->save($imageUrl);
            // } else {

            //     $filename = 'default.jpg';
            // }


            if ($request->hasFile('imgURL')) {
                $filePath = Storage::disk('s3')->put('stock/product', $request->file('imgURL'));
                $imageFileName = basename($filePath); 
            } else {
                $imageFileName = 'default.jpg';
            }

            // UI already sets finishing_price as (base + selected extra), so persist it directly.
            $totalfinshingprice = $request['finishing_price'];

            product::create([

                'code' => strtoupper($request['code']),
                'EAN' => strtoupper($EAN),
                'HSN' => strtoupper($request['hsn']),
                'name' => $request['name'],
                'category_id' => $request['category'],
                'finishing' => $request['finishing'],
                'width' => $request['width'],
                'height' => $request['height'],
                'depth' => $request['depth'],
                'boxwidth' => $request['boxwidth'],
                'boxheight' => $request['boxheight'],
                'boxdepth' => $request['boxdepth'],
                'wholesalevolume' => $request['wholesalevolume'],
                'dropshipvolume' => $request['dropshipvolume'],
                'volume' => $request['volume'],
                'imageURL' => $imageFileName,
                'hardware1' => $request['hardware1'],
                'hardware2' => $request['hardware2'],
                'hardware3' => $request['hardware3'],
                'hardware4' => $request['hardware4'],
                'hardware5' => $request['hardware5'],
                'hardware1_quantity' => $request['hardware1_quantity'],
                'hardware2_quantity' => $request['hardware2_quantity'],
                'hardware3_quantity' => $request['hardware3_quantity'],
                'hardware4_quantity' => $request['hardware4_quantity'],
                'hardware5_quantity' => $request['hardware5_quantity'],
                'upholstry' => $request['upholstry'],
                'corner' => $request['corner'],
                'lhardware' => $request['lhardware'],
                'addons' => $request['addons'],
                'remarks' => $request['remarks'],
                'subcategory_id' => $request['subcategory'],
                'product_ledger_id' => $request['ProductLedgers'],
                'gstslab' => $request['gstslab'],
                'gross_weight' => $request['gross_weight'],
                'net_weight' => $request['net_weight'],
                'finishing_price' => $totalfinshingprice,
                'unit' => $request['unit'] ?? 'No.',
                'finshing_extra_price_id' => $request['finshing_extra_price_id'] ,
            ]);
        };

        return redirect('/product')->with('success', 'Product was added successfully.');
    }

    // public function index()
    // {
    //     $products = product::get();
    //     return view('product/index', ['products' => $products]);
    // }



    public function index(Request $request)
    {
        
        
        $search = $request->input('search');

    $products = product::with('category', 'subCategory')
        ->when($search, function ($query) use ($search) {
            $query->where('code', 'like', "%{$search}%");
        })

        ->when($search, function ($query) {
            $query->orderBy('created_at', 'ASC');
        })

        ->when(!$search, function ($query) {
            $query->orderBy('created_at', 'DESC');
        })

        ->paginate(100);


        $files = Storage::disk('s3')->files('stock/product');

        // Create a map: ['filename.jpg' => 'full-url']
        $fileMap = [];
        foreach ($files as $file) {
            $filename = basename($file);
            $fileMap[$filename] = Storage::disk('s3')->url($file);
        }

        
        return view('product.index', ['products' => $products, 'fileMap' => $fileMap]);
    }

    public function importCSV(Request $request)
    {
        $file = $request->file('importCSV');
        Excel::import(new ProductImport, $file);
        return redirect('/product')->with('success', 'Product Excel was updated successfully.');
    }

    public function importCSVfinishing(Request $request)
    {
        $file = $request->file('finishingCSV');
        Excel::import(new FinishingImport, $file);
        return redirect('/product')->with('success', 'Finishing Excel was updated successfully.');
    }

    public function viewreport()
    {
        $product = product::where('quantity', '<=', '10')->get();
        return view('report/index', ['product' => $product]);
    }

    public function view($id)
    {

        $product = product::find($id);
        $WfConsumable = WfConsumable::where('product_id', $id)->with('unitType')->get();

        $ProductCarton = ProductCarton::where('product_id', $id)->first();
        $packaging = packaging::where('product_id', $id)->with('product')->first();

        if (!$product) {
            return redirect('/product')->with('danger', 'product not found.');
        }

        $imagePath = $product->imageURL;

        // Clean the DB path to match file names in S3
        $parsedPath = parse_url($imagePath, PHP_URL_PATH);
        $cleanPath = ltrim($parsedPath, '/');

        // Get all files from the 'stock/product' folder on S3
        $files = Storage::disk('s3')->files('stock/product');

        // Generate full URLs for all files
        $fileUrls = array_map(function ($file) {
            return Storage::disk('s3')->url($file);
        }, $files);

        // Match files that contain the cleaned image path
        $matchedImage = array_filter($fileUrls, function ($url) use ($cleanPath) {
            return str_contains($url, $cleanPath);
        });

        $category = category::get();
        $subCategory = subCategory::get();
        $hardwares = hardwares::get();
        $ProductLedger = ProductLedger::get();
        $finishRates = finishRate::get();
        $FinshingExtraPrice = FinshingExtraPrice::all();
        $smallhardwares = smallhardwares::get();
        $smallhardwareproducts = smallHardwareProducts::where('product_id', $id)->get();
        $unitType = UnitType::all();
$consumable = consumable::where(function($q) {
        $q->where('is_container', 0)
          ->orWhereNull('is_container');
    })
    ->get();


        if ($product) {
            return view('product/view', ['product' => $product, 'category' => $category, 'subCategory' => $subCategory, 'hardwares' => $hardwares, 'finishRates' => $finishRates, 'matchedImage' => $matchedImage, 'smallhardwares' => $smallhardwares, 'smallhardwareproducts' => $smallhardwareproducts, 'ProductLedger' => $ProductLedger,'FinshingExtraPrice'=>$FinshingExtraPrice, 'unitType' => $unitType, 'WfConsumable' => $WfConsumable, 'consumable' => $consumable, 'packaging' => $packaging, 'ProductCarton' => $ProductCarton]);
        } else {
            return redirect('/product')->with('danger', 'Product was not found.');
        }
    }

     public function update(Request $request, $id)
    {
        // return $request->all();
        $product = product::find($id);

        if ($product) {




            $packaging = packaging::where('product_id', $product->id)->first();

            ProductCarton::updateOrCreate(
                [
                    'product_id'   => $product->id,
                ],
                [
                    'packaging_id' => $packaging->id,
                    'quantity1'    => $request->quantity1,
                    'quantity2'    => $request->quantity2,
                ]
            );





            if ($request->hasFile('imgURL')) {

                $existingImage = $product->imageURL;

                $oldFilePath = 'stock/product/' . $existingImage;

                if (Storage::disk('s3')->exists($oldFilePath)) {
                    Storage::disk('s3')->delete($oldFilePath);
                }

                $filePath = Storage::disk('s3')->put('stock/product', $request->file('imgURL'));

                $imageFileName = basename($filePath);

                $product->update(['imageURL' => $imageFileName]);
            } else {
                // No new file uploaded, keep old image or use default
                $imageFileName = $product->imageURL ?? 'default.jpg';
            }

            // UI already sets finishing_price as (base + selected extra), so persist it directly.
            $totalfinshingprice = $request['finishing_price'];

            $old_quantity = $product->quantity;
            $new_quantity = $request['quantity'];


            $product->EAN = strtoupper($request['ean']);
            $product->HSN = strtoupper($request['hsn']);
            $product->name = $request['name'];
            $product->quantity = $request['quantity'];
            $product->category_id = $request['category'];
            $product->finishing = $request['finishing'];
            $product->width = $request['width'];
            $product->height = $request['height'];
            $product->depth = $request['depth'];
            $product->boxwidth = $request['boxwidth'];
            $product->boxheight = $request['boxheight'];
            $product->boxdepth = $request['boxdepth'];
            $product->wholesalevolume = $request['wholesalevolume'];
            $product->dropshipvolume = $request['dropshipvolume'];
            $product->volume = $request['volume'];
            // $product->imageURL = $filename;
            $product->hardware1 = $request['hardware1'];
            $product->hardware2 = $request['hardware2'];
            $product->hardware3 = $request['hardware3'];
            $product->hardware4 = $request['hardware4'];
            $product->hardware5 = $request['hardware5'];
            $product->hardware1_quantity = $request['hardware1_quantity'];
            $product->hardware2_quantity = $request['hardware2_quantity'];
            $product->hardware3_quantity = $request['hardware3_quantity'];
            $product->hardware4_quantity = $request['hardware4_quantity'];
            $product->hardware5_quantity = $request['hardware5_quantity'];
            $product->upholstry = $request['upholstry'];
            $product->corner = $request['corner'];
            $product->lhardware = $request['lhardware'];
            $product->addons = $request['addons'];
            $product->remarks = $request['remarks'];
            $product->subcategory_id = $request['subcategory'];
            $product->product_ledger_id = $request['ProductLedger'];
            $product->gstslab = $request['gstslab'];
            $product->finishing_price = $totalfinshingprice;
              $product->unit = $request['unit'] ?? 'No.';
              $product->gross_weight = $request['gross_weight'];
            $product->net_weight = $request['net_weight'] ?? 0;
            $product->finshing_extra_price_id = $request['finshing_extra_price_id'];
            if ($product->save()) {
                if ($new_quantity != $old_quantity) {
                    if ($new_quantity > $old_quantity) {
                        $type = 1;
                        $log_quantity = $new_quantity - $old_quantity;
                    }
                    if ($new_quantity < $old_quantity) {
                        $type = 2;
                        $log_quantity = $old_quantity - $new_quantity;
                    }
                    $s = stockLog::create([
                        'product_id' => $product->id,
                        'voucher_no' => 'Manual Modification',
                        'ref_no' => 'Manual Modification',
                        'quantity' => $log_quantity,
                        'opening_balance' => $old_quantity,
                        'remaining_stock' => $product->quantity,
                        'type' => $type,
                    ]);
                }
                $shp = smallHardwareProducts::where('product_id', $product->id)->get();
                if (isset($shp)) {
                    foreach ($shp as $shp) {
                        $shp->delete();
                    }
                }
                foreach ($request->small_hardware as $sm) {
                    if ($sm['id'] > 0) {
                        $smh = smallhardwares::where('id')->first();
                        if ($sm['price'] != '') {
                            $smprice = $sm['price'];
                        } else {
                            $smprice = null;
                        }

                        smallHardwareProducts::create([
                            'product_id' => $product->id,
                            'small_hardware_id' => $sm['id'],
                            'size' => $sm['size'],
                            'quantity' => $sm['quantity'],
                            'price' => $smprice,
                        ]);
                    }
                }
                // Always re-sync wf_consumable rows from request.
                // This allows users to remove all consumables (submit with no rows).
                WfConsumable::where('product_id', $product->id)->delete();

                $consumablesInput = $request->input('consumables', []);
                if (is_array($consumablesInput)) {
                    foreach ($consumablesInput as $consumable) {
                        $consumableId = (int) ($consumable['consumable_id'] ?? 0);
                        if ($consumableId <= 0) {
                            continue;
                        }

                        WfConsumable::create([
                            'product_id' => $product->id,
                            'consumables_id' => $consumableId,
                            'unit_type_id' => $consumable['unit_type_id'] ?? null,
                            'unit_type_name' => $consumable['unit_type_name'] ?? null,
                            'qty' => $consumable['quantity'] ?? 0,
                        ]);
                    }
                }



                return redirect('product')->with('success', 'Product was updated successfully.');
            } else {
                return redirect('product')->with('danger', 'Error occurred while saving product.');
            }
        } else {
            return redirect('/product')->with('danger', 'Product was not found.');
        }
    }


    public function delete(Request $request, $id)
    {
        $product = Product::find($id);
    
        if (!$product) {
            return redirect('/product')->with('danger', 'Product not found.');
        }
    
        // Count related data
        $productRelationsCount = $product->rejectRepair->count()
                                + $product->poTable->count()
                                + $product->pbTable->count()
                                + $product->invoiceTable->count()
                                + $product->stockoutTable->count();
    
        if ($productRelationsCount > 0) {
            return redirect('/product')->with('danger', 'Product cannot be deleted due to existing relations.');
        }
    
        if ($product->imageURL && $product->imageURL !== 'default.jpg') {
            $fileName = basename($product->imageURL); // Extract just the filename
            $filePath = 'stock/product/' . $fileName;
        
            if (Storage::disk('s3')->exists($filePath)) {
                Storage::disk('s3')->delete($filePath);
            }
        }
    
        if ($product->delete()) {
            return redirect('/product')->with('success', 'Product deleted successfully.');
        } else {
            return redirect('/product')->with('danger', 'Product could not be deleted.');
        }
    }
    

    public function printList(Request $request)
    {
        $code        =    $request['code'];
        if ($code == "All") {
            $products    =    product::orderBy('code', 'ASC')->get();
        } else {
            $products    =    product::where('code', 'like', '%' . $code . '%')->orderBy('code', 'ASC')->get();
        }
        return view('product/productList', ['products' => $products]);
    }

    public function exportCodeWiseExcel(Request $request)
    {
        $code    =    $request['code'];
        return (new ProductCodeWiseExport($code))->download('products.xlsx');
    }

    public function valuation()
    {
        $products    =    product::where('quantity', '>', '0')->orderBy('code', 'ASC')->get();
        return view('product/valuation', ['products' => $products]);
    }

    public function product_age()
    {
        $products    =    product::where('quantity', '>', '0')->orderBy('code', 'ASC')->get();
        return view('product/age', ['products' => $products]);
    }

    public function valuationPdfView(Request $request)
    {
        $companyDetails = setting::first();
        if ($request['from'] == "") {
            $from        =    date('Y-m-d');
            //return view('product/valuationpdf', ['products'=>$products]);
        } else {
            $from        =    date('Y-m-d', strtotime($request['from']));
        }
        $products    =    product::selectRaw('*,(SELECT remaining_stock FROM stock_log WHERE product_id=product_table.id AND created_at <= "' . $from . ' 23:59:59" ORDER BY created_at desc, id desc LIMIT 1) as remaining_stock')->orderBy('code', 'ASC')->get();
        foreach ($products as $key => $product) {
            $products[$key]['pbs'] = pbTable::selectRaw('*, (SELECT rate FROM pb_table WHERE created_at <= "' . $from . ' 23:59:59" AND product_id = ' . $product->id . ' AND rate > 1 ORDER BY created_at DESC LIMIT 1) as actual_rate')->where('created_at', '<=', $from . ' 23:59:59')->where('product_id', $product->id)->get();
        }

        return view('product/valuationpdf', ['products' => $products, 'companyDetails' => $companyDetails, 'from' => $from]);
        //$pdf 		= 	PDF::loadView('product/valuationpdf',['products'=>$products,'companyDetails'=>$companyDetails,'from'=>$from]);
        //return $pdf->download('valuationpdf.pdf');
    }

    public function exportValuationExcel(Request $request)
    {
        if ($request['from'] == "") {
            $from        =    date('Y-m-d');
        } else {
            $from        =    date('Y-m-d', strtotime($request['from']));
        }
        return (new exportValuationExcel($from))->download('stockValuation.xlsx');
    }

    public function inventory_report(Request $request)
    {
        $search = $request->input('search');

        $logs = stockLog::with('product')->where('type', '<', '3')
                    ->when($search, function ($query, $search) {
                        $query->where('opening_balance', 'like', "%{$search}%")
                            ->orWhere('remaining_stock', 'like', "%{$search}%")
                            ->orWhere('type', 'like', "%{$search}%")
                            ->orWhere('voucher_no', 'like', "%{$search}%")
                            ->orWhere('supplier_inv_no', 'like', "%{$search}%")
                            ->orWhere('ref_no', 'like', "%{$search}%")
                            ->orWhereHas('product', function ($q) use ($search) {
                                $q->where('name', 'like', "%{$search}%");
                                $q->orWhere('code', 'like', "%{$search}%");
                            });
                    })->orderBy('created_at','desc')
                    ->paginate(100);

        return view('product/inventory', ['logs' => $logs, 'products' => []]);
    }

    public function inventory_report_pdf(Request $request)
    {
        $from     = $request['from'];
        $to     = $request['to'];

        $from     = date('Y-m-d H:i:s', strtotime($from . " 00:00:00"));
        $to     = date('Y-m-d H:i:s', strtotime($to . " 23:59:59"));
        $logs    = stockLog::whereBetween('created_at', [$from, $to])->where('type', '<', '3')->orderBy('created_at', 'DESC')->get();
        $companyDetails = setting::first();
        $pdf    = PDF::loadView('product/inventory_pdf', ['logs' => $logs, 'companyDetails' => $companyDetails, 'from' => $from, 'to' => $to]);
        return $pdf->download('inventory_pdf.pdf');
        //return view('product/inventory_pdf', ['logs'=>$logs,'companyDetails'=>$companyDetails]);
    }

    public function exportIR(Request $request)
    {
        $from = $request['from'];
        $to = $request['to'];
        return (new exportInventory($from, $to))->download('InventoryReport.xlsx');
    }

    public function downloadInReport(Request $request)
    {
        $from = $request['from'];
        $to = $request['to'];
        $product_id = $request['product_id'];
        return (new inReport($from, $to, $product_id))->download('inReport.xlsx');
    }

    public function downloadOutReport(Request $request)
    {
        $from     = $request['from'];
        $to     = $request['to'];
        $product_id = $request['product_id'];
        return (new outReport($from, $to, $product_id))->download('outReport.xlsx');
    }
    public function downloadProductAnnualReport(Request $request)
    {
        $from     = $request['from'];
        $to     = $request['to'];
        $product_id = $request['product_id'];
        $product = product::find($product_id);
        return (new productAnnualReport($from, $to, $product_id))->download($product->code . '_productReport.xlsx');
    }
    public function downloadDateWiseReport(Request $request)
    {
        $from     = $request['from'];
        $to     = $request['to'];
        return (new inventoryDateWiseReport($from, $to))->download('inventoryDateWiseReport.xlsx');
    }

    public function createGrouping(Request $request)
    {
        $parent_id = $request['parent_id'];
        foreach ($request['child_id'] as $child) {
            productGrouping::create([
                'parent_id' => $parent_id,
                'child_id' => $child
            ]);
        }
        return redirect('/product/families')->with('success', 'Grouping added successfully.');
    }

    public function families()
    {
        $families     =    productGrouping::get();
        $fam         =    array();
        foreach ($families as $family) {
            $fam[$family->parent_id]['children'][]    =    $family->childProduct;
            $fam[$family->parent_id]['code']        =    $family->parentproduct->code;
            $fam[$family->parent_id]['name']        =    $family->parentproduct->name;
            $fam[$family->parent_id]['quantity']    =    $family->parentproduct->quantity;
        }
        $products     =    product::all();
        return view('product/families', ['fam' => $fam, 'products' => $products]);
    }

    public function delete_family(Request $request, $child_id, $parent_id)
    {
        $family = productGrouping::where('child_id', $child_id)->where('parent_id', $parent_id)->first();

        if ($family) {
            if ($family->delete()) {
                return redirect('/product/families')->with('success', 'Family deleted successfully.');
            } else {
                return redirect('/product/families')->with('danger', 'Family was not found.');
            }
        } else {
            return redirect('/product/families')->with('danger', 'Family was not found.');
        }
    }

    public function importGrouping(Request $request)
    {
        $file = $request->file('importCSV');
        Excel::import(new FamilyImport, $file);
        return redirect('/product/families')->with('success', 'Family Excel was uploaded successfully.');
    }

    public function legs()
    {
        $legs     =    legs::get();
        return view('product/legs', ['legs' => $legs]);
    }

    public function importLegsCSV(Request $request)
    {
        $file = $request->file('importCSV');
        Excel::import(new LegImport, $file);
        return redirect('/product/legs')->with('success', 'Legs Excel was uploaded successfully.');
    }

    public function leg_create() {}

    public function updateLegs(Request $request)
    {
        $leg = legs::where('id', $request['id'])->first();
        if (isset($leg->id)) {
            $leg->leg_design     = $request['leg_design'];
            $leg->qty             = $request['qty'];
            $leg->price         = $request['price'];
            $leg->height             = $request['height'];
            $leg->width             = $request['width'];
            $leg->depth             = $request['depth'];
            $leg->save();
        }
        die;
    }

    public function leg_delete(Request $request, $id)
    {
        $leg = legs::where('id', $id)->first();

        if ($leg) {
            if ($leg->delete()) {
                return redirect('/product/legs')->with('success', 'Leg deleted successfully.');
            } else {
                return redirect('/product/legs')->with('danger', 'Leg was not found.');
            }
        } else {
            return redirect('/product/legs')->with('danger', 'Leg was not found.');
        }
    }

    public function exportLegCsv() {}

    public function erp()
    {
        $country = \Request::session()->get('country');
        $csvNames = [
            'us' => 'Fairview',
            'eu' => 'Magdeburg',
            'canada' => 'Canada',
            'california' => 'California'
        ];

        $csvName = $csvNames[$country] ?? 'IP';

        $products = ErpProduct::where("site_access", $country)->get();
        //$last_sheet = ErpSheet::orderBy('created_at','desc')->first();
        $ErpSheetLessW = ErpSheet::where('type', 'less')->orderby('id', 'DESC')->where('erp_sheet_type', 1)->where('site_access', $country)->where('reverted', 0)->first();
        $ErpSheetLessI = ErpSheet::where('type', 'less')->orderby('id', 'DESC')->where('erp_sheet_type', 0)->where('site_access', $country)->where('reverted', 0)->first();
        $ErpSheetAdd = ErpSheet::where('type', 'add')->orderby('id', 'DESC')->where('site_access', $country)->where("name", "like", "%#%")->where('reverted', 0)->first();
        if (!isset($products)) {
            $products = [];
        }
        $container_name = 'NA';


        if (isset($ErpSheetAdd->name)) {
            $name = str_replace('.xlsx', '', $ErpSheetAdd->name);
            $container_name = str_replace('invoiceTable ', '', $name);
        }
        if (!empty($ErpSheetLessW)) {
            $wayfair_name  = str_replace('.csv', '', $ErpSheetLessW->name);
            $wayfair_name  = str_replace('Wayfair-', '', $wayfair_name);
        }
        if (!empty($ErpSheetLessI)) {
            $ip_name  = str_replace('.csv', '', $ErpSheetLessI->name);
            $ip_name  = str_replace('IP-03 CSV-', '', $ip_name);
        }

        if ($country == "uk") {
            $container_name = 'UK18' . $container_name;
        }

        else if ($country == "us") {
            $container_name = 'US18' . $container_name;
        }

        else if ( $country == "california") {
            $container_name = $container_name;
        }


        else if ($country == "eu") {
            $container_name = 'GR18' . $container_name;
        }

        return view('product/erp', ['products' => $products, 'last_sheet_w' => $ErpSheetLessW, 'last_sheet_ip' => $ErpSheetLessI, 'last_container' => $container_name]);
    }

    public function erp_negetive()
    {
        $country = \Request::session()->get('country');
        $csvNames = [
            'us' => 'Fairview',
            'eu' => 'Magdeburg',
            'canada' => 'Canada',
            'california' => 'California'
        ];

        $csvName = $csvNames[$country] ?? 'IP';
        $products = ErpProduct::where('quantity', '<', '0')->where("site_access", $country)->get();
        if (!isset($products)) {
            $products = [];
        }
        $ErpSheetLessW = ErpSheet::where('type', 'less')->orderby('id', 'DESC')->where('erp_sheet_type', 1)->where("site_access", $country)->first();
        $ErpSheetLessI = ErpSheet::where('type', 'less')->orderby('id', 'DESC')->where('erp_sheet_type', 0)->where('site_access', $country)->first();
        $ErpSheetAdd = ErpSheet::where('type', 'add')->orderby('id', 'DESC')->where('site_access', $country)->where("name", "like", "%#%")->first();
        if (!isset($products)) {
            $products = [];
        }

        $container_name = 'NA';


        if (isset($ErpSheetAdd->name)) {
            $name = str_replace('.xlsx', '', $ErpSheetAdd->name);
            $container_name = str_replace('invoiceTable ', '', $name);
        }
        if (!empty($ErpSheetLessW)) {
            $wayfair_name  = str_replace('.csv', '', $ErpSheetLessW->name);
            $wayfair_name  = str_replace('Wayfair-', '', $wayfair_name);
        }
        if (!empty($ErpSheetLessI)) {
            $ip_name  = str_replace('.csv', '', $ErpSheetLessI->name);
            $ip_name  = str_replace('IP-03 CSV-', '', $ip_name);
        }
        return view('product/erp', ['products' => $products, 'last_sheet_w' => $ErpSheetLessW, 'last_sheet_ip' => $ErpSheetLessI, 'last_container' => $container_name]);
    }

    public function erp_diff($type)
    {
        $country = \Request::session()->get('country');
        $csvNames = [
            'us' => 'Fairview',
            'eu' => 'Magdeburg',
            'canada' => 'Canada',
            'california' => 'California'
        ];

        $csvName = $csvNames[$country] ?? 'IP';
        if ($type == 'all') {
            $products = ErpProduct::whereRaw('(erp_products.quantity + erp_products.fullfillment_qty) != erp_products.warehouse_quantity')->whereNotNull('warehouse_quantity')->where("site_access", $country)->get();
        }
        if ($type == 'green') {
            $products = ErpProduct::whereRaw('(erp_products.quantity + erp_products.fullfillment_qty) < erp_products.warehouse_quantity')->whereNotNull('warehouse_quantity')->where("site_access", $country)->get();
        }
        if ($type == 'red') {
            $products = ErpProduct::whereRaw('(erp_products.quantity + erp_products.fullfillment_qty) > erp_products.warehouse_quantity')->whereNotNull('warehouse_quantity')->where("site_access", $country)->get();
        }

        if( $type == 'automation' ) {
            $products = DB::select("
                        SELECT
                            h.sku,
                            h.stock AS history_stock,
                            p.quantity AS product_quantity,
                            (h.stock - p.quantity) AS difference,
                            h.id AS history_id,
                            p.id AS product_id
                        FROM
                        (
                            SELECT eh.*
                            FROM erp_history eh
                            INNER JOIN (
                                SELECT sku, MAX(id) AS max_id
                                FROM erp_history
                                WHERE site_access = ?
                                GROUP BY sku
                            ) latest_h
                            ON eh.id = latest_h.max_id
                        ) h
                        INNER JOIN
                        (
                            SELECT ep.*
                            FROM erp_products ep
                            INNER JOIN (
                                SELECT sku, MAX(id) AS max_id
                                FROM erp_products
                                WHERE site_access = ?
                                GROUP BY sku
                            ) latest_p
                            ON ep.id = latest_p.max_id
                        ) p
                        ON h.sku = p.sku
                        WHERE
                            h.site_access = ?
                            AND p.site_access = ?
                            AND h.stock > p.quantity
                        ORDER BY difference DESC
                    ", [$country, $country, $country, $country]);
        }   
        $ErpSheetLessW = ErpSheet::where('type', 'less')->orderby('id', 'DESC')->where('erp_sheet_type', 1)->where("site_access", $country)->where('reverted', 0)->first();
        $ErpSheetLessI = ErpSheet::where('type', 'less')->orderby('id', 'DESC')->where('erp_sheet_type', 0)->where('site_access', $country)->where('reverted', 0)->first();
        $ErpSheetAdd = ErpSheet::where('type', 'add')->orderby('id', 'DESC')->where('site_access', $country)->where("name", "like", "%#%")->where('reverted', 0)->first();

        $container_name = 'NA';
        if (isset($ErpSheetAdd->name)) {
            $name = str_replace('.xlsx', '', $ErpSheetAdd->name);
            $container_name = str_replace('invoiceTable ', '', $name);
        }
        if (!empty($ErpSheetLessW)) {
            $wayfair_name  = str_replace('.csv', '', $ErpSheetLessW->name);
            $wayfair_name  = str_replace('Wayfair-', '', $wayfair_name);
        }
        if (!empty($ErpSheetLessI)) {
            $ip_name  = str_replace('.csv', '', $ErpSheetLessI->name);
            $ip_name  = str_replace('IP-03 CSV-', '', $ip_name);
        }

        if (strpos($container_name, 'UK18#') !== 0) {
            $container_name = 'UK18' . $container_name;
        }

        if( $type == 'automation' ) {
            return view('product/erp', ['products' => $products, 'last_sheet_w' => $ErpSheetLessW, 'last_sheet_ip' => $ErpSheetLessI, 'last_container' => $container_name, 'erp_diff' => 1, 'type' => $type]);
        }
        
        return view('product/erp', ['products' => $products, 'last_sheet_w' => $ErpSheetLessW, 'last_sheet_ip' => $ErpSheetLessI, 'last_container' => $container_name, 'erp_diff' => 1]);
    }

    public function erp_reason($type)
    {
        if ($type == 'returns') {
            $products = ErpProduct::whereRaw('erp_products.quantity != erp_products.warehouse_quantity')->whereNotNull('warehouse_quantity')->get();
        }
        if ($type == 'green') {
            $products = ErpProduct::whereRaw('erp_products.quantity < erp_products.warehouse_quantity')->whereNotNull('warehouse_quantity')->get();
        }
        if ($type == 'red') {
            $products = ErpProduct::whereRaw('erp_products.quantity > erp_products.warehouse_quantity')->whereNotNull('warehouse_quantity')->get();
        }
        $ErpSheetLessW = ErpSheet::where('type', 'less')->orderby('id', 'DESC')->where('name', 'LIKE', '%Wayfair%')->first();
        $ErpSheetLessI = ErpSheet::where('type', 'less')->orderby('id', 'DESC')->where('name', 'LIKE', '%IP%')->first();
        $ErpSheetAdd = ErpSheet::where('type', 'add')->orderby('id', 'DESC')->first();
        $name = str_replace('.xlsx', '', $ErpSheetAdd->name);
        $container_name = 'NA';
        if (isset($ErpSheetAdd->name)) {
            $container_name = str_replace('invoiceTable ', '', $name);
        }

        $wayfair_name  = str_replace('.csv', '', $ErpSheetLessW->name);
        $wayfair_name  = str_replace('Wayfair-', '', $wayfair_name);

        $ip_name  = str_replace('.csv', '', $ErpSheetLessI->name);
        $ip_name  = str_replace('IP-03 CSV-', '', $ip_name);
        return view('product/erp', ['products' => $products, 'last_sheet_w' => $ErpSheetLessW, 'last_sheet_ip' => $ErpSheetLessI, 'last_container' => $container_name, 'erp_diff' => 1]);
    }

    public function updateErpSku(Request $request)
    {
        $location = \Request::session()->get('country');
        $product = ErpProduct::find($request->product_id);
        if ($request->sku_quant > $product->quantity) {
            $diff_quant = $request->sku_quant - $product->quantity;
            $type = 'add';
        } else {
            $diff_quant = $product->quantity - $request->sku_quant;
            $type = 'less';
        }
        $product->quantity = $request->sku_quant;

        $product->save();



        if ($diff_quant > 0) {
            $history = new ErpHistory;
            $history->sheet_id = 0;
            $history->site_access = $location;
            $history->quantity = $diff_quant;
            $history->sku = $product->sku;
            $history->type = $type;
            $history->reason = $request->reason;
            $history->remark = $request->remarks;
            $history->stock = $product->quantity;
            $history->date = date('Y-m-d');
            $history->save();
        }
        $diff_quant =  $product->warehouse_quantity - $request->sku_quant;
        $data = ['id' => $product->id, 'quantity' => $product->quantity, 'diff_quant' => $diff_quant];
        return response()->json($data);
        die;
    }


    // public function importLessErpCSV(Request $request)
    // {
    //     $location = \Request::session()->get('country');
    //     $file = $request->file('importLessCSV');
    //     $filename = $file->getClientOriginalName();
    //     ErpSheet::create(['name' => $filename, 'date' => date('Y-m-d'), 'type' => 'less', 'site_access' => $location,'erp_sheet_type'=>$request->erp_sheet_type]);
    //     Excel::import(new ErpProductLessImport, $file);
    //     return redirect('/product/erp')->with('success', 'ERP Less Excel was updated successfully.');
    // }


    public function importLessErpCSV(Request $request)
    {
        $location = \Request::session()->get('country');

        // Ensure files are uploaded
        if (!$request->hasFile('importLessCSV')) {
            return redirect()->back()->with('error', 'No files were uploaded.');
        }

        $files = $request->file('importLessCSV');
        $sheetType = $request->input('erp_sheet_type'); // Single value

        foreach ($files as $file) {
            if ($file->isValid()) {
                $filename = $file->getClientOriginalName();

                ErpSheet::create([
                    'name' => $filename,
                    'date' => date('Y-m-d'),
                    'type' => 'less',
                    'site_access' => $location,
                    'erp_sheet_type' => $sheetType, // Apply the same sheet type to all
                ]);

                Excel::import(new ErpProductLessImport, $file);
            }
        }

        return redirect('/product/erp')->with('success', 'ERP Less Excel files were updated successfully.');
    }



    public function importAddErpCSV(Request $request)
    {
        $location = \Request::session()->get('country');
        $file = $request->file('importAddCSV');
        $filename = $file->getClientOriginalName();
        ErpSheet::create(['name' => $filename, 'date' => date('Y-m-d'), 'type' => 'add', 'site_access' => $location]);

        Excel::import(new ErpProductAddImport, $file);
        return redirect('/product/erp')->with('success', 'ERP Add Excel was updated successfully.');
    }

    public function export_erp()
    {
        return (new ErpProductsExport())->download('erpproducts.xlsx');
    }

    public function erp_diff_manager_export()
    {
        return (new ErpDiffProductsExport())->download('erp_discerpancies.xlsx');
    }

    public function view_erp_history($id)
    {
        $location = \Request::session()->get('country');
        $product = ErpProduct::find($id);
        $history = ErpHistory::where(['sku' => $product->sku, 'site_access' => $location])->orderBy('id', 'desc')->get();
        return view('product/erp_history', ['product' => $product, 'history' => $history]);
    }

    public function reason_filter(Request $request)
    {
        $location = \Request::session()->get('country');
        $history = ErpHistory::whereDate('date', '>=', date('Y-m-d', strtotime($request->fsd)))->whereDate('date', '<=', date('Y-m-d', strtotime($request->fed)))->where(['reason' => $request->reason, 'site_access' => $location])->orderBy('id', 'desc')->get();
        return view('product/erp_history_reason', ['history' => $history]);
    }

    public function datewiseerp(Request $request)
    {
        $fsd = $request['fsd'];
        // return  $fsd;
        return (new ErpDateWiseExport($fsd))->download('erp-filtered.xlsx');
    }

    public function erpManager()
    {
        if (!(\Request::session()->has('country'))) {
            \Request::session()->put('country', 'uk');
        }

        $siteAccess = \Request::session()->get('country');
        $csvNames = [
            'us' => 'Fairview',
            'eu' => 'Magdeburg',
            'canada' => 'Canada',
            'california' => 'California'
        ];

        $csvName = $csvNames[$siteAccess] ?? 'IP';
        $products = ErpProduct::where('site_access', $siteAccess)->get();

        $ErpSheetLessW = ErpSheet::where('type', 'less')->orderby('id', 'DESC')->where('erp_sheet_type', 1)->where('site_access', $siteAccess)->first();
        $ErpSheetLessI = ErpSheet::where('type', 'less')->orderby('id', 'DESC')->where('erp_sheet_type', 0)->where('site_access', $siteAccess)->first();
        $ErpSheetAdd = ErpSheet::where('type', 'add')->orderby('id', 'DESC')->where('site_access', $siteAccess)->where("name", "like", "%#%")->first();
        if (!isset($products)) {
            $products = [];
        }
        $container_name = 'NA';
        if (isset($ErpSheetAdd->name)) {
            $name = str_replace('.xlsx', '', $ErpSheetAdd->name);
            $container_name = str_replace('invoiceTable ', '', $name);
        }

        if (!empty($ErpSheetLessW)) {
            $wayfair_name  = str_replace('.csv', '', $ErpSheetLessW->name);
            $wayfair_name  = str_replace('Wayfair-', '', $wayfair_name);
        }
        if (!empty($ErpSheetLessI)) {
            $ip_name  = str_replace('.csv', '', $ErpSheetLessI->name);
            $ip_name  = str_replace('IP-03 CSV-', '', $ip_name);
        }
        return view('product/erp_manager', ['products' => $products, 'last_sheet_w' => $ErpSheetLessW, 'last_sheet_ip' => $ErpSheetLessI, 'last_container' => $container_name]);
    }

    public function erp_negetive_manager()
    {
        $country = \Request::session()->get('country');
        $csvNames = [
            'us' => 'Fairview',
            'eu' => 'Magdeburg',
            'canada' => 'Canada',
            'california' => 'California'
        ];

        $csvName = $csvNames[$country] ?? 'IP';
        $products = ErpProduct::where('warehouse_quantity', '<', '0')->where("site_access", $country)->get();

        $ErpSheet = ErpSheetManager::where('type', 'sheet')->orderby('id', 'DESC')->first();

        $ErpSheetLessW = ErpSheet::where('type', 'less')->orderby('id', 'DESC')->where('erp_sheet_type', 1)->where("site_access", $country)->first();
        $ErpSheetLessI = ErpSheet::where('type', 'less')->orderby('id', 'DESC')->where('erp_sheet_type', 0)->where('site_access', $country)->first();
        $ErpSheetAdd = ErpSheet::where('type', 'add')->orderby('id', 'DESC')->where('site_access', $country)->where("name", "like", "%#%")->first();
        if (!isset($products)) {
            $products = [];
        }

        $container_name = 'NA';


        if (isset($ErpSheetAdd->name)) {
            $name = str_replace('.xlsx', '', $ErpSheetAdd->name);
            $container_name = str_replace('invoiceTable ', '', $name);
        }
        if (!empty($ErpSheetLessW)) {
            $wayfair_name  = str_replace('.csv', '', $ErpSheetLessW->name);
            $wayfair_name  = str_replace('Wayfair-', '', $wayfair_name);
        }
        if (!empty($ErpSheetLessI)) {
            $ip_name  = str_replace('.csv', '', $ErpSheetLessI->name);
            $ip_name  = str_replace('IP-03 CSV-', '', $ip_name);
        }

        return view('product/erp_manager', ['products' => $products, 'last_sheet' => $ErpSheet, 'last_sheet_w' => $ErpSheetLessW, 'last_sheet_ip' => $ErpSheetLessI, 'last_container' => $container_name]);
    }

    public function erp_diff_manager($type)
    {
        $country = \Request::session()->get('country');
        $csvNames = [
            'us' => 'Fairview',
            'eu' => 'Magdeburg',
            'canada' => 'Canada',
            'california' => 'California'
        ];

        $csvName = $csvNames[$country] ?? 'IP';
        if ($type == 'all') {
            $products = ErpProduct::whereRaw('erp_products.quantity != erp_products.warehouse_quantity')->whereNotNull('warehouse_quantity')->where("site_access", $country)->get();
        }
        if ($type == 'green') {
            $products = ErpProduct::whereRaw('erp_products.quantity < erp_products.warehouse_quantity')->whereNotNull('warehouse_quantity')->where("site_access", $country)->get();
        }
        if ($type == 'red') {
            $products = ErpProduct::whereRaw('erp_products.quantity > erp_products.warehouse_quantity')->whereNotNull('warehouse_quantity')->where("site_access", $country)->get();
        }
        $ErpSheet = ErpSheetManager::where('type', 'sheet')->orderby('id', 'DESC')->first();

        $ErpSheetLessW = ErpSheet::where('type', 'less')->orderby('id', 'DESC')->where('erp_sheet_type', 1)->where("site_access", $country)->first();
        $ErpSheetLessI = ErpSheet::where('type', 'less')->orderby('id', 'DESC')->where('erp_sheet_type', 0)->where('site_access', $country)->first();
        $ErpSheetAdd = ErpSheet::where('type', 'add')->orderby('id', 'DESC')->where('site_access', $country)->where("name", "like", "%#%")->first();

        $container_name = 'NA';
        if (isset($ErpSheetAdd->name)) {
            $name = str_replace('.xlsx', '', $ErpSheetAdd->name);
            $container_name = str_replace('invoiceTable ', '', $name);
        }
        if (!empty($ErpSheetLessW)) {
            $wayfair_name  = str_replace('.csv', '', $ErpSheetLessW->name);
            $wayfair_name  = str_replace('Wayfair-', '', $wayfair_name);
        }
        if (!empty($ErpSheetLessI)) {
            $ip_name  = str_replace('.csv', '', $ErpSheetLessI->name);
            $ip_name  = str_replace('IP-03 CSV-', '', $ip_name);
        }

        return view('product/erp_manager', ['products' => $products, 'last_sheet' => $ErpSheet, 'last_sheet_w' => $ErpSheetLessW, 'last_sheet_ip' => $ErpSheetLessI, 'last_container' => $container_name, 'erp_diff' => 1]);
    }

    public function datewiseerpmanager(Request $request)
    {
        $fsd = $request['fsd'];

        return (new ErpDateWiseExportManager($fsd))->download('erp-filtered-uk.xlsx');
    }

    public function updateErpSkuManager(Request $request)
    {
        $location = \Request::session()->get('country');
        $product = ErpProduct::where('site_access', $location)->find($request->product_id);
        if ($request->sku_quant > $product->warehouse_quantity) {
            $diff_quant = $request->sku_quant - $product->warehouse_quantity;
            $type = 'add';
        } else {
            $diff_quant = $product->warehouse_quantity - $request->sku_quant;
            $type = 'less';
        }
        $product->warehouse_quantity = $request->sku_quant;

        $product->save();

        if ($diff_quant > 0) {
            $history = new ErpHistoryManager;
            $history->sheet_id = 0;
            $history->site_access = $location;
            $history->quantity = $diff_quant;
            $history->sku = $product->sku;
            $history->type = $type;
            $history->reason = $request->reason;
            $history->remark = $request->remarks;
            $history->stock = $product->warehouse_quantity;
            $history->date = date('Y-m-d');
            $history->save();
        }
        $diff_quant = $request->sku_quant - $product->quantity;
        $data = ['id' => $product->id, 'quantity' => $product->warehouse_quantity, 'diff_quant' => $diff_quant];
        return response()->json($data);
        die;
    }

    public function importLessErpCSVManager(Request $request)
    {
        $file = $request->file('importLessCSV');
        $filename = $file->getClientOriginalName();
        ErpSheetManager::create(['name' => $filename, 'date' => date('Y-m-d'), 'type' => 'less']);
        Excel::import(new ErpProductLessImportManager, $file);
        return redirect('/erp-manager')->with('success', 'ERP Less Excel was updated successfully.');
    }

    public function importAddErpCSVManager(Request $request)
    {

        $file = $request->file('importAddCSV');
        $filename = $file->getClientOriginalName();
        ErpSheetManager::create(['name' => $filename, 'date' => date('Y-m-d'), 'type' => 'sheet']);
        Excel::import(new ErpProductAddImportManager, $file);
        return redirect('/erp-manager')->with('success', 'ERP Add Excel was updated successfully.');
    }

    public function importAdd2ErpCSVManager(Request $request)
    {
        $file = $request->file('importAddCSV');
        $filename = $file->getClientOriginalName();
        ErpSheetManager::create(['name' => $filename, 'date' => date('Y-m-d'), 'type' => 'sheet']);
        Excel::import(new ErpProductAdd2ImportManager, $file);
        return redirect('/erp-manager')->with('success', 'ERP Add Excel was updated successfully.');
    }


    public function view_erp_history_manager($id)
    {
        $location = \Request::session()->get('country');
        $history = [];
        $product = ErpProduct::find($id);
        if (!empty($product)) {
            $history = ErpHistoryManager::where(['sku' => $product->sku, 'site_access' => $location])->orderBy('id', 'desc')->get();
        }
        return view('product/erp_history_manager', ['product' => $product, 'history' => $history]);
    }

    public function importErpCSV(Request $request)
    {
        $file = $request->file('importErpCSV');
        $filename = $file->getClientOriginalName();
        ErpSheet::create(['name' => $filename, 'date' => date('Y-m-d'), 'type' => 'stock']);
        Excel::import(new ErpProductImport, $file);
        return redirect('/product/erp')->with('success', 'ERP Excel was updated successfully.');
    }

    public function importErpCSVManager(Request $request)
    {
        $file = $request->file('importErpCSVManager');
        $filename = $file->getClientOriginalName();
        ErpSheetManager::create(['name' => $filename, 'date' => date('Y-m-d'), 'type' => 'add']);
        Excel::import(new ErpProductImportManager, $file);
        return redirect('/erp-manager')->with('success', 'ERP Excel was updated successfully.');
    }

    public function create_history()
    {
        $location = \Request::session()->get('country');
        $products = ErpProduct::where('site_access', $location)->get();
        foreach ($products as $product) {
            $history = ErpHistory::where(['sku' => $product->sku, 'site_access' => $location])->first();
            if (!isset($history->id)) {
                $history_new = new ErpHistory;
                $history_new->sheet_id = 0;
                $history_new->quantity = $product->quantity;
                $history_new->stock = $product->quantity;
                $history_new->sku = $product->sku;
                $history_new->type = 'add';
                $history_new->date = '2022-12-01';
                $history_new->created_at = '2022-12-01 10:00:00';
                $history_new->save();
            }
        }
        echo 1;
        die;
    }

    public function create_history_manager()
    {
        $location = \Request::session()->get('country');
        $products = ErpProduct::where('site_access', $location)->get();
        foreach ($products as $product) {
            $history = ErpHistoryManager::where(['sku' => $product->sku, 'site_access' => $location])->first();
            if (!isset($history->id)) {
                $history_new = new ErpHistoryManager;
                $history_new->sheet_id = 0;
                if ($product->warehouse_quantity == null) {
                    $history_new->quantity = 0;
                    $history_new->stock = 0;
                } else {
                    $history_new->quantity = $product->warehouse_quantity;
                    $history_new->stock = $product->warehouse_quantity;
                }
                $history_new->site_access = $location;
                $history_new->sku = $product->sku;
                $history_new->type = 'add';
                $history_new->date = '2023-01-10';
                $history_new->created_at = '2023-01-10 10:00:00';
                $history_new->save();
            }
        }
        echo 1;
        die;
    }

    public function import_array()
    {
        $location = \Request::session()->get('country');
        $array = [
            'IN023' => 33,
            'IN025' =>   5,
            'IN049' =>   2,
            'IN043' => 18,
            'IN051' =>   4,
            'IN065' =>   1,
            'IN075' =>   2,
            'IN085' => 17,
            'IN086' =>   2,
            'IN105' => 12,
            'IN106' =>   6,
            'IN109' => 13,
            'IN111' =>   3,
            'IN117' =>   4,
            'IN122' =>   3,
            'IN140' =>   4,
            'ASB320' =>  2,
            'IN187' =>   1,
            'IN251' =>   2,
            'IN256' =>   4,
            'IN263' => 24,
            'IN275' =>   7,
            'IN281' =>   6,
            'IN294' =>   7,
            'IN296' =>   1,
            'IN301' =>   4,
            'IN302' =>   4,
            'IN304' =>   2,
            'IN349' =>   1,
            'IN376' =>   5,
            'IN427' =>   1,
            'IN428' =>   2,
            'IN457' =>   2,
            'IN494' =>   1,
            'IN497' =>   1,
            'ASB287' =>   2,
            'ASB311' =>   1,
            'ASB441' =>   2,
            'IN659' =>   5,
            'IN660' =>   7,
            'IN700' =>   1,
            'IN719' =>   8,
            'IN720' => 15,
            'IN745' =>   4,
            'IN747' =>   2,
            'IN751' =>   1,
            'IN757' =>   3,
            'IN764' =>   2,
            'IN767' =>   5,
            'IN783' =>   1,
            'IN789' =>   3,
            'IN816' =>   1,
            'IN818' =>   1,
            'IN820' =>   4,
            'IN821' =>   1,
            'IN823' =>   1,
            'IN831' =>   2,
            'IN846' =>   4,
            'IN877' =>   1,
            'IN878' =>   1,
            'IN924' =>   1,
            'IN935' =>   2,
            'IN974' =>   6,
            'IN977' =>   3,
            'IN982' =>   6,
            'IN1064' =>  1,
            'IN1232' =>  1,
            'IN1272' =>  4,
            'IN1273' =>  3,
            'IN1283' =>  1,
            'IN1297' =>  3,
            'IN1340' =>  5,
            'IN1373' =>  1,
            'IN1414' =>  1,
            'IN1418' =>  1,
            'IN1423' =>  1,
            'IN1424' =>  1,
            'IN1431' =>  1,
            'IN1442' =>  1,
            'IN1448' =>  2,
            'IN1449' =>  1,
            'IN1463' =>  2,
            'IN1471' =>  2,
            'IN1472' =>  1,
            'IN1475' =>  1,
            'IN1524' =>  1,
            'IN1548' =>  3,
            'IN1581' =>  2,
            'IN1588' =>  4,
            'IN1625' =>  1,
            'IN1628' =>  1,
            'IN1668' =>  1,
            'IN1679' =>  1,
            'IN1681' =>  1,
            'IN1691' =>  2,
            'IN1703' =>  1,
            'IN1704' =>  1,
            'IN1714' =>  2,
            'IN1728' =>  2,
            'IN1740' =>  1,
            'IN1750' =>  1,
            'IN1771' =>  2,
            'IN1772' =>  1,
            'IN1773' =>  2,
            'IN1779' =>  1,
            'IN1783' =>  2,
            'IN1784' =>  2,
            'IN1786' =>  2,
            'IN1791' =>  1,
            'IN1792' =>  2,
            'IN1798' =>  1,
            'IN1799' =>  1,
            'IN1800' =>  2,
            'IN1801' =>  1,
            'IN1802' =>  2,
            'IN1805' =>  2,
            'IN1807' =>  1,
            'IN1809' =>  1,
            'IN1810' =>  1,
            'IN1814' =>  2,
            'IN1820' =>  2,
            'IN1821' =>  1,
            'IN1824' =>  1,
            'IN1827' =>  1,
            'IN1831' =>  1,
            'IN1835' =>  2,
            'IN1837' =>  1,
            'IN1838' =>  1,
            'IN1839' =>  1,
            'IN1840' =>  1,
            'IN1843' =>  1,
            'IN1845' =>  1,
            'IN1846' =>  1,
            'IN1851' =>  1,
            'IN1852' =>  1,
            'IN1860' =>  2,
            'IN1864' =>  3,
            'IN1866' =>  2,
            'IN1867' =>  2,
            'IN1871' =>  2,
            'IN1874' =>  1,
            'IN1877' =>  1,
            'IN1878' =>  1,
            'IN1879' =>  3,
            'IN1880' =>  1,
            'IN1881' =>  1,
            'IN1882' =>  1,
            'IN1883' =>  1,
            'IN1884' =>  1,
            'IN1885' =>  2,
            'IN1886' =>  2,
            'IN1887' =>  2,
            'IN1888' =>  2,
            'IN1890' =>  1,
            'IN1891' =>  2,
            'IN1892' =>  1,
            'IN1893' =>  1,
            'IN1894' =>  2,
            'IN1895' =>  1,
            'IN1896' =>  1,
            'IN1897' =>  1,
            'IN1898' =>  1,
            'IN1899' =>  1,
            'IN1901' =>  1,
            'IN1902' =>  1,
            'IN1903' =>  1,
            'IN1904' =>  1,
            'IN1905' =>  1,
            'IN1916' =>  1,
            'IN1917' =>  1,
            'IN1918' =>  1,
            'IN1919' =>  1,
            'IN1920' =>  1,
            'IN1921' =>  1,
            'IN1922' =>  1,
            'IN1923' =>  1,
            'IN1924' =>  1,
            'IN1925' =>  2,
            'IN1931' =>  2,
            'IN1934' =>  2,
            'IN1939' =>  2,
            'IN1940' =>  2,
            'IN1941' =>  2,
            'IN1942' =>  1,
            'IN1943' =>  2,
            'IN1944' =>  2,
            'IN1945' =>  1,
            'IN1946' =>  1,
            'IN1948' =>  1,
            'IN1952' =>  2,
            'IN1954' =>  2,
            'IN1955' =>  1,
            'IN1960' =>  1,
            'IN1961' =>  1,
            'IN1964' =>  1,
            'IN1965' =>  1,
            'IN1966' =>  1,
            'IN1967' =>  1,
            'IN1968' =>  1,
            'IN1970' =>  2,
            'IN1973' =>  2,
            'IN1974' =>  1,
            'IN1975' =>  2,
            'IN1976' =>  1,
            'IN1977' =>  1,
            'IN1978' =>  1,
            'IN1979' =>  1,
            'IN1980' =>  1,
            'IN1981' =>  2,
            'IN1982' =>  2,
            'IN1983' =>  2,
            'IN1984' =>  2,
            'IN1985' =>  2,
            'IN1986' =>  2,
            'IN1987' =>  1,
            'IN1988' =>  1,
            'IN1989' =>  1,
            'IN1990' =>  2,
            'IN1991' =>  2,
            'IN1992' =>  2,
            'IN1993' =>  1,
            'IN1994' =>  1,
            'IN1995' =>  1,
            'IN1996' =>  1,
            'IN1997' =>  1,
            'IN1998' =>  1,
            'IN1999' =>  1,
            'IN2117' =>  2,
            'IN3000' =>  1,
            'IN3001' =>  1,
            'IN3002' =>  1,
            'IN3003' =>  1,
            'IN3004' =>  1,
            'IN3005' =>  1,
            'IN3008' =>  1,
            'IN3009' =>  1,
            'IN3010' =>  1,
            'IN3011' =>  1,
            'IN3012' =>  1,
            'IN3013' =>  1,
            'IN3014' =>  1,
            'IN3015' =>  1,
            'IN3016' =>  1,
            'IN3018' =>  1,
            'IN3019' =>  1,
            'IN3020' =>  1,
            'IN3021' =>  1,
            'IN3022' =>  1,
            'IN3023' =>  1,
            'IN3024' =>  1,
            'IN3025' =>  1,
            'IN3026' =>  1,
            'IN3027' =>  1,
            'IN3028' =>  1,
            'IN3029' =>  1,
            'IN3030' =>  1,
            'IN3031' =>  1,
            'IN3032' =>  1,
            'IN3033' =>  1,
            'IN3034' =>  1,
            'IN3035' =>  1,
            'IN3036' =>  1,
            'IN3037' =>  1,
            'IN3038' =>  1,
            'IN3039' =>  2,
            'IN3040' =>  2,
            'IN3042' =>  1,
            'IN3043' =>  1,
            'IN3045' =>  1,
            'IN3046' =>  1,
            'IN3047' =>  1,
            'IN3048' =>  1,
            'IN3049' =>  1,
            'IN3050' =>  1,
            'IN3051' =>  1,
            'IN3052' =>  1,
            'IN3053' =>  2,
            'IN3054' =>  2,
            'IN3055' =>  2,
            'IN3056' =>  2,
            'IN3057' =>  2,
            'IN3058' =>  2,
            'IN3059' =>  2,
            'IN3060' =>  2,
            'IN3061' =>  2,
            'IN3062' =>  2,
            'IN3063' =>  2,
            'IN3064' =>  2,
            'IN3065' =>  2,
            'IN3066' =>  2,
            'IN3067' =>  1,
            'IN3068' =>  2,
            'IN3069' =>  1,
            'IN3070' =>  1,
            'IN3071' =>  1,
            'IN3072' =>  1,
            'IN3073' =>  1,
            'IN3074' =>  1,
            'IN3084' =>  3,
            'IN3085' => 13,
            'IN3087' =>  1,
            'IN3089' =>  1,
            'IN3090' =>  1,
            'IN3107' =>  5,
            'IN3112' =>  1,
            'IN3113' =>  1,
            'IN3114' =>  1,
            'IN3116' =>  1,
            'IN3117' =>  1,
            'IN3118' =>  1,
            'IN3119' =>  1,
            'IN3120' =>  1,
            'IN3121' =>  1,
            'IN3123' =>  1,
            'IN3124' =>  1,
            'IN3125' =>  1,
            'IN3126' =>  1,
            'IN3127' =>  1,
            'IN3128' =>  1,
            'IN3130' =>  1,
            'IN3131' =>  1,
            'IN3132' =>  1,
            'IN3133' =>  5,
            'IN3134' =>  5,
            'IN3135' =>  2,
            'IN3136' =>  4,
            'IN3137' =>  1,
            'IN3138' =>  1,
            'IN3141' =>  2,
            'IN3142' =>  1,
            'IN3143' =>  1,
            'IN3146' =>  1,
            'IN3147' =>  1,
            'IN3148' =>  1,
            'IN3149' =>  1,
            'IN3150' =>  1,
            'IN3151' =>  1,
            'IN3153' =>  1,
            'IN3154' =>  1,
            'IN3156' =>  1,
            'IN3157' =>  1,
            'IN3158' =>  1,
            'IN3159' =>  1,
            'IN3160' =>  1,
            'IN3162' =>  2,
            'IN3163' =>  2,
            'IN3165' =>  2,
            'IN3166' =>  2,
            'IN3167' =>  2,
            'IN3168' =>  1,
            'IN3169' =>  1,
            'IN3170' =>  1,
            'IN3171' =>  1,
            'IN3172' =>  1,
            'IN3180' =>  1,
            'IN3181' =>  1,
            'IN3182' =>  1,
            'IN3183' =>  1,
            'IN3184' =>  1,
            'IN3185' =>  1,
            'IN3186' =>  1,
            'IN3187' =>  1,
            'IN3188' =>  1,
            'IN3190' =>  1,
            'IN3192' =>  1,
            'IN3197' =>  1,
            'IN3198' =>  1,
            'IN3199' =>  1,
            'IN3200' =>  1,
            'IN3201' =>  1,
            'IN3202' =>  1,
            'IN3203' =>  1,
            'IN3204' =>  1,
            'IN3205' =>  1,
            'IN3206' =>  1,
            'IN3208' =>  1,
            'IN3210' =>  1,
            'IN3211' =>  1,
            'IN3212' =>  1,
            'IN3197' =>  1,
            'IN3198' =>  1,
            'IN3199' =>  1,
            'IN3200' =>  1,
            'IN3201' =>  1,
            'IN3202' =>  1,
            'IN3203' =>  1,
            'IN3204' =>  1,
            'IN3205' =>  1,
            'IN3206' =>  1,
            'IN3208' =>  1,
            'IN3210' =>  1,
            'IN3211' =>  1,
            'IN3212' =>  1,
            'IN3213' =>  1,
            'IN3227' =>  5,
            'IN3229' =>  1,
            'IN3231' =>  1,
            'IN3232' =>  1,
            'IN3233' =>  1,
            'IN3234' =>  1,
            'IN3235' =>  1,
            'IN3236' =>  1,
            'IN3237' =>  1,
            'IN3240' =>  1,
            'IN3241' =>  1,
            'IN3242' =>  1,
            'IN3249' =>  1,
            'IN3250' =>  1,
            'IN3252' =>  1,
            'IN3254' =>  2,
            'IN3258' =>  1,
            'IN3259' =>  1,
            'IN3261' =>  1,
            'IN3269' =>  2,
            'IN3230' =>  2,
            'IN3217' =>  2,
            'IN3218' =>  2,
            'IN3219' =>  2,
            'IN1689' =>  1
        ];
        $location = \Request::session()->get('country');
        foreach ($array as $sku => $value) {
            $sku = trim($sku);
            $p = ErpProduct::where(['sku' => $sku, 'site_access' => $location])->first();

            if (isset($p->id)) {
                $history = ErpHistory::whereDate('date', '<', '2023-01-05')->where(['sku' => $sku, 'site_access' => $location])->orderBy('created_at', 'desc')->first();
                if (isset($history->id)) {
                    $history_new = new ErpHistory;
                    $quant = $history->stock;
                    $history_new->site_access = $location;
                    if ($quant < $value) {
                        $history_new->type = 'add';
                        $history_new->quantity = $value - $quant;
                    } else {
                        $history_new->type = 'less';
                        $history_new->quantity =  $quant - $value;
                    }

                    $history_new->sheet_id = 0;

                    $history_new->stock = $value;
                    $history_new->sku = $sku;

                    $history_new->date = '2023-01-05';
                    $history_new->created_at = '2023-01-05 10:00:00';
                    $history_new->save();
                } else {
                    $history_new = new ErpHistory;
                    $history_new->site_access = $location;
                    $history_new->sheet_id = 0;
                    $history_new->quantity = $value;
                    $history_new->stock = $value;
                    $history_new->sku = $sku;
                    $history_new->type = 'add';
                    $history_new->date = '2023-01-05';
                    $history_new->created_at = '2023-01-05 10:00:00';
                    $history_new->save();
                }
            } else {
                $pn = new ErpProduct;
                $pn->site_access = $location;
                $pn->sku = $sku;
                $pn->quantity = $value;
                $pn->zone_name = 'MEZZ';
                $pn->zone_serial = 'artisan-4000 3009900003500';
                $pn->save();
                $history_new = new ErpHistory;
                $history_new->site_access = $location;
                $history_new->sheet_id = 0;
                $history_new->quantity = $value;
                $history_new->stock = $value;
                $history_new->sku = $sku;
                $history_new->type = 'add';
                $history_new->date = '2023-01-05';
                $history_new->created_at = '2023-01-05 10:00:00';
                $history_new->save();
            }
        }
        echo 1;
        die;
    }



    public function erp_us_negetive()
    {
        $products = ErpUsProduct::where('quantity', '<', '0')->get();
        $ErpSheetLessW = ErpUsSheet::where('type', 'less')->orderby('id', 'DESC')->where('name', 'LIKE', '%Wayfair%')->first();
        $ErpSheetLessI = ErpUsSheet::where('type', 'less')->orderby('id', 'DESC')->where('name', 'LIKE', '%IP%')->first();
        $ErpSheetAdd = ErpUsSheet::where('type', 'add')->orderby('id', 'DESC')->first();

        $container_name = 'NA';
        if (isset($ErpSheetAdd->name)) {
            $name = str_replace('.xlsx', '', $ErpSheetAdd->name);
            $container_name = str_replace('invoiceTable ', '', $name);
        }
        if (isset($ErpSheetLessW->name)) {
            $wayfair_name  = str_replace('.csv', '', $ErpSheetLessW->name);
            $wayfair_name  = str_replace('Wayfair-', '', $wayfair_name);
        }
        if (isset($ErpSheetLessI->name)) {
            $ip_name  = str_replace('.csv', '', $ErpSheetLessI->name);
            $ip_name  = str_replace('IP-03 CSV-', '', $ip_name);
        }
        return view('product/erp_us', ['products' => $products, 'last_sheet_w' => $ErpSheetLessW, 'last_sheet_ip' => $ErpSheetLessI, 'last_container' => $container_name]);
    }

    public function erp_us_diff($type)
    {
        if ($type == 'all') {
            $products = ErpUsProduct::whereRaw('erp_us_products.quantity != erp_us_products.us_quantity')->whereNotNull('us_quantity')->get();
        }
        if ($type == 'green') {
            $products = ErpUsProduct::whereRaw('erp_us_products.quantity < erp_us_products.us_quantity')->whereNotNull('us_quantity')->get();
        }
        if ($type == 'red') {
            $products = ErpUsProduct::whereRaw('erp_us_products.quantity > erp_us_products.us_quantity')->whereNotNull('us_quantity')->get();
        }
        $ErpSheetLessW = ErpUsSheet::where('type', 'less')->orderby('id', 'DESC')->where('name', 'LIKE', '%Wayfair%')->first();
        $ErpSheetLessI = ErpUsSheet::where('type', 'less')->orderby('id', 'DESC')->where('name', 'LIKE', '%IP%')->first();
        $ErpSheetAdd = ErpUsSheet::where('type', 'add')->orderby('id', 'DESC')->first();

        $container_name = 'NA';
        if (isset($ErpSheetAdd->name)) {
            $name = str_replace('.xlsx', '', $ErpSheetAdd->name);
            $container_name = str_replace('invoiceTable ', '', $name);
        }

        if (isset($ErpSheetLessW->name)) {
            $wayfair_name  = str_replace('.csv', '', $ErpSheetLessW->name);
            $wayfair_name  = str_replace('Wayfair-', '', $wayfair_name);
        }
        if (isset($ErpSheetLessI->name)) {
            $ip_name  = str_replace('.csv', '', $ErpSheetLessI->name);
            $ip_name  = str_replace('IP-03 CSV-', '', $ip_name);
        }
        return view('product/erp_us', ['products' => $products, 'last_sheet_w' => $ErpSheetLessW, 'last_sheet_ip' => $ErpSheetLessI, 'last_container' => $container_name, 'erp_diff' => 1]);
    }

    public function erp_us_reason($type)
    {
        if ($type == 'returns') {
            $products = ErpUsProduct::whereRaw('erp_us_products.quantity != erp_us_products.us_quantity')->whereNotNull('us_quantity')->get();
        }
        if ($type == 'green') {
            $products = ErpUsProduct::whereRaw('erp_us_products.quantity < erp_us_products.us_quantity')->whereNotNull('us_quantity')->get();
        }
        if ($type == 'red') {
            $products = ErpUsProduct::whereRaw('erp_us_products.quantity > erp_us_products.us_quantity')->whereNotNull('us_quantity')->get();
        }
        $ErpSheetLessW = ErpUsSheet::where('type', 'less')->orderby('id', 'DESC')->where('name', 'LIKE', '%Wayfair%')->first();
        $ErpSheetLessI = ErpUsSheet::where('type', 'less')->orderby('id', 'DESC')->where('name', 'LIKE', '%IP%')->first();
        $ErpSheetAdd = ErpUsSheet::where('type', 'add')->orderby('id', 'DESC')->first();
        $name = str_replace('.xlsx', '', $ErpSheetAdd->name);
        $container_name = 'NA';
        if (isset($ErpSheetAdd->name)) {
            $container_name = str_replace('invoiceTable ', '', $name);
        }

        $wayfair_name  = str_replace('.csv', '', $ErpSheetLessW->name);
        $wayfair_name  = str_replace('Wayfair-', '', $wayfair_name);

        $ip_name  = str_replace('.csv', '', $ErpSheetLessI->name);
        $ip_name  = str_replace('IP-03 CSV-', '', $ip_name);
        return view('product/erp_us', ['products' => $products, 'last_sheet_w' => $ErpSheetLessW, 'last_sheet_ip' => $ErpSheetLessI, 'last_container' => $container_name, 'erp_diff' => 1]);
    }

    public function updateErpUsSku(Request $request)
    {
        $product = ErpUsProduct::find($request->product_id);
        if ($request->sku_quant > $product->quantity) {
            $diff_quant = $request->sku_quant - $product->quantity;
            $type = 'add';
        } else {
            $diff_quant = $product->quantity - $request->sku_quant;
            $type = 'less';
        }
        $product->quantity = $request->sku_quant;

        $product->save();



        if ($diff_quant > 0) {
            $history = new ErpUsHistory;
            $history->sheet_id = 0;
            $history->quantity = $diff_quant;
            $history->sku = $product->sku;
            $history->type = $type;
            $history->reason = $request->reason;
            $history->remark = $request->remarks;
            $history->stock = $product->quantity;
            $history->date = date('Y-m-d');
            $history->save();
        }
        $diff_quant =  $product->us_quantity - $request->sku_quant;
        $data = ['id' => $product->id, 'quantity' => $product->quantity, 'diff_quant' => $diff_quant];
        return response()->json($data);
        die;
    }

    public function importLessErpUsCSV(Request $request)
    {
        $file = $request->file('importLessCSV');
        $filename = $file->getClientOriginalName();
        ErpUsSheet::create(['name' => $filename, 'date' => date('Y-m-d'), 'type' => 'less']);
        Excel::import(new ErpUsProductLessImport, $file);
        return redirect('/product/erp-us')->with('success', 'ERP Less Excel was updated successfully.');
    }

    public function importAddErpUsCSV(Request $request)
    {
        $file = $request->file('importAddCSV');
        $filename = $file->getClientOriginalName();
        ErpUsSheet::create(['name' => $filename, 'date' => date('Y-m-d'), 'type' => 'add']);
        Excel::import(new ErpUsProductAddImport, $file);
        return redirect('/product/erp-us')->with('success', 'ERP Add Excel was updated successfully.');
    }

    public function export_erp_us()
    {
        return (new ErpUsProductsExport())->download('erpproducts.xlsx');
    }

    public function erp_us_diff_manager_export()
    {
        return (new ErpUsDiffProductsExport())->download('erp_discerpancies.xlsx');
    }

    public function view_erp_us_history($id)
    {
        $product = ErpUsProduct::find($id);
        $history = ErpUsHistory::where('sku', $product->sku)->orderBy('id', 'desc')->get();
        return view('product/erp_us_history', ['product' => $product, 'history' => $history]);
    }

    public function reason_filter_us(Request $request)
    {
        $history = ErpUsHistory::whereDate('date', '>=', date('Y-m-d', strtotime($request->fsd)))->whereDate('date', '<=', date('Y-m-d', strtotime($request->fed)))->where('reason', $request->reason)->orderBy('id', 'desc')->get();
        return view('product/erp_history_reason', ['history' => $history]);
    }

    public function datewiseerpus(Request $request)
    {
        $fsd = $request['fsd'];

        return (new ErpUsDateWiseExport($fsd))->download('erp-filtered.xlsx');
    }

    public function erpUsManager()
    {
        $products = ErpUsProduct::get();

        $ErpSheetLessW = ErpUsSheet::where('type', 'less')->orderby('id', 'DESC')->where('name', 'LIKE', '%Wayfair%')->first();
        $ErpSheetLessI = ErpUsSheet::where('type', 'less')->orderby('id', 'DESC')->where('name', 'LIKE', '%IP%')->first();
        $ErpSheetAdd = ErpUsSheet::where('type', 'add')->orderby('id', 'DESC')->first();
        $name = str_replace('.xlsx', '', $ErpSheetAdd->name);
        $container_name = 'NA';
        if (isset($ErpSheetAdd->name)) {
            $container_name = str_replace('invoiceTable ', '', $name);
        }

        $wayfair_name  = str_replace('.csv', '', $ErpSheetLessW->name);
        $wayfair_name  = str_replace('Wayfair-', '', $wayfair_name);

        $ip_name  = str_replace('.csv', '', $ErpSheetLessI->name);
        $ip_name  = str_replace('IP-03 CSV-', '', $ip_name);
        return view('product/erp_us_manager', ['products' => $products, 'last_sheet_w' => $ErpSheetLessW, 'last_sheet_ip' => $ErpSheetLessI, 'last_container' => $container_name]);
    }

    public function erp_us_negetive_manager()
    {
        $products = ErpUsProduct::where('us_quantity', '<', '0')->get();

        $ErpSheet = ErpUsSheetManager::where('type', 'sheet')->orderby('id', 'DESC')->first();

        return view('product/erp_us_manager', ['products' => $products, 'last_sheet' => $ErpSheet]);
    }

    public function erp_us_diff_manager($type)
    {
        if ($type == 'all') {
            $products = ErpUsProduct::whereRaw('erp_us_products.quantity != erp_us_products.us_quantity')->whereNotNull('us_quantity')->get();
        }
        if ($type == 'green') {
            $products = ErpUsProduct::whereRaw('erp_us_products.quantity < erp_us_products.us_quantity')->whereNotNull('us_quantity')->get();
        }
        if ($type == 'red') {
            $products = ErpUsProduct::whereRaw('erp_us_products.quantity > erp_us_products.us_quantity')->whereNotNull('us_quantity')->get();
        }
        $ErpSheet = ErpUsSheetManager::where('type', 'sheet')->orderby('id', 'DESC')->first();

        return view('product/erp_us_manager', ['products' => $products, 'last_sheet' => $ErpSheet, 'erp_diff' => 1]);
    }

    public function datewiseerpusmanager(Request $request)
    {
        $fsd = $request['fsd'];

        return (new ErpUsDateWiseExportManager($fsd))->download('erp-filtered-us.xlsx');
    }

    public function updateErpUsSkuManager(Request $request)
    {
        $product = ErpUsProduct::find($request->product_id);
        if ($request->sku_quant > $product->us_quantity) {
            $diff_quant = $request->sku_quant - $product->us_quantity;
            $type = 'add';
        } else {
            $diff_quant = $product->us_quantity - $request->sku_quant;
            $type = 'less';
        }
        $product->us_quantity = $request->sku_quant;

        $product->save();

        if ($diff_quant > 0) {
            $history = new ErpUsHistoryManager;
            $history->sheet_id = 0;
            $history->quantity = $diff_quant;
            $history->sku = $product->sku;
            $history->type = $type;
            $history->reason = $request->reason;
            $history->remark = $request->remarks;
            $history->stock = $product->us_quantity;
            $history->date = date('Y-m-d');
            $history->save();
        }
        $diff_quant = $request->sku_quant - $product->quantity;
        $data = ['id' => $product->id, 'quantity' => $product->us_quantity, 'diff_quant' => $diff_quant];
        return response()->json($data);
        die;
    }

    public function importLessErpUsCSVManager(Request $request)
    {
        $file = $request->file('importLessCSV');
        $filename = $file->getClientOriginalName();
        ErpUsSheetManager::create(['name' => $filename, 'date' => date('Y-m-d'), 'type' => 'less']);
        Excel::import(new ErpUsProductLessImportManager, $file);
        return redirect('/product/erp-us-manager')->with('success', 'ERP Less Excel was updated successfully.');
    }

    public function importAddErpUsCSVManager(Request $request)
    {
        $file = $request->file('importAddCSV');
        $filename = $file->getClientOriginalName();
        ErpUsSheetManager::create(['name' => $filename, 'date' => date('Y-m-d'), 'type' => 'sheet']);
        Excel::import(new ErpUsProductAddImportManager, $file);
        return redirect('/erp-us-manager')->with('success', 'ERP Add Excel was updated successfully.');
    }

    public function importAdd2ErpUsCSVManager(Request $request)
    {
        $file = $request->file('importAddCSV');
        $filename = $file->getClientOriginalName();
        ErpUsSheetManager::create(['name' => $filename, 'date' => date('Y-m-d'), 'type' => 'sheet']);
        Excel::import(new ErpUsProductAdd2ImportManager, $file);
        return redirect('/erp-us-manager')->with('success', 'ERP Add Excel was updated successfully.');
    }


    public function view_erp_us_history_manager($id)
    {
        $product = ErpUsProduct::find($id);
        $history = ErpUsHistoryManager::where('sku', $product->sku)->orderBy('id', 'desc')->get();
        return view('product/erp_us_history_manager', ['product' => $product, 'history' => $history]);
    }

    public function importErpUsCSV(Request $request)
    {
        $file = $request->file('importErpCSV');
        $filename = $file->getClientOriginalName();
        ErpUsSheet::create(['name' => $filename, 'date' => date('Y-m-d'), 'type' => 'stock']);
        Excel::import(new ErpUsProductImport, $file);
        return redirect('/product/erp-us')->with('success', 'ERP Excel was updated successfully.');
    }

    public function importErpUsCSVManager(Request $request)
    {
        $file = $request->file('importErpCSVManager');
        $filename = $file->getClientOriginalName();
        ErpUsSheetManager::create(['name' => $filename, 'date' => date('Y-m-d'), 'type' => 'add']);
        Excel::import(new ErpUsProductImportManager, $file);
        return redirect('/erp-us-manager')->with('success', 'ERP Excel was updated successfully.');
    }

    //EU



    public function erp_eu_negetive()
    {
        $products = ErpEuProduct::where('quantity', '<', '0')->get();
        $ErpSheetLessW = ErpEuSheet::where('type', 'less')->orderby('id', 'DESC')->where('name', 'LIKE', '%Wayfair%')->first();
        $ErpSheetLessI = ErpEuSheet::where('type', 'less')->orderby('id', 'DESC')->where('name', 'LIKE', '%IP%')->first();
        $ErpSheetAdd = ErpEuSheet::where('type', 'add')->orderby('id', 'DESC')->first();

        $container_name = 'NA';
        if (isset($ErpSheetAdd->name)) {
            $name = str_replace('.xlsx', '', $ErpSheetAdd->name);
            $container_name = str_replace('invoiceTable ', '', $name);
        }
        if (isset($ErpSheetLessW->name)) {
            $wayfair_name  = str_replace('.csv', '', $ErpSheetLessW->name);
            $wayfair_name  = str_replace('Wayfair-', '', $wayfair_name);
        }
        if (isset($ErpSheetLessI->name)) {
            $ip_name  = str_replace('.csv', '', $ErpSheetLessI->name);
            $ip_name  = str_replace('IP-03 CSV-', '', $ip_name);
        }
        return view('product/erp_eu', ['products' => $products, 'last_sheet_w' => $ErpSheetLessW, 'last_sheet_ip' => $ErpSheetLessI, 'last_container' => $container_name]);
    }

    public function erp_eu_diff($type)
    {
        if ($type == 'all') {
            $products = ErpEuProduct::whereRaw('erp_eu_products.quantity != erp_eu_products.eu_quantity')->whereNotNull('eu_quantity')->get();
        }
        if ($type == 'green') {
            $products = ErpEuProduct::whereRaw('erp_eu_products.quantity < erp_eu_products.eu_quantity')->whereNotNull('eu_quantity')->get();
        }
        if ($type == 'red') {
            $products = ErpEuProduct::whereRaw('erp_eu_products.quantity > erp_eu_products.eu_quantity')->whereNotNull('eu_quantity')->get();
        }
        $ErpSheetLessW = ErpEuSheet::where('type', 'less')->orderby('id', 'DESC')->where('name', 'LIKE', '%Wayfair%')->first();
        $ErpSheetLessI = ErpEuSheet::where('type', 'less')->orderby('id', 'DESC')->where('name', 'LIKE', '%IP%')->first();
        $ErpSheetAdd = ErpEuSheet::where('type', 'add')->orderby('id', 'DESC')->first();

        $container_name = 'NA';
        if (isset($ErpSheetAdd->name)) {
            $name = str_replace('.xlsx', '', $ErpSheetAdd->name);
            $container_name = str_replace('invoiceTable ', '', $name);
        }

        if (isset($ErpSheetLessW->name)) {
            $wayfair_name  = str_replace('.csv', '', $ErpSheetLessW->name);
            $wayfair_name  = str_replace('Wayfair-', '', $wayfair_name);
        }
        if (isset($ErpSheetLessI->name)) {
            $ip_name  = str_replace('.csv', '', $ErpSheetLessI->name);
            $ip_name  = str_replace('IP-03 CSV-', '', $ip_name);
        }
        return view('product/erp_eu', ['products' => $products, 'last_sheet_w' => $ErpSheetLessW, 'last_sheet_ip' => $ErpSheetLessI, 'last_container' => $container_name, 'erp_diff' => 1]);
    }

    public function erp_eu_reason($type)
    {
        if ($type == 'returns') {
            $products = ErpEuProduct::whereRaw('erp_eu_products.quantity != erp_eu_products.eu_quantity')->whereNotNull('eu_quantity')->get();
        }
        if ($type == 'green') {
            $products = ErpEuProduct::whereRaw('erp_eu_products.quantity < erp_eu_products.eu_quantity')->whereNotNull('eu_quantity')->get();
        }
        if ($type == 'red') {
            $products = ErpEuProduct::whereRaw('erp_eu_products.quantity > erp_eu_products.eu_quantity')->whereNotNull('eu_quantity')->get();
        }
        $ErpSheetLessW = ErpEuSheet::where('type', 'less')->orderby('id', 'DESC')->where('name', 'LIKE', '%Wayfair%')->first();
        $ErpSheetLessI = ErpEuSheet::where('type', 'less')->orderby('id', 'DESC')->where('name', 'LIKE', '%IP%')->first();
        $ErpSheetAdd = ErpEuSheet::where('type', 'add')->orderby('id', 'DESC')->first();
        $name = str_replace('.xlsx', '', $ErpSheetAdd->name);
        $container_name = 'NA';
        if (isset($ErpSheetAdd->name)) {
            $container_name = str_replace('invoiceTable ', '', $name);
        }

        $wayfair_name  = str_replace('.csv', '', $ErpSheetLessW->name);
        $wayfair_name  = str_replace('Wayfair-', '', $wayfair_name);

        $ip_name  = str_replace('.csv', '', $ErpSheetLessI->name);
        $ip_name  = str_replace('IP-03 CSV-', '', $ip_name);
        return view('product/erp_eu', ['products' => $products, 'last_sheet_w' => $ErpSheetLessW, 'last_sheet_ip' => $ErpSheetLessI, 'last_container' => $container_name, 'erp_diff' => 1]);
    }

    public function updateErpEuSku(Request $request)
    {
        $product = ErpEuProduct::find($request->product_id);
        if ($request->sku_quant > $product->quantity) {
            $diff_quant = $request->sku_quant - $product->quantity;
            $type = 'add';
        } else {
            $diff_quant = $product->quantity - $request->sku_quant;
            $type = 'less';
        }
        $product->quantity = $request->sku_quant;

        $product->save();



        if ($diff_quant > 0) {
            $history = new ErpEuHistory;
            $history->sheet_id = 0;
            $history->quantity = $diff_quant;
            $history->sku = $product->sku;
            $history->type = $type;
            $history->reason = $request->reason;
            $history->remark = $request->remarks;
            $history->stock = $product->quantity;
            $history->date = date('Y-m-d');
            $history->save();
        }
        $diff_quant =  $product->eu_quantity - $request->sku_quant;
        $data = ['id' => $product->id, 'quantity' => $product->quantity, 'diff_quant' => $diff_quant];
        return response()->json($data);
        die;
    }

    public function importLessErpEuCSV(Request $request)
    {
        $file = $request->file('importLessCSV');
        $filename = $file->getClientOriginalName();
        ErpEuSheet::create(['name' => $filename, 'date' => date('Y-m-d'), 'type' => 'less']);
        Excel::import(new ErpEuProductLessImport, $file);
        return redirect('/product/erp-eu')->with('success', 'ERP Less Excel was updated successfully.');
    }

    public function importAddErpEuCSV(Request $request)
    {
        $file = $request->file('importAddCSV');
        $filename = $file->getClientOriginalName();
        ErpEuSheet::create(['name' => $filename, 'date' => date('Y-m-d'), 'type' => 'add']);
        Excel::import(new ErpEuProductAddImport, $file);
        return redirect('/product/erp-eu')->with('success', 'ERP Add Excel was updated successfully.');
    }

    public function export_erp_eu()
    {
        return (new ErpEuProductsExport())->download('erpproducts.xlsx');
    }

    public function erp_eu_diff_manager_export()
    {
        return (new ErpEuDiffProductsExport())->download('erp_discerpancies.xlsx');
    }

    public function view_erp_eu_history($id)
    {
        $product = ErpEuProduct::find($id);
        $history = ErpEuHistory::where('sku', $product->sku)->orderBy('id', 'desc')->get();
        return view('product/erp_eu_history', ['product' => $product, 'history' => $history]);
    }

    public function reason_filter_eu(Request $request)
    {
        $history = ErpEuHistory::whereDate('date', '>=', date('Y-m-d', strtotime($request->fsd)))->whereDate('date', '<=', date('Y-m-d', strtotime($request->fed)))->where('reason', $request->reason)->orderBy('id', 'desc')->get();
        return view('product/erp_history_reason', ['history' => $history]);
    }

    public function datewiseerpeu(Request $request)
    {
        $fsd = $request['fsd'];

        return (new ErpEuDateWiseExport($fsd))->download('erp-filtered.xlsx');
    }

    public function erpEuManager()
    {
        $products = ErpEuProduct::get();

        $ErpSheetLessW = ErpEuSheet::where('type', 'less')->orderby('id', 'DESC')->where('name', 'LIKE', '%Wayfair%')->first();
        $ErpSheetLessI = ErpEuSheet::where('type', 'less')->orderby('id', 'DESC')->where('name', 'LIKE', '%IP%')->first();
        $ErpSheetAdd = ErpEuSheet::where('type', 'add')->orderby('id', 'DESC')->first();
        $name = str_replace('.xlsx', '', $ErpSheetAdd->name);
        $container_name = 'NA';
        if (isset($ErpSheetAdd->name)) {
            $container_name = str_replace('invoiceTable ', '', $name);
        }

        $wayfair_name  = str_replace('.csv', '', $ErpSheetLessW->name);
        $wayfair_name  = str_replace('Wayfair-', '', $wayfair_name);

        $ip_name  = str_replace('.csv', '', $ErpSheetLessI->name);
        $ip_name  = str_replace('IP-03 CSV-', '', $ip_name);
        return view('product/erp_eu_manager', ['products' => $products, 'last_sheet_w' => $ErpSheetLessW, 'last_sheet_ip' => $ErpSheetLessI, 'last_container' => $container_name]);
    }

    public function erp_eu_negetive_manager()
    {
        $products = ErpEuProduct::where('eu_quantity', '<', '0')->get();

        $ErpSheet = ErpEuSheetManager::where('type', 'sheet')->orderby('id', 'DESC')->first();

        return view('product/erp_eu_manager', ['products' => $products, 'last_sheet' => $ErpSheet]);
    }

    public function erp_eu_diff_manager($type)
    {
        if ($type == 'all') {
            $products = ErpEuProduct::whereRaw('erp_eu_products.quantity != erp_eu_products.eu_quantity')->whereNotNull('eu_quantity')->get();
        }
        if ($type == 'green') {
            $products = ErpEuProduct::whereRaw('erp_eu_products.quantity < erp_eu_products.eu_quantity')->whereNotNull('eu_quantity')->get();
        }
        if ($type == 'red') {
            $products = ErpEuProduct::whereRaw('erp_eu_products.quantity > erp_eu_products.eu_quantity')->whereNotNull('eu_quantity')->get();
        }
        $ErpSheet = ErpEuSheetManager::where('type', 'sheet')->orderby('id', 'DESC')->first();

        return view('product/erp_eu_manager', ['products' => $products, 'last_sheet' => $ErpSheet, 'erp_diff' => 1]);
    }

    public function datewiseerpeumanager(Request $request)
    {
        $fsd = $request['fsd'];

        return (new ErpEuDateWiseExportManager($fsd))->download('erp-filtered-eu.xlsx');
    }

    public function updateErpEuSkuManager(Request $request)
    {
        $product = ErpEuProduct::find($request->product_id);
        if ($request->sku_quant > $product->eu_quantity) {
            $diff_quant = $request->sku_quant - $product->eu_quantity;
            $type = 'add';
        } else {
            $diff_quant = $product->eu_quantity - $request->sku_quant;
            $type = 'less';
        }
        $product->eu_quantity = $request->sku_quant;

        $product->save();

        if ($diff_quant > 0) {
            $history = new ErpEuHistoryManager;
            $history->sheet_id = 0;
            $history->quantity = $diff_quant;
            $history->sku = $product->sku;
            $history->type = $type;
            $history->reason = $request->reason;
            $history->remark = $request->remarks;
            $history->stock = $product->eu_quantity;
            $history->date = date('Y-m-d');
            $history->save();
        }
        $diff_quant = $request->sku_quant - $product->quantity;
        $data = ['id' => $product->id, 'quantity' => $product->eu_quantity, 'diff_quant' => $diff_quant];
        return response()->json($data);
    }

    public function importLessErpEuCSVManager(Request $request)
    {
        $file = $request->file('importLessCSV');
        $filename = $file->getClientOriginalName();
        ErpEuSheetManager::create(['name' => $filename, 'date' => date('Y-m-d'), 'type' => 'less']);
        Excel::import(new ErpEuProductLessImportManager, $file);
        return redirect('/product/erp-eu-manager')->with('success', 'ERP Less Excel was updated successfully.');
    }

    public function importAddErpEuCSVManager(Request $request)
    {
        $file = $request->file('importAddCSV');
        $filename = $file->getClientOriginalName();
        ErpEuSheetManager::create(['name' => $filename, 'date' => date('Y-m-d'), 'type' => 'sheet']);
        Excel::import(new ErpEuProductAddImportManager, $file);
        return redirect('/erp-eu-manager')->with('success', 'ERP Add Excel was updated successfully.');
    }

    public function importAdd2ErpEuCSVManager(Request $request)
    {
        $file = $request->file('importAddCSV');
        $filename = $file->getClientOriginalName();
        ErpEuSheetManager::create(['name' => $filename, 'date' => date('Y-m-d'), 'type' => 'sheet']);
        Excel::import(new ErpEuProductAdd2ImportManager, $file);
        return redirect('/erp-eu-manager')->with('success', 'ERP Add Excel was updated successfully.');
    }


    public function view_erp_eu_history_manager($id)
    {
        $product = ErpEuProduct::find($id);
        $history = ErpEuHistoryManager::where('sku', $product->sku)->orderBy('id', 'desc')->get();
        return view('product/erp_eu_history_manager', ['product' => $product, 'history' => $history]);
    }

    public function importErpEuCSV(Request $request)
    {
        $file = $request->file('importErpCSV');
        $filename = $file->getClientOriginalName();
        ErpEuSheet::create(['name' => $filename, 'date' => date('Y-m-d'), 'type' => 'stock']);
        Excel::import(new ErpEuProductImport, $file);
        return redirect('/product/erp-eu')->with('success', 'ERP Excel was updated successfully.');
    }

    public function importErpEuCSVManager(Request $request)
    {
        $file = $request->file('importErpCSVManager');
        $filename = $file->getClientOriginalName();
        ErpEuSheetManager::create(['name' => $filename, 'date' => date('Y-m-d'), 'type' => 'add']);
        Excel::import(new ErpEuProductImportManager, $file);
        return redirect('/erp-eu-manager')->with('success', 'ERP Excel was updated successfully.');
    }

    public function updateFinishingPrices()
    {

        // Fetch all products where finishing is not null or 0
        $products = product::whereNotNull('finishing')->where('finishing', '!=', '0')->get();

        foreach ($products as $product) {
            // Get rate from finishing_rates table where name = product's finishing
            $finishingRate = finishRate::where('name', $product->finishing)->first();

            if ($finishingRate) {
                $rate = $finishingRate->rate;
                $h = $product->height;
                $w = $product->width;
                $d = $product->depth;

                // Calculate volume in cubic meters
                $volume = ($h * $w * $d) / 1000000;

                // Calculate finishing price
                $finishingPrice = round(($rate / 60) * $volume);

                // Round the finishing price to the nearest 5
                $remainder = $finishingPrice % 5;
                if ($remainder < 3) {
                    $finishingPrice -= $remainder;
                } else {
                    $finishingPrice += (5 - $remainder);
                }

                // Update product with the new finishing price
                $product->finishing_price = $finishingPrice;


                $product->save();
            }
        }

        return response()->json(['message' => 'Finishing prices updated successfully']);
    }


    public function updateContractorBillFinishing()
    {
        // Get invoices created in September and October
        $invoices = invoice::where(function ($query) {
            $query->whereMonth('date', '=', 9)
                ->orWhereMonth('date', '=', 10); // September or October
        })
            ->whereYear('date', '=', 2024)
            ->get(['id']);

        // Extract the invoice IDs
        $invoiceIds = $invoices->pluck('id');

        // Get contractor bills for the extracted invoice IDs
        $contractorBills = ContractorBill::whereIn('invoice_id', $invoiceIds)
            ->get(); // Fetch all contractor bills for these invoices

        // Ensure that we are updating all the contractor bills
        foreach ($contractorBills as $bill) {
            // Retrieve the product associated with the contractor bill
            $product = Product::find($bill->product_id);

            if ($product) {
                // Check if the finishing or finishing_rate needs to be updated
                if ($bill->finishing != $product->finishing || $bill->finishing_rate != $product->finishing_price) {
                    // Update finishing and finishing_rate in contractor_bill
                    $bill->finishing = $product->finishing;
                    $bill->finishing_rate = $product->finishing_price; // Assuming finishing_price corresponds to finishing_rate
                }

                // Update the amount based on quantity and finishing
                $bill->amount = $bill->quantity * $product->finishing_price;

                // Save the changes
                $bill->save();
            }
        }

        return response()->json(['message' => 'Contractor bills updated successfully.']);
    }




    public function finishing_rates()
    {
        $finishRate = finishRate::all();
        return view('finishRate/index', ['finishRate' => $finishRate]);
    }
    public function addfinishing_rates()
    {
        return view('finishRate/create');
    }

    public function storefinishing_rates(Request $request)
    {
        // Validate incoming request
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'rate' => 'required|numeric|min:0',
        ]);

        finishRate::create([
            'name' => $validated['name'],
            'rate' => $validated['rate'],
        ]);

        return redirect('/finishing_rates')->with('success', 'Finish Rate added successfully!');
    }
    public function viewfinishing_rates($id)
    {
        $finishRate = finishRate::find($id);
        return view('finishRate/view', ['finishRate' => $finishRate]);
    }
    public function updatefinishing_rates(Request $request, $id)
    {
        // Find the finish rate by ID
        $finishRate = finishRate::find($id);

        if (!$finishRate) {
            return redirect()->route('finishing_rates.index')->with('error', 'Finish Rate not found.');
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'rate' => 'required|numeric|min:0',
        ]);

        $finishRate->old_rate = $finishRate->rate;
        $finishRate->name = $validated['name'];
        $finishRate->rate = $validated['rate'];
        $finishRate->save();

        return redirect('/finishing_rates')->with('success', 'Finish Rate updated successfully!');
    }
    public function deletefinishing_rates(Request $request, $id)
    {
        $finishRate = finishRate::find($id);

        if (!$finishRate) {
            return redirect('/finishing_rates')->with('error', 'Finish Rate not found.');
        }

        $finishRate->delete();

        return redirect('/finishing_rates')->with('success', 'Finish Rate deleted successfully!');
    }

      public function downloadbatch()
    {
        return Excel::download(new BatchExport, 'batch_export.xlsx');
    }



     public function batchimportCSV(Request $request)
    {
        $request->validate([
            'batchimportCSV' => 'required|mimes:xlsx,xls,csv'
        ]);

        try {
            Excel::import(new BatchImport, $request->file('batchimportCSV'));
            return redirect()->back()->with('success', 'Batch imported successfully!');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Error importing batch: ' . $e->getMessage());
        }
    }



      public function downloadbatchqty()
    {
        $fileName = 'Batch_Product_Wise_Report_' . date('Y_m_d_H_i_s') . '.xlsx';
        return Excel::download(new BatchProductWiseExport, $fileName);
    }


     public function batchcreate()
{
    // --- configuration ---
    $batchNo = 'GlobalVisionDirectLimited04250174';
    $supplierId = 181;
    $suppInNoValue = 'Global Vision Direct Limited';
    $logPath = storage_path('logs/batch_assign.log');

    // --- full data rows (from your paste) ---
    $rows = [
        ['id'=>37,'code'=>'IN066','product_qty'=>1,'assigned_qty'=>0,'unassigned_qty'=>1],
        ['id'=>48,'code'=>'IN080','product_qty'=>1,'assigned_qty'=>0,'unassigned_qty'=>1],
        ['id'=>56,'code'=>'IN104','product_qty'=>7,'assigned_qty'=>0,'unassigned_qty'=>7],
        ['id'=>67,'code'=>'IN122','product_qty'=>5,'assigned_qty'=>0,'unassigned_qty'=>5],
        ['id'=>104,'code'=>'IN186','product_qty'=>7,'assigned_qty'=>0,'unassigned_qty'=>7],
        ['id'=>110,'code'=>'IN207','product_qty'=>1,'assigned_qty'=>0,'unassigned_qty'=>1],
        ['id'=>111,'code'=>'IN208','product_qty'=>1,'assigned_qty'=>0,'unassigned_qty'=>1],
        ['id'=>149,'code'=>'IN263','product_qty'=>60,'assigned_qty'=>10,'unassigned_qty'=>50],
        ['id'=>181,'code'=>'IN320','product_qty'=>7,'assigned_qty'=>0,'unassigned_qty'=>7],
        ['id'=>192,'code'=>'IN341','product_qty'=>9,'assigned_qty'=>0,'unassigned_qty'=>9],
        ['id'=>194,'code'=>'IN353','product_qty'=>3,'assigned_qty'=>0,'unassigned_qty'=>3],
        ['id'=>223,'code'=>'IN426','product_qty'=>3,'assigned_qty'=>0,'unassigned_qty'=>3],
        ['id'=>260,'code'=>'IN510','product_qty'=>7,'assigned_qty'=>0,'unassigned_qty'=>7],
        ['id'=>268,'code'=>'IN535','product_qty'=>3,'assigned_qty'=>0,'unassigned_qty'=>3],
        ['id'=>273,'code'=>'IN552','product_qty'=>1,'assigned_qty'=>0,'unassigned_qty'=>1],
        ['id'=>278,'code'=>'IN662','product_qty'=>1,'assigned_qty'=>0,'unassigned_qty'=>1],
        ['id'=>329,'code'=>'ASB580','product_qty'=>12,'assigned_qty'=>11,'unassigned_qty'=>1],
        ['id'=>331,'code'=>'ASB604','product_qty'=>1,'assigned_qty'=>0,'unassigned_qty'=>1],
        ['id'=>386,'code'=>'IN077','product_qty'=>3,'assigned_qty'=>0,'unassigned_qty'=>3],
        ['id'=>392,'code'=>'IN089','product_qty'=>1,'assigned_qty'=>0,'unassigned_qty'=>1],
        ['id'=>393,'code'=>'IN090','product_qty'=>2,'assigned_qty'=>0,'unassigned_qty'=>2],
        ['id'=>394,'code'=>'IN091','product_qty'=>1,'assigned_qty'=>0,'unassigned_qty'=>1],
        ['id'=>424,'code'=>'IN165','product_qty'=>1,'assigned_qty'=>0,'unassigned_qty'=>1],
        ['id'=>458,'code'=>'1701314','product_qty'=>1,'assigned_qty'=>0,'unassigned_qty'=>1],
        ['id'=>475,'code'=>'IN675','product_qty'=>1,'assigned_qty'=>0,'unassigned_qty'=>1],
        ['id'=>603,'code'=>'IN903','product_qty'=>10,'assigned_qty'=>0,'unassigned_qty'=>10],
        ['id'=>652,'code'=>'IN941','product_qty'=>5,'assigned_qty'=>0,'unassigned_qty'=>5],
        ['id'=>762,'code'=>'IN877','product_qty'=>2,'assigned_qty'=>0,'unassigned_qty'=>2],

        ['id'=>814,'code'=>'IN2045','product_qty'=>1,'assigned_qty'=>0,'unassigned_qty'=>1],
        ['id'=>1087,'code'=>'IN1170','product_qty'=>1,'assigned_qty'=>0,'unassigned_qty'=>1],
        ['id'=>1090,'code'=>'IN1173','product_qty'=>2,'assigned_qty'=>0,'unassigned_qty'=>2],
        ['id'=>1094,'code'=>'IN1177','product_qty'=>1,'assigned_qty'=>0,'unassigned_qty'=>1],
        ['id'=>1095,'code'=>'IN1178','product_qty'=>5,'assigned_qty'=>0,'unassigned_qty'=>5],
        ['id'=>1097,'code'=>'IN1180','product_qty'=>1,'assigned_qty'=>0,'unassigned_qty'=>1],
        ['id'=>1098,'code'=>'IN1181','product_qty'=>9,'assigned_qty'=>5,'unassigned_qty'=>4],
        ['id'=>1119,'code'=>'IN1202','product_qty'=>2,'assigned_qty'=>0,'unassigned_qty'=>2],
        ['id'=>1284,'code'=>'IN1324','product_qty'=>3,'assigned_qty'=>0,'unassigned_qty'=>3],
        ['id'=>1381,'code'=>'IN1380','product_qty'=>2,'assigned_qty'=>0,'unassigned_qty'=>2],
        ['id'=>1413,'code'=>'IN1390','product_qty'=>1,'assigned_qty'=>0,'unassigned_qty'=>1],
        ['id'=>1426,'code'=>'BO150','product_qty'=>1,'assigned_qty'=>0,'unassigned_qty'=>1],
        ['id'=>1446,'code'=>'IN1401','product_qty'=>2,'assigned_qty'=>0,'unassigned_qty'=>2],
        ['id'=>1517,'code'=>'IN1439','product_qty'=>1,'assigned_qty'=>0,'unassigned_qty'=>1],
        ['id'=>1746,'code'=>'IN1605','product_qty'=>1,'assigned_qty'=>0,'unassigned_qty'=>1],
        ['id'=>1747,'code'=>'IN1606','product_qty'=>1,'assigned_qty'=>0,'unassigned_qty'=>1],
        ['id'=>1786,'code'=>'IN1627','product_qty'=>8,'assigned_qty'=>0,'unassigned_qty'=>8],
        ['id'=>1836,'code'=>'IN1672','product_qty'=>1,'assigned_qty'=>0,'unassigned_qty'=>1],
        ['id'=>2064,'code'=>'IN1780','product_qty'=>4,'assigned_qty'=>3,'unassigned_qty'=>1],
        ['id'=>2089,'code'=>'IN1812','product_qty'=>1,'assigned_qty'=>0,'unassigned_qty'=>1],
        ['id'=>2143,'code'=>'IN1853','product_qty'=>2,'assigned_qty'=>0,'unassigned_qty'=>2],
        ['id'=>2154,'code'=>'IN1863','product_qty'=>11,'assigned_qty'=>0,'unassigned_qty'=>11],
        ['id'=>2155,'code'=>'IN1864','product_qty'=>10,'assigned_qty'=>0,'unassigned_qty'=>10],
        ['id'=>2156,'code'=>'IN1865','product_qty'=>10,'assigned_qty'=>0,'unassigned_qty'=>10],
        ['id'=>2161,'code'=>'IN1870','product_qty'=>14,'assigned_qty'=>0,'unassigned_qty'=>14],
        ['id'=>2164,'code'=>'IN1873','product_qty'=>11,'assigned_qty'=>0,'unassigned_qty'=>11],
        ['id'=>2167,'code'=>'IN1876','product_qty'=>16,'assigned_qty'=>0,'unassigned_qty'=>16],
        ['id'=>2168,'code'=>'IN1877','product_qty'=>10,'assigned_qty'=>0,'unassigned_qty'=>10],
        ['id'=>2169,'code'=>'IN1878','product_qty'=>1,'assigned_qty'=>0,'unassigned_qty'=>1],
        ['id'=>2171,'code'=>'IN1880','product_qty'=>11,'assigned_qty'=>0,'unassigned_qty'=>11],
        ['id'=>2172,'code'=>'IN1881','product_qty'=>10,'assigned_qty'=>0,'unassigned_qty'=>10],
        ['id'=>2205,'code'=>'IN1914','product_qty'=>1,'assigned_qty'=>0,'unassigned_qty'=>1],
        ['id'=>2237,'code'=>'IN1947','product_qty'=>2,'assigned_qty'=>0,'unassigned_qty'=>2],
        ['id'=>2246,'code'=>'IN1956','product_qty'=>28,'assigned_qty'=>0,'unassigned_qty'=>28],
        ['id'=>2247,'code'=>'IN1957','product_qty'=>17,'assigned_qty'=>0,'unassigned_qty'=>17],
        ['id'=>2248,'code'=>'IN1958','product_qty'=>17,'assigned_qty'=>0,'unassigned_qty'=>17],
        ['id'=>2250,'code'=>'IN1960','product_qty'=>11,'assigned_qty'=>0,'unassigned_qty'=>11],
        ['id'=>2259,'code'=>'IN1969','product_qty'=>1,'assigned_qty'=>0,'unassigned_qty'=>1],
        ['id'=>2364,'code'=>'IN3075','product_qty'=>5,'assigned_qty'=>0,'unassigned_qty'=>5],
        ['id'=>2365,'code'=>'IN3076','product_qty'=>6,'assigned_qty'=>0,'unassigned_qty'=>6],
        ['id'=>2366,'code'=>'IN3077','product_qty'=>1,'assigned_qty'=>0,'unassigned_qty'=>1],
        ['id'=>2367,'code'=>'IN3078','product_qty'=>8,'assigned_qty'=>0,'unassigned_qty'=>8],
        ['id'=>2408,'code'=>'IN3116','product_qty'=>3,'assigned_qty'=>0,'unassigned_qty'=>3],
        ['id'=>2409,'code'=>'IN3117','product_qty'=>4,'assigned_qty'=>0,'unassigned_qty'=>4],
        ['id'=>2413,'code'=>'IN3121','product_qty'=>3,'assigned_qty'=>0,'unassigned_qty'=>3],
        ['id'=>2430,'code'=>'IN3139','product_qty'=>1,'assigned_qty'=>0,'unassigned_qty'=>1],
        ['id'=>2435,'code'=>'IN3144','product_qty'=>1,'assigned_qty'=>0,'unassigned_qty'=>1],
        ['id'=>2464,'code'=>'IN3173','product_qty'=>1,'assigned_qty'=>0,'unassigned_qty'=>1],
        ['id'=>2465,'code'=>'IN3174','product_qty'=>3,'assigned_qty'=>0,'unassigned_qty'=>3],
        ['id'=>2468,'code'=>'IN3177','product_qty'=>1,'assigned_qty'=>0,'unassigned_qty'=>1],
        ['id'=>2469,'code'=>'IN3178','product_qty'=>1,'assigned_qty'=>0,'unassigned_qty'=>1],
        ['id'=>2470,'code'=>'IN3179','product_qty'=>1,'assigned_qty'=>0,'unassigned_qty'=>1],
        ['id'=>2518,'code'=>'IN3224','product_qty'=>10,'assigned_qty'=>0,'unassigned_qty'=>10],
        ['id'=>2522,'code'=>'IN3226','product_qty'=>5,'assigned_qty'=>0,'unassigned_qty'=>5],
        ['id'=>2545,'code'=>'IN1924','product_qty'=>4,'assigned_qty'=>0,'unassigned_qty'=>4],
        ['id'=>2547,'code'=>'IN3244','product_qty'=>4,'assigned_qty'=>2,'unassigned_qty'=>2],
        ['id'=>2549,'code'=>'IN3246','product_qty'=>3,'assigned_qty'=>0,'unassigned_qty'=>3],
        ['id'=>2620,'code'=>'IN3299','product_qty'=>1,'assigned_qty'=>0,'unassigned_qty'=>1],

        ['id'=>2732,'code'=>'GLASS TABLE','product_qty'=>1,'assigned_qty'=>0,'unassigned_qty'=>1],
        ['id'=>2742,'code'=>'IN3367','product_qty'=>4,'assigned_qty'=>0,'unassigned_qty'=>4],
        ['id'=>2747,'code'=>'IN3372','product_qty'=>2,'assigned_qty'=>0,'unassigned_qty'=>2],
        ['id'=>2752,'code'=>'IN3377','product_qty'=>1,'assigned_qty'=>0,'unassigned_qty'=>1],
        ['id'=>2753,'code'=>'IN3378','product_qty'=>1,'assigned_qty'=>0,'unassigned_qty'=>1],
        ['id'=>2771,'code'=>'IN3395','product_qty'=>4,'assigned_qty'=>0,'unassigned_qty'=>4],
        ['id'=>2773,'code'=>'IN3397','product_qty'=>4,'assigned_qty'=>0,'unassigned_qty'=>4],
        ['id'=>2775,'code'=>'IN3399','product_qty'=>7,'assigned_qty'=>0,'unassigned_qty'=>7],
        ['id'=>2788,'code'=>'IN3412','product_qty'=>4,'assigned_qty'=>0,'unassigned_qty'=>4],
        ['id'=>2795,'code'=>'IN3419','product_qty'=>4,'assigned_qty'=>0,'unassigned_qty'=>4],
        ['id'=>2814,'code'=>'IN3435','product_qty'=>4,'assigned_qty'=>0,'unassigned_qty'=>4],

        ['id'=>2867,'code'=>'IN3473','product_qty'=>1,'assigned_qty'=>0,'unassigned_qty'=>1],
        ['id'=>2912,'code'=>'IN3511','product_qty'=>2,'assigned_qty'=>0,'unassigned_qty'=>2],
        ['id'=>2923,'code'=>'IN3519','product_qty'=>1,'assigned_qty'=>0,'unassigned_qty'=>1],
        ['id'=>2924,'code'=>'IN3520','product_qty'=>1,'assigned_qty'=>0,'unassigned_qty'=>1],
        ['id'=>2925,'code'=>'IN3521','product_qty'=>1,'assigned_qty'=>0,'unassigned_qty'=>1],
        ['id'=>2930,'code'=>'IN3526','product_qty'=>1,'assigned_qty'=>0,'unassigned_qty'=>1],
        ['id'=>2961,'code'=>'IN3547','product_qty'=>1,'assigned_qty'=>0,'unassigned_qty'=>1],
        ['id'=>2977,'code'=>'IN3563','product_qty'=>7,'assigned_qty'=>0,'unassigned_qty'=>7],
        ['id'=>3017,'code'=>'IN3595','product_qty'=>2,'assigned_qty'=>0,'unassigned_qty'=>2],
        ['id'=>3098,'code'=>'IN3618','product_qty'=>1,'assigned_qty'=>0,'unassigned_qty'=>1],
        ['id'=>3120,'code'=>'IN3633','product_qty'=>4,'assigned_qty'=>0,'unassigned_qty'=>4],
        ['id'=>3186,'code'=>'IN3664','product_qty'=>4,'assigned_qty'=>0,'unassigned_qty'=>4],
        ['id'=>3187,'code'=>'IN3665','product_qty'=>4,'assigned_qty'=>0,'unassigned_qty'=>4],
        ['id'=>3347,'code'=>'IN3781','product_qty'=>4,'assigned_qty'=>0,'unassigned_qty'=>4],
        ['id'=>3524,'code'=>'IN3897','product_qty'=>2,'assigned_qty'=>0,'unassigned_qty'=>2],
        ['id'=>3525,'code'=>'IN3898','product_qty'=>2,'assigned_qty'=>0,'unassigned_qty'=>2],
        ['id'=>3554,'code'=>'BO625','product_qty'=>1,'assigned_qty'=>0,'unassigned_qty'=>1],
        ['id'=>3799,'code'=>'IN4024','product_qty'=>1,'assigned_qty'=>0,'unassigned_qty'=>1],
        ['id'=>3833,'code'=>'IN4031','product_qty'=>1,'assigned_qty'=>0,'unassigned_qty'=>1],
        ['id'=>3885,'code'=>'IN4051','product_qty'=>4,'assigned_qty'=>0,'unassigned_qty'=>4],
        ['id'=>3998,'code'=>'IN4128','product_qty'=>1,'assigned_qty'=>0,'unassigned_qty'=>1],
        ['id'=>4003,'code'=>'IN4133','product_qty'=>4,'assigned_qty'=>0,'unassigned_qty'=>4],
        ['id'=>4015,'code'=>'IN4144','product_qty'=>3,'assigned_qty'=>0,'unassigned_qty'=>3],
        ['id'=>4057,'code'=>'IN4177','product_qty'=>1,'assigned_qty'=>0,'unassigned_qty'=>1],
        ['id'=>4060,'code'=>'IN4157','product_qty'=>1,'assigned_qty'=>0,'unassigned_qty'=>1],
        ['id'=>4079,'code'=>'IN4197','product_qty'=>1,'assigned_qty'=>0,'unassigned_qty'=>1],
        ['id'=>4081,'code'=>'IN4200','product_qty'=>2,'assigned_qty'=>0,'unassigned_qty'=>2],
        ['id'=>4082,'code'=>'IN4201','product_qty'=>2,'assigned_qty'=>0,'unassigned_qty'=>2],
        ['id'=>4095,'code'=>'IN4202','product_qty'=>4,'assigned_qty'=>0,'unassigned_qty'=>4],
        ['id'=>4096,'code'=>'IN4203','product_qty'=>4,'assigned_qty'=>0,'unassigned_qty'=>4],
        ['id'=>4097,'code'=>'IN4204','product_qty'=>4,'assigned_qty'=>0,'unassigned_qty'=>4],
        ['id'=>4098,'code'=>'IN4205','product_qty'=>4,'assigned_qty'=>0,'unassigned_qty'=>4],
        ['id'=>4099,'code'=>'IN4206','product_qty'=>4,'assigned_qty'=>0,'unassigned_qty'=>4],
        ['id'=>4115,'code'=>'IN4219','product_qty'=>1,'assigned_qty'=>0,'unassigned_qty'=>1],
        ['id'=>4138,'code'=>'IN4222','product_qty'=>4,'assigned_qty'=>0,'unassigned_qty'=>4],
        ['id'=>4139,'code'=>'IN4223','product_qty'=>4,'assigned_qty'=>0,'unassigned_qty'=>4],
        ['id'=>4140,'code'=>'IN4224','product_qty'=>4,'assigned_qty'=>0,'unassigned_qty'=>4],
        ['id'=>4141,'code'=>'IN4225','product_qty'=>4,'assigned_qty'=>0,'unassigned_qty'=>4],
    ];

    $results = [];

    // --- process each row (per-product transaction) ---
    foreach ($rows as $r) {
        $productId = (int) ($r['id'] ?? 0);
        $providedUnassigned = isset($r['unassigned_qty']) ? (float)$r['unassigned_qty'] : 0;
        $code = $r['code'] ?? null;

        if ($productId <= 0) {
            $results[] = ['product_id' => $productId, 'status' => 'skipped', 'message' => 'invalid product id'];
            continue;
        }

        if ($providedUnassigned <= 0) {
            $results[] = ['product_id' => $productId, 'status' => 'no_action', 'message' => 'provided unassigned <= 0'];
            continue;
        }

        DB::beginTransaction();
        try {
            $product = Product::find($productId);
            if (!$product) {
                DB::rollBack();
                $results[] = ['product_id' => $productId, 'status' => 'error', 'message' => 'product_not_found'];
                continue;
            }

            // recompute already assigned from DB to avoid over-assigning
            $alreadyAssigned = (float) DB::table('batch_product')->where('product_id', $productId)->sum('quantity');
            $productQtyDB = (float) $product->quantity;
            $availableToAssign = max(0, $productQtyDB - $alreadyAssigned);

            // pick qty to add: min(providedUnassigned, availableToAssign)
            $qtyToAdd = min($providedUnassigned, $availableToAssign);

            if ($qtyToAdd <= 0) {
                DB::rollBack();
                $results[] = [
                    'product_id' => $productId,
                    'status' => 'no_action',
                    'message' => 'would_overassign_or_no_capacity',
                    'provided_unassigned' => $providedUnassigned,
                    'already_assigned_db' => $alreadyAssigned,
                    'product_qty_db' => $productQtyDB
                ];
                continue;
            }

            // find or create batch (exact batch_no + supplier_id)
            $batch = Batch::firstOrCreate(
                ['batch_no' => $batchNo, 'supplier_id' => $supplierId],
                ['quantity' => 0, 'date' => now()->format('Y-m-d')]
            );

            $batchProduct = BatchProduct::firstOrNew([
                'batch_id' => $batch->id,
                'product_id' => $productId
            ]);

            $currentBatchProdQty = (float) $batchProduct->quantity;

            // safety check again
            if ($currentBatchProdQty + $qtyToAdd > $productQtyDB) {
                $qtyToAdd = max(0, $productQtyDB - $currentBatchProdQty);
            }

            if ($qtyToAdd <= 0) {
                DB::rollBack();
                $results[] = ['product_id' => $productId, 'status' => 'no_action', 'message' => 'after_safety_check_nothing_to_add'];
                continue;
            }

            // update batch_product and batch
            $batchProduct->quantity = $currentBatchProdQty + $qtyToAdd;
            $batchProduct->supp_in_no = $suppInNoValue;
            $batchProduct->save();

            $batch->quantity = (float)$batch->quantity + $qtyToAdd;
            $batch->save();

            DB::commit();

            // log
            $logLine = sprintf(
                "[%s] BULK_ASSIGN: product_id=%d code=%s added=%.4f provided_unassigned=%.4f product_qty_db=%.4f prev_assigned_db=%.4f batch_id=%d batch_no=%s supplier_id=%d\n",
                now()->toDateTimeString(),
                $productId,
                $code ?? ($product->code ?? $product->sku ?? 'N/A'),
                $qtyToAdd,
                $providedUnassigned,
                $productQtyDB,
                $alreadyAssigned,
                $batch->id,
                $batch->batch_no,
                $supplierId
            );
            file_put_contents($logPath, $logLine, FILE_APPEND | LOCK_EX);

            $results[] = [
                'product_id' => $productId,
                'code' => $code ?? ($product->code ?? null),
                'qty_assigned' => $qtyToAdd,
                'status' => 'assigned',
                'message' => 'Assigned to batch ' . $batch->batch_no
            ];
        } catch (\Exception $e) {
            DB::rollBack();
            $errLine = '[' . now()->toDateTimeString() . '] BULK_ASSIGN_ERROR: product_id=' . $productId . ' err=' . $e->getMessage() . PHP_EOL;
            file_put_contents($logPath, $errLine, FILE_APPEND | LOCK_EX);
            $results[] = ['product_id' => $productId, 'status' => 'error', 'message' => $e->getMessage()];
        }
    }

    // return summary for inspection
    return $results;
}


     public function exportSmallHardware()
    {
        return Excel::download(new ProductSmallHardwareExport, 'small_hardware_products.xlsx');
    }

       public function ConsumableimportCSV(Request $request)
    {
        $request->validate([
            'ConsumableimportCSV' => 'required|file|mimes:xlsx,xls,csv',
        ], [
            'ConsumableimportCSV.required' => 'Please select an Excel or CSV file to upload.',
            'ConsumableimportCSV.mimes'    => 'File must be xlsx, xls or csv.',
        ]);

        $file = $request->file('ConsumableimportCSV');

        try {
            Excel::import(new ProductConsumableImport, $file);
            return redirect('/product')->with('success', 'Product Consumable Excel was updated successfully.');
        } catch (\Maatwebsite\Excel\Exceptions\NoTypeDetectedException $e) {
            return redirect()->back()->with('error', 'Could not read file. Please use xlsx, xls or csv format.');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Import failed: ' . $e->getMessage());
        }
    }

    /**
     * Download dummy Consumable import template (all product codes + 10 consumable slots).
     */
    public function downloadConsumableImportTemplate()
    {
        return Excel::download(new ProductConsumableTemplateExport, 'consumable_import_template.xlsx');
    }

    public function downloadCartonImportTemplate()
    {
        return Excel::download(new CartonProductsTemplateExport, 'carton_import_template.xlsx');
    }

    public function downloadProductWeightLbsImportTemplate()
    {
        return Excel::download(new ProductWeightLbsTemplateExport, 'product_weight_lbs_import_template.xlsx');
    }

    public function importCSVcarton(Request $request)
    {
        $request->validate([
            'importCSV' => 'required|mimes:xlsx,xls,csv',
        ]);

        try {
            // Import the uploaded Excel file
            Excel::import(new CartonProductsImport, $request->file('importCSV'));

            return redirect()->back()->with('success', 'Carton products imported successfully.');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Error importing file: ' . $e->getMessage());
        }
    }

public function FinshingExtraPrice(){
    $FinshingExtraPrice = FinshingExtraPrice::orderBy('id', 'desc')->paginate(10);
    return view('product/FinshingExtraPrice',compact('FinshingExtraPrice'));
}

public function FinshingExtraPricecreate(){
    return view('product/FinshingExtraPricecreate');
}


public function FinshingExtraPricestore(Request $request)
{
    $request->validate([
        'price' => 'required|numeric|min:0',
    ]);

    FinshingExtraPrice::create([
        'price' => $request->price,
    ]);

    return redirect('/FinshingExtraPrice')->with('success', 'Finshing Extra Price added successfully');
}


public function FinshingExtraPriceedit($id){
     $FinshingExtraPrice = FinshingExtraPrice::find($id);
    return view('product/FinshingExtraPriceedit',compact('FinshingExtraPrice'));
}

public function FinshingExtraPriceupdate(Request $request)
{
    $request->validate([
        'id'    => 'required|exists:finshingExtra_price,id',
        'price' => 'required|numeric|min:0',
    ]);

    $price = FinshingExtraPrice::findOrFail($request->id);
    $price->update([
        'price' => $request->price,
    ]);

    return redirect('/FinshingExtraPrice')->with('success', 'Finshing Extra Price updated successfully');
}

public function FinshingExtraPricedelete($id)
{
    $used = product::where('finshing_extra_price_id', $id)->exists();

    if ($used) {
        return redirect()->back()
            ->with('error', 'This Finshing Extra Price is already used in products and cannot be deleted.');
    }

    $price = FinshingExtraPrice::findOrFail($id);
    $price->delete();

    return redirect()->back()
        ->with('success', 'Finshing Extra Price deleted successfully');
}


/**
     * Standalone: recalculates finishing_price from finishing_rates (base) + FinshingExtraPrice when linked.
     * Optional: dry_run=1 (preview only). Does not alter updateFinishingPrices().
     */
    public function exportFinishingPriceRecalcAndApply(Request $request)
    {
        $dryRun = $request->boolean('dry_run');
        $products = product::whereNotNull('finishing')->where('finishing', '!=', '0')->get();
        $rows = collect();

        foreach ($products as $product) {
            $old = (float) ($product->finishing_price ?? 0);
            $extraRow = $product->finshing_extra_price_id
                ? FinshingExtraPrice::find($product->finshing_extra_price_id)
                : null;
            $extraPrice = $extraRow ? (float) $extraRow->price : 0.0;

            $finishingRate = finishRate::where('name', $product->finishing)->first();

            if (!$finishingRate) {
                $rows->push([
                    $product->id,
                    $product->code ?? '',
                    $product->name ?? '',
                    (string) ($product->finishing ?? ''),
                    '',
                    '',
                    $old,
                    '',
                    $extraPrice,
                    '',
                    '',
                    'Skipped: no matching finishing_rates row',
                ]);
                continue;
            }

            $rate = $finishingRate->rate;
            $h = $product->height;
            $w = $product->width;
            $d = $product->depth;
            $volume = ($h * $w * $d) / 1000000;

            $finishingPrice = round(($rate / 60) * $volume);
            $remainder = $finishingPrice % 5;
            if ($remainder < 3) {
                $finishingPrice -= $remainder;
            } else {
                $finishingPrice += (5 - $remainder);
            }
            $baseNew = (float) $finishingPrice;
            $new = $baseNew + $extraPrice;

            if (!$dryRun) {
                $product->finishing_price = $new;
                $product->save();
            }

            $rows->push([
                $product->id,
                $product->code ?? '',
                $product->name ?? '',
                (string) ($product->finishing ?? ''),
                $rate,
                round($volume, 6),
                $old,
                $baseNew,
                $extraPrice,
                $new,
                $new - $old,
                $dryRun ? 'Dry run — not saved' : 'Updated',
            ]);
        }

        $filename = 'finishing_price_recalc_' . date('Y-m-d_His') . '.xlsx';

        return Excel::download(new FinishingPriceRecalcExport($rows), $filename);
    }


    public function ProductWeightLbsImport(Request $request)
{
    $request->validate([
        'importCSV' => 'required|mimes:xlsx,xls,csv'
    ]);

    try {
        Excel::import(new ProductWeightLbsImport, $request->file('importCSV'));

        return back()->with('success', 'Product weights imported successfully.');
    } catch (\Exception $e) {
        return back()->with('error', 'Import failed: '.$e->getMessage());
    }
}
    
    
}

// public function delete($id)
// {
//     $product = product::find($id);
//     $relation = $product->relation()->count();

//     if ($product) {
//         if ($relation>0){
//             return redirect('/product')->with('danger', 'Product has one or multiple relations.');
//         }
//         else{
//             return redirect('/product')->with('danger', 'Product has no relations.');
//             if ($product->delete()) {
//                 return redirect('/product')->with('success', 'Product deleted successfully.');
//             } 
//             else {
//                 return redirect('/product')->with('danger', 'Product was not found.');
//             }
//         }
//     }
// }