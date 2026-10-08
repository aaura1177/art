<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddEightProductColumnsInPricingTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
       Schema::table('pricingTable', function (Blueprint $table) {
            $table->double('productheight', 12, 2)->nullable();
            $table->double('productwidth', 12, 2)->nullable();
            $table->double('productdepth', 12, 2)->nullable();
            $table->double('boxheight', 12, 2)->nullable();
            $table->double('boxwidth', 12, 2)->nullable();
            $table->double('boxdepth', 12, 2)->nullable();
            $table->double('wholesalevolume', 12, 4)->nullable();
            $table->double('dropshipvolume', 12, 4)->nullable();
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
             $table->dropColumn('productheight');
             $table->dropColumn('productwidth');
             $table->dropColumn('productdepth');
             $table->dropColumn('boxheight');
             $table->dropColumn('boxwidth');
             $table->dropColumn('boxdepth');
             $table->dropColumn('wholesalevolume');
             $table->dropColumn('dropshipvolume');
        });
    }
}
