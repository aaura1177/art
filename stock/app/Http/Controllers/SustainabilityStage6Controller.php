<?php

namespace App\Http\Controllers;

use App\SustainabilityStage6;
use Illuminate\Http\Request;
use App\SustainabilityVariable;
use Illuminate\Support\Facades\Validator;
use App\Exports\SustainabilityStage6ExportMonthly;
use App\Exports\SustainabilityStage6ExportYearly;
use App\Exports\SustainabilityStage6ExportParcelDelivered;
use Maatwebsite\Excel\Facades\Excel;
use Carbon\Carbon;
class SustainabilityStage6Controller extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $sustainabilityVariable = SustainabilityVariable::first();

        $tab = $request->input('tab');
        $search = $request->input('search');

        $query = SustainabilityStage6::when($search, function ($query) use ($search) {
            $query->where('location', 'like', '%' . $search . '%');
        });

        if ($tab === "monthly") {
            $month = $request->input('month');
            $year = $request->input('year');

            if (!empty($month) && !empty($year)) {
                // Full date match
                $query->where('month_year', '=', $year . '-' . str_pad($month, 2, '0', STR_PAD_LEFT) . '-01');
            } elseif (!empty($year)) {
                // Match year only
                $query->whereYear('month_year', $year);
            } elseif (!empty($month)) {
                // Match month only (any year)
                $query->whereMonth('month_year', $month);
            }

        } else {
            $date_from = $request->input('date-from');
            $date_to = $request->input('date-to');

            if ($date_from && $date_to) {
                $query->whereBetween('month_year', [$date_from, $date_to]);
            }
        }

        $records = $query
            ->orderByDesc('month_year')
            ->orderBy('location')
            ->paginate(12);

        return view("Sustainability.stage6.index", compact("records", "sustainabilityVariable"));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        // Return the create view for SustainabilityStage6
        $sustainabilityVariable = SustainabilityVariable::first();
        return view("Sustainability.stage6.create", compact("sustainabilityVariable"));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'location' => 'required',
            'parcel_delivered' => 'required',
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
            // Calculate carbon emission based on parcel delivered and sustainability variable
            if($request->location == "US")
            {
                $carbonEmission = $request->parcel_delivered * $sustainabilityVariable->carbon_emission_per_kg_parcel_us;
            }
            elseif($request->location == "UK")
            {
                $carbonEmission = $request->parcel_delivered * $sustainabilityVariable->carbon_emission_per_kg_parcel_uk;
            }
            elseif($request->location == "EU")
            {
                $carbonEmission = $request->parcel_delivered * $sustainabilityVariable->carbon_emission_per_kg_parcel_eu;
            }
            elseif($request->location == "CA")
            {
                $carbonEmission = $request->parcel_delivered * $sustainabilityVariable->carbon_emission_per_kg_parcel_ca;
            }
            elseif($request->location == "IN")
            {
                $carbonEmission = $request->parcel_delivered * $sustainabilityVariable->carbon_emission_per_kg_parcel_in;
            }
            else
            {
               $carbonEmission = $request->parcel_delivered * $sustainabilityVariable->carbon_emission_per_kg_parcel_in;
            }

            $request->merge(['carbon_emission' => $carbonEmission]);

            $data = $request->only([
                'parcel_delivered',
                'location',
                'carbon_emission',
                'month_year',
            ]);

            // Check if record is entered this month
            $existingRecord = SustainabilityStage6::where('location', $request->input('location'))
                ->where('month_year', '=', $request->month_year)
                ->first();
            if ($existingRecord) {
                // Update if already exists
                $existingRecord->update($data);

                return redirect()->route('sustainability.stage6.create')->with('success', 'Sustainability Stage6 data updated successfully.');
            }
            // Create a new record if it doesn't exist
            SustainabilityStage6::Create($data);
            return redirect()->route('sustainability.stage6.create')->with('success', 'Sustainability Stage6 data created successfully.');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Failed to save sustainability stage6 data: ' . $e->getMessage());
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(SustainabilityStage6 $sustainabilityStage6)
    {
        // Return the show view for a specific SustainabilityStage6 record
        return view("Sustainability.stage6.show", compact('sustainabilityStage6'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(SustainabilityStage6 $sustainabilityStage6)
    {
        // Return the edit view for a specific SustainabilityStage6 record
        $sustainabilityVariable = SustainabilityVariable::first();
        $record = $sustainabilityStage6;
        return view("Sustainability.stage6.edit", compact('sustainabilityVariable', 'record'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, SustainabilityStage6 $sustainabilityStage6)
    {
        $validator = Validator::make($request->all(), [
            'location' => 'required',
            'parcel_delivered' => 'required',
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
            // Calculate carbon emission based on parcel deliverd and sustainability variable
             if($request->location == "US")
            {
                $carbonEmission = $request->parcel_delivered * $sustainabilityVariable->carbon_emission_per_kg_parcel_us;
            }
            elseif($request->location == "UK")
            {
                $carbonEmission = $request->parcel_delivered * $sustainabilityVariable->carbon_emission_per_kg_parcel_uk;
            }
            elseif($request->location == "EU")
            {
                $carbonEmission = $request->parcel_delivered * $sustainabilityVariable->carbon_emission_per_kg_parcel_eu;
            }
            elseif($request->location == "CA")
            {
                $carbonEmission = $request->parcel_delivered * $sustainabilityVariable->carbon_emission_per_kg_parcel_ca;
            }
            elseif($request->location == "IN")
            {
                $carbonEmission = $request->parcel_delivered * $sustainabilityVariable->carbon_emission_per_kg_parcel_in;
            }
            else
            {
               $carbonEmission = $request->parcel_delivered * $sustainabilityVariable->carbon_emission_per_kg_parcel_in;
            }
            
            $request->merge(['carbon_emission' => $carbonEmission]);

            $data = $request->only([
                'parcel_delivered',
                'location',
                'carbon_emission',
                'month_year',
            ]);

            // Update the existing record
            $sustainabilityStage6->update($data);
            return redirect()->route('sustainability.stage6.index')->with('success', 'Sustainability Stage6 data updated successfully.');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Failed to update sustainability stage6 data: ' . $e->getMessage());
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(SustainabilityStage6 $sustainabilityStage6)
    {
        try {
            // Delete the specified record
            $sustainabilityStage6->delete();
            return redirect()->back()->with('success', 'Sustainability Stage6 data deleted successfully.');

        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Failed to delete sustainability stage6 data: ' . $e->getMessage());
        }
    }

    /**
     * Export Stage 6 Report based on filter
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
            $filename = "stage6-monthly-report-{$monthName}-{$yearSelected}.xlsx";

            return Excel::download(new SustainabilityStage6ExportMonthly($search, $month, $year), $filename);
        } else {
            $from = $date_from ? date('Ymd', strtotime($date_from)) : 'start';
            $to = $date_to ? date('Ymd', strtotime($date_to)) : 'end';
            $filename = "stage6-yearly-report-{$from}-to-{$to}.xlsx";

            return Excel::download(new SustainabilityStage6ExportYearly($search, $date_from, $date_to), $filename);
        }
    }

    /**
     * Export Parcel Delivered
     */

    public function exportParcelDeliverd(Request $request)
    {
        $month_year = $request->input('month_year');
        $location = $request->input('location');

        $filename = "stage6-parcel-deliverd-{$month_year}-{$location}.xlsx";
        return Excel::download(new SustainabilityStage6ExportParcelDelivered($month_year, $location), $filename);
        
    }

}
