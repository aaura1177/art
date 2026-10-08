<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddPricingFieldsToTempBuyer extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('temp_buyer_table', function (Blueprint $table) {
			$table->float('final_price_percent')->nullable();
			$table->float('cost_adjustments')->nullable();
			$table->string('courierType')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('temp_buyer_table', function (Blueprint $table) {
            //
        });
    }
}
