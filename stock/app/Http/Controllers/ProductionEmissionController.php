<?php

namespace App\Http\Controllers;
use App\ProductionEmission;
use App\User;
use App\setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use Illuminate\Support\Facades\Storage;

class ProductionEmissionController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        // Fetch the production emissions for the logged-in supplier
        $logs = ProductionEmission::where('supplier_user_id', Auth::id())->get();
        return view('production_emission.index', compact('logs'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        // Validate the request input
        $request->validate([
            'date' => 'required|date_format:Y-m',
            'percentage' => 'required|numeric',
            'electricity_unit' => 'required|numeric',
            'distance' => 'required|numeric',
           'eway_bill' => 'nullable|file|max:10240', // Allow file upload up to 10 MB
            'electricity_bill' => 'nullable|file|max:10240'
        ]);
    
        // Format the date as needed
        $formattedDate = Carbon::createFromFormat('Y-m', $request->input('date'))->format('F-Y');
    
        // Handle the file upload using move
       
        $path = Storage::put('sustanibility/e-way', $request->file('eway_bill'));
        $url1 = Storage::url($path);
        $path = Storage::put('sustanibility/electricity_bill', $request->file('electricity_bill'));
        $url2 = Storage::url($path);

        $setting=setting::first();
        // Create a new record in the database
        $electricity=($request->input('electricity_unit')*$setting->electricity_factor)*($request->input('percentage')/100);
        ProductionEmission::create([
            'supplier_user_id' => Auth::id(),
            'date' => $formattedDate,
            'percentage' => $request->input('percentage'),
            'electricity_unit' => $request->input('electricity_unit'),
            'distance' => $request->input('distance'),
            'electricity_attachement' => $url2, 
            'eway_attachement' => $url1, 
            'distance_factor'=>$setting->distance_factor*$request->input('distance'),
            'electricity_factor'=> $electricity,
            'vehicle_type'=>$request->input('vehicle_type'),
            // Store the file path
        ]);
    
        return redirect()->route('production_emissions.index')->with('success', 'Record created successfully.');
    }
    
    

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
{
    // Validate the request input
    $request->validate([
        'date' => 'required|date_format:Y-m',
        'percentage' => 'required|numeric',
        'electricity_unit' => 'required|numeric',
        'distance' => 'required|numeric',
        'eway_bill' => 'nullable|file|max:10240', // Allow file upload up to 10 MB
        'electricity_bill' => 'nullable|file|max:10240'
    ]);

    // Format the date as needed
    $formattedDate = Carbon::createFromFormat('Y-m', $request->input('date'))->format('F-Y');

    // Find the emission record
    $emission = ProductionEmission::findOrFail($id);

    // Handle file upload if there's a new file
    if ($request->hasfile('electricity_bill')) {
        
        $path = Storage::put('sustanibility/electricity_bill', $request->file('electricity_bill'));
        $url2 = Storage::url($path);
        // Update the new attachment path
        $emission->electricity_attachement =  $url2;
    }
    if ($request->hasfile('eway_bill')) {
        $path = Storage::put('sustanibility/e-way', $request->file('eway_bill'));
        $url1 = Storage::url($path);

        // Update the new attachment path
        $emission->eway_attachment =  $url1 ;
    }
    $setting=setting::first();
    
    // Update other fields
    $emission->update([
        'date' => $formattedDate,
        'percentage' => $request->input('percentage'),
        'electricity_unit' => $request->input('electricity_unit'),
        'distance' => $request->input('distance'),
        'distance_factor'=>$setting->distance_factor*$request->input('distance'),
        'electricity_factor'=>($request->input('electricity_unit')*$setting->electricity_factor)*($request->input('percentage')/100)
    ]);

    return redirect()->route('production_emissions.index')->with('success', 'Record updated successfully.');
}

    

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        // Find and delete the emission record
        $emission = ProductionEmission::findOrFail($id);
        $emission->delete();

        return redirect()->route('production_emissions.index')->with('success', 'Emission data deleted successfully!');
    }

    /**
     * Calculate consumption and emissions for the admin view.
     */
    public function consumption()
    {
        // Fetch all emission records and calculate emissions using percentage
        $emissions = ProductionEmission::with('supplier')->get()->map(function ($emission) {
            $formattedDate = Carbon::parse($emission->date)->format('F-Y');
            
            
            return [
                'id' => $emission->id,
                'supplier_name' => $emission->supplier->firstname . ' ' . $emission->supplier->lastname,
                'supplier_user_id' => $emission->supplier_user_id,
                'date' => $formattedDate,
                'percentage' => $emission->percentage,
                'electricity_unit' => $emission->electricity_unit,
                'electricity_carbon_emission' => round($emission->electricity_factor, 2),
                'distance' => $emission->distance,
                'distance_emission' => $emission->distance_factor,
                'monthly_emission' => round($emission->electricity_factor, 2) + $emission->distance_factor,
                'eway_attachement' => $emission->eway_attachement,
                'electricity_attachement' => $emission->electricity_attachement,
                'vehicle_type'=>$emission->vehicle_type,
            ];
        });

        // Fetch all suppliers
        $suppliers = User::where('role', 'supplier')->get();
        // Get unique months from the emissions
        $months = $emissions->pluck('date')->unique();

        return view('admin.production_emission.index', compact('emissions', 'suppliers', 'months'));
    }
    public function store_production_emission(Request $request)
    {
        // Validate the request input
        $request->validate([
            'supplier_id' => 'required|exists:users,id',
            'date' => 'required|date_format:Y-m',
            'percentage' => 'required|numeric',
            'electricity_unit' => 'required|numeric',
            'distance' => 'required|numeric',
            'eway_bill' => 'nullable|file|max:10240',
            'electricity_bill' => 'nullable|file|max:10240'
        ]);
    
        $formattedDate = Carbon::createFromFormat('Y-m', $request->input('date'))->format('F-Y');
    
        // Handle file uploads
        $url1 = $request->file('eway_bill') ? Storage::url(Storage::put('sustanibility/e-way', $request->file('eway_bill'))) : null;
        $url2 = $request->file('electricity_bill') ? Storage::url(Storage::put('sustanibility/electricity_bill', $request->file('electricity_bill'))) : null;
    
        $setting = setting::first();
        $electricityEmission = ($request->input('electricity_unit') * $setting->electricity_factor) * ($request->input('percentage') / 100);
        $distanceEmission = $setting->distance_factor * $request->input('distance');
    
        ProductionEmission::updateOrCreate(
            ['id' => $request->input('emission_id')],  // If ID exists, update. Else, create new.
            [
                'supplier_user_id' => $request->input('supplier_id'),
                'date' => $formattedDate,
                'percentage' => $request->input('percentage'),
                'electricity_unit' => $request->input('electricity_unit'),
                'distance' => $request->input('distance'),
                'electricity_attachement' => $url2,
                'eway_attachement' => $url1,
                'distance_factor' => $distanceEmission,
                'electricity_factor' => $electricityEmission,
                'vehicle_type' => $request->input('vehicle_type'),
            ]
        );
        return redirect()->route('admin.production_emissions.consumption')->with('success', 'Record created successfully.');
    }
}
