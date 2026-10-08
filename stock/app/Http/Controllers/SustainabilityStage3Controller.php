<?php

namespace App\Http\Controllers;

use App\SustainabilityStage3;
use Illuminate\Http\Request;
use App\SustainabilityVariable;
use Illuminate\Support\Facades\Validator;

use App\Exports\SustainabilityStage3ExportPowerConsumptionMonthly;
use App\Exports\SustainabilityStage3ExportPowerConsumptionYearly;
use Maatwebsite\Excel\Facades\Excel;

class SustainabilityStage3Controller extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function powerIndex(Request $request)
    {
        $sustainabilityVariable = SustainabilityVariable::first();

        $tab = $request->input('tab');
        $search = $request->input('search');

        $query = SustainabilityStage3::where('type', 'Electricity')
            ->when($search, function ($query) use ($search) {
                $query->where('office_type', 'like', '%' . $search . '%');
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

        $records = $query->orderBy('created_at', 'desc')->paginate(200);

        return view("Sustainability.stage3.powerIndex", compact("records", "sustainabilityVariable"));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        // Return the create view for SustainabilityStage3
        $sustainabilityVariable = SustainabilityVariable::first();
        return view("Sustainability.stage3.create", compact("sustainabilityVariable"));    
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $rules = [
            'office_type' => 'required',
            'type' => 'required',
            'carbon_emission' => 'required|numeric',
            'month_year' => 'required|date',
        ];

        if ($request->input('type') == 'Electricity') {
            $rules['power_consumed'] = 'required|numeric';
        } else {
            $rules['distance_travelled'] = 'required|numeric';
        }

        $validator = Validator::make($request->all(), $rules);

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
            // Calculate carbon emission based on distance travelled and sustainability variable
            if($request->input('type') == 'Electricity'){
                $carbonEmission = $request->power_consumed * $sustainabilityVariable->carbon_emission_factor_per_kwh;
                $request->merge(['carbon_emission' => $carbonEmission]);
            }else{
                $carbonEmission = $request->distance_travelled * $sustainabilityVariable->carbon_emission_rate;
                $request->merge(['carbon_emission' => $carbonEmission]);
            }

            if($request->input('type') == 'Electricity'){
                $request->merge(['units' => 'KWh']);
                $data = $request->only([
                    'office_type',
                    'type',
                    'units',
                    'power_consumed',
                    'carbon_emission',
                    'month_year',
                ]);
            }
            
            // Check if record is entered this month
            $existingRecord = SustainabilityStage3::where('office_type', $request->input('office_type'))
                                ->where('type', $request->input('type'))
                                ->where('month_year', '=', $request->month_year)
                                ->first();
            if ($existingRecord) {
                // Update if already exists
                $existingRecord->update($data);
               
                return redirect()->route('sustainability.stage3.create')->with('success', 'Sustainability Stage3 data updated successfully.');
            }
            // Create a new record if it doesn't exist
            SustainabilityStage3::Create($data);  
            return redirect()->route('sustainability.stage3.create')->with('success', 'Sustainability Stage3 data created successfully.');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Failed to save sustainability stage3 data: ' . $e->getMessage());
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(SustainabilityStage3 $sustainabilityStage3)
    {
        // Return the show view for a specific SustainabilityStage3 record
        return view("Sustainability.stage3.show", compact('sustainabilityStage3'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(SustainabilityStage3 $sustainabilityStage3)
    {
        // Return the edit view for a specific SustainabilityStage3 record
        $sustainabilityVariable = SustainabilityVariable::first();
        $record = $sustainabilityStage3;
        return view("Sustainability.stage3.edit", compact('sustainabilityVariable','record'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, SustainabilityStage3 $sustainabilityStage3)
    {
        $rules = [
            'office_type' => 'required',
            'type' => 'required',
            'carbon_emission' => 'required|numeric',
            'month_year' => 'required|date',
        ];

        if ($request->input('type') == 'Electricity') {
            $rules['power_consumed'] = 'required|numeric';
        } else {
            $rules['distance_travelled'] = 'required|numeric';
        }

        $validator = Validator::make($request->all(), $rules);

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
            // Calculate carbon emission based on distance travelled and sustainability variable
            if($request->input('type') == 'Electricity'){
                $request->merge(['units' => 'KWh']);
                $carbonEmission = $request->power_consumed * $sustainabilityVariable->carbon_emission_factor_per_kwh;
                $request->merge(['carbon_emission' => $carbonEmission]);
            }
           
            if($request->input('type') == 'Electricity'){
                $data = $request->only([
                    'office_type',
                    'type',
                    'units',
                    'power_consumed',
                    'carbon_emission',
                    'month_year',
                ]);
            }
        
            // Update the existing record
            $sustainabilityStage3->update($data);
            return redirect()->route('sustainability.stage3.power-consumption')->with('success', 'Sustainability Stage3 data updated successfully.');
        } catch (\Exception $e) {
           return redirect()->back()->with('error', 'Failed to update sustainability stage3 data: ' . $e->getMessage());
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(SustainabilityStage3 $sustainabilityStage3)
    {
        try {
            // Delete the specified record
            $sustainabilityStage3->delete();
            return redirect()->back()->with('success', 'Sustainability Stage3 data deleted successfully.');

        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Failed to delete sustainability stage3 data: ' . $e->getMessage());
        }
    }

     /**
     * Export Power Consmption Report based on filter
     */

    public function exportPowerConsumption(Request $request)
    {
        $tab = $request->input('tab');
        $month = $request->input('month') ?? "";
        $year = $request->input('year') ?? "";

        $search = $request->input('search') ?? "";
        $date_from = $request->input('date-from') ?? "";
        $date_to = $request->input('date-to') ?? "";
        if ($tab === "monthly") 
        {
            $monthName = $month ? date("M", mktime(0, 0, 0, $month, 10)) : 'AllMonths';
            $yearSelected = $year ?? 'AllYears';
            $filename = "stage3-power-consumption-monthly-{$monthName}-{$yearSelected}.xlsx";
            return Excel::download(new SustainabilityStage3ExportPowerConsumptionMonthly($search, $month, $year ), $filename);
        }else{
            $from = $date_from ? date('Ymd', strtotime($date_from)) : 'start';
            $to = $date_to ? date('Ymd', strtotime($date_to)) : 'end';
            $filename = "stage3-power-consumption-yearly-{$from}-to-{$to}.xlsx";
            return Excel::download(new SustainabilityStage3ExportPowerConsumptionYearly($search, $date_from, $date_to ), $filename);
        }
    }

}
