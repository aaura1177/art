<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddHardwareQuantityToProduct extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('product_table', function (Blueprint $table) {
            $table->integer('hardware1_quantity')->nullable();
            $table->integer('hardware2_quantity')->nullable();
            $table->integer('hardware3_quantity')->nullable();
            $table->integer('hardware4_quantity')->nullable();
            $table->integer('hardware5_quantity')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('product_table', function (Blueprint $table) {
            //
        });
    }
}
