<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreatePoTableTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('poTable', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('poid')->unsigned();
            $table->foreign('poid')->references('id')->on('purchase_order');
            $table->integer('product_id')->unsigned();
            $table->foreign('product_id')->references('id')->on('product_table');
            $table->string('EAN');
            $table->integer('quantity')->nullable();
            $table->string('unit');
            $table->double('rate', 12, 2)->nullable();
            $table->double('amount', 12, 2)->nullable();
            $table->double('gstslab', 12, 2)->nullable();
            $table->double('gstamount', 12, 2)->nullable();
            $table->integer('remqty')->nullable();
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
        Schema::dropIfExists('poTable');
    }
}
