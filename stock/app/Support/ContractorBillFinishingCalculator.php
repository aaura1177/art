<?php

namespace App\Support;

use App\FinshingExtraPrice;
use App\finishRate;
use App\product;
use Carbon\Carbon;
use Illuminate\Support\Collection;

/**
 * Mirrors {@see resources/views/invoice/contractorbill.blade.php} finishing rate logic.
 */
class ContractorBillFinishingCalculator
{
    public static function volumeCbm(product $product): float
    {
        $h = (float) ($product->height ?? 0);
        $w = (float) ($product->width ?? 0);
        $d = (float) ($product->depth ?? 0);

        return ($h * $w * $d) / 1000000.0;
    }

    public static function extraFinishingForProduct(product $product): float
    {
        if (empty($product->finshing_extra_price_id)) {
            return 0.0;
        }
        $fep = FinshingExtraPrice::query()->find($product->finshing_extra_price_id);

        return $fep ? (float) $fep->price : 0.0;
    }

    /**
     * Round base from old_rate and volume (same as Blade: round((old_rate/60)*vol) then nearest 5).
     */
    public static function roundBaseFromOldRateAndVolume(float $oldRate, float $volumeCbm): float
    {
        $fp = (int) round(($oldRate / 60.0) * $volumeCbm);
        $rem = $fp % 5;
        if ($rem < 3) {
            $fp -= $rem;
        } else {
            $fp += (5 - $rem);
        }

        return (float) $fp;
    }

    /**
     * "Old price" path: finishRates row old_rate + volume rounding + product extra, else product.finishing_price.
     */
    public static function finishingRateOldPriceFormula(?finishRate $fr, product $product, float $volumeCbm): float
    {
        $extra = self::extraFinishingForProduct($product);
        if ($fr !== null && $fr->old_rate !== null && $fr->old_rate !== '') {
            $base = self::roundBaseFromOldRateAndVolume((float) $fr->old_rate, $volumeCbm);

            return $base + $extra;
        }

        return (float) ($product->finishing_price ?? 0);
    }

    /**
     * Same as contractor bill screen **after** cutover: {@see product::finishing_price} only — no volume/old_rate.
     */
    public static function finishingRateAlwaysNewProductPrice(product $product): float
    {
        return (float) ($product->finishing_price ?? 0);
    }

    /**
     * Rate the UI would auto-suggest for this invoice date (before cutover = old formula, else finishing_price).
     *
     * @param  Collection<string, finishRate>  $finishRatesByName  key = finishing_rates.name
     */
    public static function finishingRatePerInvoiceCutover(
        Carbon $invoiceDate,
        Carbon $cutoverStartOfDay,
        string $finishingName,
        Collection $finishRatesByName,
        product $product
    ): float {
        $useOld = $invoiceDate->copy()->startOfDay()->lt($cutoverStartOfDay);
        if ($useOld) {
            $fr = $finishRatesByName->get($finishingName);

            return self::finishingRateOldPriceFormula($fr, $product, self::volumeCbm($product));
        }

        return (float) ($product->finishing_price ?? 0);
    }

    public static function amountsClose(float $a, float $b, float $tol = 0.01): bool
    {
        return abs($a - $b) <= $tol;
    }
}
