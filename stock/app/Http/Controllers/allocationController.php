<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\allocation;
use App\product;
use App\contractor;
use \auth;

class allocationController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth','2fa']);
    }

    public function create()
    {
        $product=product::get();
        $contractor=contractor::get();
        return view('allocation/create',['product'=>$product, 'contractor'=>$contractor]);
    }

    public function store(Request $request)
    {		
		
          $q = allocation::create([
                'contractor_id'=>$request['contractor_id'],
    	    	'product_id'=>$request['product_id'],
    	    	'quantity'=>$request['quantity'],
    	    	'finish'=>$request['finish'],
    	    	'refno'=>strtoupper($request['refno']),
    	    	'ucost'=>$request['ucost'],
    	    	'tcost'=>$request['tcost'],
    	    	'vol_unit'=>$request['vol_unit'],
    	    	'tvol'=>$request['tvol'],
    	    	'remarks'=>$request['remarks']
              ]);

		return redirect('/allocation')->with('success', 'Stock was added successfully.');
    }

    public function index()
    {
    	
        $allocation=allocation::get();
        return view('allocation/index',['allocation'=>$allocation]);
    }

    public function view($id)
    {

        $allocation = allocation::find($id);
        $product=product::all();
        $contractor=contractor::all();
        if ($allocation) {
            return view('allocation/view', ['allocation'=>$allocation, 'product'=>$product, 'contractor'=>$contractor]);
        } else {
            return redirect('/allocation')->with('danger', 'allocation was not found.');
        }
    }

    public function update(Request $request, $id)
    {   
        $allocation = allocation::find($id);

        if ($allocation) {
             $allocation->contractor_id=$request['contractor_id'];
             $allocation->product_id=$request['product_id'];
             $allocation->quantity=$request['quantity'];
             $allocation->finish=$request['finish'];
             $allocation->refno=strtoupper($request['refno']);
             $allocation->ucost=$request['ucost'];
             $allocation->tcost=$request['tcost'];
             $allocation->tvol=$request['tvol'];
             $allocation->vol_unit=$request['vol_unit'];
             $allocation->remarks=$request['remarks'];

            if ($allocation->save()) {
                return redirect('allocation')->with('success', 'Allocation was updated successfully.');
            } else {
                return redirect('allocation')->with('danger', 'Error occurred while saving allocation.');
            }
        } else {
            return redirect('/allocation')->with('danger', 'Allocation was not found.');
        }
    }

    public function delete($id)
    {
        $allocation = allocation::find($id);
        if ($allocation) {
            if ($allocation->delete()) {
                return redirect('/allocation')->with('success', 'allocation deleted successfully.');
            } else {
                return redirect('/allocation')->with('danger', 'allocation was not found.');
            }
        }
    }
}
