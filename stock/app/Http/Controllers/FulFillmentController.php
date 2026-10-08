<?php

namespace App\Http\Controllers;

use App\CustomerModel;
use App\FulfillmentLogsModel;
use App\FulFillmentModel;
use App\ErpProduct;
use App\Exports\FulFillmentsListExport;
use App\Exports\FulfillmentlistLogs;
use App\Exports\ErpOverallStock;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;

class FulFillmentController extends Controller
{
    public function __construct()
    {
        // $this->middleware(['auth']);
    }

    public function fulFillmentList(Request $request)
    {
        $customerName = $request['customer_name'];
        $customerEmail = $request['customer_email'];
        $skuName = $request['sku'];
        $per_page = $request['per_page'];
        $page = $request['page'];
        $customerNameOrEmailFound = false;
        $idsArray = [];
        $count = $this->getTotalNumberOfRecords('checkSiteAccess');
        if (!empty($customerName) || !empty($customerEmail)) {
            $customerNameOrEmailFound = true;
            $idsArray = $this->getCustomerIds($customerName, $customerEmail);
            $count = $this->getTotalNumberOfRecords('checkSiteAccessAndCustomerId', $idsArray);
        }
        $perPage = 10;
        if (!empty($per_page)) {
            $perPage = $per_page;
        } else {
            $perPage = 10;
        }
        $country = \Request::session()->get('country');
        $data = FulFillmentModel::join('wp_customers_infos as w', 'w.id', '=', 'sku_fulfillment_qtys.wp_customers_info_id');
        $data =  $data->select('*');
        if (!empty($skuName)) {
            $count = $this->getTotalNumberOfRecords('checkSiteAccessAndSKU', $skuName);
            $data = $data->where('sku', 'like', '%' . $skuName . '%');
        }
        if (!empty($skuName) && COUNT($idsArray) > 0) {
            $count = $this->getTotalNumberOfRecords('checkSiteAccessSKUCustomerId', $skuName, $idsArray);
        }
        if ($customerNameOrEmailFound) {
            $data = $data->where(['sku_fulfillment_qtys.site_access' => $country])
                ->whereIn("wp_customers_info_id", $idsArray);
        } else {
            $data = $data->where(['sku_fulfillment_qtys.site_access' => $country]);
        }
        $data =  $data->orderBy('sku_fulfillment_qtys.id', 'desc')
            ->paginate($perPage);

        $countResult = 0;
        if (COUNT($data) > 0) {
            if (empty($page)) {
                $page = 1;
            }
            $countResult = 1;
            $countResult = $perPage * $page;
        }
        return view('fullfillments/fullfillment_full_list', ['data' => $data, "customer_name" => $customerName, "customer_email" => $customerEmail, "sku_name" => $skuName, 'count' => $count, 'countResult' => $countResult, "perPage" => $perPage, "per_page" => $per_page]);
    }

    public function fulFillmentLogs(Request $request)
    {
        $idsarray = [];
        $customerName = $request['customer_name'];
        $customerEmail = $request['customer_email'];
        $orderId = $request['order_id'];
        $per_page = $request['per_page'];
        $sku = $request['sku'];
        $page = $request['page'];
        if (!empty($customerName) || !empty($customerEmail)  || !empty($sku) || !empty($orderId)) {
            if (!empty($customerName) || !empty($customerEmail)) {
                $idsarray = $this->getCustomerIds($customerName, $customerEmail);
            }
            $fullLogs = $this->getFulFillmentLogs('siteAccessSKUOrderIdCustomerIdFilter', $sku, $idsarray, $orderId, $per_page);
            $count = $this->getTotalNumberOfRecords('checkSiteAccessSKUOrderIdCustomerId', $sku, $idsarray, $orderId);
        } else {
            $fullLogs = $this->getFulFillmentLogs("siteAccessFilter", null, null, null, $per_page);
            $count = $this->getTotalNumberOfRecords('checkSiteAccessLogs');
        }
        $countResult = 0;
        $perPage = !empty($per_page) ? $per_page : 10;

        if (count($fullLogs) > 0) {
            if (empty($page)) {
                $page = 1;
            }
            $countResult = 1;
            $countResult = $perPage * $page;
        }


        return view('fullfillments/sku_logs', ['fulllogs' => $fullLogs, "sku" => $sku, "customer_name" => $customerName, "customer_email" => $customerEmail, 'order_id' => $orderId, 'count' => $count, 'countResult' => $countResult, "perPage" => $perPage, "per_page" => $per_page]);
    }

    public function getFulFillmentLogs($case, $sku = '', $idsArray = [], $orderId = '', $per_page = '')
    {
        $perPage = !empty($per_page) ? $per_page : 10;
        $country = \Request::session()->get('country');
        // $data = FulfillmentLogsModel::join('wp_customers_infos as w', 'w.id', '=', 'sku_fulfillment_logs.wp_customers_info_id');
        $data = DB::table('sku_fulfillment_logs')
                ->join('wp_customers_infos', 'sku_fulfillment_logs.wp_customers_info_id', '=', 'wp_customers_infos.id');

        switch ($case) {
            case 'siteAccessFilter':
                $data = $data->where(['sku_fulfillment_logs.site_access' => $country]);
                break;

            case 'siteAccessSKUOrderIdCustomerIdFilter':
                $data = $data->where("sku_fulfillment_logs.site_access", $country);
                if (!empty($sku)) {
                    $data = $data->where("sku_fulfillment_logs.sku", $sku);
                }
                if (!empty($orderId)) {
                    $data = $data->where('order_id', 'like', '%' . $orderId . '%');
                }
                if (COUNT($idsArray) > 0) {
                    $data = $data->whereIn("wp_customers_info_id", $idsArray);
                }
                break;

            case 'siteAccessSKUOrderIdCustomerIdCSVFilter':
                $data = FulfillmentLogsModel::join('wp_customers_infos as w', 'w.id', '=', 'sku_fulfillment_logs.wp_customers_info_id');
                $data = $data->where("sku_fulfillment_logs.site_access", $country);
                if (!empty($sku)) {
                    $data = $data->where("sku_fulfillment_logs.sku", $sku);
                }
                if (!empty($orderId)) {
                    $data = $data->where('order_id', 'like', '%' . $orderId . '%');
                }
                if (COUNT($idsArray) > 0) {
                    $data = $data->whereIn("wp_customers_info_id", $idsArray);
                }
                $data =  $data->select('*')
                    ->orderBy('sku_fulfillment_logs.id', 'desc')
                    ->get();
                return $data;


            case 'siteAccessCSVFilter':
                $data = FulfillmentLogsModel::join('wp_customers_infos as w', 'w.id', '=', 'sku_fulfillment_logs.wp_customers_info_id');
                $data = $data->where("sku_fulfillment_logs.site_access", $country);
                $data =  $data->select('*')
                    ->orderBy('sku_fulfillment_logs.id', 'desc')
                    ->get();
                return $data;


            default:
                # code...
                break;
        }
        $data =  $data->select('sku_fulfillment_logs.*', 'wp_customers_infos.wp_customer_name', 'wp_customers_infos.wp_customer_email')
            ->orderBy('sku_fulfillment_logs.id', 'desc')
            ->paginate($perPage);
        return $data;
    }


    public function getCustomerIds($customerName, $customerEmail)
    {
        $country = \Request::session()->get('country');

        $customerData = CustomerModel::select("id")->where(['site_access' => $country]);
        if (!empty($customerName)) {
            $customerData = $customerData->where('wp_customer_name', 'like', '%' . $customerName . '%');
        }
        if (!empty($customerEmail)) {
            $customerData = $customerData->where('wp_customer_email', 'like', '%' . $customerEmail . '%');
        }
        $customerData = $customerData->get();
        $idsArray = [];
        foreach ($customerData as $key => $row) {
            $idsArray[$key] = $row->id;
        }
        if (COUNT($idsArray) == 0) {
            $idsArray['0'] = 0;
            return $idsArray;
        }
        return $idsArray;
    }

    public function getTotalNumberOfRecords($case, $sku = '', $arrSearch = [], $orderId = '')
    {
        $country = \Request::session()->get('country');
        switch ($case) {
            case 'checkSiteAccess':
                $data = FulFillmentModel::where("site_access", $country)->get();
                break;

            case 'checkSiteAccessLogs':
                $data = FulfillmentLogsModel::where("site_access", $country)->get();
                break;

            case 'checkSiteAccessSKUOrderIdCustomerId':
                $data = FulfillmentLogsModel::where(["site_access" => $country]);
                if (!empty($sku)) {
                    $data = $data->where(["sku" => $sku]);
                }
                if (!empty($orderId)) {
                    $data = $data->where(["order_id" => $orderId]);
                }
                if (!empty($arrSearch)) {
                    $data = $data->whereIn("wp_customers_info_id", $arrSearch);
                }
                $data = $data->get();
                break;

            case 'checkSiteAccessAndCustomerId':
                $data = FulFillmentModel::where(["site_access" => $country])->whereIn("wp_customers_info_id", $sku)->get();
                break;

            case 'checkSiteAccessAndSKU':
                $data = FulFillmentModel::where(["site_access" => $country, "sku" => $sku])->get();
                break;

            case 'checkSiteAccessSKUCustomerId':
                $data = FulFillmentModel::where(["site_access" => $country, "sku" => $sku])->get();
                break;

            default:
                # code...
                break;
        }
        return COUNT($data);
    }

    public function exportFulFillment(Request $request)
    {
        $customerName = $request['customer_name'];
        $customerEmail = $request['customer_email'];
        $sku_name = $request['sku_id'];
        $idsArray = [];
        if (!empty($customerName) || !empty($customerEmail)) {
            $idsArray = $this->getCustomerIds($customerName, $customerEmail);
        }
        $country = \Request::session()->get('country');
        $data = FulFillmentModel::join('wp_customers_infos as w', 'w.id', '=', 'sku_fulfillment_qtys.wp_customers_info_id');
        $data =  $data->select('*');
        if (!empty($sku_name)) {
            $data = $data->where('sku', 'like', '%' . $sku_name . '%');
        }
        if (COUNT($idsArray)) {
            $data = $data->whereIn("wp_customers_info_id", $idsArray);
        }
        $data = $data->where(['sku_fulfillment_qtys.site_access' => $country]);
        $data =  $data->orderBy('sku_fulfillment_qtys.id', 'desc')
            ->get();
        $nameFormat = 'fulfillmentlist_' . date("Y-m-d H-i-s") . '.xlsx';
        return (new FulFillmentsListExport($data))->download($nameFormat);
    }

    public function ErpOverallStock()
    {
        $nameFormat = 'erp_overall_' . date("Y-m-d H-i-s") . '.xlsx';
        return (new ErpOverallStock())->download($nameFormat);
    }

    public function exportFulFillmentLogs(Request $request)
    {
        $idsArray = [];
        $customerName = $request['customer_name'];
        $customerEmail = $request['customer_email'];
        $order_id = $request['order_id'];
        $sku = $request['sku_id'];
        if (!empty($customerName) || !empty($customerEmail)  || !empty($sku) || !empty($order_id)) {
            if (!empty($customerName) || !empty($customerEmail)) {
                $idsArray = $this->getCustomerIds($customerName, $customerEmail);
            }
            $fullLogs = $this->getFulFillmentLogs('siteAccessSKUOrderIdCustomerIdCSVFilter', $sku, $idsArray, $order_id);
        } else {
            $fullLogs = $this->getFulFillmentLogs('siteAccessCSVFilter');
        }
        $nameFormat = 'fulfillmentlistLogs_' . date("Y-m-d H-i-s") . '.xlsx';
        return (new FulfillmentlistLogs($fullLogs))->download($nameFormat);
    }

    public function fulFillmentOrder(Request $request)
    {
        header('Content-Type: application/json');
        $rules = [
            'action' => 'required',
            'location' => 'required|string|max:255',
            'trader_customer.display_name' => 'required|string|max:255',
            'trader_customer.email' => 'required|email',
            'order.order_id' => 'required|integer',
            'order.sku' => 'required|string|max:255',
            'order.quantity' => 'required|integer|min:1',
        ];

        $messages = [
            'action.required' => 'The action field is required.',

            'location.required' => 'The location field is required.',
            'location.max' => 'The location field must not exceed 255 characters.',

            'trader_customer.display_name.required' => 'The trader customer display name field is required.',
            'trader_customer.display_name.max' => 'The trader customer display name must not exceed 255 characters.',

            'trader_customer.email.required' => 'The trader customer email field is required.',
            'trader_customer.email.email' => 'The trader customer email must be a valid email address.',

            'order.order_id.required' => 'The order ID field is required.',
            'order.order_id.integer' => 'The order ID must be an integer.',

            'order.sku.required' => 'The order SKU field is required.',
            'order.sku.max' => 'The order SKU must not exceed 255 characters.',

            'order.quantity.required' => 'The order quantity field is required.',
            'order.quantity.integer' => 'The order quantity must be an integer.',
            'order.quantity.min' => 'The order quantity must be at least 1.',
        ];

        $validator = Validator::make($request->all(), $rules, $messages);
        if ($validator->fails()) {
            return response()->json(['status' => false, 'errors' => $validator->errors()], 422);
        } else {

            $action  = $request['action'];
            $orderId = $request['order']['order_id'];
            $quantity = $request['order']['quantity'];
            $customerName = $request['trader_customer']['display_name'];
            $email = $request['trader_customer']['email'];
            $sku = $request['order']['sku'];
            $location = $request['location'];
            $wpId = $request['trader_customer']['wp_id'];
            $response = $this->addNewFulfillment($action, $orderId, $quantity, $customerName, $email, $sku, $location, $wpId);
            return \Response::json($response);
        }
    }

    public function fulFillmentReturnCalculation($customerInfoId, $sku, $location, $quantity)
    {
        $conditionFulFillment = ['wp_customers_info_id' => $customerInfoId, 'sku' => $sku, 'site_access' => $location];
        $tableName = "sku_fulfillment_qtys";
        $skuFulfillmentQty = DB::table($tableName)->where($conditionFulFillment)->value('qty');
        $totalQTY = (int)$skuFulfillmentQty - (int)$quantity;
        DB::table($tableName)->where($conditionFulFillment)
            ->update([
                'qty' => $totalQTY
            ]);
        return true;
    }

    public function addManualFulfillment(Request $request)
    {
        $action  = $request['action'];
        $orderId = $request['order_id'];
        $quantity = $request['quantity'];
        $customerName = $request['customer_name'];
        $email = $request['customer_email'];
        $sku = $request['sku'];
        $location = \Request::session()->get('country');
        $response =  $this->addNewFulfillment($action, $orderId, $quantity, $customerName, $email, $sku, $location, 0);

        if ($response['status']) {
            return redirect('/product/erp')->with('success', $response['message']);
        } else {
            return redirect('/product/erp')->with('danger', $response['message']);
        }
    }

    public function addManualFulfillmentAPI(Request $request)
    {
        $action  = $request['action'];
        $orderId = $request['order_id'];
        $quantity = $request['quantity'];
        $customerName = $request['customer_name'];
        $email = $request['customer_email'];
        $sku = $request['sku'];
        $location = isset( $request['country'] ) ? $request['country'] : 'uk';
        $response =  $this->addNewFulfillment($action, $orderId, $quantity, $customerName, $email, $sku, $location, 0);

        return $response['message'];
    }

    public function addNewFulfillment($action, $orderId, $quantity, $customerName, $email, $sku, $location, $wpId)
    {
        $customerInfoId = $this->getCustomerInfoId("wp_customer_email", $wpId, "wp_customers_infos", $location, $customerName, $email);
        $fulfillmentType = $this->getFulfillmentType($customerInfoId, $location, $customerName, $email, $orderId, $sku, $quantity, $action);
        if ($fulfillmentType == "add-to-bucket") {
            $response = array(
                'status' => true,
                'message' => "add to bucket Successfully",
            );
        } else if ($fulfillmentType == "Fulfillment-Quantity-not-less-than-given-quantity") {
            $response = array(
                'status' => false,
                'message' => "This customer does not have enough items in his bucket.",
            );
        } else if ($fulfillmentType == "less-from-bucket") {
            $response = array(
                'status' => true,
                'message' => "less from bucket Successfully",
            );
        } else if ($fulfillmentType == "return-into-bucket") {
            $response = array(
                'status' => true,
                'message' => "return into bucket Successfully",
            );
        } elseif( $fulfillmentType == "refunded-to-india-qty") {
            $response = array(
                'status' => true,
                'message' => "Refunded and Added to India Quantity",
            );
        } else {
            $response = array(
                'status' => false,
                'message' => "Something Went Wrong",
            );
        }
        return $response;
    }

    public function getCustomerInfoId($attr, $attrConn, $table, $siteAccess, $customerName, $email)
    {
        $checkcheckFulfillmentSKUId = '';
        $customerData = DB::table($table)->select("*")->where([$attr => $email, 'site_access' => $siteAccess])->get();
        if (count($customerData)) {
            $lastUpdatedId = DB::table($table)
                ->where('wp_customer_email', $email)
                ->where('site_access', $siteAccess)
                ->value('id');
            $checkcheckFulfillmentSKUId = $lastUpdatedId;
        } else {
            $checkcheckFulfillmentSKUId = DB::table($table)->insertGetId([
                'wp_customer_id' => $attrConn,
                'site_access' => $siteAccess,
                'wp_customer_name' => $customerName,
                'wp_customer_email' => $email,
                'created_at' => date("Y-m-d H:i:s"),
                'updated_at' => date("Y-m-d H:i:s"),
            ]);
        }
        return $checkcheckFulfillmentSKUId;
    }

    public function getFulfillmentType($customerInfoId, $location, $displayName, $email, $orderId, $sku, $quantity, $action)
    {

        switch ($action) {
            case "add-to-bucket":
                $this->checkSKU("wp_customers_info_id", $customerInfoId, "sku_fulfillment_qtys", $location, $sku, $quantity);
                $this->saveFulfillmentLogs($customerInfoId, $location, $displayName, $email, $orderId, $sku, $quantity, $action, "Fulfillment", "Add");
                $this->lessFromIndiaQty($sku, $quantity, $orderId, "Add", $location, $displayName, $email);
                return "add-to-bucket";

            case "less-from-bucket":
                $erpProductData = $this->erpProductData($sku, $location);
                $fulfillmentQty = $erpProductData->fullfillment_qty;
                
                $checkCutomerExistInSkuFulfillmentQtys = DB::table('sku_fulfillment_qtys')->where(['wp_customers_info_id' => $customerInfoId, 'sku' => $sku])->first();

                if ($fulfillmentQty >= $quantity && !empty($checkCutomerExistInSkuFulfillmentQtys->qty) && $checkCutomerExistInSkuFulfillmentQtys->qty >= $quantity) {
                    $totalQTY = (int)$fulfillmentQty - (int)$quantity;
                    $this->fulFillmentReturnCalculation($customerInfoId, $sku, $location, $quantity);
                    $this->saveFulfillmentLogs($customerInfoId, $location, $displayName, $email, $orderId, $sku, $quantity, $action, "Bucket", "Less");
                    $this->updateErpProduct('fullfillment_qty', $totalQTY, $sku, $location);
                    return "less-from-bucket";
                } else {
                    return "Fulfillment-Quantity-not-less-than-given-quantity";
                }

            case "return-into-bucket":
                $this->checkSKU("wp_customers_info_id", $customerInfoId, "sku_fulfillment_qtys", $location, $sku, $quantity);
                $this->saveFulfillmentLogs($customerInfoId, $location, $displayName, $email, $orderId, $sku, $quantity, $action, "Return", "Add");
                // $this->lessFromIndiaQty($sku, $quantity, $orderId, "Return", $location, $displayName, $email);
                return "return-into-bucket";

            case "refunded-add-to-india-qty":
                $erpProductData = $this->erpProductData($sku, $location);
                $fulfillmentQty = $erpProductData->fullfillment_qty;
                $oldIndiaQty = $erpProductData->quantity;

                $checkCutomerExistInSkuFulfillmentQtys = DB::table('sku_fulfillment_qtys')->where(['wp_customers_info_id' => $customerInfoId, 'sku' => $sku])->first();
                if(!empty($checkCutomerExistInSkuFulfillmentQtys->qty)){
                    $totalQTY = (int)$fulfillmentQty - (int)$quantity;
                    $this->fulFillmentReturnCalculation($customerInfoId, $sku, $location, $quantity);
                    $this->saveFulfillmentLogs($customerInfoId, $location, $displayName, $email, $orderId, $sku, $quantity, $action, "Refund", "Less");
                   
                    $newIndiaQty = (int)$oldIndiaQty + (int)$quantity;
                    $this->updateErpProduct('fullfillment_qty', $totalQTY, $sku, $location);
                    $this->updateErpProduct('quantity', $newIndiaQty, $sku, $location);

                    $this->saveErpHistory($sku, $quantity, $orderId, 'Refund', $location, $displayName, $email);
                    return "refunded-to-india-qty";
                }

        }
        return false;
    }

    public function checkSKU($attr, $attrConn, $table, $siteAccess, $sku, $fulFillMentQnt)
    {
        $checkcheckFulfillmentSKUId = '';
        $checkFulfillmentSKU = DB::table($table)->select("*")->where([$attr => $attrConn, "sku" => $sku, 'site_access' => $siteAccess])->get();

        if (COUNT($checkFulfillmentSKU) == 0) {
             /** insert new record in sku_fulfillment_qtys */
            $checkcheckFulfillmentSKUId = DB::table($table)->insertGetId([
                $attr => $attrConn,
                'site_access' => $siteAccess,
                'sku' => $sku,
                'qty' => $fulFillMentQnt,
                'created_at' => date("Y-m-d H:i:s"),
                'updated_at' => date("Y-m-d H:i:s"),
            ]);
            if ($checkcheckFulfillmentSKUId > 0) {
                $checkcheckFulfillmentSKUId = $sku;
            }

            /** get old fullfillment_qty value from erp_product and update with new value*/
            $totalQTY = $fulFillMentQnt;
            $erpProductData = $this->erpProductData($sku, $siteAccess);
            $erpProductData = $erpProductData->fullfillment_qty;
            if (!empty($erpProductData)) {
                $totalQTY = (int)$erpProductData + (int)$fulFillMentQnt;
            }
            $this->updateErpProduct('fullfillment_qty', $totalQTY, $sku, $siteAccess);

        } else if (COUNT($checkFulfillmentSKU) > 0) {
            /** get old qty value from sku_fulfillment_qtys and update with new value*/
            $fulFillmentQty = DB::table($table)->where([$attr => $attrConn, 'sku' => $sku, 'site_access' => $siteAccess])->value('qty');
            $totalQTY = (int)$fulFillmentQty + (int)$fulFillMentQnt;
            $checkcheckFulfillmentSKUId = DB::table($table)->where([$attr => $attrConn, 'sku' => $sku, 'site_access' => $siteAccess])
                ->update([
                    'qty' => $totalQTY,
                    'updated_at' => date("Y-m-d H:i:s"),
                ]);
            
            /** get old fullfillment_qty value from erp_product and update with new value*/
            $erpProductData = $this->erpProductData($sku, $siteAccess);
            $erpProductData = $erpProductData->fullfillment_qty;

            $totalQTY = (int)$erpProductData + (int)$fulFillMentQnt;

            $this->updateErpProduct('fullfillment_qty', $totalQTY, $sku, $siteAccess);

            $checkcheckFulfillmentSKUId = DB::table($table)->where([$attr => $attrConn, 'sku' => $sku, 'site_access' => $siteAccess])->value('id');
        }

        return $checkcheckFulfillmentSKUId;
    }

    public function saveFulfillmentLogs($customerInfoId, $location, $displayName, $email, $order_id, $sku, $quantity, $action, $order_type = '', $type = '')
    {

        $lastSkuFulfillmentId = DB::table("sku_fulfillment_logs")->insertGetId([
            'wp_customers_info_id' => $customerInfoId,
            'sku' => $sku,
            'order_id' => $order_id,
            'qty' => $quantity,
            'order_type' => $order_type,
            'type' => $type,
            'site_access' => $location,
            'created_at' => date("Y-m-d H:i:s"),
            'updated_at' => date("Y-m-d H:i:s"),
        ]);

        if($lastSkuFulfillmentId) {
            $remark = '';
            // $buyerData =  $this->getBuyerInfoByOrderId($order_id);
            if($order_type == 'Fulfillment'){
                $remark = 'Reduce from the India stock and added in the bucket of buyer '. $displayName;
            } elseif($order_type == 'Bucket') {
                $remark =  'Stock reduce from bucket of customer '. $displayName;
            } elseif ($order_type == 'Return') {
                $remark = 'Stock returned to bucket of customer '.$displayName;
            } elseif($order_type == 'Refund') {
                $remark = "Refunded";
            }

            $remainingStock = DB::table('sku_fulfillment_qtys')->where(['wp_customers_info_id' => $customerInfoId, 'sku' => $sku, 'site_access' => $location])->first();
            
            DB::table("sku_fulfillment_logs")->where(['id' => $lastSkuFulfillmentId])->update(['remark' => $remark. ' via order Id -'.$order_id, 'remaining_stock' => $remainingStock->qty]);
        }
        return true;
    }

    public function lessFromIndiaQty($sku, $quantity, $orderId, $type, $location, $displayName, $email)
    {
        $erpProductData =  $this->erpProductData($sku, $location);
        $erpProductDataIndiaQty = $erpProductData->quantity;
        $indiaQty = (int)$erpProductDataIndiaQty - (int)$quantity;
        $this->updateErpProduct('quantity', $indiaQty, $sku, $location);
        $this->saveErpHistory($sku, $quantity, $orderId, $type, $location, $displayName, $email);
        return true;
    }

    public function updateErpProduct($columnName, $totalQTY, $sku, $location)
    {
        DB::table("erp_products")->where(['sku' => $sku, 'site_access' => $location])
            ->update([
                $columnName => $totalQTY,
            ]);
        return true;
    }

    public function saveErpHistory($sku, $quantity, $orderId, $type, $location, $displayName, $email)
    {
        $reason = '';
        // $buyerData =  $this->getBuyerInfoByOrderId($orderId);
        $erpProductData =  $this->erpProductData($sku, $location);

        if ($type == 'Return') {
            $typeAlter = 'Return in the bucket of buyer '. $displayName;
            $reason = 'Add';
        } else if ($type == 'Add') {
            $typeAlter = 'Reduce from the India stock and added in the bucket of buyer '. $displayName;
            $reason = "Less";
        } else if ($type == 'Less') {
            $typeAlter = 'Less from the bucket of buyer '. $displayName;
            $reason = 'Less';
        } else if($type == 'Refund') {
            $typeAlter = 'Refund and Added in India stock of buyer '. $displayName;
            $reason = 'Add';
        }


        DB::table("erp_history")->insertGetId([
            'sheet_id' => 0,
            'sku' => $sku,
            'quantity' => $quantity,
            'type' => $reason,
            'date' => date("Y-m-d"),
            'created_at' => date("Y-m-d H:i:s"),
            'updated_at' => date("Y-m-d H:i:s"),
            'remark' => $typeAlter . ' via Order id - ' . $orderId,
            // 'stock' => $erpProductData->warehouse_quantity ? $erpProductData->warehouse_quantity : 0,
            'stock' => $erpProductData->quantity,
            'reason' => $reason,
            'site_access' => $location,
        ]);
        return true;
    }

    public function getBuyerInfoByOrderId($orderId){
        $logs = DB::table("sku_fulfillment_logs")
        ->join('wp_customers_infos', 'sku_fulfillment_logs.wp_customers_info_id', '=', 'wp_customers_infos.id')
        ->where('sku_fulfillment_logs.order_id', $orderId)
        ->first();
        return $logs->wp_customer_name;
    }

    // public function erpProductData($sku, $location)
    // {
    //     // $erpProductData = DB::table("erp_products")->where(['sku' => $sku, 'site_access' => $location])->first();
    //     $erpProductData = ErpProduct::where(['sku' => $sku, 'site_access' => $location])->first();
    //     return $erpProductData;
    // }

    public function erpProductData($sku, $location)
    {
        $erpProductData = ErpProduct::firstOrCreate(
            // Search conditions
            ['sku' => $sku, 'site_access' => $location],
            // Default values if creating a new record
            [
                'sku'              => $sku,
                'site_access'      => $location,
                'fullfillment_qty' => 0,
                'created_at'       => date("Y-m-d H:i:s"),
                'updated_at'       => date("Y-m-d H:i:s"),
            ]
        );

        return $erpProductData;
    }
}
