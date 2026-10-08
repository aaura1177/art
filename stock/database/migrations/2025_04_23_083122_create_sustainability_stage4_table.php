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
        Schema::create('sustainability_stage4', function (Blueprint $table) {
            $table->id();
            $table->integer('number_of_containers')->nullable()->comment('No of containers sent per month');
            $table->decimal('carbon_emission', 10, 3)->nullable();
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
        Schema::dropIfExists('sustainability_stage4');
    }
};
