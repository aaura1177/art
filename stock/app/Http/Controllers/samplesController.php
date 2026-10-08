<?php

namespace App\Http\Controllers;

use App\product;
use App\sample;
use App\category;
use App\hardwares;
use App\stockLog;
use App\setting;
use App\productLocations;
use App\subCategory;
use App\productGrouping;
use App\Exports\AllProductsExport;
use App\Exports\ProductCodeWiseExport;
use App\Exports\exportValuationExcel;
use App\Exports\exportInventory;
use Illuminate\Http\Request;
use Illuminate\Database\Eloquent\Relations;
use \auth;
use PDF;
use Image;
use Maatwebsite\Excel\Facades\Excel;
use App\Imports\ProductImport;
use App\Imports\FamilyImport;
use App\Imports\LegImport;
use App\Exports\inReport;
use App\Exports\outReport;
use App\legs;
use App\spbTable;
use App\samplePurchaseBill;
use App\purchaseBill;
use App\supplier;
use App\Batch;
use App\BatchProduct;
use Carbon\Carbon;

use App\samplePurchaseOrder;
use App\pbTable;
use Illuminate\Support\Facades\Storage;

use App\Exports\AllSamplesExport;
use App\UniqueReferenceNumber;
use App\UniqueReferenceNumberProduct;


class samplesController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth', '2fa']);
    }

    public function exportcsv()
    {
        return (new AllSamplesExport())->download('samples.xlsx');
    }

    public function create()
    {
        $category = category::get();
        $subCategory = subCategory::get();
        $hardwares = hardwares::get();
        return view('samples/create', ['category' => $category, 'subCategory' => $subCategory, 'hardwares' => $hardwares]);
    }

    public function store(Request $request)
    {
        // echo '<pre>';print_r($_REQUEST);die;

        $sampleCount = sample::whereMonth('created_at', date('m'))->count();
        $ai = $sampleCount + 1;

        $code = strtoupper(date('M-Y')) . '-00' . $ai;
        //echo $code;die;
        if (!isset($errors)) {

            // if($request->hasfile('imgURL')) 
            // { 
            //   $file = $request->file('imgURL');
            //   $extension = $file->getClientOriginalExtension(); // getting image extension
            //   $filename =$code.'.'.$extension;

            //   $directory = public_path('../../uploads/samples');
            //   $imageUrl = $directory.'/'.$filename;
            //   Image::make($file)->resize(400, 400, function ($constraint){
            //     $constraint->aspectRatio();
            //     })->save($imageUrl);
            // }

            // else{

            //     $filename = 'default.jpg';
            // }

            if ($request->hasFile('imgURL')) {
                $filePath = Storage::disk('s3')->put('stock/samples', $request->file('imgURL'));

                $imageFileName = basename($filePath);

                $imageUrl = $imageFileName;
            } else {
                $imageUrl = 'default.jpg';
            }


            sample::create([

                'code' => strtoupper($code),
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
                'imageURL' => $imageUrl,
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
                'subcategory_id' => $request['subcategory']
            ]);
        };

        return redirect('/samples')->with('success', 'Sample was added successfully.');
    }

    public function index()
    {
        $samples = sample::orderBy('id', 'desc')->get();
        $files = Storage::disk('s3')->files('stock/samples');
        $fileMap = [];
        foreach ($files as $file) {
            $filename = basename($file);
            $fileMap[$filename] = Storage::disk('s3')->url($file);
        }

        return view('samples/index', ['samples' => $samples, 'fileMap' => $fileMap]);
    }

    public function importCSV(Request $request)
    {
        $file = $request->file('importCSV');
        Excel::import(new SampleImport, $file);
        return redirect('/samples')->with('success', 'Samples Excel was updated successfully.');
    }

    public function viewreport()
    {
        $samples = sample::where('quantity', '<=', '10')->get();
        return view('report/index', ['samples' => $samples]);
    }

    public function view($id)
    {

        $sample = sample::find($id);
        if (!$sample) {
            return redirect('/samples')->with('danger', 'samples not found.');
        }
        $imagePath = $sample->imageURL;

        $parsedPath = parse_url($imagePath, PHP_URL_PATH);
        $cleanPath = ltrim($parsedPath, '/');

        $files = Storage::disk('s3')->files('stock/samples');
        // return $files;   


        // return PHP_URL_PATH;
        $fileUrls = array_map(function ($file) {
            return Storage::disk('s3')->url($file);
        }, $files);
        $matchedImage = array_filter($fileUrls, function ($url) use ($cleanPath) {
            return str_contains($url, $cleanPath);
        });




        $category = category::get();
        $subCategory = subCategory::get();
        $hardwares = hardwares::get();
        if ($sample) {
            return view('samples/view', ['sample' => $sample, 'category' => $category, 'subCategory' => $subCategory, 'hardwares' => $hardwares, 'matchedImage' => $matchedImage]);
        } else {
            return redirect('/samples')->with('danger', 'Sample was not found.');
        }
    }

    public function update(Request $request, $id)
    {

        $sample = sample::find($id);
        if ($sample) {

            // if($request->hasfile('imgURL')) 
            // { 
            //   $file = $request->file('imgURL');
            //   $extension = $file->getClientOriginalExtension(); // getting image extension
            //   $filename =$sample->code.'.'.$extension;

            //   $directory = public_path('../../uploads/samples');
            //   $imageUrl = $directory.'/'.$filename;
            //   Image::make($file)->resize(400, 400, function ($constraint){
            //     $constraint->aspectRatio();
            //     })->save($imageUrl);
            // }else{
            // 	$filename = $sample->imageURL;
            // }

            if ($request->hasFile('imgURL')) {

                $existingImage = $sample->imageURL;


                $oldFilePath = 'stock/samples/' . $existingImage;



                if (Storage::disk('s3')->exists($oldFilePath)) {
                    Storage::disk('s3')->delete($oldFilePath);
                }
                $filePath = Storage::disk('s3')->put('stock/samples', $request->file('imgURL'));
                $imageFileName = basename($filePath);
            } else {
                $imageFileName = $sample->imageURL ?? 'default.jpg';
            }

            // if($request->hasfile('prod_image_url')) 
            // { 
            //   $file = $request->file('prod_image_url');
            //   $extension = $file->getClientOriginalExtension(); // getting image extension
            //   $filename1 =$request['prod_code'] . '-1.' . $extension;

            //   $directory = public_path('../../uploads/allproducts/'.$request['prod_code']);
            //   if (!file_exists($directory)) {
            // 	mkdir($directory, 0777, true);
            //   }
            //   $imageUrl = $directory.'/'.$filename1;
            //   Image::make($file)->resize(400, 400, function ($constraint){
            //     $constraint->aspectRatio();
            //     })->save($imageUrl);
            // }

            // else{

            //     $filename1 = 'default.jpg';
            // }

            if ($request->hasFile('prod_image_url')) {
                $file = $request->file('prod_image_url');
                $extension = $file->getClientOriginalExtension();
                $filename1 = $request['prod_code'] . '-1.' . $extension;

                // Resize image using Intervention and store it temporarily
                $resizedImage = Image::make($file)
                    ->resize(400, 400, function ($constraint) {
                        $constraint->aspectRatio();
                    })->encode($extension);

                // Store on S3
                $s3Path = 'stock/allproducts/' . $request['prod_code'] . '/' . $filename1;
                Storage::disk('s3')->put($s3Path, (string) $resizedImage, 'public');

                // Save only the filename (or full S3 path if needed)
                $imageUrl1 = $filename1;
            } else {
                $imageUrl1 = 'default.jpg';
            }


            $sample->name = $request['name'];
            $sample->category_id = $request['category'];
            $sample->finishing = $request['finishing'];
            $sample->width = $request['width'];
            $sample->height = $request['height'];
            $sample->depth = $request['depth'];
            $sample->boxwidth = $request['boxwidth'];
            $sample->boxheight = $request['boxheight'];
            $sample->boxdepth = $request['boxdepth'];
            $sample->wholesalevolume = $request['wholesalevolume'];
            $sample->dropshipvolume = $request['dropshipvolume'];
            $sample->volume = $request['volume'];
            $sample->imageURL = $imageFileName;
            $sample->hardware1 = $request['hardware1'];
            $sample->hardware2 = $request['hardware2'];
            $sample->hardware3 = $request['hardware3'];
            $sample->hardware4 = $request['hardware4'];
            $sample->hardware5 = $request['hardware5'];
            $sample->hardware1_quantity = $request['hardware1_quantity'];
            $sample->hardware2_quantity = $request['hardware2_quantity'];
            $sample->hardware3_quantity = $request['hardware3_quantity'];
            $sample->hardware4_quantity = $request['hardware4_quantity'];
            $sample->hardware5_quantity = $request['hardware5_quantity'];
            $sample->upholstry = $request['upholstry'];
            $sample->corner = $request['corner'];
            $sample->lhardware = $request['lhardware'];
            $sample->addons = $request['addons'];
            $sample->remarks = $request['remarks'];
            $sample->subcategory_id = $request['subcategory'];
            $sample->prod_code = $request['prod_code'];
            $sample->ean = $request['ean'];
            $sample->hsn = $request['hsn'];
            $sample->gstslab = $request['gstslab'];

            if ($request['create_prod'] == 'on') {
                $product = product::create([

                    'code' => strtoupper($request['prod_code']),
                    'EAN' => strtoupper($request['ean']),
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
                    'imageURL' => $imageUrl1,
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
                    'gstslab' => $request['gstslab'],
                    'quantity' => $sample->quantity
                ]);

                $sample->status = 1;
                $sample->product_id = $product->id;

                $spbRowsByBill = spbTable::where('sample_id', $sample->id)->get()->groupBy('sample_purchasebill_id');

                foreach ($spbRowsByBill as $billId => $billRows) {
                    $purchaseBill = samplePurchaseBill::find($billId);
                    if (! $purchaseBill) {
                        continue;
                    }
                    $samplePurchaseOrder = samplePurchaseOrder::where('id', $purchaseBill->purchaseOrder_id)->first();
                    if (! $samplePurchaseOrder) {
                        continue;
                    }

                    $carbonDate   = Carbon::parse($purchaseBill->supp_inv_date);
                    $Batchmonth   = $carbonDate->format('m') . $carbonDate->format('y');
                    $Batchmonth1  = $carbonDate->format('m') . '/' . $carbonDate->format('y');

                    $supplier = supplier::find($samplePurchaseOrder->supplier_id);
                    $prefix   = $supplier->short_name . $Batchmonth;

                    $batch = Batch::where('date', $Batchmonth1)
                        ->whereRaw("LEFT(batch_no, LENGTH(batch_no) - 4) = ?", [$prefix])
                        ->first();

                    if (! $batch) {
                        $batch = Batch::create([
                            'batch_no' => $supplier->short_name . $Batchmonth . rand(1000, 9999),
                            'quantity' => 0,
                            'supplier_id' => $samplePurchaseOrder->supplier_id,
                            'date'     => $Batchmonth1,
                        ]);
                    }

                    $totalBillReceive = 0;
                    foreach ($billRows as $vr) {
                        $totalBillReceive += (int) $vr->receiveqty;
                    }

                    $billUniqueReference = null;
                    if ($totalBillReceive > 0 && $batch) {
                        do {
                            $sampleRefNo = 'SMP-' . now()->format('Ymd') . '-' . strtoupper(substr(str_shuffle('ABCDEFGHJKLMNPQRSTUVWXYZ23456789'), 0, 6));
                        } while (UniqueReferenceNumber::where('ref_no', $sampleRefNo)->exists());

                        $billUniqueReference = UniqueReferenceNumber::create([
                            'ref_no' => $sampleRefNo,
                            'batch_id' => $batch->id,
                            'supplier_id' => $samplePurchaseOrder->supplier_id,
                            'supplier_invoice_id' => null,
                            'original_qty' => $totalBillReceive,
                            'remqty' => $totalBillReceive,
                            'is_old' => 1,
                        ]);
                    }

                    $suppInvUpper = strtoupper($purchaseBill->supp_inv_no);
                    $refSupplierLabel = $samplePurchaseOrder->ref_supplier ?? 'UK-18 NEW SAMPLES';

                    foreach ($billRows as $value) {
                        if ($purchaseBill->swap_id == null) {
                            $q = purchaseBill::create([
                                'purchaseOrder_id' => 0,
                                'ewaybill' => strtoupper($purchaseBill->ewaybill),
                                'supp_inv_no' => strtoupper($purchaseBill->supp_inv_no),
                                'supp_inv_date' => $purchaseBill->supp_inv_date,
                                'quantity' => $purchaseBill->quantity,
                                'subtotal' => $purchaseBill->subtotal,
                                'gst' => $purchaseBill->gst,
                                'freight' => $purchaseBill->freight,
                                'total' => $purchaseBill->total,
                                'created_at' => $purchaseBill->created_at
                            ]);
                            $purchaseBill->swap_id = $q->id;
                            $purchaseBill->save();
                        } else {
                            $q = purchaseBill::find($purchaseBill->swap_id);
                        }
                        $p = pbTable::create([
                            'product_id' => $product->id,
                            'ean' => $request['ean'],
                            'purchaseOrder_id' => $q->purchaseOrder_id,
                            'purchaseBill_id' => $q->id,
                            'orderqty' => $value->orderqty,
                            'receiveqty' => $value->receiveqty,
                            'remainingqty' => $value->orderqty - $value->receiveqty,
                            'rate' => $value->rate,
                            'amount' => $value->amount,
                            'location' => $value->location,
                            'created_at' => $value->created_at
                        ]);

                        $batchproduct = BatchProduct::where('product_id', $product->id)
                            ->where('batch_id', $batch->id)
                            ->first();

                        if ($batchproduct) {
                            $batchproduct->quantity += $value->receiveqty;
                            $batchproduct->save();
                        } else {
                            $batchproduct = BatchProduct::create([
                                'batch_id'   => $batch->id,
                                'product_id' => $product->id,
                                'quantity'   => $value->receiveqty,
                                'date'       => now()->toDateString(),
                                'supp_in_no' => $suppInvUpper,
                            ]);
                        }

                        $batch->quantity += $value->receiveqty;
                        $batch->save();

                        $lineRecv = (int) $value->receiveqty;
                        if ($billUniqueReference && $lineRecv > 0) {
                            UniqueReferenceNumberProduct::create([
                                'unique_referencenumber_id' => $billUniqueReference->id,
                                'product_id' => $product->id,
                                'originalqty' => $lineRecv,
                                'remaining_qty' => $lineRecv,
                                'remark' => 'Created from sample create_prod backfill',
                            ]);
                        }

                        $formattedSampleRef = $suppInvUpper . '(' . $lineRecv . '/0)';

                        $stockLog = stockLog::where('product_id', $product->id)
                            ->orderBy('id', 'DESC')
                            ->first();

                        if (! $stockLog) {
                            stockLog::create([
                                'product_id' => $product->id,
                                'voucher_no' => $suppInvUpper,
                                'supplier_inv_no' => $suppInvUpper,
                                'ref_no' => $refSupplierLabel,
                                'quantity' => $value->receiveqty,
                                'opening_balance' => 0,
                                'remaining_stock' => $value->receiveqty,
                                'type' => 1,
                                'entity_id' => $q->id,
                                'batch_no' => $batch->batch_no,
                                'batch_balance' => $batchproduct->quantity,
                                'supplier_name' => $supplier->c_name,
                                'created_at' => $value->created_at,
                                'reference_number' => $formattedSampleRef,
                                'reference_quantity' => $lineRecv,
                            ]);
                        } else {
                            stockLog::create([
                                'product_id' => $product->id,
                                'voucher_no' => $suppInvUpper,
                                'supplier_inv_no' => $suppInvUpper,
                                'ref_no' => $refSupplierLabel,
                                'quantity' => $value->receiveqty,
                                'opening_balance' => $stockLog->remaining_stock,
                                'remaining_stock' => $value->receiveqty + $stockLog->remaining_stock,
                                'type' => 1,
                                'entity_id' => $q->id,
                                'batch_no' => $batch->batch_no,
                                'batch_balance' => $batchproduct->quantity,
                                'supplier_name' => $supplier->c_name,
                                'created_at' => $value->created_at,
                                'reference_number' => $formattedSampleRef,
                                'reference_quantity' => $lineRecv,
                            ]);
                        }
                    }
                }
            }

            if ($sample->save()) {
                return redirect('samples')->with('success', 'Sample was updated successfully.');
            } else {
                return redirect('samples')->with('danger', 'Error occurred while saving sample.');
            }
        } else {
            return redirect('/samples')->with('danger', 'Sample was not found.');
        }
    }

    public function delete(Request $request, $id)
    {
        $sample = sample::where('id', $id)->first();

        $fileName = basename($sample->imageURL);
        $filePath = 'stock/samples/' . $fileName;

        if (Storage::disk('s3')->exists($filePath)) {
            Storage::disk('s3')->delete($filePath);
        }

        if ($sample) {
            if ($sample->delete()) {
                return redirect('/samples')->with('success', 'Sample deleted successfully.');
            } else {
                return redirect('/samples')->with('danger', 'Sample was not found.');
            }
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