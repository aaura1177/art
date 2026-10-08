<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\cornerpackaging;
use App\cornerpackagingDetail;
use App\setting;
use App\employee;
use App\invoice;
use App\supplier;
use App\Imports\PackagingImport;
use Maatwebsite\Excel\Facades\Excel;

class cornerpackagingController extends Controller
{
    public function index()
    {
    	$cornerpackaging = cornerpackaging::all();
		$supplier = supplier::all();
		$companyDetails = setting::first();
		return view('cornerpackaging/index', ['cornerpackaging'=>$cornerpackaging,'supplier'=>$supplier,'companyDetails'=>$companyDetails]);
    }
	
	public function importCSV(Request $request)
    {
          $request->validate([
        'importCSV' => 'required|file|mimes:csv,xls,xlsx', 
    ]);
        $file = $request->file('importCSV');
        Excel::import(new PackagingImport, $file);
        return redirect('/cornerpackaging')->with('success', 'Packaging Excel was updated successfully.');
    }

    public function create()
    {
		$employees	=	employee::all();
		$invoice[date('F Y')]	=	invoice::whereMonth('date','>=',(date('m')))->whereYear('date','=',(date('Y')))->where('buyer_id','!=',4)->get();
		$invoice[date('F Y',strtotime('last month'))]	=	invoice::whereMonth('date','>=',(date('m',strtotime('last month'))))->whereYear('date','=',(date('Y',strtotime('last month'))))->where('buyer_id','!=',4)->get();
        return view('cornerpackaging/create', ['employees'=>$employees,'invoice'=>$invoice]);
    }

    public function store(Request $request)
    {
			//echo '<pre>';print_r($_REQUEST);die;
			$q = cornerpackaging::create([
					'month'=>$request['month'],
					'invoice_ids'=>implode(',',$request['invoices'])
                ]);
			
			$cp = $request['cp'];
			foreach ($cp as $p){
				$s = cornerpackagingDetail::create([
                    'cornerpackaging_id'=>$q->id,
                    'employee_id'=>$p['employee_id'],
					'corner_quantity'=>$p['corner_quantity'],
					'l_quantity'=>$p['l_quantity'],
					'amount'=>$p['amount']
                ]);
			}
			
			return redirect('/cornerpackaging')->with('success', 'Corner packaging was added successfully.');
    }
	
	public function download(Request $request){
		//echo '<pre>';print_r($_REQUEST);die;
		$cornerpackaging = cornerpackaging::where('id',$request['id'])->first();
		$cornerpackagingDetail = cornerpackagingDetail::where('cornerpackaging_id',$request['id'])->get();
		$companyDetails = setting::first();
		$supplier = supplier::where('id',$request['supplier'])->first();
		$date = $request['date'];
		$cornerpackaging->po_no = $request['po_no'];
		$companyDetails->opo_no = $companyDetails->opo_no + 1;
		$cornerpackaging->save();
		$companyDetails->save();
		return view('cornerpackaging/download', ['cornerpackaging'=>$cornerpackaging, 'companyDetails' => $companyDetails,'cornerpackagingDetail'=>$cornerpackagingDetail,'supplier'=>$supplier,'date'=>$date]);
	}

    public function view($id)
    {
        $cornerpackaging = cornerpackaging::find($id);
		$invoice[date('F Y')]	=	invoice::whereMonth('date','>=',(date('m')))->whereYear('date','=',(date('Y')))->where('buyer_id','!=',4)->get();
		$invoice[date('F Y',strtotime('last month'))]	=	invoice::whereMonth('date','>=',(date('m',strtotime('last month'))))->whereYear('date','=',(date('Y',strtotime('last month'))))->where('buyer_id','!=',4)->get();
		$employees	=	employee::all();
        return view('/cornerpackaging/view', ['cornerpackaging'=>$cornerpackaging,'invoice'=>$invoice,'employees'=>$employees]);
    }

    public function update(Request $request, $id)
    {
        $cornerpackaging = cornerpackaging::where('id', $id)->first();
        $cornerpackagingDetail = cornerpackagingDetail::where('cornerpackaging_id', $id)->get();

        if ($cornerpackaging) {
             $cornerpackaging->month=$request['month'];
             $cornerpackaging->invoice_ids=implode(',',$request['invoices']);
             
             if(isset($cornerpackagingDetail)){
                foreach ($cornerpackagingDetail as $cornerpackagingDetail) {
                    $cornerpackagingDetail->delete();
                }
             }

            $cp = $request['cp'];
			foreach ($cp as $p){
				cornerpackagingDetail::create([
					'cornerpackaging_id'=>$id,
                    'employee_id'=>$p['employee_id'],
					'corner_quantity'=>$p['corner_quantity'],
					'l_quantity'=>$p['l_quantity'],
					'amount'=>$p['amount']
				]);
            }

            if ($cornerpackaging->save()) {
                return redirect('cornerpackaging')->with('success', 'PurchaseOrder was updated successfully.');
            } else {
                return redirect('cornerpackaging')->with('danger', 'Error occurred while saving PurchaseOrder.');
            }
        } else {
            return redirect('/cornerpackaging')->with('danger', 'PurchaseOrder can not be edited.');
        }
    }
	
	public function delete(Request $request, $id)
    {
        $cornerpackaging = cornerpackaging::where('id', $id)->first();
        if ($cornerpackaging) {
			if ($cornerpackaging->delete()) {
				return redirect('/cornerpackaging')->with('success', 'Packaging deleted successfully.');
			} else {
				return redirect('/cornerpackaging')->with('danger', 'Packaging was not found.');
			}
        }
    }
	
	public function empdata(){
		$employees	=	employee::all();
		return response()->json(['employees'=>$employees]);
	}
}
