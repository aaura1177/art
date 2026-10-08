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
            $table->decimal('carbon_emission_per_kg_parcel_us', 10, 5)->nullable()->default(0); // 0
            $table->decimal('carbon_emission_per_kg_parcel_uk', 10, 5)->nullable()->default(0); // 0
            $table->decimal('carbon_emission_per_kg_parcel_eu', 10, 5)->nullable()->default(0); // 0
            $table->decimal('carbon_emission_per_kg_parcel_ca', 10, 5)->nullable()->default(0); // 0
            $table->decimal('carbon_emission_per_kg_parcel_in', 10, 5)->nullable()->default(0); // 0

            $table->dropColumn('carbon_emission_per_kg_parcel');
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
