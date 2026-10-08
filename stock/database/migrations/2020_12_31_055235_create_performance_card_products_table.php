<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreatePerformanceCardProductsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('performance_card_products', function (Blueprint $table) {
            $table->bigIncrements('id');
			$table->integer('performance_card_id')->nullable();
			$table->integer('product_id')->nullable();
			$table->integer('qty_received')->nullable();
			$table->integer('qty_rejected')->nullable();
			$table->integer('net_qty')->nullable();
			$table->text('remarks')->nullable();
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
        Schema::dropIfExists('performance_card_products');
    }
}
