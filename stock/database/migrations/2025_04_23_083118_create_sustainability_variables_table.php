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
        Schema::create('sustainability_variables', function (Blueprint $table) {
            $table->id();
            $table->decimal('carbon_content', 10, 2)->nullable(); // 0.5
            $table->decimal('conversion_factor', 10, 2)->nullable(); // 3.67
            $table->decimal('carbon_emission_rate', 10, 5)->nullable(); // 0.5182 kg
            $table->decimal('carbon_emission_factor_per_kwh', 10, 2)->nullable(); // 0.82 kg
            $table->decimal('carbon_emission_per_kg_parcel', 10, 2)->nullable(); // 2.96 kg
            $table->decimal('mundra_port_distance', 10, 2)->nullable(); // 860
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sustainability_variables');
    }
};
