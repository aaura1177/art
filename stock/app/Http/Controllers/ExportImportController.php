<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Exports\DataExport;
use App\Exports\FurnitureExport;
use App\Exports\SampleExport;
use App\Exports\ConsumablesExport;
use App\Imports\DataImport;
use App\Imports\PurchaseLedgerImport;
use App\product;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;




class ExportImportController extends Controller
{


    public  function exportimport()
    {
        return view('product.export_import');
    }



    public  function export(Request $request)
    {

        return Excel::download(new DataExport, 'data.xlsx');
    }

    public  function consumablesexport(Request $request)
    {

        return Excel::download(new ConsumablesExport, 'consumables.xlsx');
    }

    public  function sampleexport(Request $request)
    {

        return Excel::download(new SampleExport, 'samples.xlsx');
    }



    public  function furnitureexport(Request $request)
    {

        return Excel::download(new FurnitureExport, 'furniture.xlsx');
    }


    public  function imports(Request $request)
    {


        $request->validate([
            'file' => 'required|mimes:xlsx,xls,csv',
        ]);

        $data = Excel::toCollection(null, $request->file('file'));

        foreach ($data[0] as $key => $row) {

            if ($key === 0) continue;

            $updatedProducts = [];

            $product = Product::where('code', $row[0])->first();
            if ($product) {
                $product->update(['right_ean' => $row[4]]);

                $updatedProducts[] = [
                    'code' => $row[0],
                    'old_right_ean' => $product->EAN,
                    'new_right_ean' => $row[4],
                ];
            } else {
                $updatedProducts[] = [
                    'code' => $row[0],
                    'error' => 'Product not found',
                ];
            }
            if (!empty($updatedProducts)) {
                $logData = json_encode($updatedProducts, JSON_PRETTY_PRINT);
                Storage::put('logs/import_log.json', $logData);
            }
        }

        Excel::import(new DataImport, $request->file('file'));
        return back()->with('success', 'Products imported successfully.');
    }

    public function import(Request $request)
    {
        $request->validate([
            'file' => 'required|mimes:xlsx,xls,csv',
        ]);

        $data = Excel::toCollection(null, $request->file('file'));

        $updatedProducts = [];

        foreach ($data[0] as $key => $row) {
            if ($key === 0) continue;

            $product = Product::where('code', $row[0])->first();

            if ($product) {
                $oldEan = $product->EAN;
                $product->update(['EAN' => $row[4]]);
                $updatedProducts[] = [
                    'id' => $product->id,
                    'Name' => $product->name,
                    'code' => $row[0],
                    'old_right_ean' => $oldEan,
                    'new_right_ean' => $row[4],
                ];
            } else {
                $updatedProducts[] = [
                    'code' => $row[0],
                    'error' => 'Product not found',
                ];
            }
        }


        if ($updatedProducts) {
            $logData = json_encode($updatedProducts, JSON_PRETTY_PRINT);

            Log::info($logData);
        }



        return back()->with('success', 'Products updated and log created successfully.');
    }

    public  function purchaseledger(Request $request)
    {

        $request->validate([
            'file' => 'required|mimes:xlsx,xls,csv',
        ]);
    
        Excel::import(new PurchaseLedgerImport, $request->file('file'));
    
        return back()->with('success', 'Products Purchase Ledger imported successfully.');
    }
}
