<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\UploadsSustainabilityFilesToS3;
use App\SustainabilityStage1;
use App\SustainabilityVariable;
use App\supplier;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Auth;

class SupplierSustainabilityStage1Controller extends Controller
{
    use UploadsSustainabilityFilesToS3;
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {

        $query = SustainabilityStage1::where('created_by', 'Supplier')
            ->where('created_by_uid', Auth::user()->id);
        
        $date_from = $request->input('date-from');
        $date_to = $request->input('date-to');

        if ($date_from && $date_to) {
            $query->whereBetween('created_at', [$date_from, $date_to]);
        }
        
        $records = $query->orderBy('created_at', 'desc')->paginate(50);
        return view("TransportLogs.index", compact("records"));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $sustainabilityVariable = SustainabilityVariable::first();
        $supplier = supplier::select('id', 'c_name', 'name')->orderBy('c_name', 'asc')->where('id',Auth::user()->supplier_id)->first();
        return view("TransportLogs.create", compact("sustainabilityVariable", "supplier"));
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

            $carbonEmission = 0;

            // Check if the sustainability variable record exists
            $sustainabilityVariable = SustainabilityVariable::first();
            if ($sustainabilityVariable) {
                // Calculate carbon emission based on distance travelled and sustainability variable
                if($request->fuel_type == 'Diesel') {
                   $carbonEmission = $request->distance_travelled * $request->number_of_rounds * $sustainabilityVariable->carbon_emission_rate_for_diesel;
                } elseif ($request->fuel_type == 'Petrol') {
                     $carbonEmission = $request->distance_travelled * $request->number_of_rounds * $sustainabilityVariable->carbon_emission_rate_for_petrol;
                } elseif ($request->fuel_type == 'Electricity') {
                     $carbonEmission = $request->distance_travelled * $request->number_of_rounds * $sustainabilityVariable->carbon_emission_rate_for_electronic;
                } 
                elseif ($request->fuel_type == 'Hybrid') {
                     $carbonEmission = $request->distance_travelled * $request->number_of_rounds * $sustainabilityVariable->carbon_emission_rate_for_hybrid;
                } 
                elseif ($request->fuel_type == 'CNG') {
                     $carbonEmission = $request->distance_travelled * $request->number_of_rounds * $sustainabilityVariable->carbon_emission_rate_for_cng;
                } 
               
            }

            $data = [
                'supplier_id' => $request->supplier_id,
                'type' => 'Transport',
                'fuel_type' => $request->fuel_type,
                'vehicle_type' => $request->vehicle_type,
                'distance_travelled' => $request->distance_travelled,
                'number_of_rounds' => $request->number_of_rounds,
                'month_year' => $request->month_year,
                'carbon_emission' => $carbonEmission ?? 0,
                'created_by' => $request->created_by,
                'created_by_uid' => $request->created_by_uid,
            ];

            $uploadError = $this->attachStage1ChalanUrl($request, $data);
            if ($uploadError) {
                return redirect()->back()->with('error', 'Failed to upload challan: ' . $uploadError)->withInput();
            }

            SustainabilityStage1::create($data);
            return redirect()->route('transport.logs.index')->with('success', 'Data created successfully.');

        } catch (\Exception $e) {
            \Log::error('Error updating Data: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Failed to save data: ' . $e->getMessage());
        }

    }

    /**
     * Display the specified resource.
     */
    public function show(SustainabilityStage1 $transportlogs)
    {
        $sustainabilityVariable = SustainabilityVariable::first();
        return view("TransportLogs.show", compact('transportlogs', 'sustainabilityVariable'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(SustainabilityStage1 $transportlogs)
    {
        $sustainabilityVariable = SustainabilityVariable::first();
        $record = $transportlogs;
        $supplier = supplier::select('id', 'c_name', 'name')->orderBy('c_name', 'asc')->where('id',Auth::user()->supplier_id)->first();
        return view("TransportLogs.edit", compact('record', 'sustainabilityVariable', 'supplier'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, SustainabilityStage1 $transportlogs)
    {
        $validator = Validator::make($request->all(), [
            'supplier_id' => 'required|exists:suppliers,id',
            'fuel_type' => 'required',
            'vehicle_type' => 'required',
            'distance_travelled' => 'required|numeric',
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

            // Check if the sustainability variable record exists
            $sustainabilityVariable = SustainabilityVariable::first();
            if ($sustainabilityVariable) {
                // Calculate carbon emission based on distance travelled and sustainability variable
                if($request->fuel_type == 'Diesel') {
                   $carbonEmission = $request->distance_travelled * $request->number_of_rounds * $sustainabilityVariable->carbon_emission_rate_for_diesel;
                } elseif ($request->fuel_type == 'Petrol') {
                     $carbonEmission = $request->distance_travelled * $request->number_of_rounds * $sustainabilityVariable->carbon_emission_rate_for_petrol;
                } elseif ($request->fuel_type == 'Electricity') {
                     $carbonEmission = $request->distance_travelled * $request->number_of_rounds * $sustainabilityVariable->carbon_emission_rate_for_electronic;
                } 
                elseif ($request->fuel_type == 'Hybrid') {
                     $carbonEmission = $request->distance_travelled * $request->number_of_rounds * $sustainabilityVariable->carbon_emission_rate_for_hybrid;
                } 
                elseif ($request->fuel_type == 'CNG') {
                     $carbonEmission = $request->distance_travelled * $request->number_of_rounds * $sustainabilityVariable->carbon_emission_rate_for_cng;
                } 
            }else{
                 $carbonEmission = 0;
            }

            $data = [
                'supplier_id' => $request->supplier_id,
                'fuel_type' => $request->fuel_type,
                'vehicle_type' => $request->vehicle_type,
                'distance_travelled' => $request->distance_travelled,
                'number_of_rounds' => $request->number_of_rounds,
                'month_year' => $request->month_year,
                'carbon_emission' => $carbonEmission,
            ];

            $transportlogs->update($data);

            return redirect()->route('transport.logs.index')->with('success', 'Data updated successfully.');
        } catch (\Exception $e) {
            \Log::error('Error updating Data: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Failed to update data: ' . $e->getMessage());
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(SustainabilityStage1 $transportlogs)
    {
        try {
            $transportlogs->delete();
            return redirect()->route('transport.logs.index')->with('success', 'Data deleted successfully.');
        } catch (\Exception $e) {
            \Log::error('Error deleting Data: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Failed to delete data: ' . $e->getMessage());
        }
    }

}
