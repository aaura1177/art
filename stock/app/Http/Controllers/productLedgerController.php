<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
// use App\category;
// use App\subCategory;
use App\ProductLedger;
use App\Product;
use \auth;

class productLedgerController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth','2fa']);
    }

    
public function dataget() {
echo "hello";}
    public function create()
    {
        return view('product_ledger/create');
    }

    public function store(Request $request)
    {
    
		$request->validate
        ([
            // 'name'=>$request['name1']
            'name' => 'unique:product_ledgers',
        ]);

        if (!isset($errors))
        {
            ProductLedger::create
            ([
                'name'=>$request['name']
            ]);
        };

		// return redirect('/category');
        if (!isset($errors)) 
        {
            return redirect('/product_ledger')->with('success', 'Product Ledger was added successfully.');
        }

        else
        {
            return redirect('/product_ledger')->with('danger', 'Error occurred while adding Product Ledger.');
        }
         
    }

    public function index()
    {
        $ProductLedger=ProductLedger::orderBy('name', 'ASC')->get();
        return view('product_ledger/index',['ProductLedger'=>$ProductLedger] );
    }

    public function view($id)
    {

        $ProductLedger = ProductLedger::find($id);
        if ($ProductLedger) {
            return view('product_ledger/view', ['ProductLedger'=>$ProductLedger]);
        } else {
            return redirect('/product_ledger')->with('danger', 'Product Ledger was not found.');
        }
    }

    public function update(Request $request, $id)
    {   
        $ProductLedger = ProductLedger::find($id);
        if ($ProductLedger) {
            $ProductLedger->name = $request['name'];

            if ($ProductLedger->save()) {
                return redirect('product_ledger')->with('success', 'Product Ledger was updated successfully.');
            } else {
                return redirect('product_ledger')->with('danger', 'Error occurred while saving ProductLedger.');
            }
        } else {
            return redirect('/product_ledger')->with('danger', 'ProductLedger was not found.');
        }
    }

    public function delete(Request $request, $id)
    {
        $category = category::where('id', $id)->first();

        if($category->product->count() == 0)
        {
            $category->delete();
            return redirect('/category')->with('success', 'Product Ledger deleted successfully.'); 
        }
        else {
                return redirect('/Product Ledger')->with('danger', 'Product Ledger was not deleted.');
            }
    }

}