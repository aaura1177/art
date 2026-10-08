<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateSamplesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('samples', function (Blueprint $table) {
            $table->bigIncrements('id');
			$table->integer('product_id')->nullable();
			$table->integer('category_id')->nullable();
            $table->integer('subcategory_id')->nullable();
            $table->string('imageURL')->nullable();
            $table->string('code');
            $table->string('name')->nullable();
            $table->string('finishing')->nullable();
            $table->float('width')->nullable();
            $table->float('height')->nullable();
            $table->float('depth')->nullable();
			$table->float('boxwidth')->nullable();
            $table->float('boxheight')->nullable();
            $table->float('boxdepth')->nullable();
            $table->float('wholesalevolume', 12, 4)->nullable();
            $table->float('dropshipvolume', 12, 4)->nullable();
            $table->float('volume', 12, 4)->nullable();
            $table->integer('hardware1')->nullable();
            $table->integer('hardware2')->nullable();
            $table->integer('hardware3')->nullable();
            $table->integer('hardware4')->nullable();
            $table->integer('hardware5')->nullable();
            $table->integer('hardware1_quantity')->nullable();
            $table->integer('hardware2_quantity')->nullable();
            $table->integer('hardware3_quantity')->nullable();
            $table->integer('hardware4_quantity')->nullable();
            $table->integer('hardware5_quantity')->nullable();
            $table->string('addons')->nullable();
            $table->string('remarks')->nullable();
            $table->integer('quantity')->default('0');
            $table->string('upholstry')->nullable();
            $table->string('corner')->nullable();
            $table->string('lhardware')->nullable();
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
        Schema::dropIfExists('samples');
    }
}
