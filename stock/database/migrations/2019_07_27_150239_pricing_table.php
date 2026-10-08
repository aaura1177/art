<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class PricingTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('pricingTable', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('product_id')->unsigned();
            $table->foreign('product_id')->references('id')->on('product_table');
            $table->integer('buyer_id')->unsigned();
            $table->foreign('buyer_id')->references('id')->on('temp_buyer_table');
            $table->date('startDate')->nullable();
            $table->date('endDate')->nullable();
            $table->longText('remarks')->nullable();
            $table->double('buyingCost', 12, 2)->nullable();
            $table->double('fabricCost', 12, 2)->nullable();
            $table->double('tapestryConsumed', 12, 2)->nullable();
            $table->double('tapestryCost', 12, 2)->nullable();
            $table->double('fillerCost', 12, 2)->nullable();
            $table->double('labourCost', 12, 2)->nullable();
            $table->double('hardwareCost', 12, 2)->nullable();
            $table->double('pUnitCost', 12, 2)->nullable();
            $table->double('polishCost', 12, 2)->nullable();
            $table->double('wSPackageCost', 12, 2)->nullable();
            $table->double('dSPackageCost', 12, 2)->nullable();
            $table->double('shippingCost', 12, 2)->nullable();
            $table->double('costPrice', 12, 2)->nullable();
            $table->double('adminCostPercent', 12, 2)->nullable();
            $table->double('adminCost', 12, 2)->nullable();
            $table->double('profitCost', 12, 2)->nullable();
            $table->double('finalCost', 12, 2)->nullable();
            $table->string('currency')->nullable();
            $table->float('converRate')->nullable();
            $table->double('fobINCost', 12, 2)->nullable();
            $table->double('boxWt', 12, 2)->nullable();
            $table->double('volWt', 12, 2)->nullable();
            $table->double('shippingCost2', 12, 2)->nullable();
            $table->double('StorageCost', 12, 2)->nullable();
            $table->double('adminCost2', 12, 2)->nullable();
            $table->double('qualityAssurance', 12, 2)->nullable();
            $table->double('landedCost', 12, 2)->nullable();
            $table->double('adminCostPercent2', 12, 2)->nullable();
            $table->double('totalAdminCost', 12, 2)->nullable();
            $table->double('finalPricePer', 12, 2)->nullable();
            $table->double('finalPrice', 12, 2)->nullable();
            $table->string('courierType')->nullable();
            $table->double('courierCost', 12, 2)->nullable();
            $table->double('deliveryCost', 12, 2)->nullable();
            $table->double('newDelCost', 12, 2)->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        //
    }
}
