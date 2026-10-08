<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddPricingSettingFieldsInSettingTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('settings', function (Blueprint $table) {
            $table->double('volWt', 12, 2)->default(4000);
            $table->double('shippingCost2', 12, 2)->default(30);
            $table->double('StorageCost', 12, 2)->default(11);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('settings', function (Blueprint $table) {
             $table->dropColumn('volWt');
              $table->dropColumn('shippingCost2');
               $table->dropColumn('StorageCost');
        });
    }
}
