<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\smallhardware;
use App\smhsupplier;
use App\employee;
use App\invoice;
use App\product;
use App\Imports\SmallhardwareImport;
use Maatwebsite\Excel\Facades\Excel;

class smallhardwareController extends Controller
{
    public function index($id)
    {
    	$last = smallhardware::where('supplier_id',$id)->orderBy('created_at','desc')->first();
		if(isset($last->created_at)){
			$products = product::where('created_at','>',$last->created_at)->get();
		}else{
			$products = product::where('created_at','>','1970-01-01')->get();
		}
		// foreach($products as $product){
		// 	smallhardware::create([
		// 		'product_id'=>$product->id,
		// 		'dust_cover_size'=>'',
		// 		'dust_cover_price'=>0,
		// 		'pouch'=>0,
		// 		'pouch_price'=>0,
		// 		'supplier_id' => $id
		// 	]);
		// }
		$smallhardware = smallhardware::where('supplier_id',$id)->get();
		$suppliers=smhsupplier::get();
		return view('smallhardware/index', ['smallhardware'=>$smallhardware,'suppliers'=>$suppliers,'supplier_id'=>$id]);
    }
	
	public function importCSV(Request $request)
    {
        $file = $request->file('importCSV');
        Excel::import(new SmallhardwareImport, $file);
        return redirect('/smallhardware/1')->with('success', 'Smallhardware Excel was updated successfully.');
    }

    public function create()
    {
		$employees	=	employee::all();
		$invoice	=	invoice::all();
        return view('smallhardware/create', ['employees'=>$employees,'invoice'=>$invoice]);
    }

    public function store(Request $request)
    {
			$q = smallhardware::create([
                    'employee_id'=>$request['employee_id'],  
					'type'=>$request['type'],
					'quantity'=>$request['quantity'],
					'invoices'=>implode(',',$request['invoices']),
                ]);
			
           return redirect('/smallhardware')->with('success', 'Smallhardware was added successfully.');
    }

    public function view($id)
    {
        $smallhardware = smallhardware::find($id);
		$invoice	=	invoice::all();
		$employees	=	employee::all();
        return view('/smallhardware/view', ['smallhardware'=>$smallhardware,'invoice'=>$invoice,'employees'=>$employees]);
    }

    public function update(Request $request, $id)
    {
        $smallhardware = smallhardware::find($id);
        if ($smallhardware) {
			
            $smallhardware->employee_id = $request['employee_id'];
            $smallhardware->type = $request['type'];
            $smallhardware->quantity = $request['quantity'];
            $smallhardware->invoices = implode(',',$request['invoices']);
            
			
            if ($smallhardware->save()) {
                return redirect('smallhardware')->with('success', 'Smallhardware was updated successfully.');
            } else {
                return redirect('smallhardware')->with('danger', 'Error occurred while saving smallhardware.');
            }
        } else {
            return redirect('/smallhardware')->with('danger', 'Smallhardware was not found.');
        }
    }
	
	public function updateSmallhardware(Request $request){
		//echo '<pre>';print_r($_REQUEST);die;
		$smallhardware = smallhardware::where('id',$request['id'])->first();
		if(isset($smallhardware->id)){
			$smallhardware->dust_cover_size 		= $request['dust_cover_size'];
			$smallhardware->dust_cover_price 		= $request['dust_cover_price'];
			$smallhardware->pouch 					= $request['pouch'];
			$smallhardware->pouch_price 			= $request['pouch_price'];
			$smallhardware->save();
			$response['message'] = 'success';
			return response()->json($response);
		}
		die;
	}
	
	
	
	public function delete(Request $request, $id)
    {
        $smallhardware = smallhardware::where('id', $id)->first();
        if ($smallhardware) {
			if ($smallhardware->delete()) {
				return redirect('/smallhardware')->with('success', 'Smallhardware deleted successfully.');
			} else {
				return redirect('/smallhardware')->with('danger', 'Smallhardware was not found.');
			}
        }
    }

	public function updatePouchPrice(Request $request){
		$smallhardwares = smallhardware::get();
		foreach($smallhardwares as $smallhardware){
			if($smallhardware->pouch > 0){
				$smallhardware->pouch_price = $request['pouch_price'];
				$smallhardware->save();
			}
		}
		return redirect('/smallhardware/1')->with('success', 'Pouch price updated successfully!');
	}
}