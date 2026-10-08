<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddHardwareColumnsInPricingTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('pricingTable', function (Blueprint $table) {
            $table->renameColumn('hardwareCost', 'hardwareCost1', 12, 2)->nullable();
            $table->double('hardwareCost2', 12, 2)->nullable();
            $table->double('hardwareCost3', 12, 2)->nullable();
            $table->double('hardwareCost4', 12, 2)->nullable();
            $table->double('hardwareCost5', 12, 2)->nullable();
            $table->tinyInteger('deliveredCostStatus');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('pricing', function (Blueprint $table) {
            //
        });
    }
}
