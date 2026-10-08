<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreatePackingSheetTableTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('pstable', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('packingSheet_id')->unsigned();
            $table->foreign('packingSheet_id')->references('id')->on('packingsheet');
            $table->integer('product_id')->unsigned();
            $table->foreign('product_id')->references('id')->on('product_table');
            $table->integer('invoice_id')->unsigned();
            $table->foreign('invoice_id')->references('id')->on('invoice');
            $table->string('EAN');
            $table->foreign('EAN')->references('EAN')->on('product_table');
            $table->integer('quantity')->default('0');
            $table->double('netwt', 12, 2)->default('0');
            $table->double('grosswt', 12, 2)->default('0');
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
        Schema::dropIfExists('pstable');
    }
}
