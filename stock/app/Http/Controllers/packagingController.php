<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\packaging;
use App\employee;
use App\invoice;
use App\StockLogCarton;
use App\product;
use App\Imports\PackagingImport;
use App\Imports\PackagingQuantityImport;
use App\Exports\PackagingCartonsExport;
use App\Exports\PackagingQuantityTemplateExport;
use Maatwebsite\Excel\Facades\Excel;

class packagingController extends Controller
{
    public function index(Request $request)
    {
    	$last = packaging::orderBy('created_at','desc')->first();
		$products = product::where('created_at','>',$last->created_at)->get();
		foreach($products as $product){
			packaging::create([
				'product_id'=>$product->id,
				'box1_height'=>0,
				'box1_width'=>0,
				'box1_depth'=>0,
				'box2_height'=>0,
				'box2_width'=>0,
				'box2_depth'=>0,
				'box1_sqinch'=>0,
				'box2_sqinch'=>0,
				'no_of_boxes'=>0,
				'box1_ply'=>0,
				'box2_ply'=>0,
			]);
		}
        $code = trim((string) $request->get('code', ''));
        $allowedPerPage = [10, 20, 50, 100];
        $perPage = (int) $request->get('per_page', 50);
        if (!in_array($perPage, $allowedPerPage, true)) {
            $perPage = 50;
        }
        $packagingQuery = packaging::with('product')->orderBy('id', 'desc');
        if ($code !== '') {
            $packagingQuery->whereHas('product', function ($query) use ($code) {
                $query->where('code', 'like', '%' . $code . '%');
            });
        }
		$packaging = $packagingQuery->paginate($perPage)->appends($request->query());
		return view('packaging/index', ['packaging'=>$packaging]);
    }
	
	public function importCSV(Request $request)
    {
        $file = $request->file('importCSV');
        Excel::import(new PackagingImport, $file);
        return redirect('/packaging')->with('success', 'Packaging Excel was updated successfully.');
    }

    public function importQuantityCSV(Request $request)
    {
        $request->validate([
            'importQuantityCSV' => 'required|mimes:xlsx,xls,csv',
        ]);

        Excel::import(new PackagingQuantityImport, $request->file('importQuantityCSV'));

        return redirect('/packaging')->with('success', 'Quantities updated successfully.');
    }

    public function exportExcel()
    {
        $filename = 'packaging_cartons_' . date('Y-m-d_His') . '.xlsx';

        return Excel::download(new PackagingCartonsExport, $filename);
    }

    public function downloadQuantityTemplate()
    {
        return Excel::download(new PackagingQuantityTemplateExport, 'packaging_quantity_template.xlsx');
    }

    public function create()
    {
		$employees	=	employee::all();
		$invoice	=	invoice::all();
        return view('packaging/create', ['employees'=>$employees,'invoice'=>$invoice]);
    }

    public function store(Request $request)
    {
			$q = packaging::create([
                    'employee_id'=>$request['employee_id'],  
					'type'=>$request['type'],
					'quantity'=>$request['quantity'],
					'invoices'=>implode(',',$request['invoices']),
                ]);
			
           return redirect('/packaging')->with('success', 'Packaging was added successfully.');
    }

    public function view($id)
    {
        $packaging = packaging::find($id);
		$invoice	=	invoice::all();
		$employees	=	employee::all();
        return view('/packaging/view', ['packaging'=>$packaging,'invoice'=>$invoice,'employees'=>$employees]);
    }

    public function update(Request $request, $id)
    {
        $packaging = packaging::find($id);
        if ($packaging) {
			
            $packaging->employee_id = $request['employee_id'];
            $packaging->type = $request['type'];
            $packaging->quantity = $request['quantity'];
            $packaging->invoices = implode(',',$request['invoices']);
            
			
            if ($packaging->save()) {
                return redirect('packaging')->with('success', 'Packaging was updated successfully.');
            } else {
                return redirect('packaging')->with('danger', 'Error occurred while saving packaging.');
            }
        } else {
            return redirect('/packaging')->with('danger', 'Packaging was not found.');
        }
    }
	
		public function updatePackaging(Request $request)
{
    $packaging = packaging::where('id', $request['id'])->first();

    if (!$packaging) {
        return response()->json(['error' => 'Packaging not found'], 404);
    }

    $box1_sqinch = 0;
    $box2_sqinch = 0;

    if ($request['box1_type'] == "Standard Box") {
        $box1_sqinch = $request['box1_width'] + $request['box1_depth'];
        $box1_sqinch = ceil($box1_sqinch);
        if ($box1_sqinch % 2 != 0) {
            $box1_sqinch += 1;
        }
        $box1_sqinch = $box1_sqinch * ($request['box1_height'] + $request['box1_width'] + 2);
        $box1_sqinch = ($box1_sqinch * 2) / 100;
        $box1_sqinch = round($box1_sqinch, 2);
    }

    if ($request['box1_type'] == "Over Flap") {
        $box1_sqinch = $request['box1_width'] + $request['box1_width'] + $request['box1_depth'];
        $box1_sqinch = ceil($box1_sqinch);
        if ($box1_sqinch % 2 != 0) {
            $box1_sqinch += 1;
        }
        $box1_sqinch = $box1_sqinch * ($request['box1_height'] + $request['box1_width'] + 2);
        $box1_sqinch = ($box1_sqinch * 2) / 100;
        $box1_sqinch = round($box1_sqinch, 2);
    }

    if ($request['box1_type'] == "Lateral Box") {
        $box1_sqinch = $request['box1_height'] + $request['box1_width'];
        $box1_sqinch = ceil($box1_sqinch);
        if ($box1_sqinch % 2 != 0) {
            $box1_sqinch += 1;
        }
        $box1_sqinch = $box1_sqinch * ($request['box1_width'] + $request['box1_depth'] + 1);
        $box1_sqinch = ($box1_sqinch * 2) / 100;
        $box1_sqinch = round($box1_sqinch, 2);
    }

    // ✅ Box 2 Calculation (if 2 boxes)
    if ($request['no_of_boxes'] == 2) {
        if ($request['box2_type'] == "Standard Box") {
            $box2_sqinch = $request['box2_width'] + $request['box2_depth'];
            $box2_sqinch = ceil($box2_sqinch);
            if ($box2_sqinch % 2 != 0) {
                $box2_sqinch += 1;
            }
            $box2_sqinch = $box2_sqinch * ($request['box2_height'] + $request['box2_width'] + 2);
            $box2_sqinch = ($box2_sqinch * 2) / 100;
            $box2_sqinch = round($box2_sqinch, 2);
        }

        if ($request['box2_type'] == "Over Flap") {
            $box2_sqinch = $request['box2_width'] + $request['box2_width'] + $request['box2_depth'];
            $box2_sqinch = ceil($box2_sqinch);
            if ($box2_sqinch % 2 != 0) {
                $box2_sqinch += 1;
            }
            $box2_sqinch = $box2_sqinch * ($request['box2_height'] + $request['box2_width'] + 2);
            $box2_sqinch = ($box2_sqinch * 2) / 100;
            $box2_sqinch = round($box2_sqinch, 2);
        }

        if ($request['box2_type'] == "Lateral Box") {
            $box2_sqinch = $request['box2_height'] + $request['box2_width'];
            $box2_sqinch = ceil($box2_sqinch);
            if ($box2_sqinch % 2 != 0) {
                $box2_sqinch += 1;
            }
            $box2_sqinch = $box2_sqinch * ($request['box2_width'] + $request['box2_depth'] + 1);
            $box2_sqinch = ($box2_sqinch * 2) / 100;
            $box2_sqinch = round($box2_sqinch, 2);
        }
    }

    // ✅ Update Packaging
    $packaging->no_of_boxes = $request['no_of_boxes'];
    $packaging->box1_height = $request['box1_height'];
    $packaging->box1_width  = $request['box1_width'];
    $packaging->box1_depth  = $request['box1_depth'];
    $packaging->box1_sqinch = $box1_sqinch;
    $packaging->box1_ply    = $request['box1_ply'];

    $packaging->box2_height = $request['box2_height'];
    $packaging->box2_width  = $request['box2_width'];
    $packaging->box2_depth  = $request['box2_depth'];
    $packaging->box2_sqinch = $box2_sqinch;
    $packaging->box2_ply    = $request['box2_ply'];

    $packaging->box1_type   = $request['box1_type'];
    $packaging->box2_type   = $request['box2_type'];

    

    $packaging->save();

    return response()->json([
        'box1_sqinch' => $box1_sqinch,
        'box2_sqinch' => $box2_sqinch,
    ]);
}

public function updateqty(Request $request){
    $packaging = Packaging::find($request->packaging_id);
    $box2 = $packaging->box_2_qty;
    $old_box2 = $packaging->box_2_qty;

    switch($request->type){
        case 'box1_plus':
            $opening_bal = $packaging->box_1_qty;
            $remaining_stock1 = $opening_bal + $request->qty;

            StockLogCarton::create([
                'product_id' => $packaging->product_id,
                'voucher_no' => 'manual',
                'ref_no' => 'Stock IN Manual Packaging',
                'quantity' => $request->qty,
                'quantity2' => 0,
                'opening_balance' => $opening_bal,
                'opening_balance2' => $old_box2,
                'remaining_stock' => $remaining_stock1,
                'remaining_stock2' => $box2,
                'type' => 1,
                'supplier_inv_no' => null,
            ]);

            $packaging->box_1_qty = $remaining_stock1;
            $packaging->save();
            break;

        case 'box1_minus':
            $opening_bal = $packaging->box_1_qty;
            $remaining_stock1 = max(0, $opening_bal - $request->qty);

            StockLogCarton::create([
                'product_id' => $packaging->product_id,
                'voucher_no' => 'manual',
                'ref_no' => 'Stock OUT Manual Packaging',
                'quantity' => $request->qty,
                'quantity2' => 0,
                'opening_balance' => $opening_bal,
                'opening_balance2' => $old_box2,
                'remaining_stock' => $remaining_stock1,
                'remaining_stock2' => $box2,
                'type' => 2,
                'supplier_inv_no' => null,
            ]);

            $packaging->box_1_qty = $remaining_stock1;
            $packaging->save();
            break;

        case 'box2_plus':
            $opening_bal = $packaging->box_2_qty;
            $remaining_stock2 = $opening_bal + $request->qty;

            StockLogCarton::create([
                'product_id' => $packaging->product_id,
                'voucher_no' => 'manual',
                'ref_no' => 'Stock IN Manual Packaging',
                'quantity' => 0,
                'quantity2' => $request->qty,
                'opening_balance' => $packaging->box_1_qty,
                'opening_balance2' => $opening_bal,
                'remaining_stock' => $packaging->box_1_qty,
                'remaining_stock2' => $remaining_stock2,
                'type' => 1,
                'supplier_inv_no' => null,
            ]);

            $packaging->box_2_qty = $remaining_stock2;
            $packaging->save();
            break;

        case 'box2_minus':
            $opening_bal = $packaging->box_2_qty;
            $remaining_stock2 = max(0, $opening_bal - $request->qty);

            StockLogCarton::create([
                'product_id' => $packaging->product_id,
                'voucher_no' => 'manual',
                'ref_no' => 'Stock OUT Manual Packaging',
                'quantity' => 0,
                'quantity2' => $request->qty,
                'opening_balance' => $packaging->box_1_qty,
                'opening_balance2' => $opening_bal,
                'remaining_stock' => $packaging->box_1_qty,
                'remaining_stock2' => $remaining_stock2,
                'type' => 2,
                'supplier_inv_no' => null,
            ]);

            $packaging->box_2_qty = $remaining_stock2;
            $packaging->save();
            break;
    }

    return redirect()->back()->with('success','Quantity updated successfully!');
}
	
	public function updateAllPackaging(){
		
		$packagings = packaging::all();
		foreach($packagings as $packaging){
			if(isset($packaging->id)){
				$box2_sqinch = 0;
				$box1_sqinch = $packaging->box1_width + $packaging->box1_depth;
				$box1_sqinch = ceil($box1_sqinch);
				if(($box1_sqinch % 2) != 0){
					$box1_sqinch = $box1_sqinch + 1;
				}
				$box1_sqinch = $box1_sqinch * ($packaging->box1_height + $packaging->box1_width +2);
				$box1_sqinch = ($box1_sqinch * 2)/100;
				$box1_sqinch = round($box1_sqinch,2);
				if($packaging->no_of_boxes == 2){
					$box2_sqinch = $packaging->box2_width + $packaging->box2_depth;
					$box2_sqinch = ceil($box2_sqinch);
					if(($box2_sqinch % 2) != 0){
						$box2_sqinch = $box2_sqinch + 1;
					}
					$box2_sqinch = $box2_sqinch * ($packaging->box2_height + $packaging->box2_width +2);
					$box2_sqinch = ($box2_sqinch * 2)/100;
					$box2_sqinch = round($box2_sqinch,2);
				}
				
				$packaging->box1_sqinch 		= $box1_sqinch;
				$packaging->box2_sqinch 		= $box2_sqinch;
				$packaging->save();
			}
		}
		die;
	}

	public function delete(Request $request, $id)
    {
        $packaging = packaging::where('id', $id)->first();
        if ($packaging) {
			if ($packaging->delete()) {
				return redirect('/packaging')->with('success', 'Packaging deleted successfully.');
			} else {
				return redirect('/packaging')->with('danger', 'Packaging was not found.');
			}
        }
    }
}
