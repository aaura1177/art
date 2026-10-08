<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\smhsupplier;
use App\smallhardware;
use \auth;
use App\states;
use App\packagingPrice;
use App\Imports\SuppliersImports;
use Maatwebsite\Excel\Facades\Excel;


class smhsupplierController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth','2fa']);
    }

    public function create()
    {
        $states = states::orderBy('statename', 'ASC')->get();
        return view('smhsupplier/create', ['states'=>$states]);
    }
	
	public function importCSV(Request $request)
    {
        $file = $request->file('importCSV');
        Excel::import(new SmhSuppliersImports, $file);
        return redirect('/smhsupplier')->with('success', 'Product Supplier Excel was updated successfully.');
    }

    public function store(Request $request)
    {       
        //dd($request);
        $s = smhsupplier::create
        ([
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
            'gstpercent'=>$request['gstpercent']
        ]);

      return redirect('/smhsupplier')->with('success', 'Supplier was added successfully.');
    }

    public function index()
    {
        $supplier=smhsupplier::get();
        return view('smhsupplier/index',['supplier'=>$supplier]);
    }

    public function view($id)
    {

        $smhsupplier = smhsupplier::find($id);
        $states = states::orderBy('statename', 'ASC')->get();
        //$poProduct = supplierProduct::where('supplier_id',$id)->get();
        //$packaging_pricing = packagingPrice::where('supplier_id',$id)->first();
        if ($smhsupplier) {
            return view('smhsupplier/view', ['supplier'=>$smhsupplier, 'states'=>$states]);
        } else {
            return redirect('/smhsupplier')->with('danger', 'Supplier was not found.');
        }
    }

    public function update(Request $request, $id)
    {   
        $supplier = smhsupplier::find($id);

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
            $supplier->gstpercent=$request['gstpercent'];
            

            if ($supplier->save()) {
                return redirect('smhsupplier')->with('success', 'Supplier was updated successfully.');
            } else {
                return redirect('smhsupplier')->with('danger', 'Error occurred while saving product.');
            }
        } else {
            return redirect('/smhsupplier')->with('danger', 'supplier was not found.');
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
}
