<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateContractorBillTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('contractor_bill', function (Blueprint $table) {
            $table->bigIncrements('id');
			$table->integer('invoice_id')->nullable();
			$table->integer('product_id')->nullable();
			$table->integer('contractor_id')->nullable();
			$table->integer('quantity')->nullable();
			$table->float('amount')->nullable();
			$table->float('finishing_rate')->nullable();
			$table->string('finishing')->nullable();
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
        Schema::dropIfExists('contractor_bill');
    }
}
