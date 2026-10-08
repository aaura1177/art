<?php

use Illuminate\Http\Request;
use App\invoice;
use App\invoiceTable;
use App\certificate;
use App\setting;
use App\User;
use App\purchaseOrder;
use App\purchaseOrderRecommended;
use App\poTable;
use App\porTable;
use App\pbTable;
use App\product;
use App\packingListProduct;
use App\packingList;
use App\Batch;
use App\BatchProduct;
use App\supplier;
use App\pricingTable;
use App\purchaseBill;
use App\stockLog;
use App\stockout;
use App\stockoutTable;
use App\suggestedProduct;
use App\supplierInvoice;
use App\soTable;
use App\rejectRepair;
use App\Notification;
use App\interestedCompany;
use App\interestedCompanyPhoto;
use App\purchaseOrderConsumable;
use App\pocTable;
use App\popTable;
use App\questionnaire;
use App\questionnaireResponse;
use App\shipping;
use App\shippingLines;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use \Illuminate\Auth\Access\AuthorizationException;
use App\Mail\OnePageEmail;
use App\Mail\SupplierInvoiceMail;
use App\Mail\InvoiceMail;
use App\Mail\StockReportMail;
use App\RejectRepairPtable;
use App\ErpProduct;
use App\ErpHistory;
use App\ErpSheet;
use App\Http\Controllers\invoiceController;
use App\Http\Controllers\SupplierTermsController;
use App\ManualStockoutPendingConsumable;
use App\Http\Controllers\Auth\ApiPasswordResetController;
use App\Http\Controllers\BackOrderAutomationController;
use App\Http\Controllers\TallyApiController;
use App\Http\Controllers\SustainabilityPopulateDataController;
use Illuminate\Support\Facades\DB;


/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

if (\Request::is('api/*')) {
        $request = $_REQUEST;
        if (isset($request['api_token'])) {
                $user = User::where('api_token', $request['api_token'])->first();
                if (!$user) {
                        $response['error'] = 'Not Allowed';
                        return response($response, 403);
                }
        }
}

Route::middleware('auth:api')->get('/user', function (Request $request) {
        //return $request->user();
});

Route::get('/getToken', function (Request $request) {
        $user = user::find(4);
        //$token = Str::random(60);
        //$user->api_token = Hash('sha256', $token);
        //$user->save();
        return ['token' => $user->api_token];
});

Route::post('/supplier_login', function (Request $request) {
        $validator = Validator::make($request->all(), [
                'email' => 'required|string|email|max:255'
        ]);
        if ($validator->fails()) {
                return response(['errors' => $validator->errors()->all()], 422);
        }
        $user = User::where('email', $request->email)->first();
        if ($user) {
                if (Hash::check($request->password, $user->password)) {
                        $token = Str::random(60);
                        $user->api_token = Hash('sha256', $token);
                        $user->device_token = $request->firebase_token;
                        $user->save();
                        $response['success'] = 'true';
                        $response['message'] = 'Login SuccessFul!';
                        $response['body'] = $user;
                        return response($response, 200);
                } else {
                        $response = ["message" => "Password mismatch"];
                        return response($response, 422);
                }
        } else {
                $response = ["message" => 'User does not exist'];
                return response($response, 422);
        }
});

Route::middleware('auth:api')->get('/supplier_terms', [SupplierTermsController::class, 'apiForSupplier']);
Route::middleware('auth:api')->post('/supplier_terms/accept', [SupplierTermsController::class, 'apiAcceptForSupplier']);

Route::middleware('auth:api')->get('/invoice', function (Request $request) {
        $invoices = invoice::get();
        foreach ($invoices as $key => $invoice) {
                $invoices[$key]['products']     =       invoiceTable::selectRaw('*, (SELECT code FROM product_table WHERE id=invoiceTable.product_id) as sku')->where('invoice_id', $invoice->id)->orderBy('box', 'ASC')->get();
        }
        return response()->json($invoices);
});

Route::middleware('auth:api')->get('/invoiceUS', function (Request $request) {
        $invoices = Invoice::where('buyerorderno', 'like', '%US-18%')->where('date', '>', '2023-12-31')->get();
        foreach ($invoices as $key => $invoice) {
                $invoices[$key]['products']     =       invoiceTable::selectRaw('*, (SELECT code FROM product_table WHERE id=invoiceTable.product_id) as sku')->where('invoice_id', $invoice->id)->orderBy('box', 'ASC')->get();
        }
        return response()->json($invoices);
});

Route::middleware('auth:api')->get('/invoiceEU', function (Request $request) {
        $invoices = Invoice::where('buyerorderno', 'like', '%EU-18%')->where('date', '>', '2023-12-31')->get();
        foreach ($invoices as $key => $invoice) {
                $invoices[$key]['products']     =       invoiceTable::selectRaw('*, (SELECT code FROM product_table WHERE id=invoiceTable.product_id) as sku')->where('invoice_id', $invoice->id)->orderBy('box', 'ASC')->get();
        }
        return response()->json($invoices);
});

Route::middleware('auth:api')->get('/invoiceUK', function (Request $request) {
    $invoices = Invoice::where('buyerorderno', 'like', '%UK-18%')
    ->where('date', '>', '2023-12-31')
    ->get();

    foreach ($invoices as $key => $invoice) {
        $invoices[$key]['products'] = invoiceTable::selectRaw('*, (SELECT code FROM product_table WHERE id=invoiceTable.product_id) as sku')
            ->where('invoice_id', $invoice->id)
            ->orderBy('box', 'ASC')
            ->get();
    }

    return response()->json($invoices);
});

Route::middleware('auth:api')->get('/invoiceAU', function (Request $request) {
    $invoices = Invoice::where('buyerorderno', 'like', '%AU-18%')
    ->where('date', '>', '2026-04-30')
    ->get();

    foreach ($invoices as $key => $invoice) {
        $invoices[$key]['products'] = invoiceTable::selectRaw('*, (SELECT code FROM product_table WHERE id=invoiceTable.product_id) as sku')
            ->where('invoice_id', $invoice->id)
            ->orderBy('box', 'ASC')
            ->get();
    }

    return response()->json($invoices);
});


Route::middleware('auth:api')->get('/invoiceCanada', function (Request $request) {
    $invoices = Invoice::where(function ($query) {
        $query->where('buyerorderno', 'like', '%CA-18%')
              ->orWhere('buyerorderno', 'like', '%CA-05%');
    })
    ->where('date', '>', '2025-09-01')
    ->get();

    foreach ($invoices as $key => $invoice) {
        $invoices[$key]['products'] = invoiceTable::selectRaw('*, (SELECT code FROM product_table WHERE id=invoiceTable.product_id) as sku')
            ->where('invoice_id', $invoice->id)
            ->orderBy('box', 'ASC')
            ->get();
    }

    return response()->json($invoices);
});



Route::middleware('auth:api')->get('/erpproductdetails', function (Request $request) {
        $country = $request['EAN'];
        $products = ErpProduct::where("site_access", $country)->get();
        return response()->json($products);
});

Route::middleware('auth:api')->get('/po', function (Request $request) {
        $purchaseOrders = purchaseOrder::get();
        foreach ($purchaseOrders as $key => $purchaseOrder) {
                $purchaseOrders[$key]['products']       =       poTable::selectRaw('*, (SELECT code FROM product_table WHERE id=poTable.product_id) as sku')->where('poid', $purchaseOrder->id)->where('remqty', '>', '0')->orderBy('id', 'ASC')->get();
        }
        return response()->json($purchaseOrders);
});

Route::middleware('auth:api')->get('/stock', function (Request $request) {
        $product = product::select('code', 'name', 'quantity')->get();
        return response()->json($product);
});

Route::middleware('auth:api')->get('/pricing', function (Request $request) {
        $pricing = pricingTable::selectRaw('*, (SELECT code FROM product_table WHERE id=pricingTable.product_id) as sku')->where('buyer_id2', 3)->get();
        return response()->json($pricing);
});

Route::middleware('auth:api')->post('/createpo', function (Request $request) {
        $payLoad = json_decode(request()->getContent(), true);
        //print_r($payLoad);die;
        $supplier = supplier::where('c_name', $payLoad['supplier'])->first();
        if (isset($supplier->id)) {
                $q = purchaseOrder::create([
                        'pono' => strtoupper($payLoad['pono']),
                        'supplier_id' => $supplier->id,
                        'podate' => $payLoad['podate'],
                        'del_date' => $payLoad['del_date'],
                        'ref_supplier' => strtoupper($payLoad['ref_supplier']),
                        'buyer_orderno' => strtoupper($payLoad['buyer_orderno']),
                        'payterms' => $payLoad['payterms'],
                        'remarks' => $payLoad['remarks'],
                        'subTotal' => $payLoad['subTotal'],
                        'tgst' => $payLoad['tgst'],
                        'tquantity' => $payLoad['tquantity'],
                        'tamount' => $payLoad['tamount'],
                        'remqty' => $payLoad['tquantity'],
                        'created_via' => 'API'
                ]);

                foreach ($payLoad['po'] as $po) {
                        $product = product::where('code', $po['product_code'])->first();
                        if (isset($product->id)) {
                                poTable::create([
                                        'product_id' => $product->id,
                                        'ean' => $po['ean'],
                                        'poid' => $q->id,
                                        'quantity' => $po['quantity'],
                                        'unit' => $po['unit'],
                                        'remqty' => $po['quantity'],
                                        'rate' => $po['rate'],
                                        'amount' => $po['amount'],
                                        'gstslab' => $po['gstslab'],
                                        'gstamount' => $po['gstamount']
                                ]);
                        }
                }
                $response['msg'] = 'PO created successfully';
                $response['err'] = 0;
        } else {
                $response['msg'] = 'Supplier not found';
                $response['err'] = 1;
        }

        return response()->json($response);
});

Route::middleware('auth:api')->post('/createinvoice', function (Request $request) {
        $payLoad = json_decode(request()->getContent(), true);
        $consignee = buyer::where('c_name', $payLoad['consignee'])->get();
        if (isset($consignee->id)) {
                $consignee_id = $consignee->id;
        } else {
                $consignee_id = 0;
        }
        $buyer = buyer::where('c_name', $payLoad['buyer'])->get();
        if (isset($buyer->id)) {
                $buyer_id = $buyer->id;
        } else {
                $buyer_id = 0;
        }
        $q = invoice::create([
                'consignee_id' => $consignee_id,
                'buyer_id' => $buyer_id,
                'date' => $payLoad['date'],
                'buyerorderno' => strtoupper($payLoad['buyerorderno']),
                'containerno' => strtoupper($payLoad['containerno']),
                'vehicleno' => $payLoad['vehicleno'],
                'totalbox' => $payLoad['totalbox'],
                'pkgs' => $payLoad['pkgs'],
                'currency' => $payLoad['currency'],
                'fob' => $payLoad['fob'],
                'payterms' => $payLoad['payterms'],
                'shipmentby' => $payLoad['shipmentby'],
                'desgoods' => $payLoad['desgoods'],
                'carriage' => $payLoad['carriage'],
                'receipt' => $payLoad['receipt'],
                'shipment' => $payLoad['shipment'],
                'postloading' => $payLoad['postloading'],
                'discharge' => $payLoad['discharge'],
                'destination' => $payLoad['destination'],
                'totalgst' => $payLoad['totalgst'],
                'totalquantity' => $payLoad['tquantity'],
                'totalwt' => $payLoad['totalwt'],
                'totalgrosswt' => $payLoad['grosswt'],
                'totalamount' => $payLoad['totalamount'],
                'conrate' => $payLoad['cunrate'],
                'rateamount' => $payLoad['rateamount'],
                'invoiceno' => strtoupper($payLoad['invoiceno']),
                'invoicetype' => $payLoad['invoicetype'],
                'exportstatus' => $payLoad['exportstatus'],
                'ewaybillno' => strtoupper($payLoad['ewaybillno']),
                'declaration' => $payLoad['declaration'],
                'shipping_charges' => $payLoad['shipping_charges'],
                'packing_charges' => $payLoad['packing_charges'],
                'discount' => $payLoad['discount']
        ]);

        foreach ($payLoad['inv'] as $inv) {
                $product = product::where('code', $inv['product_code'])->first();
                invoiceTable::create([
                        'product_id' => $product->id,
                        'invoice_id' => $q->id,
                        'quantity' => $inv['quantity'],
                        'rate' => $inv['rate'],
                        'amount' => $inv['amount'],
                        'weight' => $inv['weight'],
                        'subtotalnetwt' => $inv['subtotalnetwt'],
                        'grosswt' => $inv['grosswt'],
                        'subtotalgrosswt' => $inv['subTotalGrossWT'],
                        'gstslab' => $inv['gstslab'],
                        'box' => $inv['box'],
                        'endbox' => $inv['endBox'],
                        'subtotalbox' => $inv['subTotalBox'],
                        'qtybox' => $inv['qtybox'],
                        'gstamount' => $inv['gstamount'],
                        'remqty' => $inv['quantity'],
                        'descriptionBox' => $inv['descriptionBox']
                ]);
        }

        $response['msg'] = 'Invoice created successfully';
        $response['err'] = 0;
        return response()->json($response);
});


Route::middleware('auth:api')->get('/pendingpos', function (Request $request) {
        $po = purchaseOrder::where('status', 0)->where('supplier_id', '!=', 17)->get();
        foreach ($po as $k => $p) {
                $po[$k]['products'] = poTable::where('poid', $p->id)->where('remqty', '>', 0)->get();
        }
        return response()->json($po);
});

Route::middleware('auth:api')->post('/createRecommendedPo', function (Request $request) {
        $payLoad = json_decode(request()->getContent(), true);
        //print_r($payLoad);die;
        $supplier = supplier::where('c_name', $payLoad['supplier'])->first();
        if (isset($supplier->id)) {
                $q = purchaseOrderRecommended::create([
                        'pono' => strtoupper($payLoad['pono']),
                        'supplier_id' => $supplier->id,
                        'podate' => $payLoad['podate'],
                        'del_date' => $payLoad['del_date'],
                        'ref_supplier' => strtoupper($payLoad['ref_supplier']),
                        'buyer_orderno' => strtoupper($payLoad['buyer_orderno']),
                        'payterms' => $payLoad['payterms'],
                        'remarks' => $payLoad['remarks'],
                        'subTotal' => $payLoad['subTotal'],
                        'tgst' => $payLoad['tgst'],
                        'tquantity' => $payLoad['tquantity'],
                        'tamount' => $payLoad['tamount'],
                        'remqty' => $payLoad['tquantity'],
                        'created_via' => 'API'
                ]);

                foreach ($payLoad['po'] as $po) {
                        $product = product::where('code', $po['product_code'])->first();
                        if (isset($product->id)) {
                                porTable::create([
                                        'product_id' => $product->id,
                                        'ean' => $po['ean'],
                                        'poid' => $q->id,
                                        'quantity' => $po['quantity'],
                                        'unit' => $po['unit'],
                                        'remqty' => $po['quantity'],
                                        'rate' => $po['rate'],
                                        'amount' => $po['amount'],
                                        'gstslab' => $po['gstslab'],
                                        'gstamount' => $po['gstamount']
                                ]);
                        }
                }
                $response['msg'] = 'PO created successfully';
                $response['err'] = 0;
        } else {
                $response['msg'] = 'Supplier not found';
                $response['err'] = 1;
        }

        return response()->json($response);
});

Route::middleware('auth:api')->get('/polist', function (Request $request) {
        $purchaseOrders = purchaseOrder::where('status', 0)->where('supplier_id', '!=', 17)->orderBy('created_at', 'desc')->get();
        foreach ($purchaseOrders as $key => $value) {
                $purchaseOrders[$key]['supplier_name'] = $value->supplier->c_name;
        }
        return response()->json($purchaseOrders);
});

Route::middleware('auth:api')->post('/poprods', function (Request $request) {
        //$purchaseOrders=purchaseOrder::where('status',0)->where('id',$request['poid'])->first();
        $product = poTable::where('poid', $request['poid'])->where('EAN', $request['EAN'])->where('remqty', '>', 0)->first();
        if (isset($product->id)) {
                $product['code'] = $product->product->code;
                $product->image_url = asset('uploads/allproducts/') . "/" . $product->product->code . "/" . $product->product->code . "-1.jpg";
        }
        return response()->json($product);
});

Route::middleware('auth:api')->get('/invoiceList', function (Request $request) {
        $invoices = invoice::where('status', 0)->orderBy('id', 'desc')->get();
        return response()->json($invoices);
});
Route::middleware('auth:api')->get('/invoiceListWithProds', function (Request $request) {
        $invoices = invoice::where('status', 0)->where('date', '>', '2020-10-31')->orderBy('id', 'desc')->get();
        foreach ($invoices as $key => $invoice) {
                $invoices[$key]['buyerorderno'] = htmlentities($invoices[$key]['buyerorderno'], ENT_QUOTES, "UTF-8");
                $invoices[$key]['products']     =       invoiceTable::selectRaw('*, (SELECT code FROM product_table WHERE id=invoiceTable.product_id) as sku')->where('invoice_id', $invoice->id)->orderBy('box', 'ASC')->get();
        }
        return response()->json($invoices);
});


Route::middleware('auth:api')->get('/onWaterInvoiceListWithProds', function (Request $request) {
        $invoices = invoice::where('date', '>', '2021-03-01')->where('buyer_id', '<>', '4')->where('buyerorderno', 'like', '%UK-18%')->orderBy('id', 'desc')->get();
        foreach ($invoices as $key => $invoice) {
                $invoices[$key]['buyerorderno'] = htmlentities($invoices[$key]['buyerorderno'], ENT_QUOTES, "UTF-8");
                $invoices[$key]['products']     =       invoiceTable::selectRaw('*, (SELECT code FROM product_table WHERE id=invoiceTable.product_id) as sku')->where('invoice_id', $invoice->id)->orderBy('box', 'ASC')->get();
        }
        return response()->json($invoices);
});

Route::middleware('auth:api')->post('/invDetailsSku', function (Request $request) {
        $invoices = invoice::where('date', '>', '2021-12-06')->where('buyer_id', '<>', '4')->where('buyerorderno', 'like', '%UK-18%')->orderBy('id', 'desc')->get();
        $product = product::where('code', $request['sku'])->first();
        $invoicess = [];
        foreach ($invoices as $key => $invoice) {
                $it = invoiceTable::selectRaw('*, (SELECT code FROM product_table WHERE id=invoiceTable.product_id) as sku')->where('product_id', $product->id)->where('invoice_id', $invoice->id)->orderBy('box', 'ASC')->get();

                if ($it->count()) {
                        $invoicess[$key]['buyerorderno'] = htmlentities($invoices[$key]['buyerorderno'], ENT_QUOTES, "UTF-8");
                        $invoicess[$key]['invoiceno'] = htmlentities($invoices[$key]['invoiceno'], ENT_QUOTES, "UTF-8");

                        $invoicess[$key]['containerno'] = str_replace(' ', '', htmlentities($invoices[$key]['containerno'], ENT_QUOTES, "UTF-8"));
                        if ($invoice->id == 84) {
                                //echo $invoicess[$key]['containerno'];die;
                        }
                        $invoicess[$key]['products']    =       $it;

                        $shipping = shipping::where('container', $invoicess[$key]['containerno'])->first();
                        if (isset($shipping->shipping_line_id)) {
                                $shippingLines = shippingLines::where('id', $shipping->shipping_line_id)->first();
                                $invoicess[$key]['shipping_line'] = $shippingLines;
                        } else {
                                $invoicess[$key]['shipping_line'] = [];
                                unset($invoicess[$key]);
                        }
                }
        }

        $invoicess = array_values($invoicess);
        return response()->json($invoicess);
});

Route::middleware('auth:api')->post('/invDetailsSkuUS', function (Request $request) {
        //$invoices = invoice::where('date', '>', '2023-12-06')->where('buyer_id', '<>', '4')->where('buyerorderno', 'like', '%GR-18%')->orderBy('id', 'desc')->get();
        $invoices = invoice::where('buyerorderno', 'like', '%US-18%')->orderBy('id', 'desc')->get();

        $product = product::where('code', $request['sku'])->first();

        $invoicess = [];
        foreach ($invoices as $key => $invoice) {
                $it = invoiceTable::selectRaw('*, (SELECT code FROM product_table WHERE id=invoiceTable.product_id) as sku')->where('product_id', $product->id)->where('invoice_id', $invoice->id)->orderBy('box', 'ASC')->get();


                if ($it->count()) {

                        $invoicess[$key]['buyerorderno'] = htmlentities($invoices[$key]['buyerorderno'], ENT_QUOTES, "UTF-8");
                        $invoicess[$key]['invoiceno'] = htmlentities($invoices[$key]['invoiceno'], ENT_QUOTES, "UTF-8");

                        $invoicess[$key]['containerno'] = str_replace(' ', '', htmlentities($invoices[$key]['containerno'], ENT_QUOTES, "UTF-8"));
                        if ($invoice->id == 84) {
                                //echo $invoicess[$key]['containerno'];die;
                        }
                        $invoicess[$key]['products']    =       $it;

                        $shipping = shipping::where('container', $invoicess[$key]['containerno'])->first();
                        if (isset($shipping->shipping_line_id)) {
                                $shippingLines = shippingLines::where('id', $shipping->shipping_line_id)->first();
                                $invoicess[$key]['shipping_line'] = $shippingLines;
                        } else {
                                $invoicess[$key]['shipping_line'] = [];
                                unset($invoicess[$key]);
                        }
                }
        }

        $invoicess = array_values($invoicess);
        return response()->json($invoicess);
});

Route::middleware('auth:api')->post('/invDetailsSkuCA', function (Request $request) {
        //$invoices = invoice::where('date', '>', '2023-12-06')->where('buyer_id', '<>', '4')->where('buyerorderno', 'like', '%GR-18%')->orderBy('id', 'desc')->get();
        $invoices = invoice::where('buyerorderno', 'like', '%CA-18%')->orderBy('id', 'desc')->get();

        $product = product::where('code', $request['sku'])->first();

        $invoicess = [];
        foreach ($invoices as $key => $invoice) {
                $it = invoiceTable::selectRaw('*, (SELECT code FROM product_table WHERE id=invoiceTable.product_id) as sku')->where('product_id', $product->id)->where('invoice_id', $invoice->id)->orderBy('box', 'ASC')->get();


                if ($it->count()) {

                        $invoicess[$key]['buyerorderno'] = htmlentities($invoices[$key]['buyerorderno'], ENT_QUOTES, "UTF-8");
                        $invoicess[$key]['invoiceno'] = htmlentities($invoices[$key]['invoiceno'], ENT_QUOTES, "UTF-8");

                        $invoicess[$key]['containerno'] = str_replace(' ', '', htmlentities($invoices[$key]['containerno'], ENT_QUOTES, "UTF-8"));
                        if ($invoice->id == 84) {
                                //echo $invoicess[$key]['containerno'];die;
                        }
                        $invoicess[$key]['products']    =       $it;

                        $shipping = shipping::where('container', $invoicess[$key]['containerno'])->first();
                        if (isset($shipping->shipping_line_id)) {
                                $shippingLines = shippingLines::where('id', $shipping->shipping_line_id)->first();
                                $invoicess[$key]['shipping_line'] = $shippingLines;
                        } else {
                                $invoicess[$key]['shipping_line'] = [];
                                unset($invoicess[$key]);
                        }
                }
        }

        $invoicess = array_values($invoicess);
        return response()->json($invoicess);
});

Route::middleware('auth:api')->post('/invDetailsSkuEU', function (Request $request) {
        //$invoices = invoice::where('date', '>', '2023-12-06')->where('buyer_id', '<>', '4')->where('buyerorderno', 'like', '%GR-18%')->orderBy('id', 'desc')->get();
        $invoices = invoice::where('buyerorderno', 'like', '%EU-18%')->orderBy('id', 'desc')->get();

        $product = product::where('code', $request['sku'])->first();

        $invoicess = [];
        foreach ($invoices as $key => $invoice) {
                $it = invoiceTable::selectRaw('*, (SELECT code FROM product_table WHERE id=invoiceTable.product_id) as sku')->where('product_id', $product->id)->where('invoice_id', $invoice->id)->orderBy('box', 'ASC')->get();


                if ($it->count()) {

                        $invoicess[$key]['buyerorderno'] = htmlentities($invoices[$key]['buyerorderno'], ENT_QUOTES, "UTF-8");
                        $invoicess[$key]['invoiceno'] = htmlentities($invoices[$key]['invoiceno'], ENT_QUOTES, "UTF-8");

                        $invoicess[$key]['containerno'] = str_replace(' ', '', htmlentities($invoices[$key]['containerno'], ENT_QUOTES, "UTF-8"));
                        if ($invoice->id == 84) {
                                //echo $invoicess[$key]['containerno'];die;
                        }
                        $invoicess[$key]['products']    =       $it;

                        $shipping = shipping::where('container', $invoicess[$key]['containerno'])->first();
                        if (isset($shipping->shipping_line_id)) {
                                $shippingLines = shippingLines::where('id', $shipping->shipping_line_id)->first();
                                $invoicess[$key]['shipping_line'] = $shippingLines;
                        } else {
                                $invoicess[$key]['shipping_line'] = [];
                                unset($invoicess[$key]);
                        }
                }
        }

        $invoicess = array_values($invoicess);
        return response()->json($invoicess);
});

Route::middleware('auth:api')->get('/onWaterInvoiceListWithProdsByContainerNumber', function (Request $request) {
        $invoices = invoice::where('containerno', 'like', $request['container_number'])->orderBy('id', 'desc')->get();
        foreach ($invoices as $key => $invoice) {
                $invoices[$key]['buyerorderno'] = htmlentities($invoices[$key]['buyerorderno'], ENT_QUOTES, "UTF-8");
                $invoices[$key]['products']     =       invoiceTable::selectRaw('*, (SELECT code FROM product_table WHERE id=invoiceTable.product_id) as sku')->where('invoice_id', $invoice->id)->orderBy('box', 'ASC')->get();
        }
        return response()->json($invoices);
});

Route::middleware('auth:api')->post('/invoiceProduct', function (Request $request) {
        $prod = product::where('EAN', $request['EAN'])->first();
        if (isset($prod->id)) {
                $products       =       invoiceTable::selectRaw('*, (SELECT code FROM product_table WHERE id=invoiceTable.product_id) as sku,(SELECT EAN FROM product_table WHERE id=invoiceTable.product_id) as EAN')->where('invoice_id', $request['invoice_id'])->where('product_id', $prod->id)->where('remqty', '>', 0)->orderBy('box', 'ASC')->first();
                $directory = public_path('../../uploads/product');
                //echo $directory.'/allproducts'."/".$prod->code."/".$prod->code."-1.jpg";die;
                //if(file_exists($directory.'/allproducts'."/".$prod->code."/".$prod->code."-1.jpg")) {
                $products->image_url = asset('uploads/allproducts/') . "/" . $prod->code . "/" . $prod->code . "-1.jpg";
                //}else{
                //      $products->image_url = "";
                //}
        } else {
                $products = array();
        }
        return response()->json($products);
});

Route::middleware('auth:api')->post('/purchaseBillCreate', function (Request $request) {
        $purchaseOrder = purchaseOrder::where('id', $request['purchaseOrder_id'])->first();
        $supplier = supplier::find($purchaseOrder->supplier_id);
        $statusremqty = 0;

        $q = purchaseBill::create([
                'purchaseOrder_id' => $request['purchaseOrder_id'],
                'ewaybill' => strtoupper($request['ewaybill']),
                'supp_inv_no' => strtoupper($request['supp_inv_no']),
                'supp_inv_date' => $request['supp_inv_date'],
                'quantity' => $request['pbQty'],
                'subtotal' => $request['pbSubTotal'],
                'gst' => $request['pbGST'],
                'freight' => $request['freight'],
                'total' => $request['pbTotal']
        ]);

        foreach ($request['pb'] as $pb) {
                $p = pbTable::create([
                        'product_id' => $pb['product'],
                        'ean' => $pb['EAN'],
                        'purchaseOrder_id' => $q->purchaseOrder_id,
                        'purchaseBill_id' => $q->id,
                        'orderqty' => $pb['remqty'],
                        'receiveqty' => $pb['receiveqty'],
                        'remainingqty' => $pb['remqty'] - $pb['receiveqty'],
                        'rate' => $pb['rate'],
                        'amount' => $pb['amount'],
                        'location' => $pb['location']
                ]);

                $poProduct = poTable::where('poid', $request['purchaseOrder_id'])->where('product_id', $pb['product'])->first();
                $poProduct->remqty = $p->remainingqty;
                $poProduct->save();
                if (trim($pb['location']) != "") {
                        $plocation = productLocations::where('product_id', $pb['product'])->where('location', $pb['location'])->first();
                        if ($plocation) {
                                $plocation->quantity = $plocation->quantity + $pb['receiveqty'];
                                $plocation->save();
                        } else {
                                $pl = productLocations::create([
                                        'product_id' => $pb['product'],
                                        'location' => $pb['location'],
                                        'quantity' => $pb['receiveqty'],
                                ]);
                        }
                }
                $product = product::where('id', $pb['product'])->first();
                $open_balance = $product->quantity;
                $product->quantity = $product->quantity + $pb['receiveqty'];
                $product->location = $pb['location'];
                $product->save();

                $s = stockLog::create([
                        'product_id' => $pb['product'],
                        'voucher_no' => strtoupper($request['supp_inv_no']),
                        'ref_no' => $purchaseOrder->ref_supplier,
                        'quantity' => $pb['receiveqty'],
                        'opening_balance' => $open_balance,
                        'remaining_stock' => $product->quantity,
                        'type' => 1,
                        'entity_id' => $q->id,
                        'supplier_name' => $supplier->c_name,
                ]);
        }
        $poTableCheck = poTable::where('poid', $request['purchaseOrder_id'])->get();

        foreach ($poTableCheck as $poTableChecks) {
                $statusremqty = $statusremqty + $poTableChecks->remqty;
        }

        if ($statusremqty == 0) {
                $purchaseOrder->status = 1;
                $purchaseOrder->remqty = $statusremqty;
                $purchaseOrder->save();
        } else {
                $purchaseOrder->status = 0;
                $purchaseOrder->remqty = $statusremqty;
                $purchaseOrder->save();
        }

        $response['msg'] = 'Purchase Bill created successfully';
        $response['err'] = 0;
        return response()->json($response);
});



Route::post('/stockOut', function (Request $request) {
        $data = $request->validate([
        'invoice_id'   => ['required','integer'],
        'buyerorderno' => ['required','string'],
        'ps'           => ['required','array','min:1'],
        'ps.*.product'    => ['required','integer'],
        'ps.*.EAN'        => ['nullable','string'],
        'ps.*.remqty'     => ['required','numeric','min:0'],
        'ps.*.receiveqty' => ['required','numeric','min:0'],
        'ps.*.location'   => ['nullable','string'],
    ]);
    $pendingConsumables = [];

    return DB::transaction(function () use ($data, &$pendingConsumables) {
        // 2) Lock invoice for update, verify status
        $invoice = invoice::whereKey($data['invoice_id'])->lockForUpdate()->first();
        if (!$invoice) {
            return response()->json(['msg' => 'Invoice not found', 'err' => 1], 404);
        }
        if (trim((string) $invoice->buyerorderno) !== trim((string) $data['buyerorderno'])) {
            return response()->json(['msg' => 'Buyer order does not match selected invoice.', 'err' => 1], 422);
        }
        if (intval($invoice->status) === 1) {
            return response()->json(['msg' => 'Invoice was already stocked out!', 'err' => 1], 422);
        }

        // 3) Packing list by buyer order no
        $packing = packingList::where('buyer_order_no', $data['buyerorderno'])->lockForUpdate()->first();
        if (!$packing) {
            return response()->json(['msg' => 'Packing list not found for given buyer order no.', 'err' => 1], 422);
        }

        // 4) Create stock-out header
        $stockOut = stockout::create([
            'invoice_id'   => $invoice->id,
            'buyer_ref_no' => $data['buyerorderno'],
        ]);

        /** @var invoiceController $invCtl */
        $invCtl = app(invoiceController::class);

        // 5) Iterate lines
        foreach ($data['ps'] as $ps) {
            $requestedQty = (float) ($ps['receiveqty'] ?? 0);
            if ($requestedQty <= 0) {
                continue;
            }

            // c) Update invoice line remqty with server-side cap checks
            $invLine = invoiceTable::where('invoice_id', $invoice->id)
                ->where('product_id', $ps['product'])
                ->lockForUpdate()
                ->first();

            if (!$invLine) {
                throw new \RuntimeException("Invoice line not found for product {$ps['product']}.");
            }
            $lineRemaining = (float) ($invLine->remqty ?? 0);
            if ($requestedQty > $lineRemaining) {
                throw new \RuntimeException("Requested stock-out qty {$requestedQty} exceeds invoice remaining qty {$lineRemaining} for product {$ps['product']}.");
            }

            // a) Find packing list product → batch nos
            $plp = packingListProduct::where('product_id', $ps['product'])
                ->where('packinglist_id', $packing->id)
                ->lockForUpdate()
                ->first();

            if (!$plp) {
                throw new \RuntimeException("PackingListProduct not found for product {$ps['product']}.");
            }
            $packingQty = (float) ($plp->quantity ?? 0);
            if ($requestedQty > $packingQty) {
                throw new \RuntimeException("Requested stock-out qty {$requestedQty} exceeds packing quantity {$packingQty} for product {$ps['product']}.");
            }

            // b) Create detail row
            $remainingAfterLine = max(0, $lineRemaining - $requestedQty);
            $detail = stockoutTable::create([
                'product_id'    => $ps['product'],
                'stock_id'      => $stockOut->id,
                'ean'           => $ps['EAN'] ?? null,
                'orderqty'      => $lineRemaining,
                'receiveqty'    => $requestedQty,
                'remainingqty'  => $remainingAfterLine,
                'location'      => $ps['location'] ?? null,
            ]);
            $invLine->remqty = $remainingAfterLine;
            $invLine->save();

            // d) Reduce product quantity by allocating across batches (atomic + locked)
            $product = product::whereKey($ps['product'])->lockForUpdate()->first();
            if (!$product) {
                throw new \RuntimeException("Product {$ps['product']} not found.");
            }

            // e) Optional: location decrement
            if (!empty($ps['location'])) {
                $plocation = productLocations::where('product_id', $ps['product'])
                    ->where('location', $ps['location'])
                    ->lockForUpdate()
                    ->first();
                if ($plocation) {
                    $newQty = max(0, $plocation->quantity - $ps['receiveqty']);
                    $plocation->quantity = $newQty;
                    $plocation->save(); // (was commented out)
                }
            }

            // f) Prepare pbTable “prod_remaining” refresh against current on-hand BEFORE this stock-out
            // Compute the *theoretical* on-hand after this line to update prod_remaining snapshots.
            $onHandAfterThisLine = max(0, $product->quantity - $requestedQty);
            $purchaseBatches = pbTable::where('product_id', $product->id)
                ->orderBy('created_at', 'DESC')
                ->lockForUpdate()
                ->get();

            $accum = 0;
            $remainingToCover = $onHandAfterThisLine;
            foreach ($purchaseBatches as $row) {
                $accum += $row->receiveqty;
                if ($accum >= $onHandAfterThisLine) {
                    $row->prod_remaining = $remainingToCover; // what’s left to reflect exact on-hand
                    $row->save();
                    // Set older rows to their full receiveqty (already covered by “remainingToCover” reaching 0)
                    // No break yet: in DESC order, once we’ve covered, older rows should be full or 0 depending on logic
                    break;
                } else {
                    $remainingToCover -= $row->receiveqty;
                    $row->prod_remaining = $row->receiveqty;
                    $row->save();
                }
            }

            // g) Supplier invoice allocation (FIFO on prod_remaining)
            $fifoRows = pbTable::where('product_id', $product->id)
                ->whereNotNull('prod_remaining')
                ->where('prod_remaining', '>', 0)
                ->orderBy('created_at', 'ASC')
                ->lockForUpdate()
                ->get();

            $toAllocate = $requestedQty;
            $suppParts = [];
            foreach ($fifoRows as $fifo) {
                if ($toAllocate <= 0) break;

                $use = min($fifo->prod_remaining, $toAllocate);
                $fifo->prod_remaining -= $use;
                $fifo->save();

                $toAllocate -= $use;
                $suppParts[] = $fifo->purchaseBillTable->supp_inv_no . " ({$use})";
            }
            $suppInvNoStr = implode(', ', $suppParts);

            // h) Batch allocations + unique ref pool (same as web stockstore / invoiceController)
            $isCartonInvoice = stripos((string) $invoice->invoiceno, 'CARTON') !== false;
            $shortBy = $invCtl->applyStockOutBatchRefAndStockLogs(
                $invoice,
                $product,
                $isCartonInvoice ? null : $plp,
                (int) $requestedQty,
                $suppInvNoStr,
                (int) $stockOut->id,
                $isCartonInvoice
            );

            if ($shortBy > 0) {
                $productLabel = trim((string) ($product->code ?? '')) !== ''
                    ? $product->code
                    : ('ID ' . ($ps['product'] ?? $product->id));
                throw new \RuntimeException("Insufficient batch stock for product {$productLabel} (short by {$shortBy}).");
            }

            $invCtl->applyStockOutConsumableDeductionsForLine(
                $invoice,
                (int) $invoice->id,
                $stockOut,
                $product,
                $ps,
                $pendingConsumables
            );
        }

        // 6) Recompute invoice status from remaining qty
        $statusRem = invoiceTable::where('invoice_id', $invoice->id)->sum('remqty');
        $invoice->status = ($statusRem == 0) ? 1 : 0;
        if ((int) $invoice->status === 1) {
            $invCtl->applyStockOutContainerConsumablesForCompletedInvoice(
                $invoice,
                (int) $invoice->id,
                $stockOut,
                $pendingConsumables
            );
        }
        $invoice->save();

        $payload = ['msg' => 'Stock-out recorded successfully', 'err' => 0];
        if ($pendingConsumables !== []) {
            $payload['pending_consumables'] = array_values($pendingConsumables);
        }

        // Authoritative rows for this stock-out (furniture product code + consumable id/name + carton shorts).
        $payload['pending_consumables_detail'] = ManualStockoutPendingConsumable::query()
            ->where('stock_id', (int) $stockOut->id)
            ->where('status', 0)
            ->with(['consumable', 'product'])
            ->orderByDesc('id')
            ->get()
            ->map(static function (ManualStockoutPendingConsumable $row) {
                return [
                    'pending_id' => (int) $row->id,
                    'product_id' => (int) $row->product_id,
                    'product_code' => (int) $row->product_id > 0 ? (string) (optional($row->product)->code ?? '') : null,
                    'consumable_id' => (int) $row->consumable_id,
                    'consumable_name' => (string) (optional($row->consumable)->name ?? ''),
                    'location' => $row->location,
                    'reason' => (string) $row->reason,
                ];
            })
            ->values()
            ->all();

        return response()->json($payload);
    });
});


Route::middleware('auth:api')->post('/supplierPolist', function (Request $request) {
        if (isset($request['user_id'])) {
                $user = user::find($request['user_id']);
                if (isset($user->supplier_id)) {
                        if ($request['filters']['from_date'] != '') {
                                $from_date = $request['filters']['from_date'];
                        } else {
                                $from_date = '1971-01-01';
                        }
                        if ($request['filters']['to_date'] != '') {
                                $to_date = $request['filters']['to_date'];
                        } else {
                                $to_date = date('Y-m-d');
                        }
                        if ($request['filters']['status'] != '') {
                                $status = array($request['filters']['status']);
                        } else {
                                $status = array('0', '1', '2', '3');
                        }
                        if ($request['filters']['pono'] != '') {
                                $fpono = $request['filters']['pono'];
                                $purchaseOrders = purchaseOrder::where('supplier_id', $user->supplier_id)->whereBetween('podate', [$from_date, $to_date])->whereIn('supplier_status', $status)->where('pono', 'like', '%' . $fpono . '%')->where('remqty', '>', '0')->orderBy('del_date', 'desc')->get();
                                $purchaseOrdersConsumables = purchaseOrderConsumable::where('supplier_id', $user->supplier_id)->whereBetween('podate', [$from_date, $to_date])->whereIn('supplier_status', $status)->where('pono', 'like', '%' . $fpono . '%')->orderBy('del_date', 'desc')->get();
                        } else {
                                $purchaseOrders = purchaseOrder::where('supplier_id', $user->supplier_id)->whereBetween('podate', [$from_date, $to_date])->whereIn('supplier_status', $status)->where('remqty', '>', '0')->orderBy('del_date', 'desc')->get();
                                $purchaseOrdersConsumables = purchaseOrderConsumable::where('supplier_id', $user->supplier_id)->whereBetween('podate', [$from_date, $to_date])->whereIn('supplier_status', $status)->orderBy('del_date', 'desc')->get();
                        }

                        //$purchaseOrders = (object) array_merge((array) $purchaseOrders, (array) $purchaseOrdersConsumables);
                        //dd($purchaseOrders);

                        foreach ($purchaseOrders as $key => $value) {
                                $products = poTable::where('poid', $value->id)->where('remqty', '>', '0')->get();
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
                                                $purchaseOrdersConsumables[$k]['products'] = pocTable::where('poid', $value->id)->get();
                                        } else {
                                                $purchaseOrdersConsumables[$k]['products'] = popTable::where('poid', $value->id)->get();
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
                                                $purchaseOrders[$key]['products'] = pocTable::where('poid', $value->id)->get();
                                        } else {
                                                $purchaseOrders[$key]['products'] = popTable::where('poid', $value->id)->get();
                                        }
                                }
                        }
                        $response['success'] = 'true';
                        $response['message'] = '';
                        $response['body'] = $purchaseOrders;
                        return response($response, 200);
                } else {
                        $response['message'] = 'Invalid User';
                        return response($response, 422);
                }
        } else {
                $response['message'] = 'User ID is required';
                return response($response, 422);
        }
});

Route::middleware('auth:api')->post('/supplierPoDetail', function (Request $request) {
        if (isset($request['po_id'])) {
                $id = $request['po_id'];
                $purchaseOrder = purchaseOrder::where('id', $id)->first();
                if (isset($purchaseOrder->id)) {
                        $poTable = poTable::where('poid', $id)->where('remqty', '>', '0')->get();
                        $directory = public_path('../../uploads/product');
                        foreach ($poTable as $p => $prod) {
                                $poTable[$p]['product_name'] = $prod->product->code . '-' . $prod->product->name;
                                $poTable[$p]['product_image'] = asset('uploads/allproducts/') . "/" . $prod->product->code . "/" . $prod->product->code . "-1.jpg";
                        }
                        $purchaseOrder['po_type'] = 1;
                } else {
                        $id = str_replace('10000000', '', $id);
                        $purchaseOrder = purchaseOrderConsumable::where('id', $id)->first();
                        $purchaseOrder['id'] = '10000000' . $id;
                        if ($purchaseOrder->type == 1) {
                                $poTable = pocTable::where('poid', $id)->where('remqty', '>', '0')->get();
                                $purchaseOrder['po_type'] = 2;
                        } else {
                                $poTable = popTable::where('poid', $id)->get();
                                $purchaseOrder['po_type'] = 3;
                        }
                        $directory = public_path('../../uploads/product');
                        foreach ($poTable as $p => $prod) {
                                if (isset($prod->product->code)) {
                                        $poTable[$p]['product_name'] = $prod->product->code;
                                        $poTable[$p]['product_id'] = $prod->product_id;
                                } else {
                                        $poTable[$p]['product_name'] = $prod->consumable->name;
                                        $poTable[$p]['product_id'] = $prod->consumable_id;
                                }
                                $poTable[$p]['product_image'] = "";
                        }
                }
                $eway_limit = '100000';
                $response['success'] = 'true';
                $response['message'] = '';
                $response['body'] = $purchaseOrder;
                $response['body']['tcs_percent'] = '0.075';
                $response['body']['products'] = $poTable;
                $response['body']['eway_limit'] = $eway_limit;
                return response($response, 200);
        } else {
                $response['message'] = 'PO ID is required';
                return response($response, 422);
        }
});

Route::middleware('auth:api')->post('/supplierStatusUpdate', function (Request $request) {
        $user = auth::user();
        if (isset($request['po_id'])) {
                $id = $request['po_id'];
                $purchaseOrder = purchaseOrder::where('id', $id)->first();
                if (isset($purchaseOrder->id)) {
                        $purchaseOrder->supplier_status = $request['supplier_status'];
                        $purchaseOrder->supplier_remarks = $request['supplier_remarks'];
                        $purchaseOrder->save();
                } else {
                        $id = str_replace('10000000', '', $id);
                        $purchaseOrder = purchaseOrderConsumable::where('id', $id)->first();
                        $purchaseOrder->supplier_status = $request['supplier_status'];
                        $purchaseOrder->supplier_remarks = $request['supplier_remarks'];
                        $purchaseOrder->save();
                }
                $admins = User::where('role', 'Admin')->get();
                foreach ($admins as $admin) {
                        Notification::create([
                                'user_id' => $admin->id,
                                'notification' => 'Purchase Order - ' . $purchaseOrder->pono . ' has been accepted by the supplier ' . $user->supplier->c_name,
                                'is_read' => 0
                        ]);
                }
                $response['success'] = 'true';
                $response['message'] = 'Status updated successfully!';
                return response($response, 200);
        } else {
                $response['message'] = 'PO ID is required';
                return response($response, 422);
        }
});

Route::middleware('auth:api')->post('/supplierRaiseInvoice', function (Request $request) {
        //dd($request);
        if (isset($request['purchase_order_id']) && !empty($request['purchase_order_id'])) {
                $po = purchaseOrder::where('id', $request['purchase_order_id'])->first();
                $po_type = "Furniture";
                $poid = $request['purchase_order_id'];
                if (!isset($po->id)) {
                        $poid = str_replace('10000000', '', $request['purchase_order_id']);
                        $po = purchaseOrderConsumable::where('id', $poid)->first();
                        $po_type = "Consumable";
                }
                $supplier_id = $po->supplier_id;
                $user = user::where('supplier_id', $supplier_id)->first();
                $supplier_invoice_number = '';
                $eway_bill_no = '';
                $vehicle_no = '';
                $total_quantity = 0;
                $subTotal = 0;
                $filename = '';
                $totalremqty = 0;
                $tcs = $request['tcs'];
                if (isset($request['supplier_invoice_number']) && !empty($request['supplier_invoice_number'])) {
                        $supplier_invoice_number = $request['supplier_invoice_number'];
                }
                if (isset($request['eway_bill_no']) && !empty($request['eway_bill_no'])) {
                        $eway_bill_no = $request['eway_bill_no'];
                }
                if (isset($request['vehicle_no']) && !empty($request['vehicle_no'])) {
                        $vehicle_no = $request['vehicle_no'];
                }
                if ($request->hasfile('eway_bill_upload')) {
                        $file = $request->file('eway_bill_upload');
                        $extension = $file->getClientOriginalExtension(); // getting image extension
                        $filename = $po->pono . '.' . $extension;
                        Storage::disk('local')->put('supplier-eway/' . $filename, file_get_contents($request->file('eway_bill_upload')));
                }
                $q = supplierInvoice::create([
                        'purchase_order_id' => $poid,
                        'supplier_invoice_number' => strtoupper($supplier_invoice_number),
                        'eway_bill_no' => $eway_bill_no,
                        'vehicle_no' => $vehicle_no,
                        'eway_bill_pdf' => $filename,
                        'user_id' => $user->id,
                        'tamount' => $request['tamount'],
                        'purchase_order_type' => $po_type
                ]);
                foreach ($request['products'] as $pb) {
                        if ($po_type == "Furniture") {
                                if ($pb['quantity'] == 0) {
                                        $pb['amount'] = 0;
                                }
                        } else {
                                if ($po->type == 1) {
                                        if ($pb['quantity'] == 0) {
                                                $pb['amount'] = 0;
                                        }
                                } else {
                                        if ($pb['receiveqty_box_1'] == 0) {
                                                $pb['amount'] = 0;
                                        }
                                }
                        }
                        $p = soTable::create([
                                'product_id' => $pb['product'],
                                'supplier_invoice_id' => $q->id,
                                'purchase_order_id' => $poid,
                                'gst' => $pb['gst'],
                                'amount' => $pb['amount'],
                                'total' => $pb['amount'] + (($pb['amount'] * $pb['gst']) / 100),
                        ]);
                        if ($po_type != "Furniture") {
                                if ($po->type == 2) {
                                        $p->quantity = $pb['receiveqty_box_1'];
                                        $p->quantity2 = $pb['receiveqty_box_2'];
                                } else {
                                        $p->quantity = $pb['quantity'];
                                }
                        } else {
                                $p->quantity = $pb['quantity'];
                        }
                        $p->save();
                        if ($po_type != "Furniture") {
                                if ($po->type == 2) {
                                        $total_quantity = $total_quantity + $pb['receiveqty_box_1'] + $pb['receiveqty_box_2'];
                                } else {
                                        $total_quantity = $total_quantity + $pb['quantity'];
                                }
                        } else {
                                $total_quantity = $total_quantity + $pb['quantity'];
                        }
                        $subTotal = $subTotal + $pb['amount'];
                        if ($po_type != "Consumable") {
                                $poProduct = poTable::where('poid', $poid)->where('product_id', $pb['product'])->first();
                                $poProduct->remqty = $poProduct->remqty - $pb['receiveqty'];
                                $poProduct->save();
                        } else {
                                if ($po->type == 1) {
                                        $poProduct = pocTable::where('poid', $poid)->where('consumable_id', $pb['product'])->first();
                                        $poProduct->remqty = $poProduct->remqty - $pb['quantity'];
                                        $poProduct->save();
                                } else {
                                        $poProduct = popTable::where('poid', $poid)->where('product_id', $pb['product'])->first();
                                        $poProduct->remqty_box1 = $poProduct->remqty_box1 - $pb['receiveqty_box_1'];
                                        $poProduct->remqty_box2 = $poProduct->remqty_box2 - $pb['receiveqty_box_2'];
                                        $poProduct->save();
                                }
                        }
                }

                $q->tquantity = $total_quantity;
                $q->subTotal = $subTotal;
                $q->tgst = $request['tamount'] - $subTotal;
                $q->internal_invoice_number = 'GV-' . date('Y') . '-' . $q->id;
                $q->save();

                if ($po_type == "Furniture") {
                        $poTableCheck = poTable::where('poid', $poid)->get();
                } else {
                        if ($po->type == 1) {
                                $poTableCheck = pocTable::where('poid', $poid)->get();
                        } else {
                                $poTableCheck = popTable::where('poid', $poid)->get();
                        }
                }

                foreach ($poTableCheck as $poTableChecks) {
                        $totalremqty = $totalremqty + $poTableChecks->remqty;
                }
                $po->remqty = $totalremqty;
                $po->save();
                $response['success'] = 'true';
                $response['message'] = 'Invoice created successfully! Please check your email ' . $user->email;
                $request['supplier_email'] = $user->email;
                $request['firstname'] = $user->firstname;
                $request['lastname'] = $user->lastname;
                $request['invoice_link'] = URL::to('/supplierInvoice/modalEmail/' . $q->id);
                Mail::send(new SupplierInvoiceMail($request));
                $admins = User::whereIn('role', ['Admin', 'Factory'])->get();
                foreach ($admins as $admin) {
                        Notification::create([
                                'user_id' => $admin->id,
                                'notification' => 'Invoice for Purchase Order - ' . $po->pono . ' has been created by the supplier ' . $user->supplier->c_name,
                                'is_read' => 0
                        ]);
                }
                return response($response, 200);
        } else {
                $response['message'] = 'PO ID is required';
                return response($response, 422);
        }
});

Route::middleware('auth:api')->post('/supplierInvoiceList', function (Request $request) {
        if (isset($request['user_id'])) {
                $invoices = supplierInvoice::where('user_id', $request['user_id'])->orderBy('created_at', 'desc')->get();
                $response['success']    =       'true';
                $response['body']               =       $invoices;
                return response($response, 200);
        } else {
                $response['message'] = 'User ID is required';
                return response($response, 422);
        }
});

Route::middleware('auth:api')->post('/returnList', function (Request $request) {
        if (isset($request['user_id'])) {
                $user = user::find($request['user_id']);
                $rejectRepair = rejectRepair::where('supplier_id', $user->supplier_id)->get();
                foreach ($rejectRepair as $key => $value) {
                        $rejectRepair[$key]['product_name'] = $value->product->code . ' - ' . $value->product->name;
                }
                $response['success'] = 'true';
                $response['message'] = '';
                $response['body']               =       $rejectRepair;
                return response($response, 200);
        } else {
                $response['message'] = 'User ID is required';
                return response($response, 422);
        }
});

Route::middleware('auth:api')->post('/suggestedProducts', function (Request $request) {
        if (isset($request['code'])) {
                suggestedProduct::create([
                        'code' => $request['code'],
                        'last_month_sale' => $request['last_month_sale'],
                        'current_price' => $request['current_price'],
                        'current_stock' => $request['current_stock']
                ]);
                $response['success'] = 'true';
                $response['message'] = 'Record received successfully.';
                return response($response, 200);
        } else {
                $response['message'] = 'Product code is required';
                return response($response, 422);
        }
});

Route::middleware('auth:api')->post('/supplier_notifications', function (Request $request) {
        $notifications = Notification::where('user_id', $request['user_id'])->get();
        $response['success'] = 'true';
        $response['body']       =       $notifications;
        return response($response, 200);
});

Route::middleware('auth:api')->post('/interestedCompanies', function (Request $request) {
        //dd($request);
        if (isset($request['company_name'])) {
                $product_type = '';
                if (isset($request['product_type']) && !empty($request['product_type'])) {
                        $product_type = implode(",", $request['product_type']);
                }
                $ic_data = interestedCompany::create([
                        'company_name' => $request['company_name'],
                        'establishment_date' => $request['establishment_date'],
                        'contact_name' => $request['contact_name'],
                        'email' => $request['email'],
                        'phone' => $request['phone'],
                        'website_link' => $request['website_link'],
                        'location' => $request['location'],
                        'work_with_companies_name' => $request['work_with_companies_name'],
                        'product_type' => $product_type
                ]);
                if ($request->hasfile('product_photos')) {
                        $files = $request->file('product_photos');
                        $images = array();
                        foreach ($files as $file) {
                                $filename = $file->getClientOriginalName();
                                Storage::disk('local')->put('interested-company-photos/' . $filename, file_get_contents($file));
                                $images[] = $filename;
                        }
                }
                foreach ($images as $imageName) {
                        interestedCompanyPhoto::create([
                                'interested_company_id' => $ic_data->id,
                                'photo' => $imageName
                        ]);
                }
                $response['success'] = 'true';
                $response['message'] = 'Record saved successfully.';
                Mail::send(new OnePageEmail($request));
                return redirect('https://artisanadmin.net?submitted=1');
        } else {
                $response['message'] = 'Company name is required';
                return response($response, 422);
        }
});

Route::post('/forgotPassword', function (Request $request) {
        $input = $request->all();
        $rules = array(
                'email' => "required|email",
        );
        $validator = Validator::make($input, $rules);
        if ($validator->fails()) {
                $arr = array("status" => 400, "message" => $validator->errors()->first(), "data" => array());
        } else {
                try {
                        $response = Password::sendResetLink($request->only('email'), function (Message $message) {
                                $message->subject($this->getEmailSubject());
                        });
                        switch ($response) {
                                case Password::RESET_LINK_SENT:
                                        return \Response::json(array("success" => "true", "status" => 200, "message" => trans($response), "data" => array()));
                                case Password::INVALID_USER:
                                        return \Response::json(array("status" => 400, "message" => trans($response), "data" => array()));
                        }
                } catch (\Swift_TransportException $ex) {
                        $arr = array("status" => 400, "message" => $ex->getMessage(), "data" => []);
                } catch (Exception $ex) {
                        $arr = array("status" => 400, "message" => $ex->getMessage(), "data" => []);
                }
        }
        return \Response::json($arr);
});
Route::post('/changePassword', function (Request $request) {
        $input = $request->all();
        $userid = Auth::guard('api')->user()->id;
        $rules = array(
                'old_password' => 'required',
                'new_password' => 'required|min:6',
                'confirm_password' => 'required|same:new_password',
        );
        $validator = Validator::make($input, $rules);
        if ($validator->fails()) {
                $arr = array("status" => 400, "message" => $validator->errors()->first(), "data" => array());
        } else {
                try {
                        if ((Hash::check(request('old_password'), Auth::guard('api')->user()->password)) == false) {
                                $arr = array("status" => 400, "message" => "Check your old password.", "data" => array());
                        } else if ((Hash::check(request('new_password'), Auth::guard('api')->user()->password)) == true) {
                                $arr = array("status" => 400, "message" => "Please enter a password which is not similar then current password.", "data" => array());
                        } else {
                                User::where('id', $userid)->update(['password' => Hash::make($input['new_password'])]);
                                $arr = array("success" => "true", "status" => 200, "message" => "Password updated successfully.", "data" => array());
                        }
                } catch (\Exception $ex) {
                        if (isset($ex->errorInfo[2])) {
                                $msg = $ex->errorInfo[2];
                        } else {
                                $msg = $ex->getMessage();
                        }
                        $arr = array("status" => 400, "message" => $msg, "data" => array());
                }
        }
        return \Response::json($arr);
});

Route::post('/2fa_hr', function (Request $request) {
        $google2fa_url = "";
        $secret_key = "";

        if ($request->google2fa_secret) {
                $google2fa = (new \PragmaRX\Google2FAQRCode\Google2FA());
                $google2fa_url = $google2fa->getQRCodeInline(
                        'GV-HR',
                        $request->email,
                        $request->google2fa_secret
                );
                $secret_key = $request->google2fa_secret;
        }

        $data = array(
                'secret' => $secret_key,
                'google2fa_url' => $google2fa_url
        );
        return \Response::json($data);
});

Route::post('/2fa_hr_sec_gen', function (Request $request) {
        // Initialise the 2FA class
        $google2fa = (new \PragmaRX\Google2FAQRCode\Google2FA());
        $data['secret_key'] = $google2fa->generateSecretKey();
        return \Response::json($data);
});

Route::post('/2fa_hr_verify', function (Request $request) {
        $google2fa = (new \PragmaRX\Google2FAQRCode\Google2FA());

        $secret = $request->secret;
        $valid = $google2fa->verifyKey($request->google2fa_secret, $secret);
        return \Response::json($valid);
});

Route::middleware('auth:api')->post('/questionnaire', function (Request $request) {
        //dd($request);
        $questionnaire = questionnaire::create([
                'full_name' => $request['full_name'],
                'designation' => $request['designation'],
                'work_location' => $request['work_location']
        ]);

        foreach ($request['ans'] as $q => $answers) {
                // if(isset($answers['ml'][1])){
                //      $ml = 'a';
                // }
                // if(isset($answers['ml'][2])){
                //      $ml = 'b';
                // }
                // if(isset($answers['ml'][3])){
                //      $ml = 'c';
                // }
                // if(isset($answers['ll'][1])){
                //      $ll = 'a';
                // }
                // if(isset($answers['ll'][2])){
                //      $ll = 'b';
                // }
                // if(isset($answers['ll'][3])){
                //      $ll = 'c';
                // }
                questionnaireResponse::create([
                        'questionnaire_id' => $questionnaire->id,
                        'question_no' => $q,
                        'most_likely' => $answers['ml'],
                        'least_likely' => $answers['ll'],
                ]);
        }
        $response['success'] = 'true';
        $response['message'] = 'Record saved successfully.';
        Mail::send(new OnePageEmail($request));
        return redirect('https://artisanadmin.net/questionnaire.php?submitted=1');
});

Route::middleware('auth:api')->get('/invoiceMail', function (Request $request) {
        $invoices = invoice::whereDate('date', '<', date('Y-m-d', strtotime('-2 weekdays')))->whereDate('created_at', '>', '2022-09-10')->where('send_mail', 0)->orderBy('id', 'desc')->limit(10)->get();
        $companyDetails = setting::first();
        /* echo "<pre>";
    print_r($companyDetails);die;*/
        //dd($invoices);

        foreach ($invoices as $invoice) {
                $invoiceTable = invoiceTable::where('invoice_id', $request->id)->orderBy('box', 'ASC')->get();
                $certificate = certificate::first();
                $print = 0;
                if (isset($_REQUEST['print']) && $_REQUEST['print'] == 1) {
                        $print = 1;
                }

                //$pdfHtml = view('invoice/printinv', ['invoice'=>$invoice, 'print' => $print, 'companyDetails' => $companyDetails, 'invoiceTable' => $invoiceTable, 'certificate'=>$certificate]);
                //echo $pdfHtml;die;
                $filename = str_replace('/', '-', $invoice->invoiceno) . '.pdf';
                // $pdf = App::make('dompdf.wrapper');
                // $pdf->loadHTML($pdfHtml);
                $directory = storage_path('app/public/invoices');
                //exec('xvfb-run wkhtmltopdf https://stock.artisanadmin.net/invoice/printinv/'.$invoice->id.' '.$directory.'/'.$filename);
                //die;

                //$pdf->save($directory . '/' . $filename);
                // \Illuminate\Support\Facades\Notification::route('mail', 'wholesale@artisanfurniture.net')->notify(new InvoiceMail($invoice));
                Mail::to('wholesale@artisanfurniture.net')->send(new InvoiceMail($invoice));
                $invoice->send_mail = 1;
                $invoice->save();
        }
        die;
});

Route::middleware('auth:api')->get('/rejectRepair', function (Request $request) {
        $reject_repair = RejectRepairPtable::where('status', 2)->where('created_at', '<', date('Y-m-d', strtotime('-360 hours')))->get();
        foreach ($reject_repair as $key => $rr) {
                $rr->pending_for_dn = 1;
                $rr->save();
        }
        die;
});

//Route::middleware('auth:api')->get('/stockReport', function (Request $request) {

        Route::get('/stockReport', function (Request $request) {

                $data['ChinaStock'] = ErpProduct::where('product_type', 'china')
                   ->where(function($query) {
                           $query->where('site_access', 'uk')
                                         ->orWhere('site_access', 'UK');
                   })
                   ->sum('quantity');

   $data['IndiaAccessoryStock'] = ErpProduct::where('product_type', 'indian_accessory')
                                        ->where(function($query) {
                                                $query->where('site_access', 'uk')
                                                          ->orWhere('site_access', 'UK');
                                        })
                                        ->sum('quantity');

   $data['FurnitureStock'] = ErpProduct::whereNull('product_type')
                           ->where(function($query) {
                                   $query->where('site_access', 'uk')
                                                 ->orWhere('site_access', 'UK');
                           })
                           ->sum('quantity');

                   $data['TotalStock'] = ErpProduct::where('site_access', 'uk')
                                                                   ->orWhere('site_access', 'UK')
                                                                   ->sum('quantity');
           $data['FactoryStock'] = product::sum('quantity');
           $data['IndiaSuppliers'] = supplier::where('country', 'India')->count();


                   $furniture = ErpProduct::whereNull('product_type')
                                                 ->where(function($query) {
                                                         $query->where('site_access', 'uk')
                                                                   ->orWhere('site_access', 'UK');
                                                 })
                                                 ->orderBy('sku', 'asc')
                                                 ->get();
           $sum_old = 0;
           $sum_new = 0;

           //echo date('Y-m-d',strtotime('-90 days'));die;
           $e = 0;
           foreach ($furniture as $key => $f) {
                   $sum = ErpHistory::where('sku', $f->sku)->whereDate('date', '>', date('Y-m-d', strtotime('-90 days')))->where('type', 'add')->where('reason', '!=', 'Returns')->where('reason', '!=', 'Cancellation')->where('reason', '!=', 'Stock Correction')->orderBy('id', 'desc')->sum('quantity');

                   if ($sum >= $f->quantity) {
                           $sum_new = $sum_new + $f->quantity;
                           $added = $f->quantity;
                   } else {
                           $sum_new = $sum_new + $sum;
                           $added = $sum;
                   }

                   // if($added > 0){
                   //   echo $added .'<br>';
                   //   $e++;
                   // }


                   // if(strtotime($history->date) < strtotime('-87 days')){
                   //   $sum_old = $sum_old + $history->stock;
                   // }
                   // if(strtotime($history->date) >= strtotime('-87 days')){
                   //   $sum_new = $sum_new + $history->stock;
                   // }
                   // if($history->stock != $f->quantity){
                   //   echo $f->sku .'<br>';
                   // }
           }
           //echo $e;
           //echo $data['FurnitureStock'];
           //dd($sum_new);
           $sum_old = $data['FurnitureStock'] - $sum_new;

           $data['UkOldStock'] = $sum_old;
           $data['UkNewStock'] = $sum_new;

           $setting = setting::get()->first();
           $data['USA_IN2047'] = $setting->in2047;
           $data['USA_IN2108'] = $setting->in2108;
           $data['USA_PRODUCT_1'] = $setting->us_product_1;
           $data['USA_PRODUCT_2'] = $setting->us_product_2;
           $data['USA_REMAINING_STOCK'] = $setting->usa_remaining_stock;
           $data['EU_REMAINING_STOCK'] = $setting->eu_remaining_stock;

           return \Response::json($data);

           //dd($data);
           //Mail::to(['info@globalvisiondirect.co.uk', 'info@artisanfurniture.net'])->send(new StockReportMail($data));
           die;
   });



Route::middleware('auth:api')->post('/erp_stock_history', function (Request $request) {
        $sku = '';
        if (isset($request->sku)) {
                $sku = $request->sku;
        }
        $date = $request->date;
        if ($sku != '') {
                $history = ErpHistory::select('sku', 'quantity', 'date', 'remark', 'stock')->where('sku', $sku)->where('date', $date)->where('type', 'less')->orderBy('id', 'desc')->get()->toArray();
                //$data = json_encode($history);
                $data[0]['status'] = 'sale';
                $data[0]['data'] = $history;

                $arrivals = ErpHistory::select('sku', 'quantity', 'date', 'remark', 'stock', 'sheet_id')->where('sku', $sku)->where('date', $date)->where('type', 'add')->orderBy('id', 'desc')->get()->toArray();
                foreach ($arrivals as $key => $arrival) {
                        //dd($arrival);
                        $sheet = ErpSheet::where('id', $arrival['sheet_id'])->first();
                        if (isset($sheet->id)) {
                                $shipping = shipping::where('reference', 'LIKE', '%' . str_replace('.xlsx', '', $sheet->name) . '%')->first();
                                //dd($shipping);
                                if (isset($shipping->id)) {
                                        $arrival_date = date('Y-m-d', strtotime($shipping->eta_at_port . ' + 3 days'));
                                        $arrivals[$key]['arrival_date'] = $arrival_date;
                                }
                        }
                }
                $data[1]['status'] = 'arrival';
                $data[1]['data'] = $arrivals;
        } else {

                $skuss = ErpHistory::select('id', 'sku', 'quantity', 'date', 'remark', 'stock', 'sheet_id')->where('date', $date)->orderBy('id', 'desc')->distinct()->pluck('sku');
                $skus = ErpProduct::select('sku')->whereIn('sku', $skuss)->pluck('sku');
                //dd($skus);
                $data = [];
                foreach ($skus as $key => $sku) {
                        $history = ErpHistory::select('sku', 'quantity', 'date', 'remark', 'stock')->where('sku', $sku)->where('date', $date)->where('type', 'less')->orderBy('id', 'desc')->get()->toArray();
                        if (count($history) > 0) {
                                //$data = json_encode($history);
                                $data[$sku][0]['status'] = 'sale';
                                $data[$sku][0]['data'] = $history;
                        }

                        $arrivals = ErpHistory::select('sku', 'quantity', 'date', 'remark', 'stock', 'sheet_id')->where('sku', $sku)->where('date', $date)->where('type', 'add')->orderBy('id', 'desc')->get()->toArray();
                        if (count($arrivals) > 0) {
                                foreach ($arrivals as $key => $arrival) {
                                        //dd($arrival);
                                        $sheet = ErpSheet::where('id', $arrival['sheet_id'])->first();
                                        if (isset($sheet->id)) {
                                                $shipping = shipping::where('reference', 'LIKE', '%' . str_replace('.xlsx', '', $sheet->name) . '%')->first();
                                                //dd($shipping);
                                                if (isset($shipping->id)) {
                                                        $arrival_date = date('Y-m-d', strtotime($shipping->eta_at_port . ' + 3 days'));
                                                        $arrivals[$key]['arrival_date'] = $arrival_date;
                                                }
                                        }
                                }
                                $data[$sku][1]['status'] = 'arrival';
                                $data[$sku][1]['data'] = $arrivals;
                        }
                }
        }



        return \Response::json($data);
});

////////////////////API FOR GETTING DATA FROM WEBSITE ON FULFILLMENT//////////////////////
Route::post('/product/handle-fulfillment-order', 'FulFillmentController@fulFillmentOrder');

Route::middleware('auth:api')->get('/remainingPoQtyForAllProducts', function (Request $request) {
    $purchaseOrders = PurchaseOrder::get();

    $filteredPurchaseOrders = [];

    foreach ($purchaseOrders as $purchaseOrder) {
        $poEntries = PoTable::where('poid', $purchaseOrder->id)
                            ->where('remqty', '>', 0)
                            ->whereHas('product')
                            ->get();

        foreach ($poEntries as $poEntry) {

            $filteredPurchaseOrders[] = [
                'purchase_order_id'   => $purchaseOrder->id,
                'sku'                 => $poEntry->product->code ?? null, // Ensures SKU is included
                'total_remqty'        => $poEntry->remqty,
                'supplier_reference'  => $purchaseOrder->ref_supplier
            ];
        }
    }

    return response()->json($filteredPurchaseOrders);
});



Route::middleware('auth:api')->get('/remainingPoQty', function (Request $request) {
    $sku = $request['sku'];

    $purchaseOrders = PurchaseOrder::get();

    $hasRequestedSku = PoTable::whereHas('product', function ($query) use ($sku) {
                            $query->where('code', $sku);
                        })->exists();

    if (!$hasRequestedSku) {
        return response()->json(['message' => 'Requested SKU not found in any purchase orders.']);
    }

    $filteredPurchaseOrders = [];

    foreach ($purchaseOrders as $key => $purchaseOrder) {
        $totalRemQty = PoTable::where('poid', $purchaseOrder->id)
                            ->where('remqty', '>', '0')
                            ->whereHas('product', function ($query) use ($sku) {
                                $query->where('code', $sku);
                            })
                            ->sum('remqty');

        if ($totalRemQty > 0) {
            $filteredPurchaseOrders[] = [
                'purchase_order_id' => $purchaseOrder->id,
                'total_remqty' => $totalRemQty,
                                'supplier_reference' => $purchaseOrder->ref_supplier
            ];
        }
    }

    return response()->json($filteredPurchaseOrders);
});

Route::middleware('auth:api')->get('/products', function (Request $request) {
        $products = product::get();
        return response()->json($products);
});

Route::get('/export-each-csv/{id}/{site}', [invoiceController::class, 'exportEachCSVApi']);
Route::get('/revert-each-csv/{id}/{site}', [invoiceController::class, 'revertCsvApi']);
Route::get('/store-inward-invoice-file-api/{id}/{shipmentNumber}', [invoiceController::class, 'storeInvoiceInwardFileApi']);
Route::get('/process-outbound-dropship-csv/{filename}/{site}', [invoiceController::class, 'processOutboundDropshipCsvApi']);
Route::get('/revert-outbound-dropship-csv/{filename}/{site}', [invoiceController::class, 'processRevertOutboundDropshipCsvApi']);
Route::get('/add-order-return/{location}/{sku}/{quantity}/{reason}/{remarks}', [invoiceController::class, 'updateErpSkuApi']);

Route::post('/fulfillment/fulfillment-order-manually-api', 'FulFillmentController@addManualFulfillmentAPI');

Route::post('/password/email', [ApiPasswordResetController::class, 'sendResetLinkEmail'])
    ->name('api.password.email');

        // BackOrder Automation
Route::get('/test/api', [BackOrderAutomationController::class, 'checkBackorderStockStatus']);
Route::get('/test/mail', [BackOrderAutomationController::class, 'sendPOMailtoFactory']);

//Tally API

Route::get('/credit-notes/export', [TallyApiController::class, 'exportCreditNotes'])->name('api.credit_notes.export');
Route::get('/debit-notes/export', [TallyApiController::class, 'exportDebitNotes'])->name('api.debit_notes.export');
Route::get('/purchase-bill/export', [TallyApiController::class, 'exportPurchaseBill'])->name('api.purchase_bill.export');
Route::get('/sales/export', [TallyApiController::class, 'exportSales'])->name('api.sales.export');



// For Populating Data On live server for April months
Route::prefix('sustainability/')->controller(SustainabilityPopulateDataController::class)->group(function () {
    Route::post('/stage3Record', 'stage3');
    Route::post('/stage4Record', 'stage4');
    Route::post('/stage5Record', 'stage5');
    Route::post('/stage6Record', 'stage6');
});

Route::get('/pricing/by-destination', function (Request $request) {

    $destination = $request->query('destination');

    if (empty($destination)) {
        return response()->json([
            'success' => false,
            'message' => 'destination query parameter is required',
        ], 422);
    }

    $data = DB::select(
        "SELECT
            pt.*,
            p.code
        FROM pricingTable AS pt
        INNER JOIN product_table AS p
            ON pt.product_id = p.id
        WHERE pt.destination = ?",
        [$destination]
    );

    return response()->json([
        'success' => true,
        'count'   => count($data),
        'data'    => $data,
    ]);
});

Route::get('/getskudata', function (Request $request) {
        $country = $request['country'];
        $sku = $request['sku'];
        $products = ErpProduct::where("site_access", $country)->where("sku", $sku)->get();
        return response()->json($products);
});

Route::post('/stock/recalculate', function (Request $request) {

    $sku = $request->query('sku');
    $siteAccess = $request->query('site_access');
    $dryRun = filter_var($request->query('dry_run', false), FILTER_VALIDATE_BOOLEAN);
    $anchorId = $request->query('anchor_id');

    if (empty($sku)) {
        return response()->json([
            'success' => false,
            'message' => 'sku query parameter is required',
        ], 422);
    }

    // shared delta logic so detection and replay always agree
    $deltaFor = function ($row) {
        $type = strtolower(trim($row->type));
        if ($type === 'add') return (int) $row->quantity;
        if ($type === 'less') return -1 * (int) $row->quantity;
        return 0; // unknown type: no change
    };

    $result = DB::transaction(function () use ($sku, $siteAccess, $dryRun, $anchorId, $deltaFor) {

        $query = DB::table('erp_history')
            ->where('sku', $sku)
            ->orderBy('created_at')
            ->orderBy('id')
            ->lockForUpdate();

        if (!empty($siteAccess)) {
            $query->whereRaw('LOWER(site_access) = ?', [strtolower($siteAccess)]);
        }

        $rows = $query->get();

        if ($rows->isEmpty()) {
            return ['groups' => [], 'corrected_count' => 0, 'total_rows' => 0];
        }

        $groups = $rows->groupBy(fn ($row) => strtolower($row->site_access));

        $report = [];
        $correctedCount = 0;

        foreach ($groups as $site => $groupRows) {
            $groupRows = $groupRows->sortBy([['created_at', 'asc'], ['id', 'asc']])->values();
            $lastIndex = $groupRows->count() - 1;

            $anchorIndex = null;

            if (!empty($anchorId)) {
                $anchorIndex = $groupRows->search(fn ($r) => (int) $r->id === (int) $anchorId);
            } else {
                // Walk backward through consecutive pairs. The first pair (from the
                // end) where recorded stock doesn't equal prevStock + thisRow's delta
                // is the most recent point where the ledger went wrong.
                $breakIndex = null;

                for ($i = $lastIndex; $i >= 1; $i--) {
                    $prevStock = (int) $groupRows[$i - 1]->stock;
                    $expected = $prevStock + $deltaFor($groupRows[$i]);

                    if ((int) $groupRows[$i]->stock !== $expected) {
                        $breakIndex = $i;
                        break;
                    }
                }

                if ($breakIndex === null) {
                    $report[$site] = ['message' => 'Ledger internally consistent, nothing to fix'];
                    continue;
                }

                $anchorIndex = $breakIndex - 1;
            }

            if ($anchorIndex === false || $anchorIndex === null) {
                $report[$site] = ['message' => 'Anchor row not found in this group, skipped'];
                continue;
            }

            $anchorRow = $groupRows[$anchorIndex];
            $running = (int) $anchorRow->stock;

            $groupReport = [
                'anchor_id' => $anchorRow->id,
                'anchor_stock_trusted' => $running,
                'rows' => [],
            ];

            foreach ($groupRows->slice($anchorIndex + 1) as $row) {
                $running += $deltaFor($row);

                $isWrong = (int) $row->stock !== $running;

                if ($isWrong) {
                    $correctedCount++;

                    if (!$dryRun) {
                        DB::table('erp_history')
                            ->where('id', $row->id)
                            ->update(['stock' => $running, 'updated_at' => now()]);
                    }
                }

                $groupReport['rows'][] = [
                    'id' => $row->id,
                    'type' => $row->type,
                    'quantity' => $row->quantity,
                    'old_stock' => (int) $row->stock,
                    'correct_stock' => $running,
                    'corrected' => $isWrong,
                ];
            }

            $report[$site] = $groupReport;
        }

        return [
            'groups' => $report,
            'corrected_count' => $correctedCount,
            'total_rows' => $rows->count(),
        ];
    });

    return response()->json([
        'success' => true,
        'dry_run' => $dryRun,
        'sku' => $sku,
        'total_rows' => $result['total_rows'],
        'corrected_count' => $result['corrected_count'],
        'data' => $result['groups'],
    ]);
});

Route::post('/stock/sync-products', function (Request $request) {

    $sku = $request->query('sku');
    $siteAccess = $request->query('site_access');
    $dryRun = filter_var($request->query('dry_run', false), FILTER_VALIDATE_BOOLEAN);

    if (empty($sku)) {
        return response()->json([
            'success' => false,
            'message' => 'sku query parameter is required',
        ], 422);
    }

    $result = DB::transaction(function () use ($sku, $siteAccess, $dryRun) {

        // Get the latest erp_history row per site_access group for this sku
        $query = DB::table('erp_history')
            ->where('sku', $sku)
            ->orderBy('created_at')
            ->orderBy('id');

        if (!empty($siteAccess)) {
            $query->whereRaw('LOWER(site_access) = ?', [strtolower($siteAccess)]);
        }

        $rows = $query->get();

        if ($rows->isEmpty()) {
            return ['products' => []];
        }

        // latest row per normalized site_access = correct current stock
        $latestBySite = $rows
            ->groupBy(fn ($row) => strtolower($row->site_access))
            ->map(fn ($groupRows) => (int) $groupRows->last()->stock);

        $productReport = [];

        foreach ($latestBySite as $site => $correctQuantity) {
            $product = DB::table('erp_products')
                ->where('sku', $sku)
                ->whereRaw('LOWER(site_access) = ?', [$site])
                ->lockForUpdate()
                ->first();

            if (!$product) {
                $productReport[] = [
                    'site_access' => $site,
                    'message' => 'No matching erp_products row found — skipped',
                ];
                continue;
            }

            $needsUpdate = (int) $product->quantity !== $correctQuantity;

            if ($needsUpdate && !$dryRun) {
                DB::table('erp_products')
                    ->where('id', $product->id)
                    ->update([
                        'quantity' => $correctQuantity,
                        'updated_at' => now(),
                    ]);
            }

            $productReport[] = [
                'id' => $product->id,
                'site_access' => $site,
                'old_quantity' => (int) $product->quantity,
                'correct_quantity' => $correctQuantity,
                'corrected' => $needsUpdate,
            ];
        }

        return ['products' => $productReport];
    });

    return response()->json([
        'success' => true,
        'dry_run' => $dryRun,
        'sku' => $sku,
        'products' => $result['products'],
    ]);
});