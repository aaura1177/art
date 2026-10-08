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
        Schema::create('emission_invoice_logs_california', function (Blueprint $table) {
            $table->id();
             $table->unsignedInteger('invoice_id')->nullable();
            $table->date('processed_month'); // e.g., '2025-04-01'
            $table->timestamps();
        
            $table->unique(['invoice_id', 'processed_month'], 'california_inv_id_month_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('emission_invoice_logs_california');
    }
};
