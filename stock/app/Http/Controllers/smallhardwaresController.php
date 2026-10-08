<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\smallhardwares;
use App\smallhardwareSupplier;
use App\supplier;
use App\Imports\SmallHardwaresImport;
use App\Exports\SmallHardwareExport;
use Maatwebsite\Excel\Facades\Excel;

class smallhardwaresController extends Controller
{
    public function index()
    {
    	$smallhardwares = smallhardwares::all();
		return view('smallhardwares/index', ['smallhardwares'=>$smallhardwares]);
    }

    public function create()
    {
        $suppliers = supplier::get();
        return view('smallhardwares/create', ['suppliers'=>$suppliers]);
    }

    public function store(Request $request)
    {
			$supplier = supplier::where('id',$request['smallhardware_supplier'])->first();
			
			// if(!isset($smallhardware_supplier->id)){
			// 	$s = smallhardwareSupplier::create([
			// 		'name'=>$request['smallhardware_supplier'],
			// 		'c_name'=>$request['smallhardware_supplier']
			// 	]);
			// }else{
			// 	$s	=	$hardware_supplier;
			// }
			//echo $s->id;die;
			$q = smallhardwares::create([
                    'name'=>$request['name'],
                    'rate'=>$request['rate'],
                    'supplier'=>$supplier->id,
                    'buyer'=>$request['buyer'],
                ]);
			
           return redirect('/smallhardwares')->with('success', 'Small Hardware was added successfully.');
    }

    public function view($id)
    {
        $smallhardwares = smallhardwares::find($id);
        $suppliers = supplier::get();
        return view('/smallhardwares/view', ['smallhardwares'=>$smallhardwares,'suppliers'=>$suppliers]);
    }

    public function update(Request $request, $id)
    {
        $smallhardwares = smallhardwares::find($id);
        if ($smallhardwares) {
			$supplier = supplier::where('id',$request['smallhardware_supplier'])->first();
            
            $smallhardwares->name = $request['name'];
            $smallhardwares->rate = $request['rate'];
            $smallhardwares->supplier = $supplier->id;
            $smallhardwares->buyer = $request['buyer'];
			
            if ($smallhardwares->save()) {
                return redirect('smallhardwares')->with('success', 'Small Hardware was updated successfully.');
            } else {
                return redirect('smallhardwares')->with('danger', 'Error occurred while saving hardware.');
            }
        } else {
            return redirect('/smallhardwares')->with('danger', 'Hardware was not found.');
        }
    }
	
	public function delete(Request $request, $id)
    {
        $hardware = smallhardwares::where('id', $id)->first();
        if ($hardware) {
			if ($hardware->delete()) {
				return redirect('/smallhardwares')->with('success', 'Small Hardware deleted successfully.');
			} else {
				return redirect('/smallhardwares')->with('danger', 'Small Hardware was not found.');
			}
        }
    }

    public function importCSV(Request $request)
    {
        $file = $request->file('importCSV');
        Excel::import(new SmallHardwaresImport, $file);
        return redirect('/product')->with('success', 'Product Excel was updated successfully.');
    }

    public function downloadExcel()
{
    return Excel::download(new SmallHardwareExport, 'small_hardware.xlsx');
}
}