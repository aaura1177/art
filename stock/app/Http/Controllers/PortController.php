<?php

namespace App\Http\Controllers;

use App\Port;
use Illuminate\Http\Request;

class PortController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $records = Port::orderByDesc('id')->paginate(12);

        return view("Sustainability.port.index", compact("records"));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view("Sustainability.port.create");
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $this->validate($request, [
            'name' => 'required|unique:ports,name',
            'carbon_emission_per_container' => 'required|numeric',
        ]);

        try {
            // check if port already exists
            $port = Port::where('name', $request->name)->first();
            if ($port) {
                // update if already exists 
                $port->update($request->all());
            }else{
                // create new port
                Port::create($request->all());
            }

            // Redirect to the index page with success message
            return redirect()->route('sustainability.port.index')->with('success', 'Sustainability port saved successfully.');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Failed to save sustainability port: ' . $e->getMessage());
        }
    }

    
    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Port $Port)
    {
        return view("Sustainability.port.edit", compact('Port'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Port $Port)
    {
        $this->validate($request, [
            'name' => 'required|unique:ports,name,' . $Port->id,
            'carbon_emission_per_container' => 'required|numeric',
        ]);

       
        try {
            $Port->update($request->all());
            return redirect()->route('sustainability.port.index')->with('success', 'Sustainability port updated successfully.');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Failed to update sustainability port: ' . $e->getMessage());
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Port $Port)
    {
        try {
            $Port->delete();
            return redirect()->route('sustainability.port.index')->with('success', 'Sustainability port deleted successfully.');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Failed to delete sustainability port: ' . $e->getMessage());
        }
    }
}
