<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateErpHistoryManagerTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('erp_history_manager', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->integer('sheet_id');
            $table->string('sku');
            $table->integer('quantity');
            $table->string('type');
            $table->date('date');
            $table->text('remark')->nullable();
            $table->integer('stock')->default(0);
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
        Schema::dropIfExists('erp_history_manager');
    }
}
