<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddGstAndTdsPercentToUphoContractor extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('upholestry_contractors', function (Blueprint $table) {
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
        Schema::table('upholestry_contractors', function (Blueprint $table) {
            //
        });
    }
}
