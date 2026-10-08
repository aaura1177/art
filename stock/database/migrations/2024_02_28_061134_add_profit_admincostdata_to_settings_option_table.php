<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
class AddProfitAdmincostdataToSettingsOptionTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('settings_option', function (Blueprint $table) {
            DB::table('settings_option')->insert([
                'setting_key' => 'profit_percentage',
                'setting_value' => '1',
                'created_at' => now(),
                'updated_at' => now()
            ]);
            DB::table('settings_option')->insert([
                'setting_key' => 'admin_cost_percentage',
                'setting_value' => '1',
                'created_at' => now(),
                'updated_at' => now()
            ]);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('settings_option', function (Blueprint $table) {
            //
        });
    }
}
