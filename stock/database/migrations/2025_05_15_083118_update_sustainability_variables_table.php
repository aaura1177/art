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
        Schema::table('sustainability_variables', function (Blueprint $table) {
            $table->decimal('carbon_emission_rate_for_electronic', 10, 5)->nullable()->default(0); // 0
            $table->decimal('carbon_emission_rate_for_petrol', 10, 5)->nullable()->default(0); // 0
            $table->decimal('carbon_emission_rate_for_hybrid', 10, 5)->nullable()->default(0);// 0
            $table->decimal('carbon_emission_rate_for_solar', 10, 5)->nullable()->default(0); // 0
            $table->decimal('carbon_emission_rate_for_diesel', 10, 5)->nullable()->default(0); // 0
            $table->decimal('carbon_emission_rate_for_cng', 10, 5)->nullable()->default(0); // 0
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
