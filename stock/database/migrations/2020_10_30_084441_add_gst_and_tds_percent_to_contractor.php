<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddGstAndTdsPercentToContractor extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('contractor', function (Blueprint $table) {
            $table->integer('tds')->nullable();
            $table->float('tdspercent')->nullable();
            $table->float('gstpercent')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('contractor', function (Blueprint $table) {
            //
        });
    }
}
