<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateInvoiceTableTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('invoiceTable', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('invoice_id')->unsigned();
            $table->foreign('invoice_id')->references('id')->on('invoice');
            $table->integer('product_id')->unsigned();
            $table->foreign('product_id')->references('id')->on('product_table');
            $table->integer('quantity');
            $table->double('rate', 12, 2);
            $table->double('amount', 12, 2);
            $table->double('weight', 12, 2)->nullable();
            $table->double('subtotalnetwt', 12, 2)->nullable();
            $table->double('grosswt', 12, 2)->nullable();
            $table->double('subtotalgrosswt', 12, 2)->nullable();
            $table->double('gstslab', 12, 2)->nullable();
            $table->double('gstamount', 12, 2)->nullable();
            $table->integer('box')->default('0');
            $table->integer('endbox')->default('0');
            $table->integer('subtotalbox')->default('0');
            $table->string('qtybox')->default('1 Pc/Box');
            $table->integer('remqty');
            $table->longText('descriptionBox')->nullable();
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
        Schema::dropIfExists('invoiceTable');
    }
}
