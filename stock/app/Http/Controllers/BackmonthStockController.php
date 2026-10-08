<?php

namespace App\Http\Controllers;

use App\invoice;
use App\invoiceTable;
use App\setting;
use App\stockLog;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class BackmonthStockController extends Controller
{
    private const SETTINGS_EMAILS = [
        'aaura1177@gmail.com',
        'info@globalvisioncompany.com',
        'finance@artisanfurniture.net',
    ];

    private const SIDEBAR_EMAILS = [
        'aaura1177@gmail.com',
        'info@globalvisioncompany.com',
        'finance@artisanfurniture.net',
        'factory@globalvisioncompany.com',
    ];

    public function index()
    {
        $this->abortIfNoSidebarAccess();

        $targetDate = Carbon::now()->subMonthNoOverflow()->endOfMonth()->toDateString();

        $invoices = invoice::with('buyer')
            ->whereDate('date', $targetDate)
            ->orderBy('invoiceno', 'asc')
            ->get();

        $editInfo = $this->getEditAccessInfo();

        return view('manual_stock/backmonth_index', [
            'invoices' => $invoices,
            'targetDate' => $targetDate,
            'editInfo' => $editInfo,
        ]);
    }

    public function show($invoiceId)
    {
        $this->abortIfNoSidebarAccess();

        $invoice = invoice::with('buyer')->findOrFail($invoiceId);
        $invoiceRows = invoiceTable::with('product')
            ->where('invoice_id', $invoice->id)
            ->get();

        $stockRows = collect();
        foreach ($invoiceRows as $invoiceRow) {
            $rows = stockLog::where('voucher_no', $invoice->invoiceno)
                ->where('product_id', $invoiceRow->product_id)
                ->orderBy('created_at', 'asc')
                ->get();

            foreach ($rows as $log) {
                $stockRows->push([
                    'log' => $log,
                    'invoice_row' => $invoiceRow,
                ]);
            }
        }

        $editInfo = $this->getEditAccessInfo();

        return view('manual_stock/backmonth_show', [
            'invoice' => $invoice,
            'invoiceRows' => $invoiceRows,
            'stockRows' => $stockRows,
            'editInfo' => $editInfo,
            'targetDate' => $this->resolveTargetDate($invoice)->toDateString(),
        ]);
    }

    public function cumulativeUpdate(Request $request, $invoiceId)
    {
        $this->abortIfNoSidebarAccess();

        $editInfo = $this->getEditAccessInfo();
        if (! $editInfo['can_edit'] || ! $editInfo['can_cumulative']) {
            return back()->with('danger', 'Cumulative update is not allowed for your user or current date window.');
        }

        $invoice = invoice::findOrFail($invoiceId);
        $invoiceRows = invoiceTable::where('invoice_id', $invoice->id)->get();
        $selectedLogIds = $this->parseSelectedLogIds($request->input('selected_log_ids'));
        $targetDate = $this->resolveTargetDate($invoice);
        $dayStart = $targetDate->copy()->startOfDay();
        $updatedCount = 0;

        DB::transaction(function () use ($invoice, $invoiceRows, $dayStart, $selectedLogIds, &$updatedCount) {
            foreach ($invoiceRows as $invoiceRow) {
                $logQuery = stockLog::where('voucher_no', $invoice->invoiceno)
                    ->where('product_id', $invoiceRow->product_id)
                    ->orderBy('created_at', 'asc')
                    ->orderBy('id', 'asc');

                if (! empty($selectedLogIds)) {
                    $logQuery->whereIn('id', $selectedLogIds);
                }

                $logs = $logQuery->get();

                foreach ($logs as $log) {
                    $lastSameDay = stockLog::where('product_id', $invoiceRow->product_id)
                        ->whereDate('created_at', $dayStart->toDateString())
                        ->where('id', '!=', $log->id)
                        ->orderBy('created_at', 'desc')
                        ->first();

                    $currentSameVoucher = stockLog::where('product_id', $invoiceRow->product_id)
                        ->where('voucher_no', $invoice->invoiceno)
                        ->whereDate('created_at', $dayStart->toDateString())
                        ->where('id', '<', $log->id)
                        ->orderBy('created_at', 'desc')
                        ->first();

                    $base = $dayStart->copy();
                    if ($lastSameDay && $lastSameDay->created_at) {
                        $base = Carbon::parse($lastSameDay->created_at);
                    }
                    if ($currentSameVoucher && $currentSameVoucher->created_at) {
                        $voucherBase = Carbon::parse($currentSameVoucher->created_at);
                        if ($voucherBase->greaterThan($base)) {
                            $base = $voucherBase;
                        }
                    }

                    $newCreatedAt = $base->copy()->addSecond();
                    // Store previous created_at in updated_at to support explicit revert action.
                    $previousCreatedAt = $log->created_at ? Carbon::parse($log->created_at) : null;
                    $log->created_at = $newCreatedAt;
                    $log->updated_at = $previousCreatedAt ?: Carbon::now();
                    $log->save();
                    $updatedCount++;
                }
            }
        });

        return back()->with('success', "Cumulative update completed. Updated {$updatedCount} selected stock rows.");
    }

    public function revertCumulativeUpdate(Request $request, $invoiceId)
    {
        $this->abortIfNoSidebarAccess();

        $editInfo = $this->getEditAccessInfo();
        if (! $editInfo['can_edit'] || ! $editInfo['can_cumulative']) {
            return back()->with('danger', 'Revert is not allowed for your user or current date window.');
        }

        $invoice = invoice::findOrFail($invoiceId);
        $invoiceRows = invoiceTable::where('invoice_id', $invoice->id)->get();
        $selectedLogIds = $this->parseSelectedLogIds($request->input('selected_log_ids'));
        $updatedCount = 0;

        DB::transaction(function () use ($invoice, $invoiceRows, $selectedLogIds, &$updatedCount) {
            foreach ($invoiceRows as $invoiceRow) {
                $logQuery = stockLog::where('voucher_no', $invoice->invoiceno)
                    ->where('product_id', $invoiceRow->product_id)
                    ->orderBy('id', 'asc');

                if (! empty($selectedLogIds)) {
                    $logQuery->whereIn('id', $selectedLogIds);
                }

                $logs = $logQuery->get();

                foreach ($logs as $log) {
                    if (empty($log->updated_at)) {
                        continue;
                    }

                    $restoreTs = Carbon::parse($log->updated_at);
                    $log->created_at = $restoreTs;
                    $log->updated_at = Carbon::now();
                    $log->save();
                    $updatedCount++;
                }
            }
        });

        return back()->with('success', "Revert completed. Restored {$updatedCount} selected stock rows from updated_at.");
    }

    public function individualUpdate(Request $request, $invoiceId)
    {
        $this->abortIfNoSidebarAccess();

        $editInfo = $this->getEditAccessInfo();
        if (! $editInfo['can_edit'] || ! $editInfo['can_individual']) {
            return back()->with('danger', 'Individual update is not allowed for your user or current date window.');
        }

        $validated = $request->validate([
            'rows' => 'required|array|min:1',
            'rows.*.id' => 'required|integer',
            'rows.*.created_at' => 'nullable|date',
            'rows.*.selected' => 'nullable|in:1',
        ]);

        $invoice = invoice::findOrFail($invoiceId);
        $allowedProductIds = invoiceTable::where('invoice_id', $invoice->id)->pluck('product_id')->toArray();
        $updatedCount = 0;

        DB::transaction(function () use ($validated, $invoice, $allowedProductIds, &$updatedCount) {
            foreach ($validated['rows'] as $row) {
                if (($row['selected'] ?? null) !== '1') {
                    continue;
                }
                if (empty($row['created_at'])) {
                    continue;
                }

                $log = stockLog::where('id', (int) $row['id'])
                    ->where('voucher_no', $invoice->invoiceno)
                    ->first();

                if (! $log || ! in_array((int) $log->product_id, $allowedProductIds, true)) {
                    continue;
                }

                $log->created_at = Carbon::parse($row['created_at']);
                $log->updated_at = Carbon::now();
                $log->save();
                $updatedCount++;
            }
        });

        return back()->with('success', "Individual update completed. Updated {$updatedCount} stock rows.");
    }

    public static function sidebarAllowedEmails(): array
    {
        return self::SIDEBAR_EMAILS;
    }

    private function abortIfNoSidebarAccess(): void
    {
        $email = strtolower((string) (auth()->user()->email ?? ''));
        if (! in_array($email, self::SIDEBAR_EMAILS, true)) {
            abort(403);
        }
    }

    private function getEditAccessInfo(): array
    {
        $email = strtolower((string) (auth()->user()->email ?? ''));
        $isPrivileged = in_array($email, self::SETTINGS_EMAILS, true);
        $isFactory = $email === 'factory@globalvisioncompany.com';

        $setting = setting::first();
        $dayLimit = (int) ($setting->backmonth_edit_day_limit ?? 3);
        if ($dayLimit <= 0) {
            $dayLimit = 3;
        }
        $withinWindow = (int) Carbon::now()->day <= $dayLimit;

        $factoryCanCumulative = (int) ($setting->backmonth_factory_can_cumulative ?? 0) === 1;
        $factoryCanIndividual = (int) ($setting->backmonth_factory_can_individual ?? 0) === 1;

        $canCumulative = $isPrivileged || ($isFactory && $factoryCanCumulative);
        $canIndividual = $isPrivileged || ($isFactory && $factoryCanIndividual);

        return [
            'day_limit' => $dayLimit,
            'within_window' => $withinWindow,
            'can_cumulative' => $canCumulative,
            'can_individual' => $canIndividual,
            'can_edit' => $withinWindow && ($canCumulative || $canIndividual),
            'is_privileged' => $isPrivileged,
            'is_factory' => $isFactory,
        ];
    }

    private function resolveTargetDate(invoice $invoice): Carbon
    {
        if (! empty($invoice->date)) {
            return Carbon::parse($invoice->date)->startOfDay();
        }

        return Carbon::now()->subMonthNoOverflow()->endOfMonth()->startOfDay();
    }

    private function parseSelectedLogIds($raw): array
    {
        if (! is_string($raw) || trim($raw) === '') {
            return [];
        }

        $ids = explode(',', $raw);
        $ids = array_map('trim', $ids);
        $ids = array_filter($ids, static function ($id) {
            return $id !== '' && ctype_digit($id);
        });
        $ids = array_map('intval', $ids);

        return array_values(array_unique($ids));
    }
}

