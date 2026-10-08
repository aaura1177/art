<?php

namespace App\Console\Commands;

use App\ErpHistoryManager;
use App\ErpProduct;
use App\Helpers\ThreePLCentralHelper;
use Illuminate\Console\Command;

class CheckWarehouseStock extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'warehouse:check-us-3pl-warehouse-stock';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'This cron will sync 3PL warehouse qantity';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $tokenResponse = ThreePLCentralHelper::get3PLAccessToken();
        $tokenResponse = json_decode($tokenResponse);
        $token = $tokenResponse->access_token;
        print_r($token);
        echo "\n\n";

        $inventoryResponse = ThreePLCentralHelper::getInventory($token);
        $location = "US";
        $type = "add";

        if(count($inventoryResponse)) {
            foreach($inventoryResponse as $product) {
                $sku = $product->upc;
                $availableQty = intval($product->onHandQty);
                $addErpHistory = false;

                $erpProduct = ErpProduct::where(['sku' => $sku, 'site_access' => $location])->first();
                $erpProduct = $erpProduct ? $erpProduct->toArray() : [];
                print_r($erpProduct);

                if(count($erpProduct) && ($erpProduct['warehouse_quantity'] != $availableQty)) {
                    ErpProduct::where(['sku' => $sku, 'site_access' => $location])->update(['warehouse_quantity' => $availableQty]);
                    $addErpHistory = true;
                } elseif(count($erpProduct) == 0) {
                    $erpProductObj  = new ErpProduct();
                    $erpProductObj->sku = $sku;
                    $erpProductObj->quantity = $availableQty;
                    $erpProductObj->zone_name = "EDISON-NJ";
                    $erpProductObj->zone_serial = "artisan-13000 6009900003402";
                    $erpProductObj->warehouse_quantity = $availableQty;
                    $erpProductObj->site_access = $location;
                    $erpProductObj->save();
                    $addErpHistory = true;
                }

                if($addErpHistory){
                    $historyhistoryObj = new ErpHistoryManager();
                    $historyhistoryObj->sku = $sku;
                    $historyhistoryObj->sheet_id = 0;
                    $historyhistoryObj->quantity = $availableQty;
                    $historyhistoryObj->type = $type;
                    $historyhistoryObj->date = date('Y-m-d');
                    $historyhistoryObj->stock = $availableQty;
                    $historyhistoryObj->reason = $type;
                    $historyhistoryObj->site_access = $location;
                    $historyhistoryObj->remark = "API ENTRY";
                    $historyhistoryObj->save();
                }
                
                
            }
        }
        
    }

}
