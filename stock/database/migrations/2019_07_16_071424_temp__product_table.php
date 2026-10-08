<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class TempProductTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('temp_product_table', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('category_id')->unsigned();
            $table->foreign('category_id')->references('id')->on('product_category');
            $table->integer('subcategory_id')->unsigned();
            $table->foreign('subcategory_id')->references('id')->on('product_subcategory');
            $table->string('imageURL')->nullable();
            $table->string('code')->unique();
            $table->string('EAN')->nullable();
            $table->string('HSN')->nullable();
            $table->string('name')->nullable();
            $table->string('finishing')->nullable();
            $table->integer('gstslab')->nullable();
            $table->float('width')->nullable();
            $table->float('height')->nullable();
            $table->float('depth')->nullable();
            $table->float('volume', 12, 4)->nullable();
            $table->string('hardware')->nullable();
            $table->string('addons')->nullable();
            $table->string('remarks')->nullable();
            $table->integer('quantity')->nullable();
            $table->float('boxwidth')->nullable();
            $table->float('boxheight')->nullable();
            $table->float('boxdepth')->nullable();
            $table->double('wholesalevolume', 12, 4)->nullable();
            $table->double('dropshipvolume', 12, 4)->nullable();
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
        //
    }
}
