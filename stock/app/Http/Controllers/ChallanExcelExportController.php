<?php

namespace App\Http\Controllers;

use App\Challan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ChallanExcelExportController extends Controller
{
    /**
     * UTF-8 BOM CSV: one row per challan (header-level fields only) for Microsoft Excel.
     *
     * @return \Illuminate\Http\Response|\Symfony\Component\HttpFoundation\StreamedResponse
     */
    public function export(Request $request)
    {
        $fsd = $request->input('fsd');
        $fed = $request->input('fed');

        if (! $fsd || ! $fed || $fsd > $fed) {
            return redirect()->back()->with('error', 'Invalid date range for challan export.');
        }

        $challans = Challan::query()
            ->with(['supplier', 'purchaseOrder'])
            ->where(function ($q) use ($fsd, $fed) {
                $q->whereBetween('challan_date', [$fsd, $fed])
                    ->orWhere(function ($q2) use ($fsd, $fed) {
                        $q2->whereNull('challan_date')
                            ->whereBetween(DB::raw('DATE(created_at)'), [$fsd, $fed]);
                    });
            })
            ->orderByRaw('COALESCE(challan_date, DATE(created_at)) ASC')
            ->orderBy('id')
            ->get();

        $filename = 'challan_excel_' . $fsd . '_' . $fed . '.csv';

        $headers = [
            'Content-Type'        => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ];

        $poStatusLabel = static function ($status) {
            if ($status === null || $status === '') {
                return '';
            }

            return (int) $status === 1 ? 'Completed' : 'Pending';
        };

        $challanStatusLabel = static function ($status) {
            if ((int) $status === 2) {
                return 'Cancelled by Supplier';
            }

            return 'Active';
        };

        return response()->stream(function () use ($challans, $poStatusLabel, $challanStatusLabel) {
            $out = fopen('php://output', 'w');
            fprintf($out, chr(0xEF) . chr(0xBB) . chr(0xBF));

            fputcsv($out, [
                'PO No',
                'Supplier Invoice / Delivery No',
                'Supplier Name',
                'Supplier GSTIN',
                'Challan Date',
                'Vehicle No',
                'Total Quantity',
                'Sub Total',
                'Total GST',
                'Grand Total',
                'PO Status',
                'PO Remarks',
                'Challan Status',
            ]);

            foreach ($challans as $challan) {
                $po = $challan->purchaseOrder;
                fputcsv($out, [
                    $po->pono ?? '',
                    $challan->challan_number ?? '',
                    optional($challan->supplier)->c_name ?? '',
                    optional($challan->supplier)->gstin ?? '',
                    $challan->challan_date ?? '',
                    $challan->vehicle_no ?? '',
                    $challan->tquantity ?? '',
                    $challan->subTotal ?? '',
                    $challan->tgst ?? '',
                    $challan->tamount ?? '',
                    $po ? $poStatusLabel($po->status ?? null) : '',
                    $po->remarks ?? '',
                    $challanStatusLabel($challan->status ?? null),
                ]);
            }

            fclose($out);
        }, 200, $headers);
    }
}
