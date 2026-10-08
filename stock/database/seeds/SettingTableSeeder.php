<?php

use Illuminate\Database\Seeder;

class SettingTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::table('settings')->delete();
        DB::table('settings')->insert([
            'c_name' => 'Global Vision Direct (P) Ltd',	
	    	'pan' => 'ABFPB2314J',
	    	'gstin' => '08ABFPB2314J1Z3',
	    	'address1' => 'Global House 21/59 Bhrigu Path',
	    	'address2' => 'Mansarovar',
	    	'city' => 'Jaipur',
	    	'state' => 'Rajasthan',
	    	'country' => 'India',
	    	'postcode' => '302020',
	    	'iec' => '1394000685',
	    	'rbi' => 'JG 000399',
	    	'lut' => 'AA0803180218727 Dated 26-3-2018',
	    	'website' => 'www.globalvisioncompany.com',
	    	'gsp' => 'INREX1394000685EC024',
	    	'email' => 'info@globalvisioncompany.com',
	    	'phone1' => '+91-141-2399922',
	    	'phone2' => '+91-141-2399923',
	    	'logourl' => ''
        ]);
    }
}
