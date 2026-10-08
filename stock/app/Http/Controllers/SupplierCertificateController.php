<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\SupplierCertificate;
use auth;
use Illuminate\Support\Facades\Storage;

class SupplierCertificateController extends Controller
{
    public function index()
    {
        $certificates = SupplierCertificate::all();
        $files = Storage::disk('s3')->files('stock/supplier-certificate');
        $fileMap = [];
        foreach ($files as $file) {
            $filename = basename($file);
            $fileMap[$filename] = Storage::disk('s3')->url($file);
        }
        return view('supplierUser.certificates', compact('certificates', 'fileMap'));
    }


    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required',
            'certificate' => 'required|file|mimes:pdf,jpg,jpeg,png|max:2048',
        ]);
        
        // $path = Storage::put('supplier-certificate', $request->file('certificate'));
        // $url = Storage::url($path);
        
        $filePath = Storage::disk('s3')->put('stock/supplier-certificate', $request->file('certificate'));
        $imageFileName = basename($filePath);

        SupplierCertificate::create([
            'user_id' =>auth::user()->id,
            'name' => $request->name,
            'file_path' => $imageFileName,
        ]);

        return redirect('/certificates')->with('success', 'Certificate uploaded successfully!');
    }

    public function update(Request $request, SupplierCertificate $certificate)
    {
        $request->validate([
            'name' => 'required',
            'certificate' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:2048',
        ]);
          // Handle file upload if there's a new file



    // if ($request->hasfile('certificate')) {
    //     $path = Storage::put('supplier-certificate', $request->file('certificate'));
    //     $url = Storage::url($path);

        
    //     $certificate->file_path =$url;
    // }
        

    //     $certificate->name = $request->name;
    //     $certificate->save();


    if ($request->hasfile('certificate')) {
          
        $filePath = Storage::disk('s3')->put('stock/supplier-certificate', $request->file('certificate'));
        $imageFileName = basename($filePath); 


            $certificate->file_path = $imageFileName;
        }


        $certificate->name = $request->name;
        $certificate->save();


        return redirect('/certificates')->with('success', 'Certificate updated successfully!');
    }

    public function destroy(SupplierCertificate $certificate)
    {
        // \Storage::delete($certificate->file_path);
        $file = $certificate->file_path;
        $fileName = basename($certificate->file_path);
       
        $filePath = 'stock/supplier-certificate/' . $fileName;
        if (Storage::disk('s3')->exists($filePath)) {
            Storage::disk('s3')->delete($filePath);
        }
        $certificate->delete();

        return response()->json(['message' => 'Certificate deleted successfully!']);
    }
}