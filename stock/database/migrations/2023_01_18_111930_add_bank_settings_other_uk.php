<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddBankSettingsOtherUk extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('settingsuk', function (Blueprint $table) {
            $table->text('bank_details_euro')->nullable();
            $table->text('bank_details_dollar')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('settingsuk', function (Blueprint $table) {
            //
        });
    }
}
