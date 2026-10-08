<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateShippingTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('shipping', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->integer('shipping_line_id');
            $table->string('container');
            $table->string('reference');
            $table->float('thc')->nullable();
            $table->float('bl')->nullable();
            $table->float('incidental_charges')->nullable();
            $table->float('transit_time')->nullable();
            $table->float('type')->nullable();
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
        Schema::dropIfExists('shipping');
    }
}
