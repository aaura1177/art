<?php

namespace App\Console\Commands;

use App\Console\Commands\Concerns\FetchesStage6WpOrderQuantities;
use App\Console\Commands\Concerns\ResolvesSustainabilityCommandMonth;
use Illuminate\Console\Command;
use App\SustainabilityStage6;
use App\SustainabilityVariable;
class AutoCreateStage6RecordsIn extends Command
{
    use FetchesStage6WpOrderQuantities;
    use ResolvesSustainabilityCommandMonth;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sustainability:auto-create-stage6-records-in {--month= : Target month as YYYY-MM (default: current month)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        try {
            $monthStart = $this->sustainabilityCommandMonth($this->option('month'));
        } catch (\InvalidArgumentException $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        }

        $startOfMonth = $monthStart->toDateString();
        $endOfMonth = $monthStart->copy()->endOfMonth()->toDateString();
        $location = "IN";

        $total_parcel_delivered = $this->fetchCompletedParcelTotal(env('IN_URL'), $startOfMonth, $endOfMonth);
        if ($total_parcel_delivered === null) {
            return;
        }

        $sustainabilityVariable = SustainabilityVariable::first();
        if (! $sustainabilityVariable) {
            \Log::info('Sustainability variable not found. Please set it up first.');

            return;
        }

        $carbonEmission = $total_parcel_delivered * $sustainabilityVariable->carbon_emission_per_kg_parcel_in;

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

    }
}
