<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;

class PermissionTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $permissions = [
           'product-read',
           'product-edit',
           'quality-read',
           'sample-edit',
           'category-read',
           'category-edit',
           'supplier-read',
           'supplier-edit',
           'buyer-read',
           'buyer-edit',
           'contractor-read',
           'contractor-edit',
           'allocation-read',
           'allocation-edit',
           'rejectrepar-read',
           'rejectrepar-edit',
           'po-read',
           'po-edit',
           'invoice-read',
           'invoice-edit',
           'packing',
           'stockout-read',
           'stockout-edit',
           'report',
           'pricing-read',
           'pricing-edit',
           'courier',
           'hardwares',
           'smallhardware',
           'shipping-lines',
           'packaging',
           'cornerpackaging',
           'settings-edit',
           'performance-edit',
           'courier-read',
           'courier-edit',
           'pb_read',
           'pb-edit',
           'purchase-bill-list',
           'sustainability-read',
           'sustainability-edit',
           'container-allocation-read',
           'container-allocation-edit',
           'push-allocation-to-inventory',
           'can-edit-allocation-quantity',
           'cancel-purchase-order',
           'delete-purchase-order',
           'cancel-consumables-po-order',
           'delete-consumables-po-order',
           'cancel-invoice',
           'delete-invoice',

        ];

        foreach ($permissions as $permission) {
            Permission::create(['name' => $permission]);
        }
    }
}
