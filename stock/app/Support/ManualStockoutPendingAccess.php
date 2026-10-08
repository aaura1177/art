<?php

namespace App\Support;

use App\invoice;
use Carbon\Carbon;

class ManualStockoutPendingAccess
{
    public static function isCartonInvoice(?string $invoiceno): bool
    {
        return stripos((string) $invoiceno, 'CARTON') !== false;
    }

    public static function isWithinTwoDayWindow($invoiceDate): bool
    {
        if ($invoiceDate === null || $invoiceDate === '') {
            return false;
        }
        $from = Carbon::today()->subDays(2)->startOfDay();
        $to = Carbon::today()->endOfDay();
        $d = Carbon::parse($invoiceDate);

        return $d->greaterThanOrEqualTo($from) && $d->lessThanOrEqualTo($to);
    }

    /**
     * True if another non-carton (export) invoice in the same container has stockout
     * and is strictly after this invoice (date, then id).
     */
    public static function hasLaterExportStockoutInSameContainer(invoice $inv): bool
    {
        $cn = trim((string) $inv->containerno);
        if ($cn === '') {
            return false;
        }

        $invDate = $inv->date;
        $invId = (int) $inv->id;

        $candidates = invoice::query()
            ->where('containerno', $cn)
            ->whereNotNull('containerno')
            ->where('containerno', '!=', '')
            ->where('id', '!=', $invId)
            ->whereHas('stockout')
            ->get(['id', 'date', 'invoiceno']);

        foreach ($candidates as $other) {
            if (self::isCartonInvoice($other->invoiceno)) {
                continue;
            }
            $cmp = strcmp((string) $other->date, (string) $invDate);
            if ($cmp > 0 || ($cmp === 0 && (int) $other->id > $invId)) {
                return true;
            }
        }

        return false;
    }

    /**
     * User may open detail and submit partial fulfillment.
     */
    public static function canFulfillPendingForInvoice(invoice $inv): bool
    {
        if (! self::isWithinTwoDayWindow($inv->date)) {
            return false;
        }
        if (self::hasLaterExportStockoutInSameContainer($inv)) {
            return false;
        }

        return true;
    }

    public static function lockReason(invoice $inv): ?string
    {
        if (! self::isWithinTwoDayWindow($inv->date)) {
            return 'Invoice date is outside the allowed 2-day window.';
        }
        if (self::hasLaterExportStockoutInSameContainer($inv)) {
            return 'A later export invoice in this container already has stockout — finish or resolve that sequence first.';
        }

        return null;
    }
}
