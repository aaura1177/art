<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateWpCustomersInfosTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('wp_customers_infos', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->integer('wp_customer_id')->nullable();
            $table->string('wp_customer_name')->nullable();
            $table->string('wp_customer_email');
            $table->timestamps();
            $table->string('site_access')->default('UK');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('wp_customers_infos');
    }
}
