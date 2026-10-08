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
        Schema::create('sustainability_stage1', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('supplier_id')->nullable();
            $table->enum('type',['Transport'])->nullable();
            $table->enum('vehicle_type',['Car','Bike','Scooty','Truck','Trolley'])->nullable();
            $table->enum('fuel_type',['Petrol','Diesel','CNG','Electricity','Hybrid'])->nullable();
            $table->decimal('distance_travelled', 10, 2)->nullable()->comment('Distance travelled in km from sawmill to supplier factory');
            $table->integer('number_of_rounds')->nullable()->default(1)->comment('Number of round trips');
            $table->decimal('carbon_emission', 10, 2)->nullable();
            $table->date('month_year')->nullable();
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
        Schema::dropIfExists('sustainability_stage1');
    }
};
