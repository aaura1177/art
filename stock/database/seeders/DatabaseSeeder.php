<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run()
    {
        $this->call(UsersTableSeeder::class);
        $this->call(SettingTableSeeder::class);
        $this->call(CategoryTableSeeder::class);
        $this->call(StatecodeTableSeeder::class);
        $this->call(CertificateTableSeeder::class);
        $this->call(PermissionPricingSeeder::class);
    }
}
