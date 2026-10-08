<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateSmallhardwareSuppliersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('smallhardware_suppliers', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('c_name');
            $table->string('name')->nullable();
            $table->string('address1')->nullable();
            $table->string('address2')->nullable();
            $table->string('city');
            $table->string('state');
            $table->string('country');
            $table->string('postcode')->nullable();
            $table->boolean('gst')->default('0');
            $table->string('gstin')->nullable();
            $table->string('state_code')->nullable();
            $table->string('pan')->nullable();
            $table->string('email')->nullable();
            $table->bigInteger('phone1')->nullable();
            $table->bigInteger('phone2')->nullable();
            $table->integer('tds')->nullable();
            $table->double('tdspercent')->nullable();
            $table->double('gstpercent')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('smallhardware_suppliers');
    }
}
