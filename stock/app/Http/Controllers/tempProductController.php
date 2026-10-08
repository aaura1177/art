<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\category;
use App\subCategory;
use App\tempProduct;
use Image;
use \auth;
use Illuminate\Support\Facades\Storage;

class tempProductController extends Controller
{
	public function __construct()
    {
        $this->middleware(['auth','2fa']);
    }

    public function create()
    {
    	$category = category::get();
    	$subCategory = subCategory::get();

    	return view('temporaryProduct/create', ['category' => $category, 'subCategory' => $subCategory]);
    }

    public function store(Request $request)
    {
      $EAN = preg_replace("/[~!@#$%^&*()_+=`{}\[\]\|\"\:;'<>,.\/? ]+/", "", $request['ean']);
      
    	$request->validate([
    		'code' => 'unique:temp_product_table'
    	]);

    	if(!isset($errors)){

    		// if($request->hasfile('imgURL'))
    		// {
    		// 	$file = $request->file('imgURL');
        	// $extension = $file->getClientOriginalExtension(); // getting image extension
        	// $filename =$EAN.'.'.$extension;

			// 	  $directory = public_path('../../uploads/product');
			// 	  $imageUrl = $directory.'/'.$filename;
			// 	Image::make($file)->resize(200, 200, function ($constraint){
            //     $constraint->aspectRatio();
            //     })->save($imageUrl);
    		// }

    		// else{

            //     $filename = 'default.jpg';
            // }

            if ($request->hasFile('imgURL')) {
                $filePath = Storage::disk('s3')->put('stock/product', $request->file('imgURL'));
                $imageFileName = basename($filePath); 
                        } else {
                $imageFileName = 'default.jpg'; 
            }


            tempProduct::create([
      				'code'=>strtoupper($request['code']),
      				'EAN'=>strtoupper($EAN),
      				'HSN'=>strtoupper($request['hsn']),
      				'name'=>$request['name'],
      				'category_id'=>$request['category'],
      				'finishing'=>$request['finishing'],
      				'width'=>$request['width'],
      				'height'=>$request['height'],
      				'depth'=>$request['depth'],
      				'boxwidth'=>$request['boxwidth'],
      				'boxheight'=>$request['boxheight'],
      				'boxdepth'=>$request['boxdepth'],
      				'wholesalevolume'=>$request['wholesalevolume'],
      				'dropshipvolume'=>$request['dropshipvolume'],
      				'volume'=>$request['volume'],
      				'imageURL'=>$imageFileName,
      				'hardware'=>$request['hardware'],
      				'addons'=>$request['addons'],
      				'remarks'=>$request['remarks'],
      				'subcategory_id'=>$request['subcategory'],
      				'gstslab'=>$request['gstslab']
            ]);
    	};

    	return redirect('/temporaryProduct')->with('success', 'Temporary Product was added successfully.');
    }

    public function index(){
    	$tempProducts = tempProduct::get();
        $files = Storage::disk('s3')->files('stock/product');
        $fileMap = [];
        foreach ($files as $file) {
            $filename = basename($file);
            $fileMap[$filename] = Storage::disk('s3')->url($file);
        }
    	return view('temporaryProduct/index', ['tempProducts' => $tempProducts,'fileMap' => $fileMap]);
    }

    public function view($id)
    {
    	$tempProduct = tempProduct::find($id);
        if (!$tempProduct) {
            return redirect('/temporaryProduct')->with('danger', 'product not found.');
        }

        $imagePath = $tempProduct->imageURL;
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
        if ($tempProduct) {
            return view('temporaryProduct/view', ['tempProduct'=>$tempProduct, 'category'=>$category, 'subCategory'=>$subCategory,'matchedImage'=>$matchedImage]);
        } else {
            return redirect('/temporaryProduct')->with('danger', 'Temporary Product was not found.');
        }
    }

    public function update(Request $request, $id)
    {   
        $tempProduct = tempProduct::find($id);

        if ($tempProduct) {

            // if($request->hasfile('imgURL')) 
            // { 
            //   $file = $request->file('imgURL');
            //   $extension = $file->getClientOriginalExtension(); // getting image extension
            //   $filename =$request['ean'].'.'.$extension;

            //   $directory = public_path('../../uploads/product');
            //   $imageUrl = $directory.'/'.$filename;
            //   Image::make($file)->resize(200, 200, function ($constraint){
            //     $constraint->aspectRatio();
            //     })->save($imageUrl);
            // }

            // else{

            //     $filename = $tempProduct->imageURL;
            // }


            if ($request->hasFile('imgURL')) {

                $existingImage = $tempProduct->imageURL;

                $oldFilePath = 'stock/product/' . $existingImage;
                if (Storage::disk('s3')->exists($oldFilePath)) {
                    Storage::disk('s3')->delete($oldFilePath);
                }        
            

                $filePath = Storage::disk('s3')->put('stock/product', $request->file('imgURL'));
                $imageFileName = basename($filePath);               
            } else {
                $imageFileName = $product->imageURL ?? 'default.jpg';
            }



             $tempProduct->EAN=strtoupper($request['ean']);
             $tempProduct->HSN=strtoupper($request['hsn']);
             $tempProduct->name=$request['name'];
             $tempProduct->category_id=$request['category'];
             $tempProduct->finishing=$request['finishing'];
             $tempProduct->width=$request['width'];
             $tempProduct->height=$request['height'];
             $tempProduct->depth=$request['depth'];
             $tempProduct->boxwidth=$request['boxwidth'];
             $tempProduct->boxheight=$request['boxheight'];
             $tempProduct->boxdepth=$request['boxdepth'];
             $tempProduct->wholesalevolume=$request['wholesalevolume'];
             $tempProduct->dropshipvolume=$request['dropshipvolume'];
             $tempProduct->volume=$request['volume'];
             $tempProduct->imageURL=$imageFileName;
             $tempProduct->hardware=$request['hardware'];
             $tempProduct->addons=$request['addons'];
             $tempProduct->remarks=$request['remarks'];
             $tempProduct->quantity=$request['quantity'];
             $tempProduct->subcategory_id=$request['subcategory'];
             $tempProduct->gstslab=$request['gstslab'];

            if ($tempProduct->save()) {
                return back()->with('success', 'Temporary Product was updated successfully.');
            } else {
                return redirect('temporaryProduct')->with('danger', 'Error occurred while saving Temporary Product.');
            }
        } else {
            return redirect('/temporaryProduct')->with('danger', 'Temporary Product was not found.');
        }
    }

    public function delete($id)
    {
    	$tempProduct = tempProduct::find($id);
        $fileName = basename($product->imageURL); // Extract just the filename
            $filePath = 'stock/product/' . $fileName;
        
            if (Storage::disk('s3')->exists($filePath)) {
                Storage::disk('s3')->delete($filePath);
            }
        
    
    	if($tempProduct) {
    		if($tempProduct->delete()) {
    			return redirect('/temporaryProduct')->with('success', 'Temporary Product deleted successfully.');
    		}
    		else {
                return redirect('/temporaryProduct')->with('danger', 'Temporary Product was not found.');
            }
    	}

    }
}

