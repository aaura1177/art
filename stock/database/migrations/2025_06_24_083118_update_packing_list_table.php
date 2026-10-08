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
        Schema::table('packinglist', function (Blueprint $table) {
            $table->boolean('is_sent_for_invoice')->after('status')->nullable()->default(0);
        });
    }

};
