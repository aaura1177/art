<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\quality;
use App\channel;
use App\product;
use App\supplier;
use App\stockLog;
use \auth;
use Image;
use Illuminate\Support\Facades\Storage;
use App\Exports\QualityExport;
use Maatwebsite\Excel\Facades\Excel;

class qualityController extends Controller
{
        public function __construct()
        {
                $this->middleware(['auth', '2fa']);
        }

        public function create()
        {
                $product = product::get();
                $channel = channel::get();
                $supplier = supplier::get();
                return view('quality/create', ['product' => $product, 'supplier' => $supplier, 'channel' => $channel]);
        }

        public function store(Request $request)
        {
                // if($request->hasfile('imgURL'))
                // {
                //  $product = product::find($request['product_id']);
                //   $files = $request['imgURL'];
                //   //print_r($files);die;
                //   $filenames = "";
                //   foreach($files as $k=>$file){
                //      $extension = $file->getClientOriginalExtension(); // getting image extension
                //      $filename =     time().'-'.$k.'.'.$extension;

                //      $directory = public_path('../../uploads/quality');
                //      $imageUrl = $directory.'/'.$filename;
                //      Image::make($file)->resize(1000, 1000, function ($constraint){
                //              $constraint->aspectRatio();
                //      })->save($imageUrl);
                //      $filenames = $filenames . $filename.",";
                //   }
                // }
                // else{
                //      $filenames = 'default.jpg';
                // }


                $imageUrls = [];

                if ($request->hasFile('imgURL')) {
                        foreach ($request->file('imgURL') as $file) {
                                $filePath = Storage::disk('s3')->put('stock/quality', $file);
                                $imageUrls[] = basename($filePath);
                        }
                } else {
                        $imageUrls[] = 'default.jpg';
                }


                $imageUrls = implode(',', $imageUrls);

                quality::create([
                        'product_id' => $request['product_id'],
                        'channel_id' => $request['channel_id'],
                        'supplier_inv_no' => strtoupper($request['supplier_inv_no']),
                        'status' => 0,
                        'quantity' => $request['quantity'],
                        'date' => $request['date'],
                        'remarks' => $request['remarks'],
                        'imageURL' => $imageUrls
                ]);



                return redirect('/quality')->with('success', 'Product marked for QC.');
        }

        public function view($id)
        {
                $quality = quality::find($id);

                $product = product::get();
                $channel = channel::get();
                return view('quality/view', ['product' => $product, 'channel' => $channel, 'quality' => $quality]);
        }

        // public function update(Request $request, $id){
        //      $quality = quality::find($id);
        //      if($request->hasfile('imgURL'))
        //      {
        //       $product = product::find($request['product_id']);
        //        $files = $request['imgURL'];
        //        //print_r($files);die;
        //        $filenames = "";
        //        foreach($files as $k=>$file){
        //              $extension = $file->getClientOriginalExtension(); // getting image extension
        //              $filename =     time().'-'.$k.'.'.$extension;

        //              $directory = public_path('../../uploads/quality');
        //              $imageUrl = $directory.'/'.$filename;
        //              Image::make($file)->resize(1000, 1000, function ($constraint){
        //                      $constraint->aspectRatio();
        //              })->save($imageUrl);
        //              $filenames = $filenames . $filename.",";
        //        }
        //      }
        //      else{
        //              $filenames = $quality->imageURL;
        //      }

        //      $quality->product_id    =       $request['product_id'];
        //     $quality->channel_id     =       $request['channel_id'];
        //     $quality->supplier_inv_no=       strtoupper($request['supplier_inv_no']);
        //     $quality->quantity               =       $request['quantity'];
        //     $quality->date                   =       $request['date'];
        //      $quality->remarks               =       $request['remarks'];
        //      $quality->imageURL              =       $filenames;
        //      $quality->save();
        //     return redirect('/quality')->with('success', 'Quality record updated');
        // }



        public function update(Request $request, $id)
        {
                $quality = quality::find($id);

                $imageUrl = $quality->imageURL ?? 'default.jpg';

                if ($request->hasFile('imgURL')) {
                        $existingImages = $quality->imageURL;
                        $oldFilePaths = [];

                        // Decode old images
                        if ($existingImages) {
                                $existingImages = json_decode($existingImages, true) ?: explode(',', $existingImages);
                                $oldFilePaths = array_map(function ($image) {
                                        return 'stock/quality/' . basename($image); // Extract filename even if URL
                                }, $existingImages);
                        }

                        // Delete old files from S3
                        foreach ($oldFilePaths as $oldFilePath) {
                                if (Storage::disk('s3')->exists($oldFilePath)) {
                                        Storage::disk('s3')->delete($oldFilePath);
                                }
                        }

                        // Upload new images
                        $imagePaths = []; // Store relative paths or filenames
                        foreach ($request->file('imgURL') as $file) {
                                $filePath = Storage::disk('s3')->put('stock/quality', $file);
                                $imagePaths[] = basename($filePath); // Save only filename
                                // OR: $imagePaths[] = $filePath; // if you want relative path like 'stock/quality/abc.jpg'
                        }

                        // Save to DB as JSON
                        $imageUrl = json_encode($imagePaths);

                        // Update model
                }


                // Update other quality fields
                $quality->product_id = $request['product_id'];
                $quality->channel_id = $request['channel_id'];
                $quality->supplier_inv_no = strtoupper($request['supplier_inv_no']);
                $quality->quantity = $request['quantity'];
                $quality->date = $request['date'];
                $quality->remarks = $request['remarks'];
                $quality->imageURL = $imageUrl;

                $quality->save();

                return redirect('/quality')->with('success', 'Quality record updated');
        }


        public function index()
        {
                $quality = quality::orderBy('created_at', 'asc')->get();
                $files = Storage::disk('s3')->files('stock/quality');

                 $fileMap = [];
        foreach ($files as $file) {
            $filename = basename($file);
            $fileMap[$filename] = Storage::disk('s3')->url($file);
        }

                return view('quality/index', ['quality' => $quality,  'fileMap' => $fileMap]);
        }

        public function complete(Request $request, $id)
        {
                $quality = quality::find($id);
                $quantity = $quality->quantity;
                $product_id = $quality->product_id;

                $product = product::find($product_id);
                if ($product) {

                        $quality->status = '0';
                        if ($product->save() && $quality->save()) {
                                return redirect('/quality')->with('success', 'Product marked for completion.');
                        }
                } else {
                        return redirect('/quality')->with('danger', 'Error Occured');
                }
        }

        public function delete(Request $request, $id)
        {
                $quality = Quality::find($id);

                if (!$quality) {
                        return redirect('/quality')->with('danger', 'Quality entry not found.');
                }

                $imageURLs = $quality->imageURL;

                if ($imageURLs) {
                        $imageURLs = json_decode($imageURLs, true);

                        foreach ($imageURLs as $filename) {
                                $filePath = 'stock/quality/' . $filename;

                                if (Storage::disk('s3')->exists($filePath)) {
                                        Storage::disk('s3')->delete($filePath);
                                }
                        }
                }

                $quality->delete();

                return redirect('/quality')->with('success', 'Entry deleted successfully.');
        }

public function download()
    {
        return Excel::download(new QualityExport, 'quality_data.xlsx');
    }

}