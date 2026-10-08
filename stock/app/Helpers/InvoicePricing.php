<?php

namespace App\Helpers;

use App\buyer;
use App\pricingTable;

class InvoicePricing
{
    /**
     * Resolve pricing destination (buyer_id2) for an invoice buyer.
     * Returns null when the buyer is not assigned on a temp buyer.
     */
    public static function resolveTempBuyerIdForInvoiceBuyer($invoiceBuyerId): ?int
    {
        if ($invoiceBuyerId === null || $invoiceBuyerId === '') {
            return null;
        }

        $tempBuyerId = buyer::where('id', (int) $invoiceBuyerId)->value('temp_buyer_id');

        if ($tempBuyerId === null || $tempBuyerId === '') {
            return null;
        }

        return (int) $tempBuyerId;
    }

    /**
     * Pre-tariff FOB India Cost (fobINCost) for invoice line rate.
     */
    public static function getFobInCostForInvoiceBuyer(int $productId, $invoiceBuyerId): ?float
    {
        $tempBuyerId = self::resolveTempBuyerIdForInvoiceBuyer($invoiceBuyerId);
        if ($tempBuyerId === null) {
            return null;
        }

        $pricing = pricingTable::query()
            ->where('product_id', $productId)
            ->where('buyer_id2', $tempBuyerId)
            ->where('productType', 1)
            ->orderBy('created_at', 'desc')
            ->first(['fobINCost']);

        if ($pricing && $pricing->fobINCost !== null) {
            return (float) $pricing->fobINCost;
        }

        $pricing = pricingTable::query()
            ->where('product_id', $productId)
            ->where('buyer_id2', $tempBuyerId)
            ->orderBy('created_at', 'desc')
            ->first(['fobINCost']);

        if ($pricing && $pricing->fobINCost !== null) {
            return (float) $pricing->fobINCost;
        }

        return null;
    }
}
