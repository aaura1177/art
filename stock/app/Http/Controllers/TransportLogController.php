<?php

namespace App\Http\Controllers;

use App\TransportLog;
use App\setting;
use App\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;



class TransportLogController extends Controller
{
    // Display a listing of the transport logs for the logged-in supplier
    public function index()
    {
        // Get only the transport logs of the authenticated supplier
        $logs = TransportLog::where('supplier_user_id', Auth::id())->get();

        return view('transport_logs.index', compact('logs'));
    }

    // Show the form for creating a new transport log
    public function create()
    {
        return view('transport_logs.create');
    }

    // Store a newly created transport log in storage
    public function store(Request $request)
    {
        // Validate the input
        $request->validate([
            'distance_travelled' => 'required|numeric',
            'times_transport_occurred' => 'required|integer',
            'transport_date' => 'required|date',
            'vehicle_type' => 'required',
        ]);

        // Create the transport log, associating it with the logged-in supplier

        TransportLog::create([
            'supplier_user_id' => Auth::id(),  // Automatically assign the logged-in user's ID
            'distance_travelled' => $request->input('distance_travelled'),
            'times_transport_occurred' => $request->input('times_transport_occurred'),
            'transport_date' => $request->input('transport_date'),
            'vehicle_type' => $request->input('vehicle_type')
        ]);

        return redirect()->route('transport_logs.index')->with('success', 'Transport log created successfully.');
    }

    // Show the form for editing the specified transport log
    public function edit(TransportLog $transportLog)
    {
        // Ensure the logged-in supplier is the owner of the transport log
        if ($transportLog->supplier_user_id !== Auth::id()) {
            return redirect()->route('transport_logs.index')->with('error', 'You are not authorized to edit this log.');
        }

        return view('transport_logs.edit', compact('transportLog'));
    }

    // Update the specified transport log in storage
    public function update(Request $request, TransportLog $transportLog)
    {
        // Ensure the logged-in supplier is the owner of the transport log
        if ($transportLog->supplier_user_id !== Auth::id()) {
            return redirect()->route('transport_logs.index')->with('error', 'You are not authorized to update this log.');
        }

        // Validate the input
        $request->validate([
            'distance_travelled' => 'required|numeric',
            'times_transport_occurred' => 'required|integer',
            'transport_date' => 'required|date',
        ]);

        // Update the transport log
        $transportLog->update([
            'distance_travelled' => $request->input('distance_travelled'),
            'times_transport_occurred' => $request->input('times_transport_occurred'),
            'transport_date' => $request->input('transport_date'),
        ]);

        return redirect()->route('transport_logs.index')->with('success', 'Transport log updated successfully.');
    }

    // Remove the specified transport log from storage
    public function destroy(TransportLog $transportLog)
    {
        // Ensure the logged-in supplier is the owner of the transport log
        if ($transportLog->supplier_user_id !== Auth::id()) {
            return redirect()->route('transport_logs.index')->with('error', 'You are not authorized to delete this log.');
        }

        // Delete the transport log
        $transportLog->delete();

        return redirect()->route('transport_logs.index')->with('success', 'Transport log deleted successfully.');
    }

    // Import Carbon for date handling



    public function adminTransportLogs(Request $request)
    {
        // Admin panel: Filter transport logs by supplier and month
        $supplierId = $request->input('supplier_user_id');
        $month = $request->input('month');
        $year = $request->input('year', date('Y')) ?? 2024;  // Default to current year if not provided

        // Build the query to fetch supplier-wise, month-wise transport log aggregation
        $setting = Setting::first();

        $distanceFactor = $setting ? $setting->distance_factor : 1;  // Set default value if no setting

        $query = TransportLog::selectRaw("
        CONCAT(users.firstname, ' ', users.lastname) as supplier_name, 
        SUM(distance_travelled) as total_km, 
        SUM(times_transport_occurred) as total_rounds, 
        SUM(distance_travelled * times_transport_occurred * ?) as carbon_emission,
        DATE_FORMAT(transport_date, '%b-%Y') as month
    ")
            ->join('users', 'transport_logs.supplier_user_id', '=', 'users.id')
            ->whereYear('transport_date', $year)  // Use dynamic year
            ->groupBy('users.firstname', 'users.lastname', DB::raw('DATE_FORMAT(transport_date, "%b-%Y")'))
            ->addBinding($distanceFactor, 'select');

        // Apply supplier filter if selected
        if ($supplierId) {
            $query->where('supplier_user_id', $supplierId);
        }

        // Apply month filter if selected
        if ($month) {
            $query->whereMonth('transport_date', $month)
                ->whereYear('transport_date', $year);  // Ensure we are filtering by the correct year
        }

        // Fetch the filtered/aggregated results without sorting
        $logs = $query->get();

        // Sort logs in PHP by month in descending order
        $logs = $logs->sortByDesc(function ($log) {
            // Convert the 'month' field to a date for proper sorting (e.g., "Sep-2024")
            return Carbon::createFromFormat('M-Y', $log->month);
        })->values();  // Re-index the collection after sorting

        // Fetch suppliers for the supplier filter dropdown
        $suppliers = User::where('role', 'supplier')->get();  // Assuming suppliers have a 'role' column

        // Return the view with the logs and suppliers data
        return view('admin.transportlogs.index', compact('logs', 'suppliers'));
    }

    public function storeadmin(Request $request)
    {

        // Converts "2025-01" to "January-2025"
        TransportLog::updateOrCreate(
            ['id' => $request->log_id],
            [
                'supplier_user_id' => $request->input('supplier_id'), // Auto-assign logged-in user
                'distance_travelled' => $request->input('total_km'),
                'times_transport_occurred' => $request->input('total_rounds'),
                'transport_date' => $request->input('date'), // Save as "January-2025"
                'vehicle_type' => $request->input('vehicle_type'),
            ]

        );
        return redirect()->back()->with('success', 'Transport log saved successfully.');
    }


}
