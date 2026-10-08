<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateCornerpackTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('cornerpackaging', function (Blueprint $table) {
            $table->bigIncrements('id');
			$table->string('invoice_ids')->nullable();
			$table->string('month')->nullable();
			$table->string('po_no')->nullable();
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
        Schema::dropIfExists('cornerpackaging');
    }
}
