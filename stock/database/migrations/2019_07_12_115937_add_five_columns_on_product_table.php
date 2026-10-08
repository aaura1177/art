<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddFiveColumnsOnProductTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
         Schema::table('product_table', function ($table) {
            $table->float('boxwidth')->default('0');
            $table->float('boxheight')->default('0');
            $table->float('boxdepth')->default('0');
            $table->float('wholesalevolume', 12, 4)->default('0');
            $table->float('dropshipvolume', 12, 4)->default('0');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        //
    }
}
