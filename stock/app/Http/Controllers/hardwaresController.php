<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\hardwares;
use App\hardwareSuppliers;

class hardwaresController extends Controller
{
    public function index()
    {
    	$hardwares = hardwares::all();
		return view('hardwares/index', ['hardwares'=>$hardwares]);
    }

    public function create()
    {
        return view('hardwares/create');
    }

    public function store(Request $request)
    {
			$hardware_supplier = hardwareSuppliers::where('name',$request['hardware_supplier'])->first();
			
			if(!isset($hardware_supplier->id)){
				$s = hardwareSuppliers::create([
					'name'=>$request['hardware_supplier']
				]);
			}else{
				$s	=	$hardware_supplier;
			}
			//echo $s->id;die;
			$q = hardwares::create([
                    'name'=>$request['name'],
                    'rate'=>$request['rate'],
                    'hardware_supplier'=>$s->id,
                ]);
			
           return redirect('/hardwares')->with('success', 'Hardware was added successfully.');
    }

    public function view($id)
    {
        $hardwares = hardwares::find($id);
        return view('/hardwares/view', ['hardwares'=>$hardwares]);
    }

    public function update(Request $request, $id)
    {
        $hardwares = hardwares::find($id);
        if ($hardwares) {
			$hardware_supplier = hardwareSuppliers::where('name',$request['hardware_supplier'])->first();
			if(!isset($hardware_supplier->id)){
				$s = hardwareSuppliers::create([
					'name'=>$request['hardware_supplier']
				]);
			}else{
				$s	=	$hardware_supplier;
			}
            $hardwares->name = $request['name'];
            $hardwares->rate = $request['rate'];
            $hardwares->hardware_supplier = $s->id;
			
            if ($hardwares->save()) {
                return redirect('hardwares')->with('success', 'Hardware was updated successfully.');
            } else {
                return redirect('hardwares')->with('danger', 'Error occurred while saving hardware.');
            }
        } else {
            return redirect('/hardwares')->with('danger', 'Hardware was not found.');
        }
    }
	
	public function delete(Request $request, $id)
    {
        $hardware = hardwares::where('id', $id)->first();
        if ($hardware) {
			if ($hardware->delete()) {
				return redirect('/hardwares')->with('success', 'Hardware deleted successfully.');
			} else {
				return redirect('/hardwares')->with('danger', 'Hardware was not found.');
			}
        }
    }
}
