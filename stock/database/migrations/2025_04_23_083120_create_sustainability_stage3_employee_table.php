<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('sustainability_stage3_employee', function (Blueprint $table) {
            $table->id();
            $table->integer('emp_id')->nullable();
            $table->string('user_name')->nullable();
            $table->enum('type', ['Transport'])->nullable();
            $table->enum('vehicle_type', ['Car', 'Bike', 'Scooty'])->nullable();
            $table->enum('fuel_type', ['Petrol', 'Diesel', 'CNG', 'Hybrid', 'Electricity'])->nullable();
            $table->enum('units', ['Km'])->nullable();
            $table->decimal('distance_travelled', 10, 2)->nullable()->comment('Distance travelled in km One Side');
            $table->integer('number_of_rounds')->nullable()->default(0)->comment('Number of round trips');
            $table->decimal('carbon_emission', 10, 2)->nullable();
            $table->integer('days')->unsigned()->nullable()->comment('Number of days user visits office');
            $table->date('month_year')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sustainability_stage3_employee');
    }
};
