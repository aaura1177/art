<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreatePorTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('porTable', function (Blueprint $table) {
            $table->bigIncrements('id');
			$table->integer('poid')->unsigned();
            $table->integer('product_id')->unsigned();
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
        Schema::dropIfExists('porTable');
    }
}
