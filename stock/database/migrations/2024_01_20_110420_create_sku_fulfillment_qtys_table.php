<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateSkuFulfillmentQtysTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('sku_fulfillment_qtys', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->integer('wp_customers_info_id')->nullable();
            $table->string('sku', 50);
            $table->integer('qty');
            $table->timestamps();
            $table->string('site_access', 50)->default('UK');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('sku_fulfillment_qtys');
    }
}
