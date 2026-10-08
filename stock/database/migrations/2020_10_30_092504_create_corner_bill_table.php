<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateCornerBillTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('corner_bill', function (Blueprint $table) {
            $table->bigIncrements('id');
			$table->integer('product_id')->nullable();
			$table->integer('invoice_id')->nullable();
			$table->integer('corners')->nullable();
			$table->integer('total_corners')->nullable();
			$table->float('total_corners_amount')->nullable();
			$table->integer('l')->nullable();
			$table->integer('total_l')->nullable();
			$table->float('total_l_amount')->nullable();
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
        Schema::dropIfExists('corner_bill');
    }
}
