<?php

namespace App\Http\Controllers;

use App\SustainabilityStage0;
use Illuminate\Http\Request;
use App\Exports\SustainabilityStage0Export;
use Maatwebsite\Excel\Facades\Excel;
use App\invoice;
use App\invoiceus;
use App\invoiceuk;
use App\invoicecanada;
use App\invoicecalifornia;
use App\invoiceeu;
use App\SustainabilityStage5;
use App\EmissionInvoiceLogIn;
use App\EmissionInvoiceLogUs;
use App\EmissionInvoiceLogUk;
use App\EmissionInvoiceLogEu;
use App\EmissionInvoiceLogCa;
use App\EmissionInvoiceLogCalifornia;
use Carbon\Carbon;
class SustainabilityStage0Controller extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $search = $request->input('search');
        $date_from = $request->input('date-from') ; // ?? date('Y-04-01');
        $date_to = $request->input('date-to') ; //?? date('Y-03-31', strtotime('+1 year'));

        $records = SustainabilityStage0::when($date_from && $date_to, function ($query) use ($date_from, $date_to) {
            $query->whereBetween('month_year', [$date_from, $date_to]);
        })->orderBy('created_at', 'desc')
        ->paginate(12);
        
        return view("Sustainability.stage0.index", compact("records"));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view("Sustainability.stage0.create");
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $this->validate($request, [ 
            'total_qty_delivered_by_containers' => 'required|numeric',
            'total_weight_delivered_in_containers' => 'required|numeric',
            'carbon_sequestration' => 'required|numeric',
            'month_year' => 'required|date',
        ]);
        try {
            // Check if record is entered this month
            $existingRecord = SustainabilityStage0::whereMonth('month_year', date('m'))
                ->whereYear('month_year', date('Y'))
                ->first();
            if ($existingRecord) {
               // update if already exists
                $existingRecord->update($request->only([
                    'total_qty_delivered_by_containers',
                    'total_weight_delivered_in_containers',
                    'carbon_sequestration',
                    'month_year',
                ]));
                \Log::info('Sustainability Stage0 updated successfully.');
                return response()->json([
                    'status' => 'success',
                    'message' => 'Sustainability Stage0 data updated successfully.',
                ]);
            }
            // Create a new record if it doesn't exist
            SustainabilityStage0::Create(
                $request->only([
                    'total_qty_delivered_by_containers',
                    'total_weight_delivered_in_containers',
                    'carbon_sequestration',
                    'month_year',
                ])
            );

            \Log::info('Sustainability Stage0 updated successfully.');
            return response()->json([
                'status' => 'success',
                'message' => 'Sustainability Stage0 data saved successfully.',
            ]);

        } catch (\Exception $e) {
            \Log::info( 'Failed to save sustainability Stage0 data ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to save sustainability Stage0 data ' . $e->getMessage(),
            ]);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(SustainabilityStage0 $sustainabilityStage0)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(SustainabilityStage0 $sustainabilityStage0)
    {
        return view("Sustainability.stage0.edit", compact('sustainabilityStage0'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, SustainabilityStage0 $sustainabilityStage0)
    {
        try {
            $sustainabilityStage0->update($request->only([
                'total_qty_delivered_by_containers',
                'total_weight_delivered_in_containers',
                'carbon_sequestration',
                'month_year',
            ]));
            \Log::info('Sustainability Stage0 updated successfully.');
            return response()->json([
                'status' => 'success',
                'message' => 'Sustainability Stage0 data updated successfully.',
            ]);
        } catch (\Exception $e) {
            \Log::info( 'Failed to update sustainability Stage0 data ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to update sustainability Stage0 data ' . $e->getMessage(),
            ]);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(SustainabilityStage0 $sustainabilityStage0)
    {
        try {
            $sustainabilityStage0->delete();
            \Log::info('Sustainability Stage0 deleted successfully.');
            return response()->json([
                'status' => 'success',
                'message' => 'Sustainability Stage0 data deleted successfully.',
            ]);
        } catch (\Exception $e) {
            \Log::info( 'Failed to delete sustainability Stage0 data ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to delete sustainability Stage0 data ' . $e->getMessage(),
            ]);
        }
    }

    /**
     * Export Report
     */
    public function export(Request $request)
    {
        $search = $request->input('search') ?? "";
        $date_from = $request->input('date-from') ?? "";
        $date_to = $request->input('date-to') ?? "";

        return Excel::download(new SustainabilityStage0Export($search,$date_from,$date_to), 'stage0.xlsx');
    }

    /**
     * Get Container Details
     */

    public function containerDetails(Request $request)
    {
        try {
            $monthYear = $request->date; // Expecting 'YYYY-MM' format from JS
            $startOfMonth = Carbon::parse($monthYear . '-01')->startOfMonth()->toDateString();
            $endOfMonth = Carbon::parse($monthYear . '-01')->endOfMonth()->toDateString();

            $siteModelMap = [
                'Us'         => ['invoice' => invoiceus::class , 'country' => 'US'],
                'Uk'         => ['invoice' => invoiceuk::class, 'country' => 'UK'],
                'Eu'         => ['invoice' => invoiceeu::class, 'country' => 'EU'],
                'Ca'         => ['invoice' => invoicecanada::class, 'country' => 'CA'],
                'California' => ['invoice' => invoicecalifornia::class, 'country' => 'California'],
                'In'         => ['invoice' => invoice::class, 'country' => 'India'],
            ];

            $result = [];

            foreach ($siteModelMap as $models) {
                $invoiceModel = $models['invoice'];

                $invoices = $invoiceModel::with('portInfo')
                    ->whereBetween('date', [$startOfMonth, $endOfMonth])
                    ->whereNotNull('date')
                    ->whereHas('portInfo', function ($query) {
                        $query->whereNotNull('name');
                    })
                    ->where('invoiceno', 'like', 'GVD/UK/%')
                    ->orderBy('date', 'Asc')
                    ->get();

                foreach ($invoices as $inv) {
                    
                    $port = $inv->portInfo;
                    if (!$port) {
                        continue;
                    }

                    $result[] = [
                        'country'   =>  $models['country'],
                        'invoiceno'   => $inv->invoiceno,
                        'containerno'   => $inv->containerno,
                        'discharge'     => optional($inv->portInfo)->name,
                        'totalquantity' => $inv->totalquantity,
                        'totalwt'       => $inv->totalwt,
                        'totalgrosswt'  => $inv->totalgrosswt,
                        'date'          => $inv->date,
                    ];
                }
            }

            return response()->json([
                'status' => 1,
                'data' => $result,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 0,
                'message' => 'Failed to get data: ' . $e->getMessage(),
            ]);
        }
    }

}
