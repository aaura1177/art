<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\shippingLines;

class shippingLinesController extends Controller
{
    public function index()
    {
    	$shippingLines = shippingLines::all();
		return view('shippingLines/index', ['shippingLines'=>$shippingLines]);
    }

    public function create()
    {
        return view('shippingLines/create');
    }

    public function store(Request $request)
    {	
        $q = shippingLines::create([
                'name'=>$request['name'],
                'agent_name'=>$request['agent_name'],
                'agent_uk'=>$request['agent_uk']
            ]);
        
        return redirect('/shippingLines')->with('success', 'Shipping line was added successfully.');
    }

    public function view($id)
    {
        $shippingLine = shippingLines::find($id);
        return view('/shippingLines/view', ['shippingLine'=>$shippingLine]);
    }

    public function update(Request $request, $id)
    {
        $shippingLine = shippingLines::find($id);
        if ($shippingLine) {			
            $shippingLine->name = $request['name'];
            $shippingLine->agent_name = $request['agent_name'];
            $shippingLine->agent_uk = $request['agent_uk'];
			
            if ($shippingLine->save()) {
                return redirect('shippingLines')->with('success', 'Shipping line was updated successfully.');
            } else {
                return redirect('shippingLines')->with('danger', 'Error occurred while saving shipping line.');
            }
        } else {
            return redirect('/shippingLines')->with('danger', 'Shipping line was not found.');
        }
    }
	
	public function delete(Request $request, $id)
    {
        $shippingLine = shippingLines::where('id', $id)->first();
        if ($shippingLine) {
			if ($shippingLine->delete()) {
				return redirect('/shippingLines')->with('success', 'Shipping line deleted successfully.');
			} else {
				return redirect('/shippingLines')->with('danger', 'Shipping line was not found.');
			}
        }
    }
}
