<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddBoxFieldsPriceFields extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('popTable', function (Blueprint $table) {
            $table->float('box1_rate')->nullable();
            $table->float('box2_rate')->nullable();
            $table->float('box1_amount')->nullable();
            $table->float('box2_amount')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('popTable', function (Blueprint $table) {
            //
        });
    }
}
