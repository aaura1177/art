<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddInvUkFields extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('invoice_uk', function (Blueprint $table) {
            $table->string('container_size')->nullable();
            $table->string('delivery_term')->nullable();
            $table->float('deposit')->nullable();
            $table->date('deposit_date')->nullable();
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
