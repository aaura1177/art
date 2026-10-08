<?php

use Illuminate\Database\Seeder;

class StatecodeTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::table('states')->insert([
           
			[	'statename' => 'Jammu & Kashmir',
				'statecode' => '01'
			],
			[	'statename' => 'Himachal Pradesh',
				'statecode' => '02'
			],
			[	'statename' => 'Punjab',
				'statecode' => '03'
			],
			[	'statename' => 'Chandigarh',
				'statecode' => '04'
			],
			[	'statename' => 'Uttarakhand',
				'statecode' => '05'
			],
			[	'statename' => 'Haryana',
				'statecode' => '06'
			],
			[	'statename' => 'Delhi',
				'statecode' => '07'
			],
			[	'statename' => 'Rajasthan',
				'statecode' => '08'
			],
			[	'statename' => 'Uttar Pradesh',
				'statecode' => '09'
			],
			[	'statename' => 'Bihar',
				'statecode' => '10'
			],
			[	'statename' => 'Sikkim',
				'statecode' => '11'
			],
			[	'statename' => 'Arunachal Pradesh',
				'statecode' => '12'
			],
			[	'statename' => 'Nagaland',
				'statecode' => '13'
			],
			[	'statename' => 'Manipur',
				'statecode' => '14'
			],
			[	'statename' => 'Mizoram',
				'statecode' => '15'
			],
			[	'statename' => 'Tripura',
				'statecode' => '16'
			],
			[	'statename' => 'Meghalaya',
				'statecode' => '17'
			],
			[	'statename' => 'Assam',
				'statecode' => '18'
			],
			[	'statename' => 'West Bengal',
				'statecode' => '19'
			],
			[	'statename' => 'Jharkhand',
				'statecode' => '20'
			],
			[	'statename' => 'Orissa',
				'statecode' => '21'
			],
			[	'statename' => 'Chhattisgarh',
				'statecode' => '22'
			],
			[	'statename' => 'Madhya Pradesh',
				'statecode' => '23'
			],
			[	'statename' => 'Gujarat',
				'statecode' => '24'
			],
			[	'statename' => 'Daman & Diu',
				'statecode' => '25'
			],
			[	'statename' => 'Dadra & Nagar Haveli',
				'statecode' => '26'
			],
			[	'statename' => 'Maharashtra',
				'statecode' => '27'
			],
			[	'statename' => 'Andhra Pradesh',
				'statecode' => '28'
			],
			[	'statename' => 'Karnataka',
				'statecode' => '29'
			],
			[	'statename' => 'Goa',
				'statecode' => '30'
			],
			[	'statename' => 'Lakshadweep',
				'statecode' => '31'
			],
			[	'statename' => 'Kerala',
				'statecode' => '32'
			],
			[	'statename' => 'Tamil Nadu',
				'statecode' => '33'
			],
			[	'statename' => 'Puducherry',
				'statecode' => '34'
			],
			[	'statename' => 'Andaman & Nicobar Islands',
				'statecode' => '35'
			],
			[	'statename' => 'Telengana',
				'statecode' => '36'
			],
			[	'statename' => 'Andrapradesh(New)',
				'statecode' => '37'
			]
		]);
    }
}
