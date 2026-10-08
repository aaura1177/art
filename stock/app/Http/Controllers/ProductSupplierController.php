<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\ProductSupplier;
use App\supplierProduct;
use App\supplier;
use App\product;
use App\Support\SupplierProductPriceLogWriter;
use Auth;
class ProductSupplierController extends Controller
{
    //


    public function index()
    {
       
        $products_id= supplierProduct::orderBy('id','desc')->take(100)->with('product','supplier')->get();
       
        return view('admin.supplier-prices.index', compact('products_id'));
    }

    // Store a new product-supplier ProductSupplier
    public function store(Request $request)
    {
        // Validate incoming request
        $request->validate([
            'product_id' => 'required',
            'supplier_id' => 'required',
            'price' => 'required|numeric|min:0',
        ]);
      

        // Create a new ProductSupplier
        ProductSupplier::create([
            'product_id' => $request->product_id,
            'supplier_id' => $request->supplier_id,
            'price' => $request->price,
        ]);
        
        return redirect()->route('ProductSuppliers.index')->with('success', 'ProductSupplier created successfully.');
    }

    // Show the edit form (not necessary if using modals)
    public function edit($id)
    {
        $ProductSupplier = ProductSupplier::findOrFail($id);
        $products = Product::all();
        $suppliers = Supplier::all();

        return view('ProductSuppliers.edit', compact('ProductSupplier', 'products', 'suppliers'));
    }

    // Update an existing product-supplier ProductSupplier
    public function update(Request $request, $id)
    {
        // Validate incoming request
        $request->validate([
            'product_id' => 'required',
            'supplier_id' => 'required',
            'price' => 'required|numeric|min:0',
        ]);

        // Find the ProductSupplier and update it
        $ProductSupplier = ProductSupplier::findOrFail($id);
        $ProductSupplier->update([
            'product_id' => $request->product_id,
            'supplier_id' => $request->supplier_id,
            'price' => $request->price,
        ]);

        return redirect()->route('ProductSuppliers.index')->with('success', 'ProductSupplier updated successfully.');
    }

    // Delete an existing product-supplier ProductSupplier
    public function destroy($id)
    {
        // Find the ProductSupplier and delete it
        $ProductSupplier = ProductSupplier::findOrFail($id);
        $ProductSupplier->delete();

        return redirect()->route('ProductSuppliers.index')->with('success', 'ProductSupplier deleted successfully.');
    }
    public function supplier_products(){
       $products_id= supplierProduct::with('product')->where('supplier_id',Auth::user()->supplier_id)->get();

       return view('supplierUser.assigned_products',compact('products_id'));
    }
   public function reviseprice(Request $request){
    $request->validate([
        'product_id' => 'required|exists:supplier_products,id',
        'new_price' => 'required|numeric|min:0',
        'effective_date' => 'required|date|after_or_equal:today',
    ]);

    
        $supplierProduct = supplierProduct::where('id',$request->input('product_id'))->where('supplier_id',Auth::user()->supplier_id)->first();
        
        $before = SupplierProductPriceLogWriter::buildState($supplierProduct);
        // Store the pending changes
        $supplierProduct->pending_rate = $request->input('new_price');
        $supplierProduct->effective_date = $request->input('effective_date');
        $supplierProduct->admin_approved = false; // Mark as not approved

        $supplierProduct->save();
        SupplierProductPriceLogWriter::log($supplierProduct, 'revise', 'revise', $before);

        return redirect()->back()->with('success', 'Price revision submitted for approval.');
    
   }
   public function approvePrice(Request $request){
 
    $supplierProduct = supplierProduct::where('id',$request->product_id)->where('supplier_id',$request->supplier_id)->first();
    $before = SupplierProductPriceLogWriter::buildState($supplierProduct);
    $supplierProduct->rate = $supplierProduct->pending_rate;
    $supplierProduct->pending_rate = null;
    $supplierProduct->admin_approved = true; // Mark as not approved
    $supplierProduct->save();
    SupplierProductPriceLogWriter::log($supplierProduct, 'approve', 'approve', $before);
    return response()->json([
        'status' => 'success',
        'message' => 'Product price approved successfully.',
    ]);
      

   }
}
