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
        Schema::create('sustainability_stage3', function (Blueprint $table) {
            $table->id();
            $table->enum('office_type',['Factory','Office','Uk Office'])->nullable();
            $table->enum('type',['Electricity'])->nullable();
            $table->enum('units',['KWh'])->nullable();
            $table->decimal('power_consumed', 10, 2)->nullable()->comment('Power consumed in office or factory');
            $table->decimal('carbon_emission', 10, 2)->nullable();
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
        Schema::dropIfExists('sustainability_stage3');
    }
};
