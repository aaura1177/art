<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateQualityTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('quality', function (Blueprint $table) {
            $table->bigIncrements('id');
			$table->integer('product_id')->unsigned();
            $table->integer('supplier_id')->unsigned();
            $table->string('supplier_inv_no')->nullable();
            $table->integer('status')->unsigned();
            $table->integer('quantity')->default('1');
            $table->date('date')->nullable();
            $table->string('remarks')->nullable();
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
        Schema::dropIfExists('quality');
    }
}
