<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreatePackinglistproductsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('packinglistproducts', function (Blueprint $table) {
            $table->bigIncrements('id');
			$table->integer('packinglist_id')->nullable();
			$table->integer('product')->nullable();
			$table->integer('quantity')->nullable();
			$table->float('weight')->nullable();
			$table->float('subtotalnetwt')->nullable();
			$table->float('grosswt')->nullable();
			$table->float('subtotalgrosswt')->nullable();
			$table->float('box')->nullable();
			$table->float('endBox')->nullable();
			$table->float('subTotalBox')->nullable();
			$table->float('qtybox')->nullable();
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
        Schema::dropIfExists('packinglistproducts');
    }
}
