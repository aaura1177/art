<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreatePbTableTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('pb_table', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('purchaseOrder_id')->unsigned();
            $table->foreign('purchaseOrder_id')->references('id')->on('purchase_order');
            $table->integer('product_id')->unsigned();
            $table->foreign('product_id')->references('id')->on('product_table');
            $table->integer('purchasebill_id')->unsigned();
            $table->foreign('purchasebill_id')->references('id')->on('purchase_bill');
            $table->string('EAN')->nullable();
            $table->foreign('EAN')->references('EAN')->on('product_table');
            $table->integer('orderqty');
            $table->integer('receiveqty');
            $table->integer('remainingqty');
            $table->double('rate', 12, 2)->nullable();
            $table->double('amount', 12, 2)->nullable();
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
        Schema::dropIfExists('pb_table');
    }
}
