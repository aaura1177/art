<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddIsFieldsToRejectRepair extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('reject_repair_ptable', function (Blueprint $table) {
            $table->integer('is_challan_raised')->default(0);
            $table->integer('is_debit_note')->default(0);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('reject_repair_ptable', function (Blueprint $table) {
            //
        });
    }
}
