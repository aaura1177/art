<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddNewColRemarkInSkuFulfillmentLogs extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('sku_fulfillment_logs', function (Blueprint $table) {
            $table->string('remark', 100)->nullable();
            $table->string('remaining_stock', 11)->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('sku_fulfillment_logs', function (Blueprint $table) {
            //
        });
    }
}
