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
        Schema::create('sustainability_stage5', function (Blueprint $table) {
            $table->id();
            $table->foreignId('port_id')->nullable()->constrained('ports')->cascadeOnDelete();
            $table->integer('container_sent')->nullable();
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
        Schema::dropIfExists('sustainability_stage5');
    }
};
