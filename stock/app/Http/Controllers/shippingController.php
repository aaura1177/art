<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\shipping;
use App\shippingLines;

class shippingController extends Controller
{
    public function index()
    {
    	$shipping = shipping::orderBy('shipping_line_id')->get();
		return view('shipping/index', ['shipping'=>$shipping]);
    }

    public function create()
    {
        $shippingLines=shippingLines::get();
        return view('shipping/create',['shippingLines'=>$shippingLines]);
    }

    public function getShippingData(Request $request){
        $shipping = shipping::where('month',$request['month'])->where('shipping_line_id',$request['shipping_line_id'])->first();
        if(isset($shipping->id)){
            $res['exist'] = 1;
            $res['body'] = $shipping;
        }else{
            $res['exist'] = 0;
        }
        return response()->json(['res'=>$res]);
    }

    public function store(Request $request)
    {	
        $shipping = shipping::where('month',$request['month'])->where('shipping_line_id',$request['shipping_line_id'])->first();
        if(!isset($shipping->id)){
            shipping::create([
                'month'=>$request['month'],
                'shipping_line_id'=>$request['shipping_line_id'],
                'container'=>'',
                'reference'=>'',
                'thc'=>$request['thc'],
                'bl'=>$request['bl'],
                'incidental_charges'=>$request['incidental_charges'],
                'transit_time'=>$request['transit_time'],
                'type'=>$request['type'],
                'ocean_freight'=>$request['ocean_freight'],
                'conversion_rate'=>$request['conversion_rate'],
                'thc_uk'=>$request['thc_uk'],
                'handling_charges'=>$request['handling_charges'],
                'other'=>$request['other'],
                'total_india'=>$request['total_india'],
                'total_uk'=>$request['total_uk']
            ]);
        }
        $q = shipping::create([
                'month'=>$request['month'],
                'shipping_line_id'=>$request['shipping_line_id'],
                'container'=>$request['container'],
                'reference'=>$request['reference'],
                'thc'=>$request['thc'],
                'bl'=>$request['bl'],
                'incidental_charges'=>$request['incidental_charges'],
                'transit_time'=>$request['transit_time'],
                'type'=>$request['type'],
                'ocean_freight'=>$request['ocean_freight'],
                'conversion_rate'=>$request['conversion_rate'],
                'thc_uk'=>$request['thc_uk'],
                'handling_charges'=>$request['handling_charges'],
                'other'=>$request['other'],
                'total_india'=>$request['total_india'],
                'total_uk'=>$request['total_uk'],
                'magnus'=>$request['magnus'],
                'magnus_date'=>$request['magnus_date'],
                'magnus_tracking_number'=>$request['magnus_tracking_number'],
                'eta_at_port'=>$request['eta_at_port']
            ]);
        
        return redirect('/shipping')->with('success', 'Shipping was added successfully.');
    }

    public function view($id)
    {
        $shipping = shipping::find($id);
        $shippingLines = shippingLines::all();
        if ($shipping) {
            return view('shipping/view', ['shipping'=>$shipping, 'shippingLines'=>$shippingLines]);
        } else {
            return redirect('/shipping')->with('danger', 'Shipping was not found.');
        }
    }

    public function update(Request $request, $id)
    {
        $shipping = shipping::find($id);
        if ($shipping) {

            $shipping->month = $request['month'];
            $shipping->shipping_line_id = $request['shipping_line_id'];
            $shipping->container = $request['container'];
            $shipping->reference = $request['reference'];
            $shipping->thc = $request['thc'];
            $shipping->bl = $request['bl'];
            $shipping->incidental_charges = $request['incidental_charges'];
            $shipping->transit_time = $request['transit_time'];
            $shipping->type = $request['type'];
            $shipping->ocean_freight = $request['ocean_freight'];
            $shipping->conversion_rate = $request['conversion_rate'];
            $shipping->thc_uk = $request['thc_uk'];
            $shipping->handling_charges = $request['handling_charges'];
            $shipping->other = $request['other'];
            $shipping->total_india = $request['total_india'];
            $shipping->total_uk = $request['total_uk'];
            $shipping->magnus = $request['magnus'];
            $shipping->magnus_date = $request['magnus_date'];
            $shipping->magnus_tracking_number = $request['magnus_tracking_number'];
            $shipping->eta_at_port = $request['eta_at_port'];
			
            if ($shipping->save()) {
                return redirect('shipping')->with('success', 'Shipping was updated successfully.');
            } else {
                return redirect('shipping')->with('danger', 'Error occurred while saving shipping.');
            }
        } else {
            return redirect('/shipping')->with('danger', 'Shipping was not found.');
        }
    }
	
	public function delete(Request $request, $id)
    {
        $shipping = shipping::where('id', $id)->first();
        if ($shipping) {
			if ($shipping->delete()) {
				return redirect('/shipping')->with('success', 'Shipping line deleted successfully.');
			} else {
				return redirect('/shipping')->with('danger', 'Shipping line was not found.');
			}
        }
    }
}
