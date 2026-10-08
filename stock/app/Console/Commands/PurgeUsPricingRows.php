<?php

namespace App\Console\Commands;

use App\pricingTable;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class PurgeUsPricingRows extends Command
{
    protected $signature = 'pricing:purge-us-rows
                            {--apply : Delete US destination pricing rows (default is dry-run)}';

    protected $description = 'Remove pricingTable rows with US destination (dry-run by default).';

    public function handle(): int
    {
        $apply = (bool) $this->option('apply');
        $rows = pricingTable::query()
            ->whereRaw('LOWER(TRIM(destination)) = ?', ['us'])
            ->orderBy('id')
            ->get();

        if ($rows->isEmpty()) {
            $this->info('No US destination pricing rows found.');

            return self::SUCCESS;
        }

        $productIds = $rows->pluck('product_id')->unique()->values();
        $onlyUsProducts = [];
        foreach ($productIds as $productId) {
            $hasNonUs = pricingTable::where('product_id', $productId)
                ->whereRaw('LOWER(TRIM(destination)) != ?', ['us'])
                ->exists();
            if (! $hasNonUs) {
                $onlyUsProducts[] = $productId;
            }
        }

        $this->line($apply ? '=== APPLY MODE ===' : '=== DRY RUN (pass --apply to delete) ===');
        $this->info('US pricing rows to delete: ' . $rows->count());
        $this->info('Distinct products affected: ' . $productIds->count());
        $this->warn('Products with ONLY US rows (no UK/other left after delete): ' . count($onlyUsProducts));

        $headers = ['id', 'product_id', 'productType', 'buyer_id2', 'courierType', 'courierCost', 'newDelCost'];
        $this->table($headers, $rows->take(50)->map(function ($r) {
            return [
                $r->id,
                $r->product_id,
                $r->productType,
                $r->buyer_id2,
                $r->courierType,
                $r->courierCost,
                $r->newDelCost,
            ];
        })->all());

        if ($rows->count() > 50) {
            $this->comment('... and ' . ($rows->count() - 50) . ' more rows.');
        }

        if (! $apply) {
            $this->comment('No changes made. Re-run with --apply to delete.');

            return self::SUCCESS;
        }

        if (! $this->confirm('Delete ' . $rows->count() . ' US pricing row(s)?', false)) {
            $this->comment('Aborted.');

            return self::SUCCESS;
        }

        $deleted = DB::table((new pricingTable)->getTable())
            ->whereRaw('LOWER(TRIM(destination)) = ?', ['us'])
            ->delete();

        $this->info("Deleted {$deleted} US pricing row(s).");

        return self::SUCCESS;
    }
}
