<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use App\SustainabilityStage3Employee;
use App\SustainabilityVariable;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\SustainabilityStage4;
use App\invoice;
use App\invoiceus;
use App\invoiceuk;
use App\invoicecanada;
use App\invoicecalifornia;
use App\invoiceeu;
use App\SustainabilityStage5;
use App\EmissionInvoiceLogIn;
use App\EmissionInvoiceLogUs;
use App\EmissionInvoiceLogUk;
use App\EmissionInvoiceLogEu;
use App\EmissionInvoiceLogCa;
use App\EmissionInvoiceLogCalifornia;
use App\SustainabilityStage0;
use App\SustainabilityStage6;
use App\Console\Commands\Concerns\FetchesStage6WpOrderQuantities;
use App\Support\SustainabilityStage4Postloading;

class SustainabilityPopulateDataController extends Controller
{
    use FetchesStage6WpOrderQuantities;
   public function stage3(Request $request)
   {
        try 
        {
            $processedMonth = $request->input('processed_month');
            $month = $request->input('month');
            $year =  $request->input('year');
    
            $sustainabilityVariable = SustainabilityVariable::first();
            if (!$sustainabilityVariable) {
                return response()->json([
                        'status' => 0,
                        'message' => 'Sustainability variable not found. Please set it up first.',
                    ]);
            }
            
    
            $response = Http::timeout(10)->get(env('HRMS_URL') . "api/getActiveEmployee", [
                'month' => $month,
                'year' => $year,
            ]);
    
            if (!$response->successful()) {
                return response()->json([
                        'status' => 0,
                        'message' => 'API request failed with status:',
                    ]);
            }
    
            $apiData = $response->json();
            $records = $apiData['data'] ?? [];
    
            if (empty($records)) {
                return response()->json([
                        'status' => 0,
                        'message' => 'No records found in API response.',
                    ]);
            }
        
            foreach ($records as $item) {
                $empId = $item['id'];

                if($item['fuel_type'] == 'Diesel') {
                   $emissionRate = $sustainabilityVariable->carbon_emission_rate_for_diesel;
                } elseif ($item['fuel_type'] == 'Petrol') {
                    $emissionRate = $sustainabilityVariable->carbon_emission_rate_for_petrol;
                } elseif ($item['fuel_type'] == 'Electricity') {
                    $emissionRate = $sustainabilityVariable->carbon_emission_rate_for_electronic;
                } 
                elseif ($item['fuel_type'] == 'Hybrid') {
                    $emissionRate = $sustainabilityVariable->carbon_emission_rate_for_hybrid;
                } 
                elseif ($item['fuel_type'] == 'CNG') {
                    $emissionRate = $sustainabilityVariable->carbon_emission_rate_for_cng;
                } 

                $distanceTravelled = $item['one_way_distance'] * $item['number_of_rounds'] * $item['attendance_count'];
                $carbonEmission = $distanceTravelled * $emissionRate;
    
                $data = [
                    'emp_id' => $empId,
                    'user_name' => $item['given_name'],
                    'type' => 'Transport',
                    'units' => 'Km',
                    'distance_travelled' => $item['one_way_distance'],
                    'number_of_rounds' => $item['number_of_rounds'],
                    'carbon_emission' => $carbonEmission,
                    'days' => $item['attendance_count'],
                    'fuel_type' => $item['fuel_type'],
                    'vehicle_type' => $item['vehicle_type'],
                    'month_year' => $processedMonth,
                ];
    
                SustainabilityStage3Employee::updateOrCreate(
                    ['emp_id' => $empId, 'month_year' => $processedMonth],
                    $data
                );
            }
    
            return response()->json([
                        'status' => 1,
                        'message' => 'Employee sustainability records processed successfully.',
                    ]);
    
        } catch (\Exception $e) {
             return response()->json([
                        'status' => 0,
                        'message' => "Exception occurred: " . $e->getMessage(),
                    ]);
        }
   }
   public function stage4(Request $request)
   {
        try 
        {
            $startOfMonth = $request->input('startOfMonth');
            $endOfMonth = $request->input('endOfMonth');          
            $processedMonth = $request->input('processed_month');
           
            $sustainabilityVariable = SustainabilityVariable::first();
            if (!$sustainabilityVariable) {
                return response()->json([
                        'status' => 0,
                        'message' => 'Sustainability variable not found. Please set it up first.',
                    ]);
            }
    
            $number_of_containers = SustainabilityStage4Postloading::constrain(
                    invoice::whereBetween('date', [$startOfMonth, $endOfMonth])
                        ->whereNotNull('date')
                        ->where('invoiceno', 'like', 'GVD/UK/%')
                        ->where('invoiceno', 'not like', '%TEMP%')
                        ->whereRaw('(containerno IS NULL OR UPPER(TRIM(containerno)) != ?)', ['TBC'])
                        ->where('is_canceled', 0)
                )
                ->count();

            $carbonEmission = $number_of_containers * $sustainabilityVariable->mundra_port_distance * $sustainabilityVariable->carbon_emission_rate;

            SustainabilityStage4::updateOrCreate(
                ['month_year' => $processedMonth],
                [
                    'number_of_containers' => $number_of_containers,
                    'carbon_emission' => $carbonEmission,
                ]
            );

            return response()->json([
                        'status' => 1,
                        'message' => 'Sustainability Stage 4 record processed successfully.',
                    ]);
    
        } catch (\Exception $e) {
            return response()->json([
                        'status' => 0,
                        'message' => "Exception occurred: " . $e->getMessage(),
                    ]);
        }
   }
   public function stage5(Request $request)
   {
        try {
            $startOfMonth = $request->input('startOfMonth');
            $endOfMonth = $request->input('endOfMonth');          
            $processedMonth = $request->input('processed_month');
    
            $siteModelMap = [
                'Us'         => ['invoice' => invoiceus::class,        'log' => EmissionInvoiceLogUs::class],
                'Uk'         => ['invoice' => invoiceuk::class,        'log' => EmissionInvoiceLogUk::class],
                'Eu'         => ['invoice' => invoiceeu::class,        'log' => EmissionInvoiceLogEu::class],
                'Ca'         => ['invoice' => invoicecanada::class,     'log' => EmissionInvoiceLogCa::class],
                'California' => ['invoice' => invoicecalifornia::class, 'log' => EmissionInvoiceLogCalifornia::class],
                'In'         => ['invoice' => invoice::class,           'log' => EmissionInvoiceLogIn::class],
            ];
    
            $total_qty_sent = 0;
            foreach ($siteModelMap as $models) 
            {
                $invoiceModel = $models['invoice'];
                $logModel = $models['log'];
    
                // Get IDs of already processed invoices to avoid redundant log checks
                $processedIds = $logModel::where('processed_month', $processedMonth)
                    ->pluck('invoice_id')
                    ->toArray();
    
                $invoiceQuery = $invoiceModel::with('portInfo')
                    ->whereBetween('date', [$startOfMonth, $endOfMonth])
                    ->whereNotNull('date')
                    ->whereHas('portInfo')
                    ->where('invoiceno', 'like', 'GVD/UK/%')
                    ->where('invoiceno', 'not like', '%TEMP%')
                    ->whereRaw('(containerno IS NULL OR UPPER(TRIM(containerno)) != ?)', ['TBC'])
                    ->where('is_canceled',0)
                    ->orderByDesc('date');
    
                foreach ($invoiceQuery->lazy() as $invoice) {
                    if (in_array($invoice->id, $processedIds)) {
                        continue;
                    }
    
                    $port = $invoice->portInfo;
                    if (!$port) {
                        continue;
                    }
    
                    $monthYear = Carbon::parse($invoice->date)->format('Y-m') . '-01';
    
                    $record = SustainabilityStage5::firstOrNew([
                        'port_id' => $port->id,
                        'month_year' => $monthYear,
                    ]);
    
                    $record->container_sent += 1;
                    $record->total_weight_delivered += $invoice->totalwt;
                    $record->total_qty_sent += $invoice->totalquantity;
                    $record->carbon_emission = $record->container_sent * $port->carbon_emission_per_container;
                    $record->save();
    
                    $logModel::create([
                        'invoice_id' => $invoice->id,
                        'processed_month' => $processedMonth,
                    ]);
    
                    \Log::info(($record->wasRecentlyCreated ? 'Created' : 'Updated') . " record for port ID {$port->id}");
                }
            }
    
            $stage5_record = SustainabilityStage5::where('month_year', $processedMonth)->get();
            if ($stage5_record->isNotEmpty()) {
                $total_weight_deliverd = $stage5_record->sum('total_weight_delivered');
                $total_qty_sent = $stage5_record->sum('total_qty_sent');
                $carbon_sequestration = null;


                $variable = SustainabilityVariable::first();
                if ($variable) {

                   $carbon_sequestration = $variable->carbon_content * $variable->conversion_factor * $total_weight_deliverd;
                    
                    SustainabilityStage0::updateOrCreate(
                        ['month_year' => $processedMonth],
                        [
                            'total_qty_delivered_by_containers' => $total_qty_sent,
                            'total_weight_delivered_in_containers' => $total_weight_deliverd,
                            'carbon_sequestration' => $carbon_sequestration,
                        ]
                    );
                }
            }


            return response()->json([
                        'status' => 1,
                        'message' => 'Sustainability Stage5 and stage0 updated successfully.',
                    ]);
        } catch (\Exception $e) {
            return response()->json([
                        'status' => 0,
                        'message' => "Exception occurred: " . $e->getMessage(),
                    ]);
        }
   }
   public function stage6(Request $request)
   {
        try
        {
            $startOfMonth = $request->input('startOfMonth');
            $endOfMonth = $request->input('endOfMonth');
            $location = $request->input('location');

            $shopBaseUrl = match ($location) {
                'CA' => env('CA_URL'),
                'EU' => env('EU_URL'),
                'IN' => env('IN_URL'),
                'UK' => env('UK_URL'),
                'US' => env('US_URL'),
                default => env('UK_URL'),
            };

            $total_parcel_delivered = $this->fetchCompletedParcelTotal($shopBaseUrl, $startOfMonth, $endOfMonth);
            if ($total_parcel_delivered === null) {
                return response()->json([
                    'status' => 0,
                    'message' => 'Failed to fetch order quantities (completed) from shop API.',
                ]);
            }

            $sustainabilityVariable = SustainabilityVariable::first();
            if (! $sustainabilityVariable) {
                return response()->json([
                    'status' => 0,
                    'message' => 'Sustainability variable not found. Please set it up first.',
                ]);
            }

            if ($location == "CA") {
                $carbonEmission = $total_parcel_delivered * $sustainabilityVariable->carbon_emission_per_kg_parcel_ca;
            } elseif ($location == "EU") {
                $carbonEmission = $total_parcel_delivered * $sustainabilityVariable->carbon_emission_per_kg_parcel_eu;
            } elseif ($location == "IN") {
                $carbonEmission = $total_parcel_delivered * $sustainabilityVariable->carbon_emission_per_kg_parcel_in;
            } elseif ($location == "UK") {
                $carbonEmission = $total_parcel_delivered * $sustainabilityVariable->carbon_emission_per_kg_parcel_uk;
            } elseif ($location == "US") {
                $carbonEmission = $total_parcel_delivered * $sustainabilityVariable->carbon_emission_per_kg_parcel_us;
            } else {
                $carbonEmission = $total_parcel_delivered * $sustainabilityVariable->carbon_emission_per_kg_parcel_in;
            }

            $data = [
                'parcel_delivered' => $total_parcel_delivered,
                'location' => $location,
                'carbon_emission' => $carbonEmission,
                'month_year' => $startOfMonth,
            ];

            $existingRecord = SustainabilityStage6::where('location', $location)
                ->where('month_year', '=', $startOfMonth)
                ->first();
            if ($existingRecord) {
                $existingRecord->update($data);
                \Log::info('Record updated for location '.$location);
            } else {
                SustainabilityStage6::Create($data);
                \Log::info('Record added for location '.$location);
            }

            return response()->json([
                'status' => 1,
                'message' => "Sustainability Stage6 updated or created successfully.",
            ]);

        } catch (\Exception $e) {
            return response()->json([
                        'status' => 0,
                        'message' => "Exception occurred: " . $e->getMessage(),
                    ]);
        }
   }

}
