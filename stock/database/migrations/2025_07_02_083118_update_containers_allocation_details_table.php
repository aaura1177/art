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
        Schema::table('containers_allocation_details', function (Blueprint $table) {
            $table->unsignedBigInteger('po_consumable_id')->after('purchase_order_id')->nullable();
        });
    }

};
