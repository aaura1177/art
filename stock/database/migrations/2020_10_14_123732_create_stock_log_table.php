<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateStockLogTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('stock_log', function (Blueprint $table) {
            $table->bigIncrements('id');
			$table->string('ref_no')->nullable();
			$table->string('voucher_no')->nullable();
			$table->integer('type')->nullable();
			$table->integer('product_id')->nullable();
			$table->integer('quantity')->nullable();
			$table->integer('opening_balance')->nullable();
			$table->integer('remaining_stock')->nullable();
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
        Schema::dropIfExists('stock_log');
    }
}
