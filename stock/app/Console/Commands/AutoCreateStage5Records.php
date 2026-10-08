<?php

namespace App\Console\Commands;

use App\Console\Commands\Concerns\ResolvesSustainabilityCommandMonth;
use Illuminate\Console\Command;
use App\Port;
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
use Carbon\Carbon;
use App\SustainabilityVariable;

class AutoCreateStage5Records extends Command
{
    use ResolvesSustainabilityCommandMonth;

    protected $signature = 'sustainability:autocreate-stage5 {--month= : Target month as YYYY-MM (default: current month)}';
    protected $description = 'Auto-create Sustainability Stage 5 records based on port data';

    public function handle()
    {
        try {
            try {
                $monthStart = $this->sustainabilityCommandMonth($this->option('month'));
            } catch (\InvalidArgumentException $e) {
                $this->error($e->getMessage());

                return self::FAILURE;
            }

            $startOfMonth = $monthStart->toDateString();
            $endOfMonth = $monthStart->copy()->endOfMonth()->toDateString();
            $processedMonth = $monthStart->format('Y-m-d');
    
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
                    ->where('invoiceno', 'not like', '%TEMP%') // 👈 exclude TEMP invoices
                    ->whereRaw('(containerno IS NULL OR UPPER(TRIM(containerno)) != ?)', ['TBC']) // align with Stage 5 modal / aggregates
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
    
            \Log::info('Sustainability Stage0 updated or created successfully.');
        } catch (\Exception $e) {
            \Log::info('Error: ' . $e->getMessage());
        }
    }
    

}

