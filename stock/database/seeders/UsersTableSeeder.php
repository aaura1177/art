<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class UsersTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::table('users')->delete();
        DB::table('users')->insert([
            'firstname' => 'admin',
            'lastname' => 'admin',
            'role' => 'Admin',
            'email' => 'admin@admin.com',
            'password' => bcrypt('password'),
        ]);

        DB::table('users')->insert([
            'firstname' => 'Global',
            'lastname' => 'Vision',
            'role' => 'Admin',
            'email' => 'purchase@globalvisioncompany.com',
            'password' => bcrypt('purchase@123'),
        ]);

        DB::table('users')->insert([
            'firstname' => 'Factory',
            'lastname' => 'Global Vision',
            'role' => 'Factory',
            'email' => 'factory@globalvisioncompany.com',
            'password' => bcrypt('factory@123'),
        ]);

        DB::table('users')->insert([
            'firstname' => 'Anu',
            'lastname' => 'Jain',
            'role' => 'Admin',
            'email' => 'info@globalvisioncompany.com',
            'password' => bcrypt('pricing@123'),
        ]);

        DB::table('roles')->delete();
        DB::table('roles')->insert([
            'name' => 'admin',
            'guard_name' => 'web',
        ]);
        DB::table('roles')->insert([
            'name' => 'factory',
            'guard_name' => 'web',
        ]);
        DB::table('roles')->insert([
            'name' => 'pricingManager',
            'guard_name' => 'web',
        ]);
        DB::table('roles')->insert([
            'name' => 'office',
            'guard_name' => 'web',
        ]);

        DB::table('model_has_roles')->delete();
        DB::table('model_has_roles')->insert([
            'role_id' => '1',
            'model_type' => 'App\user',
            'model_id' =>'1',
        ]);

        DB::table('model_has_roles')->insert([
            'role_id' => '1',
            'model_type' => 'App\user',
            'model_id' =>'2',
        ]);

        DB::table('model_has_roles')->insert([
            'role_id' => '2',
            'model_type' => 'App\user',
            'model_id' =>'3',
        ]);

        DB::table('model_has_roles')->insert([
            'role_id' => '1',
            'model_type' => 'App\user',
            'model_id' =>'4',
        ]);
    }
}
