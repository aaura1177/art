<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddBoxFieldsCartonpoFields extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('popTable', function (Blueprint $table) {
            $table->float('box1_height');
			$table->float('box1_width');
			$table->float('box1_depth');
			$table->float('box2_height');
			$table->float('box2_width');
			$table->float('box2_depth');
			$table->float('box1_sqinch');
			$table->float('box2_sqinch');
			$table->float('box1_ply');
			$table->float('box2_ply');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('popTable', function (Blueprint $table) {
            //
        });
    }
}
