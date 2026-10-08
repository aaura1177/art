<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddHardwareFieldsToProductTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('product_table', function (Blueprint $table) {
            $table->renameColumn('hardware', 'hardware1', 255)->nullable();
            $table->string('hardware2', 255)->nullable();
            $table->string('hardware3', 255)->nullable();
            $table->string('hardware4', 255)->nullable();
            $table->string('hardware5', 255)->nullable();
            $table->string('upholstry', 255)->nullable();
            $table->string('corner', 255)->nullable();
            $table->string('lhardware', 255)->nullable();
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
