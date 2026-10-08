<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class CertificateTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::table('certificate')->insert([
            'name' => 'VRIKSH',	
	    	'description' => 'VRIKSH Timber Legality Assessment and Verification standard VRIKSH-STD-01-01 V1.4 EN Certificate code: VRIKSH-GIPL-0011',
        ]);
    }
}
