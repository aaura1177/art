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
        Schema::create('containers_allocations', function (Blueprint $table) {
            $table->id();
            $table->string('container_number')->nullable();
            $table->string('tenant')->nullable();
            $table->string('buyer_order_number')->nullable()->comment('tenant#container_number');
            $table->date('planned_date')->nullable();
            $table->boolean('package_list_generated')->default(0)->nullable();
            $table->boolean('package_list_locked')->default(0)->nullable();
            $table->unsignedBigInteger('package_list_id')->nullable();
            $table->unsignedBigInteger('push_to_inventory')->nullable()->default(0);
            $table->enum('status',['pending','in_progress','completed'])->nullable();
            $table->date('completed_and_dispatched_on')->nullable();
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
