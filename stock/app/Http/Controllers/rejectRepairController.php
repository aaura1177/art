<?php

namespace App\Http\Controllers;

use App\product;
use App\rejectRepair;
use App\RejectRepairPtable;
use App\stockLog;
use App\supplierInvoiceProduct;
use App\supplier;
use App\supplierInvoice;
use App\User;
use App\purchaseOrder;
use App\purchaseBill;
use App\Batch;
use App\BatchProduct;
use App\poTable;
use App\setting;
use App\pbTable;
use App\samplePurchaseOrder;
use App\samplePurchaseBill;
use Illuminate\Http\Request;
use App\Mail\DebitNoteMail;
use Mail;
use Illuminate\Support\Facades\DB;



class rejectRepairController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth', '2fa']);
    }

    public function create(Request $request)
    {
        $product = product::get();
        $supplier = supplier::get();
        if ($request->Ajax()) {
            if (isset($request->btn)) {
                $id = $request->p_id;
                $product = product::find($id);
                return $product->quantity;
            } else {
                $supplier_id          = $request->supplier_id;
                $purchaseOrderIds     = purchaseOrder::where('supplier_id', $supplier_id)->pluck('id');
                $samplePurchaseOrder  = samplePurchaseOrder::where('supplier_id', $supplier_id)->pluck('id');
                $samplePurchaseBills  = samplePurchaseBill::whereIn('purchaseOrder_id', $samplePurchaseOrder)->pluck('swap_id');

                $purchaseBills = purchaseBill::whereIn('purchaseOrder_id', $purchaseOrderIds)
                    ->orWhereIn('id', $samplePurchaseBills->all())
                    ->orderBy('created_at', 'desc')
                    // Include both keys so the frontend can show whichever exists
                    ->get(['id', 'supp_inv_no', 'created_at']);

                return $purchaseBills;
            }
        }


        return view('rejectRepair/create', ['products' => $product, 'supplier' => $supplier]);
    }
    public function data(Request $request)
    {
        $batchFlag = 0;
        $productsData = [];
 
        if (isset($request->supplier_inv_id)) {
            $purchasebill = purchaseBill::find($request->supplier_inv_id);
    
            if ($purchasebill) {
                $supplierInvoice = supplierInvoice::where('supplier_invoice_number', $purchasebill->supp_inv_no)
                    ->orderBy('id', 'desc')
                    ->first();

                if ($supplierInvoice && $supplierInvoice->batch_no != null) {
                    $batchFlag = 1;
                }

                $supplierInvoiceProducts = pbTable::where('purchasebill_id', $request->supplier_inv_id)->with('product')->get();

                foreach ($supplierInvoiceProducts as $p) {
                    $productsData[] = [
                        'id' => $p->product_id,
                        'product_name' => $p->product->name ?? '',
                        'product_code' => $p->product->code ?? '',
                        'quantity' => $p->receiveqty ?? 1,
                        'po_no' => $p->mulitple_po_purchaseBill ?? '',
                        'mulitple_po_purchaseBill' => $p->mulitple_po_purchaseBill ?? '',
                    ];
                }
            }
        } else {
            $prod = product::where('quantity', '>', 0)->get();
            foreach ($prod as $p) {
                $productsData[] = [
                    'id' => $p->id,
                    'product_name' => $p->name,
                    'product_code' => $p->code,
                    'quantity' => 1,
                    'po_no' => '',
                    'mulitple_po_purchaseBill' => '',
                ];
            }
        }

        
        return response()->json([
            'product' => $productsData,
            'batch_flag' => $batchFlag,
        ]);
    }


    public function store(Request $request)
    {
        if ($request['send_to_supplier'] == null) {
            $request['send_to_supplier'] = 0;
        }

        $poRows = is_array($request['po'] ?? null) ? $request['po'] : [];
        $skipBatch = collect($poRows)->pluck('product')->contains(3068);

        $selectedSupplierInvoice = null;
        $batch_no = null;
        if (!$skipBatch) {
            $inv_no = strtoupper(trim($request['supplier_inv_no']));
            $selectedSupplierInvoice = supplierInvoice::where('supplier_invoice_number', $inv_no)->first();
            if (!$selectedSupplierInvoice) {
                return redirect()->back()->with('error', 'No batch found for this Supplier Invoice Number.');
            }
            $batch_no = $selectedSupplierInvoice->batch_no;
        }

        DB::beginTransaction();
        try {
            $q = rejectRepair::create([
                'supplier_id' => $request['supplier_id'],
                'supplier_inv_no' => strtoupper($request['supplier_inv_no']),
                'purchase_bill_id' => $request['supplier_inv_id'],
                'status' => $request['status'],
                'date' => $request['date'],
                'outward_challan_no' => $request['outward_challan_no'],
                'send_to_supplier' => $request['send_to_supplier'],
            ]);

            $supplier = supplier::find($request['supplier_id']);
            if (! $supplier) {
                throw new \RuntimeException('Supplier not found.');
            }

            foreach ($poRows as $po) {
                $requestedQty = (int) ($po['quantity'] ?? 0);
                if ($requestedQty <= 0) {
                    throw new \RuntimeException('Invalid quantity for product ' . (int) ($po['product'] ?? 0) . '.');
                }

                $sal = new RejectRepairPtable();
                $sal->product_id = $po['product'];
                $sal->reject_repair_id = $q->id;
                $sal->quantity = $requestedQty;
                $sal->remarks = $po['remarks'];
                $sal->po_no = $po['po_id'] ?? '';
                $sal->status = $request['status'];
                if ((int) $request['status'] === 1) {
                    $sal->pending_for_dn = 1;
                }
                $sal->purchase_bill_id = $request['supplier_inv_id'];
                $sal->send_to_supplier = $request['send_to_supplier'];
                $sal->save();

                $product = product::whereKey((int) $po['product'])->lockForUpdate()->first();
                if (! $product) {
                    throw new \RuntimeException('Product not found: ' . (int) $po['product']);
                }

                $processedBatches = [];
                $selectedBatchList = [];
                if (isset($po['batch']) && is_array($po['batch']) && count($po['batch']) > 0) {
                    $selectedBatchList = array_values(array_filter(array_map('strval', $po['batch'])));
                } elseif (! empty($batch_no)) {
                    $selectedBatchList = [trim((string) $batch_no)];
                }
                $mode = ((int) $request['status'] === 3) ? 'Convert' : (((int) $request['status'] === 1) ? 'Reject' : 'Repair');

                // For Repair rows, validate available qty at add time (no deduction here).
                if ((int) $request['status'] === 2) {
                    if ($selectedBatchList === []) {
                        throw new \RuntimeException('No batch selected for repair product ' . (int) $po['product']);
                    }

                    $availableTotal = 0;
                    foreach ($selectedBatchList as $bn) {
                        $availableTotal += $this->availableQtyForRejectRepairOnBatch(
                            (int) $product->id,
                            (string) $bn,
                            $selectedSupplierInvoice ? (int) $selectedSupplierInvoice->id : null
                        );
                    }
                    if ($requestedQty > $availableTotal) {
                        throw new \RuntimeException(
                            'Insufficient batch/ref qty for repair product ' . (int) $po['product'] .
                            ' (requested ' . $requestedQty . ', available ' . $availableTotal . ').'
                        );
                    }
                }

                // Only Reject/Convert deduct here; Repair is handled during debit-note create.
                if (in_array((int) $request['status'], [1, 3], true)) {
                    if ($selectedBatchList === []) {
                        throw new \RuntimeException('No batch selected for product ' . (int) $po['product']);
                    }

                    $remainingQty = (int) ($po['quantity'] ?? 0);
                    foreach ($selectedBatchList as $bn) {
                        if ($remainingQty <= 0) {
                            break;
                        }
                        $dedRes = $this->applyRejectRepairDeductionOnBatch(
                            $product,
                            $supplier,
                            (int) $q->id,
                            strtoupper((string) $request['supplier_inv_no']),
                            $mode,
                            $bn,
                            $remainingQty,
                            $selectedSupplierInvoice ? (int) $selectedSupplierInvoice->id : null
                        );
                        if (($dedRes['deducted'] ?? 0) > 0) {
                            $processedBatches[] = (string) $bn;
                            $remainingQty -= (int) $dedRes['deducted'];
                        }
                    }

                    if ($remainingQty > 0) {
                        throw new \RuntimeException('Insufficient batch/ref stock for product ' . (int) $po['product'] . ' (short by ' . $remainingQty . ').');
                    }
                }

                if ((int) $request['status'] === 2) {
                    // For Repair, save selected/fallback batches so debit-note step can deduct later.
                    $sal->batch_no = !empty($selectedBatchList) ? json_encode(array_values(array_unique($selectedBatchList))) : null;
                } else {
                    $sal->batch_no = !empty($processedBatches) ? json_encode(array_values(array_unique($processedBatches))) : null;
                }
                $sal->save();
            }

            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            return redirect()->back()->with('error', $e->getMessage());
        }

        if ((int) $request['status'] === 1 || (int) $request['status'] === 2) {
            return redirect('/rejectRepair')->with('success', 'Product marked for Repair/Rejection.');
        }
        if ((int) $request['status'] === 3) {
            return redirect('/rejectRepair')->with('success', 'Product marked for Conversion.');
        }
    }

    private function deductUniqueRefRemainingQtyForProductOnBatch(
        int $supplierInvoiceId,
        int $productId,
        int $batchId,
        int $maxDeduct
    ): int {
        if ($maxDeduct <= 0 || $supplierInvoiceId <= 0 || $productId <= 0 || $batchId <= 0) {
            return 0;
        }

        $totalD = 0;
        $remaining = $maxDeduct;
        while ($remaining > 0) {
            $row = DB::table('unique_referencenumber as ur')
                ->join('unique_referencenumber_product as urp', 'urp.unique_referencenumber_id', '=', 'ur.id')
                ->where('ur.batch_id', $batchId)
                ->where('ur.supplier_invoice_id', $supplierInvoiceId)
                ->where('urp.product_id', $productId)
                ->where('urp.remaining_qty', '>', 0)
                ->orderBy('ur.id')
                ->orderBy('urp.id')
                ->select('ur.id as ur_id', 'ur.remqty as ur_remqty', 'urp.id as urp_id', 'urp.remaining_qty')
                ->lockForUpdate()
                ->first();

            if (! $row) {
                break;
            }

            $urpRq = (int) ($row->remaining_qty ?? 0);
            if ($urpRq <= 0) {
                break;
            }
            $urRem = (int) ($row->ur_remqty ?? 0);

            $d = min($remaining, $urpRq);
            if ($urRem > 0) {
                $d = min($d, $urRem);
            }
            if ($d <= 0) {
                break;
            }

            DB::table('unique_referencenumber_product')
                ->where('id', (int) $row->urp_id)
                ->update([
                    'remaining_qty' => $urpRq - $d,
                    'updated_at' => now(),
                ]);

            DB::table('unique_referencenumber')
                ->where('id', (int) $row->ur_id)
                ->update([
                    'remqty' => max(0, $urRem - $d),
                    'updated_at' => now(),
                ]);

            $totalD += $d;
            $remaining -= $d;
        }

        return $totalD;
    }

    /**
     * Shared stock/ref deduction on one batch for reject/repair/convert.
     *
     * @return array{deducted:int}
     */
    private function applyRejectRepairDeductionOnBatch(
        product $product,
        supplier $supplier,
        int $entityId,
        string $voucherNo,
        string $refNo,
        string $batchNo,
        int $requestedQty,
        ?int $preferredSupplierInvoiceId = null
    ): array {
        $batchNo = trim((string) $batchNo);
        if ($batchNo === '' || $requestedQty <= 0) {
            return ['deducted' => 0];
        }

        $batch = Batch::where('batch_no', $batchNo)->lockForUpdate()->first();
        if (! $batch || (int) ($batch->quantity ?? 0) <= 0) {
            return ['deducted' => 0];
        }

        $batchProduct = BatchProduct::where('batch_id', (int) $batch->id)
            ->where('product_id', (int) $product->id)
            ->lockForUpdate()
            ->first();
        if (! $batchProduct || (int) ($batchProduct->quantity ?? 0) <= 0) {
            return ['deducted' => 0];
        }

        $deductQty = min((int) $requestedQty, (int) $batchProduct->quantity);
        if ($deductQty <= 0) {
            return ['deducted' => 0];
        }

        $open_balance = (int) ($product->quantity ?? 0);

        $batchProduct->quantity = (int) $batchProduct->quantity - $deductQty;
        $batchProduct->save();

        $batch->quantity = (int) $batch->quantity - $deductQty;
        $batch->save();

        $product->quantity = (int) $product->quantity - $deductQty;
        $product->save();

        $remainingDeduct = (int) $deductQty;
        $usedRefQtyBySupplierInvoiceId = [];
        $availableRefQtyBySupplierInvoiceId = [];

        $rows = DB::table('unique_referencenumber as ur')
            ->join('unique_referencenumber_product as urp', 'urp.unique_referencenumber_id', '=', 'ur.id')
            ->join('supplier_invoices as si', 'si.id', '=', 'ur.supplier_invoice_id')
            ->where('ur.batch_id', (int) $batch->id)
            ->where('urp.product_id', (int) $product->id)
            ->where('urp.remaining_qty', '>', 0)
            ->where('si.is_approved', 1)
            ->select('si.id as supplier_invoice_id', DB::raw('SUM(COALESCE(urp.remaining_qty,0)) as avail'))
            ->groupBy('si.id')
            ->get();
        foreach ($rows as $r) {
            $sid = (int) ($r->supplier_invoice_id ?? 0);
            if ($sid > 0) {
                $availableRefQtyBySupplierInvoiceId[$sid] = (int) ($r->avail ?? 0);
            }
        }

        $orderedInvoices = supplierInvoice::where('batch_no', $batchNo)
            ->where('is_approved', 1)
            ->orderBy('id')
            ->get();

        if ($preferredSupplierInvoiceId && $orderedInvoices->count() > 1) {
            $preferred = $orderedInvoices->firstWhere('id', (int) $preferredSupplierInvoiceId);
            if ($preferred) {
                $orderedInvoices = $orderedInvoices->sortBy(function ($row) use ($preferredSupplierInvoiceId) {
                    return ((int) $row->id === (int) $preferredSupplierInvoiceId) ? 0 : 1;
                })->values();
            }
        }

        foreach ($orderedInvoices as $supInv) {
            if ($remainingDeduct <= 0) {
                break;
            }
            $sid = (int) $supInv->id;
            $dUrp = $this->deductUniqueRefRemainingQtyForProductOnBatch(
                $sid,
                (int) $product->id,
                (int) $batch->id,
                (int) $remainingDeduct
            );
            if ($dUrp > 0) {
                $usedRefQtyBySupplierInvoiceId[$sid] = ($usedRefQtyBySupplierInvoiceId[$sid] ?? 0) + (int) $dUrp;
                $remainingDeduct -= (int) $dUrp;
            }
        }

        if ($remainingDeduct > 0) {
            throw new \RuntimeException(
                'Ref deduction incomplete for batch ' . $batchNo . '. Re-open ref selection and ensure enough available qty.'
            );
        }

        $referenceNumbers = [];
        foreach ($orderedInvoices as $supInv) {
            $sid = (int) $supInv->id;
            $used = (int) ($usedRefQtyBySupplierInvoiceId[$sid] ?? 0);
            if ($used <= 0) {
                continue;
            }
            $total = (int) ($availableRefQtyBySupplierInvoiceId[$sid] ?? 0);
            if ($total <= 0) {
                $total = $used;
            }
            $referenceNumbers[] = ($supInv->supplier_invoice_number ?? '') . '(' . $used . '/' . $total . ')';
        }
        $referenceNumber = implode(', ', array_filter($referenceNumbers));

        stockLog::create([
            'product_id'      => (int) $product->id,
            'voucher_no'      => strtoupper($voucherNo),
            'ref_no'          => $refNo,
            'quantity'        => $deductQty,
            'opening_balance' => $open_balance,
            'remaining_stock' => $product->quantity,
            'type'            => 2,
            'entity_id'       => $entityId,
            'supplier_name'   => $supplier->c_name,
            'batch_no'        => $batchNo,
            'batch_balance'   => $batchProduct->quantity,
            'reference_number'=> $referenceNumber,
        ]);

        return ['deducted' => (int) $deductQty];
    }

    /**
     * Non-mutating available qty estimate for reject/repair on a batch:
     * effective availability is constrained by both batch_product quantity and unique-ref pool.
     */
    private function availableQtyForRejectRepairOnBatch(
        int $productId,
        string $batchNo,
        ?int $preferredSupplierInvoiceId = null
    ): int {
        $batchNo = trim((string) $batchNo);
        if ($productId <= 0 || $batchNo === '') {
            return 0;
        }

        $batch = Batch::where('batch_no', $batchNo)->first();
        if (! $batch) {
            return 0;
        }

        $batchProduct = BatchProduct::where('batch_id', (int) $batch->id)
            ->where('product_id', $productId)
            ->first();
        if (! $batchProduct) {
            return 0;
        }
        $batchAvail = max(0, (int) ($batchProduct->quantity ?? 0));
        if ($batchAvail <= 0) {
            return 0;
        }

        $query = DB::table('unique_referencenumber as ur')
            ->join('unique_referencenumber_product as urp', 'urp.unique_referencenumber_id', '=', 'ur.id')
            ->join('supplier_invoices as si', 'si.id', '=', 'ur.supplier_invoice_id')
            ->where('ur.batch_id', (int) $batch->id)
            ->where('urp.product_id', $productId)
            ->where('urp.remaining_qty', '>', 0)
            ->where('si.is_approved', 1);

        // Prefer exact selected invoice when available; fallback to all approved if no rows for preferred.
        if ($preferredSupplierInvoiceId) {
            $preferredRefAvail = (int) (clone $query)
                ->where('si.id', (int) $preferredSupplierInvoiceId)
                ->sum(DB::raw('COALESCE(urp.remaining_qty,0)'));
            if ($preferredRefAvail > 0) {
                return min($batchAvail, $preferredRefAvail);
            }
        }

        $refAvail = (int) $query->sum(DB::raw('COALESCE(urp.remaining_qty,0)'));
        return min($batchAvail, max(0, $refAvail));
    }

    public function createDebitNote($id)
    {
        $rejectRepair = RejectRepairPtable::find($id);
        if (! $rejectRepair) {
            return redirect()->back()->with('error', 'Reject/Repair line not found.');
        }
        $rejRepair = rejectRepair::find($rejectRepair->reject_repair_id);
        if (! $rejRepair) {
            return redirect()->back()->with('error', 'Reject/Repair parent not found.');
        }
        if ((int) ($rejectRepair->is_debit_note ?? 0) === 1) {
            return redirect('/rejectRepair')->with('success', 'Debit note already created.');
        }

        $supplierInvoice = supplierInvoice::where('supplier_invoice_number', $rejRepair->supplier_inv_no)
            ->orderBy('id', 'desc')
            ->first();
        if (!$supplierInvoice) {
            return redirect()->back()->with('error', 'Batch not found');
        }

        DB::beginTransaction();
        try {
            // For repair (new-flow rows), validate + deduct first; only then mark debit note.
            if ($rejectRepair->id > 904 && (int) $rejRepair->status === 2) {
                $batchNos = [];
                if (! empty($rejectRepair->batch_no)) {
                    $batchNos = json_decode($rejectRepair->batch_no, true);
                }
                if (! is_array($batchNos) || $batchNos === []) {
                    if (! empty($supplierInvoice->batch_no)) {
                        $batchNos = [trim((string) $supplierInvoice->batch_no)];
                        // Persist fallback for this line so subsequent retries use the same source.
                        $rejectRepair->batch_no = json_encode($batchNos);
                    }
                }
                if (! is_array($batchNos) || $batchNos === []) {
                    throw new \RuntimeException('Invalid batch list for this repair line.');
                }

                $remainingQty = (int) $rejectRepair->quantity;
                $supplierModel = supplier::find($rejRepair->supplier_id);
                if (! $supplierModel) {
                    throw new \RuntimeException('Supplier not found.');
                }
                $product = product::whereKey((int) $rejectRepair->product_id)->lockForUpdate()->first();
                if (! $product) {
                    throw new \RuntimeException('Product not found.');
                }

                foreach ($batchNos as $batch_no) {
                    if ($remainingQty <= 0) {
                        break;
                    }
                    $res = $this->applyRejectRepairDeductionOnBatch(
                        $product,
                        $supplierModel,
                        (int) $rejRepair->id,
                        strtoupper((string) $rejRepair->supplier_inv_no),
                        'Debit Note created (Repair)',
                        (string) $batch_no,
                        (int) $remainingQty,
                        (int) $supplierInvoice->id
                    );
                    $remainingQty -= (int) ($res['deducted'] ?? 0);
                }

                if ($remainingQty > 0) {
                    throw new \RuntimeException('Insufficient batch/ref stock for repair debit note (short by ' . $remainingQty . ').');
                }
            }

            $rejectRepair->is_debit_note = 1;
            $rejectRepair->pending_for_dn = 0;
            $rejectRepair->save();

            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            return redirect()->back()->with('error', $e->getMessage());
        }

        // Send mail only after successful DB commit.
        try {
            $product = product::where('id', $rejectRepair->product_id)->first();
            $supplier = supplier::where('id', $rejRepair->supplier_id)->pluck('c_name');
            $mailData['subject'] = 'Debit Note';
            $mailData['message'] = "Debit note has been created.";
            $mailData['email'] = ['po@artisanfurniture.net', 'backend@artisanfurniture.net'];
            $mailData['product_name'] = ($product?->code ?? '') . ' - ' . ($product?->name ?? '');
            $mailData['supplier_name'] = $supplier[0] ?? '';
            $mailData['supplier_inv_no'] = $rejRepair->supplier_inv_no;
            $mailData['rejectRepair'] = $rejectRepair;
            Mail::send(new DebitNoteMail($mailData));
        } catch (\Throwable $e) {
            return redirect('/rejectRepair')->with('success', 'Debit note created. Email sending failed: ' . $e->getMessage());
        }

        return redirect('/rejectRepair')->with('success', 'Debit note created.');
    }

    public function viewDebitNote($id)
    {

        // $rejectRepair = RejectRepairPtable::find($id);
        // $companyDetails = setting::first();
        // $rejectRepairParent = rejectRepair::find($rejectRepair->reject_repair_id);
        // $supplier = supplier::find($rejectRepairParent->supplier_id);

        // //    echo "<pre>";
        // //     print_r($supplier);die;

        // return view('rejectRepair/viewDebitNote', ['rejectRepair' => $rejectRepair,'supplier'=>$supplier]);

        $RejectRepairPtable = RejectRepairPtable::find($id);

        $rejectRepair = rejectRepair::find($RejectRepairPtable->reject_repair_id);
        //dd($rejectRepair);
        //$purchaseBill = purchaseBill::where('supp_inv_no', $rejectRepair->supplier_inv_no)->first();
        // dd($purchaseBill);
        // if(!isset($purchaseBill->id)){
        //     return redirect('/rejectRepair')->with('danger', 'Debit note cannot be viewed as invoice is not present in the software.');
        // }
        //$purchase_order_id =  $purchaseBill->purchaseOrder_id;            
        //$purchaseOrder = purchaseOrder::where('id',$purchase_order_id)->first();

        $companyDetails = setting::first();
        $supplier = supplier::find($rejectRepair->supplier_id);
        $pbTable = pbTable::where('purchasebill_id', $RejectRepairPtable->purchase_bill_id)->where('product_id', $RejectRepairPtable->product_id)->first();
        $print = 0;
        if (isset($_REQUEST['print']) && $_REQUEST['print'] == 1) {
            $print = 1;
        }

        $suppInvoice = purchaseBill::find($rejectRepair->purchase_bill_id);

        return view('rejectRepair/viewDebitNote', ['rejectRepair' => $rejectRepair, 'print' => $print, 'companyDetails' => $companyDetails, 'supplier' => $supplier, 'RejectRepairPtable' => $RejectRepairPtable, 'pbTable' => $pbTable, 'suppInvoice' => $suppInvoice]);
    }

    public function index(Request $request)
    {
        $rejectRepairQuery = RejectRepairPtable::join('reject_repair', 'reject_repair_ptable.reject_repair_id', 'reject_repair.id')
            ->select('reject_repair.id', 'reject_repair_ptable.id as p_id', 'reject_repair.supplier_inv_no', 'reject_repair.supplier_id', 'reject_repair_ptable.receiveqty', 'reject_repair.date', 'reject_repair.outward_challan_no', 'reject_repair_ptable.*')
            ->orderBy('pending_for_dn', 'desc')->orderBy('created_at', 'desc')->orderBy('pending_for_dn', 'desc');

        if (isset($request->filter)) {
            if ($request->filter == 'view_debit') {
                $rejectRepairQuery->where('reject_repair_ptable.is_debit_note', 1);
            }
            if ($request->filter == 'pending_debit') {
                $rejectRepairQuery->where('reject_repair_ptable.is_debit_note', 0)->where('reject_repair_ptable.pending_for_dn', 1)->where('reject_repair_ptable.status', '>', 0);
            }
            if ($request->filter == 'reject') {
                $rejectRepairQuery->where('reject_repair_ptable.is_debit_note', 0)->where('reject_repair_ptable.status', 1);
            }
            if ($request->filter == 'repair') {
                $rejectRepairQuery->where('reject_repair_ptable.is_debit_note', 0)->where('reject_repair_ptable.status', 2);
            }
            if ($request->filter == 'complete') {
                $rejectRepairQuery->where('reject_repair_ptable.is_debit_note', 0)->where('reject_repair_ptable.status', 0);
            }
            if ($request->filter == 'convert') {
                $rejectRepairQuery->where('reject_repair_ptable.is_debit_note', 0)->where('reject_repair_ptable.status', 3);
            }
        }

        $rejectRepair = $rejectRepairQuery->get();

        return view('rejectRepair/index', ['rejectRepair' => $rejectRepair]);
    }

    public function complete(Request $request, $id)
    {

        $quantity = $request->receiveqty;
        $p_id = $request->p_id;
        $reject = RejectRepairPtable::find($p_id);
        $product = product::find($reject->product_id);
        $rejectRepair = rejectRepair::find($reject->reject_repair_id);
        $product_id = $reject->product_id;
        if ($product) {
            if ($reject->id < 905) {
                $supplier = supplier::find($rejectRepair->supplier_id);
                $prod_quantity = $product->quantity;
                $product->quantity = $product->quantity + $quantity;
                $s = stockLog::create([
                    'product_id' => $product_id,
                    'voucher_no' => $rejectRepair->supplier_inv_no,
                    'ref_no' => "Reject/Repair Complete",
                    'quantity' => $quantity,
                    'opening_balance' => $prod_quantity,
                    'remaining_stock' => $product->quantity,
                    'type' => 1,
                    'entity_id' => $rejectRepair->id,
                    'supplier_name' => $supplier->c_name
                ]);
            }
            $totalqtyreceived =  $request->receiveqty + $reject->receiveqty;
            if ($totalqtyreceived == $reject->quantity) {
                $reject->status = '0';
            }

            $reject->receiveqty = $request->receiveqty;
            if ($product->save() && $reject->save()) {
                return redirect('/rejectRepair')->with('success', 'Product marked for completion.');
            }
        } else {
            return redirect('/rejectRepair')->with('danger', 'Error Occured');
        }
    }

    public function delete(Request $request, $id)
    {
        $rejectRepair = rejectRepair::where('id', $id)->first();
        if ($rejectRepair->status != 0) {
            $rejectRepair->delete();
            return redirect('/rejectRepair')->with('success', 'Entry deleted successfully.');
        } else {
            return redirect('/rejectRepair')->with('danger', 'Entry was not deleted.');
        }
    }

    public function rejectRepairFixNew()
    {
        $rejectRepair = rejectRepair::get();
        foreach ($rejectRepair as $rr) {
            RejectRepairPtable::create([
                'reject_repair_id' => $rr->id,
                'product_id' => $rr->product_id,
                'quantity' => $rr->quantity,
                'receiveqty' => $rr->receiveqty,
                'status' => $rr->status,
            ]);
        }
        die;
    }
}
