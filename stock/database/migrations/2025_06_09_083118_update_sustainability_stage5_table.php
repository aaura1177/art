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
        Schema::table('sustainability_stage5', function (Blueprint $table) {
            $table->decimal('total_weight_delivered', 10, 2)->after('container_sent')->nullable()->default(0); // 0
            $table->decimal('total_qty_sent', 10, 2)->after('total_weight_delivered')->nullable()->default(0); 
        });
    }

};
