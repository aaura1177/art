<?php

namespace App\Console\Commands\Concerns;

use Carbon\Carbon;

trait ResolvesSustainabilityCommandMonth
{
    /**
     * @param  string|null  $monthOption  Value from --month= (YYYY-MM) or null for current month
     */
    protected function sustainabilityCommandMonth(?string $monthOption): Carbon
    {
        if ($monthOption === null || trim((string) $monthOption) === '') {
            return Carbon::now()->startOfMonth();
        }

        $trimmed = trim($monthOption);
        if (! preg_match('/^\d{4}-\d{2}$/', $trimmed)) {
            throw new \InvalidArgumentException(
                'Invalid --month value. Use YYYY-MM (e.g. 2025-04).'
            );
        }

        $monthStart = Carbon::createFromFormat('Y-m', $trimmed)->startOfMonth();
        if ($monthStart->format('Y-m') !== $trimmed) {
            throw new \InvalidArgumentException(
                'Invalid --month value. Use a real calendar month (01-12), e.g. 2025-04.'
            );
        }

        return $monthStart;
    }
}
