<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateSamplePbTableTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('sample_pb_table', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->integer('purchaseOrder_id');
            $table->integer('sample_id');
            $table->integer('sample_purchasebill_id');
            $table->string('EAN');
            $table->integer('orderqty');
            $table->integer('receiveqty');
            $table->float('rate');
            $table->float('amount');
            $table->string('location');
            $table->integer('prod_remaining');
            $table->string('remarks');
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
        Schema::dropIfExists('sample_pb_table');
    }
}
