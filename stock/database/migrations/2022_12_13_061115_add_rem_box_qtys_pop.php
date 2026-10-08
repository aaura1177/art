<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddRemBoxQtysPop extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('popTable', function (Blueprint $table) {
            $table->integer('remqty_box1')->default(0);
            $table->integer('remqty_box2')->default(0);
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
