<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddAdditionalFieldsInvoiceUk extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('invoice_uk', function (Blueprint $table) {
            $table->string('refund_statement')->nullable();
            $table->float('refund')->default(0);
            $table->float('oceanic_freight')->default(0);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('invoice_uk', function (Blueprint $table) {
            //
        });
    }
}
