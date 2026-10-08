<?php 
namespace App\Http\Controllers;

use App\LogisticsEmission;
use App\LogisticPartener;
use App\invoice;
use Illuminate\Http\Request;

class LogisticsEmissionController extends Controller
{
    public function index(Request $request)
{
    // Get all logistics partners for the dropdown
    $logisticsPartners = LogisticPartener::all();

    // Start building the query for invoices
    $carbonEmissionRecords = invoice::query();

    // Exclude records with NULL dates
    $carbonEmissionRecords->whereNotNull('date');
    $carbonEmissionRecords->orderBy('date', 'desc');
    // Apply filter by month if provided
    if ($request->has('month') && $request->month) {
        $carbonEmissionRecords->whereMonth('date', \Carbon\Carbon::parse($request->month)->format('m'))
                              ->whereYear('date', \Carbon\Carbon::parse($request->month)->format('Y'));
    }

    // Only include invoices with a valid logistic partner
    $carbonEmissionRecords->whereHas('logisticPartner');

    // Eager load the logistics partner relationship and get the filtered data
    $carbonEmissionRecords = $carbonEmissionRecords->with('logisticPartner')->get();

    // Initialize an array to hold the grouped results
    $groupedRecords = [];

    // Group by logistics partner and month, and sum quantities and carbon emissions
    foreach ($carbonEmissionRecords as $invoice) {
        if ($invoice->logisticPartner) {
            $carbonEmissionValue = $invoice->logisticPartner->carbon_emission_value;
            $invoice->carbon_emission = $invoice->quantity * $carbonEmissionValue;
            $formattedMonth = \Carbon\Carbon::parse($invoice->date)->format('M-Y');
            $partnerName = $invoice->logisticPartner->name;

            // Create a unique key for grouping (by partner and month)
            $groupKey = $partnerName . '_' . $formattedMonth;

            if (!isset($groupedRecords[$groupKey])) {
                // If this is the first record for this group, initialize the values
                $groupedRecords[$groupKey] = [
                    'logisticPartner' => $partnerName,
                    'quantity' => 0,
                    'carbon_emission' => 0,
                    'month' => $formattedMonth
                ];
            }

            // Sum the quantity and carbon emission for this group
            $groupedRecords[$groupKey]['quantity'] += 1;
            $groupedRecords[$groupKey]['carbon_emission'] = $invoice->logisticPartner->carbon_emission_value;
        }
    }
 
    // Pass both logisticsPartners (for the filter dropdown) and groupedRecords to the view
    return view('admin.logistics_emissions.index', compact('logisticsPartners', 'groupedRecords'));
}




    


    

    public function create()
    {
        $logisticsPartners = LogisticPartener::all();
        return view('carbon_emission_records.create', compact('logisticsPartners'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'logistics_partner_id' => 'required|exists:logistics_partners,id',
            'number_of_containers' => 'required|integer',
            'month' => 'required|string',
        ]);

        // Get the carbon emission factor from the selected logistics partner
        $logisticsPartner = LogisticPartener::findOrFail($request->logistics_partner_id);
        $carbonEmissionFactor = $logisticsPartner->carbon_emission_value;

        // Calculate the total carbon emission
        $totalEmission = $request->number_of_containers * $carbonEmissionFactor;

        LogisticsEmission::create([
            'logistics_partner_id' => $request->logistics_partner_id,
            'number_of_containers' => $request->number_of_containers,
            'month' => $request->month,
            'calculated_emission' => $totalEmission,
        ]);

        return redirect()->route('logistics_emission.index')->with('success', 'Carbon emission record created successfully.');
    }

    public function update(Request $request, LogisticsEmission $carbonEmissionRecord)
    {
        $request->validate([
            'logistics_partner_id' => 'required|exists:logistics_partners,id',
            'number_of_containers' => 'required|integer',
            'month' => 'required|string',
        ]);

        // Get the carbon emission factor from the selected logistics partner
        $logisticsPartner = LogisticPartener::findOrFail($request->logistics_partner_id);
        $carbonEmissionFactor = $logisticsPartner->carbon_emission_value;

        // Recalculate the total carbon emission
        $totalEmission = $request->number_of_containers * $carbonEmissionFactor;

        $carbonEmissionRecord->update([
            'logistics_partner_id' => $request->logistics_partner_id,
            'number_of_containers' => $request->number_of_containers,
            'month' => $request->month,
            'calculated_emission' => $totalEmission,
        ]);

        return redirect()->route('logistics_emission.index')->with('success', 'Carbon emission record updated successfully.');
    }
    public function edit(LogisticsEmission $carbonEmissionRecord)
    {
        $logisticsPartners = LogisticPartener::all();
        return view('logistics_emission.edit', compact('carbonEmissionRecord', 'logisticsPartners'));
    }

   

    public function destroy(LogisticsEmission $carbonEmissionRecord)
    {
        $carbonEmissionRecord->delete();

        return redirect()->route('logistics_emission.index')->with('success', 'Carbon emission record deleted successfully.');
    }
}
