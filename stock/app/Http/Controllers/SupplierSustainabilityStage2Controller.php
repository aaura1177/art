<?php

namespace App\Http\Controllers;

use App\SustainabilityStage2;
use Illuminate\Http\Request;
use App\SustainabilityVariable;
use App\supplier;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;
use Auth;
use App\Http\Controllers\Concerns\UploadsSustainabilityFilesToS3;

class SupplierSustainabilityStage2Controller extends Controller
{
    use UploadsSustainabilityFilesToS3;
    /**
     * Display a listing of the resource.
     */
    public function fuelIndex(Request $request)
    {

        $tab = $request->input('tab');
        $search = $request->input('search');

        $query = SustainabilityStage2::where('type', 'Transport')
                ->where('created_by','Supplier')
                ->where('created_by_uid',Auth::user()->id)
                ->with('supplierInfo')
                ->when($search, function ($query) use ($search) {
                $query->whereHas('supplierInfo', function ($q) use ($search) {
                    $q->where('c_name', 'like', '%' . $search . '%');
                });
            });

        $date_from = $request->input('date-from');
        $date_to = $request->input('date-to');

        if ($date_from && $date_to) {
            $query->whereBetween('created_at', [$date_from, $date_to]);
        }
        

        $records = $query->orderBy('created_at', 'desc')->paginate(200);

        return view("ProductionLogs.fuelIndex", compact("records"));
    }


    public function powerIndex(Request $request)
    {
    
        $search = $request->input('search');

        $query = SustainabilityStage2::where('type', 'Electricity')
            ->where('created_by','Supplier')
            ->where('created_by_uid',Auth::user()->id)
            ->with('supplierInfo')
            ->when($search, function ($query) use ($search) {
                $query->whereHas('supplierInfo', function ($q) use ($search) {
                    $q->where('c_name', 'like', '%' . $search . '%');
                });
            });

        $date_from = $request->input('date-from');
        $date_to = $request->input('date-to');

        if ($date_from && $date_to) {
            $query->whereBetween('created_at', [$date_from, $date_to]);
        }
    
        $records = $query->orderBy('created_at', 'desc')->paginate(50);

        return view("ProductionLogs.powerIndex", compact("records"));
    }


    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        // Return the create view for SustainabilityStage2
        $sustainabilityVariable = SustainabilityVariable::first();
        $supplier = supplier::select('id', 'c_name', 'name')->orderBy('c_name', 'asc')->where('id',Auth::user()->supplier_id)->first();
        return view("ProductionLogs.create", compact("sustainabilityVariable", "supplier"));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $rules = [
            'supplier_id' => 'required|numeric',
            'type' => 'required',
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
            $request->merge(['created_by' => "Supplier"]);
            $request->merge(['created_by_uid' => Auth::user()->id]);

            // Check if the sustainability variable record exists
            $sustainabilityVariable = SustainabilityVariable::first();
            if ($sustainabilityVariable) {
                if ($request->input('type') == 'Electricity') {
                     if($request->is_solar== '1') {
                            $carbonEmission = $request->power_consumed * $sustainabilityVariable->carbon_emission_rate_for_solar;
                            $request->merge(['carbon_emission' => $carbonEmission]);

                     }else{
                            $carbonEmission = $request->power_consumed * $sustainabilityVariable->carbon_emission_factor_per_kwh;
                            $request->merge(['carbon_emission' => $carbonEmission]);
                     }
                } else {

                    if($request->fuel_type == 'Diesel') {
                        $carbonEmission = $request->distance_travelled * $request->number_of_rounds * $sustainabilityVariable->carbon_emission_rate_for_diesel;
                    } elseif ($request->fuel_type == 'Petrol') {
                        $carbonEmission = $request->distance_travelled * $request->number_of_rounds * $sustainabilityVariable->carbon_emission_rate_for_petrol;
                    } elseif ($request->fuel_type == 'Electricity') {
                        $carbonEmission = $request->distance_travelled * $request->number_of_rounds * $sustainabilityVariable->carbon_emission_rate_for_electronic;
                    } 
                    elseif ($request->fuel_type == 'Hybrid') {
                        $carbonEmission = $request->distance_travelled * $request->number_of_rounds * $sustainabilityVariable->carbon_emission_rate_for_hybrid;
                    } 
                    elseif ($request->fuel_type == 'CNG') {
                        $carbonEmission = $request->distance_travelled * $request->number_of_rounds * $sustainabilityVariable->carbon_emission_rate_for_cng;
                    } 

                    $request->merge(['carbon_emission' => $carbonEmission]);
                }
            }else{
                $request->merge(['carbon_emission' => "0"]);
            }
           
            
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
                    'is_solar'
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

            SustainabilityStage2::Create($data);
            return redirect()->route('production.logs.create')->with('success', 'Data created successfully.');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Failed to save Data: ' . $e->getMessage());
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(SustainabilityStage2 $productionlogs)
    {
        // Return the show view for a specific SustainabilityStage2 record
        return view("ProductionLogs.show", compact('productionlogs'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(SustainabilityStage2 $productionlogs)
    {
        // Return the edit view for a specific SustainabilityStage2 record
        $sustainabilityVariable = SustainabilityVariable::first();
        $record = $productionlogs;
        $supplier = supplier::select('id', 'c_name', 'name')->orderBy('c_name', 'asc')->where('id',Auth::user()->supplier_id)->first();
        return view("ProductionLogs.edit", compact('record', 'sustainabilityVariable', 'supplier'));
    }

    /**
     * Update the specified resource in storage.
     */
    
    public function update(Request $request, SustainabilityStage2 $productionlogs)
    {
        $rules = [
            'supplier_id' => 'required|numeric',
            'type' => 'required',
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
            $sustainabilityVariable = SustainabilityVariable::first();
             if ($sustainabilityVariable) {
                if ($request->input('type') == 'Electricity') {
                     if($request->is_solar== '1') {
                            $carbonEmission = $request->power_consumed * $sustainabilityVariable->carbon_emission_rate_for_solar;
                            $request->merge(['carbon_emission' => $carbonEmission]);

                     }else{
                            $carbonEmission = $request->power_consumed * $sustainabilityVariable->carbon_emission_factor_per_kwh;
                            $request->merge(['carbon_emission' => $carbonEmission]);
                     }
                } else {

                    if($request->fuel_type == 'Diesel') {
                        $carbonEmission = $request->distance_travelled * $request->number_of_rounds * $sustainabilityVariable->carbon_emission_rate_for_diesel;
                    } elseif ($request->fuel_type == 'Petrol') {
                        $carbonEmission = $request->distance_travelled * $request->number_of_rounds * $sustainabilityVariable->carbon_emission_rate_for_petrol;
                    } elseif ($request->fuel_type == 'Electricity') {
                        $carbonEmission = $request->distance_travelled * $request->number_of_rounds * $sustainabilityVariable->carbon_emission_rate_for_electronic;
                    } 
                    elseif ($request->fuel_type == 'Hybrid') {
                        $carbonEmission = $request->distance_travelled * $request->number_of_rounds * $sustainabilityVariable->carbon_emission_rate_for_hybrid;
                    } 
                    elseif ($request->fuel_type == 'CNG') {
                        $carbonEmission = $request->distance_travelled * $request->number_of_rounds * $sustainabilityVariable->carbon_emission_rate_for_cng;
                    } 
                    
                    $request->merge(['carbon_emission' => $carbonEmission]);
                }
            }else{
                $request->merge(['carbon_emission' => "0"]);
            }
            
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
                
                $route = 'production.logs.power-consumption';
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
                $route = 'production.logs.fuel-consumption';
            }

            // Update the existing record
            $productionlogs->update($data);

            return redirect()->route($route)->with('success', 'Data updated successfully.');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Failed to update Data: ' . $e->getMessage());
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(SustainabilityStage2 $productionlogs)
    {
        try {
            // Delete the specified record
            $productionlogs->delete();
            return redirect()->back()->with('success', 'Data deleted successfully.');

        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Failed to delete Data: ' . $e->getMessage());
        }
    }

}
