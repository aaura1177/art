<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Storage;
use App\Service;
use App\serviceTable;
use App\SubServiceProductTable;
use App\SubServiceTable;
use Illuminate\Http\Request;
use \auth;
use App\Exports\PoExport;
use App\Exports\purchaseOrderTallyExport;
use App\supplier;
use App\User;
use App\supplierInvoice;
use App\soTable;
use App\product;
use App\supplierServiceInvoice;
use App\serviceProductTable;
use App\contractor;
use App\Notification;
use App\poTable;
use App\UnitType;
use App\purchaseOrder;
use App\purchaseOrderConsumable;
use App\setting;
use App\pbTable;
use App\consumable;
use App\purchaseBill;
use App\pocTable;
use App\popTable;
use App\packaging;
use App\packagingPrice;
use App\rejectRepair;
use App\RejectRepairPtable;
use App\supplierInvoiceProduct;
use App\Services\ServiceInvoiceQuantityService;
use App\Support\SendToSupplierPo;
use App\Support\ConsumableMonthEndInvoiceSupport;
use App\Support\PurchaseOrderVersionWriter;
use App\Support\SupplierMultiPoSupport;
use Maatwebsite\Excel\Facades\Excel;
use Carbon\Carbon;

class supplierUserController extends Controller
{
    public function __construct()
    {
        //$this->middleware(['auth','2fa']);
    }

    public function dashboard()
    {
        $user = auth::user();
        if (!(\Request::session()->has('country'))) {
            \Request::session()->put('country', 'india');
        }
        if ($user->hasRole('supplier')) {
            $supplier_id = $user->supplier_id;
            $user_id = $user->id;
            $supplier = supplier::find($supplier_id);
            $supplierType = $supplier->type ?? '';
            $useConsumablePos = in_array($supplierType, ['Consumable', 'Both'], true);

            if ($supplierType === 'Consumable') {
                $purchaseOrders = purchaseOrderConsumable::where('supplier_id', $supplier_id)->count();
                $pendingPurchaseOrders = purchaseOrderConsumable::where('supplier_id', $supplier_id)
                    ->where('send_to_supplier_status', 1)
                    ->where('supplier_status', 0)->count();
                $soonduePurchaseOrders = purchaseOrderConsumable::where('supplier_id', $supplier_id)
                    ->where('status', 0)
                    ->whereDate('del_date', '<', date('Y-m-d', strtotime('+30 days')))->count();
                $overduePurchaseOrders = purchaseOrderConsumable::where('supplier_id', $supplier_id)
                    ->where('status', 0)
                    ->whereDate('del_date', '<', date('Y-m-d'))->count();
                $acceptedPurchaseOrders = purchaseOrderConsumable::where('supplier_id', $supplier_id)
                    ->where('supplier_status', 1)->count();
                $purchaseOrderList = purchaseOrderConsumable::where('supplier_id', $supplier_id)
                    ->where('send_to_supplier_status', 1)
                    ->where('status', 0)
                    ->with('supplier')
                    ->orderBy('created_at', 'DESC')
                    ->limit(5)
                    ->get();
            } else {
                $purchaseOrders = purchaseOrder::where('supplier_id', $supplier_id)->count();
                $pendingPurchaseOrders = purchaseOrder::where('supplier_id', $supplier_id)
                    ->where('send_to_supplier_status', 1)
                    ->where('supplier_status', 0)->count();
                $soonduePurchaseOrders = purchaseOrder::where('supplier_id', $supplier_id)->where('status', 0)->whereDate('del_date', '<', date('Y-m-d', strtotime('+30 days')))->count();
                $overduePurchaseOrders = purchaseOrder::where('supplier_id', $supplier_id)->where('status', 0)->whereDate('del_date', '<', date('Y-m-d'))->count();
                $acceptedPurchaseOrders = purchaseOrder::where('supplier_id', $supplier_id)->where('supplier_status', 1)->count();
                $purchaseOrderList = purchaseOrder::where('supplier_id', $supplier_id)
                    ->where('send_to_supplier_status', 1)
                    ->where('status', 0)
                    ->with('supplier')
                    ->orderBy('created_at', 'DESC')
                    ->limit(5)
                    ->get();

                if ($useConsumablePos) {
                    $purchaseOrders += purchaseOrderConsumable::where('supplier_id', $supplier_id)->count();
                    $pendingPurchaseOrders += purchaseOrderConsumable::where('supplier_id', $supplier_id)
                        ->where('send_to_supplier_status', 1)
                        ->where('supplier_status', 0)->count();
                    $soonduePurchaseOrders += purchaseOrderConsumable::where('supplier_id', $supplier_id)
                        ->where('status', 0)
                        ->whereDate('del_date', '<', date('Y-m-d', strtotime('+30 days')))->count();
                    $overduePurchaseOrders += purchaseOrderConsumable::where('supplier_id', $supplier_id)
                        ->where('status', 0)
                        ->whereDate('del_date', '<', date('Y-m-d'))->count();
                    $acceptedPurchaseOrders += purchaseOrderConsumable::where('supplier_id', $supplier_id)
                        ->where('supplier_status', 1)->count();
                }
            }

            $invoices = supplierInvoice::where('user_id', $user_id)->count();
            $approvedInvoices = supplierInvoice::where('user_id', $user_id)->where('is_approved', 1)->count();
            //$returnedPurchaseOrders = rejectRepair::where('supplier_id', $supplier_id)->where('status',2)->count();
            $rejectPurchaseOrders = rejectRepair::where('supplier_id', $supplier_id)->where('status', 1)->count();
            $repairPurchaseOrders = rejectRepair::where('supplier_id', $supplier_id)->where('status', 2)->count();

            $invoicesList = supplierInvoice::where('user_id', $user_id)
                ->orderBy('created_at', 'DESC')
                ->limit(5)
                ->get();

            $notifications = Notification::where('user_id', $user_id)->where('is_read', 0)->limit('20')->orderBy('id', 'desc')->get();
            return view('supplierUser/dashboard', ['purchaseOrders' => $purchaseOrders, 'invoices' => $invoices, 'acceptedPurchaseOrders' => $acceptedPurchaseOrders, 'repairPurchaseOrders' => $repairPurchaseOrders, 'rejectPurchaseOrders' => $rejectPurchaseOrders, 'purchaseOrderList' => $purchaseOrderList, 'invoicesList' => $invoicesList, 'pendingPurchaseOrders' => $pendingPurchaseOrders, 'overduePurchaseOrders' => $overduePurchaseOrders, 'soonduePurchaseOrders' => $soonduePurchaseOrders, 'approvedInvoices' => $approvedInvoices, 'notifications' => $notifications]);
        }
    }

    public function instructions()
    {
        return view('supplierUser/instructions');
    }

    public function getPurchaseOrders()
    {
        $user = auth::user();
        if ($user->hasRole('supplier')) {
            $supplier_id = $user->supplier_id;
            $purchaseOrders = purchaseOrder::where('supplier_id', $supplier_id)
                ->where('status', '!=', 2)
                ->where('send_to_supplier_status', 1)
                ->with(['poTable.product'])
                ->latest()
                ->get();

            $purchaseOrdersConsumables = purchaseOrderConsumable::where('supplier_id', $supplier_id)
                ->where('status', '!=', 2)
                ->where('send_to_supplier_status', 1)
                ->with(['popTable.product', 'poTable.consumable', 'poTable.product'])
                ->orderBy('del_date', 'desc')
                ->get();
            foreach ($purchaseOrders as $key => $value) {
                $products = poTable::where('poid', $value->id)->where('remqty', '>', '0')->with('product')->get();
                if (count($products)) {
                    $purchaseOrders[$key]['supplier_name'] = $value->supplier->c_name;
                    $purchaseOrders[$key]['products'] = $products;
                }
            }
            if (isset($key)) {
                foreach ($purchaseOrdersConsumables as $k => $value) {
                    $purchaseOrdersConsumables[$k]['id'] = '10000000' . $value->id;
                    $purchaseOrdersConsumables[$k]['supplier_name'] = $value->supplier->c_name;
                    if ($value->type == 1) {
                        $purchaseOrdersConsumables[$k]['products'] = pocTable::where('poid', $value->id)->with(['consumable', 'product'])->get();
                    } else {
                        $purchaseOrdersConsumables[$k]['products'] = popTable::where('poid', $value->id)->with('product')->get();
                    }
                    $purchaseOrders[$key + 1] = $purchaseOrdersConsumables[$k];
                    $key = $key + 1;
                }
            } else {
                $purchaseOrders = $purchaseOrdersConsumables;
                foreach ($purchaseOrders as $key => $value) {
                    $purchaseOrders[$key]['id'] = '10000000' . $value->id;
                    $purchaseOrders[$key]['supplier_name'] = $value->supplier->c_name;
                    if ($value->type == 1) {
                        $purchaseOrders[$key]['products'] = pocTable::where('poid', $value->id)->with(['consumable', 'product'])->get();
                    } else {
                        $purchaseOrders[$key]['products'] = popTable::where('poid', $value->id)->with('product')->get();
                    }
                }
            }

            // return $purchaseOrders;
            return view('supplierUser/purchaseOrders', ['purchaseOrder' => $purchaseOrders]);
        }
    }

    public function modal($id)
    {
        $purchaseOrder = purchaseOrder::find($id);
        $poTable = poTable::where('poid', $id)->get();
        $is_consumable = 0;
        if ($purchaseOrder && !SendToSupplierPo::isSent($purchaseOrder)) {
            return redirect('/supplier-dashboard/purchase-orders')->with('danger', 'PO not available.');
        }
        if (!$purchaseOrder) {
            $id = str_replace('10000000', '', $id);
            $purchaseOrder = purchaseOrderConsumable::where('id', $id)->first();
            if ($purchaseOrder && !SendToSupplierPo::isSent($purchaseOrder)) {
                return redirect('/supplier-dashboard/purchase-orders')->with('danger', 'PO not available.');
            }
            if ($purchaseOrder->type == 1) {
                $poTable = pocTable::where('poid', $id)->get();
            } else {
                $poTable = popTable::where('poid', $id)->get();
            }
            $is_consumable = 1;
        }
        $companyDetails = setting::first();
        $files = Storage::disk('s3')->files('stock');
        $fileMap1 = [];
        foreach ($files as $file) {
            $filename = basename($file);
            $fileMap1[$filename] = Storage::disk('s3')->url($file);
        }


        $print = 0;
        if (isset($_REQUEST['print']) && $_REQUEST['print'] == 1) {
            $print = 1;
        }

        return view('supplierUser/modal', ['purchaseOrder' => $purchaseOrder, 'print' => $print, 'companyDetails' => $companyDetails, 'poTable' => $poTable, 'is_consumable' => $is_consumable, 'fileMap1' => $fileMap1]);
    }



      public function modalCarton($id)
    {


        $id = str_replace('10000000', '', $id);
        $purchaseOrder = purchaseOrderConsumable::where('id', $id)->first();
        $companyDetails = setting::first();
        $files1 = Storage::disk('s3')->files('stock');
        $fileMap1 = [];
        foreach ($files1 as $file) {
            $filename = basename($file);
            $fileMap1[$filename] = Storage::disk('s3')->url($file);
        }
        $poTable = popTable::where('poid', $id)->get();

        $print = 0;
        if (isset($_REQUEST['print']) && $_REQUEST['print'] == 1) {
            $print = 1;
        }


        return view('supplierUser/modalcarton', ['purchaseOrder' => $purchaseOrder, 'print' => $print, 'companyDetails' => $companyDetails, 'poTable' => $poTable, 'fileMap1' => $fileMap1]);
    }



    public function acceptPurchaseOrder(Request $request, $id)
    {
        $user = auth()->user();
        $states = false;
        $msg = 'Purchase order was not found.';
        $purchaseOrder = purchaseOrder::where('id', $id)->first();
        if (isset($purchaseOrder->id)) {
            if (!SendToSupplierPo::isSent($purchaseOrder)) {
                $msg = 'This PO has not been sent to you yet.';
                if ($request->ajax()) {
                    return response()->json(['states' => false, 'msg' => $msg]);
                }

                return redirect(url('/supplier-dashboard/purchase-orders'))->with('danger', $msg);
            }
            if ($purchaseOrder) {
                $purchaseOrder->supplier_status = 1;
                if ($purchaseOrder->save()) {
                    PurchaseOrderVersionWriter::logActivity(
                        $purchaseOrder,
                        'accept_po',
                        '0',
                        '1',
                        'PO accepted by supplier'
                    );
                    $states = true;
                    $msg = 'Purchase order accepted.';
                }
            }
        } else {
            $id = str_replace('10000000', '', $id);
            $purchaseOrder = purchaseOrderConsumable::where('id', $id)->first();
            if (!$purchaseOrder || !SendToSupplierPo::isSent($purchaseOrder)) {
                $msg = 'This PO has not been sent to you yet.';
                if ($request->ajax()) {
                    return response()->json(['states' => false, 'msg' => $msg]);
                }

                return redirect(url('/supplier-dashboard/purchase-orders'))->with('danger', $msg);
            }
            $purchaseOrder->supplier_status = 1;
            if ($purchaseOrder->save()) {
                $states = true;
                $msg = 'Purchase order accepted.';
            }
        }
        $admins = User::where('role', 'Admin')->get();
        foreach ($admins as $admin) {
            Notification::create([
                'user_id' => $admin->id,
                'notification' => 'Purchase Order - ' . $purchaseOrder->pono . ' has been accepted by the supplier ' . $user->supplier->c_name,
                'is_read' => 0
            ]);
        }
        if ($request->ajax()) {
            return response()->json(['states' => $states, 'msg' => $msg]);
        } else {
            return redirect(url('/supplier-dashboard/purchase-orders'))->with('success', 'Purchase Order accepted successfully!');
        }
    }



    public function raiseInvoice(Request $request, $id)
    {
        $user = auth::user();
        $user = $user->id;
        $userSplier = User::where('id', $user)->with('supplier')->first();

           $invoiceTotal = SupplierInvoice::where('user_id', $user)
    ->where(function ($q) {
        $q->where('status', '!=', 2)
          ->orWhereNull('status');
    })
    ->whereDate('invoice_date', now()->toDateString())
    ->sum('tamount');
        // return $invoiceTotal;
        $purchaseOrder = purchaseOrder::where('id', $id)->first();
        $poTable = poTable::where('poid', $id)->get();
        $type = 1;
        $acceptedOrdersRedirect = url('/supplier-dashboard/accepted-purchase-orders');
        if (! $purchaseOrder || ! isset($purchaseOrder->id)) {
            $id = str_replace('10000000', '', $id);
            $purchaseOrder = purchaseOrderConsumable::where('id', $id)->first();
            if (! $purchaseOrder) {
                return redirect($acceptedOrdersRedirect)->with(
                    'danger',
                    'Purchase order not found or invalid PO reference.'
                );
            }
            if ($purchaseOrder->type == 1) {
                $poTable = pocTable::where('poid', $id)->get();
                $type = 2;
            } else {
                $poTable = popTable::where('poid', $id)->get();
                $type = 3;
            }
            // foreach($poTable as $p=>$prod){
            //     $poTable[$p]['product']['code'] = '';
            // }
            $id = '10000000' . $id;
            $acceptedOrdersRedirect = url('/supplier-dashboard/accepted-purchase-orders-consumable');
        } else {
            $gate = PurchaseOrderVersionWriter::invoiceGateStatus((int) $purchaseOrder->id);
            if (! $gate['ok']) {
                return redirect($acceptedOrdersRedirect)->with('danger', $gate['message']);
            }
        }

        $singlePoEligibilityDate = $this->getInvoiceEligibilityDateFromPurchaseOrders(collect([$purchaseOrder]));
        if ($singlePoEligibilityDate && Carbon::today()->lt($singlePoEligibilityDate)) {
            return redirect($acceptedOrdersRedirect)->with(
                'danger',
                'Invoice can be generated only from 7 days before delivery date (eligible on ' . $singlePoEligibilityDate->format('d-m-Y') . ').'
            );
        }

          $unitTypes = UnitType::all()->keyBy('name');

        foreach ($poTable as $key => $row) {
            $unitName = $row['unit'];
            if (isset($unitTypes[$unitName])) {
                $poTable[$key]['data_type'] = $unitTypes[$unitName]->data_type;
            } else {
                $poTable[$key]['data_type'] = 'unknown';
            }
        }
                    if($type == 2 ||  $type == 3){
                        $ewayThreshold = 100000;
                        if (
                            (int) $type === 2 &&
                            isset($purchaseOrder->type) &&
                            (int) $purchaseOrder->type === 1 &&
                            (int) ($purchaseOrder->address_option ?? 0) !== 100
                        ) {
                            $supplierCity = strtoupper(trim((string) optional($purchaseOrder->supplier)->city));
                            $ewayThreshold = ($supplierCity === 'JAIPUR') ? 200000 : 100000;
                        }

                         return view('supplierUser/raiseInvoicecosumableCarton', ['purchaseOrder' => $purchaseOrder, 'poTable' => $poTable, 'type' => $type, 'id' => $id, 'invoiceTotal' => $invoiceTotal, 'userSplier' => $userSplier, 'ewayThreshold' => $ewayThreshold]);
                     }



        return view('supplierUser/raiseInvoice', ['purchaseOrder' => $purchaseOrder, 'poTable' => $poTable, 'type' => $type, 'id' => $id, 'invoiceTotal' => $invoiceTotal, 'userSplier' => $userSplier]);
    }





    public function editInvoice(Request $request, $id)
    {
        $user = auth::user();
        $user = $user->id;
        $userSplier = User::where('id', $user)->with('supplier')->first();
        //  return $userSplier
        $supplierInvoice = supplierInvoice::find($id)->where('id', $id)->first();
        //dd($supplierInvoice->purchase_order_id);
        if ($supplierInvoice->purchase_order_type == 'Furniture') {
            $purchaseOrder = purchaseOrder::where('id', $supplierInvoice->purchase_order_id)->first();
            $poTable = poTable::where('poid', $supplierInvoice->purchase_order_id)->get();
            $typeUnit = 1;
        } else {
            $purchaseOrder = purchaseOrderConsumable::where('id', $supplierInvoice->purchase_order_id)->first();
            if ($purchaseOrder && (int) $purchaseOrder->type === 2) {
                return redirect(url('/supplier-dashboard/edit-invoice/carton/' . $id));
            }
            $typeUnit = 2;
            if ($purchaseOrder->type == 1) {
                $poTable = pocTable::where('poid', $supplierInvoice->purchase_order_id)->get();
            } else {
                $poTable = popTable::where('poid', $supplierInvoice->purchase_order_id)->get();
            }
        }

         $UnitType = UnitType::all();

        foreach ($poTable as $item) {
            $unitType = $UnitType->firstWhere('name', $item->unit);

            if ($unitType) {
                $item->data_type = $unitType->data_type;
            } else {
                $item->data_type = null;
            }
        }

        $supplierInvoiceProduct = supplierInvoiceProduct::where('supplier_invoice_id', $id)->get();

        // foreach($poTable as $p=>$prod){
        //     $poTable[$p]['product']['code'] = '';
        // }

        if( $typeUnit == 2){
            $ewayThreshold = 100000;
            if (
                isset($purchaseOrder->type) &&
                (int) $purchaseOrder->type === 1 &&
                (int) ($purchaseOrder->address_option ?? 0) !== 100
            ) {
                $supplierCity = strtoupper(trim((string) optional($purchaseOrder->supplier)->city));
                $ewayThreshold = ($supplierCity === 'JAIPUR') ? 200000 : 100000;
            }
      
                     return view('supplierUser/editInvoiceconsumable', ['purchaseOrder' => $purchaseOrder, 'poTable' => $poTable, 'supplierInvoiceProduct' => $supplierInvoiceProduct, 'supplierInvoice' => $supplierInvoice, 'userSplier' => $userSplier, 'typeUnit' => $typeUnit, 'ewayThreshold' => $ewayThreshold]);

        }

        return view('supplierUser/editInvoice', ['purchaseOrder' => $purchaseOrder, 'poTable' => $poTable, 'supplierInvoiceProduct' => $supplierInvoiceProduct, 'supplierInvoice' => $supplierInvoice, 'userSplier' => $userSplier, 'typeUnit' => $typeUnit]);
    }

      public function editInvoiceCarton(Request $request, $id)
    {

        $user = auth::user();
        $user = $user->id;
        $userSplier = User::where('id', $user)->with('supplier')->first();

        $supplierInvoice = supplierInvoice::find($id)->where('id', $id)->first();

        $invoiceTotal = supplierInvoice::where('user_id', $user)
            ->whereDate('invoice_date', now()->toDateString())  // Filter by today's date
            ->sum('tamount');
        // return $invoiceTotal;
        if ($supplierInvoice->purchase_order_type == 'Furniture') {
            $purchaseOrder = purchaseOrder::where('id', $id)->first();
            $poTable = poTable::where('poid', $id)->get();
            $type = 1;
        } else {

            $purchaseOrder = purchaseOrderConsumable::where('id', $supplierInvoice->purchase_order_id)->first();
            if ($purchaseOrder->type == 1) {
                $poTable = pocTable::where('poid', $id)->get();
                $type = 2;
            } else {
                $poTable = popTable::where('poid', $id)->get();
                $type = 3;
            }

            $id = '10000000' . $id;
        }
        $unitTypes = UnitType::all()->keyBy('name');

        foreach ($poTable as $key => $row) {
            $unitName = $row['unit'];
            if (isset($unitTypes[$unitName])) {
                $poTable[$key]['data_type'] = $unitTypes[$unitName]->data_type;
            } else {
                $poTable[$key]['data_type'] = 'unknown';
            }
        }

        $supplierInvoiceProduct = supplierInvoiceProduct::where('supplier_invoice_id', $supplierInvoice->id)->get();

        return view('supplierUser/editInvoicecarton',  ['purchaseOrder' => $purchaseOrder, 'poTable' => $poTable, 'type' => $type, 'id' => $id, 'invoiceTotal' => $invoiceTotal, 'userSplier' => $userSplier, 'supplierInvoiceProduct' => $supplierInvoiceProduct, 'supplierInvoice' => $supplierInvoice]);
    }



    public function cancelInvoice(Request $request, $id)
    {
        $supplierInvoice = supplierInvoice::find($id)->where('id', $id)->where('is_approved', 0)->first();
        if (isset($supplierInvoice->id)) {
            $supplierInvoice->status = 2;
            $supplierInvoice->save();
        }

        $sitable = supplierInvoiceProduct::where('supplier_invoice_id', $id)->get();

        if ($supplierInvoice->purchase_order_type == 'Furniture') {
            foreach ($sitable as $st) {
                $poTable = poTable::where('poid', $supplierInvoice->purchase_order_id)->where('product_id', $st->product_id)->first();
                $poTable->remqty = $poTable->remqty + $st->quantity;
                $poTable->remaining_discount = $poTable->remaining_discount + $st->discount;

                $poTable->save();
            }
        } else {
            $purchaseOrder = purchaseOrderConsumable::where('id', $supplierInvoice->purchase_order_id)->first();
            if ($purchaseOrder->type == 1) {
                foreach ($sitable as $st) {
                    $pocLineId = ConsumableMonthEndInvoiceSupport::supplierInvoiceProductsHasPocTableId()
                        ? (int) ($st->poc_table_id ?? 0)
                        : null;
                    $poProduct = ConsumableMonthEndInvoiceSupport::resolvePocLine(
                        $purchaseOrder,
                        (int) $st->product_id,
                        $pocLineId > 0 ? $pocLineId : null
                    );
                    if ($poProduct) {
                        $poProduct->remqty += (float) $st->quantity;
                        $poProduct->save();
                    }
                }
                $rem = (float) pocTable::where('poid', $supplierInvoice->purchase_order_id)->sum('remqty');
                purchaseOrderConsumable::where('id', $supplierInvoice->purchase_order_id)->update([
                    'remqty' => $rem,
                    'status' => $rem == 0 ? 1 : 0,
                ]);
            } else {
                foreach ($sitable as $st) {
                    $poTable = popTable::where('poid', $supplierInvoice->purchase_order_id)->where('product_id', $st->product_id)->first();
                    if ($poTable) {
                        $poTable->remqty_box1 = $poTable->remqty_box1 + $st->quantity;
                        $poTable->remqty_box2 = $poTable->remqty_box2 + $st->quantity2;
                        $poTable->save();
                    }
                }
                $rem = (float) popTable::where('poid', $supplierInvoice->purchase_order_id)->get()
                    ->sum(fn ($row) => (float) $row->remqty_box1 + (float) $row->remqty_box2);
                purchaseOrderConsumable::where('id', $supplierInvoice->purchase_order_id)->update([
                    'remqty' => $rem,
                    'status' => $rem == 0 ? 1 : 0,
                ]);
            }
        }
        //dd($supplierInvoice->purchase_order_id);
        // foreach($poTable as $p=>$prod){
        //     $poTable[$p]['product']['code'] = '';
        // }
        return redirect(url('/supplier-dashboard/invoice-orders'))->with('success', 'Invoice has been cancelled!');
        //return view('supplierUser/editInvoice', ['purchaseOrder'=>$purchaseOrder, 'poTable' => $poTable, 'supplierInvoiceProduct'=>$supplierInvoiceProduct,'supplierInvoice'=>$supplierInvoice]);
    }

    public function cancelInvoicemulti(Request $request, $id)
    {
        $supplierInvoice = supplierInvoice::find($id)->where('id', $id)->where('is_approved', 0)->first();
        if (isset($supplierInvoice->id)) {
            $supplierInvoice->status = 2;
            $supplierInvoice->save();
        }

        $sitable = supplierInvoiceProduct::where('supplier_invoice_id', $id)->get();

        if ($supplierInvoice->purchase_order_type == 'Furniture') {
            foreach ($sitable as $st) {
                $poTable = poTable::where('poid', $st->purchase_order_id)->where('product_id', $st->product_id)->first();
                $poTable->remqty = $poTable->remqty + $st->quantity;
                $poTable->remaining_discount = $poTable->remaining_discount + $st->discount;

                $poTable->save();
            }
        }
        //dd($supplierInvoice->purchase_order_id);
        // foreach($poTable as $p=>$prod){
        //     $poTable[$p]['product']['code'] = '';
        // }
        return redirect(url('/supplier-dashboard/invoice-orders/multi'))->with('success', 'Invoice has been cancelled!');
        //return view('supplierUser/editInvoice', ['purchaseOrder'=>$purchaseOrder, 'poTable' => $poTable, 'supplierInvoiceProduct'=>$supplierInvoiceProduct,'supplierInvoice'=>$supplierInvoice]);
    }

    public function createInvoice(Request $request, $id)
    {
        // return $request->all();
          $loggedInUser = auth::user();
        if (isset($request['purchase_order_id']) && !empty($request['purchase_order_id'])) {
            $po = purchaseOrder::where('id', $request['purchase_order_id'])->first();
            $po_type = "Furniture";
            $poid = $request['purchase_order_id'];

            if (!isset($po->id)) {
                $poid = str_replace('10000000', '', $request['purchase_order_id']);
                $po = purchaseOrderConsumable::where('id', $poid)->first();
                  if ($po->type == 1) {

                    $po_type = "Consumable";
                } else {
                    $po_type = "Carton";
                }
            } else {
                $gate = PurchaseOrderVersionWriter::invoiceGateStatus((int) $po->id);
                if (! $gate['ok']) {
                    return back()->with('danger', $gate['message'])->withInput();
                }
            }

            $singlePoEligibilityDate = $this->getInvoiceEligibilityDateFromPurchaseOrders(collect([$po]));
            if ($singlePoEligibilityDate && Carbon::today()->lt($singlePoEligibilityDate)) {
                return back()->with(
                    'danger',
                    'Invoice can be generated only from 7 days before delivery date (eligible on ' . $singlePoEligibilityDate->format('d-m-Y') . ').'
                )->withInput();
            }

            $supplier_id = $po->supplier_id;
            $user = User::where('supplier_id', $supplier_id)->first();
            $invoiceUserId = $user->id;
            if (
                $po_type === "Consumable" &&
                isset($po->type) &&
                (int) $po->type === 1 &&
                isset($po->address_option) &&
                (int) $po->address_option === 100
            ) {
                $invoiceUserId = $loggedInUser->id;
            }
            if (
                $po_type === "Consumable" &&
                isset($po->type) &&
                (int) $po->type === 1 &&
                (int) ($po->address_option ?? 0) !== 100
            ) {
                $supplierCity = strtoupper(trim((string) optional($po->supplier)->city));
                $ewayThreshold = ($supplierCity === 'JAIPUR') ? 200000 : 100000;
                $pbTotal = (float) ($request['pbTotal'] ?? 0);
                $ewayBillNo = trim((string) $request->input('ewaybill', ''));
                if ($pbTotal > $ewayThreshold && $ewayBillNo === '') {
                    return back()->withErrors([
                        'ewaybill' => 'E-Way Bill number is required when invoice total exceeds ' . number_format($ewayThreshold, 0, '.', ',') . '.',
                    ])->withInput();
                }
            }

            $supplier_invoice_number = strtoupper($request->input('supp_inv_no', ''));
            $eway_bill_no            = strtoupper($request->input('ewaybill', ''));

            $vehicle_no = strtoupper($request->input('vehicle_no', ''));
            $filename = '';
            $totalremqty = 0;

       
if ($eway_bill_no) {
            $existseway_bill_no = supplierInvoice::where('supplier_id', $supplier_id)
                ->where('eway_bill_no', $eway_bill_no)
                ->where(function ($q) {
        $q->where('status', '!=', 2)
          ->orWhereNull('status');
    })
    ->exists();
}

            $existssupplier_invoice_number = supplierInvoice::where('supplier_id', $supplier_id)
                ->where('supplier_invoice_number', $supplier_invoice_number)
               ->where(function ($q) {
        $q->where('status', '!=', 2)
          ->orWhereNull('status');
    })
    ->exists();

            $errors = [];
if ($eway_bill_no) {
            if ($existseway_bill_no) {
                $errors['eway_bill_no'] = 'This Eway Bill number already exists for this supplier.';
            }
}
            if ($existssupplier_invoice_number) {
                $errors['supplier_invoice_number'] = 'This Supplier Invoice number already exists for this supplier.';
            }
            if (!empty($errors)) {
                return back()->withErrors($errors)->withInput();
            }

            $tcs = $request['tcs'] ?? 0;

            // Check if supplier invoice already exists
            $existingInvoice = supplierInvoice::where('supplier_invoice_number', strtoupper($supplier_invoice_number))
                ->first();

            if ($existingInvoice) {
                $internal_invoice_number = $existingInvoice->internal_invoice_number;
            } else {
                $internal_invoice_number = null;
            }

            // if ($request->hasfile('eway_bill_upload')) {
            //     $file = $request->file('eway_bill_upload');
            //     $extension = $file->getClientOriginalExtension();
            //     $filename = $po->pono . '.' . $extension;
            //     Storage::disk('local')->put('supplier-eway/' . $filename, file_get_contents($file));
            // }

            if ($request->hasfile('eway_bill_upload')) {
                $filePath = Storage::disk('s3')->put('stock/supplier-eway', $request->file('eway_bill_upload'));
                $imageFileName = basename($filePath);
            } else {
                $imageFileName = 'default.jpg';
            }

            $monthEndHeaderTotals = null;
            if ($po_type === 'Consumable' && isset($po->type) && (int) $po->type === 1) {
                $monthEndHeaderTotals = ConsumableMonthEndInvoiceSupport::headerTotalsFromSubmittedPbRows(
                    $request['pb'] ?? [],
                    $po
                );
            }

            $q = supplierInvoice::create([
                'purchase_order_id' => $poid,
                'supplier_id' => $po->supplier_id,
                'supplier_invoice_number' => strtoupper($supplier_invoice_number),
                'eway_bill_no' => strtoupper($eway_bill_no),
                'vehicle_no' => strtoupper($vehicle_no),
                'eway_bill_pdf' => $imageFileName,
                'user_id' => $invoiceUserId,
                'tamount' => $monthEndHeaderTotals['tamount'] ?? $request['pbTotal'],
                'purchase_order_type' => $po_type,
                'invoice_date' => $request['invoice_date'],
                'tquantity' => $monthEndHeaderTotals['tquantity'] ?? $request['pbQty'],
                'subTotal' => $monthEndHeaderTotals['subTotal'] ?? $request['pbSubTotal'],
                'roundoff' => ($monthEndHeaderTotals !== null || $po_type === 'Carton') ? 0 : ($request['roundoff'] ?? 0),
                'totaldiscount'           => ($po_type == "Furniture") ? $request['totaldiscount'] : null,

                'tgst' => $monthEndHeaderTotals['tgst'] ?? $request['pbGST'],

                'internal_invoice_number' => $internal_invoice_number ?? null,
            ]);

            foreach ($request['pb'] as $pb) {
                if ($po_type == "Furniture") {
                    if ($pb['receiveqty'] == 0) {
                        $pb['amount'] = 0;
                    }
                } else {
                    if ($po->type == 1) {
                        if ($pb['receiveqty'] == 0) {
                            $pb['amount'] = 0;
                        }
                    } else {
                        if (($pb['receiveqty_box_1'] == 0) && ($pb['receiveqty_box_2'] == 0)) {
                            $pb['amount'] = 0;
                        }
                    }
                }
                $total = 0;
                $lineAmount = (float) ($pb['amount'] ?? 0);
                $pocLine = null;

                if ($po_type == "Furniture") {
                    $amountdiscout = $pb['amount'] - $pb['remaing_discount'];
                    $total = $amountdiscout + (($pb['amount'] * $pb['gstslab']) / 100);
                } elseif ($po_type === 'Consumable' && isset($po->type) && (int) $po->type === 1) {
                    $pocLineId = ConsumableMonthEndInvoiceSupport::pocTableIdFromRequest($po, $pb);
                    $pocLine = ConsumableMonthEndInvoiceSupport::resolvePocLine(
                        $po,
                        (int) $pb['product'],
                        $pocLineId
                    );
                    $lineAmount = ConsumableMonthEndInvoiceSupport::soLineAmountFromRequest($po, $pb, $pocLine);
                    $total = ConsumableMonthEndInvoiceSupport::soLineTotalFromRequest($po, $pb, $pocLine);
                } elseif ($po_type === 'Carton') {
                    $cartonPoLine = popTable::where('poid', $poid)->where('product_id', $pb['product'])->first();
                    if (! $cartonPoLine) {
                        return back()->with('danger', 'Purchase order line not found for one of the products.')->withInput();
                    }
                    $r1 = (float) ($pb['receiveqty_box_1'] ?? 0);
                    $r2 = (float) ($pb['receiveqty_box_2'] ?? 0);
                    $rate1 = (float) ($cartonPoLine->box1_rate ?? 0);
                    $rate2 = (float) ($cartonPoLine->box2_rate ?? 0);
                    $lineAmount = round($rate1 * $r1 + $rate2 * $r2, 2);
                    $gstSlab = (float) ($pb['gstslab'] ?? $cartonPoLine->gstslab ?? 0);
                    $total = round($lineAmount + round($lineAmount * $gstSlab / 100, 2), 2);
                } else {
                    $total = $pb['amount'] + (($pb['amount'] * $pb['gstslab']) / 100);
                }
                $soAttrs = [
                    'product_id' => $pb['product'],
                    'supplier_invoice_id' => $q->id,
                    'purchase_order_id' => $poid,
                    'gst' => $pb['gstslab'],
                    'amount' => $lineAmount,
                    'total' => $total,
                ];
                if ($po_type === 'Consumable' && isset($po->type) && (int) $po->type === 1) {
                    $pocForSo = ConsumableMonthEndInvoiceSupport::pocTableIdFromRequest($po, $pb);
                    if ($pocForSo !== null && ConsumableMonthEndInvoiceSupport::supplierInvoiceProductsHasPocTableId()) {
                        $soAttrs['poc_table_id'] = $pocForSo;
                    }
                }
                $p = soTable::create($soAttrs);

                 if ($request->typeUnit == 2) {
                    $p->unit = $pb['unit'];
                    $p->save();
                }

                $p->quantity = ($po_type == "Furniture") ? $pb['receiveqty'] : ($po->type == 2 ? $pb['receiveqty_box_1'] : $pb['receiveqty']);
                if ($po->type == 2) {
                    $p->quantity2 = $pb['receiveqty_box_2'];
                }
                 $p->save();


                if ($po_type == "Furniture") {
                    $poProduct = poTable::where('poid', $poid)->where('product_id', $pb['product'])->first();
                    $poProduct->remqty -= $pb['receiveqty'];
                    $p->discount_type = $pb['discount_type'];
                    $p->discount = $pb['remaing_discount'];
                    $poProduct->remaining_discount = $poProduct->remaining_discount - $pb['remaing_discount'];
                } else {
                    if ($po->type == 1) {
                        $pocLineId = ConsumableMonthEndInvoiceSupport::pocTableIdFromRequest($po, $pb);
                        $poProduct = ConsumableMonthEndInvoiceSupport::resolvePocLine(
                            $po,
                            (int) $pb['product'],
                            $pocLineId
                        );
                        if (! $poProduct) {
                            return back()->with('danger', 'Purchase order line not found for one of the products.')->withInput();
                        }
                        $poProduct->remqty -= $pb['receiveqty'];
                    } else {
                        $poProduct = popTable::where('poid', $poid)->where('product_id', $pb['product'])->first();
                        $poProduct->remqty_box1 -= $pb['receiveqty_box_1'];
                        $poProduct->remqty_box2 -= $pb['receiveqty_box_2'];
                    }
                }
                $p->save();
                $poProduct->save();
            }

            if (!$internal_invoice_number) {
                $q->internal_invoice_number = 'GV-' . date('Y') . '-' . $q->id;
                $q->save();
            }

            if ($po_type === 'Consumable' && isset($po->type) && (int) $po->type === 1) {
                ConsumableMonthEndInvoiceSupport::applyMonthEndHeaderToInvoice(
                    $q,
                    $request['pb'] ?? [],
                    $po
                );
            }

            $poTableCheck = ($po_type == "Furniture") ? poTable::where('poid', $poid)->get() : (($po->type == 1) ? pocTable::where('poid', $poid)->get() : popTable::where('poid', $poid)->get());

            foreach ($poTableCheck as $poTableChecks) {
                if ($po_type === 'Furniture') {
                    $totalremqty += (float) ($poTableChecks->remqty ?? 0);
                } elseif ($po_type === 'Consumable' && (int) $po->type === 1) {
                    $totalremqty += (float) ($poTableChecks->remqty ?? 0);
                } elseif ($po_type === 'Consumable' && (int) $po->type === 2) {
                    $totalremqty += (float) ($poTableChecks->remqty_box1 ?? 0)
                        + (float) ($poTableChecks->remqty_box2 ?? 0);
                } else {
                    $totalremqty += (float) ($poTableChecks->remqty_box1 ?? 0)
                        + (float) ($poTableChecks->remqty_box2 ?? 0);
                }
            }

            $po->remqty = $totalremqty;
            $po->save();

            $response['success'] = 'true';
            $response['message'] = 'Invoice created successfully! Please check your email ' . $user->email;

            $admins = User::whereIn('role', ['Admin', 'Factory'])->get();
            foreach ($admins as $admin) {
                Notification::create([
                    'user_id' => $admin->id,
                    'notification' => 'Invoice for Purchase Order - ' . $po->pono . ' has been created by the supplier ' . $user->supplier->c_name,
                    'is_read' => 0
                ]);
            }

            return redirect(url('/supplier-dashboard/purchase-orders'))->with('success', 'Invoice created successfully!');
        } else {
            return response(['message' => 'PO ID is required'], 422);
        }
    }





    public function updateInvoice(Request $request, $id)
    {
        // return $request->all();
        //echo 1;die;
        //dd($request);
         $user = auth::user();
           $purchase_order_id = $request['purchase_order_id'];
        if ($request->typeUnit  == 2) {
            $purchase_order_id = '10000000' . $request['purchase_order_id'];
        }

        if (isset($purchase_order_id) && !empty($purchase_order_id)) {
            $po = purchaseOrder::where('id', $purchase_order_id)->first();
            $po_type = "Furniture";
            $poid = $purchase_order_id;
              if (!isset($po->id)) {
                $poid = str_replace('10000000', '', $purchase_order_id);
                $po = purchaseOrderConsumable::where('id', $poid)->first();
                $po_type = "Consumable";
            }
            $supplier_id = $po->supplier_id;
            $user = User::where('supplier_id', $supplier_id)->first();
            if (
                $po_type === "Consumable" &&
                isset($po->type) &&
                (int) $po->type === 1 &&
                (int) ($po->address_option ?? 0) !== 100
            ) {
                $supplierCity = strtoupper(trim((string) optional($po->supplier)->city));
                $ewayThreshold = ($supplierCity === 'JAIPUR') ? 200000 : 100000;
                $pbTotal = (float) ($request['pbTotal'] ?? 0);
                $ewayBillNo = trim((string) ($request['ewaybill'] ?? ''));
                if ($pbTotal > $ewayThreshold && $ewayBillNo === '') {
                    return back()->withErrors([
                        'ewaybill' => 'E-Way Bill number is required when invoice total exceeds ' . number_format($ewayThreshold, 0, '.', ',') . '.',
                    ])->withInput();
                }
            }
            $supplier_invoice_number = '';
            $eway_bill_no = '';
            $vehicle_no = '';
            $total_quantity = 0;
            $subTotal = 0;
            $filename = '';
            $totalremqty = 0;
            $tcs = $request['tcs'];
            if (isset($request['supp_inv_no']) && !empty($request['supp_inv_no'])) {
                $supplier_invoice_number = strtoupper($request['supp_inv_no'] ?? '');
                
            }
            if (isset($request['ewaybill']) && !empty($request['ewaybill'])) {
                $eway_bill_no            = strtoupper($request['ewaybill'] ?? '');
             
            }
            if (isset($request['vehicle_no']) && !empty($request['vehicle_no'])) {
                $vehicle_no = strtoupper($request['vehicle_no']);
            }
            $q = supplierInvoice::where('id', $id)->first();
            // if ($request->hasfile('eway_bill_upload')) {
            //     $file = $request->file('eway_bill_upload');
            //     $extension = $file->getClientOriginalExtension(); // getting image extension
            //     $filename = $po->pono . '.' . $extension;
            //     Storage::disk('local')->put('supplier-eway/' . $filename, file_get_contents($request->file('eway_bill_upload')));
            // } else {
            //     $filename = $q->eway_bill_pdf;
            // }


           $errors = [];

            if (!empty($eway_bill_no)) {
                $existseway_bill_no = supplierInvoice::where('supplier_id', $supplier_id)
                    ->where('eway_bill_no', $eway_bill_no)
                    ->where('id', '!=', $id)
                    ->where(function ($q) {
        $q->where('status', '!=', 2)
          ->orWhereNull('status');
    })
    ->exists();

                if ($existseway_bill_no) {
                    $errors['eway_bill_no'] = 'This Eway Bill number already exists for this supplier.';
                }
            }

            if (!empty($supplier_invoice_number)) {
                $existssupplier_invoice_number = supplierInvoice::where('supplier_id', $supplier_id)
                    ->where('supplier_invoice_number', $supplier_invoice_number)
                    ->where('id', '!=', $id)
                    ->where(function ($q) {
        $q->where('status', '!=', 2)
          ->orWhereNull('status');
    })
    ->exists();

                if ($existssupplier_invoice_number) {
                    $errors['supplier_invoice_number'] = 'This Supplier Invoice number already exists for this supplier.';
                }
            }

            if (!empty($errors)) {
                return back()->withErrors($errors)->withInput();
            }


            if ($request->hasfile('eway_bill_upload')) {
                $filePath = Storage::disk('s3')->put('stock/supplier-eway', $request->file('eway_bill_upload'));
                $imageFileName = basename($filePath);
            } else {
                $imageFileName = $q->eway_bill_pdf;
            }

            $q->supplier_invoice_number = strtoupper($supplier_invoice_number);
            $q->eway_bill_no = strtoupper($eway_bill_no);
            $q->vehicle_no = strtoupper($vehicle_no);
            $q->eway_bill_pdf = $imageFileName;
            $q->tamount = $request['pbTotal'];
            $q->tdsTotal = $request['tdsTotal'];
            $q->roundoff = $request['roundoff'];
            $q->totaldiscount = ($po_type == "Furniture") ? $request['totaldiscount'] : null;

            foreach ($request['pb'] as $pb) {
                if ($pb['receiveqty'] == 0) {
                    $pb['amount'] = 0;
                }
                $soTable = ConsumableMonthEndInvoiceSupport::resolveSoLine(
                    (int) $id,
                    (int) $poid,
                    (int) $pb['product'],
                    ConsumableMonthEndInvoiceSupport::pocTableIdFromRequest(
                        $po_type === 'Consumable' ? $po : null,
                        $pb
                    ),
                    $po_type === 'Consumable' ? $po : null
                );
                if (! $soTable) {
                    $soTable = soTable::where('supplier_invoice_id', $id)->where('product_id', $pb['product'])->first();
                }
                $oldqty = 0;
                $olddiscount = 0;
                if (isset($soTable->quantity)) {

                    $olddiscount = $soTable->discount;

                    $oldqty = $soTable->quantity;
                    $soTable->delete();
                }

                $total = 0;
                $discount_type = null;
                $discount = null;

                if ($po_type == "Furniture") {
                    $amount        = $pb['amount'] - $pb['remaing_discount'];
                    $discount_type = $pb['discount_type'] ?? null;
                    $discount      = $pb['remaing_discount'] ?? null;
                    $total         = $amount + (($pb['amount'] * $pb['gstslab']) / 100);
                } elseif ($po_type === 'Consumable' && isset($po->type) && (int) $po->type === 1) {
                    $pocLine = ConsumableMonthEndInvoiceSupport::resolvePocLine(
                        $po,
                        (int) $pb['product'],
                        ConsumableMonthEndInvoiceSupport::pocTableIdFromRequest($po, $pb)
                    );
                    $amount = ConsumableMonthEndInvoiceSupport::soLineAmountFromRequest($po, $pb, $pocLine);
                    $total = ConsumableMonthEndInvoiceSupport::soLineTotalFromRequest($po, $pb, $pocLine);
                } else {
                    $amount = $pb['amount'];
                    $total = $pb['amount'] + (($pb['amount'] * $pb['gstslab']) / 100);
                }
                $soCreate = [
                    'product_id'          => $pb['product'],
                    'supplier_invoice_id' => $q->id,
                    'purchase_order_id'   => $poid,
                    'quantity'            => $pb['receiveqty'],
                    'gst'                 => $pb['gstslab'],
                    'amount'              => ($po_type === 'Consumable' && isset($po->type) && (int) $po->type === 1)
                        ? $amount
                        : $pb['amount'],
                    'discount_type'       => $discount_type,
                    'discount'            => $discount,
                    'total'               => $total,
                ];
                if ($po_type === 'Consumable' && isset($po->type) && (int) $po->type === 1) {
                    $pocForSo = ConsumableMonthEndInvoiceSupport::pocTableIdFromRequest($po, $pb);
                    if ($pocForSo !== null && ConsumableMonthEndInvoiceSupport::supplierInvoiceProductsHasPocTableId()) {
                        $soCreate['poc_table_id'] = $pocForSo;
                    }
                }
                $p = soTable::create($soCreate);
                 if ($request->typeUnit == 2) {
                    $p->unit = $pb['unit'];
                    $p->save();
                }

                $total_quantity = $total_quantity + $pb['receiveqty'];
                $subTotal = $subTotal + $amount;
                if ($po_type == "Furniture") {
                    $poProduct = poTable::where('poid', $poid)->where('product_id', $pb['product'])->first();

                    $poProduct->remqty = $poProduct->remqty + $oldqty - $pb['receiveqty'];

                    $poProduct->remaining_discount = $poProduct->remaining_discount + $olddiscount - $pb['remaing_discount'];
                } else {
                    if ($po->type == 1) {
                        $poProduct = ConsumableMonthEndInvoiceSupport::resolvePocLine(
                            $po,
                            (int) $pb['product'],
                            ConsumableMonthEndInvoiceSupport::pocTableIdFromRequest($po, $pb)
                        );
                        if (! $poProduct) {
                            return back()->with('danger', 'Purchase order line not found.')->withInput();
                        }
                        $poProduct->remqty = $poProduct->remqty + $oldqty - $pb['receiveqty'];
                    } else {
                        $poProduct = popTable::where('poid', $poid)->where('product_id', $pb['product'])->first();
                        
                    }
                }



                $poProduct->save();
            }

            $q->tquantity = $request['pbQty'];
            $q->subTotal = $request['pbSubTotal'];
            $q->tgst = $request['pbGST'];
            $q->invoice_date = $request['invoice_date'];
            if ($po_type === 'Consumable' && isset($po->type) && (int) $po->type === 1) {
                ConsumableMonthEndInvoiceSupport::applyMonthEndHeaderToInvoice(
                    $q,
                    $request['pb'] ?? [],
                    $po
                );
            } else {
                $q->save();
            }

            if ($po_type === 'Furniture') {
                $poTableCheck = poTable::where('poid', $poid)->get();
                foreach ($poTableCheck as $poTableChecks) {
                    $totalremqty = $totalremqty + $poTableChecks->remqty;
                }
            } elseif ($po_type === 'Consumable' && (int) $po->type === 1) {
                $poTableCheck = pocTable::where('poid', $poid)->get();
                foreach ($poTableCheck as $poTableChecks) {
                    $totalremqty += (float) $poTableChecks->remqty;
                }
            } elseif ($po_type === 'Consumable' && (int) $po->type === 2) {
                $poTableCheck = popTable::where('poid', $poid)->get();
                foreach ($poTableCheck as $poTableChecks) {
                    $totalremqty += (float) ($poTableChecks->remqty_box1 ?? 0) + (float) ($poTableChecks->remqty_box2 ?? 0);
                }
            }
            $po->remqty = $totalremqty;
            $po->save();
            $response['success'] = 'true';
            $response['message'] = 'Invoice created successfully! Please check your email ' . $user->email;
            $request['supplier_email'] = $user->email;
            $request['firstname'] = $user->firstname;
            $request['lastname'] = $user->lastname;
            //$request['invoice_link'] = URL::to('/supplierInvoice/modalEmail/'.$q->id);
            //Mail::send(new SupplierInvoiceMail($request));
            return redirect(url('/supplier-dashboard/invoice-orders'))->with('success', 'Invoice updated successfully!');
        } else {
            $response['message'] = 'PO ID is required';
            return response($response, 422);
        }
    }



       public function updateInvoiceCarton(Request $request, $id)
    {
        //echo 1;die;
        //dd($request);
        // return $request->all();
        $user = auth::user();
        // return $request->all();


        if ($request->has('purchase_order_id')) {

            $poid = str_replace('10000000', '', $request->purchase_order_id);
            $q = supplierInvoice::where('id', $id)->first();
            $po = purchaseOrderConsumable::where('id', $q->purchase_order_id)->first();
            $po_type = "Consumable";

            $supplier_id = $po->supplier_id;
            $user = User::where('id', $user->id)->where('supplier_id', $supplier_id)->first();
            $supplier_invoice_number = '';
            $eway_bill_no = '';
            $vehicle_no = '';
            $total_quantity = 0;
            $subTotal = 0;
            $filename = '';
            $totalremqty = 0;
            $tcs = $request['tcs'];
            if (isset($request['supp_inv_no']) && !empty($request['supp_inv_no'])) {
                $supplier_invoice_number = $request['supp_inv_no'];
            }
            if (isset($request['ewaybill']) && !empty($request['ewaybill'])) {
                $eway_bill_no = $request['ewaybill'];
            }
            if (isset($request['vehicle_no']) && !empty($request['vehicle_no'])) {
                $vehicle_no = $request['vehicle_no'];
            }


            // if ($request->hasfile('eway_bill_upload')) {
            //     $file = $request->file('eway_bill_upload');
            //     $extension = $file->getClientOriginalExtension(); // getting image extension
            //     $filename = $po->pono . '.' . $extension;
            //     Storage::disk('local')->put('supplier-eway/' . $filename, file_get_contents($request->file('eway_bill_upload')));
            // } else {
            //     $filename = $q->eway_bill_pdf;
            // }

            if ($request->hasfile('eway_bill_upload')) {
                $filePath = Storage::disk('s3')->put('stock/supplier-eway', $request->file('eway_bill_upload'));
                $imageFileName = basename($filePath);
            } else {
                $imageFileName = $q->eway_bill_pdf;
            }

            $q->supplier_invoice_number = strtoupper($supplier_invoice_number);
            $q->eway_bill_no = $eway_bill_no;
            $q->vehicle_no = $vehicle_no;
            $q->eway_bill_pdf = $imageFileName;
            $q->tamount = $request['pbTotal'];
            $q->tdsTotal = $request['tdsTotal'] ?? 0;

            foreach ($request['pb'] as $pb) {
                if ($pb['receiveqty_box_1'] == 0 && $pb['receiveqty_box_2'] == 0) {
                    $pb['amount'] = 0;
                }
                $soTable = soTable::where('supplier_invoice_id', $id)->where('product_id', $pb['product'])->first();
                $oldqty = 0;
                $oldqty2 = 0;
                if (isset($soTable->quantity)) {
                    $oldqty = $soTable->quantity;
                    $oldqty2 = $soTable->quantity2;
                    $soTable->delete();
                }

                $poProduct = popTable::where('poid', $po->id)->where('product_id', $pb['product'])->first();
                if (! $poProduct) {
                    return back()->with('danger', 'Purchase order line not found for one of the products.')->withInput();
                }

                $r1 = (float) ($pb['receiveqty_box_1'] ?? 0);
                $r2 = (float) ($pb['receiveqty_box_2'] ?? 0);
                $rate1 = (float) ($poProduct->box1_rate ?? 0);
                $rate2 = (float) ($poProduct->box2_rate ?? 0);
                $lineAmount = round($rate1 * $r1 + $rate2 * $r2, 2);
                $gstSlab = (float) ($pb['gstslab'] ?? $poProduct->gstslab ?? 0);
                $lineTotal = round($lineAmount + round($lineAmount * $gstSlab / 100, 2), 2);

                $p = soTable::create([
                    'product_id' => $pb['product'],
                    'supplier_invoice_id' => $q->id,
                    'purchase_order_id' => $po->id,
                    'quantity' => $pb['receiveqty_box_1'],
                    'quantity2' => $pb['receiveqty_box_2'],
                    'gst' => $gstSlab,
                    'amount' => $lineAmount,
                    'total' => $lineTotal,
                ]);

                $total_quantity = $total_quantity + $pb['receiveqty_box_1'] + $pb['receiveqty_box_2'];
                $subTotal = $subTotal + $lineAmount;

                $poProduct->remqty_box1 = $poProduct->remqty_box1 + $oldqty - $pb['receiveqty_box_1'];
                $poProduct->remqty_box2 = $poProduct->remqty_box2 + $oldqty2 - $pb['receiveqty_box_2'];
                $poProduct->save();
            }

            $rem = (float) popTable::where('poid', $po->id)->get()
                ->sum(fn ($row) => (float) $row->remqty_box1 + (float) $row->remqty_box2);
            $po->remqty = $rem;
            $po->status = $rem == 0 ? 1 : 0;
            $po->save();

            $q->tquantity = $request['pbQty'];
            $q->subTotal = $request['pbSubTotal'];
            $q->tgst = $request['pbGST'];
            $q->invoice_date = $request['invoice_date'];
            $q->save();


            $response['success'] = 'true';
            $response['message'] = 'Invoice created successfully! Please check your email ' . $user->email;
            $request['supplier_email'] = $user->email;
            $request['firstname'] = $user->firstname;
            $request['lastname'] = $user->lastname;
            //$request['invoice_link'] = URL::to('/supplierInvoice/modalEmail/'.$q->id);
            //Mail::send(new SupplierInvoiceMail($request));
            return redirect(url('/supplier-dashboard/invoice-orders'))->with('success', 'Invoice updated successfully!');
        } else {
            $response['message'] = 'PO ID is required';
            return response($response, 422);
        }
    }


    public function data()
    {
        $purchaseOrder = purchaseOrder::all();
        $supplier = supplier::all();
        $product = product::all();
        return response()->json(['purchaseOrder' => $purchaseOrder, 'supplier' => $supplier, 'product' => $product]);
    }

    public function poTableData($id)
    {
        $poTable = poTable::where('poid', $id)->get();
        return response()->json(['poTable' => $poTable]);
    }

    public function exportcsv(Request $request)
    {
        $fsd = $request['fsd'];
        $fed = $request['fed'];
        return (new PoExport($fsd, $fed))->download('supplierPurchaseOrder.xlsx');
    }

    public function getInvoiceOrders(Request $request)
    {
        $user = auth::user();
        if ($user->hasRole('supplier')) {
            $user_id = $user->id;
            $query = supplierInvoice::where('user_id', $user_id)->whereNull('mulitple_po');

            $kind = $request->query('kind');
            if ($kind === 'furniture') {
                $query->where('purchase_order_type', 'Furniture');
            } elseif ($kind === 'consumable') {
                $query->where('purchase_order_type', 'Consumable');
            } elseif ($kind === 'carton') {
                $query->where('purchase_order_type', 'Carton');
            }

            $pageTitles = [
                'furniture' => 'Supplier Invoices (Furniture — single PO)',
                'consumable' => 'Supplier Invoices (Consumable — single PO)',
                'carton' => 'Supplier Invoices (Carton — single PO)',
            ];

            return view('supplierUser/invoiceOrders', [
                'supplierInvoice' => $query->orderByDesc('created_at')->get(),
                'pageTitle' => $pageTitles[$kind] ?? 'Supplier Invoices',
            ]);
        }
    }



    public function getAcceptedPurchaseOrders()
    {
        $user = auth::user();
        if ($user->hasRole('supplier')) {
            $supplier_id = $user->supplier_id;
            $acceptedPurchaseOrders = purchaseOrder::where('supplier_id', $supplier_id)->where('supplier_status', 1)->orderBy('created_at', 'desc')
                ->get();
            $poIds = $acceptedPurchaseOrders->pluck('id');
            $latestVersions = PurchaseOrderVersionWriter::latestVersionMap($poIds);
            $pendingVersions = PurchaseOrderVersionWriter::pendingVersionCountMap($poIds);

            return view('supplierUser/acceptedPurchaseOrders', [
                'purchaseOrder' => $acceptedPurchaseOrders,
                'latestVersions' => $latestVersions,
                'pendingVersions' => $pendingVersions,
            ]);
        }
    }

    public function poVersionHistory($id)
    {
        $user = auth::user();
        $purchaseOrder = purchaseOrder::with('supplier')->findOrFail($id);
        if ((int) $purchaseOrder->supplier_id !== (int) $user->supplier_id) {
            return redirect('/supplier-dashboard/purchase-orders')->with('danger', 'PO not available.');
        }
        if (! SendToSupplierPo::isSent($purchaseOrder)) {
            return redirect('/supplier-dashboard/purchase-orders')->with('danger', 'PO not available.');
        }

        $versions = collect();
        $activities = collect();
        $tableMissing = ! PurchaseOrderVersionWriter::versionsTableExists();
        if (! $tableMissing) {
            $versions = \App\PurchaseOrderVersion::where('purchase_order_id', $id)
                ->orderByDesc('version')
                ->get();
        }
        if (PurchaseOrderVersionWriter::activityTableExists()) {
            $activities = \App\PurchaseOrderActivityLog::where('purchase_order_id', $id)
                ->orderByDesc('id')
                ->get();
        }

        return view('supplierUser.po_version_history', [
            'purchaseOrder' => $purchaseOrder,
            'versions' => $versions,
            'activities' => $activities,
            'tableMissing' => $tableMissing,
            'canAcceptVersions' => true,
        ]);
    }

    public function acceptPoVersion($id, $versionId)
    {
        $user = auth::user();
        $purchaseOrder = purchaseOrder::findOrFail($id);
        if ((int) $purchaseOrder->supplier_id !== (int) $user->supplier_id) {
            return redirect()->back()->with('danger', 'PO not available.');
        }
        if (! SendToSupplierPo::isSent($purchaseOrder)) {
            return redirect()->back()->with('danger', 'PO not available.');
        }

        $version = \App\PurchaseOrderVersion::where('id', $versionId)
            ->where('purchase_order_id', $id)
            ->firstOrFail();

        if ($version->isAccepted()) {
            return redirect()->back()->with('info', 'Version v'.$version->version.' is already accepted.');
        }

        PurchaseOrderVersionWriter::acceptVersion($version);
        PurchaseOrderVersionWriter::logActivity(
            $purchaseOrder,
            'accept_version',
            null,
            'v'.$version->version,
            'Supplier accepted version v'.$version->version
        );

        return redirect()->back()->with('success', 'Version v'.$version->version.' accepted.');
    }

    public function getAcceptedConsumablePurchaseOrders()
    {
        $user = auth::user();
        if ($user->hasRole('supplier')) {
            $supplier_id = $user->supplier_id;
            $supplier = supplier::find($supplier_id);
            $doesPackaging = ($supplier->do_packaging ?? 'No') === 'Yes';

            $acceptedPurchaseOrdersQuery = purchaseOrderConsumable::where('supplier_id', $supplier_id)
                ->where('supplier_status', 1)
                ->with(['poTable.consumable', 'poTable.product', 'popTable.product']);

            $acceptedPurchaseOrdersQuery->where('type', 1);

            $acceptedPurchaseOrders = $acceptedPurchaseOrdersQuery
                ->orderBy('created_at', 'desc')
                ->get();

            return view('supplierUser/acceptedPurchaseOrdersConsumable', [
                'purchaseOrder' => $acceptedPurchaseOrders,
                'doesPackaging' => $doesPackaging,
            ]);
        }
    }

    public function getReturnedPurchaseOrders()
    {
        $user = auth::user();
        if ($user->hasRole('supplier')) {
            $supplier_id = $user->supplier_id;
            $returnedPurchaseOrders = RejectRepairPtable::join('reject_repair', 'reject_repair_ptable.reject_repair_id', 'reject_repair.id')
                ->select('reject_repair.id', 'reject_repair_ptable.id as p_id', 'reject_repair.supplier_inv_no', 'reject_repair.supplier_id', 'reject_repair_ptable.receiveqty', 'reject_repair.date', 'reject_repair_ptable.*')
                ->where('reject_repair.supplier_id', $supplier_id)
                ->get();
            return view('supplierUser/returnedPurchaseOrders', ['rejectRepair' => $returnedPurchaseOrders]);
        }
    }

    public function invoiceModal($id)
    {

        $supplierInvoice = supplierInvoice::find($id)->where('id', $id)->first();
        //dd($supplierInvoice->purchase_order_id);
        if ($supplierInvoice->purchase_order_type == 'Furniture') {
            $purchaseOrder = purchaseOrder::where('id', $supplierInvoice->purchase_order_id)->first();
        } else {
            $purchaseOrder = purchaseOrderConsumable::where('id', $supplierInvoice->purchase_order_id)->first();
        }
        $companyDetails = setting::first();
        $files = Storage::disk('s3')->files('stock');
        $fileMap1 = [];
        foreach ($files as $file) {
            $filename = basename($file);
            $fileMap1[$filename] = Storage::disk('s3')->url($file);
        }
        $supplierInvoiceProduct = supplierInvoiceProduct::where('supplier_invoice_id', $id)->get();
        foreach ($supplierInvoiceProduct as $key => $sup) {
            if ($supplierInvoice->purchase_order_type == 'Furniture') {
                $supplierInvoiceProduct[$key]['potable'] = poTable::where('poid', $sup->purchase_order_id)->where('product_id', $sup->product_id)->first();
                $potable = $supplierInvoiceProduct[$key]['potable'];
                $gstslab = (float) (optional($potable)->gstslab ?? 0);
                $supplierInvoiceProduct[$key]['gstamount'] = (float) $sup->amount * $gstslab / 100.0;
            } else {
                if ((int) $purchaseOrder->type === 2) {
                    $supplierInvoiceProduct[$key]['potable'] = popTable::where('poid', $sup->purchase_order_id)->where('product_id', $sup->product_id)->first();
                    $potable = $supplierInvoiceProduct[$key]['potable'];
                    $gstslab = (float) (optional($potable)->gstslab ?? 0);
                    $supplierInvoiceProduct[$key]['gstamount'] = round((float) $sup->amount * $gstslab / 100.0, 2);
                } else {
                    $potable = ConsumableMonthEndInvoiceSupport::resolvePotableForSupplierInvoiceLine(
                        $purchaseOrder,
                        $sup
                    );
                    $supplierInvoiceProduct[$key]['potable'] = $potable;
                    $supplierInvoiceProduct[$key]['gstamount'] = ConsumableMonthEndInvoiceSupport::supplierInvoiceLineGstAmount(
                        $purchaseOrder,
                        $sup,
                        $potable
                    );
                }
            }
        }

        $print = 0;
        if (isset($_REQUEST['print']) && $_REQUEST['print'] == 1) {
            $print = 1;
        }

        $footerTgst = $purchaseOrder instanceof purchaseOrderConsumable
            && ConsumableMonthEndInvoiceSupport::isMonthEndConsumablePo($purchaseOrder)
            ? (float) $supplierInvoice->tgst
            : null;

        return view('supplierInvoice/modal', [
            'supplierInvoice' => $supplierInvoice,
            'print' => $print,
            'companyDetails' => $companyDetails,
            'poTable' => $supplierInvoiceProduct,
            'purchaseOrder' => $purchaseOrder,
            'fileMap1' => $fileMap1,
            'footerTgst' => $footerTgst,
        ]);
    }

    public function notiRead($id)
    {
        $notification = Notification::where('id', $id)->first();
        $notification->is_read = 1;
        $notification->save();
        return redirect(url('/supplier-dashboard'))->with('success', 'Notification marked as read!');
    }

    public function raiseChallan($id)
    {
        $rejectRepair = RejectRepairPtable::find($id);
        $rejectRepair->is_challan_raised = 1;
        $rejectRepair->save();
        return redirect('/supplier-dashboard/returned-purchase-orders')->with('success', 'Challan raised successfully.');
    }
    public function viewChallan($id)
    {
        $RejectRepairPtable = RejectRepairPtable::find($id);
        $rejectRepair = rejectRepair::find($RejectRepairPtable->reject_repair_id);
        $purchaseOrder = purchaseOrder::find($id)->where('id', $id)->first();
        $companyDetails = setting::first();
        $poTable = poTable::where('poid', $id)->get();

        return view('supplierUser/modal', ['purchaseOrder' => $purchaseOrder, 'companyDetails' => $companyDetails, 'poTable' => $RejectRepairPtable]);
    }



    public function getservice()
    {
        $user = auth::user();
        if ($user->hasRole('supplier')) {
            $supplier_id = $user->supplier_id;
            $purchaseOrders = Service::where('supplier_id', $supplier_id)
                ->where('status', '!=', 2)
                ->where('send_to_supplier_status', 1)
                ->orderBy('del_date', 'desc')
                ->get();

            foreach ($purchaseOrders as $key => $value) {
                $products = serviceTable::where('poid', $value->id)->where('remqty', '>', '0')->get();
                if (count($products)) {
                    $purchaseOrders[$key]['supplier_name'] = $value->supplier->c_name;
                    $purchaseOrders[$key]['products'] = $products;
                }
            }



            return view('supplierUser/serviceOrder', ['purchaseOrder' => $purchaseOrders]);
        }
    }




    public function createServiceInvoice(Request $request, $id)
    {
        // return $request->all();
        if (isset($request['purchase_order_id']) && !empty($request['purchase_order_id'])) {
            $po = Service::where('id', $request['purchase_order_id'])->first();
            if (!$po) {
                return redirect(url('/supplier-dashboard/accepted-service-orders'))->with(
                    'danger',
                    'Purchase order not found.'
                );
            }
            if ((int) $po->status === 1) {
                return redirect(url('/supplier-dashboard/accepted-service-orders'))->with(
                    'danger',
                    'This purchase order is complete. No further invoices can be raised.'
                );
            }
            $po_type = "Furniture";
            $poid = $request['purchase_order_id'];



            $supplier_id = $po->supplier_id;
            $user = User::where('supplier_id', $supplier_id)->first();

            $supplier_invoice_number = $request->input('supp_inv_no', '');
            $eway_bill_no = $request->input('ewaybill', '');
            $vehicle_no = $request->input('vehicle_no', '');
            $filename = '';
            $totalremqty = 0;

            $tcs = $request['tcs'] ?? 0;

            // Check if supplier invoice already exists
            $existingInvoice = supplierServiceInvoice::where('supplier_invoice_number', strtoupper($supplier_invoice_number))
                ->first();

            if ($existingInvoice) {
                $internal_invoice_number = $existingInvoice->internal_invoice_number;
            } else {
                $internal_invoice_number = null;
            }

            if ($request->hasfile('eway_bill_upload')) {
                $file = $request->file('eway_bill_upload');
                $extension = $file->getClientOriginalExtension();
                $filename = $po->pono . '.' . $extension;
                Storage::disk('local')->put('supplier-eway/' . $filename, file_get_contents($file));
            }

            $q = supplierServiceInvoice::create([
                'purchase_order_id' => $poid,
                'supplier_invoice_number' => strtoupper($supplier_invoice_number),
                'eway_bill_no' => strtoupper($eway_bill_no),
                'vehicle_no' => strtoupper($vehicle_no),
                'eway_bill_pdf' => $filename,
                'user_id' => $user->id,
                'tamount' => $request['pbTotal'],
                'purchase_order_type' => $po_type,
                'invoice_date' => $request['invoice_date'],
                'tquantity' => $request['pbQty'],
                'subTotal' => $request['pbSubTotal'],
                'tgst' => $request['pbGST'],
                'internal_invoice_number' => $internal_invoice_number ?? null,
            ]);
            foreach ($request['pb'] as $key => $pb) {
                if ($pb['unit'] == 'Hours' && $po_type == "Furniture" && $pb['receiveqty'] == 0) {
                    $pb['amount'] = 0;
                }

                $poProduct = serviceTable::where('poid', $poid)->where('product_id', $pb['product'])->first();
                if ($poProduct && $poid && $po_type == "Furniture") {
                    if (($pb['unit'] ?? '') == 'Hours') {
                        $requestedQty = (float) ($pb['receiveqty'] ?? 0);
                        if ($requestedQty > (float) $poProduct->remqty + 0.0001) {
                            return redirect()->back()->withInput()->with(
                                'danger',
                                'Invoice quantity cannot exceed remaining PO quantity.'
                            );
                        }
                    }
                }

                if (isset($request['po'][$key]['sub_name'], $request['po'][$key]['sub_rate'])) {
                    foreach ($request['po'][$key]['sub_name'] as $index => $name) {
                        if (!isset($request['po'][$key]['sub_rate'][$index])) {
                            continue;
                        }
                        $existingSubService = !empty($request['po'][$key]['id'][$index])
                            ? SubServiceTable::where('id', $request['po'][$key]['id'][$index])->first()
                            : null;

                        if ($existingSubService && ($pb['unit'] ?? '') == 'Hours') {
                            $requestedSubQty = (float) ($request['po'][$key]['sub_quantity'][$index] ?? 0);
                            if ($requestedSubQty > (float) $existingSubService->sub_remqty + 0.0001) {
                                return redirect()->back()->withInput()->with(
                                    'danger',
                                    "Qty for {$name} cannot exceed remaining quantity ({$existingSubService->sub_remqty})."
                                );
                            }
                        }
                    }
                }

                $p = serviceProductTable::create([
                    'product_id' => $pb['product'],
                    'supplier_invoice_id' => $q->id,
                    'purchase_order_id' => $poid,
                    'unit' => $pb['unit'],
                    'gst' => $pb['gstslab'],
                    'amount' => $pb['amount'],
                    'total' => $pb['amount'] + (($pb['amount'] * $pb['gstslab']) / 100),
                ]);
                if ($p->unit == 'Hours') {
                    $p->quantity = ($po_type == "Furniture") ? $pb['receiveqty'] : $pb['receiveqty'];
                } else {
                    $p->quantity = 1;
                    $p->percentage = round((float) $pb['remaining_percentage'], 2);
                }
                $p->save();

                if (isset($request['po'][$key])) {
                    $poItem = $request['po'][$key];

                    if (isset($poItem['sub_name']) && isset($poItem['sub_rate'])) {
                        foreach ($poItem['sub_name'] as $index => $name) {
                            if (isset($poItem['sub_rate'][$index])) {
                                $existingSubService = null;

                                if (!empty($poItem['id'][$index])) {
                                    $existingSubService = SubServiceTable::where('id', $poItem['id'][$index])->first();
                                }

                                $invoiceSubLine = SubServiceProductTable::create([
                                    'supplier_invoice_id' => $q->id,
                                    'supplier_invoice_product_id' => $p->id,
                                    'product_id' => $p->product_id,
                                    'sub_name' => $name,
                                    'sub_rate' => $poItem['sub_rate'][$index],
                                    'sub_amount' => $poItem['sub_amount'][$index],
                                    'sub_gstamount' => $poItem['sub_gstamount'][$index],
                                    'sub_percentage' => round((float) ($poItem['sub_percentage'][$index] ?? 0), 2),
                                    'sub_quantity' => $poItem['sub_quantity'][$index] ?? 0,
                                    'sub_remqty' => 0,
                                    'sub_remaining_percentage' => 0,
                                    'sub_remaining_amount' => 0,
                                ]);
                            }
                        }
                    }
                }
            }

            ServiceInvoiceQuantityService::syncAllForPo((int) $poid);

            SubServiceProductTable::where('supplier_invoice_id', $q->id)->get()->each(function ($line) use ($poid) {
                $sub = SubServiceTable::where('po_id', $poid)
                    ->where('product_id', $line->product_id)
                    ->where('sub_name', $line->sub_name)
                    ->first();
                if ($sub) {
                    ServiceInvoiceQuantityService::snapshotSubRemainingOnInvoiceLine($line, $sub);
                }
            });

            if (!$internal_invoice_number) {
                $q->internal_invoice_number = 'GV-' . date('Y') . '-' . $q->id;
                $q->save();
            }

            $admins = User::whereIn('role', ['Admin', 'Factory'])->get();
            foreach ($admins as $admin) {
                Notification::create([
                    'user_id' => $admin->id,
                    'notification' => 'Invoice for Purchase Order - ' . $po->pono . ' has been created by the supplier ' . $user->supplier->c_name,
                    'is_read' => 0
                ]);
            }

            return redirect(url('/supplier-dashboard/service'))->with('success', 'Invoice created successfully!');
        } else {
            return response(['message' => 'PO ID is required'], 422);
        }
    }



    public function acceptServiceOrder(Request $request, $id)
    {
        $user = auth()->user();
        $states = false;
        $msg = 'Purchase order was not found.';
        $purchaseOrder = Service::where('id', $id)->first();
        if (isset($purchaseOrder->id)) {
            if (!SendToSupplierPo::isSent($purchaseOrder)) {
                $msg = 'This PO has not been sent to you yet.';
                if ($request->ajax()) {
                    return response()->json(['states' => false, 'msg' => $msg]);
                }

                return redirect(url('/supplier-dashboard/service'))->with('danger', $msg);
            }
            if ($purchaseOrder) {
                $purchaseOrder->supplier_status = 1;
                if ($purchaseOrder->save()) {
                    $states = true;
                    $msg = 'Purchase order accepted.';
                }
            }
        }
        $admins = User::where('role', 'Admin')->get();
        foreach ($admins as $admin) {
            Notification::create([
                'user_id' => $admin->id,
                'notification' => 'Purchase Order - ' . $purchaseOrder->pono . ' has been accepted by the supplier ' . $user->supplier->c_name,
                'is_read' => 0
            ]);
        }
        if ($request->ajax()) {
            return response()->json(['states' => $states, 'msg' => $msg]);
        } else {
            return redirect(url('/supplier-dashboard/service'))->with('success', 'Purchase Order accepted successfully!');
        }
    }


    public function raiseServiceInvoice(Request $request, $id)
    {
        $user = auth::user();
        $user = $user->id;
        $userSplier = User::where('id', $user)->with('supplier')->first();

          $invoiceTotal = SupplierInvoice::where('user_id', $user)
    ->where(function ($q) {
        $q->where('status', '!=', 2)
          ->orWhereNull('status');
    })
    ->whereDate('invoice_date', now()->toDateString())
    ->sum('tamount');

        $purchaseOrder = Service::where('id', $id)->first();
        if (!$purchaseOrder) {
            return redirect(url('/supplier-dashboard/accepted-service-orders'))->with(
                'danger',
                'Purchase order not found.'
            );
        }
        if ((int) $purchaseOrder->status === 1) {
            return redirect(url('/supplier-dashboard/accepted-service-orders'))->with(
                'danger',
                'This purchase order is complete. No further invoices can be raised.'
            );
        }
        ServiceInvoiceQuantityService::syncAllForPo((int) $id);
        $poTable = serviceTable::where('poid', $id)->get();
        $subServiceTable = SubServiceTable::where('po_id', $id)->with('serviceTable')->get();
        $type = 1;

        return view('supplierUser/raiseServiceInvoice', ['purchaseOrder' => $purchaseOrder, 'poTable' => $poTable, 'type' => $type, 'id' => $id, 'invoiceTotal' => $invoiceTotal, 'userSplier' => $userSplier, 'subServiceTable' => $subServiceTable]);
    }



    public function serivceTableData($id)
    {
        $poTable = serviceTable::where('poid', $id)->get();
        return response()->json(['poTable' => $poTable]);
    }


    public function getserviceInvoiceOrders()
    {
        $user = auth::user();
        if ($user->hasRole('supplier')) {
            $user_id = $user->id;
            $invoiceOrders = supplierServiceInvoice::where('user_id', $user_id)
                ->orderBy('created_at', 'desc')
                ->get();
            return view('supplierUser/service_invoiceOrders', ['supplierInvoice' => $invoiceOrders]);
        }
    }


    public function serviceinvoiceModal($id)
    {
        $supplierInvoice = supplierServiceInvoice::find($id)->where('id', $id)->first();
        //dd($supplierInvoice->purchase_order_id);
        if ($supplierInvoice->purchase_order_type == 'Furniture') {
            $purchaseOrder = Service::where('id', $supplierInvoice->purchase_order_id)->first();
        }
        $companyDetails = setting::first();
        $files = Storage::disk('s3')->files('stock');
        $fileMap1 = [];
        foreach ($files as $file) {
            $filename = basename($file);
            $fileMap1[$filename] = Storage::disk('s3')->url($file);
        }
        $supplierInvoiceProduct = serviceProductTable::where('supplier_invoice_id', $id)->with('sub_service_product')->get();
        $subsupplierInvoiceProduct = SubServiceProductTable::where('supplier_invoice_id', $id)->with('serviceProductTableIN')->get();




        foreach ($supplierInvoiceProduct as $key => $sup) {
            if ($supplierInvoice->purchase_order_type == 'Furniture') {
                $supplierInvoiceProduct[$key]['potable'] = serviceTable::where('poid', $sup->purchase_order_id)->where('product_id', $sup->product_id)->first();
            }
            $supplierInvoiceProduct[$key]['gstamount'] = $sup->amount * ($supplierInvoiceProduct[$key]['potable']->gstslab / 100);
        }

        $print = 0;
        if (isset($_REQUEST['print']) && $_REQUEST['print'] == 1) {
            $print = 1;
        }


        // return  $supplierInvoiceProduct;
        return view('supplierInvoice/service_modal', ['supplierInvoice' => $supplierInvoice, 'print' => $print, 'companyDetails' => $companyDetails, 'poTable' => $supplierInvoiceProduct, 'purchaseOrder' => $purchaseOrder, 'fileMap1' => $fileMap1, 'subsupplierInvoiceProduct' => $subsupplierInvoiceProduct]);
    }



    public function serviceeditInvoice(Request $request, $id)
    {
        $user = auth::user();
        $user = $user->id;
        $userSplier = User::where('id', $user)->with('supplier')->first();
        //  return $userSplier
        $invoiceTotal = supplierServiceInvoice::find($id)->where('id', $id)->first();

        $purchaseOrder = Service::where('id', $invoiceTotal->purchase_order_id)->first();
        ServiceInvoiceQuantityService::syncAllForPo((int) $invoiceTotal->purchase_order_id);
        $poTable = serviceTable::where('poid', $invoiceTotal->purchase_order_id)->get();






        $supplierInvoiceProduct = serviceProductTable::where('supplier_invoice_id', $id)->get();
        $subPotable = SubServiceTable::where('po_id', $invoiceTotal->purchase_order_id)->with('serviceTable')->get();
        $subserviceProduct = SubServiceProductTable::where('supplier_invoice_id', $invoiceTotal->id)->with('supplierInvoice')->get();

        // foreach($poTable as $p=>$prod){
        //     $poTable[$p]['product']['code'] = '';
        // }
        $type = 1;

        return view('supplierUser/service_editInvoice', ['purchaseOrder' => $purchaseOrder, 'poTable' => $poTable,  'invoiceTotal' => $invoiceTotal, 'userSplier' => $userSplier, 'type' => $type, 'supplierInvoiceProduct' => $supplierInvoiceProduct, 'subPotable' => $subPotable, 'subserviceProduct' => $subserviceProduct]);
    }


    public function updateServiceInvoice(Request $request, $id)
    {
        // return  $request->all();
        if (isset($request['purchase_order_id']) && !empty($request['purchase_order_id'])) {
            $po = Service::find($request['purchase_order_id']);
            if (!$po) {
                return response(['message' => 'Purchase Order not found'], 404);
            }


            $po_type = "Furniture";
            $poid = $request['purchase_order_id'];
            $supplier_id = $po->supplier_id;
            $user = User::where('supplier_id', $supplier_id)->first();

            $supplier_invoice_number = strtoupper($request->input('supp_inv_no', ''));
            $eway_bill_no = strtoupper($request->input('ewaybill', ''));
            $vehicle_no = strtoupper($request->input('vehicle_no', ''));
            $subTotal = $request['pbSubTotal'] ?? 0;
            $tcs = $request['tcs'] ?? 0;
            $filename = '';
            $total_quantity = 0;
            $totalremqty = 0;

            $q = supplierServiceInvoice::find($id);
            if (!$q) {
                return response(['message' => 'Invoice not found'], 404);
            }

            // File upload
            if ($request->hasFile('eway_bill_upload')) {
                $file = $request->file('eway_bill_upload');
                $extension = $file->getClientOriginalExtension();
                $filename = $po->pono . '.' . $extension;
                Storage::disk('local')->put('supplier-eway/' . $filename, file_get_contents($file));
            } else {
                $filename = $q->eway_bill_pdf;
            }

            // Update invoice
            $q->supplier_invoice_number = $supplier_invoice_number;
            $q->eway_bill_no = $eway_bill_no;
            $q->vehicle_no = $vehicle_no;
            $q->eway_bill_pdf = $filename;
            $q->tamount = $request['pbTotal'];
            $q->tdsTotal = $request['tdsTotal'];

            foreach ($request['pb'] as $key => $pb) {
                if (($pb['unit'] ?? '') == 'Hours' && $po_type === "Furniture" && ($pb['receiveqty'] ?? 0) == 0) {
                    $pb['amount'] = 0;
                }

                $soTable = serviceProductTable::where('supplier_invoice_id', $id)
                    ->where('product_id', $pb['product'] ?? 0)
                    ->first();

                $oldqty = 0;
                $amount = 0;

                if ($soTable) {
                    $oldqty = $soTable->quantity;
                    $amount = $soTable->amount;
                }

                $poProduct = serviceTable::where('poid', $poid)->where('product_id', $pb['product'] ?? 0)->first();
                if ($poProduct && ($pb['unit'] ?? '') === 'Hours') {
                    $maxAllowed = round((float) $poProduct->remqty + (float) $oldqty, 4);
                    $newQty = (float) ($pb['receiveqty'] ?? 0);
                    if ($newQty > $maxAllowed + 0.0001) {
                        return redirect()->back()->withInput()->with(
                            'danger',
                            'Invoice quantity cannot exceed available remaining quantity.'
                        );
                    }
                }

                if (isset($request['po'][$key]['sub_name'], $request['po'][$key]['sub_rate'])) {
                    foreach ($request['po'][$key]['sub_name'] as $index => $name) {
                        if (!isset($request['po'][$key]['sub_rate'][$index])) {
                            continue;
                        }

                        $oldSubQty = 0;
                        if ($soTable) {
                            $oldLine = SubServiceProductTable::where('supplier_invoice_product_id', $soTable->id)
                                ->where('sub_name', $name)
                                ->first();
                            $oldSubQty = $oldLine ? (float) $oldLine->sub_quantity : 0;
                        }

                        $subServiceTable = $poProduct
                            ? SubServiceTable::where('serviceTable_id', $poProduct->id)->where('sub_name', $name)->first()
                            : null;

                        if ($subServiceTable && ($pb['unit'] ?? '') === 'Hours') {
                            $maxAllowed = round((float) $subServiceTable->sub_remqty + $oldSubQty, 4);
                            $newSubQty = (float) ($request['po'][$key]['sub_quantity'][$index] ?? 0);
                            if ($newSubQty > $maxAllowed + 0.0001) {
                                return redirect()->back()->withInput()->with(
                                    'danger',
                                    "Qty for {$name} cannot exceed available quantity ({$maxAllowed})."
                                );
                            }
                        }
                    }
                }

                $p = serviceProductTable::create([
                    'product_id' => $pb['product'] ?? 0,
                    'supplier_invoice_id' => $q->id,
                    'purchase_order_id' => $poid,
                    'unit' => $pb['unit'] ?? '',
                    'gst' => $pb['gstslab'] ?? 0,
                    'amount' => $pb['amount'] ?? 0,
                    'total' => ($pb['amount'] ?? 0) + ((($pb['amount'] ?? 0) * ($pb['gstslab'] ?? 0)) / 100),
                ]);

                if ($p->unit === 'Hours') {
                    $p->quantity = $pb['receiveqty'] ?? 0;
                } else {
                    $p->quantity = 1;
                    $p->percentage = round((float) ($pb['remaining_percentage'] ?? 0), 2);
                }
                $p->save();

                $total_quantity = $request['pbQty'] ?? 0;

                if (isset($request['po'][$key]) && is_array($request['po'][$key])) {
                    $subServiceData = $request['po'][$key];
                    $names = $subServiceData['sub_name'] ?? [];

                    foreach ($names as $index => $name) {
                        if (!isset($subServiceData['sub_rate'][$index])) {
                            continue;
                        }

                        $existingSubService = null;
                        if (!empty($name) && $soTable) {
                            $existingSubService = SubServiceProductTable::where('supplier_invoice_product_id', $soTable->id)
                                ->where('sub_name', $name)
                                ->first();
                        }

                        $newSubQty = (float) ($subServiceData['sub_quantity'][$index] ?? 0);
                        $newSubAmount = (float) ($subServiceData['sub_amount'][$index] ?? 0);

                        if ($existingSubService) {
                            $existingSubService->update([
                                'sub_name' => $name,
                                'supplier_invoice_product_id' => $p->id,
                                'sub_rate' => $subServiceData['sub_rate'][$index],
                                'sub_quantity' => $newSubQty,
                                'sub_amount' => $newSubAmount,
                                'sub_gstslab' => $subServiceData['sub_gstslab'][$index] ?? 0,
                                'sub_gstamount' => $subServiceData['sub_gstamount'][$index] ?? 0,
                                'sub_percentage' => round((float) ($subServiceData['sub_percentage'][$index] ?? 0), 2),
                            ]);
                        } else {
                            SubServiceProductTable::create([
                                'supplier_invoice_id' => $id,
                                'product_id' => $pb['product'] ?? 0,
                                'supplier_invoice_product_id' => $p->id,
                                'sub_name' => $name,
                                'sub_rate' => $subServiceData['sub_rate'][$index],
                                'sub_quantity' => $newSubQty,
                                'sub_amount' => $newSubAmount,
                                'sub_percentage' => round((float) ($subServiceData['sub_percentage'][$index] ?? 0), 2),
                                'sub_remaining_percentage' => $subServiceData['sub_remaining_percentage'][$index] ?? 0,
                                'sub_remaining_amount' => $subServiceData['sub_remaining_amount'][$index] ?? 0,
                                'sub_gstslab' => $subServiceData['sub_gstslab'][$index] ?? 0,
                                'sub_gstamount' => $subServiceData['sub_gstamount'][$index] ?? 0,
                            ]);
                        }
                    }
                }

                if ($soTable) {
                    $soTable->delete();
                }
            }

            ServiceInvoiceQuantityService::syncAllForPo((int) $poid);

            SubServiceProductTable::where('supplier_invoice_id', $id)->get()->each(function ($line) use ($poid) {
                $sub = SubServiceTable::where('po_id', $poid)
                    ->where('product_id', $line->product_id)
                    ->where('sub_name', $line->sub_name)
                    ->first();
                if ($sub) {
                    ServiceInvoiceQuantityService::snapshotSubRemainingOnInvoiceLine($line, $sub);
                }
            });

            // Final invoice update
            $q->tquantity = $total_quantity;
            $q->subTotal = $subTotal;
            $q->tgst = ($request['pbTotal'] ?? 0) - $subTotal;
            $q->invoice_date = $request['invoice_date'] ?? now();
            $q->save();

            return redirect(url('/supplier-dashboard/service-invoice-orders'))
                ->with('success', 'Invoice updated successfully! Please check your email ' . ($user->email ?? ''));
        }

        return response(['message' => 'PO ID is required'], 422);
    }


    public function cancelServiceInvoice(Request $request, $id)
    {
        $supplierInvoice = supplierServiceInvoice::find($id)->where('id', $id)->where('is_approved', 0)->first();
        if (isset($supplierInvoice->id)) {
            $supplierInvoice->status = 2;
            $supplierInvoice->save();
        }

        $sitable = serviceProductTable::where('supplier_invoice_id', $id)->get();

        if ($supplierInvoice && $supplierInvoice->purchase_order_type == 'Furniture') {
            ServiceInvoiceQuantityService::syncAllForPo((int) $supplierInvoice->purchase_order_id);
        }
        //dd($supplierInvoice->purchase_order_id);
        // foreach($poTable as $p=>$prod){
        //     $poTable[$p]['product']['code'] = '';
        // }
        return redirect(url('/supplier-dashboard/service-invoice-orders'))->with('success', 'Invoice has been cancelled!');
        //return view('supplierUser/editInvoice', ['purchaseOrder'=>$purchaseOrder, 'poTable' => $poTable, 'supplierInvoiceProduct'=>$supplierInvoiceProduct,'supplierInvoice'=>$supplierInvoice]);
    }




    public function servicemodal($id)
    {

        $purchaseOrder = Service::find($id);
        $poTable = serviceTable::where('poid', $id)->with('sub_serviceproduct')->get();
        $is_consumable = 0;

        $companyDetails = setting::first();


        $files = Storage::disk('s3')->files('stock');
        $fileMap1 = [];
        foreach ($files as $file) {
            $filename = basename($file);
            $fileMap1[$filename] = Storage::disk('s3')->url($file);
        }

        $print = 0;
        if (isset($_REQUEST['print']) && $_REQUEST['print'] == 1) {
            $print = 1;
        }

        return view('supplierUser/serivce_modal', ['purchaseOrder' => $purchaseOrder, 'print' => $print, 'companyDetails' => $companyDetails, 'poTable' => $poTable, 'is_consumable' => $is_consumable, 'fileMap1' => $fileMap1]);
    }
    public function getAcceptedServiceOrders()
    {
        $user = auth::user();
        if ($user->hasRole('supplier')) {
            $supplier_id = $user->supplier_id;
            $acceptedPurchaseOrders = Service::where('supplier_id', $supplier_id)
                ->where('supplier_status', 1)
                ->orderBy('created_at', 'desc')
                ->get();
            return view('supplierUser/acceptedServiceOrders', ['purchaseOrder' => $acceptedPurchaseOrders]);
        }
    }


    public function raiseInvoicemultiple(Request $request)
    {
        $user = auth()->user()->id;

        $request->validate([
            'selected_po_ids' => 'required|string',
        ]);

        // POST → redirect to GET so refresh / reopen never hits Method Not Allowed
        if ($request->isMethod('post')) {
            return redirect()->to(
                url('/supplier-dashboard/raise-invoice/multiple') . '?' . http_build_query([
                    'selected_po_ids' => $request->input('selected_po_ids'),
                ])
            );
        }

        $selectedPOIds = explode(',', $request->input('selected_po_ids'));
        $type = 1;

        if (empty($selectedPOIds)) {
            return redirect()->back()->with('error', 'No valid Purchase Order selected.');
        }

        $purchaseOrders = PurchaseOrder::whereIn('id', $selectedPOIds)
            ->with('supplier')
            ->get();

        if ($purchaseOrders->isEmpty()) {
            return redirect()->back()->with('error', 'No Purchase Orders found.');
        }

        foreach ($purchaseOrders as $poRow) {
            $gate = PurchaseOrderVersionWriter::invoiceGateStatus((int) $poRow->id);
            if (! $gate['ok']) {
                return redirect()->back()->with(
                    'danger',
                    ($poRow->pono ?? ('PO #'.$poRow->id)).': '.$gate['message']
                );
            }
        }

        $ineligiblePoEligibility = $this->getIneligiblePurchaseOrdersWithEligibilityDate($purchaseOrders);
        if (!empty($ineligiblePoEligibility)) {
            $ineligibleSummary = collect($ineligiblePoEligibility)
                ->map(function ($eligibleOn, $poNumber) {
                    return $poNumber . ' (eligible on ' . $eligibleOn . ')';
                })
                ->implode(', ');
            return redirect()->back()->with(
                'danger',
                'Invoice cannot be generated yet for: ' . $ineligibleSummary . '.'
            );
        }

        $poTables = PoTable::whereIn('poid', $selectedPOIds)->get();

       $invoiceTotal = SupplierInvoice::where('user_id', $user)
    ->where(function ($q) {
        $q->where('status', '!=', 2)
          ->orWhereNull('status');
    })
    ->whereDate('invoice_date', now()->toDateString())
    ->sum('tamount');


        $supplierNames = $purchaseOrders->pluck('supplier.c_name')->unique()->values();
        $poNumbers = $purchaseOrders->pluck('pono')->unique()->values();
        $deliveryDates = $purchaseOrders->pluck('del_date')->unique()->values();
        $supplierRefs = $purchaseOrders->pluck('ref_supplier')->unique()->values();

        $userSplier = User::where('id', $user)->with('supplier')->first();

        return view('supplierUser.raiseInvoiceMultiple', [
            'purchaseOrders' => $purchaseOrders,
            'poTable' => $poTables,
            'type' => $type,
            'invoiceTotal' => $invoiceTotal,
            'userSplier' => $userSplier,
            'selectedPOIds' => $selectedPOIds,
            'supplierNames' => $supplierNames,
            'poNumbers' => $poNumbers,
            'deliveryDates' => $deliveryDates,
            'supplierRefs' => $supplierRefs,
        ]);
    }



    public function submitInvoicemultiple(Request $request)
    {
        // return $request->all();

        $user = auth()->user()->id;
        $userSplier = User::where('id', $user)->first();




        $selectedPOIds = explode(',', $request->input('selectedPOIds'));
        $purchaseOrders = PurchaseOrder::whereIn('id', $selectedPOIds)->get();
        foreach ($purchaseOrders as $poRow) {
            $gate = PurchaseOrderVersionWriter::invoiceGateStatus((int) $poRow->id);
            if (! $gate['ok']) {
                return back()->with(
                    'danger',
                    ($poRow->pono ?? ('PO #'.$poRow->id)).': '.$gate['message']
                )->withInput();
            }
        }
        $ineligiblePoEligibility = $this->getIneligiblePurchaseOrdersWithEligibilityDate($purchaseOrders);
        if (!empty($ineligiblePoEligibility)) {
            $ineligibleSummary = collect($ineligiblePoEligibility)
                ->map(function ($eligibleOn, $poNumber) {
                    return $poNumber . ' (eligible on ' . $eligibleOn . ')';
                })
                ->implode(', ');
            return back()->with(
                'danger',
                'Invoice cannot be generated yet for: ' . $ineligibleSummary . '.'
            )->withInput();
        }




        $supplier_id = $userSplier->supplier_id;
        $user = User::where('supplier_id', $supplier_id)->first();

     $supplier_invoice_number = strtoupper($request->input('supp_inv_no', ''));
$eway_bill_no            = strtoupper($request->input('ewaybill', ''));

        $vehicle_no = strtoupper($request->input('vehicle_no', ''));
        $imageFileName = $request->hasFile('eway_bill_upload')
            ? basename(Storage::disk('s3')->put('stock/supplier-eway', $request->file('eway_bill_upload')))
            : 'default.jpg';

        $existingInvoice = supplierInvoice::where('supplier_invoice_number', $supplier_invoice_number)->first();
         $errors = [];
if (!empty($eway_bill_no)) {
            $existseway_bill_no = supplierInvoice::where('supplier_id', $supplier_id)
                ->where('eway_bill_no', $eway_bill_no)
                ->where(function ($q) {
                    $q->where('status', '!=', 2)
                        ->orWhereNull('status');
                })
                ->exists();

            if ($existseway_bill_no) {
                $errors['eway_bill_no'] = 'This E-way Bill number already exists for this supplier.';
            }
        }

        $existssupplier_invoice_number = supplierInvoice::where('supplier_id', $supplier_id)
            ->where('supplier_invoice_number', $supplier_invoice_number)
            ->where(function ($q) {
        $q->where('status', '!=', 2)
          ->orWhereNull('status');
    })
    ->exists();



        if ($existssupplier_invoice_number) {
            $errors['supplier_invoice_number'] = 'This Supplier Invoice number already exists for this supplier.';
        }
        if (!empty($errors)) {
            return back()->withErrors($errors)->withInput();
        }


        $internal_invoice_number = $existingInvoice ? $existingInvoice->internal_invoice_number : null;

        // Create supplier invoice
        $invoice = supplierInvoice::create([
            'purchase_order_id' => implode(',', $selectedPOIds),
            'supplier_invoice_number' => $supplier_invoice_number,
            'eway_bill_no' => $eway_bill_no,
            'vehicle_no' => $vehicle_no,
            'eway_bill_pdf' => $imageFileName,
            'supplier_id' => $user->supplier_id,
            'user_id' => $user->id,
            'tamount' => $request->pbTotal,
            'purchase_order_type' => 'Furniture',
            'invoice_date' => $request->invoice_date,
            'totaldiscount' => $request->totaldiscount,
            'tquantity' => $request->pbQty,
            'subTotal' => $request->pbSubTotal,
            'tgst' => $request->pbGST,
            'roundoff' => $request->roundoff,
            'internal_invoice_number' => $internal_invoice_number,
            'mulitple_po' => $request->poNumbers,
        ]);

        // Process products
        foreach ($request->pb as $pb) {
            $totalReceiveQty = $pb['receiveqty'];
            $productId = $pb['product'];
            $gst = $pb['gstslab'];
            $rate = $pb['rate'];
            $amount =  $pb['amount'] - $pb['remaing_discount'];
            // Insert into soTable
            $p = soTable::create([
                'product_id' => $productId,
                'supplier_invoice_id' => $invoice->id,
                'purchase_order_id' =>  $pb['poid'],
                'gst' => $gst,
                'amount' => $pb['amount'],
                'total' => $pb['remaing_discount'] + (($pb['amount'] * $gst) / 100),
                'quantity' => $totalReceiveQty,
                'mulitple_po' => (int) $pb['poid'],
            ]);

            $remainingQtyToDeduct = $totalReceiveQty;

            // Deduct from each PO remqty
            // foreach ($selectedPOIds as $poid) {


            $poProduct = poTable::where('poid', $pb['poid'])
                ->where('product_id', $productId)
                ->first();



            if ($poProduct && $poProduct->remqty > 0) {
                $deductQty = min($poProduct->remqty, $remainingQtyToDeduct);
                $poProduct->remqty -= $deductQty;


                $p->discount_type = $pb['discount_type'];
                $p->discount = $pb['discountamount'];
                $poProduct->remaining_discount = $poProduct->remaining_discount - $pb['discountamount'];

                $p->save();
                $poProduct->save();
            }
            // }
        }

        // Generate internal invoice number if new
        if (!$internal_invoice_number) {
            $invoice->internal_invoice_number = 'GV-' . date('Y') . '-' . $invoice->id;
            $invoice->save();
        }

        // Update each PO status and remqty
        foreach ($selectedPOIds as $poid) {
            $remQty = poTable::where('poid', $poid)->sum('remqty');
            $po = PurchaseOrder::find($poid);
            if ($po) {
                $po->remqty = $remQty;

                $po->save();
            }
        }

        return redirect(url('/supplier-dashboard/accepted-purchase-orders'))
            ->with('success', 'Invoice against multiple POs generated successfully!');
    }




    public function getInvoiceOrdersmulti()
    {
        $user = auth::user();
        if ($user->hasRole('supplier')) {
            $user_id = $user->id;
            $invoiceOrders = supplierInvoice::where('user_id', $user_id)
                ->whereNotNull('mulitple_po')
                ->where('purchase_order_type', 'Furniture')
                ->orderByDesc('created_at')
                ->get();

            return view('supplierUser/invoiceOrdersMulti', ['supplierInvoice' => $invoiceOrders]);
        }
    }



    public function invoiceModalMulti($id)
    {
        $supplierInvoice = supplierInvoice::findOrFail($id);
        $companyDetails = setting::first();

        $files1 = Storage::disk('s3')->files('stock');
        $fileMap1 = [];
        foreach ($files1 as $file) {
            $filename = basename($file);
            $fileMap1[$filename] = Storage::disk('s3')->url($file);
        }

        $isFurniture = $supplierInvoice->purchase_order_type === 'Furniture';
        if ($isFurniture) {
            $poIds = array_values(array_filter(array_map('intval', array_map(
                'trim',
                explode(',', (string) $supplierInvoice->purchase_order_id)
            ))));
            $purchaseOrders = purchaseOrder::whereIn('id', $poIds)->get()->keyBy('id');
            $firstPOId = $poIds[0] ?? null;
            $firstPO = $firstPOId ? ($purchaseOrders[$firstPOId] ?? purchaseOrder::find($firstPOId)) : null;
        } else {
            $poIds = SupplierMultiPoSupport::normalizeConsumablePoIds($supplierInvoice->purchase_order_id);
            $purchaseOrders = purchaseOrderConsumable::whereIn('id', $poIds)->get()->keyBy('id');
            $firstPOId = $poIds[0] ?? null;
            $firstPO = $firstPOId ? ($purchaseOrders[$firstPOId] ?? purchaseOrderConsumable::find($firstPOId)) : null;
        }

        $podate = $purchaseOrders->pluck('podate')->filter()->unique()->values();
        $del_date = $purchaseOrders->pluck('del_date')->filter()->unique()->values();

        $supplierInvoiceProduct = supplierInvoiceProduct::where('supplier_invoice_id', $id)
            ->with(['product', 'consumable'])
            ->get();

        foreach ($supplierInvoiceProduct as $key => $sup) {
            $poid = $sup->purchase_order_id;

            if ($isFurniture) {
                $poTableRow = poTable::where('poid', $poid)
                    ->where('product_id', $sup->product_id)
                    ->first();

                $supplierInvoiceProduct[$key]['potable'] = $poTableRow;
                $gstRate = $poTableRow->gstslab ?? 0;
                $supplierInvoiceProduct[$key]['rate'] = $poTableRow->rate ?? 0;
                $supplierInvoiceProduct[$key]['gstslab'] = $gstRate;
                $supplierInvoiceProduct[$key]['gstamount'] = $sup->amount * ($gstRate / 100);
                $linePo = $purchaseOrders->get($poid);
                $supplierInvoiceProduct[$key]['mulitple_po'] = $linePo->pono ?? $sup->mulitple_po;
            } else {
                $po = $purchaseOrders->get($poid) ?? purchaseOrderConsumable::find($poid);
                if (! $po) {
                    continue;
                }
                if ((int) $po->type === 2) {
                    $supplierInvoiceProduct[$key]['potable'] = popTable::where('poid', $poid)
                        ->where('product_id', $sup->product_id)
                        ->first();
                    $potable = $supplierInvoiceProduct[$key]['potable'];
                    $gstslab = (float) (optional($potable)->gstslab ?? $sup->gst ?? 0);
                    $supplierInvoiceProduct[$key]['gstslab'] = $gstslab;
                    $supplierInvoiceProduct[$key]['gstamount'] = round((float) $sup->amount * $gstslab / 100.0, 2);
                } else {
                    $potable = ConsumableMonthEndInvoiceSupport::resolvePotableForSupplierInvoiceLine(
                        $po,
                        $sup
                    );
                    $supplierInvoiceProduct[$key]['potable'] = $potable;
                    $gstslab = (float) (optional($potable)->gstslab ?? $sup->gst ?? 0);
                    $supplierInvoiceProduct[$key]['gstslab'] = $gstslab;
                    $supplierInvoiceProduct[$key]['gstamount'] = ConsumableMonthEndInvoiceSupport::supplierInvoiceLineGstAmount(
                        $po,
                        $sup,
                        $potable
                    );
                }
                $supplierInvoiceProduct[$key]['mulitple_po'] = $po->pono;
            }
        }

        $print = request('print') == 1 ? 1 : 0;

        $footerTgst = null;
        if (! $isFurniture) {
            $hasMonthEndPo = $purchaseOrders->contains(
                fn ($po) => $po instanceof purchaseOrderConsumable
                    && ConsumableMonthEndInvoiceSupport::isMonthEndConsumablePo($po)
            );
            if ($hasMonthEndPo) {
                $footerTgst = (float) $supplierInvoice->tgst;
            }
        }

        return view('supplierInvoice/modalmulti', [
            'supplierInvoice' => $supplierInvoice,
            'print' => $print,
            'companyDetails' => $companyDetails,
            'poTable' => $supplierInvoiceProduct,
            'purchaseOrders' => $purchaseOrders,
            'purchaseOrder' => $firstPO,
            'fileMap1' => $fileMap1,
            'podate' => $podate,
            'del_date' => $del_date,
            'multiPoNumbers' => SupplierMultiPoSupport::displayMultiPoNumbers($supplierInvoice),
            'footerTgst' => $footerTgst,
        ]);
    }



    public function editInvoicemulti(Request $request, $id)
    {
        $user = auth::user()->id;
        $userSplier = User::where('id', $user)->with('supplier')->first();

        $supplierInvoice = supplierInvoice::findOrFail($id);

        if (! empty($supplierInvoice->mulitple_po)) {
            if ($supplierInvoice->purchase_order_type === 'Consumable') {
                return redirect(url('/supplier-dashboard/edit-invoice/multi-consumable/' . $id));
            }
            if ($supplierInvoice->purchase_order_type === 'Carton') {
                return redirect(url('/supplier-dashboard/edit-invoice/multi-carton/' . $id));
            }
        }

        if ($supplierInvoice->purchase_order_type !== 'Furniture') {
            return redirect()->back()->with('danger', 'This invoice cannot be edited from the furniture multi-invoice screen.');
        }

        $poIds = array_values(array_filter(array_map('trim', explode(',', (string) $supplierInvoice->purchase_order_id))));
        $firstPOId = $poIds[0] ?? null;

        $supplierInvoiceProduct = supplierInvoiceProduct::where('supplier_invoice_id', $id)->get();

        $purchaseOrders = purchaseOrder::whereIn('id', $poIds)->get()->keyBy('id');
        $supplierNames = $purchaseOrders->pluck('supplier.c_name')->unique()->values();
        $poNumbers = $purchaseOrders->pluck('pono')->unique()->values();
        $deliveryDates = $purchaseOrders->pluck('del_date')->unique()->values();
        $supplierRefs = $purchaseOrders->pluck('ref_supplier')->unique()->values();
        $firstPO = $firstPOId ? ($purchaseOrders[$firstPOId] ?? null) : null;
        $poTable = poTable::whereIn('poid', $poIds)->get();

        return view('supplierUser/editInvoicemulti', [
            'purchaseOrders' => $purchaseOrders,
            'purchaseOrder' => $firstPO,
            'poTable' => $poTable,
            'supplierInvoiceProduct' => $supplierInvoiceProduct,
            'supplierInvoice' => $supplierInvoice,
            'userSplier' => $userSplier,
            'poNumbers' => $poNumbers,
            'supplierNames' => $supplierNames,
            'deliveryDates' => $deliveryDates,
            'supplierRefs' => $supplierRefs,

        ]);
    }



    public function updateInvoicemulti(Request $request, $id)
    {
        // return $req  uest->all();
        // return $request->all();
        $q = supplierInvoice::find($id);
        $selectedPOIds = explode(',', $request->input('poids'));
        $purchaseOrderIds = PurchaseOrder::whereIn('pono', $selectedPOIds)
            ->pluck('id')
            ->toArray();
        $total_quantity = 0;
        $subTotal = 0;
        $totalRemQtyMap = [];
        $supplier_id = $q->supplier_id;
        $supplier_invoice_number = strtoupper($request['supp_inv_no'] ?? '');
        $eway_bill_no = strtoupper($request['ewaybill'] ?? '');

      
if ($eway_bill_no) {
        $existseway_bill_no = supplierInvoice::where('supplier_id', $supplier_id)
            ->where('eway_bill_no', $eway_bill_no)

            ->where('id', '!=', $id)
           ->where(function ($q) {
        $q->where('status', '!=', 2)
          ->orWhereNull('status');
    })
    ->exists();
}

        $existssupplier_invoice_number = supplierInvoice::where('supplier_id', $supplier_id)
            ->where('supplier_invoice_number', $supplier_invoice_number)

            ->where('id', '!=', $id)
            ->where(function ($q) {
        $q->where('status', '!=', 2)
          ->orWhereNull('status');
    })
    ->exists();

        $errors = [];
if ($eway_bill_no) {
        if ($existseway_bill_no) {
            $errors['ewaybill'] = 'This Eway Bill number already exists for this supplier.';
        }
}
        if ($existssupplier_invoice_number) {
            $errors['supp_inv_no'] = 'This Supplier Invoice number already exists for this supplier.';
        }

        // If any error, return back with validation messages
        if (!empty($errors)) {
            return back()->withErrors($errors)->withInput();
        }


        // Handle eWay bill upload
        if ($request->hasFile('eway_bill_upload')) {
            $filePath = Storage::disk('s3')->put('stock/supplier-eway', $request->file('eway_bill_upload'));
            $imageFileName = basename($filePath);
        } else {
            $imageFileName = $q->eway_bill_pdf;
        }
        // 'purchase_order_id' => implode(',', $selectedPOIds),
        // Update invoice basic fields
      $q->supplier_invoice_number = strtoupper($request['supp_inv_no'] ?? '');
$q->eway_bill_no            = strtoupper($request['ewaybill'] ?? '');

        $q->purchase_order_id = implode(',', $selectedPOIds);
        $q->vehicle_no = strtoupper($request['vehicle_no'] ?? '');
        $q->eway_bill_pdf = $imageFileName;
        $q->tamount = $request['pbTotal'];
        $q->tdsTotal = $request['tdsTotal'];
        $q->roundoff = $request['roundoff'];
        $q->invoice_date = $request['invoice_date'] ?? null;
        $q->mulitple_po = $request['mulitple_po'] ?? null;
        $q->totaldiscount = $request['totaldiscount'] ?? null;


        // Clean up old soTable entries and restore old remqty
        $existingSoTables = soTable::where('supplier_invoice_id', $id)->get();
        foreach ($existingSoTables as $entry) {
            $poid = $entry->purchase_order_id;
            $product_id = $entry->product_id;

            if ($poid) {
                $po = purchaseOrder::find($poid);
                $po_type = $po ? "Furniture" : "Consumable";


                if ($po_type == "Furniture") {
                    $poProduct = poTable::where('poid', $poid)->where('product_id', $product_id)->first();
                }

                if ($poProduct) {
                    $poProduct->remqty += $entry->quantity;

                    $poProduct->remaining_discount += $entry->discount;

                    $poProduct->save();
                }

                $entry->delete();
            }
        }

        // Insert new soTable entries and update remqty
        foreach ($request['pb'] as $pb) {
            $poid = $pb['poid'];
            $product_id = $pb['product'];
            $qty = $pb['receiveqty'];
            $amount = $pb['amount'];
            $gst = $pb['gstslab'];
            $amount2 =   $pb['discountamount'];


            if ($qty == 0) $amount = 0;
            $po = purchaseOrder::find($poid);
            // Create soTable entry
            soTable::create([
                'product_id' => $product_id,
                'supplier_invoice_id' => $id,
                'purchase_order_id' => $poid,
                'quantity' => $qty,
                'gst' => $gst,
                'amount' => $amount,
                'discount_type'       => $pb['discount_type'],
                'discount'            => $pb['remaing_discount'],
                'total' => $pb['discountamount'] + ($amount * $gst / 100),
                'mulitple_po' => (int) $poid,
            ]);

            $total_quantity += $qty;
            $subTotal += $amount2;

            // Adjust remqty in po table
            $po = purchaseOrder::find($poid);
            $po_type = $po ? "Furniture" : "Consumable";



            if ($po_type == "Furniture") {
                $poProduct = poTable::where('poid', $poid)->where('product_id', $product_id)->first();
            }

            if ($poProduct) {
                $poProduct->remqty -= $qty;
                $poProduct->remaining_discount -=  $pb['remaing_discount'];

                $poProduct->save();

                // Track remqty per PO
                if (!isset($totalRemQtyMap[$poid])) {
                    $totalRemQtyMap[$poid] = 0;
                }
                $totalRemQtyMap[$poid] += $poProduct->remqty;
            }
        }

        $q->tquantity = $request['pbQty'];
        $q->subTotal = $request['pbSubTotal'];
        $q->tgst = $request['pbGST'];
        $q->save();

        // Update remqty and status for each PO
        foreach ($totalRemQtyMap as $poid => $remqty) {
            $po = purchaseOrder::find($poid) ?? purchaseOrderConsumable::find($poid);
            if ($po) {
                $po->remqty = $remqty;
                $po->save();
            }
        }

        return redirect('/supplier-dashboard/invoice-orders/multi')->with('success', 'Invoice updated successfully!');
    }



    // GET /supplier-dashboard/eligible-pos?exclude=1,2,3
    public function eligiblePOs(Request $request)
    {
        $exclude = collect(explode(',', (string) $request->query('exclude')))
            ->filter()->map('intval')->all();

        $supplierId = optional(Auth::user())->supplier_id;

        $query = PurchaseOrder::query()
            ->where('supplier_status', 1)               // accepted only
            ->when($supplierId, fn($q) => $q->where('supplier_id', $supplierId)) // scope to supplier
            ->when(!empty($exclude), fn($q) => $q->whereNotIn('id', $exclude))
            ->orderByDesc('podate')
            ->select(['id', 'pono', 'ref_supplier', 'del_date', 'tquantity', 'tamount']);

        return response()->json($query->limit(200)->get());
    }

    protected function getInvoiceEligibilityDateFromPurchaseOrders($purchaseOrders): ?Carbon
    {
        if (!$purchaseOrders) {
            return null;
        }

        $latestDeliveryDate = null;
        foreach ($purchaseOrders as $purchaseOrder) {
            $rawDeliveryDate = optional($purchaseOrder)->del_date;
            if (empty($rawDeliveryDate)) {
                continue;
            }

            try {
                $parsedDeliveryDate = Carbon::parse($rawDeliveryDate)->startOfDay();
            } catch (\Throwable $e) {
                continue;
            }

            if ($latestDeliveryDate === null || $parsedDeliveryDate->gt($latestDeliveryDate)) {
                $latestDeliveryDate = $parsedDeliveryDate;
            }
        }

        if ($latestDeliveryDate === null) {
            return null;
        }

        return $latestDeliveryDate->copy()->subDays(7)->startOfDay();
    }

    protected function getIneligiblePurchaseOrdersWithEligibilityDate($purchaseOrders): array
    {
        if (!$purchaseOrders) {
            return [];
        }

        $today = Carbon::today();
        $ineligible = [];

        foreach ($purchaseOrders as $purchaseOrder) {
            $rawDeliveryDate = optional($purchaseOrder)->del_date;
            if (empty($rawDeliveryDate)) {
                continue;
            }

            try {
                $deliveryDate = Carbon::parse($rawDeliveryDate)->startOfDay();
            } catch (\Throwable $e) {
                continue;
            }

            $eligibleDate = $deliveryDate->copy()->subDays(7)->startOfDay();
            if ($today->lt($eligibleDate)) {
                $poNumber = trim((string) (optional($purchaseOrder)->pono ?? 'PO'));
                $ineligible[$poNumber] = $eligibleDate->format('d-m-Y');
            }
        }

        return $ineligible;
    }

    // GET /supplier-dashboard/po/{id}/detail
    public function poDetail($id)
    {
        $po = PurchaseOrder::with(['supplier:id,c_name', 'poTable.product:id,code,name'])
            ->select(['id', 'pono', 'ref_supplier', 'del_date', 'tquantity', 'tamount', 'subTotal', 'supplier_id'])
            ->findOrFail($id);

        // Authorization safety (optional but recommended)
        if (Auth::check() && Auth::user()->supplier_id && Auth::user()->supplier_id !== $po->supplier_id) {
            abort(403);
        }

        $rows = PoTable::where('poid', $po->id)->get()->map(function ($r) {
            return [
                'poid'                => $r->poid,
                'EAN'                 => $r->EAN,
                'remqty'              => (float) $r->remqty,
                'rate'                => (float) $r->rate,
                'gstslab'             => (float) $r->gstslab,
                'remaining_discount'  => (float) $r->remaining_discount,
                'discount_type'       => $r->discount_type,
                'product_id'          => $r->product_id,
                'product_code'        => optional($r->product)->code,
                'product_name'        => optional($r->product)->name,
            ];
        })->values();

        return response()->json([
            'po'   => [
                'id'            => $po->id,
                'pono'          => $po->pono,
                'ref_supplier'  => $po->ref_supplier,
                'del_date'      => $po->del_date,
                'subTotal'      => (float) $po->subTotal,
                'tquantity'     => (float) $po->tquantity,
                'tamount'       => (float) $po->tamount,
                'supplier_name' => optional($po->supplier)->c_name,
            ],
            'rows' => $rows,
        ]);
    }
}
