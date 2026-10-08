<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\performanceCard;
use App\performanceCardProduct;
use App\setting;
use App\productLocations;
use App\contractorBill;
use App\product;
use App\contractor;
use App\certificate;
use App\productSwapping;
use App\pbTable;
use App\purchaseBill;
use App\upholstreyContractor;
use App\upholstreyBill;
use App\supplier;
use App\cornerBill;
use Maatwebsite\Excel\Facades\Excel;

class performanceCardsController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth','2fa']);
    }
	
	
	public function index(){
		$performanceCard	=	performanceCard::orderBy('id','DESC')->get();
        return view('performanceCards/index',['performanceCard'=>$performanceCard]);
	}
	
	public function create(){
		$contractor = contractor::all();
		return view('performanceCards/create',['contractor'=>$contractor]);
	}
	
	public function store(Request $request){
		$q = performanceCard::create
      ([
        'contractor_id'=>$request['contractor_id'],
        'date'=>$request['date'],
        'job'=>$request['job']
      ]);

        foreach ($request['inv'] as $inv) {
			performanceCardProduct::create([
				'product_id' => $inv['product_id'],
				'performance_card_id' => $q->id,
				'qty_received' => $inv['qty_received'],
				'qty_rejected' => $inv['qty_rejected'],
				'net_qty' => $inv['net_qty'],
				'remarks' => $inv['remarks']
			]);
		}
		return redirect('/performanceCards')->with('success', 'performanceCard was added successfully.');
	}
	
	public function view($id)
    {
        $performanceCard =performanceCard::find($id);
        $contractor = contractor::all();
		$product=product::all();
		$performanceCardProduct=performanceCardProduct::where('performance_card_id', $id)->get();
		if ($performanceCard) {
			return view('performanceCards/view', ['performanceCard'=>$performanceCard, 'product'=>$product, 'performanceCardProduct'=>$performanceCardProduct,'contractor'=>$contractor]);
		} else {
			return redirect('/performanceCards')->with('danger', 'performanceCard was not found.');
		}
    }
	
	public function update($id, Request $request){
		$performanceCard = performanceCard::where('id', $id)->first();
        $performanceCardProduct = performanceCardProduct::where('performance_card_id', $id)->get();
		
		$performanceCard->contractor_id=$request['contractor_id'];
		$performanceCard->date=$request['date'];
		$performanceCard->job=$request['job'];

		foreach ($performanceCardProduct as $performanceCardProduct) {
			$performanceCardProduct->delete();
		}

		foreach ($request['inv'] as $inv) {

		
			performanceCardProduct::create([
				'product_id' => $inv['product_id'],
				'performance_card_id' => $performanceCard->id,
				'qty_received' => $inv['qty_received'],
				'qty_rejected' => $inv['qty_rejected'],
				'net_qty' => $inv['net_qty'],
				'remarks' => $inv['remarks']
			]);
		}

		if ($performanceCard->save()) {
			return back()->with('success', 'performanceCard updated successfully.');
		} else {
			return back()->with('danger', 'Error occurred while saving performanceCard.');
		}
	}
	
	 public function delete($id)
    {
        $performanceCard = performanceCard::where('id', $id)->first();
        
		$performanceCardProduct = performanceCardProduct::where('performance_card_id', $id)->get();

		if ($performanceCardProduct) {
			foreach ($performanceCardProduct as $key => $value) {
				$performanceCardProduct[$key]->delete();
			}  
		}

		if ($performanceCard) {
			if ($performanceCard->delete()) {
				return redirect('/performanceCards')->with('success', 'performanceCard deleted successfully.');
			} else {
				return redirect('/performanceCards')->with('danger', 'performanceCard was not found.');
			}
		}
    }
	
	public function modal($id){
		$performanceCard = performanceCard::where('id', $id)->first();
		$performanceCardProduct = performanceCardProduct::where('performance_card_id', $id)->get();
		$companyDetails = setting::first();
		if ($performanceCard) {
			return view('performanceCards/modal', ['performanceCard'=>$performanceCard, 'performanceCardProduct'=>$performanceCardProduct,'companyDetails'=>$companyDetails]);
		} else {
			return redirect('/performanceCards')->with('danger', 'performanceCard was not found.');
		}
	}
	
	public function detail($id){
		$performanceCardProduct = performanceCardProduct::where('performance_card_id', $id)->get();
		foreach($performanceCardProduct as $k=>$pp){
			$performanceCardProduct[$k]['product'] = $pp->product;
		}
		return response()->json($performanceCardProduct);
	}
}