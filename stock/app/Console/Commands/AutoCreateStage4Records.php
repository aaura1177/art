<?php

namespace App\Console\Commands;

use App\Console\Commands\Concerns\ResolvesSustainabilityCommandMonth;
use Illuminate\Console\Command;
use App\SustainabilityStage4;
use App\SustainabilityVariable;
use App\Support\SustainabilityStage4Postloading;
use Illuminate\Support\Facades\Log;
use App\invoice;
class AutoCreateStage4Records extends Command
{
    use ResolvesSustainabilityCommandMonth;

    protected $signature = 'sustainability:autocreate-stage4 {--month= : Target month as YYYY-MM (default: current month)}';
    protected $description = 'Auto-create Sustainability Stage 4';

    public function handle()
    {
        try 
        {
            try {
                $monthStart = $this->sustainabilityCommandMonth($this->option('month'));
            } catch (\InvalidArgumentException $e) {
                $this->error($e->getMessage());

                return self::FAILURE;
            }

            $startOfMonth = $monthStart->toDateString();
            $endOfMonth = $monthStart->copy()->endOfMonth()->toDateString();
            $processedMonth = $monthStart->format('Y-m-d');
          
            $sustainabilityVariable = SustainabilityVariable::first();
            if (!$sustainabilityVariable) {
                \Log::info("Sustainability variable not found. Please set it up first.");
                return;
            }
    
            $number_of_containers = SustainabilityStage4Postloading::constrain(
                    invoice::whereBetween('date', [$startOfMonth, $endOfMonth])
                        ->whereNotNull('date')
                        ->where('invoiceno', 'like', 'GVD/UK/%')
                        ->where('invoiceno', 'not like', '%TEMP%') // 👈 exclude TEMP invoices
                        ->whereRaw('(containerno IS NULL OR UPPER(TRIM(containerno)) != ?)', ['TBC']) // align with Stage 4 modal
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

            \Log::info("Sustainability Stage 4 record processed successfully.");
    
        } catch (\Exception $e) {
            \Log::info("Exception occurred: " . $e->getMessage());
        }
    }

}

