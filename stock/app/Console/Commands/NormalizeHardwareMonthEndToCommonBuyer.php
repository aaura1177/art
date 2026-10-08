<?php

namespace App\Console\Commands;

use App\consumable;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * One-off / maintenance: hardware month-end consumables → M-series Common path.
 *
 * Sets hardware_monthend_po_product = 0 and monthEndpo_buyer = 3 (Common) so Month End PO
 * with Common selected picks them up without the separate Hardware Month End PO flow.
 */
class NormalizeHardwareMonthEndToCommonBuyer extends Command
{
    /** monthEndpo_buyer: Common */
    private const BUYER_COMMON = 3;

    protected $signature = 'consumables:normalize-hardware-monthend-to-common
                            {--apply : Persist updates (default is preview only)}
                            {--id= : Limit to one consumables.id}
                            {--include-no-supplier : Also update hardware-flagged rows with no monthEndpo_supplier}';

    protected $description = 'Set hardware month-end consumables to buyer Common (3) and hardware_monthend_po_product = 0.';

    public function handle(): int
    {
        if (! Schema::hasColumn('consumables', 'hardware_monthend_po_product')) {
            $this->error('Column consumables.hardware_monthend_po_product does not exist.');

            return self::FAILURE;
        }

        if (! Schema::hasColumn('consumables', 'monthEndpo_buyer')) {
            $this->error('Column consumables.monthEndpo_buyer does not exist.');

            return self::FAILURE;
        }

        $apply = (bool) $this->option('apply');
        $singleId = $this->option('id') !== null && $this->option('id') !== ''
            ? (int) $this->option('id')
            : null;
        $includeNoSupplier = (bool) $this->option('include-no-supplier');

        $query = consumable::query()
            ->where('hardware_monthend_po_product', 1);

        if ($singleId) {
            $query->where('id', $singleId);
        }

        if (! $includeNoSupplier) {
            $query->whereNotNull('monthEndpo_supplier')
                ->where('monthEndpo_supplier', '!=', 0);
        }

        $rows = $query->orderBy('id')->get();

        if ($rows->isEmpty()) {
            $this->warn('No consumables matched (hardware_monthend_po_product = 1'
                . ($includeNoSupplier ? ').' : ' with monthEndpo_supplier set).'));

            return self::SUCCESS;
        }

        $this->info($apply ? 'APPLY mode — updating database.' : 'DRY-RUN — no database writes.');
        $this->line('Matched rows: ' . $rows->count());
        if (! $includeNoSupplier) {
            $this->line('Scope: hardware flag = 1 and monthEndpo_supplier is set (use --include-no-supplier to widen).');
        }
        $this->newLine();

        $tableRows = [];
        $toUpdate = [];

        foreach ($rows as $row) {
            $currentBuyer = $this->buyerCodeFromRow($row);
            $needsHwOff = (int) ($row->hardware_monthend_po_product ?? 0) === 1;
            $needsBuyer = $currentBuyer !== self::BUYER_COMMON;

            if (! $needsHwOff && ! $needsBuyer) {
                continue;
            }

            $toUpdate[] = $row;
            $tableRows[] = [
                $row->id,
                mb_substr((string) ($row->name ?? ''), 0, 40),
                (int) ($row->monthEndpo_supplier ?? 0) ?: '—',
                $needsHwOff ? '1' : '0',
                $currentBuyer === null ? 'null' : (string) $currentBuyer,
                '0',
                (string) self::BUYER_COMMON,
            ];
        }

        if ($tableRows === []) {
            $this->info('All matched rows already have hardware = 0 and buyer = Common (3). Nothing to do.');

            return self::SUCCESS;
        }

        $this->table(
            ['ID', 'Name', 'ME supplier', 'HW (was)', 'Buyer (was)', 'HW (new)', 'Buyer (new)'],
            $tableRows
        );

        $this->newLine();
        $this->line('Rows to update: ' . count($toUpdate));

        if (! $apply) {
            $this->comment('Re-run with --apply to save changes.');

            return self::SUCCESS;
        }

        if (! $this->option('no-interaction') && ! $this->confirm('Apply updates to ' . count($toUpdate) . ' consumable(s)?', true)) {
            $this->warn('Aborted.');

            return self::SUCCESS;
        }

        $updated = 0;

        DB::transaction(function () use ($toUpdate, &$updated) {
            foreach ($toUpdate as $row) {
                consumable::where('id', $row->id)->update([
                    'hardware_monthend_po_product' => 0,
                    'monthEndpo_buyer' => self::BUYER_COMMON,
                ]);
                $updated++;
            }
        });

        $this->info("Updated {$updated} consumable(s).");
        $this->line('They will appear on Month End PO when Common is selected (same supplier + wf mapping).');

        return self::SUCCESS;
    }

    private function buyerCodeFromRow(consumable $consumable): ?int
    {
        $raw = $consumable->getAttributes()['monthEndpo_buyer'] ?? null;
        if ($raw === null || $raw === '') {
            return null;
        }
        if (is_int($raw) || is_float($raw)) {
            $i = (int) $raw;

            return in_array($i, [1, 2, 3], true) ? $i : null;
        }
        if (is_string($raw) && is_numeric(str_replace(' ', '', $raw))) {
            $i = (int) round((float) trim($raw));

            return in_array($i, [1, 2, 3], true) ? $i : null;
        }

        return null;
    }
}
