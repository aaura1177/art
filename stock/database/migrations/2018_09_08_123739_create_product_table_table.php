<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateProductTableTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('product_table', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('category_id')->unsigned();
            $table->foreign('category_id')->references('id')->on('product_category');
            $table->integer('subcategory_id')->unsigned();
            $table->foreign('subcategory_id')->references('id')->on('product_subcategory');
            $table->string('imageURL')->nullable();
            $table->string('code');
            $table->string('EAN')->unique();
            $table->string('HSN')->nullable();
            $table->string('name');
            $table->string('finishing')->nullable();
            $table->integer('gstslab');
            $table->float('width')->nullable();
            $table->float('height')->nullable();
            $table->float('depth')->nullable();
            $table->float('volume', 12, 4)->nullable();
            $table->string('hardware')->nullable();
            $table->string('addons')->nullable();
            $table->string('remarks')->nullable();
            $table->integer('quantity')->default('0');
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
        Schema::dropIfExists('product_table');
    }
}
