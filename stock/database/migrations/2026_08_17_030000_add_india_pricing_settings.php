<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('settings', function (Blueprint $table): void {
            $table->decimal('india_volumetric_weight_kg', 12, 2)
                ->default(5000)
                ->after('australia_volumetric_weight_kg');
        });

        $defaults = [
            'india_admin_cost_percentage' => DB::table('settings_option')
                ->where('setting_key', 'admin_cost_percentage')
                ->value('setting_value') ?? 0,
            'india_profit_percentage' => DB::table('settings_option')
                ->where('setting_key', 'profit_percentage')
                ->value('setting_value') ?? 0,
        ];

        foreach ($defaults as $key => $value) {
            DB::table('settings_option')->updateOrInsert(
                ['setting_key' => $key],
                [
                    'setting_value' => $value,
                    'updated_at' => now(),
                    'created_at' => now(),
                ]
            );
        }
    }

    public function down(): void
    {
        DB::table('settings_option')
            ->whereIn('setting_key', [
                'india_admin_cost_percentage',
                'india_profit_percentage',
            ])
            ->delete();

        Schema::table('settings', function (Blueprint $table): void {
            $table->dropColumn('india_volumetric_weight_kg');
        });
    }
};
