<?php

namespace App\Http\Controllers\Concerns;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;

trait PaginatesSustainabilityMonthlyByYear
{
    protected function applyMonthlyTabFilters(Builder $query, Request $request): void
    {
        $month = $request->input('month');
        $year = $request->input('year');

        if (!empty($month) && !empty($year)) {
            $query->where(
                'month_year',
                '=',
                $year . '-' . str_pad((string) $month, 2, '0', STR_PAD_LEFT) . '-01'
            );
        } elseif (!empty($year)) {
            $query->whereYear('month_year', $year);
        } elseif (!empty($month)) {
            $query->whereMonth('month_year', $month);
        }
    }

    protected function applyYearlyTabFilters(Builder $query, Request $request): void
    {
        $date_from = $request->input('date-from');
        $date_to = $request->input('date-to');

        if ($date_from && $date_to) {
            $query->whereBetween('month_year', [$date_from, $date_to]);
        }
    }

    protected function financialYearStartFromMonthYear(string $monthYear): int
    {
        $timestamp = strtotime($monthYear);
        $month = (int) date('n', $timestamp);
        $year = (int) date('Y', $timestamp);

        return $month >= 4 ? $year : $year - 1;
    }

    protected function financialYearDateRange(int $financialYearStart): array
    {
        return [
            sprintf('%d-04-01', $financialYearStart),
            sprintf('%d-03-01', $financialYearStart + 1),
        ];
    }

    protected function formatFinancialYearLabel(int $financialYearStart): string
    {
        return $financialYearStart . '–' . substr((string) ($financialYearStart + 1), -2);
    }

    protected function buildFinancialYearPageLabels($financialYearStarts): array
    {
        $labels = [];

        foreach ($financialYearStarts as $index => $financialYearStart) {
            $labels[$index + 1] = $this->formatFinancialYearLabel((int) $financialYearStart);
        }

        return $labels;
    }

    protected function attachFinancialYearPaginationMeta(
        LengthAwarePaginator $paginator,
        $financialYearStarts,
        ?int $currentFinancialYearStart = null
    ): LengthAwarePaginator {
        $paginator->financialYearPageLabels = $this->buildFinancialYearPageLabels($financialYearStarts);

        if ($currentFinancialYearStart !== null) {
            $paginator->financialYearLabel = $this->formatFinancialYearLabel($currentFinancialYearStart);
        }

        return $paginator;
    }

    /**
     * @return LengthAwarePaginator|\Illuminate\Http\RedirectResponse
     */
    protected function paginateMonthlyByYear(Builder $query, Request $request)
    {
        $financialYearStarts = (clone $query)
            ->selectRaw(
                'CASE WHEN MONTH(month_year) >= 4 THEN YEAR(month_year) ELSE YEAR(month_year) - 1 END as fy_start'
            )
            ->distinct()
            ->orderByDesc('fy_start')
            ->pluck('fy_start')
            ->filter()
            ->values();

        $page = max(1, (int) $request->input('page', 1));
        $perPage = 1;
        $total = $financialYearStarts->count();
        $lastPage = max(1, $total);

        if ($total === 0) {
            $paginator = new LengthAwarePaginator(
                collect(),
                0,
                $perPage,
                $page,
                ['path' => $request->url()]
            );
            $paginator->withQueryString();

            return $this->attachFinancialYearPaginationMeta($paginator, $financialYearStarts);
        }

        if ($page > $lastPage) {
            return redirect()->to($request->fullUrlWithQuery(['page' => $lastPage]));
        }

        $currentFinancialYearStart = (int) $financialYearStarts->get($page - 1);
        [$rangeFrom, $rangeTo] = $this->financialYearDateRange($currentFinancialYearStart);

        $items = (clone $query)
            ->whereBetween('month_year', [$rangeFrom, $rangeTo])
            ->orderByDesc('month_year')
            ->orderByDesc('created_at')
            ->get();

        $paginator = new LengthAwarePaginator(
            $items,
            $total,
            $perPage,
            $page,
            ['path' => $request->url()]
        );
        $paginator->withQueryString();

        return $this->attachFinancialYearPaginationMeta(
            $paginator,
            $financialYearStarts,
            $currentFinancialYearStart
        );
    }
}
