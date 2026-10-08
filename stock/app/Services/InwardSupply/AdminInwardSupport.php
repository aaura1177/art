<?php

namespace App\Services\InwardSupply;

use App\supplierInvoice;

class AdminInwardSupport
{
    public static function existingActiveSupplierInvoiceConflict(int $supplierId, string $suppInvNo, string $ewayBill): bool
    {
        $suppInvNo = strtoupper(trim($suppInvNo));
        $ewayBill = strtoupper(trim($ewayBill));

        $query = supplierInvoice::where('supplier_id', $supplierId)
            ->where(function ($q) use ($suppInvNo, $ewayBill) {
                $q->where('supplier_invoice_number', $suppInvNo);
                if ($ewayBill !== '') {
                    $q->orWhere('eway_bill_no', $ewayBill);
                }
            })
            ->where(function ($q) {
                $q->where('status', '!=', 2)
                    ->orWhereNull('status');
            });

        return $query->exists();
    }

    public static function newSupplierInvoiceReferenceNumber(): string
    {
        do {
            $referenceNumber = 'SI-' . now()->format('Ymd') . '-' . strtoupper(substr(str_shuffle('ABCDEFGHJKLMNPQRSTUVWXYZ23456789'), 0, 6));
        } while (supplierInvoice::where('reference_number', $referenceNumber)->exists());

        return $referenceNumber;
    }

    /**
     * Same rounding as furniture purchase bill approve when TDS is present.
     */
    public static function roundTds(mixed $tdsTotal): ?float
    {
        if ($tdsTotal === null || $tdsTotal === '') {
            return null;
        }
        $number = (float) $tdsTotal;
        $intPart = floor($number);
        $decimal = $number - $intPart;

        return (float) ($decimal < 0.5 ? $intPart : $intPart + 1);
    }
}
