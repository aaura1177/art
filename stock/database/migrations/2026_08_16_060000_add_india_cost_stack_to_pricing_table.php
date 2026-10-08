<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pricingTable', function (Blueprint $table): void {
            $table->decimal('indiaShippingCost', 15, 2)->default(0)->after('finalCost');
            $table->decimal('indiaCostPrice', 15, 2)->default(0)->after('indiaShippingCost');
            $table->decimal('indiaAdminCostPercent', 8, 3)->default(0)->after('indiaCostPrice');
            $table->decimal('indiaAdminCost', 15, 2)->default(0)->after('indiaAdminCostPercent');
            $table->decimal('indiaProfitPercent', 8, 3)->default(0)->after('indiaAdminCost');
            $table->decimal('indiaFinalCost', 15, 2)->default(0)->after('indiaProfitPercent');
        });
    }

    public function down(): void
    {
        Schema::table('pricingTable', function (Blueprint $table): void {
            $table->dropColumn([
                'indiaShippingCost',
                'indiaCostPrice',
                'indiaAdminCostPercent',
                'indiaAdminCost',
                'indiaProfitPercent',
                'indiaFinalCost',
            ]);
        });
    }
};
