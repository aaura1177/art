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
        Schema::create('sustainability_stage0', function (Blueprint $table) {
            $table->id();
            $table->decimal('total_qty_delivered_by_containers', 10, 2)->nullable()->comment('Total qty delivered by containers');
            $table->decimal('total_weight_delivered_in_containers', 10, 2)->nullable()->comment('Total weight delivered in containers');
            $table->decimal('carbon_sequestration', 10, 2)->nullable();
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
        Schema::dropIfExists('sustainability_stage0');
    }
};
