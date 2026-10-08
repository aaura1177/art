<?php

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;

class PermissionPricingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::table('permissions')->insert([
            'name' => 'pricingFullAccess',
            'guard_name' => 'web',
        ]);

        DB::table('model_has_permissions')->insert([
            'permission_id' => 'pricingFullAccess',
            'model_type' => 'App\user',
            'model_id' =>'4',
        ]);
    }
}
