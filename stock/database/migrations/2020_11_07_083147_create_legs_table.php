<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateLegsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('legs', function (Blueprint $table) {
            $table->bigIncrements('id');
			$table->integer('product_id')->nullable();
			$table->string('leg_design')->nullable();
			$table->string('qty')->nullable();
			$table->float('price')->nullable();
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
        Schema::dropIfExists('legs');
    }
}
