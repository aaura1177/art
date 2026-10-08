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
        Schema::create('containers_allocation_details', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('container_allocation_id')->nullable();
            $table->string('product_sku')->nullable();
            $table->integer('product_id')->nullable();
            $table->integer('supplier_id')->nullable();
            $table->boolean('is_in_stock')->nullable()->default(0)->comment('Boolean Value');
            $table->integer('asked_quantity')->nullable();
            $table->enum('status',['po_generated','pending'])->nullable();
            $table->unsignedBigInteger('purchase_order_id')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('containers_allocations');
    }
};
