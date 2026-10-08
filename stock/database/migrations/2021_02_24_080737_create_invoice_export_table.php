<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateInvoiceExportTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('invoice_export', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->date('realisation_date')->nullable();
            $table->string('realisation_fc')->nullable();
            $table->float('rate')->nullable();
            $table->string('bank_reference')->nullable();
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
        Schema::dropIfExists('invoice_export');
    }
}
