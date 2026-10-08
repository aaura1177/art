<?php

namespace App\Console\Commands;

use App\pricingTable;
use App\Support\DestinationPricingPolicy;
use Illuminate\Console\Command;

class ReconcileIndiaPricing extends Command
{
    protected $signature = 'pricing:reconcile-india {--dry-run : Count matching rows without changing them}';

    protected $description = 'Seed the India cost stack on every pricing row and apply the India-only Final Cost plus Courier policy to India rows';

    public function handle(): int
    {
        if ($this->option('dry-run')) {
            $this->info(pricingTable::count().' pricing row(s) would get an India cost stack.');
            $this->info(
                pricingTable::whereRaw('LOWER(TRIM(destination)) = ?', ['india'])->count()
                .' India row(s) would also be repriced.'
            );

            return self::SUCCESS;
        }

        $seeded = 0;
        $repriced = 0;

        pricingTable::query()->orderBy('id')->chunkById(200, function ($rows) use (&$seeded, &$repriced): void {
            foreach ($rows as $row) {
                // Every row carries the India stack because the block lives in
                // the shared pricing form; only India rows change their totals.
                pricingTable::where('id', $row->id)->update(
                    DestinationPricingPolicy::indiaCostStack($row->getAttributes())
                );
                $seeded++;

                if (DestinationPricingPolicy::isIndia($row->destination)) {
                    $row->refresh()->save();
                    $repriced++;
                }
            }
        });

        $this->info($seeded.' pricing row(s) seeded with the India cost stack.');
        $this->info($repriced.' India pricing row(s) repriced.');

        return self::SUCCESS;
    }
}
