<?php

namespace App\Http\Controllers;

use App\SustainabilityStage3Employee;
use Illuminate\Http\Request;
use App\SustainabilityVariable;
use Illuminate\Support\Facades\Validator;
use App\Exports\SustainabilityStage3EmployeeExport;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\Http;
class SustainabilityStage3EmployeeController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $sustainabilityVariable = SustainabilityVariable::first();

        $search = $request->input('search');

        $query = SustainabilityStage3Employee::when($search, function ($query) use ($search) {
                $query->where('user_name', 'like', '%' . $search . '%');
            });
           
        $date_from = $request->input('date-from');
        $date_to = $request->input('date-to');

        if ($date_from && $date_to) {
            $query->whereBetween('month_year', [$date_from, $date_to]);
        }
        
        $records = $query->orderBy('created_at', 'desc')->paginate(12);

        // Return the index view with the records
        return view("Sustainability.stage3.employee.index", compact("records","sustainabilityVariable"));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $sustainabilityVariable = SustainabilityVariable::first();
        return view("Sustainability.stage3.employee.create", compact("sustainabilityVariable"));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'user_name' => 'required',
            'distance_travelled' => 'required|numeric',
            'number_of_rounds' => 'required|numeric',
            'days' => 'required|numeric',
            'fuel_type' => 'required',
            'vehicle_type' => 'required',
            'carbon_emission' => 'required|numeric',
            'month_year' => 'required|date',
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        try {
            $request->merge(['month_year' => $request->month_year . '-01']);

            // Check if the sustainability variable record exists
            // $sustainabilityVariable = SustainabilityVariable::first();
            // if (!$sustainabilityVariable) {
            //     return redirect()->back()->with('error', 'Sustainability variable not found. Please set it up first.');
            // }
            // // Calculate carbon emission based on distance travelled and sustainability variable
            // $distance_travelled = $request->distance_travelled * $request->number_of_rounds * $request->days;
            // $carbonEmission =  $distance_travelled * $sustainabilityVariable->carbon_emission_rate;
            // $request->merge(['carbon_emission' => $carbonEmission]);
            
            $request->merge(['type' => 'Transport']);
            $request->merge(['units' => 'Km']);
            
        
            $data = $request->only([
                'user_name',
                'type',
                'units',
                'distance_travelled',
                'number_of_rounds',
                'days',
                'fuel_type',
                'vehicle_type',
                'carbon_emission',
                'month_year',
            ]);

            if($request->has('emp_id') && !empty($request->has('emp_id')))
            {
                $data['emp_id'] = $request->emp_id;
                // Check if record is entered this month
                $existingRecord =  SustainabilityStage3Employee::where('user_name', $request->user_name)
                ->where('emp_id', $request->emp_id)
                ->where('month_year', '=', $request->month_year)
                ->first();
            }else{

                // Check if record is entered this month
                $existingRecord =  SustainabilityStage3Employee::where('user_name', $request->user_name)
                ->where('month_year', '=', $request->month_year)
                ->first();
            }

            if ($existingRecord) {
                // Update if already exists
                $existingRecord->update($data);
                return redirect()->route('sustainability.stage3.employee.index')->with('success', 'Sustainability Stage3 Employee data updated successfully.');
            }
            // Create a new record if it doesn't exist
            SustainabilityStage3Employee::Create($data);
            return redirect()->route('sustainability.stage3.employee.index')->with('success', 'Sustainability Stage3 Employee data created successfully.');
        } catch (\Exception $e) {
            \Log::error('Error updating Sustainability Stage3 Employee: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Failed to save sustainability stage3 Employee data: ' . $e->getMessage());
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(SustainabilityStage3Employee $employeeVariable)
    {
        // Return the show view for a specific SustainabilityStage3Employee record
        return view("Sustainability.stage3.employee.show", compact('employeeVariable'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(SustainabilityStage3Employee $employeeVariable)
    {
        $sustainabilityVariable = SustainabilityVariable::first();
        $record = $employeeVariable;
        // Return the edit view for a specific SustainabilityStage3Employee record
        return view("Sustainability.stage3.employee.edit", compact('record', 'sustainabilityVariable'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, SustainabilityStage3Employee $employeeVariable)
    {
        $validator = Validator::make($request->all(), [
            'user_name' => 'required',
            'distance_travelled' => 'required|numeric',
            'number_of_rounds' => 'required|numeric',
            'days' => 'required|numeric',
            'fuel_type' => 'required',
            'vehicle_type' => 'required',
            'carbon_emission' => 'required|numeric',
            'month_year' => 'required|date',
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        try {
            
            $request->merge(['month_year' => $request->month_year . '-01']);

            // check if the sustainability variable record exists
            // $sustainabilityVariable = SustainabilityVariable::first();
            // if (!$sustainabilityVariable) {
            //     return redirect()->back()->with('error', 'Sustainability variable not found. Please set it up first.');
            // }
            // // Calculate carbon emission based on distance travelled and sustainability variable
            // $distance_travelled = $request->distance_travelled * $request->number_of_rounds * $request->days;

            // $carbonEmission =  $distance_travelled * $sustainabilityVariable->carbon_emission_rate;

            // $request->merge(['carbon_emission' => $carbonEmission]);
            
            $request->merge(['type' => 'Transport']);
            $request->merge(['units' => 'Km']);
            
            $data = $request->only([
                'user_name',
                'type',
                'units',
                'distance_travelled',
                'number_of_rounds',
                'carbon_emission',
                'days',
                'fuel_type',
                'vehicle_type',
                'month_year',
            ]);
          
            if($request->has('emp_id') && !empty($request->has('emp_id')))
            {
                $data['emp_id'] = $request->emp_id;
            }

            // Update the record
            $employeeVariable->update($data);
            return redirect()->route('sustainability.stage3.employee.index')->with('success', 'Sustainability Stage3 Employee data updated successfully.');
        } catch (\Exception $e) {
            \Log::error('Error updating Sustainability Stage3 Employee: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Failed to update sustainability stage3 Employee data: ' . $e->getMessage());
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(SustainabilityStage3Employee $employeeVariable)
    {
        try {
            $employeeVariable->delete();
            return redirect()->route('sustainability.stage3.employee.index')->with('success', 'Sustainability Stage3 Employee data deleted successfully.');
        } catch (\Exception $e) {
            \Log::error('Error deleting Sustainability Stage3 Employee: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Failed to delete sustainability stage3 Employee data: ' . $e->getMessage());
        }
    }

    /**
     * Auto-complete user name
     */
    public function autocomplete(Request $request)
    {
        $search = $request->get('term');

        $month_year = $request->get('monthYear') ? explode("-", $request->get('monthYear')) : null;
        $month = $month_year ? $month_year[1] : date("m");
        $year = $month_year ? $month_year[0] : date("Y");
        $url = env('HRMS_URL')."api/getEmployeeAttendenceDetails?term=" . urlencode($search)."&month=".$month."&year=".$year;

        // Initialize cURL
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);

        curl_close($ch);


        if ($response === false || $httpCode !== 200) {
            return response()->json([]);
        }

        $apiData = json_decode($response, true);

    
        // Extract and format data for autocomplete
        $suggestions = [];
        if (!empty($apiData['data']) && is_array($apiData['data'])) {
            foreach ($apiData['data'] as $item) {
                $suggestions[] = [
                    'label' => $item['given_name'] ?? '', // What appears in dropdown
                    'value' => $item['given_name'] ?? '', // What is inserted into the input box
                    'attendance_count' => $item['attendance_count'] ?? '0',
                    'emp_id' => $item['id'] ?? '',
                    'vehicle_type' => $item['vehicle_type'] ?? '',
                    'fuel_type' => $item['fuel_type'] ?? '',
                    'one_way_distance' => $item['one_way_distance'] ?? '',
                    'number_of_rounds' => $item['number_of_rounds'] ?? '',
                    'id' => $item['id'] ?? null,
                ];
            }
        }
        return response()->json($suggestions);
    }


    /**
     * Export Employee Report 
     */
    public function export(Request $request)
    {
        $search = $request->input('search') ?? "";
        $month = $request->input('month') ?? "";
        $year = $request->input('year') ?? "";
        
        $monthName = $month ? date("M", mktime(0, 0, 0, $month, 10)) : 'AllMonths';
        $yearSelected = $year ?? 'AllYears';
        $filename = "stage3-employee-report-{$monthName}-{$yearSelected}.xlsx";
        
        return Excel::download(new SustainabilityStage3EmployeeExport($search, $month, $year ), $filename);
    }

}
