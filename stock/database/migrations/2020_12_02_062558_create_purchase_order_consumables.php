<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreatePurchaseOrderConsumables extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('purchase_order_consumables', function (Blueprint $table) {
            $table->bigIncrements('id');
			$table->string('pono');
            $table->integer('supplier_id')->unsigned();
            $table->date('podate')->nullable();
            $table->date('del_date')->nullable();
            $table->string('ref_supplier')->nullable();
            $table->string('buyer_orderno')->nullable();
            $table->string('payterms')->nullable();
            $table->string('remarks')->nullable();
            $table->double('tgst', 12, 2)->nullable();
            $table->integer('tquantity')->nullable();
            $table->double('subTotal', 12, 2)->nullable();
            $table->double('tamount', 12, 2)->nullable();
            $table->integer('remqty')->nullable();
            $table->integer('status')->unsigned()->default('0');
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
        Schema::dropIfExists('purchase_order_consumables');
    }
}
