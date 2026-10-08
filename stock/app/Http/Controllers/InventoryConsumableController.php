<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\packaging;
use App\consumable;
use App\stockLogConsumable;
use App\StockLogCarton;


class InventoryConsumableController extends Controller
{
    public function index()
    {
        $latestLogSubquery = DB::table('stock_log_consumable')
            ->select('consumable_id', DB::raw('MAX(created_at) as last_log_at'))
            ->groupBy('consumable_id');

        $consumables = consumable::leftJoinSub($latestLogSubquery, 'latest_logs', function ($join) {
                $join->on('latest_logs.consumable_id', '=', 'consumables.id');
            })
            ->select('consumables.*')
            ->orderByRaw('latest_logs.last_log_at IS NULL ASC')
            ->orderBy('latest_logs.last_log_at', 'desc')
            ->orderBy('consumables.created_at', 'desc')
            ->get();
        return view('consumable_inventory.index', compact('consumables'));
    }
    public function carton()
    {
        $consumables = packaging::with('product')->orderBy('created_at', 'desc')->get();
        return view('consumable_inventory.cartonindex', compact('consumables'));
    }

     public function detail_report(Request $request, $id)
    {
        
         $products = consumable::where('id', $id)->first();

    // Base query for stock logs
   $logsQuery = stockLogConsumable::where('type', '<', '3')->where('consumable_id', $id);

    // Apply filters (date range, supplier, etc.)
    if ($request->has('from') && $request->from != '') {
        $logsQuery->whereDate('created_at', '>=', $request->from);
    }

    if ($request->has('to') && $request->to != '') {
        $logsQuery->whereDate('created_at', '<=', $request->to);
    }

    if(empty($request->type)){
        $logsQuery->where('type', '<', '3');
       }else{
        $logsQuery->where('type', $request->type);
       }

    // Get filtered logs
    $logs = $logsQuery->orderBy('created_at', 'desc')
    ->orderBy('id', 'desc')->get();

    // Calculate the sum of received stock (type 1) and out stock (type 2)
    $receivedStock = $logsQuery->where('type', 1)->sum('quantity');
    $outStock = $logsQuery->where('type', 2)->sum('quantity');

    // Remaining stock calculation (can be the last remaining stock from the logs or custom logic)
    $lastLog = $logs->first();
    $remainingStock = $lastLog ? $lastLog->remaining_stock : 0;
       
       
      
         
        // Get the filtered logs
     
        // Get suppliers to populate supplier dropdown
       
    
        return view('consumable_inventory.detail_report', [
            'logs' => $logs,
            'products' => $products, 
            'receivedStock' => $receivedStock,
            'outStock' => $outStock,
            'remainingStock' => $remainingStock
            
        ]);
    }

       public function carton_detail_report(Request $request, $id)
    {
         
           $products = packaging::where('id', $id)->first();

    // Base query for stock logs
   $logsQuery = StockLogCarton::where('type', '<', '3')->where('product_id', $products->product_id);

    // Apply filters (date range, supplier, etc.)
    if ($request->has('from') && $request->from != '') {
        $logsQuery->whereDate('created_at', '>=', $request->from);
    }

    if ($request->has('to') && $request->to != '') {
        $logsQuery->whereDate('created_at', '<=', $request->to);
    }

    if(empty($request->type)){
        $logsQuery->where('type', '<', '3');
       }else{
        $logsQuery->where('type', $request->type);
       }

    // Get filtered logs
    $logs = $logsQuery->orderBy('created_at', 'desc')
    ->orderBy('id', 'desc')->get();

    // Calculate the sum of received stock (type 1) and out stock (type 2)
    $receivedStock = $logsQuery->where('type', 1)->sum('quantity');
    $receivedStock2 = $logsQuery->where('type', 1)->sum('quantity2');
    $outStock = $logsQuery->where('type', 2)->sum('quantity');
    $outStock2 = $logsQuery->where('type', 2)->sum('quantity2');

    // Remaining stock calculation (can be the last remaining stock from the logs or custom logic)
    $lastLog = $logs->first();
    $remainingStock = $lastLog ? $lastLog->remaining_stock : 0;
    $remainingStock2 = $lastLog ? $lastLog->remaining_stock2 : 0;
       
       
      
         
        // Get the filtered logs
     
        // Get suppliers to populate supplier dropdown
    //    return $remainingStock;
    
        return view('consumable_inventory.carton_detail_report', [
            'logs' => $logs,
            'products' => $products, 
            'receivedStock' => $receivedStock,
            'receivedStock2' => $receivedStock2,
            'outStock' => $outStock,
            'outStock2' => $outStock2,
            'remainingStock' => $remainingStock,
            'remainingStock2' => $remainingStock2
            
        ]);
    }
}
