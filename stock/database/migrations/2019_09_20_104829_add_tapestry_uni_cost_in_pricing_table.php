<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddTapestryUniCostInPricingTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('pricingTable', function (Blueprint $table) {
            $table->double('tapestryUnitCost', 12, 2)->default('0');
            $table->string('fabricCost')->nullable()->change();
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
            $table->dropColumn('tapestryUnitCost');
            $table->double('fabricCost', 12, 2)->nullable();
        });
    }
}
