<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateInvoiceTable extends Migration
{
    public function up()
    {
        Schema::create('invoice', function (Blueprint $table) {
            $table->increments('id');
            $table->string('invoiceno');
            $table->date('date')->nullable();
            $table->string('consignee')->nullable();
            $table->integer('buyer_id')->unsigned();
            $table->foreign('buyer_id')->references('id')->on('buyers');
            $table->string('buyerorderno')->nullable();
            $table->string('containerno')->nullable();
            $table->string('vehicleno')->nullable();
            $table->string('ewaybillno')->nullable();
            $table->string('pkgs')->nullable();
            $table->string('currency')->nullable();
            $table->double('conrate')->nullable();
            $table->longText('declaration');
            $table->string('fob')->nullable();
            $table->string('payterms')->nullable();
            $table->string('shipmentby')->nullable();
            $table->string('desgoods')->nullable();
            $table->string('carriage')->nullable();
            $table->string('receipt')->nullable();
            $table->string('shipment')->nullable();
            $table->string('postloading')->nullable();
            $table->string('discharge')->nullable();
            $table->string('destination')->nullable();
            $table->double('shipping_charges', 12, 2)->nullable();
            $table->double('packing_charges', 12, 2)->nullable();
            $table->double('discount', 12, 2)->nullable();
            $table->double('totalamount', 12, 2)->nullable();
            $table->double('totalgst', 12, 2)->nullable(); 
            $table->double('rateamount', 12, 2)->nullable();    
            $table->double('totalquantity')->nullable();
            $table->double('totalwt', 12, 2)->nullable();
            $table->double('totalgrosswt', 12, 2)->nullable();
            $table->integer('totalbox')->nullable();
            $table->integer('status')->unsigned()->default('0');
            $table->integer('invoicetype');  
            $table->integer('exportstatus');
            $table->timestamps();
        });

        DB::update("ALTER TABLE invoice AUTO_INCREMENT = 1001;");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('invoice');
    }
}
