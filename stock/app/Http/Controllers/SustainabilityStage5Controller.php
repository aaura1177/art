<?php

namespace App\Http\Controllers;

use App\SustainabilityStage5;
use Illuminate\Http\Request;
use App\Port;

use Illuminate\Support\Facades\Validator;
use App\Exports\SustainabilityStage5Export;
use Maatwebsite\Excel\Facades\Excel;
use App\invoice;
use App\invoiceus;
use App\invoiceuk;
use App\invoicecanada;
use App\invoicecalifornia;
use App\invoiceeu;
use Carbon\Carbon;
use DB;
use App\SustainabilityVariable;
use App\SustainabilityStage0;

class SustainabilityStage5Controller extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $search = $request->input('search');

        $query = SustainabilityStage5::with('portInfo')
            ->when($search, function ($query) use ($search) {
                $query->whereHas('portInfo', function ($q) use ($search) {
                    $q->where('name', 'like', '%' . $search . '%');
                });
            });

        $date_from = $request->input('date-from');
        $date_to = $request->input('date-to');

        if ($date_from && $date_to) {
            $query->whereBetween('month_year', [$date_from, $date_to]);
        }

        $records = $query
            ->orderByDesc('month_year')
            ->orderBy('port_id')
            ->paginate(12);

        return view("Sustainability.stage5.index", compact("records"));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $ports = Port::get();
        // Return the create view for SustainabilityStage5
        return view("Sustainability.stage5.create", compact('ports'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'port_id' => 'required|exists:ports,id',
            'container_sent' => 'required|numeric',
            'carbon_emission' => 'required|numeric',
            'month_year' => 'required|date',
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        try {

            $request->merge(['month_year' => $request->month_year . '-01']);

            // Check if the port record exists
            $port = Port::where('id', $request->port_id)->first();
            if (!$port) {
                return redirect()->back()->with('error', 'Port not found. Please set it up first.');
            }
            // Calculate carbon emission based on number of containers and port
            $carbonEmission = $request->container_sent * $port->carbon_emission_per_container;
            $request->merge(['carbon_emission' => $carbonEmission]);

            // Store the record
            SustainabilityStage5::create($request->all());
            return redirect()->route('sustainability.stage5.index')->with('success', 'Record created successfully.');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Error creating record: ' . $e->getMessage());
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(SustainabilityStage5 $sustainabilityStage5)
    {
        // Show the details of the specified record
        return view("Sustainability.stage5.show", compact("sustainabilityStage5"));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(SustainabilityStage5 $sustainabilityStage5)
    {
        $record = $sustainabilityStage5;
        $ports = Port::get();
        // Return the edit view for a specific SustainabilityStage5 record
        return view("Sustainability.stage5.edit", compact('record', 'ports'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, SustainabilityStage5 $sustainabilityStage5)
    {
        $validator = Validator::make($request->all(), [
            'port_id' => 'required|exists:ports,id',
            'container_sent' => 'required|numeric',
            'carbon_emission' => 'required|numeric',
            'month_year' => 'required|date',
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        try {

            $request->merge(['month_year' => $request->month_year . '-01']);

            // Check if the port record exists
            $port = Port::where('id', $request->port_id)->first();
            if (!$port) {
                return redirect()->back()->with('error', 'Port not found. Please set it up first.');
            }
            // Calculate carbon emission based on number of containers and sustainability variable
            $carbonEmission = $request->container_sent * $port->carbon_emission_per_container;
            $request->merge(['carbon_emission' => $carbonEmission]);

            // Update the record
            $sustainabilityStage5->update($request->all());
            return redirect()->route('sustainability.stage5.index')->with('success', 'Record updated successfully.');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Error updating record: ' . $e->getMessage());
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(SustainabilityStage5 $sustainabilityStage5)
    {
        DB::beginTransaction();
        try {

            $old_record = $sustainabilityStage5;
            $sustainabilityStage5->delete();

            $processedMonth = $old_record->month_year;
            $stage5_record = SustainabilityStage5::where('month_year', $processedMonth)->get();
            if ($stage5_record->isNotEmpty()) {
                $total_weight_deliverd = $stage5_record->sum('total_weight_delivered');
                $total_qty_sent = $stage5_record->sum('total_qty_sent');
                $carbon_sequestration = null;

                $variable = SustainabilityVariable::first();
                if ($variable) {
                    $carbon_sequestration = $variable->carbon_content * $variable->conversion_factor * $total_weight_deliverd;

                    SustainabilityStage0::updateOrCreate(
                        ['month_year' => $processedMonth],
                        [
                            'total_qty_delivered_by_containers' => $total_qty_sent,
                            'total_weight_delivered_in_containers' => $total_weight_deliverd,
                            'carbon_sequestration' => $carbon_sequestration,
                        ]
                    );
                }
            }

            DB::commit();
            return redirect()->route('sustainability.stage5.index')->with('success', 'Record deleted successfully.');
        } catch (\Exception $e) {

            DB::rollback();
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
        $filename = "stage5-report-{$from}-to-{$to}.xlsx";

        return Excel::download(new SustainabilityStage5Export($search, $date_from, $date_to), $filename);
    }

   
     /**
     * Get Container Details
     */
    public function containerDetails(Request $request)
    {
        try {
            $port_name = $request->portname; // Expecting 'YYYY-MM' format from JS
            $monthYear = $request->date; // Expecting 'YYYY-MM' format from JS
            $startOfMonth = Carbon::parse($monthYear . '-01')->startOfMonth()->toDateString();
            $endOfMonth = Carbon::parse($monthYear . '-01')->endOfMonth()->toDateString();

            $siteModelMap = [
                'Us'         => ['invoice' => invoiceus::class,        'country' => 'US'],
                'Uk'         => ['invoice' => invoiceuk::class,        'country' => 'UK'],
                'Eu'         => ['invoice' => invoiceeu::class,        'country' => 'EU'],
                'Ca'         => ['invoice' => invoicecanada::class,     'country' => 'CA'],
                'California' => ['invoice' => invoicecalifornia::class, 'country' => 'California'],
                'In'         => ['invoice' => invoice::class,          'country' => 'India'],
            ];

            $result = [];

            foreach ($siteModelMap as $models) {
                $invoiceModel = $models['invoice'];

                $invoiceQuery = $invoiceModel::with('portInfo')
                    ->whereBetween('date', [$startOfMonth, $endOfMonth])
                    ->whereNotNull('date')
                    ->where('discharge',$port_name)
                    ->where('invoiceno', 'like', 'GVD/UK/%')
                    ->where('invoiceno', 'not like', '%TEMP%') // exclude TEMP invoices
                    ->whereRaw('(containerno IS NULL OR UPPER(TRIM(containerno)) != ?)', ['TBC']) // exclude TBC placeholders from modal list
                    ->orderByDesc('date');

                foreach ($invoiceQuery->lazy() as $invoice) {
                    
                    $port = $invoice->portInfo;
                    if (!$port) {
                        continue;
                    }

                   $result[] = [
                        'country'     => $models['country'],
                        'invoiceno'   => $invoice->invoiceno,
                        'containerno' => $invoice->containerno,
                        'discharge'   => @$invoice->portInfo->name,
                        'postloading' => $invoice->postloading,
                        'destination' => $invoice->destination,
                        'date'        => $invoice->date,
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
