<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\packingSheet;
use App\invoice;
use App\product;
use App\invoiceTable;
use App\psTable;
use App\CreditNote;
use App\CreditNoteProduct;

class packingSheetController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth','2fa']);
    }

    public function create()
    {
        $invoice = invoice::get();
        return view('packingSheet/create', ['invoice' => $invoice]);
    }

    public function store(Request $request)
    {       
        $invoice = invoice::where('id', $request['invoice_id'])->first();
        $psExist = packingSheet::where('invoice_id', $request['invoice_id'])->count();

        if($psExist > 0 ){
            return redirect('/packingSheet/create')->with('danger', 'Packing Sheet already exists with this Invoice.');
        }

        else{

            $q = packingSheet::create([
                    'invoice_id'=>$request['invoice_id'],
                    'totalbox'=>$request['totalbox'],
                    'quantity'=>$request['quantity'],
                    'netwt'=>$request['weight'],
                    'grosswt'=>$request['grosswt']
                ]);        

              foreach ($request['ps'] as $ps) {
                    psTable::create([
                        'product_id' => $ps['product'],
                        'invoice_id' => $q->invoice_id,
                        'packingSheet_id' => $q->id,
                        'quantity' => $ps['quantity'],
                        'box' => $ps['box'],
                        'qtybox' => $ps['qtybox'],
                        'netwt' => $ps['weight'],
                        'grosswt' => $ps['grosswt'],
                    ]);

                    $product = product::where('id', $ps['product'])->first();
                    $product->quantity = $product->quantity - $ps['quantity'];
                    $product->save();
                }

            if($invoice->quantity == $request['quantity']){
                $invoice->status = '2';
                $invoice->save(); 
            }

            else{
                $invoice->status = '1';
                $invoice->save(); 
            }
                     
           return redirect('/packingSheet');

        }
    }

    public function update(Request $request, $id)
    {   
        $invoice = invoice::where('id', $id)->first();
        $psExist = packingSheet::where('invoice_id', $id)->first();
        $psProduct = psTable::where('invoice_id', $id)->get();

        if(isset($psExist)){

            foreach ($psProduct as $psProduct) {
                $product1 = product::where('id', $psProduct->product_id)->first();
                $product1->quantity = $product1->quantity + $psProduct->quantity;
                $product1->save();
                $psProduct->delete();
            }

            $psExist->quantity = $request['quantity'];
            $psExist->totalbox = $request['totalbox'];
            $psExist->netwt = $request['netwt'];
            $psExist->grosswt = $request['grosswt'];
            $psExist->save();

            foreach ($request['ps'] as $ps) {
                psTable::create([
                    'product_id' => $ps['product'],
                    'ean' => $ps['EAN'],
                    'invoice_id' => $psExist->invoice_id,
                    'packingSheet_id' => $psExist->id,
                    'quantity' => $ps['quantity'],
                    'box' => $ps['box'],
                    'qtybox' => $ps['qtybox'],
                    'netwt' => $ps['netwt'],
                    'grosswt' => $ps['grosswt'],
                ]);

                $product = product::where('id', $ps['product'])->first();
                $product->quantity = $product->quantity - $ps['quantity'];
                $product->save();
            }

            if($invoice->tquantity == $request['psQty']){
                $invoice->status = '2';
                $invoice->save(); 
            }

            else{
                $invoice->status = '1';
                $invoice->save(); 
            }
        }       
                 
       return redirect('/packingSheet');
    }

    public function index()
    {
        $packingSheet=packingSheet::get();
        return view('packingSheet/index', ['packingSheet' => $packingSheet]);
    }

    public function shutout()
    {
        $packingSheet=packingSheet::get();
        return view('report/shutOut', ['packingSheet' => $packingSheet]);
    }

    public function view($id)
    {
        $packingSheet = packingSheet::where('invoice_id',$id)->first();
        $psStatus = $packingSheet->invoice->status;
        $psTable = psTable::where('invoice_id',$id)->get();
        if ($packingSheet) {
            if($psStatus != 2){
                return view('packingSheet/view', ['packingSheet'=>$packingSheet], ['psTable'=>$psTable]);
            }

            else{
                 return redirect('/packingSheet')->with('danger', 'Invoice is marked complete. Can not edit the Packing Sheet.');
            }
            
        } else {
            return redirect('/packingSheet')->with('danger', 'Packing Sheet was not found.');
        }
    }

    public function data()
    {
        $invoice = invoice::all();
        $product = product::all();
        
        return response()->json(['invoice'=>$invoice, 'product' => $product]);
    }

    public function viewdata()
    {
        $product = product::all();        
        return response()->json(['product' => $product]);
    }

    public function psInvoiceTable($id)
    {
        $invoiceTable = invoiceTable::where('invoice_id',$id)->get();
        return response()->json(['invoiceTable'=>$invoiceTable]);
    }

    public function psInvoiceTableForCN($id)
    {
        $invoiceTable = invoiceTable::where('invoice_id',$id)->get();
        $invt_array = $invoiceTable->toArray();
        $cn = CreditNote::where('invoice_id',$id)->get();
        foreach ($invt_array as $rowIdx => $value) {
            $total_in_cn = 0.0;
            if ($cn->count()) {
                foreach ($cn as $cnRow) {
                    $cnp = CreditNoteProduct::where('credit_note_id', $cnRow->id)
                        ->where('product_id', $value['product_id'])
                        ->first();
                    if (isset($cnp->id)) {
                        $total_in_cn += (float) ($cnp->quantity ?? 0);
                    }
                }
            }

            $lineQty = (float) ($value['quantity'] ?? 0);
            $lineRem = (float) ($value['remqty'] ?? 0); // original invoice remqty
            // For credit note, "remaining" should mean returnable qty:
            // sold/stocked-out qty = quantity - remqty; then subtract already credited qty.
            $soldQty = max(0.0, $lineQty - $lineRem);
            $invt_array[$rowIdx]['remqty'] = max(0.0, $soldQty - $total_in_cn);
        }
        return response()->json(['invoiceTable'=>$invt_array]);
    }

    public function delete($id)
    {
        $psTable = psTable::where('packingSheet_id',$id)->get();
        if ($psTable) {
            foreach ($psTable as $key => $value) {
                $psTable[$key]->delete();
            }  
        }

        $packingSheet = packingSheet::find($id);
        if ($packingSheet) {
            if ($packingSheet->delete()) {
                return redirect('/packingSheet')->with('success', 'Packing Sheet deleted successfully.');
            } else {
                return redirect('/packingSheet')->with('danger', 'Packing Sheet was not found.');
            }
        }
    }
}
