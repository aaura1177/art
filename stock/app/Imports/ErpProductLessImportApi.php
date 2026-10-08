<?php

namespace App\Imports;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\Importable;
use App\product;
use App\ErpProduct;
use App\productLocations;
use App\hardwares;
use App\buyer;
use App\tempBuyer;
use App\tempProduct;
use App\pricingTable;
use App\ErpHistory;
use App\ErpSheet;


class ErpProductLessImportApi implements ToCollection, WithHeadingRow
{
    use Importable;

    /**
     * @param Collection $collection
     */

     protected $location;

     public function __construct($location)
     {
        $this->location = $location;
     } 

    public function collection(Collection $rows)
    {
        $location = $this->location;
        

        foreach ($rows as $row) {

            $saveHistory = false;
            

            $sheet = ErpSheet::where(['type' => 'less', 'site_access' => $location])->orderBy('id', 'desc')->first();
            $product1 = ErpProduct::where(['sku' => $row['sku'], 'site_access' => $location])->first();

            if (isset($product1->id)) {
                $quantity = $product1->quantity - $row['quantity'];
                $product1->quantity = $quantity;

                echo "case 1 \n";
            } else {
                $product1 = new ErpProduct;
                $product1->sku = $row['sku'];
                $product1->quantity = ($row['quantity'] == 0) ? 0 : -$row['quantity'];

                echo "case 2 \n";
            }
            $product1->site_access = $location;


            
            if ( isset($row['order_type']) && in_array(trim($row['order_type']), ['Dropship', 'clickcollect']) ) {
                $product1->save();

                $saveHistory = true;

                echo "case 3 \n";
            }

            if ( $location === "us" || $location === "california" || $location === "eu" ) {
                $product1->save();

                $saveHistory = true;

                echo "case 4 \n";
            }

            else if ( isset($row['order_type']) && trim($row['order_type']) === 'Bucket') {

                // get the bucket order details from artisanfurniture

                $orderId = $row['order_id'];

                $curl = curl_init();

                $bucketOrderUrl = 'https://www.artisanfurniture.net/wp-json/erp-route/get_bucket_order_details?order_id='.$orderId;

                if( $location === "us" || $location === "california" ) {

                    $bucketOrderUrl = 'https://www.artisanfurniture.us/wp-json/erp-route/get_bucket_order_details?order_id='.$orderId;
                }

                if( $location === "eu" ) {

                    $bucketOrderUrl = 'https://www.artisanfurniture.eu/wp-json/erp-route/get_bucket_order_details?order_id='.$orderId;
                }

                curl_setopt_array($curl, array(
                CURLOPT_URL => $bucketOrderUrl,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => '',
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 0,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => 'GET',
                ));

                $response = curl_exec($curl);

                curl_close($curl);
                
                $response = json_decode($response);

                $orderQty = $response[0]->quantity;
                $customer_name = $response[0]->customer_name;
                $customer_email = $response[0]->customer_email;
                $skuForLog = $response[0]->sku;
                $country = $response[0]->country;

                $postFields = http_build_query([
                    'action' => 'less-from-bucket',
                    'order_id' => $orderId,
                    'quantity' => $orderQty,
                    'customer_name' => $customer_name,
                    'customer_email' => $customer_email,
                    'sku' => $skuForLog,
                    'country' => $country
                ]);

                $curl = curl_init();

                curl_setopt_array($curl, array(
                    CURLOPT_URL => 'https://inventory.artisanadmin.net/api/fulfillment/fulfillment-order-manually-api',
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_ENCODING => '',
                    CURLOPT_MAXREDIRS => 10,
                    CURLOPT_TIMEOUT => 0,
                    CURLOPT_FOLLOWLOCATION => true,
                    CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                    CURLOPT_CUSTOMREQUEST => 'POST',
                    CURLOPT_POSTFIELDS => $postFields,
                    CURLOPT_HTTPHEADER => array(
                        'Content-Type: application/x-www-form-urlencoded',
                        'Cookie: XSRF-TOKEN=eyJpdiI6IkozS2JUbGVlNzJZU3psOXFGcnhNVUE9PSIsInZhbHVlIjoiY0I0M2VWeXdBVTNSVGtyRVhQK2ZWQ2VLRWl0SVcrNVhhMldmS1p3QTQ0b1d4cm1pSExVR2Z3VENoa0NLOUZ6UDArVHk0UkJsejgzOGszc3JQenFkMXdxd3Zna3Y0TFdWeHFEVDA1ZGxQZENIUGswSnRpQUkzd3NQTWxtL1I1WmIiLCJtYWMiOiIwYjJiYWExNTIzZWRhYjM5NjYwM2FmZGQ1OTUwODY0MmEyYzdlZGRiZjg1MTBjNDhiZjk3NDgxOWM3ZjI0Zjk5IiwidGFnIjoiIn0%3D; gv_pricing_session=eyJpdiI6InNQL2xWRTBHM2FRU3dlc1JUNEZteGc9PSIsInZhbHVlIjoiUHVjSGw1cThQMkIrS3VyRm4wY0t0SlZrcUx0cGlJdDlRTDRSZ3U1WmFHaFN0Q0pTaGZ5dkptd1g4d1gvbE1kWk1ZWE0yR3c0L0s3VEZmZnNXSDBYV2NzcnZMdHN2ekNmQ3dqOGtiUE5mLzV1SjZUdnh5WjBHUjc5ei9SSkQyV2IiLCJtYWMiOiIyNGY3MmM5YmU5ZjYzYzg2YWM3NWRiMmYyY2JkMDE0NDE4YWE2YzQ0ZDI0M2VlNjMxNTY0MTU4OTk3NzY4N2YyIiwidGFnIjoiIn0%3D'
                    ),
                ));
                
                $response = curl_exec($curl);

                $saveHistory = false;
                
                curl_close($curl);

                // if ($response !== false) {

                //     $customerInfoQuery = DB::select("
                //         SELECT id 
                //         FROM wp_customers_infos 
                //         WHERE wp_customer_email = ? 
                //         AND site_access = ?
                //     ", [$customer_email, $country]);

                //     $customerInfoId = $customerInfoQuery[0]->id ?? null;

                //     $fulfilmentSkuQtyQuery = DB::selectOne("
                //         SELECT * 
                //         FROM sku_fulfillment_qtys 
                //         WHERE sku = ? 
                //         AND wp_customers_info_id = ? 
                //         AND site_access = ?
                //     ", [$skuForLog, $customerInfoId, $country]);

                //     // Access fields
                //     $fulfilmentQty = $fulfilmentSkuQtyQuery->qty ?? null;

                //     DB::update("
                //         UPDATE erp_products 
                //         SET fullfillment_qty = ? 
                //         WHERE sku = ? 
                //         AND site_access = ?
                //     ", [$fulfilmentQty, $skuForLog, $country]);
                // }


                
            }

            if( isset($row['artisan_wayfair']) && trim($row['artisan_wayfair']) === 'Wayfair' ) {

                $product1->save();

                $saveHistory = true;
            }

            $artisan_wayfair = '(Artisan)';
            if (isset($row['artisan_wayfair'])) {
                $artisan_wayfair = '(' . $row['artisan_wayfair'] . ')';
            }

            if ( $saveHistory ) {
                $history = ErpHistory::create([
                    'sheet_id' => $sheet->id,
                    'sku' => $row['sku'],
                    'quantity' => $row['quantity'],
                    'type' => 'less',
                    'date' => $sheet->date,
                    'stock' => $product1->quantity,
                    'remark' => 'Less via Order id - ' . $row['order_id'] . $artisan_wayfair,
                    'site_access' => $location
                ]);
            }
        }
    }
}
