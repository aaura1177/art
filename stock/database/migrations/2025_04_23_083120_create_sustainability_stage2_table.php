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
        Schema::create('sustainability_stage2', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('supplier_id')->nullable();
            $table->enum('type',['Electricity','Transport'])->nullable();
            $table->enum('units',['KWh','Km'])->nullable();
            $table->enum('vehicle_type',['Car','Bike','Scooty','Truck','Trolley'])->nullable();
            $table->enum('fuel_type',['Petrol','Diesel','CNG','Electricity','Hybrid'])->nullable();
            $table->boolean('is_value_in_percentage')->nullable()->default(0)->comment('Are you entring value in percentage?');
            $table->decimal('percentage_value',10,2)->nullable()->comment('Amount of Percentage consumed');
            $table->decimal('actual_value',10,2)->nullable()->comment('Actual Value of Electricity Bill');
            $table->decimal('power_consumed', 10, 2)->nullable()->comment('Power consumed in sawmill by supplier factory');
            $table->decimal('distance_travelled', 10, 2)->nullable()->comment('Distance travelled in km from supplier factory to artisan warehouse');
            $table->integer('number_of_rounds')->nullable()->default(0)->comment('Number of round trips');
            $table->decimal('carbon_emission', 10, 2)->nullable();
            $table->date('month_year')->nullable();
            $table->string('chalan_url')->nullable()->comment('AWS url of a transport chalan');
            $table->string('bill_url')->nullable()->comment('AWS url of a electricty bill');
            $table->enum('created_by',['Admin','Supplier'])->nullable();
            $table->unsignedInteger('created_by_uid')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sustainability_stage2');
    }
};
