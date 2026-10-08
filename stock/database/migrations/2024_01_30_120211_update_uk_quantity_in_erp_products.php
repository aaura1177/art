<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class UpdateUkQuantityInErpProducts extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('erp_products', function (Blueprint $table) {
            $table->renameColumn('uk_quantity', 'warehouse_quantity');
            $table->string('site_access', 50)->default('UK');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('erp_products', function (Blueprint $table) {
            //
        });
    }
}
