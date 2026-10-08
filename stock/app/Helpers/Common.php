<?php 
namespace App\Helpers;
use App\SettingsOption;
use Exception;
use App\supplier;
use App\purchaseOrder;
use App\purchaseBill;
use App\supplierInvoice;
use App\ContainersAllocationItem;
use App\ContainersAllocationDetail;
use App\stockLog;
use App\product;
class Common
{
    public static function test(){
        return "Its Working !";
    }

    public static function getCountries(){
        return ['india'=>'India','UK'=>'UK','US'=>'US','EU'=>'EU','Canada'=>'CANADA','Australia'=>'AUSTRALIA'];
    }

    public static function getSettings(){
        $settingsOption = SettingsOption::get()->toArray();
        $settingsOptionArray = [];
        foreach ($settingsOption as $item) {
            $settingsOptionArray[$item['setting_key']] = $item['setting_value'];
        }
        return $settingsOptionArray;
    }

    public static function callCurlRequest($url, $method = 'GET', $postFields = array(), $headers = array(), $timeout = 2000, $asynch = false, $postAsBodyParam = false, $userPass = false)
    {
        try {
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            if ($method == 'GET') {
                curl_setopt($ch, CURLOPT_POST, false);
            }

            if ($method == 'POST') {
                curl_setopt($ch, CURLOPT_POST, TRUE);
                curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
                if (is_array($postFields) && count($postFields)) {
                    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($postFields));
                } else {
                    curl_setopt($ch, CURLOPT_POSTFIELDS, $postFields);
                }
                if ($postAsBodyParam === true) {
                    curl_setopt($ch, CURLOPT_POSTFIELDS, @json_encode($postFields));
                } elseif ($postAsBodyParam === 5) {
                    // $postAsBodyParam = 5 means put as it is
                    curl_setopt($ch, CURLOPT_POSTFIELDS, $postFields);
                }
            }

            if ($method == 'DELETE') {
                curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE');
                if (is_array($postFields) && count($postFields)) {
                    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($postFields));
                }
            }

            if ($method == 'PUT') {
                if (is_array($headers)) {
                    $headers = array_merge($headers, array('X-HTTP-Method-Override: PUT'));
                }
                if (is_array($postFields) && count($postFields)) {
                    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($postFields));
                }
            }
            if ($userPass) {
                curl_setopt($ch, CURLOPT_USERPWD, $userPass);
            }
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            if (is_array($headers) && count($headers)) {
                curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
            } else {
                $headers = array("Content-Type: application/json");
                curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
            }
            // Asynchronous Request
            if ($asynch === true) {
                curl_setopt($ch, CURLOPT_FRESH_CONNECT, true);
                curl_setopt($ch, CURLOPT_TIMEOUT, 1);
            } else {
                curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
            }
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            $response = curl_exec($ch);
            $result = curl_getinfo($ch);
            curl_close($ch);

            if ($asynch || (isset($result['http_code']) && in_array($result['http_code'], [200, 201, 204]))) {
                return $response;
            } else {
                throw new Exception($response, $result['http_code']);
            }

        } catch (Exception $e) {
            throw $e;
        }
    }

    public static function getMeasurements(){
        $measurements = [
            '1_sides' => "1 Sides",
            '2_sides' => "2 Sides",
            '3_sides' => "3 Sides",
            'kg' => "KG",
            'lbs' => "LBS",
            'box' => "BOX",
        ];
        return $measurements;
    }

    /**
     * Attribute key => unit key for courier conditions.
     * When user selects an attribute, Measurements dropdown shows this unit.
     */
    public static function getMeasurementUnitMap(){
        return [
            '1_sides' => 'cm',
            '2_sides' => 'cm',
            '3_sides' => 'cm',
            'kg'      => 'kg',
            'lbs'     => 'lbs',
            'box'     => 'kg',
        ];
    }
    
    public static function getMeasurementsUnits(){
        $measurementUnits = [
            'cm' => "CM",
            'kg' => "KG",
            'lbs' => "LBS",
        ];
        return $measurementUnits;
    }


    public function getSupplierMonthlyInvoice(int $supplierId, string $plannedDate)
    {
        $startOfMonth = date('Y-m-01', strtotime($plannedDate));
        $endOfMonth = date('Y-m-t', strtotime($plannedDate));
         
        return purchaseOrder::where('supplier_id', $supplierId)
            ->whereBetween('del_date', [$startOfMonth, $endOfMonth])
            ->sum('tamount');
    }


    public function getItemDetailForSupplier($supplier_id, $product_id, $container_allocation_id)
    {
        $data = [];

        // Build the base query once
        $baseQuery = ContainersAllocationDetail::where('supplier_id', $supplier_id)
            ->where('product_id', $product_id)
            ->where('container_allocation_id', $container_allocation_id)
            ->whereNull('deleted_at');

        // Get first matching record
        $record = (clone $baseQuery)->first();

        // Set record existence details
        if ($record) {
            $data['record_exists'] = true;
            $data['row_id'] = $record->id;
            $data['po_exists'] = !is_null($record->purchase_order_id);
        } else {
            $data['record_exists'] = false;
            $data['row_id'] = 0;
            $data['po_exists'] = false;
        }

        // Sum the asked_quantity
        $data['asked_quantity'] = (clone $baseQuery)->sum('asked_quantity');

        return $data;
    }


    public static function checkPoAccepted($purchase_order_id)
    {
        $record = purchaseOrder::where('id', $purchase_order_id)->first();
        if($record){
            if($record->supplier_status == 1)
            {
                return true;
            }
        }
        return false;
    }

    public static function getRemainingQtyNotInStock($product_id, $container_allocation_id)
    {

        $records = ContainersAllocationDetail::with('purchaseOrderInfo.poTable')
            ->where('container_allocation_id', $container_allocation_id)
            ->where('product_id', $product_id)
            ->whereNull('deleted_at')
            ->where('is_in_stock',0)
            ->whereNotNull('purchase_order_id')
            ->get();

        if(!empty($records[0]))
        {
            $totalRemQty = 0;
            foreach ($records as $record) 
            {
                $poTable = collect($record->purchaseOrderInfo->poTable ?? []);
                $remQty = $poTable->firstWhere(function ($po) use ($record) {
                    return $po['poid'] == $record->purchase_order_id && $po['product_id'] == $record->product_id;
                })['remqty'] ?? 0;
                $totalRemQty += $remQty;
            }
        }
        else
        {
            $totalRemQty = ContainersAllocationDetail::where('container_allocation_id', $container_allocation_id)
            ->where('product_id', $product_id)
            ->whereNull('deleted_at')
            ->where('is_in_stock',0)
            ->sum('asked_quantity');
        }
        
        return $totalRemQty;
    }


    public function checkSupplierInvoice($purchase_order_id)
    {
        $data = [
            'exists' => false,
            'is_approved' => false,
            'invoice_id' => 0,
        ];
        $record = supplierInvoice::where('purchase_order_id', $purchase_order_id)->first();
        if($record){
            $data['exists'] = true;
            $data['is_approved'] = $record->is_approved;
            $data['invoice_id'] = $record->id;
        }

        return $data;
    }
    public function checkPurchaseBill($purchase_order_id)
    {
        $data = [
            'exists' => false,
            'is_checked' => false,
            'bill_id' => 0,
        ];
        $record = purchaseBill::where('purchaseOrder_id', $purchase_order_id)->first();
        if($record){
            $data['exists'] = true;
            $data['is_checked'] = $record->is_checked;
            $data['bill_id'] = $record->id;
        }

        return $data;
    }


    public function getbatchesbyid($id)
    {
        $product= product::where('id',$id)->first();
        $batches = stockLog::where('product_id', $id)
            ->whereNotNull('batch_no') // Ensure batch_no is not null
            ->whereNotNull('batch_balance') // Ensure batch_balance is not null
            ->where('batch_balance', '>', 0) // Ensure batch_balance is greater than 0
            ->select('product_id', 'batch_no', 'batch_balance')
            ->whereIn('id', function ($query) {
                $query->selectRaw('MAX(id)')
                    ->from('stock_log')
                    ->whereNotNull('batch_no') // Ensure batch_no is not null in subquery
                    ->groupBy('product_id', 'batch_no');
            })
            ->get();
            
        if(count($batches) > 0)
        {
            
            $batches = $batches->concat($batches->map(function ($batch) use ($product, $batches) {
                return [
                    'product_id' => $batch->product_id,
                    'batch_no' => 'No Batch', // Modify batch_no to distinguish
                    'batch_balance' => @$product->quantity-$batches->sum('batch_balance') , // Example of modifying balance
                ];
            }));
            
        }
        else
        {
            $batches = collect([
                [
                    'product_id' => $id,
                    'batch_no' => 'No Batch', // Modify batch_no to distinguish
                    'batch_balance' => @$product->quantity ?? 0, // Example of modifying balance
                ]
            ]);
            
        }

        return $batches;
    }

}
?>

