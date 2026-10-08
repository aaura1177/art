<?php

namespace Database\Seeders;


use Illuminate\Database\Seeder;

class CategoryTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::table('product_category')->delete();
        DB::table('product_category')->insert([
            'name' => 'Wood'
        ]);
    }
}
