<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Exports\SustainabilitySummaryExport;
use App\Support\SustainabilityCountryEmissions;
use Maatwebsite\Excel\Facades\Excel;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class SustainabilitySummaryController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        // Final usage with request fallback
        $today = Carbon::now();

        // If current month is before April, use previous year as financial year start
        $financialYearStart = $today->month < 4 ? Carbon::create($today->year - 1, 4, 1) : Carbon::create($today->year, 4, 1);

        // Financial year end is always March 31 next year
        $financialYearEnd = $financialYearStart->copy()->addYear()->subDay(); // March 31 of next year

        // Final usage with request fallback
        $startDate = $request->input('date-from') ?? $financialYearStart->toDateString();
        $endDate = $request->input('date-to') ?? $financialYearEnd->toDateString();

        // Calculate the number of months in the range
        if ($request->input('date-from')) {
            $months = Carbon::parse($request->input('date-from'))->startOfMonth()->diffInMonths(Carbon::parse($request->input('date-to'))->startOfMonth()) + 1;
        } else {
            $months = Carbon::parse($financialYearStart->toDateString())->startOfMonth()->diffInMonths(Carbon::parse(date('Y-m-d'))->startOfMonth()) + 1;
        }

        $stages = [
            'stage0' => 'Tree plantation and carbon sequestration impact',
            'stage1' => 'Upstream Raw Material Emissions. Covering emissions from sourcing raw timber and operations at the sawmill, including transportation to the artisans',
            'stage2' => 'Artisan Production Emissions. Emissions associated with the artisanal crafting and initial processing of the furniture',
            'stage3' => 'Factory Finishing Emissions. Encompassing emissions from the Jaipur factory for polishing, hardware fixation, packaging, and other finishing processes',
            'stage4' => 'Transport and Maritime Emissions. Accounting for emissions from transporting the products to the Indian port and the subsequent sea voyage to the destination port',
            'stage5' => 'Inbound Logistics Emissions. Emissions from the final leg of transportation from the destination port to the local distribution centre',
            'stage6' => 'Last Mile Distribution Emissions. Focused on the emissions from delivering the products from the Ipswich warehouse to the end customer',
        ];

        $records = ['data' => []];
        $totalEmissions = 0;
        $sequestrationTotal = 0;
        $total_qty_delivered_per_month = 0;
        $total_container_sent = 0;
        $savedS123Period = 0.0;
        $savedS4Period = 0.0;
        $savedS5Period = 0.0;

        foreach ($stages as $table => $process) {
            if ($table === 'stage0') {
                $emission = DB::table("sustainability_" . $table)
                    ->whereBetween('month_year', [$startDate, $endDate])
                    ->whereNull('deleted_at')
                    ->sum('carbon_sequestration');

                $total_qty_delivered_per_month = DB::table("sustainability_" . $table)
                    ->whereBetween('month_year', [$startDate, $endDate])
                    ->whereNull('deleted_at')
                    ->sum('total_qty_delivered_by_containers');
            } else {
                $emission = DB::table("sustainability_" . $table)
                    ->whereBetween('month_year', [$startDate, $endDate])
                    ->whereNull('deleted_at')
                    ->sum('carbon_emission');

                if ($table === 'stage3') {
                    $emission_stage3_miscellaneous = DB::table("sustainability_" . $table . "_miscellaneous")
                        ->whereBetween('month_year', [$startDate, $endDate])
                        ->whereNull('deleted_at')
                        ->sum('carbon_emission');

                    $emission = $emission + $emission_stage3_miscellaneous;

                    $emission_stage3_employee = DB::table("sustainability_" . $table . "_employee")
                        ->whereBetween('month_year', [$startDate, $endDate])
                        ->whereNull('deleted_at')
                        ->sum('carbon_emission');

                    $emission = $emission + $emission_stage3_employee;
                }

                if ($table === 'stage5') {
                    $total_container_sent = DB::table("sustainability_" . $table)
                        ->whereBetween('month_year', [$startDate, $endDate])
                        ->whereNull('deleted_at')
                        ->sum('container_sent');
                }
            }

            $monthly = $emission / $months;

            $records['data'][] = [
                'name' => $table,
                'process' => $process,
                'carbon_emission_monthly' => round($monthly, 5),
            ];

            if ($table === 'stage0') {
                $sequestrationTotal = $monthly;
            } else {
                $totalEmissions += $monthly;

                if (in_array($table, ['stage1', 'stage2', 'stage3'], true)) {
                    $savedS123Period += (float) $emission;
                } elseif ($table === 'stage4') {
                    $savedS4Period = (float) $emission;
                } elseif ($table === 'stage5') {
                    $savedS5Period = (float) $emission;
                }
            }
        }

        $avg_monthly_containers = ($months > 0) ? round($total_container_sent / $months, 2) : 0;
        $emission_per_container = ($avg_monthly_containers > 0) ? round($totalEmissions / $avg_monthly_containers, 5) : "0";

        $avg_pieces_per_container = ($total_container_sent > 0)
            ? ($total_qty_delivered_per_month / $total_container_sent) : 0;
        $per_product_emission = ($avg_pieces_per_container > 0)
            ? round($emission_per_container / $avg_pieces_per_container, 5) : "0";

        $countryBreakdown = SustainabilityCountryEmissions::countryBreakdown(
            $startDate,
            $endDate,
            $savedS123Period,
            $savedS4Period,
            $savedS5Period,
            (float) $total_qty_delivered_per_month
        );

        $perProductByCountry = [];
        foreach ($countryBreakdown['countries'] as $row) {
            $perProductByCountry["Per Product Emissions ({$row['country']})"] = $row['per_product'];
        }

        $records['avg'] = array_merge([
            'Total carbon emission per month' => round($totalEmissions, 5),
            'Average Monthly Sequestration' => round($sequestrationTotal, 5),
            'Net Emissions' => round($sequestrationTotal - $totalEmissions, 5),
            'Emission Per Container' => $emission_per_container,
            'Per Product Emissions (Overall)' => $per_product_emission,
        ], $perProductByCountry);

        $records['date-range'] = date("M Y", strtotime($startDate)) . " to " . date("M Y", strtotime($endDate));
        $records['country_breakdown'] = $countryBreakdown;

        return view('Sustainability.summary.index', compact('records'));
    }

    /**
     * Export Summary Report based on filter
     */
    public function export(Request $request)
    {
        $search = $request->input('search') ?? "";

        $today = Carbon::now();

        // If current month is before April, use previous year as financial year start
        $financialYearStart = $today->month < 4 ? Carbon::create($today->year - 1, 4, 1) : Carbon::create($today->year, 4, 1);

        // Financial year end is always March 31 next year
        $financialYearEnd = $financialYearStart->copy()->addYear()->subDay(); // March 31 of next year

        // Final usage with request fallback
        $startDate = $request->input('date-from') ?? $financialYearStart->toDateString();
        $endDate = $request->input('date-to') ?? $financialYearEnd->toDateString();

        // Calculate the number of months in the range
        if ($request->input('date-from')) {
            $months = Carbon::parse($request->input('date-from'))->startOfMonth()->diffInMonths(Carbon::parse($request->input('date-to'))->startOfMonth()) + 1;
        } else {
            $months = Carbon::parse($financialYearStart->toDateString())->startOfMonth()->diffInMonths(Carbon::parse(date('Y-m-d'))->startOfMonth()) + 1;
        }

        $title = date("M-Y", strtotime($startDate)) . "-to-" . date("M-Y", strtotime($endDate));

        $filename = "Carbon-Emission-Report-" . $title;

        return Excel::download(new SustainabilitySummaryExport($search, $startDate, $endDate, $months), $filename . '.xlsx');
    }
}
