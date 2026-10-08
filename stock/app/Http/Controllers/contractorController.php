<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\contractor;
use App\invoice;
use App\invoiceTable;
use App\contractorBill;
use \auth;
use App\states;


class contractorController extends Controller
{
     public function __construct()
    {
        $this->middleware(['auth','2fa']);
    }

    public function create()
    {
        $states=states::orderBy('statename', 'ASC')->get();
        return view('contractor/create', ['states'=>$states]);
    }

    public function store(Request $request)
    {       
      contractor::create
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

      return redirect('/contractor')->with('success', 'Contractor was added successfully.');
    }

    public function index()
    {
        $contractor=contractor::get();
        return view('contractor/index',['contractor'=>$contractor]);
    }

    public function view($id)
    {

        $contractor = contractor::find($id);
        $states = states::orderBy('statename', 'ASC')->get();
        if ($contractor) {
            return view('contractor/view', ['contractor'=>$contractor, 'states'=>$states]);
        } else {
            return redirect('/contractor')->with('danger', 'Contractor was not found.');
        }
    }

    public function update(Request $request, $id)
    {   
        $contractor = contractor::find($id);

       

        if ($contractor) {
            $contractor->c_name=$request['c_name'];  
            $contractor->name=$request['name'];
            $contractor->pan=strtoupper($request['pan']);
            $contractor->address1=$request['address1'];
            $contractor->address2=$request['address2'];
            $contractor->city=$request['city'];
            $contractor->state=$request['state'];
            $contractor->country=$request['country'];
            $contractor->postcode=$request['postcode'];
            $contractor->gst=$request['gst'];
            $contractor->gstin=strtoupper($request['gstin']);
            $contractor->state_code=$request['statecode'];
            $contractor->email=$request['email'];
            $contractor->phone1=$request['phone1'];
            $contractor->phone2=$request['phone2'];
            $contractor->tds=$request['tds'];
            $contractor->tdspercent=$request['tdspercent'];
            $contractor->gstpercent=$request['gstpercent'];

            if ($contractor->save()) {
                return redirect('contractor')->with('success', 'Contractor was updated successfully.');
            } else {
                return redirect('contractor')->with('danger', 'Error occurred while saving product.');
            }
        } else {
            return redirect('/contractor')->with('danger', 'Contractor was not found.');
        }
    }

    public function data()
    {
        $states=states::all();
        return response()->json(['states'=>$states]);
    }
    
    public function delete(Request $request, $id)
    {
        $contractor = contractor::where('id', $id)->first();
        $contractorRelationCount = $contractor->allocation->count();
        if($contractorRelationCount > 0){
            return redirect('/contractor')->with('danger', 'Contractor cannot be deleted. Contractor exist in other relations.');
        }
        else {
            if ($contractor->delete()) {
                return redirect('/contractor')->with('success', 'Contractor deleted successfully.');
            } else {
                return redirect('/contractor')->with('danger', 'Contractor was not found.');
            }
        }
    }
	
	public function downloadBill(Request $request){

		$contractor = 	contractor::where('id', $request['contractor'])->first();
		$month 		=	explode('-',$request['month']);
        //dd($month);
		$invoices	=	invoice::whereMonth('date','=',$month[0])->whereYear('date','=',$month[1])->get();

		if(count($invoices)){
			foreach($invoices as $invoice){
				$bills[$invoice->buyerorderno] 	=	contractorBill::where('contractor_id',$contractor->id)->where('quantity','>',0)->where('invoice_id',$invoice->id)->get();
				$invoice_ids[]			=	$invoice->id;
			}

			$bill 	=	contractorBill::where('contractor_id',$contractor->id)->where('quantity','>',0)->whereIn('invoice_id',$invoice_ids)->get();
            
			if(count($bill)){
				return view('contractor/downloadBill', ['bills'=>$bills,'contractor'=>$contractor,'month'=>$month]);
			}else{
				return redirect('/contractor')->with('danger', 'Contractor Bill was not found.');
			}
		}else{
			return redirect('/contractor')->with('danger', 'Contractor Bill was not found.');
		}
	}
}
