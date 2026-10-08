<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreatePackagingPricingTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('packaging_pricing', function (Blueprint $table) {
            $table->bigIncrements('id');
			$table->integer('supplier_id')->nullable();
			$table->float('3ply')->nullable();
			$table->float('5ply')->nullable();
			$table->float('7ply')->nullable();
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
        Schema::dropIfExists('packaging_pricing');
    }
}
