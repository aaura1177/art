<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\invoice;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Collection;

class SustainController extends Controller
{
    //
    public function stagezero(){
        $monthlyData = Invoice::select('created_at', 'totalquantity', 'totalwt')
    ->get()
    ->groupBy(function ($date) {
        return \Carbon\Carbon::parse($date->created_at)->format('Y-m'); // Group by year-month
    });
    $monthlySums = $monthlyData->map(function ($group, $key) {
        $totalQuantity = $group->sum('totalquantity');
        $totalWeight = $group->sum('totalwt');
        
        $formattedMonth = \Carbon\Carbon::parse($key . '-01')->format('F-Y'); // Format as May-2024
        
        return [
            'month' => $formattedMonth,
            'total_quantity' => $totalQuantity,
            'total_weight' => $totalWeight,
            'carbon_sub'   =>round($totalWeight*0.5*3.67,2)
        ];
    })->sortByDesc(function($item) {
        return \Carbon\Carbon::parse($item['month']);
    });;
    
    // Convert to an array or continue working with a collection
    $monthlySumsArray = $monthlySums->values()->toArray();
    
    return view('sustanibility.stagezero', ['monthlySums' => $monthlySums]);
       }
}
