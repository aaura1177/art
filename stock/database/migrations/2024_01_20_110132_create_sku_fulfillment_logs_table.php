<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateSkuFulfillmentLogsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('sku_fulfillment_logs', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('sku', 50);
            $table->string('order_id', 50);
            $table->integer('qty');
            $table->string('order_type', 50);
            $table->integer('wp_customers_info_id')->nullable();
            $table->timestamps();
            $table->string('type', 50)->comment('Add / Less');
            $table->string('site_access', 191)->default('UK');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('sku_fulfillment_logs');
    }
}
