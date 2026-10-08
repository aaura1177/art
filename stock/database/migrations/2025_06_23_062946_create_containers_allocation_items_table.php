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
        Schema::create('containers_allocation_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('container_allocation_id')->nullable();
            $table->string('sku')->nullable();
            $table->integer('product_id')->nullable();
            $table->integer('qty')->nullable();
            $table->integer('current_allocation')->nullable()->default(0)->comment('No. of qty allocated to supplier');
            $table->decimal('drop_ship_volume',10,3)->nullable()->comment('Volume of the item in cubic meters');
            $table->decimal('physical_volume',10,3)->nullable()->default(0)->comment('Physical Volume of the item in cubic meters');
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('container_allocation_id')->references('id')->on('containers_allocations')->onDelete('cascade')->onUpdate('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('containers_allocation_items');
    }
};
