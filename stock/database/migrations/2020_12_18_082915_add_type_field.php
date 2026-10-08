<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up(): void
    {
        Schema::table('purchase_order_consumables', function (Blueprint $table) {
            $table->integer('type')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('purchase_order_consumables', function (Blueprint $table) {
            //
        });
    }
};
