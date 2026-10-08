<?php

namespace App\Http\Controllers;

use App\Exports\SustainabilityStage1ExportMonthly;
use App\Exports\SustainabilityStage1ExportYearly;
use App\Http\Controllers\Concerns\UploadsSustainabilityFilesToS3;
use App\SustainabilityStage1;
use App\SustainabilityVariable;
use App\supplier;
use Auth;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Maatwebsite\Excel\Facades\Excel;

class SustainabilityStage1Controller extends Controller
{
    use UploadsSustainabilityFilesToS3;
    use Concerns\PaginatesSustainabilityMonthlyByYear;
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $suppliers = supplier::select('id', 'c_name', 'name')->orderBy('c_name', 'asc')->get();

        $search = $request->input('search');

        $searchFilter = function ($query) use ($search) {
            if ($search) {
                $query->whereHas('supplierInfo', function ($q) use ($search) {
                    $q->where('c_name', 'like', '%' . $search . '%');
                });
            }
        };

        $monthlyQuery = SustainabilityStage1::with('supplierInfo');
        $searchFilter($monthlyQuery);
        $this->applyMonthlyTabFilters($monthlyQuery, $request);

        $monthlyRecords = $this->paginateMonthlyByYear($monthlyQuery, $request);
        if ($monthlyRecords instanceof \Illuminate\Http\RedirectResponse) {
            return $monthlyRecords;
        }

        $yearlyQuery = SustainabilityStage1::with('supplierInfo');
        $searchFilter($yearlyQuery);
        $this->applyYearlyTabFilters($yearlyQuery, $request);

        $yearlyRecords = $yearlyQuery
            ->orderBy('created_at', 'desc')
            ->paginate(200, ['*'], 'yearly_page')
            ->withQueryString();

        return view("Sustainability.stage1.index", compact("monthlyRecords", "yearlyRecords", "suppliers"));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $sustainabilityVariable = SustainabilityVariable::first();
        $suppliers = supplier::select('id', 'c_name', 'name')->orderBy('c_name', 'asc')->get();
        return view("Sustainability.stage1.create", compact("sustainabilityVariable", "suppliers"));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'supplier_id' => 'required|exists:suppliers,id',
            'fuel_type' => 'required',
            'vehicle_type' => 'required',
            'distance_travelled' => 'required|numeric',
            'number_of_rounds' => 'required|numeric',
            'carbon_emission' => 'required|numeric',
            'month_year' => 'required|date',
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        try {

            $request->merge(['month_year' => $request->month_year . '-01']);
            $request->merge(['type' => 'Transport']);

            $request->merge(['created_by' => Auth::user()->role]);
            $request->merge(['created_by_uid' => Auth::user()->id]);

            // // Check if the sustainability variable record exists
            // $sustainabilityVariable = SustainabilityVariable::first();
            // if (!$sustainabilityVariable) {
            //     return redirect()->back()->with('error', 'Sustainability variable not found. Please set it up first.');
            // }

            // // Calculate carbon emission based on distance travelled and sustainability variable
            // $carbonEmission = $request->distance_travelled * $request->number_of_rounds * $sustainabilityVariable->carbon_emission_rate;
            // $request->merge(['carbon_emission' => $carbonEmission]);


            $data = [
                'supplier_id' => $request->supplier_id,
                'type' => 'Transport',
                'fuel_type' => $request->fuel_type,
                'vehicle_type' => $request->vehicle_type,
                'distance_travelled' => $request->distance_travelled,
                'number_of_rounds' => $request->number_of_rounds,
                'carbon_emission' => $request->carbon_emission,
                'month_year' => $request->month_year,
                'created_by' => $request->created_by,
                'created_by_uid' => $request->created_by_uid,
            ];

            $uploadError = $this->attachStage1ChalanUrl($request, $data);
            if ($uploadError) {
                return redirect()->back()->with('error', 'Failed to upload challan: ' . $uploadError)->withInput();
            }

            SustainabilityStage1::create($data);
            return redirect()->route('sustainability.stage1.index')->with('success', 'Sustainability Stage1 data created successfully.');

        } catch (\Exception $e) {
            \Log::error('Error updating Sustainability Stage1: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Failed to save sustainability stage1 data: ' . $e->getMessage());
        }

    }

    /**
     * Display the specified resource.
     */
    public function show(SustainabilityStage1 $sustainabilityStage1)
    {
        $sustainabilityVariable = SustainabilityVariable::first();
        return view("Sustainability.stage1.show", compact('sustainabilityStage1', 'sustainabilityVariable'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(SustainabilityStage1 $sustainabilityStage1)
    {
        $sustainabilityVariable = SustainabilityVariable::first();
        $record = $sustainabilityStage1;
        $suppliers = supplier::select('id', 'c_name', 'name')->orderBy('c_name', 'asc')->get();
        return view("Sustainability.stage1.edit", compact('record', 'sustainabilityVariable', 'suppliers'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, SustainabilityStage1 $sustainabilityStage1)
    {
        $validator = Validator::make($request->all(), [
            'supplier_id' => 'required|exists:suppliers,id',
            'fuel_type' => 'required',
            'vehicle_type' => 'required',
            'distance_travelled' => 'required|numeric',
            'carbon_emission' => 'required|numeric',
            'number_of_rounds' => 'required|numeric',
            'month_year' => 'required|date',
        ]);

        if ($validator->fails()) 
        {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        try {

            $request->merge(['month_year' => $request->month_year . '-01']);
            $request->merge(['type' => 'Transport']);

            // // Check if the sustainability variable record exists
            // $sustainabilityVariable = SustainabilityVariable::first();
            // if (!$sustainabilityVariable) {
            //     return redirect()->back()->with('error', 'Sustainability variable not found. Please set it up first.');
            // }
            // // Calculate carbon emission based on distance travelled and sustainability variable
            // $carbonEmission = $request->distance_travelled * $request->number_of_rounds * $sustainabilityVariable->carbon_emission_rate;
            // $request->merge(['carbon_emission' => $carbonEmission]);
           
            $sustainabilityStage1->update($request->all());
            return redirect()->route('sustainability.stage1.index')->with('success', 'Sustainability Stage1 data updated successfully.');
        } catch (\Exception $e) {
            \Log::error('Error updating Sustainability Stage1: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Failed to update sustainability stage1 data: ' . $e->getMessage());
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(SustainabilityStage1 $sustainabilityStage1)
    {
        try {
            $sustainabilityStage1->delete();
            return redirect()->route('sustainability.stage1.index')->with('success', 'Sustainability Stage1 data deleted successfully.');
        } catch (\Exception $e) {
            \Log::error('Error deleting Sustainability Stage1: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Failed to delete sustainability stage1 data: ' . $e->getMessage());
        }

    }

    /**
     * Export Record based on filter.
     */
    public function export(Request $request)
    {
        $tab = $request->input('tab');
        $month = $request->input('month') ?? "";
        $year = $request->input('year') ?? "";

        $search = $request->input('search') ?? "";
        $date_from = $request->input('date-from') ?? "";
        $date_to = $request->input('date-to') ?? "";

       
        if ($tab === "monthly") {
            $monthName = $month ? date("M", mktime(0, 0, 0, $month, 10)) : 'AllMonths';
            $yearSelected = $year ?? 'AllYears';
            $filename = "stage1-monthly-{$monthName}-{$yearSelected}.xlsx";
            return Excel::download(new SustainabilityStage1ExportMonthly($search, $month, $year), $filename);
        } else {
            $from = $date_from ? date('Ymd', strtotime($date_from)) : 'start';
            $to = $date_to ? date('Ymd', strtotime($date_to)) : 'end';
            $filename = "stage1-yearly-{$from}-to-{$to}.xlsx";
            return Excel::download(new SustainabilityStage1ExportYearly($search, $date_from, $date_to), $filename);
        }
    }
}
