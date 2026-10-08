<?php

namespace App\Support;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\Builder as QueryBuilder;

class SustainabilityStage4Postloading
{
    /**
     * Postloadings counted in Stage 4 (Mundra maritime + ICD Dhankya / Jaipur).
     */
    public static function isEligible(?string $postloading): bool
    {
        $p = strtoupper(trim((string) $postloading));

        if ($p === '') {
            return false;
        }

        if ($p === 'MUNDRA / INDIA') {
            return true;
        }

        // ICD Dhankya, Jaipur / ICD Dhanakya (spelling variants in invoices)
        return str_contains($p, 'DHANKYA') || str_contains($p, 'DHANAKYA');
    }

    /**
     * Apply Stage 4 postloading filter to an invoice query.
     *
     * @param  Builder|QueryBuilder  $query
     * @return Builder|QueryBuilder
     */
    public static function constrain($query)
    {
        return $query->where(function ($q) {
            $q->where('postloading', 'MUNDRA / INDIA')
                ->orWhereRaw('UPPER(TRIM(postloading)) LIKE ?', ['%DHANKYA%'])
                ->orWhereRaw('UPPER(TRIM(postloading)) LIKE ?', ['%DHANAKYA%']);
        });
    }
}
