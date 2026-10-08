<?php

namespace App\Http\Controllers;

use App\TransportMaritimeEmission;
use App\setting;
use Illuminate\Http\Request;



class TransportMaritimeEmissionController extends Controller
{
    public function index(Request $request)
    {
        // Filter by month if provided
        $query = TransportMaritimeEmission::query();

        if ($request->has('month') && $request->month) {
            $query->where('month', 'LIKE', $request->month . '%');
        }

        $emissions = $query->get();

        return view('admin.transport_emissions.index', compact('emissions'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'route' => 'required|string',
            'distance_km' => 'required|numeric',
            'month' => 'required|date_format:Y-m',
        ]);
        $setting=setting::first();
        $emission = new TransportMaritimeEmission($request->only(['route', 'distance_km', 'month']));
         $emission['total_emissions']=$request->distance_km*$setting->distance_factor;
        $emission->save();

        return redirect()->route('emissions.index')->with('success', 'Emission record created successfully.');
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'route' => 'required|string',
            'distance_km' => 'required|numeric',
            'month' => 'required|date_format:Y-m',
        ]);
        $setting=setting::first();
        $emission = TransportMaritimeEmission::findOrFail($id);
        $emission->fill($request->only(['route', 'distance_km', 'month']));
        $emission['total_emissions']=$request->distance_km*$setting->distance_factor;
        $emission->save();

        return redirect()->route('emissions.index')->with('success', 'Emission record updated successfully.');
    }

    public function destroy($id)
    {
        $emission = TransportMaritimeEmission::findOrFail($id);
        $emission->delete();

        return redirect()->route('emissions.index')->with('success', 'Emission record deleted successfully.');
    }
}

