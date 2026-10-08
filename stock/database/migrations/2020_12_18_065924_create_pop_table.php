<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreatePopTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('popTable', function (Blueprint $table) {
            $table->bigIncrements('id');
			$table->integer('poid')->unsigned();
            $table->integer('product_id')->unsigned();
            $table->integer('quantity')->nullable();
            $table->string('sq_inches');
            $table->string('unit');
            $table->double('rate', 12, 2)->nullable();
            $table->double('amount', 12, 2)->nullable();
            $table->double('gstslab', 12, 2)->nullable();
            $table->double('gstamount', 12, 2)->nullable();
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
        Schema::dropIfExists('popTable');
    }
}
