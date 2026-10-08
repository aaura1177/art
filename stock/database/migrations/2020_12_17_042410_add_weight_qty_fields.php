<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddWeightQtyFields extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('packinglist', function (Blueprint $table) {
            $table->integer('tquantity')->nullable();
            $table->integer('totalwt')->nullable();
            $table->integer('grosswt')->nullable();
            $table->integer('totalbox')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('packinglist', function (Blueprint $table) {
            //
        });
    }
}
