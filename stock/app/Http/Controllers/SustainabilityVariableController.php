<?php

namespace App\Http\Controllers;

use App\SustainabilityVariable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class SustainabilityVariableController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $record = SustainabilityVariable::first();
        return view("Sustainability.variable.index", compact("record"));
    }

    /**
     * Show the form for creating a new resource.
     */
    // public function create()
    // {
    //     return view("Sustainability.variable.create");
    // }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        // return validation error with input data   
        $validator = Validator::make($request->all(), [
            'carbon_content' => 'required|numeric',
            'conversion_factor' => 'required|numeric',
            'carbon_emission_rate' => 'required|numeric',
            'carbon_emission_factor_per_kwh' => 'required|numeric',
            'carbon_emission_per_kg_parcel_us' => 'required|numeric',
            'carbon_emission_per_kg_parcel_uk' => 'required|numeric',
            'carbon_emission_per_kg_parcel_eu' => 'required|numeric',
            'carbon_emission_per_kg_parcel_ca' => 'required|numeric',
            'carbon_emission_per_kg_parcel_in' => 'required|numeric',
            'mundra_port_distance' => 'required|numeric',
            'carbon_emission_rate_for_electronic' => 'required|numeric',
            'carbon_emission_rate_for_petrol' => 'required|numeric',
            'carbon_emission_rate_for_hybrid' => 'required|numeric',
            'carbon_emission_rate_for_solar' => 'required|numeric',
            'carbon_emission_rate_for_diesel' => 'required|numeric',
            'carbon_emission_rate_for_cng' => 'required|numeric',
        ]);
    
        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        try {
            // Update or create the sustainability variable record
            SustainabilityVariable::updateOrCreate(
                ['id' => 1], // Assuming a single record with a fixed ID
                $request->only([
                    'carbon_content',
                    'conversion_factor',
                    'carbon_emission_rate',
                    'carbon_emission_factor_per_kwh',
                    'mundra_port_distance',
                    'carbon_emission_rate_for_electronic',
                    'carbon_emission_rate_for_petrol',
                    'carbon_emission_rate_for_hybrid',
                    'carbon_emission_rate_for_solar',
                    'carbon_emission_rate_for_diesel',
                    'carbon_emission_rate_for_cng',
                    'carbon_emission_per_kg_parcel_us',
                    'carbon_emission_per_kg_parcel_uk',
                    'carbon_emission_per_kg_parcel_eu',
                    'carbon_emission_per_kg_parcel_ca',
                    'carbon_emission_per_kg_parcel_in',
                ])
            );

            // Redirect to the index page with success message
            return redirect()->route('sustainability.variable.index')->with('success', 'Sustainability variable saved successfully.');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Failed to save sustainability variable: ' . $e->getMessage());
        }
    }

    
    /**
     * Show the form for editing the specified resource.
     */
    // public function edit(SustainabilityVariable $sustainabilityVariable)
    // {
    //     return view("Sustainability.variable.edit", compact('sustainabilityVariable'));
    // }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, SustainabilityVariable $sustainabilityVariable)
    {
        // return validation error with input data
        $validator = Validator::make($request->all(), [
            'carbon_content' => 'required|numeric',
            'conversion_factor' => 'required|numeric',
            'carbon_emission_rate' => 'required|numeric',
            'carbon_emission_factor_per_kwh' => 'required|numeric',
            'carbon_emission_per_kg_parcel_us' => 'required|numeric',
            'carbon_emission_per_kg_parcel_uk' => 'required|numeric',
            'carbon_emission_per_kg_parcel_eu' => 'required|numeric',
            'carbon_emission_per_kg_parcel_ca' => 'required|numeric',
            'carbon_emission_per_kg_parcel_in' => 'required|numeric',
            'mundra_port_distance' => 'required|numeric',
            'carbon_emission_rate_for_electronic' => 'required|numeric',
            'carbon_emission_rate_for_petrol' => 'required|numeric',
            'carbon_emission_rate_for_hybrid' => 'required|numeric',
            'carbon_emission_rate_for_solar' => 'required|numeric',
            'carbon_emission_rate_for_diesel' => 'required|numeric',
            'carbon_emission_rate_for_cng' => 'required|numeric',
        ]);
            
        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }
    
        try {
            $sustainabilityVariable->update($request->all());
            return redirect()->route('sustainability.variable.index')->with('success', 'Sustainability variable updated successfully.');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Failed to update sustainability variable: ' . $e->getMessage());
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(SustainabilityVariable $sustainabilityVariable)
    {
        try {
            $sustainabilityVariable->delete();
            return redirect()->route('sustainability.variable.index')->with('success', 'Sustainability variable deleted successfully.');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Failed to delete sustainability variable: ' . $e->getMessage());
        }
    }
}
