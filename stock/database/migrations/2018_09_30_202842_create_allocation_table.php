<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateAllocationTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('allocation', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('contractor_id')->unsigned();
            $table->foreign('contractor_id')->references('id')->on('contractor');
            $table->integer('product_id')->unsigned();
            $table->foreign('product_id')->references('id')->on('product_table');
            $table->float('quantity')->default('0');
            $table->string('finish')->nullable();
            $table->string('refno')->nullable();
            $table->double('ucost', 12, 2)->default('0');
            $table->string('vol_unit')->nullable();
            $table->double('tvol', 12, 4)->default('0');
            $table->double('tcost', 12, 2)->default('0');
            $table->string('remarks')->nullable();
            $table->timestamps();
        });

        DB::update("ALTER TABLE allocation AUTO_INCREMENT = 1001;");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('allocation');
    }
}
