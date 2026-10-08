<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        // Adjust these SQL statements based on what changes you want
        DB::statement("ALTER TABLE sustainability_stage3_employee MODIFY COLUMN `vehicle_type` ENUM('Car','Bike','Scooty','Truck','Trolley','Bus','Auto') NULL");

        // Add new boolean column
        Schema::table('sustainability_stage3', function (Blueprint $table) {
            $table->boolean('is_solar')->default(0)->comment('Is Solar');
        });

    }

    public function down()
    {
        // Revert back to the original enum values
        DB::statement("ALTER TABLE sustainability_stage3_employee MODIFY COLUMN `vehicle_type` ENUM('Car','Bike','Scooty','Truck','Trolley') NULL");
       
        // Drop the is_solar column
        Schema::table('sustainability_stage2', function (Blueprint $table) {
            $table->dropColumn('is_solar');
        });
   
    }
};
