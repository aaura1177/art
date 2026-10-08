<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class ModifyHardwareFieldsInProductTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('product_table', function (Blueprint $table) {
            $table->integer('hardware1')->change();
            $table->integer('hardware2')->change();
            $table->integer('hardware3')->change();
            $table->integer('hardware4')->change();
            $table->integer('hardware5')->change();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('product_table', function (Blueprint $table) {
            //
        });
    }
}
