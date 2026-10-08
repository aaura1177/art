<?php

namespace App\Http\Controllers;

use App\ArtisanEmission;
use Illuminate\Http\Request;
use App\setting;

class ArtisanEmissionController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        // Get the month filter if available
        $month = $request->input('month');
        $query = ArtisanEmission::query();

        if ($month) {
            $query->whereMonth('month', '=', date('m', strtotime($month)))
                  ->whereYear('month', '=', date('Y', strtotime($month)));
        }

        $emissions = $query->get();

        return view('admin.artisan_emissions.index', compact('emissions'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        // Validate the request
        $setting=setting::first();
       
        $validated = $request->validate([
           
            'factory_electricity' => 'required|numeric',
            'uk_electricity' => 'required|numeric',
            'office_electricity' => 'required|numeric',
            'month' => 'required|date_format:Y-m',
        ]);
        $validated['electricity_consumed']=$validated['factory_electricity']+$validated['uk_electricity']+$validated['office_electricity'];
        // Carbon intensity in kgCO2 per kWh (constant)
        $carbon_intensity = $setting->electricity_factor;

        // Calculate the total carbon emissions
        $total_emissions = $validated['electricity_consumed'] * $carbon_intensity;

        // Create the new record
        ArtisanEmission::create([
            'electricity_consumed' => $validated['electricity_consumed'],
            'factory_electricity' => $validated['factory_electricity'],
            'uk_electricity' => $validated['uk_electricity'],
            'office_electricity' => $validated['office_electricity'],
            'carbon_emissions' => $total_emissions,
            'month' => $validated['month'] . '-01', // Store month in YYYY-MM-01 format
        ]);

        return redirect()->back()->with('success', 'Emission record added successfully');
    }

    /**
     * Show the form for editing the specified resource via modal.
     */
    public function edit($id)
    {
        // Find the emission record by its ID
        $emission = ArtisanEmission::findOrFail($id);
        return response()->json($emission); // Return JSON for modal
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        // Validate the request
        $setting=setting::first();
       
        $validated = $request->validate([
           
            'factory_electricity' => 'required|numeric',
            'uk_electricity' => 'required|numeric',
            'office_electricity' => 'required|numeric',
            'month' => 'required|date_format:Y-m',
        ]);
        $validated['electricity_consumed']=$validated['factory_electricity']+$validated['uk_electricity']+$validated['office_electricity'];
        // Carbon intensity in kgCO2 per kWh (constant)
        $carbon_intensity = $setting->electricity_factor;
        $total_emissions = $validated['electricity_consumed'] * $carbon_intensity;
        // Find the emission record by ID and update it
        $emission = ArtisanEmission::findOrFail($id);
        $emission->update([
            'electricity_consumed' => $validated['electricity_consumed'],
            'factory_electricity' => $validated['factory_electricity'],
            'uk_electricity' => $validated['uk_electricity'],
            'office_electricity' => $validated['office_electricity'],
            'carbon_emissions' => $total_emissions,
            'month' => $validated['month'] . '-01', // Store month in YYYY-MM-01 format
        ]);

        return redirect()->back()->with('success', 'Emission record updated successfully');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        // Find and delete the emission record
        ArtisanEmission::destroy($id);
        return redirect()->back()->with('success', 'Emission record deleted successfully');
    }
}
