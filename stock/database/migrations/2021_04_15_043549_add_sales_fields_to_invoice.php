<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddSalesFieldsToInvoice extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('invoice', function (Blueprint $table) {
            $table->string('bl_no')->nullable();
            $table->date('bl_date')->nullable();
            $table->string('egm_no')->nullable();
            $table->date('egm_date')->nullable();
            $table->string('agent_name')->nullable();
            $table->date('eta')->nullable();
            $table->date('etd')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('invoice', function (Blueprint $table) {
            //
        });
    }
}
