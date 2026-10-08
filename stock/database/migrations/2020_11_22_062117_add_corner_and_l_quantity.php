<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddCornerAndLQuantity extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('cornerpackaging_details', function (Blueprint $table) {
            $table->integer('corner_quantity')->nullable();
            $table->integer('l_quantity')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('cornerpackaging_details', function (Blueprint $table) {
            //
        });
    }
}
