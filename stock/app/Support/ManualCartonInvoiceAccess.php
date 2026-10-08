<?php

namespace App\Support;

use App\invoice;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class ManualCartonInvoiceAccess
{
    public static function dateWindow(): array
    {
        return [
            Carbon::today()->subDays(2)->toDateString(),
            Carbon::today()->toDateString(),
        ];
    }

    public static function isEligibleForUi(invoice $inv): bool
    {
        [$from, $to] = self::dateWindow();
        if ($inv->date === null || $inv->date === '') {
            return false;
        }
        $d = Carbon::parse($inv->date)->toDateString();
        if ($d < $from || $d > $to) {
            return false;
        }
        if (! ManualStockoutPendingAccess::canFulfillPendingForInvoice($inv)) {
            return false;
        }

        return true;
    }

    /**
     * Oldest invoice in container that still needs manual carton and passes UI rules.
     */
    public static function activeInvoiceForContainer(string $container, Collection $candidates): ?invoice
    {
        $ordered = $candidates
            ->filter(fn (invoice $inv) => trim((string) $inv->containerno) === trim($container))
            ->sortBy(fn (invoice $inv) => sprintf('%s-%010d', (string) $inv->date, (int) $inv->id))
            ->values();

        foreach ($ordered as $inv) {
            if (self::isEligibleForUi($inv)) {
                return $inv;
            }
        }

        return null;
    }
}
