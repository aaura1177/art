<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateCornerpackagingTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('cornerpackaging_details', function (Blueprint $table) {
            $table->bigIncrements('id');
			$table->integer('cornerpackaging_id')->nullable();
			$table->integer('employee_id')->nullable();
			$table->integer('quantity')->nullable();
			$table->integer('amount')->nullable();
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
        Schema::dropIfExists('cornerpackaging_details');
    }
}
