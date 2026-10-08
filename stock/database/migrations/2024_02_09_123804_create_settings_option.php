<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
class CreateSettingsOption extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
        {
            Schema::create('settings_option', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->string('setting_key');
                $table->double('setting_value', 12, 2)->nullable();
                $table->timestamps();
            });
    
            // Add data to the table in UK currency
            DB::table('settings_option')->insert([
                'setting_key' => 'inr_to_pound_conversion_rate',
                'setting_value' => '104.83',
                'created_at' => now(),
                'updated_at' => now()
            ]);
    
            // Add data to the table in US currency
            DB::table('settings_option')->insert([
                'setting_key' => 'inr_to_dollar_conversion_rate',
                'setting_value' => '82.93',
                'created_at' => now(),
                'updated_at' => now()
            ]);
    
            // Add data to the table in EU currency
            DB::table('settings_option')->insert([
                'setting_key' => 'inr_to_euro_conversion_rate',
                'setting_value' => '89.48',
                'created_at' => now(),
                'updated_at' => now()
            ]); 
            DB::table('settings_option')->insert([
                'setting_key' => 'shipping_cost_uk',
                'setting_value' => '110',
                'created_at' => now(),
                'updated_at' => now()
            ]); 
            DB::table('settings_option')->insert([
                'setting_key' => 'storage_cost_uk',
                'setting_value' => '0',
                'created_at' => now(),
                'updated_at' => now()
            ]);
            DB::table('settings_option')->insert([
                'setting_key' => 'shipping_cost_us',
                'setting_value' => '0',
                'created_at' => now(),
                'updated_at' => now()
            ]); 
            DB::table('settings_option')->insert([
                'setting_key' => 'storage_cost_us',
                'setting_value' => '0',
                'created_at' => now(),
                'updated_at' => now()
            ]);
             DB::table('settings_option')->insert([
                'setting_key' => 'shipping_cost_eu',
                'setting_value' => '0',
                'created_at' => now(),
                'updated_at' => now()
            ]); 
            DB::table('settings_option')->insert([
                'setting_key' => 'storage_cost_eu',
                'setting_value' => '0',
                'created_at' => now(),
                'updated_at' => now()
            ]);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('settings_option');
    }
}
