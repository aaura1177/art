<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateUpholestryBillTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('upholestry_bill', function (Blueprint $table) {
            $table->bigIncrements('id');
			$table->integer('invoice_id')->nullable();
			$table->integer('product_id')->nullable();
			$table->integer('contractor_id')->nullable();
			$table->integer('quantity')->nullable();
			$table->float('amount')->nullable();
			$table->float('upholestry_rate')->nullable();
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
        Schema::dropIfExists('upholestry_bill');
    }
}
