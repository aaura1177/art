<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\DistributionEmission;
use App\setting;

class DistributionEmissionController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        // Get the month filter if applied
        $month = $request->input('month');

        // Fetch emissions, optionally filtered by month
        $emissions = DistributionEmission::when($month, function ($query, $month) {
            return $query->whereMonth('month', date('m', strtotime($month)))
                         ->whereYear('month', date('Y', strtotime($month)));
        })->get();

        // Return the view with emissions
        return view('admin.distribution_emission.index', compact('emissions'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        // Validate the request inputs
        $setting=setting::first();
        $request->validate([
            'no_of_parcels' => 'required|integer|min:1',
            'month' => 'required|date_format:Y-m'
        ]);

        // Calculate the total emissions
        $no_of_parcels = $request->input('no_of_parcels');
        $total_emission = $setting->distribution_factor * $no_of_parcels;

        // Create a new emission record
        DistributionEmission::create([
            'no_of_parcels' => $no_of_parcels,
            'total_emission' => $total_emission,
            'month' => $request->input('month')
        ]);

        // Redirect back to the list with a success message
        return redirect()->route('distribution_emissions.index')
                         ->with('success', 'Emission record created successfully.');
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        // Validate the request inputs
        $request->validate([
            'no_of_parcels' => 'required|integer|min:1',
            'month' => 'required|date_format:Y-m'
        ]);
        $setting=setting::first();
        // Find the existing emission record
        $emission = DistributionEmission::findOrFail($id);

        // Calculate the total emissions
        $no_of_parcels = $request->input('no_of_parcels');
        $total_emission = $setting->distribution_factor * $no_of_parcels;

        // Update the emission record
        $emission->update([
            'no_of_parcels' => $no_of_parcels,
            'total_emission' => $total_emission,
            'month' => $request->input('month')
        ]);

        // Redirect back to the list with a success message
        return redirect()->route('distribution_emissions.index')
                         ->with('success', 'Emission record updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        // Find the emission record
        $emission = DistributionEmission::findOrFail($id);

        // Delete the emission record
        $emission->delete();

        // Redirect back to the list with a success message
        return redirect()->route('distribution_emissions.index')
                         ->with('success', 'Emission record deleted successfully.');
    }
}
