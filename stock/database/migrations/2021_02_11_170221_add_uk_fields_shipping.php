<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddUkFieldsShipping extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('shipping', function (Blueprint $table) {
            $table->float('ocean_freight')->nullable();
            $table->float('thc_uk')->nullable();
            $table->float('handling_charges')->nullable();
            $table->float('other')->nullable();
            $table->float('total_india')->nullable();
            $table->float('total_uk')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('shipping', function (Blueprint $table) {
            //
        });
    }
}
