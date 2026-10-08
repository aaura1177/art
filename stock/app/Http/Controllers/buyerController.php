<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\buyer;
use \auth;
use App\states;
use App\Helpers\Common;
class buyerController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth','2fa']);
    }

    public function create()
    {
        $states = states::orderBy('statename', 'ASC')->get();
        $country = 'india';
        // if(\Request::session()->get('country') == 'uk'){
        //     $country = 'uk';
        // }
        
        if(\Request::session()->get('country') ){
            $country = \Request::session()->get('country');
        }

        return view('buyer/create', ['states'=>$states,'country'=>$country]);
    }

    public function store(Request $request)
    {       
        $request->validate([
        'code' => 'unique:buyers',
        ]);

        if (!isset($errors))
        {
            if(!isset($request['state_code'])){
                $request['state_code'] = 0;
            }
            $buyer = buyer::create
          ([
            'code'=>strtoupper($request['code']),
            'c_name'=>$request['c_name'],  
            'name'=>$request['name'],
            'email'=>$request['email'],
            'phone'=>$request['phone'],
            'address1'=>$request['address1'],
            'address2'=>$request['address2'],
            'city'=>$request['city'],
            'state'=>$request['state'],
            'country'=>$request['country'],
             'tally_name'=>$request['tally_name'],
            'postcode'=>$request['postcode'],
            'buyertype'=>$request['buyertype'],
            'gstno'=>$request['gstno'],
            'state_code'=>$request['state_code'],
          ]);

          if(\Request::session()->get('country') == 'uk'){
            $buyer->is_uk = 1;
            $buyer->save();
          }
          if(\Request::session()->get('country') == 'us'){
            $buyer->is_us = 1;
            $buyer->save();
          }
          if(\Request::session()->get('country') == 'eu'){
            $buyer->is_eu = 1;
            $buyer->save();
          }
          if(\Request::session()->get('country') == 'canada'){
            $buyer->is_canada = 1;
            $buyer->save();
          }
          if(\Request::session()->get('country') == 'california'){
            $buyer->is_california = 1;
            $buyer->save();
          }
        };
        if (\Request::session()->get('country') == 'uk') {
            return redirect('/buyer_uk')->with('success', 'Buyer was added successfully.');
        } elseif (\Request::session()->get('country') == 'us') {
            return redirect('/buyer_us')->with('success', 'Buyer was added successfully.');
        }
        elseif (\Request::session()->get('country') == 'canada') {
            return redirect('/buyer_canada')->with('success', 'Buyer was added successfully.');
        }
        elseif (\Request::session()->get('country') == 'california') {
            return redirect('/buyer_california')->with('success', 'Buyer was added successfully.');
        } elseif (\Request::session()->get('country') == 'eu') {
            return redirect('/buyer_eu')->with('success', 'Buyer was added successfully.');
        } else {
            return redirect('/buyer')->with('success', 'Buyer was added successfully.');
        }        
    }

    public function index()
    {
        $buyer=buyer::where('is_uk',0)->get();
        return view('buyer/index',['buyer'=>$buyer]);
    }
    
    public function index_uk()
    {
        $buyer=buyer::where('is_uk',1)->get();
        return view('buyer/index',['buyer'=>$buyer]);
    }

    public function index_us()
    {
        $buyer=buyer::where('is_us',1)->get();
        return view('buyer/index',['buyer'=>$buyer]);
    }

    public function index_eu()
    {
        $buyer=buyer::where('is_eu',1)->get();
        return view('buyer/index',['buyer'=>$buyer]);
    }
    public function index_canada()
    {
        $buyer=buyer::where('is_canada',1)->get();
        return view('buyer/index',['buyer'=>$buyer]);
    }
    public function index_california()
    {
        $buyer=buyer::where('is_california',1)->get();
        return view('buyer/index',['buyer'=>$buyer]);
    }

    public function view($id)
    {
        $buyer = buyer::find($id);
        if ($buyer) {
            return view('buyer/view', ['buyer'=>$buyer]);
        } else {
            return redirect('/buyer')->with('danger', 'buyer was not found.');
        }
    }

    public function update(Request $request, $id)
    {   
        $buyer = buyer::find($id);

        if ($buyer) {
            $buyer->c_name=$request['c_name'];  
            $buyer->name=$request['name'];
            $buyer->address1=$request['address1'];
            $buyer->address2=$request['address2'];
            $buyer->city=$request['city'];
            $buyer->email=$request['email'];
            $buyer->phone=$request['phone'];
            $buyer->state=$request['state'];
              $buyer->tally_name=$request['tally_name'];
            $buyer->country=$request['country'];
            $buyer->postcode=$request['postcode'];
            $buyer->buyertype=$request['buyertype'];
            $buyer->gstno=$request['gstno'];
            $buyer->state_code=$request['state_code'];
           
            if ($buyer->save()) {
                return redirect('buyer')->with('success', 'Buyer was updated successfully.');
            } else {
                return redirect('buyer')->with('danger', 'Error occurred while saving product.');
            }
        } else {
            return redirect('/buyer')->with('danger', 'Buyer was not found.');
        }
    }

    public function delete(Request $request, $id)
    {
        $buyer = buyer::where('id', $id)->with('invoiceus', 'invoiceuk', 'invoiceeu', 'invoice','invoicecalifornia','invoicecanada')->first();
        $buyerRelationCount = 0;

   
    if ($buyer->invoice->count() > 0) {
        $buyerRelationCount = $buyer->invoice->count();
    } elseif ($buyer->invoiceeu->count() > 0) {
        $buyerRelationCount = $buyer->invoiceeu->count();
    } 
    elseif ($buyer->invoicecalifornia->count() > 0) {
        $buyerRelationCount = $buyer->invoicecalifornia->count();
    }
    elseif ($buyer->invoicecanada->count() > 0) {
        $buyerRelationCount = $buyer->invoicecanada->count();
    }
    elseif ($buyer->invoiceuk->count() > 0) {
        $buyerRelationCount = $buyer->invoiceuk->count();
    } elseif ($buyer->invoiceus->count() > 0) {
        $buyerRelationCount = $buyer->invoiceus->count();
    }
        if($buyerRelationCount > 0){
            return redirect()->back()->with('danger', 'Buyer cannot be deleted. Buyer exist in other relations.');
        }
        else {
            if ($buyer->delete()) {
                return redirect('/buyer')->with('success', 'Buyer deleted successfully.');
            } else {
                return redirect('/buyer')->with('danger', 'Buyer was not found.');
            }
        }
    }
}
