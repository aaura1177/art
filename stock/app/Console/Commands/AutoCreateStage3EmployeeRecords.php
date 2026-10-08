<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\SustainabilityStage5;
use App\SustainabilityStage3Employee;
use Carbon\Carbon;
use App\SustainabilityVariable;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
class AutoCreateStage3EmployeeRecords extends Command
{
    protected $signature = 'sustainability:autocreate-stage3-employee';
    protected $description = 'Auto-create Sustainability Stage 3 Employee records from HR Portal';

    public function handle()
    {
        try {
            $processedMonth = Carbon::now()->startOfMonth()->format('Y-m-d');
            $month = date("m");
            $year = date("Y");
    
            $sustainabilityVariable = SustainabilityVariable::first();
            if (!$sustainabilityVariable) {
                $this->warn("Sustainability variable not found. Please set it up first.");
                return;
            }
    
            $response = Http::timeout(10)->get(env('HRMS_URL') . "api/getActiveEmployee", [
                'month' => $month,
                'year' => $year,
            ]);
    
            if (!$response->successful()) {
                $this->error("API request failed with status: " . $response->status());
                \Log::info("API request failed with status: ");
                return;
            }
    
            $apiData = $response->json();
            $records = $apiData['data'] ?? [];
    
            if (empty($records)) {
                $this->info("No records found in API response.");
                \Log::info("No records found in API response.");
                return;
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
    
            $this->info("Employee sustainability records processed successfully.");
            \Log::info("Employee sustainability records processed successfully.");
    
        } catch (\Exception $e) {
            $this->error("Exception occurred: " . $e->getMessage());
            \Log::info("Exception occurred: " . $e->getMessage());
        }
    }

}

