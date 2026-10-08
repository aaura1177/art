<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreatePurchaseBillTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('purchase_bill', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('purchaseOrder_id')->unsigned();
            $table->foreign('purchaseOrder_id')->references('id')->on('purchase_order');
            $table->string('supp_inv_no');
            $table->date('supp_inv_date');
            $table->string('ewaybill')->nullable();
            $table->integer('quantity');
            $table->double('subtotal', 12, 2);
            $table->float('gst', 12, 2)->nullable();
            $table->double('freight', 12, 2)->nullable();
            $table->double('total', 12, 2);
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
        Schema::dropIfExists('purchase_bill');
    }
}
