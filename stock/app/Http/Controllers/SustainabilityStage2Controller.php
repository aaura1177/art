<?php

namespace App\Http\Controllers;

use App\SustainabilityStage2;
use Illuminate\Http\Request;
use App\SustainabilityVariable;
use App\supplier;
use Illuminate\Support\Facades\Validator;
use App\Exports\SustainabilityStage2ExportFuelConsumptionMonthly;
use App\Exports\SustainabilityStage2ExportFuelConsumptionYearly;
use App\Exports\SustainabilityStage2ExportPowerConsumptionMonthly;
use App\Exports\SustainabilityStage2ExportPowerConsumptionYearly;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\Storage;
use Auth;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Concerns\UploadsSustainabilityFilesToS3;

class SustainabilityStage2Controller extends Controller
{
    use Concerns\PaginatesSustainabilityMonthlyByYear;
    use UploadsSustainabilityFilesToS3;
    /**
     * Display a listing of the resource.
     */
    public function fuelIndex(Request $request)
    {
        $suppliers = supplier::select('id', 'c_name', 'name')->orderBy('c_name', 'asc')->get();

        $search = $request->input('search');

        $searchFilter = function ($query) use ($search) {
            if ($search) {
                $query->whereHas('supplierInfo', function ($q) use ($search) {
                    $q->where('c_name', 'like', '%' . $search . '%');
                });
            }
        };

        $monthlyQuery = SustainabilityStage2::where('type', 'Transport')->with('supplierInfo');
        $searchFilter($monthlyQuery);
        $this->applyMonthlyTabFilters($monthlyQuery, $request);

        $monthlyRecords = $this->paginateMonthlyByYear($monthlyQuery, $request);
        if ($monthlyRecords instanceof \Illuminate\Http\RedirectResponse) {
            return $monthlyRecords;
        }

        $yearlyQuery = SustainabilityStage2::where('type', 'Transport')->with('supplierInfo');
        $searchFilter($yearlyQuery);
        $this->applyYearlyTabFilters($yearlyQuery, $request);

        $yearlyRecords = $yearlyQuery
            ->orderBy('created_at', 'desc')
            ->paginate(200, ['*'], 'yearly_page')
            ->withQueryString();

        return view("Sustainability.stage2.fuelIndex", compact("monthlyRecords", "yearlyRecords", "suppliers"));
    }


    public function powerIndex(Request $request)
    {
        $suppliers = supplier::select('id', 'c_name', 'name')->orderBy('c_name', 'asc')->get();

        $search = $request->input('search');

        $searchFilter = function ($query) use ($search) {
            if ($search) {
                $query->whereHas('supplierInfo', function ($q) use ($search) {
                    $q->where('c_name', 'like', '%' . $search . '%');
                });
            }
        };

        $monthlyQuery = SustainabilityStage2::where('type', 'Electricity')->with('supplierInfo');
        $searchFilter($monthlyQuery);
        $this->applyMonthlyTabFilters($monthlyQuery, $request);

        $monthlyRecords = $this->paginateMonthlyByYear($monthlyQuery, $request);
        if ($monthlyRecords instanceof \Illuminate\Http\RedirectResponse) {
            return $monthlyRecords;
        }

        $yearlyQuery = SustainabilityStage2::where('type', 'Electricity')->with('supplierInfo');
        $searchFilter($yearlyQuery);
        $this->applyYearlyTabFilters($yearlyQuery, $request);

        $yearlyRecords = $yearlyQuery
            ->orderBy('created_at', 'desc')
            ->paginate(200, ['*'], 'yearly_page')
            ->withQueryString();

        return view("Sustainability.stage2.powerIndex", compact("monthlyRecords", "yearlyRecords", "suppliers"));
    }


    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
       
        // Return the create view for SustainabilityStage2
        $sustainabilityVariable = SustainabilityVariable::first();
        $suppliers = supplier::select('id', 'c_name', 'name')->orderBy('c_name', 'asc')->get();
        return view("Sustainability.stage2.create", compact("sustainabilityVariable", "suppliers"));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $rules = [
            'supplier_id' => 'required|numeric',
            'type' => 'required',
            'carbon_emission' => 'required|numeric',
            'month_year' => 'required',
        ];

        if ($request->input('type') == 'Electricity') {
            $rules['power_consumed'] = 'required|numeric';
        } else {
            $rules['distance_travelled'] = 'required|numeric';
        }

        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        try {

            $request->merge(['month_year' => $request->month_year . '-01']);
            $request->merge(['created_by' => "Admin"]);
            $request->merge(['created_by_uid' => Auth::user()->id]);

            // Check if the sustainability variable record exists
            // $sustainabilityVariable = SustainabilityVariable::first();
            // if (!$sustainabilityVariable) {
            //     return redirect()->back()->with('error', 'Sustainability variable not found. Please set it up first.');
            // }
            // // Calculate carbon emission based on distance travelled and sustainability variable
            // if ($request->input('type') == 'Electricity') {
            //     $carbonEmission = $request->power_consumed * $sustainabilityVariable->carbon_emission_factor_per_kwh;
            //     $request->merge(['carbon_emission' => $carbonEmission]);
            // } else {
            //     $carbonEmission = $request->distance_travelled * $request->number_of_rounds * $sustainabilityVariable->carbon_emission_rate;
            //     $request->merge(['carbon_emission' => $carbonEmission]);
            // }

            if ($request->input('type') == 'Electricity') {
                $request->merge(['units' => 'KWh']);


                $data = $request->only([
                    'supplier_id',
                    'type',
                    'units',
                    'power_consumed',
                    'carbon_emission',
                    'is_value_in_percentage',
                    'actual_value',
                    'percentage_value',
                    'month_year',
                    'created_by',
                    'created_by_uid',
                    'is_solar',
                ]);

               if ($request->hasFile('chalan_url')) {
                    $supplier = Supplier::select('id', 'c_name', 'name')->find($request->supplier_id);

                    $folderName = 'sustainability/supplier/power-chalan/' . str_replace(" ", "_", $supplier->c_name) . '-' . $supplier->id . '/';
                    $file = $request->file('chalan_url');
                    $fileName = str_replace(" ", "_", $supplier->c_name) . '-' . $supplier->id . '-' . time() . '.' . $file->getClientOriginalExtension();

                    // Full path in S3
                    $filePath = $folderName . $fileName;

                    // Upload the file using helper
                    $return_data = $this->uploadToS3($filePath, $file->get());

                    if (isset($return_data['error'])) {
                        return response()->json($return_data, 500);
                    }

                    // Save to database or continue logic
                    $data['chalan_url'] = $return_data['path'];

                   
                }

                if ($request->hasFile('e-bill')) {
                    $supplier = supplier::select('id', 'c_name', 'name')->find($request->supplier_id);
                    $folderName = 'sustainability/supplier/power-ebill/' . str_replace(" ", "_", $supplier->c_name) . '-' . $supplier->id. '/';
                    $file = $request->file('e-bill');
                    $fileName = str_replace(" ", "_", $supplier->c_name) . '-' . $supplier->id . '-' . time() . '.' . $file->getClientOriginalExtension();

                   // Full path in S3
                    $filePath = $folderName . $fileName;

                    // Upload the file using helper
                    $return_data = $this->uploadToS3($filePath, $file->get());

                    if (isset($return_data['error'])) {
                        return response()->json($return_data, 500);
                    }

                    $data['bill_url'] = $return_data['path'];
                }

            } else {
                $request->merge(['units' => 'Km']);
                $request->merge(['number_of_rounds' => $request->number_of_rounds]);
                $data = $request->only([
                    'supplier_id',
                    'type',
                    'units',
                    'distance_travelled',
                    'number_of_rounds',
                    'carbon_emission',
                    'month_year',
                    'vehicle_type',
                    'fuel_type',
                    'created_by',
                    'created_by_uid',
                ]);

                $uploadError = $this->attachStage2TransportChalanUrl($request, $data);
                if ($uploadError) {
                    return redirect()->back()->with('error', 'Failed to upload challan: ' . $uploadError)->withInput();
                }
            }

            // Check if record is entered this month
            // $existingRecord = SustainabilityStage2::where('supplier_id', $request->supplier_id)
            //     ->where('type', $request->input('type'))
            //     ->where('month_year', '=', $request->month_year)
            //     ->first();

            // if ($existingRecord) {
            //     // Update if already exists
            //     $existingRecord->update($data);
                
            //     return redirect()->route('sustainability.stage2.create')->with('success', 'Sustainability Stage2 data updated successfully.');
            // }
            // Create a new record if it doesn't exist
            SustainabilityStage2::Create($data);
           
            return redirect()->route('sustainability.stage2.create')->with('success', 'Sustainability Stage2 data created successfully.');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Failed to save sustainability stage2 data: ' . $e->getMessage());
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(SustainabilityStage2 $sustainabilityStage2)
    {
        // Return the show view for a specific SustainabilityStage2 record
        return view("Sustainability.stage2.show", compact('sustainabilityStage2'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(SustainabilityStage2 $sustainabilityStage2)
    {
        // Return the edit view for a specific SustainabilityStage2 record
        $sustainabilityVariable = SustainabilityVariable::first();
        $record = $sustainabilityStage2;
        $suppliers = supplier::select('id', 'c_name', 'name')->orderBy('c_name', 'asc')->get();
        return view("Sustainability.stage2.edit", compact('record', 'sustainabilityVariable', 'suppliers'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, SustainabilityStage2 $sustainabilityStage2)
    {
        $rules = [
            'supplier_id' => 'required|numeric',
            'type' => 'required',
            'carbon_emission' => 'required|numeric',
            'month_year' => 'required',
        ];

        if ($request->input('type') == 'Electricity') {
            $rules['power_consumed'] = 'required|numeric';
        } else {
            $rules['distance_travelled'] = 'required|numeric';
        }

        $validator = Validator::make($request->all(), $rules);

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
            // if ($request->input('type') == 'Electricity') {


            //     $carbonEmission = $request->power_consumed * $sustainabilityVariable->carbon_emission_factor_per_kwh;
            //     $request->merge(['carbon_emission' => $carbonEmission]);
            // } else {
            //     $carbonEmission = $request->distance_travelled * $request->number_of_rounds * $sustainabilityVariable->carbon_emission_rate;
            //     $request->merge(['carbon_emission' => $carbonEmission]);
            // }

            if ($request->input('type') == 'Electricity') {
                 $request->merge(['units' => 'KWh']);
                $data = $request->only([
                    'supplier_id',
                    'type',
                    'units',
                    'power_consumed',
                    'carbon_emission',
                    'is_value_in_percentage',
                    'actual_value',
                    'percentage_value',
                    'month_year',
                    'is_solar',
                ]);

                
               if ($request->hasFile('chalan_url')) {
                    $supplier = Supplier::select('id', 'c_name', 'name')->find($request->supplier_id);

                    $folderName = 'sustainability/supplier/power-chalan/' . str_replace(" ", "_", $supplier->c_name) . '-' . $supplier->id . '/';
                    $file = $request->file('chalan_url');
                    $fileName = str_replace(" ", "_", $supplier->c_name) . '-' . $supplier->id . '-' . time() . '.' . $file->getClientOriginalExtension();

                    // Full path in S3
                    $filePath = $folderName . $fileName;

                    // Upload the file using helper
                    $return_data = $this->uploadToS3($filePath, $file->get());

                    if (isset($return_data['error'])) {
                        return response()->json($return_data, 500);
                    }

                    // Save to database or continue logic
                    $data['chalan_url'] = $return_data['path'];

                   
                }

                if ($request->hasFile('e-bill')) {
                    $supplier = supplier::select('id', 'c_name', 'name')->find($request->supplier_id);
                    $folderName = 'sustainability/supplier/power-ebill/' . str_replace(" ", "_", $supplier->c_name) . '-' . $supplier->id. '/';
                    $file = $request->file('e-bill');
                    $fileName = str_replace(" ", "_", $supplier->c_name) . '-' . $supplier->id . '-' . time() . '.' . $file->getClientOriginalExtension();

                   // Full path in S3
                    $filePath = $folderName . $fileName;

                    // Upload the file using helper
                    $return_data = $this->uploadToS3($filePath, $file->get());

                    if (isset($return_data['error'])) {
                        return response()->json($return_data, 500);
                    }

                    $data['bill_url'] = $return_data['path'];
                }
                
                $route = 'sustainability.stage2.power-consumption';
            } else {
                $request->merge(['units' => 'Km']);
                $request->merge(['number_of_rounds' => $request->number_of_rounds]);

                $data = $request->only([
                    'supplier_id',
                    'type',
                    'units',
                    'distance_travelled',
                    'number_of_rounds',
                    'carbon_emission',
                    'month_year',
                    'vehicle_type',
                    'fuel_type',
                ]);
                $route = 'sustainability.stage2.fuel-consumption';
            }

            // Update the existing record
            $sustainabilityStage2->update($data);

            return redirect()->route($route)->with('success', 'Sustainability Stage2 data updated successfully.');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Failed to update sustainability stage2 data: ' . $e->getMessage());
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(SustainabilityStage2 $sustainabilityStage2)
    {
        try {
            // Delete the specified record
            $sustainabilityStage2->delete();
            return redirect()->back()->with('success', 'Sustainability Stage2 data deleted successfully.');

        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Failed to delete sustainability stage2 data: ' . $e->getMessage());
        }
    }

    /**
     * Export Fuel Consumption Record based on filter.
     */

    public function exportFuelConsumption(Request $request)
    {
        $tab = $request->input('tab');
        $month = $request->input('month') ?? "";
        $year = $request->input('year') ?? "";

        $search = $request->input('search') ?? "";
        $date_from = $request->input('date-from') ?? "";
        $date_to = $request->input('date-to') ?? "";
        if ($tab === "monthly") {
            $monthName = $month ? date("M", mktime(0, 0, 0, $month, 10)) : 'AllMonths';
            $yearSelected = $year ?? 'AllYears';
            $filename = "stage2-fuel-consumption-monthly-{$monthName}-{$yearSelected}.xlsx";

            return Excel::download(new SustainabilityStage2ExportFuelConsumptionMonthly($search, $month, $year), $filename);
        } else {
            $from = $date_from ? date('Ymd', strtotime($date_from)) : 'start';
            $to = $date_to ? date('Ymd', strtotime($date_to)) : 'end';
            $filename = "stage2-fuel-consumption-yearly-{$from}-to-{$to}.xlsx";
            return Excel::download(new SustainabilityStage2ExportFuelConsumptionYearly($search, $date_from, $date_to),$filename);
        }
    }

    /**
     * Export Power Consumption Record based on filter.
     */

    public function exportPowerConsumption(Request $request)
    {
        $tab = $request->input('tab');
        $month = $request->input('month') ?? "";
        $year = $request->input('year') ?? "";

        $search = $request->input('search') ?? "";
        $date_from = $request->input('date-from') ?? "";
        $date_to = $request->input('date-to') ?? "";
        if ($tab === "monthly") {
            $monthName = $month ? date("M", mktime(0, 0, 0, $month, 10)) : 'AllMonths';
            $yearSelected = $year ?? 'AllYears';
            $filename = "stage2-power-consumption-monthly-{$monthName}-{$yearSelected}.xlsx";

            return Excel::download(new SustainabilityStage2ExportPowerConsumptionMonthly($search, $month, $year), $filename);
        } else {
            $from = $date_from ? date('Ymd', strtotime($date_from)) : 'start';
            $to = $date_to ? date('Ymd', strtotime($date_to)) : 'end';
            $filename = "stage2-power-consumption-yearly-{$from}-to-{$to}.xlsx";
            return Excel::download(new SustainabilityStage2ExportPowerConsumptionYearly($search, $date_from, $date_to), $filename);
        }
    }


}
