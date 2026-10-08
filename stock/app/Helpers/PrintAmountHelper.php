<?php

namespace App\Helpers;

/**
 * Print-time GST round-off: bridges stored totals vs CGST/SGST shown at 2 decimals.
 */
class PrintAmountHelper
{
    public static function gstRoundOff(
        float $taxableAmount,
        float $tgst,
        float $tamount,
        bool $intraState,
        float $freight = 0.0
    ): float {
        $taxable = round($taxableAmount + $freight, 2);
        $tax = round($tgst, 2);
        $total = round($tamount, 2);

        if ($intraState) {
            $half = round($tax / 2, 2);
            $sum = round($taxable + $half + $half, 2);
        } else {
            $sum = round($taxable + $tax, 2);
        }

        return round($total - $sum, 2);
    }

    public static function shouldShowRoundOff(float $roundOff): bool
    {
        return abs($roundOff) >= 0.01;
    }

    /**
     * Footer Total (₹) before round-off: subtotal + CGST + SGST (intra-state), else subtotal + IGST.
     * CGST/SGST are each round(tgst / 2, 2) so the sum matches printed column values.
     */
    public static function footerTotalSubtotalPlusGst(float $subtotal, float $tgst, bool $intraState): float
    {
        if ($intraState) {
            $cgst = round($tgst / 2, 2);
            $sgst = round($tgst / 2, 2);

            return round($subtotal + $cgst + $sgst, 2);
        }

        return round($subtotal + round($tgst, 2), 2);
    }

    public static function formatAmount(float $amount): string
    {
        return str_replace(',', '', number_format($amount, 2));
    }

    /** Two decimal places for print/PDF amounts (qty and gstslab % excluded). */
    public static function formatViewAmount($value): string
    {
        if ($value === null || $value === '') {
            return '0.00';
        }
        if (is_string($value) && ! is_numeric($value)) {
            return $value;
        }

        return self::formatAmount((float) $value);
    }

    /**
     * Use stored roundoff when set (furniture SI/PB); otherwise derive from totals.
     */
    public static function resolvePrintRoundOff(
        $storedRoundoff,
        float $taxableAmount,
        float $tgst,
        float $tamount,
        bool $intraState,
        float $freight = 0.0
    ): float {
        if ($storedRoundoff !== null && $storedRoundoff !== '' && abs((float) $storedRoundoff) >= 0.01) {
            return round((float) $storedRoundoff, 2);
        }

        return self::gstRoundOff($taxableAmount, $tgst, $tamount, $intraState, $freight);
    }
}
