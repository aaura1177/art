<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use App\invoice;
use App\SustainabilityStage4;
use Illuminate\Http\Request;
use App\SustainabilityVariable;
use Illuminate\Support\Facades\Validator;
use App\Exports\SustainabilityStage4Export;
use App\Support\SustainabilityStage4Postloading;
use Maatwebsite\Excel\Facades\Excel;

class SustainabilityStage4Controller extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $sustainabilityVariable = SustainabilityVariable::first();
        $date_from = $request->input('date-from');
        $date_to = $request->input('date-to');

        $records = SustainabilityStage4::when($date_from && $date_to, function ($query) use ($date_from, $date_to) {
            $query->whereBetween('month_year', [$date_from, $date_to]);
        })
            ->orderByDesc('month_year')
            ->paginate(12);

        return view("Sustainability.stage4.index", compact("records", "sustainabilityVariable"));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        // Return the create view for SustainabilityStage4
        $sustainabilityVariable = SustainabilityVariable::first();
        return view("Sustainability.stage4.create", compact("sustainabilityVariable"));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'number_of_containers' => 'required|numeric',
            'carbon_emission' => 'required|numeric',
            'month_year' => 'required|date',
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        try {

            $request->merge(['month_year' => $request->month_year . '-01']);

            // Check if the sustainability variable record exists
            $sustainabilityVariable = SustainabilityVariable::first();
            if (!$sustainabilityVariable) {
                return redirect()->back()->with('error', 'Sustainability variable not found. Please set it up first.');
            }
            // Calculate carbon emission based on number of containers and sustainability variable
            $carbonEmission = $request->number_of_containers * $sustainabilityVariable->mundra_port_distance * $sustainabilityVariable->carbon_emission_rate;
            $request->merge(['carbon_emission' => $carbonEmission]);

            // Store the record
            SustainabilityStage4::create($request->all());
            return redirect()->route('sustainability.stage4.index')->with('success', 'Record created successfully.');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Error creating record: ' . $e->getMessage());
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(SustainabilityStage4 $sustainabilityStage4)
    {
        // Show the details of the specified record
        return view("Sustainability.stage4.show", compact("sustainabilityStage4"));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(SustainabilityStage4 $sustainabilityStage4)
    {
        $sustainabilityVariable = SustainabilityVariable::first();
        $record = $sustainabilityStage4;
        // Return the edit view for a specific SustainabilityStage4 record
        return view("Sustainability.stage4.edit", compact('record', 'sustainabilityVariable'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, SustainabilityStage4 $sustainabilityStage4)
    {
        $validator = Validator::make($request->all(), [
            'number_of_containers' => 'required|numeric',
            'carbon_emission' => 'required|numeric',
            'month_year' => 'required|date',
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        try {

            $request->merge(['month_year' => $request->month_year . '-01']);

            // Check if the sustainability variable record exists
            $sustainabilityVariable = SustainabilityVariable::first();
            if (!$sustainabilityVariable) {
                return redirect()->back()->with('error', 'Sustainability variable not found. Please set it up first.');
            }
            // Calculate carbon emission based on number of containers and sustainability variable
            $carbonEmission = $request->number_of_containers * $sustainabilityVariable->mundra_port_distance * $sustainabilityVariable->carbon_emission_rate;
            $request->merge(['carbon_emission' => $carbonEmission]);

            // Update the record
            $sustainabilityStage4->update($request->all());
            return redirect()->route('sustainability.stage4.index')->with('success', 'Record updated successfully.');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Error updating record: ' . $e->getMessage());
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(SustainabilityStage4 $sustainabilityStage4)
    {
        try {
            $sustainabilityStage4->delete();
            return redirect()->route('sustainability.stage4.index')->with('success', 'Record deleted successfully.');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Error deleting record: ' . $e->getMessage());
        }
    }

    /**
     * Export Stage 4 Record
     */
    public function export(Request $request)
    {
        $search = $request->input('search') ?? "";
        $date_from = $request->input('date-from') ?? "";
        $date_to = $request->input('date-to') ?? "";

        $from = $date_from ? date('Ymd', strtotime($date_from)) : 'start';
        $to = $date_to ? date('Ymd', strtotime($date_to)) : 'end';
        $filename = "stage4-report-{$from}-to-{$to}.xlsx";

        return Excel::download(new SustainabilityStage4Export($search, $date_from, $date_to), $filename);
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
                'In'         => ['invoice' => invoice::class, 'country' => 'India'],
             ];
 
             $result = [];
 
             foreach ($siteModelMap as $models) {
                 $invoiceModel = $models['invoice'];
 
                 $invoices = SustainabilityStage4Postloading::constrain(
                        $invoiceModel::whereBetween('date', [$startOfMonth, $endOfMonth])
                            ->whereNotNull('date')
                            ->where('invoiceno', 'like', 'GVD/UK/%')
                            ->where('invoiceno', 'not like', '%TEMP%') // exclude TEMP invoices
                            ->whereRaw('(containerno IS NULL OR UPPER(TRIM(containerno)) != ?)', ['TBC']) // exclude TBC placeholders from modal list
                            ->where('is_canceled', 0)
                    )
                    ->orderBy('date', 'Asc')
                    ->get();
 
                 foreach ($invoices as $inv) 
                 { 
                    $result[] = [
                         'country'     =>  $models['country'],
                         'invoiceno'   => $inv->invoiceno,
                         'containerno' => $inv->containerno,
                         'discharge'   => $inv->discharge,
                         'postloading' => $inv->postloading,
                         'destination' => $inv->destination,
                         'date'        => $inv->date,
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
