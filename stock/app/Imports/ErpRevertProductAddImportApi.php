<?php

namespace App\Imports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\Importable;
use App\product;
use App\ErpProduct;
use App\ErpSheet;
use App\ErpHistory;
use App\productLocations;
use App\hardwares;
use App\buyer;
use App\tempBuyer;
use App\tempProduct;
use App\pricingTable;
use Illuminate\Support\Facades\DB;


class ErpRevertProductAddImportApi implements ToCollection, WithHeadingRow
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

    // public function collection(Collection $rows)
    // {
    //     $location = $this->location;
    //     $saveHistory = false;

    //     foreach ($rows as $row) {
    //         $sheet = ErpSheet::where(['type' => 'add', 'site_access' => $location])->orderBy('id', 'desc')->first();
    //         $product1 = ErpProduct::where(['sku' => $row['sku'], 'site_access' => $location])->first();
            
    //         if (isset($product1->id)) {
    //             $quantity = $product1->quantity + $row['quantity'];
    //             $product1->quantity = $quantity;
    //         } else {
    //             $product1 = new ErpProduct;
    //             $product1->sku = $row['sku'];
    //             $product1->quantity = $row['quantity'];
    //         }
    //         $product1->site_access = $location;

    //         if ( isset($row['order_type']) &&  trim($row['order_type']) === 'Dropship' ) {
    //             $product1->save();

    //             $saveHistory = true;
    //         }

    //         else if ( isset($row['order_type']) && trim($row['order_type']) === 'Bucket') {

    //             // get the bucket order details from artisanfurniture

    //             $orderId = $row['order_id'];

    //             $curl = curl_init();

    //             curl_setopt_array($curl, array(
    //             CURLOPT_URL => 'https://www.artisanfurniture.net/wp-json/erp-route/get_bucket_order_details?order_id='.$orderId ,
    //             CURLOPT_RETURNTRANSFER => true,
    //             CURLOPT_ENCODING => '',
    //             CURLOPT_MAXREDIRS => 10,
    //             CURLOPT_TIMEOUT => 0,
    //             CURLOPT_FOLLOWLOCATION => true,
    //             CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
    //             CURLOPT_CUSTOMREQUEST => 'GET',
    //             ));

    //             $response = curl_exec($curl);

    //             curl_close($curl);
                
    //             $response = json_decode($response);

    //             $orderQty = $response[0]->quantity;
    //             $customer_name = $response[0]->customer_name;
    //             $customer_email = $response[0]->customer_email;
    //             $skuForLog = $response[0]->sku;
    //             $country = $response[0]->country;

    //             $postFields = http_build_query([
    //                 'action' => 'return-into-bucket',
    //                 'order_id' => $orderId,
    //                 'quantity' => $orderQty,
    //                 'customer_name' => $customer_name,
    //                 'customer_email' => $customer_email,
    //                 'sku' => $skuForLog,
    //                 'country' => $country
    //             ]);

    //             $curl = curl_init();

    //             curl_setopt_array($curl, array(
    //                 CURLOPT_URL => 'https://inventory.artisanadmin.net/api/fulfillment/fulfillment-order-manually-api',
    //                 CURLOPT_RETURNTRANSFER => true,
    //                 CURLOPT_ENCODING => '',
    //                 CURLOPT_MAXREDIRS => 10,
    //                 CURLOPT_TIMEOUT => 0,
    //                 CURLOPT_FOLLOWLOCATION => true,
    //                 CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
    //                 CURLOPT_CUSTOMREQUEST => 'POST',
    //                 CURLOPT_POSTFIELDS => $postFields,
    //                 CURLOPT_HTTPHEADER => array(
    //                     'Content-Type: application/x-www-form-urlencoded',
    //                     'Cookie: XSRF-TOKEN=eyJpdiI6IkozS2JUbGVlNzJZU3psOXFGcnhNVUE9PSIsInZhbHVlIjoiY0I0M2VWeXdBVTNSVGtyRVhQK2ZWQ2VLRWl0SVcrNVhhMldmS1p3QTQ0b1d4cm1pSExVR2Z3VENoa0NLOUZ6UDArVHk0UkJsejgzOGszc3JQenFkMXdxd3Zna3Y0TFdWeHFEVDA1ZGxQZENIUGswSnRpQUkzd3NQTWxtL1I1WmIiLCJtYWMiOiIwYjJiYWExNTIzZWRhYjM5NjYwM2FmZGQ1OTUwODY0MmEyYzdlZGRiZjg1MTBjNDhiZjk3NDgxOWM3ZjI0Zjk5IiwidGFnIjoiIn0%3D; gv_pricing_session=eyJpdiI6InNQL2xWRTBHM2FRU3dlc1JUNEZteGc9PSIsInZhbHVlIjoiUHVjSGw1cThQMkIrS3VyRm4wY0t0SlZrcUx0cGlJdDlRTDRSZ3U1WmFHaFN0Q0pTaGZ5dkptd1g4d1gvbE1kWk1ZWE0yR3c0L0s3VEZmZnNXSDBYV2NzcnZMdHN2ekNmQ3dqOGtiUE5mLzV1SjZUdnh5WjBHUjc5ei9SSkQyV2IiLCJtYWMiOiIyNGY3MmM5YmU5ZjYzYzg2YWM3NWRiMmYyY2JkMDE0NDE4YWE2YzQ0ZDI0M2VlNjMxNTY0MTU4OTk3NzY4N2YyIiwidGFnIjoiIn0%3D'
    //                 ),
    //             ));
                
    //             $response = curl_exec($curl);

    //             $saveHistory = true;
                
    //             curl_close($curl);
                
    //         }

    //         if( isset($row['artisan_wayfair']) && trim($row['artisan_wayfair']) === 'Wayfair' ) {

    //             $product1->save();

    //             $saveHistory = true;
    //         }

    //         if ( $saveHistory ) {
           
    //             $history = ErpHistory::create([
    //                 'sheet_id' => $sheet->id,
    //                 'sku' => $row['sku'],
    //                 'quantity' => $row['quantity'],
    //                 'type' => 'add',
    //                 'date' => $sheet->date,
    //                 'stock' => $product1->quantity,
    //                 'remark' => 'reverted',
    //                 'site_access' => $location
    //             ]);
    //         }
    //     }
    // }

    public function collection(Collection $rows)
    {
        $location = $this->location;

        // ✅ Move sheet query OUTSIDE the loop — it's the same value every iteration
        $sheet = ErpSheet::where(['type' => 'add', 'site_access' => $location])
            ->orderBy('id', 'desc')
            ->first();

        foreach ($rows as $row) {
            $this->processRow($row, $location, $sheet);
        }
    }

    private function processRow($row, $location, $sheet, int $attempt = 1)
    {
        try {
            DB::transaction(function () use ($row, $location, $sheet) {

                // ✅ Lock the row for update immediately to avoid deadlock races
                $product1 = ErpProduct::where(['sku' => $row['sku'], 'site_access' => $location])
                    ->lockForUpdate()   // SELECT ... FOR UPDATE
                    ->first();

                if ($product1) {
                    $product1->quantity += $row['quantity'];
                } else {
                    $product1 = new ErpProduct;
                    $product1->sku      = $row['sku'];
                    $product1->quantity = $row['quantity'];
                    $product1->site_access = $location;
                }

                $saveHistory = false;
                $orderType   = isset($row['order_type']) ? trim($row['order_type']) : null;

                if ($orderType === 'Dropship' || $orderType === 'clickcollect') {
                    $product1->save();
                    $saveHistory = true;
                } elseif ($orderType === 'Bucket') {
                    $this->handleBucketOrder($row);
                    $saveHistory = true;
                }

                if ( $location === "us" || $location === "california" || $location === "eu" ) {
                    $product1->save();
                    $saveHistory = true;
                }

                if (isset($row['artisan_wayfair']) && trim($row['artisan_wayfair']) === 'Wayfair') {
                    $product1->save();
                    $saveHistory = true;
                }

                if ($saveHistory) {
                    ErpHistory::create([
                        'sheet_id'    => $sheet->id,
                        'sku'         => $row['sku'],
                        'quantity'    => $row['quantity'],
                        'type'        => 'add',
                        'date'        => $sheet->date,
                        'stock'       => $product1->quantity,
                        'remark'      => 'reverted',
                        'site_access' => $location,
                    ]);
                }
            });

        } catch (\Illuminate\Database\QueryException $e) {
            // ✅ Retry up to 3 times on lock timeout (MySQL error 1205)
            if ($attempt <= 3 && str_contains($e->getMessage(), '1205')) {
                sleep($attempt); // back off: 1s, 2s, 3s
                $this->processRow($row, $location, $sheet, $attempt + 1);
            } else {
                \Log::error("Lock timeout on SKU {$row['sku']} after {$attempt} attempts: " . $e->getMessage());
                throw $e;
            }
        }
    }

    private function handleBucketOrder($row)
    {
        $orderId  = $row['order_id'];
        $details  = $this->fetchBucketOrderDetails($orderId);

        $postFields = http_build_query([
            'action'         => 'return-into-bucket',
            'order_id'       => $orderId,
            'quantity'       => $details->quantity,
            'customer_name'  => $details->customer_name,
            'customer_email' => $details->customer_email,
            'sku'            => $details->sku,
            'country'        => $details->country,
        ]);

        // ... your existing curl POST logic here ...
    }

    private function fetchBucketOrderDetails($orderId)
    {

        $bucketOrderUrl = 'https://www.artisanfurniture.net/wp-json/erp-route/get_bucket_order_details?order_id='.$orderId;

        if( $this->location === "us" || $this->location === "california" ) {

            $bucketOrderUrl = 'https://www.artisanfurniture.us/wp-json/erp-route/get_bucket_order_details?order_id='.$orderId;
        }

        if( $this->location === "eu" ) {

            $bucketOrderUrl = 'https://www.artisanfurniture.eu/wp-json/erp-route/get_bucket_order_details?order_id='.$orderId;
        }

        $curl = curl_init();
        curl_setopt_array($curl, [
            CURLOPT_URL            => $bucketOrderUrl,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 30,
            CURLOPT_FOLLOWLOCATION => true,
        ]);
        $response = curl_exec($curl);
        curl_close($curl);

        return json_decode($response)[0];
    }
}
