<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Country;
use App\LogisticPartener;

class LogisticsPartnerController extends Controller
{
    //
    public function index()
    {
        $logisticsPartners = LogisticPartener::with('country')->get();
        $countries = Country::all(); // Fetch countries for dropdown
        return view('admin.logistics_parteners.index', compact('logisticsPartners', 'countries'));
    }
    public function create()
    {
        $countries = Country::all();
        return view('logistics_partners.create', compact('countries'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required',
            'country_id' => 'required|exists:countries,id',
            'carbon_emission_value' => 'required|numeric',
        ]);

        LogisticPartener::create($request->all());

        return redirect()->route('logistics_partners.index')->with('success', 'Logistics Partner created successfully.');
    }

    public function edit(LogisticPartener $logisticsPartner)
    {
        $countries = Country::all();
        return view('logistics_partners.edit', compact('logisticsPartner', 'countries'));
    }

    public function update(Request $request, LogisticPartener $logisticsPartner)
    {
        $request->validate([
            'name' => 'required',
            'country_id' => 'required|exists:countries,id',
            'carbon_emission_value' => 'required|numeric',
        ]);

        $logisticsPartner->update($request->all());

        return redirect()->route('logistics_partners.index')->with('success', 'Logistics Partner updated successfully.');
    }

    public function destroy(LogisticPartener $logisticsPartner)
    {
        $logisticsPartner->delete();

        return redirect()->route('logistics_partners.index')->with('success', 'Logistics Partner deleted successfully.');
    }
}
