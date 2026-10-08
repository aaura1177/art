<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddBuyer2InPricingTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('pricingTable', function (Blueprint $table) {
            $table->integer('buyer_id2')->unsigned()->nullable();
            $table->foreign('buyer_id2')->references('id')->on('temp_buyer_table');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('pricingTable', function (Blueprint $table) {
             $table->dropColumn('buyer_id2');
        });
    }
}
