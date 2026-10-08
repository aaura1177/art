<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreatePostableTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('posTable', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->integer('poid')->unsigned();
            $table->integer('product_id')->unsigned();
            $table->integer('sample_id')->unsigned();
            $table->string('EAN');
            $table->integer('quantity')->nullable();
            $table->string('unit');
            $table->double('rate', 12, 2)->nullable();
            $table->double('amount', 12, 2)->nullable();
            $table->double('gstslab', 12, 2)->nullable();
            $table->double('gstamount', 12, 2)->nullable();
            $table->integer('remqty')->nullable();
            $table->integer('priority')->nullable();
            $table->integer('legs')->nullable();
            $table->string('delivery_point');
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
        Schema::dropIfExists('posTable');
    }
}
