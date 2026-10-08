<?php

namespace App\Services\InwardSupply;

use Carbon\Carbon;

/**
 * Mirrors purchase bill inward eligibility (7 days before delivery) for consumable/carton POs.
 */
class InwardEligibility
{
    /**
     * First calendar day inward is allowed, or null if no delivery date is set.
     *
     * @param  object|null  $order  e.g. purchaseOrderConsumable with del_date / delivery_date / podate
     */
    public static function firstEligibleInwardDate(?object $order): ?Carbon
    {
        if (!$order) {
            return null;
        }

        $rawDeliveryDate = null;
        foreach (['del_date', 'delivery_date', 'podate'] as $field) {
            if (!empty($order->{$field})) {
                $rawDeliveryDate = $order->{$field};
                break;
            }
        }

        if (empty($rawDeliveryDate)) {
            return null;
        }

        try {
            $deliveryDate = Carbon::parse($rawDeliveryDate)->startOfDay();
        } catch (\Throwable $e) {
            return null;
        }

        return $deliveryDate->copy()->subDays(7)->startOfDay();
    }
}
