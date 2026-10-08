<?php

namespace App\Http\Controllers;

use App\SustainabilityStage3Miscellaneous;
use Illuminate\Http\Request;
use App\SustainabilityVariable;
use Illuminate\Support\Facades\Validator;
use App\Exports\SustainabilityStage3MiscellaneousExport;
use Maatwebsite\Excel\Facades\Excel;

class SustainabilityStage3MiscellaneousController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $sustainabilityVariable = SustainabilityVariable::first();
        // Fetch all records from the SustainabilityStage3Miscellaneous model

        $search = $request->input('search');

        $query = SustainabilityStage3Miscellaneous::when($search, function ($query) use ($search) {
                $query->where('user_name', 'like', '%' . $search . '%')
                    ->orWhere('travel_mode', 'like', '%' . $search . '%')
                    ->orWhere('origin_name', 'like', '%' . $search . '%')
                    ->orWhere('destination_name', 'like', '%' . $search . '%');
            });
           
        $date_from = $request->input('date-from');
        $date_to = $request->input('date-to');

        if ($date_from && $date_to) {
            $query->whereBetween('month_year', [$date_from, $date_to]);
        }
        
        $records = $query->orderBy('created_at', 'desc')->paginate(12);

        // Return the index view with the records
        return view("Sustainability.stage3.miscellaneous.index", compact("records","sustainabilityVariable"));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $sustainabilityVariable = SustainabilityVariable::first();
        return view("Sustainability.stage3.miscellaneous.create", compact("sustainabilityVariable"));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'user_name' => 'required',
            'travel_mode' => 'required',
            'origin_name' => 'required',
            'destination_name' => 'required',
            'distance_travelled' => 'required|numeric',
            'carbon_emission' => 'required|numeric',
            'month_year' => 'required|date',
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }


        try {
            // Check if the sustainability variable record exists
            $sustainabilityVariable = SustainabilityVariable::first();
            if (!$sustainabilityVariable) {
                return redirect()->back()->with('error', 'Sustainability variable not found. Please set it up first.');
            }
            // Calculate carbon emission based on distance travelled and sustainability variable
            $carbonEmission = $request->distance_travelled * $sustainabilityVariable->carbon_emission_rate;
            $request->merge(['carbon_emission' => $carbonEmission]);
            $request->merge(['month_year' => $request->month_year . '-01']);
            
            // Create a new record if it doesn't exist
            SustainabilityStage3Miscellaneous::create($request->all());
            return redirect()->route('sustainability.stage3.miscellaneous.index')->with('success', 'Sustainability Stage3 Miscellaneous data created successfully.');
        } catch (\Exception $e) {
            \Log::error('Error updating Sustainability Stage3 Miscellaneous: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Failed to save sustainability stage3 miscellaneous data: ' . $e->getMessage());
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(SustainabilityStage3Miscellaneous $miscellaneousVariable)
    {
        // Return the show view for a specific SustainabilityStage3Miscellaneous record
        return view("Sustainability.stage3.miscellaneous.show", compact('miscellaneousVariable'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(SustainabilityStage3Miscellaneous $miscellaneousVariable)
    {
        $sustainabilityVariable = SustainabilityVariable::first();
        $record = $miscellaneousVariable;
        // Return the edit view for a specific SustainabilityStage3Miscellaneous record
        return view("Sustainability.stage3.miscellaneous.edit", compact('record', 'sustainabilityVariable'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, SustainabilityStage3Miscellaneous $miscellaneousVariable)
    {
        $validator = Validator::make($request->all(), [
            'user_name' => 'required',
            'travel_mode' => 'required',
            'origin_name' => 'required',
            'destination_name' => 'required',
            'distance_travelled' => 'required|numeric',
            'carbon_emission' => 'required|numeric',
            'month_year' => 'required|date',
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        try {
            
            $request->merge(['month_year' => $request->month_year . '-01']);

            // check if the sustainability variable record exists
            $sustainabilityVariable = SustainabilityVariable::first();
            if (!$sustainabilityVariable) {
                return redirect()->back()->with('error', 'Sustainability variable not found. Please set it up first.');
            }
            // Calculate carbon emission based on distance travelled and sustainability variable
            $carbonEmission = $request->distance_travelled * $sustainabilityVariable->carbon_emission_rate;
            $request->merge(['carbon_emission' => $carbonEmission]);

            // Update the record
            $miscellaneousVariable->update($request->all());
            return redirect()->route('sustainability.stage3.miscellaneous.index')->with('success', 'Sustainability Stage3 Miscellaneous data updated successfully.');
        } catch (\Exception $e) {
            \Log::error('Error updating Sustainability Stage3 Miscellaneous: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Failed to update sustainability stage3 miscellaneous data: ' . $e->getMessage());
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(SustainabilityStage3Miscellaneous $miscellaneousVariable)
    {
        try {
            $miscellaneousVariable->delete();
            return redirect()->route('sustainability.stage3.miscellaneous.index')->with('success', 'Sustainability Stage3 Miscellaneous data deleted successfully.');
        } catch (\Exception $e) {
            \Log::error('Error deleting Sustainability Stage3 Miscellaneous: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Failed to delete sustainability stage3 miscellaneous data: ' . $e->getMessage());
        }
    }

    /**
     * Auto-complete user name
     */
    public function autocomplete(Request $request)
    {
        $search = $request->get('term');
        $users = SustainabilityStage3Miscellaneous::where('user_name', 'LIKE', "%{$search}%")->distinct()->pluck('user_name');

        return response()->json($users);
    }

    /**
     * Auto-complete origin name
     */
    public function autocompleteOrigin(Request $request)
    {
        $search = $request->get('term');
        $users = SustainabilityStage3Miscellaneous::where('origin_name', 'LIKE', "%{$search}%")->distinct()->pluck('origin_name');

        return response()->json($users);
    }

    /**
     * Auto-complete destination name
     */
    public function autocompleteDestination(Request $request)
    {
        $search = $request->get('term');
        $users = SustainabilityStage3Miscellaneous::where('destination_name', 'LIKE', "%{$search}%")->distinct()->pluck('destination_name');

        return response()->json($users);
    }

    /**
     * Export Employee Report 
     */
    public function export(Request $request)
    {
        $search = $request->input('search') ?? "";
        $date_from = $request->input('date-from') ?? "";
        $date_to = $request->input('date-to') ?? "";
        
        $from = $date_from ? date('Ymd', strtotime($date_from)) : 'start';
        $to = $date_to ? date('Ymd', strtotime($date_to)) : 'end';
        $filename = "stage3-miscellaneous-report-{$from}-to-{$to}.xlsx";
        
        return Excel::download(new SustainabilityStage3MiscellaneousExport($search, $date_from, $date_to ), $filename);
    }

}
