<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreatePackaging extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('packaging', function (Blueprint $table) {
            $table->bigIncrements('id');
			$table->integer('product_id');
			$table->float('box1_height');
			$table->float('box1_width');
			$table->float('box1_depth');
			$table->float('box2_height');
			$table->float('box2_width');
			$table->float('box2_depth');
			$table->float('box1_sqinch');
			$table->float('box2_sqinch');
			$table->float('no_of_boxes');
			$table->float('box1_ply');
			$table->float('box2_ply');
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
        Schema::dropIfExists('packaging');
    }
}
