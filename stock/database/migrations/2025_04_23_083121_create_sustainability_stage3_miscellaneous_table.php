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
        Schema::create('sustainability_stage3_miscellaneous', function (Blueprint $table) {
            $table->id();
            $table->string('user_name')->nullable();
            $table->enum('travel_mode',['Air','Train','Car','Bus','Bike'])->nullable();
            $table->string('origin_name')->nullable();
            $table->string('destination_name')->nullable();
            $table->decimal('distance_travelled', 10, 2)->nullable();
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
        Schema::dropIfExists('sustainability_stage3_miscellaneous');
    }
};
